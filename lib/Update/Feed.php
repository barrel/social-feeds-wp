<?php
namespace Barrel\SocialFeeds\Update;
use Misd\Linkify\Linkify;
use WP_Query;

require_once(ABSPATH.'wp-admin/includes/media.php');
require_once(ABSPATH.'wp-admin/includes/file.php');
require_once(ABSPATH.'wp-admin/includes/image.php');

/**
 * Pulls the latest social posts and saves them to custom post type.
 */
class Feed {

  function __construct($options = array()) {
    if(isset($options['sync_start_date'])) {
      $this->start_time = strtotime($options['sync_start_date']);
    }

    if(isset($options['sync_update'])) {
      $this->sync_update = $options['sync_update'];
    }

    if(isset($options['sync_publish'])) {
      $this->sync_publish = $options['sync_publish'];
    }

    $this->linkify = new Linkify();
  }

  function save($feed, &$updated = array()) {
    if(static::$network === 'instagram') {
      $results = $feed->data;
    } else if(static::$network === 'linkedin') {
      $results = $feed['values'];
    } else {
      $results = $feed;
    }

    if(empty($results)) {
      return;
    }
    
    foreach ($results as $social_post) {
      $post_info = $this->parse($social_post);

      if(@$this->start_time === false || $post_info['created'] >= $this->start_time) {
        $id = $this->update_social_post($post_info);
        array_push($updated, $id);
      }
    }

    if(static::$network === 'instagram') {
      $last_post_time = (int) $results[(count($results)-1)]->created_time;

      if($last_post_time >= $this->start_time && @$feed->pagination->next_url) {
        $this->save($this->instagram->pagination($feed, 30), $updated);
      }
    }

    $max = get_option( static::$network.'_max_publish' );

    $this->prune_social_post(static::$network, $max);

  }

  function get_option_terms($option_name) {
    $option_string = trim(get_option($option_name));

    if(strpos($option_name, 'hashtag') !== false) {
      $option_string = str_replace('#', '', $option_string);
    }

    if(!empty($option_string)) {
      return explode(' ', $option_string);
    } else {
      return false;
    }
  }

  /**
   * Parse common fields out of the API response for a social network post
   */
  function parse($social_post) {
    return false;
  }

  /**
   * Handles a social API response by adding or updating posts in custom post type.
   */
  function update_social_post($post_info) {
    $title = $post_info['text'];
    $content = $this->linkify->process($post_info['text']);

    $existing = get_posts(array(
      'post_type' => 'social-post',
      'post_status' => 'any',
      'meta_key' => 'social_post_permalink',
      'meta_value' => $post_info['permalink']
    ));

    $update_details = false;

    if(empty($existing)) {
      $update_details = true;

      $post_status = @$this->sync_publish ? 'publish' : 'draft';

      // Create the post, saving caption to title
      $id = wp_insert_post(array(
        'post_type' => 'social-post',
        'post_title' => $title,
        'post_content' => $content,
        'post_status' => $post_status
      ));

      // Save the unique identifier (permalink) and media to post

      update_post_meta($id, 'social_post_permalink', $post_info['permalink']);

      if($post_info['image']) {
        update_post_meta($id, 'social_post_image', $post_info['image']);
        media_sideload_image($post_info['image'], $id);
        $images = array_values(get_attached_media('image', $id));
        set_post_thumbnail($id, $images[0]->ID);
      }

      if($post_info['video']) {
        update_post_meta($id, 'social_post_video', $post_info['video']);
      }

    } else {
      // Find existing post.
      $id = $existing[0]->ID;

      if(@$this->sync_update) {
        $update_details = true;

        // Update title of existing post
        wp_update_post(array(
          'ID' => $id,
          'post_title' => $title,
          'post_content' => $content
        ));
      }
    }

    // Update details for new posts (or archived posts if sync_update is set).
    if($update_details) {
      update_post_meta($id, 'social_post_username', $post_info['username']);
      update_post_meta($id, 'social_post_created', $post_info['created']);
      update_post_meta($id, 'social_post_details', $post_info['details']);
    }

    // Save post type
    wp_set_object_terms($id, array($post_info['type']), 'social_types');

    return $id;
  }

  /**
   * Prunes older posts of the custom post type and taxonomy.
   */
  function prune_social_post($network = '', $max = -1) {

    if(!$max || $max <= 0) return;
    
    $latest_posts = new WP_Query( array(
      'post_type'  =>  'social-post',
      'post_status' => 'publish',
      'meta_key' => 'social_post_created',
      'orderby' => 'meta_value',
      'posts_per_page' => -1,
      'tax_query' => array(
          array(
              'taxonomy' => 'social_types',
              'field' => 'slug',
              'terms' => $network,
          ),
        )
    ) );

    $post_count = 1;
    $ids = '';
    while ( $latest_posts->have_posts() ) { $latest_posts->the_post();
      if($post_count > $max) {
        $id = get_the_ID();
        wp_update_post(array(
          'ID' => $id,
          'post_status' => 'draft'
        ));
        $ids .= $id.',';
      }
      $post_count++;
    }
    update_option('run_update', $ids);

    wp_reset_postdata();

  }

}