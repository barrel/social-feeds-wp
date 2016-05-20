<?php
namespace Barrel\SocialFeeds\Update;
use MetzWeb\Instagram\Instagram;

/**
 * Pulls the latest social posts and saves them to custom post type.
 */
class InstagramFeed extends Feed {

  public static $network = 'instagram';

  /**
   * Initializes the Instagram API and fetches the latest posts
   */
  function __construct($options = false) {
    parent::__construct($options);

    $this->instagram = new Instagram(array(
      'apiKey' => get_option('instagram_client_id'),
      'apiSecret' => get_option('instagram_client_secret'),
      'apiCallback' => home_url('/?callback=instagram_auth')
    ));

    $token = get_option('instagram_access_token');

    if(!empty($token)) {
      $this->instagram->setAccessToken($token);
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

  function parse($social_post) {
    return array(
      'permalink' => $social_post->link,
      'text' => $social_post->caption->text,
      'image' => $social_post->images->standard_resolution->url,
      'video' => @$social_post->videos->standard_resolution->url,
      'username' => $social_post->user->username,
      'created' => $social_post->created_time,
      'details' => json_encode($social_post),
      'type' => 'Instagram'
    );
  }

}