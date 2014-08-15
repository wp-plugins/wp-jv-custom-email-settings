<?php
/**
 * Plugin Name: WP JV Custom Email Settings
 * Plugin URI: http://janosver.com/projects/wordpress/wp-jv-custom-email-settings
 * Description: By default all notification emails received from "Wordpress" wordpress@yourdomain.com. Once this plugin activated you can customize these (email from text and email address) at Settings -> General -> "WP JV Custom Email Settings" section
 * Version: 1.1
 * Author: Janos Ver
 * License: GPLv2 or later
 */

 
//No direct access allowed to plugin php file
if(!defined('ABSPATH')) {
	die('You are not allowed to call this page directly.');
}
  
// Adds Settings link to Plugin page next under Plugin description
function wp_jv_ces_settings_link($links, $file) {
	if ( strpos( $file, 'wp-jv-custom-email-settings.php' ) !== false ) {
	$new_links = array(
						'<a href="options-general.php#wp_jv_ces_set_email_from">Settings</a>'
					  );
	
	$links = array_merge( $links, $new_links );
	}
return $links;
}
add_filter( 'plugin_row_meta', 'wp_jv_ces_settings_link' , 10, 2 );


// Adds Donate link to Plugin page next under Plugin description 
function wp_jv_ces_donate_link($links, $file) {
	if ( strpos( $file, 'wp-jv-custom-email-settings.php' ) !== false ) {
	$new_links = array(
						'<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=JNF92QJY4PGGA&lc=HU&item_name=WP%20JV%20Custom%20Email%20Settings%20%2d%20Plugin%20Donation&item_number=2&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted" target="_blank">Donate</a>'
					  );
	
	$links = array_merge( $links, $new_links );
	}
return $links;
}
add_filter( 'plugin_row_meta', 'wp_jv_ces_donate_link' , 10, 2 );


//Initialize settings
add_action( 'admin_init', 'wp_jv_ces_admin_init' );
 
//Adding settings to Settings->General
function wp_jv_ces_admin_init() {
	add_settings_section('wp_jv_ces_general_settings','WP JV Custom Email Settings','wp_jv_ces_settings','general');
	add_settings_field('wp_jv_ces_set_email_from', 'Email From', 'wp_jv_ces_set_email_from', 'general', 'wp_jv_ces_general_settings');
	add_settings_field('wp_jv_ces_set_email_from_address', 'Email Address', 'wp_jv_ces_set_email_from_address', 'general', 'wp_jv_ces_general_settings');
	register_setting( 'wp_jv_ces_general_settings', 'wp_jv_ces_set_email_from' );
	register_setting( 'wp_jv_ces_general_settings', 'wp_jv_ces_set_email_from_address' );
}

//WP JV Custom Email Settings section intro text
function wp_jv_ces_settings() {  
	// Get the site domain and get rid of www. 
    $sitename = strtolower( $_SERVER['SERVER_NAME'] ); 
    if ( substr( $sitename, 0, 4 ) == 'www.' ) { 
		$sitename = substr( $sitename, 4 ); 
	} 
	echo 'By default all notification emails received from "Wordpress" < wordpress@'. $sitename. ' >. You can change these below.';
}

//Settings field to set Email From text
function wp_jv_ces_set_email_from() {
	settings_fields( 'wp_jv_ces_general_settings' ); 	
	echo '<input class="regular-text ltr" type="text" id="wp_jv_ces_set_email_from" name="wp_jv_ces_set_email_from" placeholder="Wordpress" value="'. get_option('wp_jv_ces_set_email_from'). '"></input>';
} 

//Settings field to set Email from email Address
function wp_jv_ces_set_email_from_address() {
	settings_fields( 'wp_jv_ces_general_settings' ); 	
	// Get the site domain and get rid of www. 
    $sitename = strtolower( $_SERVER['SERVER_NAME'] ); 
    if ( substr( $sitename, 0, 4 ) == 'www.' ) { 
		$sitename = substr( $sitename, 4 ); 
	} 	
	echo '<input class="regular-text ltr" type="email" id="wp_jv_ces_set_email_from_address" name="wp_jv_ces_set_email_from_address" placeholder="wordpress@'. $sitename. '" value="'. get_option('wp_jv_ces_set_email_from_address'). '"></input>';
}

//Replace default <wordpress@yourdomain.com> e-mail address 
function wp_jv_ces_wp_mail_from($email){
	// Get the site domain and get rid of www. 
    $sitename = strtolower( $_SERVER['SERVER_NAME'] ); 
    if ( substr( $sitename, 0, 4 ) == 'www.' ) { 
		$sitename = substr( $sitename, 4 ); 
	} 	
	if ($email=="wordpress@". $sitename) { return get_option('wp_jv_ces_set_email_from_address');}
	else {
		return $email;
	}
}
//Overwrite default e-mail address only if user set new value
if (get_option('wp_jv_ces_set_email_from_address')) { add_filter('wp_mail_from', 'wp_jv_ces_wp_mail_from',1);}

//Replace default e-mail from "Wordpress"
function wp_jv_ces_wp_mail_from_name($from_name){
	if ($from_name=="WordPress") {return get_option('wp_jv_ces_set_email_from');}
	else {
		return $from_name;
	}
}
//Overwrite default e-mail from text only if user set new value
if (get_option('wp_jv_ces_set_email_from')) { add_filter('wp_mail_from_name', 'wp_jv_ces_wp_mail_from_name',1);}

?>