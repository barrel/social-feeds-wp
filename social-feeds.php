<?php
/*
Plugin Name: Social Feeds
Description: Curate posts from social feeds.
Version: 0.1.0
*/

/*
Hide API credentials from dashboard by defining in your wp-config 

  define( 'SF_TWITTER_CONSUMER_KEY', 'YOUR KEY' );
  define( 'SF_TWITTER_CONSUMER_SECRET', 'YOUR SECRET' );
  define( 'SF_TWITTER_ACCESS_TOKEN', 'YOUR ACCESS TOKEN' );
  define( 'SF_TWITTER_ACCESS_TOKEN_SECRET', 'YOUR ACCESS TOKEN SECRET' );

  define( 'SF_LINKEDIN_COMPANY_NAME', 'YOUR COMPANY PAGE NAME' );
  define( 'SF_LINKEDIN_CLIENT_ID', 'YOUR CLIENT ID' );
  define( 'SF_LINKEDIN_CLIENT_SECRET', 'YOUR CLIENT SECRET' );

  define( 'SF_MAX_PUBLISHED', 'NUMBER OF MAXIMUM POSTS TO ALLOW' );

*/

namespace Barrel\SocialFeeds;
use MetzWeb\Instagram\Instagram;
use League\OAuth2\Client\Provider\LinkedIn;
use WP_Query;

require('vendor/autoload.php');

/**
 * Base plugin class, initializes theme and admin functionality.
 */
class SocialFeeds {

  static $options = array();
  static $is_plugin = false;

  function __construct($options = array()) {

    $this->init_post_type();

    if(is_admin()) {
      $this->init_admin();
    }

    $settings = array_merge(
      array_keys(Admin\InstagramSettingsPage::$settings),
      array_keys(Admin\TwitterSettingsPage::$settings),
      array_keys(Admin\LinkedInSettingsPage::$settings)
    );

    foreach ($settings as $key) {
      $value = null;
      $setting_const = strtoupper('sf_'.$key);
      
      if(defined($setting_const)) {
        $value = constant($setting_const);
      } else if(array_key_exists($key, $options)) (
        $value = $options[$key];
      )

      if($value !== null) {
        update_option($key, $value);
      }
    }

    add_action('init', array($this, 'init'), 50);

  }

