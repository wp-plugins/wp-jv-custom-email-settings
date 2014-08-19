<?php
/**
 * Remove settings on plugin delete.
 *
 * WP JV Custom Email Settings Uninstaller
 * @version 1.3
 */

if(!defined('WP_UNINSTALL_PLUGIN')) {
  die('You are not allowed to call this page directly.');
}

delete_option('wp_jv_ces_general_settings');
delete_option('wp_jv_ces_set_email_from');
delete_option('wp_jv_ces_set_email_from_address');

?>
