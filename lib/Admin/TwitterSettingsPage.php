<?php
namespace Barrel\SocialFeeds\Admin;
use Barrel\SocialFeeds\SocialFeeds;
use MetzWeb\Instagram\Instagram;

/**
 * Creates the admin page for configuring plugin settings.
 */
class TwitterSettingsPage extends Page {

  static $page_title = 'Twitter Settings';
  static $menu_title = 'Twitter Settings';
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
    'twitter_sync_now' => array(
      'type' => 'sync_now',
      'title' => 'Sync Now',
      'args' => array('twitter')
    ),
    'twitter_cron' => array(
      'type' => 'cron',
      'title' => 'Auto-Update',
      'args' => array('twitter')
    ),
    'twitter_cron_publish' => array(
      'type' => 'hidden',
    ),
  );

  function add_options_page() {
    if(!isset(SocialFeeds::$options['enable_twitter']) || SocialFeeds::$options['enable_twitter'] === true) {
      parent::add_options_page();
    }
  }

}