  /**
   * Initialize post type
   */
  function init_post_type() {

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
      'supports' => array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
      'taxonomies' => array( 'social_types' ),
    ));

    /** Register the custom post type for social posts. */
    register_taxonomy(
        'social_types',
        'social-post',
        array(
            'labels' => array(
                'name' => 'Social Post Type',
            ),
            'show_ui' => false,
            'show_tagcloud' => false,
            'hierarchical' => false,
            'query_var'    => false,
            'public' => false,
            'rewrite' => false,
        )
    );

    // Add each type to taxonomy
    wp_insert_term('Instagram', 'social_types');
    wp_insert_term('Twitter', 'social_types');
    wp_insert_term('LinkedIn', 'social_types');

  }

  /**
   * Initialize plugin
   */
  function init() {

    if(isset($_REQUEST['callback'])) {
      if($_REQUEST['callback'] == 'instagram_auth') {
        $this->retrieve_access_token('instagram');
      } else if($_REQUEST['callback'] == 'linkedin_auth') {
        $this->retrieve_access_token('linkedin');
      }
    }

    if(isset($_REQUEST['instagram_sync_now'])) {
      foreach (Admin\InstagramSettingsPage::$settings as $id => $setting) {
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
      foreach (Admin\TwitterSettingsPage::$settings as $id => $setting) {
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
    } else if(isset($_REQUEST['linkedin_sync_now'])) {
      foreach (Admin\LinkedInSettingsPage::$settings as $id => $setting) {
        if(isset($_REQUEST[$id])) {
          update_option($id, $_REQUEST[$id]);
        }
      }

      $sync_now = new Update\LinkedInFeed(array(
        'sync_start_date' => $_REQUEST['linkedin_sync_now'],
        'sync_update' => @$_REQUEST['linkedin_sync_update']
      ));

      wp_send_json(array(
        'updated' => $sync_now->updated
      ));
    }
    
    $this->init_cron_event('linkedin');
    $this->init_cron_event('twitter');
    
    $this->init_cron('linkedin');
    $this->init_cron('twitter');

  }

  /**
   * Initialize admin functionality
   */
  function init_admin() {

    new Admin\SelectPostsPage;
    new Admin\InstagramSettingsPage;
    new Admin\TwitterSettingsPage;
    new Admin\LinkedInSettingsPage;

    add_action('admin_menu', function() {
      remove_submenu_page('edit.php?post_type=social-post', 'post-new.php?post_type=social-post');
    });

    add_action('admin_enqueue_scripts', function($hook) {
      if(self::$is_plugin) {
        $admin_css_uri = plugins_url('assets/css/social-feeds-admin.min.css', __FILE__);
        $admin_modernizr_uri = plugins_url('assets/js/social-feeds-modernizr.min.js', __FILE__);
        $admin_js_uri = plugins_url('assets/js/social-feeds-admin.min.js', __FILE__);
      } else {
        $theme_path = get_template_directory();
        $file_path = __FILE__;
        $dir = dirname(substr($file_path, strlen($theme_path)));
        $theme_uri = get_template_directory_uri();

        $admin_css_uri = $theme_uri.$dir.'/assets/css/social-feeds-admin.min.css';
        $admin_modernizr_uri = $theme_uri.$dir.'/assets/js/social-feeds-modernizr.min.js';
        $admin_js_uri = $theme_uri.$dir.'/assets/js/social-feeds-admin.min.js';
      }

      wp_register_style(
        'social-feeds-admin',
        $admin_css_uri,
        false,
        '1.0.0'
      );
      wp_enqueue_style('social-feeds-admin');

      wp_enqueue_script(
        'social-feeds-modernizr',
        $admin_modernizr_uri
      );

      wp_enqueue_script('jquery-ui-core');
      wp_enqueue_script('jquery-ui-datepicker');
      wp_enqueue_style('jquery-ui-css', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css');

      wp_enqueue_script(
      'social-feeds-admin',
        $admin_js_uri,
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
   * Schedule cron for network
   */
  function init_cron($network = '') {
    $cron = get_option($network.'_cron');
    $cron_name = 'social_feeds_auto_update_'.$network;
    $next = wp_next_scheduled( $cron_name, array($network) );
    if( $cron && $cron != '' ) {
      if(!$next) {
        wp_schedule_event( mktime(14, 0, 0), $cron, $cron_name, array($network) );
      }
    } else {
      wp_unschedule_event( $next, $cron_name );
    }
  }

  /**
   * Create event for network cron
   */
  function init_cron_event($network = '') {
    $cron_name = 'social_feeds_auto_update_'.$network;
    add_action( $cron_name, array( $this, 'run_update' ), 51, 1 );
  }

  /**
   * Handle the Instagram OAuth response to retrieve an access token
   */
  function retrieve_access_token($network) {
    if($network === 'instagram') {
      $instagram = new Instagram(array(
        'apiKey' => get_option('instagram_client_id'),
        'apiSecret' => get_option('instagram_client_secret'),
        'apiCallback' => home_url('/?callback=instagram_auth')
      ));

      $code = $_REQUEST['code'];
      $result = $instagram->getOAuthToken($code);

      if(isset($result->access_token)) {
        update_option('instagram_access_token', $result->access_token);

        $settings_page = admin_url('edit.php?post_type=social-post&page=instagram-settings');

        wp_redirect($settings_page);
        exit;
      }
    } else if($network === 'linkedin') {
      $linkedin = new LinkedIn(array(
        'clientId'          => get_option('linkedin_client_id'),
        'clientSecret'      => get_option('linkedin_client_secret'),
        'redirectUri'       => home_url('/?callback=linkedin_auth'),
      ));

      $code = $_REQUEST['code'];
      $result = $linkedin->getAccessToken('authorization_code', array(
        'code' => $code
      ));
      $token = $result->getToken();

      if(isset($token)) {
        update_option('linkedin_access_token', $token);

        $company_name = get_option('linkedin_company_name');
        if( $company_name) {
          $resource = '/v1/companies';
          $params = array('oauth2_access_token' => $token, 'format' => 'json', 'is-company-admin' => 'true');
          $url = 'https://api.linkedin.com' . $resource . '?' . http_build_query($params);
          $context = stream_context_create(array('http' => array('method' => 'GET')));
          $response = file_get_contents($url, false, $context);
          $companies = json_decode($response, true);
          foreach($companies['values'] as $company) {
            if(strtolower($company_name) == strtolower($company['name'])) {
              update_option('linkedin_company_id', $company['id']);
            }
          }
        }

        $settings_page = admin_url('edit.php?post_type=social-post&page=linkedin-settings');

        wp_redirect($settings_page);
        exit;
      }
    }
  }

  /**
   * Process cron update
   */
  public static function run_update($network = '') {

    $sync_date = time();

    $last_post = new WP_Query( array(
      'post_type'  =>  'social-post',
      'post_status' => 'publish',
      'meta_key' => 'social_post_created',
      'orderby' => 'meta_value',
      'order' => 'DESC',
      'posts_per_page' => 1,
      'tax_query' => array(
          array(
              'taxonomy' => 'social_types',
              'field' => 'slug',
              'terms' => $network,
          ),
        )
    ) );

    if ( $last_post->have_posts() ) {
      while ($last_post->have_posts()) { $last_post->the_post();
        $sync_date = get_post_time('U', true);
      }
    }

    if( $network == 'instagram' ) {

      $sync_now = new Update\InstagramFeed(array(
        'sync_start_date' => $sync_date,
        'sync_publish' => get_option( $network.'_cron_publish' )
      ));

    } else if( $network == 'twitter' ) {

      $sync_now = new Update\TwitterFeed(array(
        'sync_start_date' => $sync_date,
        'sync_publish' => get_option( $network.'_cron_publish' )
      ));

    } else if( $network == 'linkedin' ) {

      $sync_now = new Update\LinkedInFeed(array(
        'sync_start_date' => $sync_date,
        'sync_publish' => get_option( $network.'_cron_publish' )
      ));

    }

  }

}

/** Instantiate automatically when installed as a plugin. */
if(strpos(__FILE__, 'wp-content/plugins') !== false) {
  SocialFeeds::$is_plugin = true;
  new SocialFeeds;
}
