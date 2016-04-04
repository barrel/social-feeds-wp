<?php
namespace Barrel\SocialFeeds\Admin;
use MetzWeb\Instagram\Instagram;

/**
 * Creates the admin page for configuring plugin settings.
 */
class TwitterSettingsPage extends Page {

  static $page_title = 'Twitter Settings';
  static $menu_title = 'Twitter Settings';
  static $parent_slug = 'edit.php?post_type=social-post';
  static $capability = 'manage_options';
  static $menu_slug = 'twitter-settings';

  function add_options_page() {
    parent::add_options_page();
    remove_submenu_page(self::$parent_slug, 'post-new.php?post_type=social-post');
  }

  function initialize_options() {
    add_option('twitter_consumer_key', '');
    add_option('twitter_consumer_secret', '');
    add_option('twitter_access_token', '');
    add_option('twitter_access_token_secret', '');
    // add_option('twitter_feed_hashtag', '');
    add_option('twitter_feed_username', '');

    add_settings_section(
      'twitter_settings',
      'Twitter Settings',
      function() {
        // echo '<p>Enter your credentials for the Twitter API.</p>';
      },
      self::$menu_slug
    );

    add_settings_field(
      'twitter_consumer_key',
      'Consumer Key',
      array($this, 'render_text_setting'),
      self::$menu_slug,
      'twitter_settings',
      array('twitter_consumer_key')
    );

    register_setting('twitter_settings', 'twitter_consumer_key');

    add_settings_field(
      'twitter_consumer_secret',
      'Consumer Secret',
      array($this, 'render_text_setting'),
      self::$menu_slug,
      'twitter_settings',
      array('twitter_consumer_secret')
    );

    register_setting('twitter_settings', 'twitter_consumer_secret');

    add_settings_field(
      'twitter_access_token',
      'Access Token',
      array($this, 'render_text_setting'),
      self::$menu_slug,
      'twitter_settings',
      array('twitter_access_token')
    );

    register_setting('twitter_settings', 'twitter_access_token');

    add_settings_field(
      'twitter_access_token_secret',
      'Access Token Secret',
      array($this, 'render_text_setting'),
      self::$menu_slug,
      'twitter_settings',
      array('twitter_access_token_secret')
    );

    register_setting('twitter_settings', 'twitter_access_token_secret');

    add_settings_field(
      'twitter_feed_hashtag',
      'Hashtag(s)',
      array($this, 'render_text_setting'),
      self::$menu_slug,
      'twitter_settings',
      array('twitter_feed_hashtag', '<em>Enter one or more tags separated with spaces. (Optional)</em>')
    );

    register_setting('twitter_settings', 'twitter_feed_hashtag');

    add_settings_field(
      'twitter_feed_username',
      'Username(s)',
      array($this, 'render_text_setting'),
      self::$menu_slug,
      'twitter_settings',
      array('twitter_feed_username', '<em>Enter one or more usernames separated with spaces. (Optional)</em>')
    );

    register_setting('twitter_settings', 'twitter_feed_username');

    add_settings_field(
      'sync_now',
      'Sync Now',
      array($this, 'render_sync_now_setting'),
      self::$menu_slug,
      'twitter_settings',
      array()
    );

    register_setting('twitter_settings', 'sync_now');
  }

  function display_options_page() {
    ?>
    <div class="wrap">
      <h2><?= self::$page_title ?></h2>
      <form id="social_feeds_settings" action="options.php" method="post">
        <?php
        settings_fields( 'twitter_settings' );
        do_settings_sections( self::$menu_slug );
        submit_button();
        ?>
      </form>
    </div>
    <?php
  }

  function render_sync_now_setting() {
    ?>
    <p>Sync all posts since <input type="date" name="twitter_sync_start" value="" placeholder="Select a date..." /> <button type="button" name="twitter_sync_now_button" data-network="twitter" class="button" disabled>Sync Now</button><img class="social-feeds-spinner" src="<?= admin_url('images/loading.gif'); ?>"></p>
    <p><label><input type="checkbox" name="twitter_sync_update"> Update details for existing posts</label></p>
    <?php
  }

}