<?php
namespace Barrel\SocialFeeds\Update;
// use MetzWeb\Instagram\Instagram;

/**
 * Pulls the latest social posts and saves them to custom post type.
 */
class TwitterFeed extends Feed {

  /**
   * Initializes the Instagram API and fetches the latest posts
   */
  function __construct($options = false) {
    $this->twitter = new \TwitterAPIExchange(array(
      'consumer_key' => get_option('twitter_consumer_key'),
      'consumer_secret' => get_option('twitter_consumer_secret'),
      'oauth_access_token' => get_option('twitter_access_token'),
      'oauth_access_token_secret' => get_option('twitter_access_token_secret'),
    ));

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

  function save($feed, &$updated = array()) {
    if(empty($feed)) {
      return;
    }
    
    foreach ($feed as $social_post) {
      $created_time = strtotime($social_post['created_at']);

      if($this->start_time === false || $created_time >= $this->start_time) {
        $post_info = $this->parse_social_post('twitter', $social_post);
        $id = $this->update_social_post($post_info);
        array_push($updated, $id);
      }
    }

    // $last_post_time = (int) $feed->data[(count($feed->data)-1)]->created_time;

    // if($last_post_time >= $this->start_time && @$feed->pagination->next_url) {
    //   $this->save($this->instagram->pagination($feed, 30), $updated);
    // }
  }

}