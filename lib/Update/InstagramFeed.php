<?php
namespace Barrel\SocialFeeds\Update;
use MetzWeb\Instagram\Instagram;

/**
 * Pulls the latest social posts and saves them to custom post type.
 */
class InstagramFeed extends Feed {

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

    if(isset($options['sync_update'])) {
      $this->sync_update = $options['sync_update'];
    }

    $this->fetch();
  }

  /**
   * Fetches Instagram posts based on the provided usernames and hashtags
   */
  function fetch() {
    $updated_posts = array();

    $tags = $this->get_option_terms('instagram_feed_hashtag');

    if($tags) {
      foreach ($tags as $tag) {
        $feed = $this->instagram->getTagMedia($tag, 30);

        $this->save($feed, $updated_posts);
      }
    }

    $usernames = $this->get_option_terms('instagram_feed_username');

    if($usernames) {
      foreach ($usernames as $username) {
        $user = $this->instagram->searchUser($username, 1);
        $user_id = $user->data[0]->id;
        $feed = $this->instagram->getUserMedia($user_id, 30);

        $this->save($feed, $updated_posts);
      }
    }

    $this->updated = $updated_posts;
  }

  function save($feed, &$updated = array()) {
    if(empty($feed->data)) {
      return;
    }
    
    foreach ($feed->data as $social_post) {
      $created_time = (int) $social_post->created_time;

      if($this->start_time === false || $created_time >= $this->start_time) {
        $post_info = $this->parse_social_post('instagram', $social_post);
        $id = $this->update_social_post($post_info);
        array_push($updated, $id);
      }
    }

    $last_post_time = (int) $feed->data[(count($feed->data)-1)]->created_time;

    if($last_post_time >= $this->start_time && @$feed->pagination->next_url) {
      $this->save($this->instagram->pagination($feed, 30), $updated);
    }
  }

}