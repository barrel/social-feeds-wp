<?php
namespace Barrel\SocialFeeds\Admin;
use Barrel\SocialFeeds\Update\InstagramFeed;

/**
 * Creates the admin page for selecting posts to publish.
 */
class SelectPostsPage extends Page {

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

  function display_options_page() {
    // Update on admin page load?
    // new InstagramFeed();

    $types = get_terms( 'social_types', array(
        'hide_empty' => false,
    ) );

    $query = array(
      'post_type' => 'social-post',
      'post_status' => 'any',
      'posts_per_page' => -1,
      'meta_key' => 'social_post_created',
      'orderby' => 'meta_value',
    );

    $social_type = isset($_GET['social_type']) ? $_GET['social_type'] : false;

    if($social_type) {
      $query['tax_query'] = array(
        array(
            'taxonomy' => 'social_types',
            'field' => 'slug',
            'terms' => $social_type,
        ),
      );
    }

    $social_post_query = new \WP_Query($query);
    ?>
    <div class="wrap">
      <h2><?= self::$page_title ?></h2>
      <p>Select posts to make visible on the front end.</p>
      <?php if( count($types) > 1 ): ?>
      <ul class="subsubsub">
        <?php $type_index=0; foreach($types as $type): ?>
         <?php if(!isset(\Barrel\SocialFeeds\SocialFeeds::$options['enable_'.$type->slug]) || \Barrel\SocialFeeds\SocialFeeds::$options['enable_'.$type->slug] === true): ?>
          <li><a href="<?= add_query_arg( array( 'post_type' => 'social-post', 'social_type' => $type->slug ), admin_url( 'edit.php?page=select-posts' ) ); ?>" class="<?php if( $social_type == $type->slug ): ?>current<?php endif; ?>"><?= $type->name ?></a> <?php if( $type_index < count($types)-1 ): ?>|<?php endif; ?></li>
         <?php endif; ?>
        <?php $type_index++; endforeach; ?>
      </ul>
      <?php endif; ?>
      <form action="<?= admin_url('admin-post.php'); ?>" method="post" style="clear: both;">
        <input type="hidden" name="action" value="curate_social_feed">
        <input type="hidden" name="curate_social_feed_type" value="<?= $social_type ?>">
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
                    <p class="user">@<?= $item_user ?></p>
                  </div>
                </label>
                <?php
              }

              wp_reset_postdata();
            } else {
            ?>
            <p>There are no posts yet for this social media type. Run "Sync Now" under the settings to load older posts.</p>
            <?php
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

      $published_posts_args = array(
        'post_type' => 'social-post',
        'post_status' => 'publish',
        'posts_per_page' => -1
      );

      $social_type = isset($_REQUEST['curate_social_feed_type']) ? $_REQUEST['curate_social_feed_type'] : false;
      if($social_type) {
        $published_posts_args['tax_query'] = array(
          array(
              'taxonomy' => 'social_types',
              'field' => 'slug',
              'terms' => $social_type,
          ),
        );
      }

      $published_posts = get_posts($published_posts_args);

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