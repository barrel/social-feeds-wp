<?php
namespace Barrel\SocialFeeds\Admin;
use Barrel\SocialFeeds\SocialFeeds;
use MetzWeb\Instagram\Instagram;

/**
 * Creates the admin page for configuring plugin settings.
 */
class InstagramSettingsPage extends Page {

  static $page_title = 'Instagram Settings';
  static $menu_title = 'Instagram Settings';
  static $capability = 'manage_options';
  static $menu_slug = 'instagram-settings';
  static $settings_section = 'instagram_settings';

  static $settings = array(
    'instagram_client_id' => array(
      'type' => 'text',
      'title' => 'Client ID',
    ),
    'instagram_client_secret' => array(
      'type' => 'text',
      'title' => 'Client Secret',
    ),
    'instagram_access_token' => array(
      'type' => 'text',
      'title' => 'Access Token',
    ),
    'instagram_feed_hashtag' => array(
      'type' => 'text',
      'title' => 'Hashtag(s)',
      'args' => array('<em>Enter one or more tags separated with spaces. (Optional)</em>'),
    ),
    'instagram_feed_username' => array(
      'type' => 'text',
      'title' => 'Username(s)',
      'args' => array('<em>Enter one or more usernames separated with spaces. (Optional)</em>'),
    ),
    'instagram_auto_publish' => array(
      'type' => 'checkbox',
      'title' => 'Post Status',
      'args' => array('Automatically publish new posts', '<em>Leave unchecked to create new posts as drafts.</em>')
    ),
    'instagram_max_publish' => array(
      'type' => 'number',
      'title' => 'Post Limit',
      'args' => array('Maximum number of posts to save', '<em>Set to "0" to keep all posts indefinitely.</em>')
    ),
    'instagram_cron' => array(
      'type' => 'cron',
      'title' => 'Scheduled Sync',
      'args' => array('instagram')
    ),
    'instagram_sync_now' => array(
      'type' => 'sync_now',
      'title' => 'One-Time Sync',
      'args' => array('instagram')
    )
  );

  function __construct() {
    parent::__construct();

    $this->instagram = new Instagram(array(
      'apiKey' => get_option('instagram_client_id'),
      'apiSecret' => get_option('instagram_client_secret'),
      'apiCallback' => home_url('/?callback=instagram_auth')
    ));
    $oauth_url = $this->instagram->getLoginUrl();
    $oauth_link = '<a href="'.$oauth_url.'">Generate Access Token</a>';
    self::$settings['instagram_access_token']['args'] = array($oauth_link);
  }

  function add_options_page() {
    if(!isset(SocialFeeds::$options['enable_instagram']) || SocialFeeds::$options['enable_instagram'] === true) {
      parent::add_options_page();
    }
  }

}