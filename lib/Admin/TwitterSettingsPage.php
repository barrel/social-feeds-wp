<?php
namespace Barrel\SocialFeeds\Admin;
use MetzWeb\Instagram\Instagram;

/**
 * Creates the admin page for configuring plugin settings.
 */
class TwitterSettingsPage extends Page {

  static $network = 'twitter';

  static $page_title = 'Twitter Settings';
  static $menu_title = 'Twitter Settings';
  static $parent_slug = 'edit.php?post_type=social-post';
  static $capability = 'manage_options';
  static $menu_slug = 'twitter-settings';
  static $settings_section = 'twitter_settings';

  static $settings = array(
    'twitter_consumer_key' => array(
      'type' => 'text',
      'title' => 'Consumer Key',
    ),
    'twitter_consumer_secret' => array(
      'type' => 'text',
      'title' => 'Consumer Secret',
    ),
    'twitter_access_token' => array(
      'type' => 'text',
      'title' => 'Access Token',
    ),
    'twitter_access_token_secret' => array(
      'type' => 'text',
      'title' => 'Access Token Secret',
    ),
    'twitter_feed_hashtag' => array(
      'type' => 'text',
      'title' => 'Hashtag(s)',
      'args' => array('<em>Enter one or more tags separated with spaces. (Optional)</em>'),
    ),
    'twitter_feed_username' => array(
      'type' => 'text',
      'title' => 'Username(s)',
      'args' => array('<em>Enter one or more usernames separated with spaces. (Optional)</em>'),
    ),
    'sync_now' => array(
      'type' => 'sync_now',
      'title' => 'Sync Now',
    )
  );

}