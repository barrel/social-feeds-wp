<?php
namespace Barrel\SocialFeeds\Admin;
use Barrel\SocialFeeds\SocialFeeds;
use League\OAuth2\Client\Provider\LinkedIn;

/**
 * Creates the admin page for configuring plugin settings.
 */
class LinkedInSettingsPage extends Page {

  static $page_title = 'LinkedIn Settings';
  static $menu_title = 'LinkedIn Settings';
  static $capability = 'manage_options';
  static $menu_slug = 'linkedin-settings';
  static $settings_section = 'linkedin_settings';

  static $settings = array(
    'linkedin_company_name' => array(
      'type' => 'text',
      'title' => 'Company Page Name',
    ),
    'linkedin_company_id' => array(
      'type' => 'text',
      'title' => 'Company Page',
      'hide' => true
    ),
    'linkedin_client_id' => array(
      'type' => 'text',
      'title' => 'Client ID',
    ),
    'linkedin_client_secret' => array(
      'type' => 'text',
      'title' => 'Client Secret',
    ),
    'linkedin_access_token' => array(
      'type' => 'text',
      'title' => 'Access Token',
      'hide' => true
    ),
    'linkedin_auto_publish' => array(
      'type' => 'checkbox',
      'title' => 'Default Post Status',
      'args' => array('Automatically publish new posts', '<em>Leave unchecked to create new posts as drafts.</em>')
    ),
    'linkedin_cron' => array(
      'type' => 'cron',
      'title' => 'Auto-Update',
      'args' => array('linkedin')
    ),
    'linkedin_sync_now' => array(
      'type' => 'sync_now',
      'title' => 'Sync Now',
      'args' => array('linkedin')
    ),
    'linkedin_max_publish' => array(
      'type' => 'hidden',
    ),
  );

  function __construct() {
    parent::__construct();

    $this->linkedin = new LinkedIn(array(
      'clientId'          => get_option('linkedin_client_id'),
      'clientSecret'      => get_option('linkedin_client_secret'),
      'redirectUri'       => home_url('/?callback=linkedin_auth'),
    ));
    $oauth_url = $this->linkedin->getAuthorizationUrl();
    $oauth_link = '<a href="'.$oauth_url.'">Generate Access Token</a>';
    $company_page = '<a href="https://linkedin.com/company/'.strtolower(get_option('linkedin_company_name')).'" target="_blank">'.get_option('linkedin_company_name').'</a>';
    if(!get_option('linkedin_company_id')) {
      $company_page = '<em>No company page ID found for <strong>"'.get_option('linkedin_company_name').'"</strong>. Please verify your access token, company page name, and authenticated user\'s management privileges of this company page.</em>';
    }

    self::$settings['linkedin_access_token']['args'] = array($oauth_link);

    self::$settings['linkedin_company_id']['args'] = array($company_page);
  }

  function add_options_page() {
    if(!isset(SocialFeeds::$options['enable_linkedin']) || SocialFeeds::$options['enable_linkedin'] === true) {
      parent::add_options_page();
    }
  }

}