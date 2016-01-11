<?php
namespace Barrel\SocialFeeds\Admin;
use Barrel\SocialFeeds\Cron\Update;

/**
 * Creates the admin page for selecting posts to publish.
 */
class SelectPosts extends AdminPage {

  static $page_title = 'Select Social Posts';
  static $menu_title = 'Select Posts';
  static $menu_slug = 'select-posts';

  /**
   * Constructs the admin page and sets up actions
   */
  function __construct() {
    parent::__construct();

    add_action('admin_post_curate_social_feed', array($this, 'curate_feed'));
  }

  function add_options_page() {
    parent::add_options_page();

    // remove_submenu_page(self::$parent_slug, self::$parent_slug);
    remove_submenu_page(self::$parent_slug, 'post-new.php?post_type=social-post');
  }

  function display_options_page() {
    new Update();

    $social_post_query = new \WP_Query(array(
      'post_type' => 'social-post',
      'post_status' => 'any',
      'posts_per_page' => -1,
      'meta_key' => 'social_post_created',
      'orderby' => 'meta_value'
    ));
    ?>
    <div class="wrap">
      <h2><?= self::$page_title ?></h2>
      <p>Select posts to make visible on the front end.</p>
      <form action="<?= admin_url('admin-post.php'); ?>" method="post">
        <input type="hidden" name="action" value="curate_social_feed">
        <?php submit_button(); ?>
        <div class="social-post-cards">
          <?php
            if($social_post_query->have_posts()) {
              while ($social_post_query->have_posts()) { $social_post_query->the_post();
                $item_id = get_the_ID();
                $item_checked = (get_post_status($item_id) == 'publish') ? 'checked' : '';
                $item_image = get_post_meta($item_id, 'social_post_image', true);
                $item_user = get_post_meta($item_id, 'social_post_username', true);
                $item_content = get_the_title();
                ?>
                <label class="social-post-card">
                  <input type="checkbox" name="social-post-publish[]" value="<?= $item_id ?>" <?= $item_checked ?>>
                  <span class="label">Publish</span>
                  <div class="social-post-details">
                    <?php the_post_thumbnail('thumbnail'); ?>
                    <p><?= $item_content ?></p>
                    <p class="user"><?= $item_user ?></p>
                  </div>
                </label>
                <?php
              }

              wp_reset_postdata();
            }
          ?>
        </div>
      </form>
    </div>
    <?php
  }

  /**
   * Handle POST request to curate social posts. Publishes any checked posts and unpublishes any unchecked posts.
   */
  function curate_feed() {
    if(isset($_REQUEST['social-post-publish']) && is_array($_REQUEST['social-post-publish'])) {
      $published_posts = get_posts(array(
        'post_type' => 'social-post',
        'post_status' => 'publish',
        'posts_per_page' => -1
      ));

      $published = array_map(function($social_post) {
        return strval($social_post->ID);
      }, $published_posts);

      $selected = $_REQUEST['social-post-publish'];

      foreach ($selected as $social_post) {
        wp_update_post(array(
          'ID' => $social_post,
          'post_status' => 'publish'
        ));
      }

      $unpublish = array_diff($published, $selected);

      foreach ($unpublish as $social_post) {
        wp_update_post(array(
          'ID' => $social_post,
          'post_status' => 'draft'
        ));
      }
    }

    wp_redirect($_SERVER['HTTP_REFERER']);
    exit;
  }

}