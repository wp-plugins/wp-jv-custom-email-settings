<?php
/**
 * Remove settings on plugin delete.
 *
 * WP JV Custom Email Settings Uninstaller
 * @version 2.3
 */

if(!defined('WP_UNINSTALL_PLUGIN')) {
  die('You are not allowed to call this page directly.');
}

global $wpdb;

function wp_jv_ces_del_options (){
	global $wpdb;	
	//wp_jv_ces_general_settings
	delete_option('wp_jv_ces_db_version');
	delete_option('wp_jv_ces_set_email_from');
	delete_option('wp_jv_ces_set_email_from_address');

	//wp_jv_ces_writing_settings
	delete_option('wp_jv_ces_set_notification_mode');
	delete_option('wp_jv_ces_set_notification_about');
	delete_option('wp_jv_ces_set_subject');
	delete_option('wp_jv_ces_set_content');
	//#TODO: purge
	//delete_option('wp_jv_ces_set_notification_log');

	//delete wp_jv_ces_notification_sent flags
	delete_post_meta_by_key('wp_jv_ces_notification_sent');	
	
	//drop notifications log
	$table_name = $wpdb->prefix . 'jv_notification_log';
	$wpdb->query("DROP TABLE IF EXISTS $table_name");
}

if ( !is_multisite() ) {
	wp_jv_ces_del_options();
}
else {
	$blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs");
	foreach( $blog_ids as $blog_id ){
		switch_to_blog( $blog_id );
		wp_jv_ces_del_options();
		restore_current_blog();
	}
	
}

//Delete notification settings for users
$all_user_ids = get_users( array(
								'meta_key'	=>	'wp_jv_ces_user_notification',
								'fields'	=>	'ID'
								) 
	);
foreach ( $all_user_ids as $value ) {
    delete_user_option( $value, 'wp_jv_ces_user_notification',true );
}


?>
