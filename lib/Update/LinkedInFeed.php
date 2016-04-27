<?php
namespace Barrel\SocialFeeds\Update;
use League\OAuth2\Client\Provider\LinkedIn;

/**
 * Pulls the latest social posts and saves them to custom post type.
 */
class LinkedInFeed extends Feed {

  public static $network = 'linkedin';

  /**
   * Initializes the Instagram API and fetches the latest posts
   */
  function __construct($options = false) {
    parent::__construct($options);
    $this->fetch();
  }

  /**
   * Fetches LinkedIn posts based on the provided usernames and hashtags
   */
  function fetch() {
    $updated_posts = array();

    $client_id = get_option('linkedin_company_id');
    $access_token = get_option('linkedin_access_token');

    if( $client_id && $access_token ) {

      $resource = '/v1/companies/'.$client_id.'/updates';
      $params = array('oauth2_access_token' => $access_token, 'format' => 'json');
      $url = 'https://api.linkedin.com' . $resource . '?' . http_build_query($params);
      $context = stream_context_create(array('http' => array('method' => 'GET')));
      $response = file_get_contents($url, false, $context);
      $feed = json_decode($response, true);

      $this->save($feed, $updated_posts);
    }

    $this->updated = $updated_posts;
  }

  function parse($social_post) {
    if(!isset($social_post['updateContent']['companyStatusUpdate']) || empty($social_post['updateContent']['companyStatusUpdate'])) return array();
    return array(
      'permalink' => 'https://linkedin.com/company/'.$social_post['updateContent']['company']['name'].'/#'.$social_post['updateContent']['companyStatusUpdate']['share']['id'],
      'text' => $social_post['updateContent']['companyStatusUpdate']['share']['comment'],
      'image' => $social_post['updateContent']['companyStatusUpdate']['share']['content']['submittedImageUrl'],
      'video' => false,
      'username' => $social_post['updateContent']['company']['name'],
      'created' => ( $social_post['timestamp']/1000 ),
      'details' => json_encode($social_post),
      'type' => 'LinkedIn'
    );
  }

}