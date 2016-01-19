<?php
namespace Barrel\SocialFeeds\Cron;
use MetzWeb\Instagram\Instagram;

require_once(ABSPATH.'wp-admin/includes/media.php');
require_once(ABSPATH.'wp-admin/includes/file.php');
require_once(ABSPATH.'wp-admin/includes/image.php');

/**
 * Pulls the latest social posts and saves them to custom post type.
 */
class Update {

  /**
   * Initializes the Instagram API and fetches the latest posts
   */
  function __construct($options = false) {
    $this->instagram = new Instagram(array(
      'apiKey' => get_option('instagram_client_id'),
      'apiSecret' => get_option('instagram_client_secret'),
      'apiCallback' => home_url('/?callback=instagram_auth')
    ));

    $token = get_option('instagram_access_token');

    if(!empty($token)) {
      $this->instagram->setAccessToken($token);
    }

    if(isset($options['sync_start_date'])) {
      $this->start_time = strtotime($options['sync_start_date']);
    }

    $this->fetch_instagram();
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

  function save_instagram_feed($feed, &$updated = array()) {
    if(empty($feed->data)) {
      return;
    }
    
    foreach ($feed->data as $social_post) {
      $created_time = (int) $social_post->created_time;

      if($this->start_time === false || $created_time >= $this->start_time) {
        $id = $this->update_social_post($social_post);
        array_push($updated, $id);
      }
    }

    $last_post_time = (int) $feed->data[(count($feed->data)-1)]->created_time;

    if($last_post_time >= $this->start_time && $feed->pagination->next_url) {
      $this->save_instagram_feed($this->instagram->pagination($feed), $updated);
    }
  }

  /**
   * Fetches Instagram posts based on the provided usernames and hashtags
   */
  function fetch_instagram() {
    $updated_posts = array();

    $tags = $this->get_option_terms('instagram_feed_hashtag');

    if($tags) {
      foreach ($tags as $tag) {
        $feed = $this->instagram->getTagMedia($tag, 30);

        $this->save_instagram_feed($feed, $updated_posts);
      }
    }

    $usernames = $this->get_option_terms('instagram_feed_username');

    if($usernames) {
      foreach ($usernames as $username) {
        $user = $this->instagram->searchUser($username, 1);
        $user_id = $user->data[0]->id;
        $feed = $this->instagram->getUserMedia($user_id, 30);

        $this->save_instagram_feed($feed, $updated_posts);
      }
    }

    $this->updated = $updated_posts;
  }

  /**
   * Handles a social API response by adding or updating posts in custom post type.
   */
  function update_social_post($social_post) {
    $existing = get_posts(array(
      'post_type' => 'social-post',
      'post_status' => 'any',
      'meta_key' => 'social_post_permalink',
      'meta_value' => $social_post->link
    ));

    if(!empty($existing)) {
      $id = $existing[0]->ID;
    } else {
      $id = wp_insert_post(array(
        'post_type' => 'social-post',
        'post_title' => $social_post->caption->text
      ));

      update_post_meta($id, 'social_post_username', $social_post->user->username);
      update_post_meta($id, 'social_post_permalink', $social_post->link);
      update_post_meta($id, 'social_post_image', $social_post->images->standard_resolution->url);
      update_post_meta($id, 'social_post_created', $social_post->created_time);
      update_post_meta($id, 'social_post_details', json_encode($social_post));

      if(isset($social_post->videos)) {
        update_post_meta($id, 'social_post_video', $social_post->videos->standard_resolution->url);
      }

      media_sideload_image($social_post->images->standard_resolution->url, $id);

      $images = array_values(get_attached_media('image', $id));

      set_post_thumbnail($id, $images[0]->ID);
    }

    return $id;
  }

}