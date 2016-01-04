<?php
/*
Plugin Name: Social Feeds
Description: Curate posts from social feeds.
Version: 0.0.1
*/

namespace Barrel\SocialFeeds;

require('vendor/autoload.php');

class SocialFeeds {

  function __construct() {

    add_action('init', array($this, 'init_post_type'));

    if(is_admin()) {
      $this->init_admin();
    }

    if(isset($_SERVER['REDIRECT_URL']) &&
      $_SERVER['REDIRECT_URL'] == '/instagram-cron/') {
      new Cron\Update;
    }

  }

  function init_post_type() {

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

}

new SocialFeeds;