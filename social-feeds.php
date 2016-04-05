<?php
/*
Plugin Name: Social Feeds
Description: Curate posts from social feeds.
Version: 0.1.0
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

    if(isset($_REQUEST['instagram_sync_now'])) {
      foreach (InstagramSettingsPage::$settings as $id => $setting) {
        if(isset($_REQUEST[$id])) {
          update_option($id, $_REQUEST[$id]);
        }
      }

      $sync_now = new Update\InstagramFeed(array(
        'sync_start_date' => $_REQUEST['instagram_sync_now'],
        'sync_update' => @$_REQUEST['instagram_sync_update']
      ));

      wp_send_json(array(
        'updated' => $sync_now->updated
      ));
    } else if(isset($_REQUEST['twitter_sync_now'])) {
      foreach (TwitterSettingsPage::$settings as $id => $setting) {
        if(isset($_REQUEST[$id])) {
          update_option($id, $_REQUEST[$id]);
        }
      }

      $sync_now = new Update\TwitterFeed(array(
        'sync_start_date' => $_REQUEST['twitter_sync_now'],
        'sync_update' => @$_REQUEST['twitter_sync_update']
      ));

      wp_send_json(array(
        'updated' => $sync_now->updated
      ));
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

    new Admin\SelectPostsPage;
    new Admin\InstagramSettingsPage;
    new Admin\TwitterSettingsPage;

    add_action('admin_menu', function() {
      remove_submenu_page('edit.php?post_type=social-post', 'post-new.php?post_type=social-post');
    });

    add_action('admin_enqueue_scripts', function($hook) {
      wp_register_style(
        'social-feeds-admin',
        plugins_url('assets/css/social-feeds-admin.min.css', __FILE__),
        false,
        '1.0.0'
      );
      wp_enqueue_style('social-feeds-admin');

      wp_enqueue_script(
        'social-feeds-modernizr',
        plugins_url('assets/js/social-feeds-modernizr.min.js', __FILE__)
      );

      wp_enqueue_script('jquery-ui-core');
      wp_enqueue_script('jquery-ui-datepicker' );
      wp_enqueue_style('jquery-ui-css', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css');

      wp_enqueue_script(
      'social-feeds-admin',
        plugins_url('assets/js/social-feeds-admin.min.js', __FILE__),
        array('jquery'),
        '1.0.0'
      );
    });

    add_filter('manage_social-post_posts_columns', function($columns) {
      return array_slice($columns, 0, 1) +
        array('thumbnail' => '') +
        array_slice($columns, 1, count($columns)-2) +
        array(
          'date' => 'Date',
          'date-created' => 'Date Created'
        );
    });

    add_filter('manage_edit-social-post_sortable_columns', function($columns) {
      $columns['date-created'] = 'date_created';
      return $columns;
    });

    add_action('manage_social-post_posts_custom_column', function($column_name, $post_id) {
      if($column_name == 'thumbnail') {
        the_post_thumbnail('thumbnail');
      } else if($column_name == 'date-created') {
        $created = (int) get_post_meta($post_id, 'social_post_created', true);
        $timezone = (int) get_option('gmt_offset');
        $offset = $timezone * 60 * 60;
        $created += $offset;
        echo 'Posted<br>';
        echo date('Y/m/d g:i:s a', $created);
      }
    }, 10, 2);

    add_action('pre_get_posts', function( $query ) {
      if(!is_admin()) {
        return;
      }

      $post_type = $query->get('post_type');

      if($post_type === 'social-post') {
        $orderby = $query->get( 'orderby');

        if(empty($orderby) || $orderby === 'date_created') {
          $query->set('meta_key', 'social_post_created');
          $query->set('orderby', 'meta_value_num');
        }
      }
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