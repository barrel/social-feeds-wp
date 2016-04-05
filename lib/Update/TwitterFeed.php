<?php
namespace Barrel\SocialFeeds\Update;

/**
 * Pulls the latest social posts and saves them to custom post type.
 */
class TwitterFeed extends Feed {

  public static $network = 'twitter';

  /**
   * Initializes the Instagram API and fetches the latest posts
   */
  function __construct($options = false) {
    parent::__construct($options);

    $this->twitter = new \TwitterAPIExchange(array(
      'consumer_key' => get_option('twitter_consumer_key'),
      'consumer_secret' => get_option('twitter_consumer_secret'),
      'oauth_access_token' => get_option('twitter_access_token'),
      'oauth_access_token_secret' => get_option('twitter_access_token_secret'),
    ));

    $this->fetch();
  }

  /**
   * Fetches Twitter posts based on the provided usernames and hashtags
   */
  function fetch() {
    $updated_posts = array();

    $usernames = $this->get_option_terms('twitter_feed_username');

    if($usernames) {
      foreach ($usernames as $username) {
        $url = 'https://api.twitter.com/1.1/statuses/user_timeline.json';
        $query = '?screen_name='.$username.'&count=30&exclude_replies=1'; 

        $feed =
          $this->twitter->setGetfield($query)
                        ->buildOauth($url, 'GET')
                        ->performRequest();

        $feed = json_decode($feed, true);

        $this->save($feed, $updated_posts);
      }
    }

    $this->updated = $updated_posts;
  }

  function parse($social_post) {
    return array(
      'permalink' => 'https://twitter.com/'.$social_post['user']['screen_name'].'/status/'.$social_post['id'],
      'text' => $social_post['text'],
      'image' => false,
      'video' => false,
      'username' => $social_post['user']['screen_name'],
      'created' => strtotime($social_post['created_at']),
      'details' => json_encode($social_post)
    );
  }

}