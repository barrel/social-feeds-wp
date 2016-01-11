<?php
/*
Plugin Name: Social Feeds
Description: Curate posts from social feeds.
Version: 0.0.2
*/

namespace Barrel\SocialFeeds;
use MetzWeb\Instagram\Instagram;

require('vendor/autoload.php');

/**
 * Base plugin class, initializes theme and admin functionality.
 */
class SocialFeeds {

  function __construct() {

    add_action('init', array($this, 'init'));

    if(is_admin()) {
      $this->init_admin();
    }

  }

  /**
   * Initialize plugin
   */
  function init() {

    if(isset($_REQUEST['callback']) &&
      $_REQUEST['callback'] == 'instagram_auth') {
      $this->retrieve_access_token();
    }

    /** Register the custom post type for social posts. */
    register_post_type('social-post', array(
      'labels' => array(
        'name' => 'Social Posts',
        'singular_name' => 'Social Post',
        'menu_name' => 'Social Posts',
      ),
      'public' => false,
      'show_ui' => true,
      'show_in_nav_menus' => false,
      'show_in_menu' => true,
      'show_in_admin_bar' => false,
      'menu_icon' => 'dashicons-thumbs-up',
      'supports' => array('title', 'thumbnail', 'custom-fields')
    ));

  }

  /**
   * Initialize admin functionality
   */
  function init_admin() {

    new Admin\SelectPosts;
    new Admin\Settings;

    add_action('admin_enqueue_scripts', function($hook) {
      wp_register_style(
        'social-feeds-admin',
        plugins_url('assets/css/social-feeds-admin.min.css', __FILE__),
        false,
        '1.0.0'
      );
      wp_enqueue_style('social-feeds-admin');

      wp_enqueue_script(
        'social-feeds-admin',
        plugins_url('assets/js/social-feeds-admin.min.js', __FILE__),
        array('jquery'),
        '1.0.0'
      );
    });

  }

  /**
   * Handle the Instagram OAuth response to retrieve an access token
   */
  function retrieve_access_token() {
    $instagram = new Instagram(array(
      'apiKey' => get_option('instagram_client_id'),
      'apiSecret' => get_option('instagram_client_secret'),
      'apiCallback' => home_url('/?callback=instagram_auth')
    ));

    $code = $_REQUEST['code'];
    $result = $instagram->getOAuthToken($code);

    if(isset($result->access_token)) {
      update_option('instagram_access_token', $result->access_token);

      $settings_page = admin_url('edit.php?post_type=social-post&page=feed-settings');

      \wp_redirect($settings_page);
      exit;
    }
  }

}

/** Instantiate the plugin class. */
new SocialFeeds;