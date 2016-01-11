<?php
namespace Barrel\SocialFeeds\Admin;

/**
 * Base admin page class, initializes common functionality for admin pages.
 */
class AdminPage {

  static $page_title = 'Settings';
  static $menu_title = 'Settings';
  static $parent_slug = 'edit.php?post_type=social-post';
  static $capability = 'manage_options';
  static $menu_slug = 'settings';

  function __construct() {
    add_action('admin_init', array($this, 'initialize_options'));
    add_action('admin_menu', array($this, 'add_options_page'));
  }

  /**
   * Register any options for this admin page (called on admin_init).
   */
  function initialize_options() {
    
  }

  /**
   * Adds this page to the admin menu using static properties (can be overridden by child classes).
   */
  function add_options_page() {
    add_submenu_page(
      static::$parent_slug,
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
      <form action="options.php" method="post">
        <?php
        do_settings_sections( static::$menu_slug );
        submit_button();
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

}