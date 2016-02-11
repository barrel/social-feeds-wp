<?php
namespace Barrel\SocialFeeds\Admin;
use MetzWeb\Instagram\Instagram;

/**
 * Creates the admin page for configuring plugin settings.
 */
class SettingsPage extends Page {

  static $page_title = 'Social Feed Settings';
  static $menu_title = 'Feed Settings';
  static $parent_slug = 'edit.php?post_type=social-post';
  static $capability = 'manage_options';
  static $menu_slug = 'feed-settings';

  function add_options_page() {
    parent::add_options_page();
    remove_submenu_page(self::$parent_slug, 'post-new.php?post_type=social-post');
  }

  function initialize_options() {
    add_option('instagram_client_id', '');
    add_option('instagram_client_secret', '');
    add_option('instagram_access_token', '');
    add_option('instagram_feed_hashtag', '');
    add_option('instagram_feed_username', '');

    add_settings_section(
      'instagram_settings',
      'Instagram Settings',
      function() {
        // echo '<p>Enter your credentials for the Instagram API.</p>';
      },
      self::$menu_slug
    );

    add_settings_field(
      'instagram_client_id',
      'Client ID',
      array($this, 'render_text_setting'),
      self::$menu_slug,
      'instagram_settings',
      array('instagram_client_id')
    );

    register_setting('instagram_settings', 'instagram_client_id');

    add_settings_field(
      'instagram_client_secret',
      'Client Secret',
      array($this, 'render_text_setting'),
      self::$menu_slug,
      'instagram_settings',
      array('instagram_client_secret')
    );

    register_setting('instagram_settings', 'instagram_client_secret');

    $instagram = new Instagram(array(
      'apiKey' => get_option('instagram_client_id'),
      'apiSecret' => get_option('instagram_client_secret'),
      'apiCallback' => home_url('/?callback=instagram_auth')
    ));

    $oauth_link = $instagram->getLoginUrl();

    add_settings_field(
      'instagram_access_token',
      'Access Token',
      array($this, 'render_text_setting'),
      self::$menu_slug,
      'instagram_settings',
      array('instagram_access_token', '<a href="'.$oauth_link.'">Generate Access Token</a>')
    );

    register_setting('instagram_settings', 'instagram_access_token');

    add_settings_field(
      'instagram_feed_hashtag',
      'Hashtag(s)',
      array($this, 'render_text_setting'),
      self::$menu_slug,
      'instagram_settings',
      array('instagram_feed_hashtag', '<em>Enter one or more tags separated with spaces. (Optional)</em>')
    );

    register_setting('instagram_settings', 'instagram_feed_hashtag');

    add_settings_field(
      'instagram_feed_username',
      'Username(s)',
      array($this, 'render_text_setting'),
      self::$menu_slug,
      'instagram_settings',
      array('instagram_feed_username', '<em>Enter one or more usernames separated with spaces. (Optional)</em>')
    );

    register_setting('instagram_settings', 'instagram_feed_username');

    add_settings_field(
      'sync_now',
      'Sync Now',
      array($this, 'render_sync_now_setting'),
      self::$menu_slug,
      'instagram_settings',
      array()
    );

    register_setting('instagram_settings', 'sync_now');
  }

  function display_options_page() {
    ?>
    <div class="wrap">
      <h2><?= self::$page_title ?></h2>
      <form id="social_feeds_settings" action="options.php" method="post">
        <?php
        settings_fields( 'instagram_settings' );
        do_settings_sections( self::$menu_slug );
        submit_button();
        ?>
      </form>
    </div>
    <?php
  }

  function render_sync_now_setting() {
    ?>
    <p>Sync all posts since <input type="date" name="instagram_sync_start" value="" placeholder="Select a date..." /> <button type="button" name="instagram_sync_now_button" class="button" disabled>Sync Now</button><img class="social-feeds-spinner" src="<?= admin_url('images/loading.gif'); ?>"></p>
    <p><label><input type="checkbox" name="instagram_sync_update"> Update details for existing posts</label></p>
    <?php
  }

}