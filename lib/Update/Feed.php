<?php
namespace Barrel\SocialFeeds\Update;

require_once(ABSPATH.'wp-admin/includes/media.php');
require_once(ABSPATH.'wp-admin/includes/file.php');
require_once(ABSPATH.'wp-admin/includes/image.php');

/**
 * Pulls the latest social posts and saves them to custom post type.
 */
class Feed {

  function __construct() {
    if(isset($options['sync_start_date'])) {
      $this->start_time = strtotime($options['sync_start_date']);
    }

    if(isset($options['sync_update'])) {
      $this->sync_update = $options['sync_update'];
    }
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
  function parse_social_post($network, $social_post) {
    if($network === 'twitter') {
      $url = 'https://twitter.com/'.$social_post['user']['screen_name'].'\/status\/'.$social_post['id'];
      return array(
        'permalink' => $url,
        'text' => $social_post['text'],
        'image' => false,
        'video' => false,
        'username' => $social_post['user']['screen_name'],
        'created' => strtotime($social_post['created_at']),
        'details' => json_encode($social_post)
      );
    } else if($network === 'instagram') {
      return array(
        'permalink' => $social_post->link,
        'text' => $social_post->caption->text,
        'image' => $social_post->images->standard_resolution->url,
        'video' => @$social_post->videos->standard_resolution->url,
        'username' => $social_post->user->username,
        'created' => $social_post->created_time,
        'details' => json_encode($social_post)
      );
    }
  }

  /**
   * Handles a social API response by adding or updating posts in custom post type.
   */
  function update_social_post($post_info) {
    $existing = get_posts(array(
      'post_type' => 'social-post',
      'post_status' => 'any',
      'meta_key' => 'social_post_permalink',
      'meta_value' => $post_info['permalink']
    ));

    $update_details = false;

    if(empty($existing)) {
      $update_details = true;

      // Create the post, saving caption to title
      $id = wp_insert_post(array(
        'post_type' => 'social-post',
        'post_title' => $post_info['text']
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
          'post_title' => $post_info['text']
        ));
      }
    }

    // Update details for new posts (or archived posts if sync_update is set).
    if($update_details) {
      update_post_meta($id, 'social_post_username', $post_info['username']);
      update_post_meta($id, 'social_post_created', $post_info['created']);
      update_post_meta($id, 'social_post_details', $post_info['details']);
    }

    return $id;
  }

}