<?php
namespace Barrel\SocialFeeds\Cron;
use MetzWeb\Instagram\Instagram;

/**
 * Pulls the latest social posts and saves them to custom post type.
 */
class Update {

  /**
   * Initializes the Instagram API and fetches the latest posts
   */
  function __construct() {
    $this->instagram = new Instagram(array(
      'apiKey' => get_option('instagram_client_id'),
      'apiSecret' => get_option('instagram_client_secret'),
      'apiCallback' => home_url('/?callback=instagram_auth')
    ));

    $token = get_option('instagram_access_token');

    if(!empty($token)) {
      $this->instagram->setAccessToken($token);
    }

    $this->fetch_instagram();
  }

  /**
   * Fetches Instagram posts based on the provided usernames and hashtags
   */
  function fetch_instagram() {
    $hashtag_string = trim(get_option('instagram_feed_hashtag'));

    if(!empty($hashtag_string)) {
      $hashtag_string = str_replace('#', '', $hashtag_string);
      $tags = explode(' ', $hashtag_string);

      foreach ($tags as $tag) {
        $feed = $this->instagram->getTagMedia($tag, 30);
        
        foreach ($feed->data as $social_post) {
          $this->update_social_post($social_post);
        }
      }
    }

    $username_string = trim(get_option('instagram_feed_username'));

    if(!empty($username_string)) {
      $usernames = explode(' ', $username_string);

      foreach ($usernames as $name) {
        $users = $this->instagram->searchUser($name, 1);
        $user_id = $users->data[0]->id;
        $feed = $this->instagram->getUserMedia($user_id, 30);

        foreach ($feed->data as $social_post) {
          $this->update_social_post($social_post);
        }
      }
    }

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
  }

}