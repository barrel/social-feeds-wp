<?php
namespace Barrel\SocialFeeds\Admin;

/**
 * Base admin page class, initializes common functionality for admin pages.
 */
class Page {

  static $page_title = 'Settings';
  static $menu_title = 'Settings';
  static $capability = 'manage_options';
  static $menu_slug = 'settings';

  static $settings = false;
  static $settings_section = false;

  function __construct() {
    add_action('admin_init', array($this, 'initialize_options'));
    add_action('admin_menu', array($this, 'add_options_page'));
  }

  /**
   * Register any options for this admin page (called on admin_init).
   */
  function initialize_options() {
    if(static::$settings_section) {
      add_settings_section(
        static::$settings_section,
        static::$page_title,
        function() {
          // echo '<p></p>';
        },
        static::$menu_slug
      );
    }

    if(static::$settings) {
      foreach (static::$settings as $id => $setting) {
        if($setting['type'] !== 'sync_now') {
          add_option($id, '');
        }

        $args = array_merge(array($id), (@$setting['args'] ?: array()));

        add_settings_field(
          $id,
          $setting['title'],
          array($this, 'render_'.$setting['type'].'_setting'),
          static::$menu_slug,
          static::$settings_section,
          $args
        );

        register_setting(static::$settings_section, $id);
      }
    }
  }

  /**
   * Adds this page to the admin menu using static properties (can be overridden by child classes).
   */
  function add_options_page() {
    add_submenu_page(
      'edit.php?post_type=social-post',
      static::$page_title,
      static::$menu_title,
      static::$capability,
      static::$menu_slug,
      array($this, 'display_options_page')
    );
  }

  /**
   * Render the HTML for the admin page content.
   */
  function display_options_page() {
    ?>
    <div class="wrap">
      <h2><?= static::$page_title ?></h2>
      <form id="social_feeds_settings" action="options.php" method="post">
        <?php
        if(static::$settings_section) {
          settings_fields( static::$settings_section );
        }
        if(static::$menu_slug) {
          do_settings_sections( static::$menu_slug );
          submit_button();
        }
        ?>
      </form>
    </div>
    <?php
  }

  /**
   * Render the HTML for a single text field setting.
   */
  function render_text_setting($args) {
    $option = get_option( $args[0] );
    
    echo '<input type="text" id="twitter" name="'.$args[0].'" value="' . htmlspecialchars($option) . '" size="50" />';
    
    if(isset($args[1])) {
      echo '<br/>'.$args[1];
    }
  }

  function render_sync_now_setting($args) {
    $network = $args[1];
    ?>
    <p>Sync all posts since <input type="date" name="<?= $network ?>_sync_start" value="" placeholder="Select a date..." /> <button type="button" name="<?= $network ?>_sync_now_button" data-network="twitter" class="button" disabled>Sync Now</button><img class="social-feeds-spinner" src="<?= admin_url('images/loading.gif'); ?>"></p>
    <p><label><input type="checkbox" name="<?= $network ?>_sync_update"> Update details for existing posts</label></p>
    <?php
  }

}