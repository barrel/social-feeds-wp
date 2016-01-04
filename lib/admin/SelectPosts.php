<?php
namespace Barrel\SocialFeeds\Admin;
use Barrel\SocialFeeds\Cron\Update;

class SelectPosts extends AdminPage {

  static $page_title = 'Select Posts';
  static $menu_title = 'Select Posts';
  static $menu_slug = 'select-posts';

  function __construct() {
    parent::__construct();

    $this->posts = get_posts(array(
      'post_type' => 'social-post',
      'post_status' => 'any',
      'posts_per_page' => -1,
      'meta_key' => 'social_post_created',
      'orderby' => 'meta_value'
    ));
  }

  function add_options_page() {
    parent::add_options_page();

    // remove_submenu_page(self::$parent_slug, self::$parent_slug);
    remove_submenu_page(self::$parent_slug, 'post-new.php?post_type=social-post');
  }

  function display_options_page() {
    new Update();
    ?>
    <div class="wrap">
      <h2><?= self::$page_title ?></h2>
      <form action="<?= admin_url('admin-post.php'); ?>" method="post">
        <input type="hidden" name="action" value="social_feed_curate">
        <?php submit_button(); ?>
        <div class="social-post-cards">
          <?
          foreach ($this->posts as $social_post) {
            $item_id = $social_post->ID;
            $item_content = $social_post->post_title;
            $item_checked = $social_post->post_status == 'publish' ? 'checked' : '';
            $item_image = get_post_meta($item_id, 'social_post_image', true);
            $item_user = get_post_meta($item_id, 'social_post_username', true);
            ?>
            <label class="social-post-card">
              <input type="checkbox" name="social-post" value="<?= $item_id ?>" <?= $item_checked ?>>
              <span class="label">Publish</span>
              <div class="social-post-details">
                <img src="<?= $item_image ?>" />
                <p><?= $item_content ?></p>
                <p class="user"><?= $item_user ?></p>
              </div>
            </label>
            <?
          }
          ?>
        <div class="social-post-cards">
      </form>
    </div>
    <?php
  }

}