<?php
/**
 * Plugin Name: WP JV Custom Email Settings
 * Plugin URI: http://janosver.com/projects/wordpress/wp-jv-custom-email-settings
 * Description: Notify users about new posts published and customize your e-mail notification settings
 * Version: 2.1
 * Author: Janos Ver
 * Author URI: http://janosver.com
 * License: GPLv2 or later
 */

//No direct access allowed to plugin php file
if(!defined('ABSPATH')) {
	die('You are not allowed to call this page directly.');
}
  
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

/************************************************************************************************************/
/* Settings */
/************************************************************************************************************/

// Install log table for storing notification log
function wp_jv_ces_install_notification_log () {
	global $wpdb;
	$table_name = $wpdb->prefix . 'jv_notification_log'; 
	$charset_collate = $wpdb->get_charset_collate();
	//Related post, user, e-mail sent (Time stamp), e-mail address, Status (successful/failed)
	$sql = "CREATE TABLE $table_name (
	  id mediumint(9) NOT NULL AUTO_INCREMENT,	  
	  user_id bigint(20) NOT NULL,
	  post_id bigint(20) NOT NULL,
	  time_mail_sent datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
	  user_email varchar(100) NOT NULL,
	  status varchar(100) NOT NULL,
	  PRIMARY KEY  (id),
	  UNIQUE KEY id (id),
	  INDEX (user_id),
	  INDEX (post_id)  
	) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
	
	add_option( 'wp_jv_ces_db_version', '2.0' );
}
//Install table when plugin activated
register_activation_hook( __FILE__, 'wp_jv_ces_install_notification_log' );

//Make sure we update database in case of a manual plugin download as well
function wp_jv_ces_db_version_check() {
	$current_dv_ver=get_option('wp_jv_ces_db_version');
	if ($current_dv_ver!='2.0') {
		wp_jv_ces_install_notification_log();
	}
}
add_action( 'plugins_loaded', 'wp_jv_ces_db_version_check' );

function wp_jv_ces_admin_init() {	
	//Adding settings to Settings->General
	add_settings_section('wp_jv_ces_general_settings','WP JV Custom Email Settings','wp_jv_ces_general_settings','general');
	add_settings_field('wp_jv_ces_set_email_from', __('Email From','wp-jv-custom-email-settings'), 'wp_jv_ces_set_email_from', 'general', 'wp_jv_ces_general_settings');
	add_settings_field('wp_jv_ces_set_email_from_address', __('Email Address','wp-jv-custom-email-settings'), 'wp_jv_ces_set_email_from_address', 'general', 'wp_jv_ces_general_settings');
	register_setting( 'general', 'wp_jv_ces_set_email_from' );
	register_setting( 'general', 'wp_jv_ces_set_email_from_address' );
	//Adding settings to Settings->Writing
	add_settings_section('wp_jv_ces_writing_settings','WP JV Custom Email Settings - '.__('Notifications','wp-jv-custom-email-settings'),'wp_jv_ces_writing_settings','writing');
	add_settings_field('wp_jv_ces_set_notification_mode', __('Notification mode','wp-jv-custom-email-settings'), 'wp_jv_ces_set_notification_mode', 'writing', 'wp_jv_ces_writing_settings');
	add_settings_field('wp_jv_ces_set_notification_about', __('Notify users about','wp-jv-custom-email-settings'), 'wp_jv_ces_set_notification_about', 'writing', 'wp_jv_ces_writing_settings');
	add_settings_field('wp_jv_ces_set_subject', __('Notification e-mail subject','wp-jv-custom-email-settings'), 'wp_jv_ces_set_subject', 'writing', 'wp_jv_ces_writing_settings');
	add_settings_field('wp_jv_ces_set_content', __('Notification e-mail content','wp-jv-custom-email-settings'), 'wp_jv_ces_set_content', 'writing', 'wp_jv_ces_writing_settings');	
	//#TODO: purge
	//add_settings_field('wp_jv_ces_set_notification_log', 'Logging', 'wp_jv_ces_set_notification_log', 'writing', 'wp_jv_ces_writing_settings');	
	register_setting( 'writing', 'wp_jv_ces_set_notification_mode' );
	register_setting( 'writing', 'wp_jv_ces_set_notification_about' );	
	register_setting( 'writing', 'wp_jv_ces_set_subject' );	
	register_setting( 'writing', 'wp_jv_ces_set_content' );	
	//#TODO:purge
	///register_setting( 'writing', 'wp_jv_ces_set_notification_log' );	
}
//Initialize settings
add_action( 'admin_init', 'wp_jv_ces_admin_init' );


//Initialize js methods
function wp_jv_ces_load_js_methods() {   
   wp_register_script( 'wp_jv_ces_script', plugin_dir_url(__FILE__).'wp-jv-custom-email-settings.js', array('jquery') );      
   //support languages
   load_plugin_textdomain('wp-jv-custom-email-settings', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
   //Improve security   
   $nonce_array = array( 
	'wp_jv_ces_nonce' =>  wp_create_nonce ('wp_jv_ces_nonce'),
	'sending_mails' => __('Sending email notifications...','wp-jv-custom-email-settings'),
	'error_sending'=>__('Error sending emails.','wp-jv-custom-email-settings'),
	'resend'=>__('Re-send notification email(s)','wp-jv-custom-email-settings'),
	'email_out_of' => __('email(s) out of','wp-jv-custom-email-settings'),
	'notif_sent_check' => __('notification(s) sent. Check','wp-jv-custom-email-settings'),
	'sent_with' => __('sent with','wp-jv-custom-email-settings'),
	'log_issues'=>__('log issues. Check','wp-jv-custom-email-settings'),
	'log' =>__('log','wp-jv-custom-email-settings'),
	'for_details'=>__('for details.','wp-jv-custom-email-settings')
   );   
   wp_localize_script( 'wp_jv_ces_script', 'wp_jv_ces_obj', $nonce_array );
   wp_enqueue_script( 'wp_jv_ces_script' );
   //Make sure we can use jQuery
   wp_enqueue_script( 'jquery' );   
}
add_action( 'init', 'wp_jv_ces_load_js_methods' );

/************************************************************************************************************/
/* Settings->General */
/************************************************************************************************************/

//WP JV Custom Email Settings section intro text
function wp_jv_ces_general_settings() {  
	// Get the site domain and get rid of www. 
    $sitename = strtolower( $_SERVER['SERVER_NAME'] ); 
    if ( substr( $sitename, 0, 4 ) == 'www.' ) { 
		$sitename = substr( $sitename, 4 ); 
	} 
	echo __('By default all notification e-mails received from "Wordpress" < wordpress@','wp-jv-custom-email-settings'). $sitename. ' >. '.__('You can change these below.','wp-jv-custom-email-settings');
}

//Settings field to set Email From text
function wp_jv_ces_set_email_from() {
	echo '<input class="regular-text ltr" type="text" id="wp_jv_ces_set_email_from" name="wp_jv_ces_set_email_from" placeholder="Wordpress" value="'. get_option('wp_jv_ces_set_email_from'). '"></input>';
} 

//Settings field to set Email from email Address
function wp_jv_ces_set_email_from_address() {
	// Get the site domain and get rid of www. 
    $sitename = strtolower( $_SERVER['SERVER_NAME'] ); 
    if ( substr( $sitename, 0, 4 ) == 'www.' ) { 
		$sitename = substr( $sitename, 4 ); 
	} 	
	echo '<input class="regular-text ltr" type="email" id="wp_jv_ces_set_email_from_address" name="wp_jv_ces_set_email_from_address" placeholder="wordpress@'. $sitename. '" value="'. get_option('wp_jv_ces_set_email_from_address'). '"></input>';
}

/************************************************************************************************************/
/* Settings->Writing */
/************************************************************************************************************/

//Notifications section intro text
function wp_jv_ces_writing_settings() {  	
	echo __('Tags available to make e-mail subject and/or content dynamic:','wp-jv-custom-email-settings');
	echo '<br>';
	echo '<strong>%title%</strong> '. __('means title of the post','wp-jv-custom-email-settings') .'<br>';	
	echo '<strong>%permalink%</strong> '. __('means URL of the post','wp-jv-custom-email-settings') .'<br>';
	echo '<strong>%title_with_permalink%</strong> '. __('means URL with title of the post','wp-jv-custom-email-settings') .'<br>';	
	echo '<strong>%author_name%</strong> '. __('means the name of the post author','wp-jv-custom-email-settings') .'<br>';
	echo '<strong>%excerpt%</strong> '. __('means excerpt of the post','wp-jv-custom-email-settings') .'<br>';
	echo '<strong>%words_n%</strong> '. __('means the first n (must be an integer number) number of word(s) extracted from the post','wp-jv-custom-email-settings') .'<br>';
	echo '<strong>%recipient_name%</strong> '. __('means display name of the user who receives the e-mail','wp-jv-custom-email-settings') .'<br>';
	echo '<br><br>';
}

//Settings field to set Notification mode: Auto/Manual
function wp_jv_ces_set_notification_mode() {	
	echo '<input type="radio" name="wp_jv_ces_set_notification_mode" value="Auto" '. checked('Auto', get_option('wp_jv_ces_set_notification_mode'), false). '>'. __('Auto','wp-jv-custom-email-settings').'</input> '.__('(send e-mails automatically when you publish a post)','wp-jv-custom-email-settings').'<br>';
	echo '<input type="radio" name="wp_jv_ces_set_notification_mode" value="Manual" '. checked('Manual', get_option('wp_jv_ces_set_notification_mode'), false). '>'. __('Manual','wp-jv-custom-email-settings').'</input> '.__('(you need to press a button to send notification)','wp-jv-custom-email-settings');
} 

//Settings field to set Notify users about public/private/password protected posts
function wp_jv_ces_set_notification_about() {	
	$options = get_option('wp_jv_ces_set_notification_about');
	$public_checked = '';
	$password_checked = '';
	$private_checked = '';
	if (!empty($options))
	{	
	if (array_key_exists('Chkbx_Public',$options)) { $public_checked = 'checked="checked"'; } 
	if (array_key_exists('Chkbx_Password',$options)) { $password_checked = ' checked="checked" '; } 
	if (array_key_exists('Chkbx_Private',$options)) { $private_checked = ' checked="checked" '; } 
	}	
	echo '<input type="checkbox" id="Chkbx_Public" name="wp_jv_ces_set_notification_about[Chkbx_Public]" value="Public" '. $public_checked.'>'. __('Public posts','wp-jv-custom-email-settings') .'</input><br>';
	
	echo '<input type="checkbox" id="Chkbx_Password" name="wp_jv_ces_set_notification_about[Chkbx_Password]" value="Password"'.  $password_checked.'>'.__('Password','wp-jv-custom-email-settings'). '</input> '. __('protected posts (password will','wp-jv-custom-email-settings').' <strong>'.__('NOT','wp-jv-custom-email-settings'). '</strong> '. __('be included in notification e-mail)','wp-jv-custom-email-settings'). '<br>';
	
	//Check if WP JV Post Reading Groups is active or not
	$private_message='';
	if (is_plugin_active('wp-jv-post-reading-groups/wp-jv-post-reading-groups.php')) { $private_possible='';}
	else {
		$private_possible='disabled';
		$private_message=__('This feature requires','wp-jv-custom-email-settings').' <a href="https://wordpress.org/plugins/wp-jv-post-reading-groups/" target="_blank">WP JV Post Reading Groups plugin</a> '.__('to be installed and activated.','wp-jv-custom-email-settings');
	}
	
	echo '<input type="checkbox" id="Chkbx_Private" name="wp_jv_ces_set_notification_about[Chkbx_Private]" value="Private"'.  $private_checked. $private_possible. '>'. __('Private posts','wp-jv-custom-email-settings'). '</input> - '. $private_message.' '. __('Notifications are based on','wp-jv-custom-email-settings').' <a href="https://wordpress.org/plugins/wp-jv-post-reading-groups/" target="_blank">WP JV Post Reading Groups plugin</a> '.__('settings.','wp-jv-custom-email-settings');	
} 

//Settings field to set notification email subject
function wp_jv_ces_set_subject(){	
	echo '<input class="regular-text ltr" type="text" id="wp_jv_ces_set_subject" name="wp_jv_ces_set_subject" placeholder="New post: %title%" value="'. get_option('wp_jv_ces_set_subject'). '"></input>';
	echo '<br><br>'.__('Hint: HTML tags are not allowed here, e.g.: %title_with_permalink% will revert to %title%.','wp-jv-custom-email-settings');
}

//Settings field to set notification email content
function wp_jv_ces_set_content(){
	echo '<textarea id="wp_jv_ces_set_content" name="wp_jv_ces_set_content" cols="80" rows="10" 
	placeholder="Dear %recipient_name%, A new post is published. Check it out! %title_with_permalink% %words_50% In case you do not want to receive this kind of notification you can turn it off in your profile.">'. get_option('wp_jv_ces_set_content'). '</textarea>';
	echo '<br><br>'.__('Hint: HTML tags are welcome here to make your notification e-mails more personalized.','wp-jv-custom-email-settings');
}

//#TODO: purge
/*
//Settings field to set purge interval
function wp_jv_ces_set_notification_log() {	
	echo 'Purge log every <input type="number" id="wp_jv_ces_set_notification_log" name="wp_jv_ces_set_notification_log" value="'. get_option('wp_jv_ces_set_notification_log'). '" min="0" max="999" size="3"></input> day(s). Zero or empty value means no purge needed.<br>';
} 
*/

/************************************************************************************************************/
//Add subscription to notifications to User's Profile screen
/************************************************************************************************************/

function wp_jv_ces_user_profile($user) {  	

	//Wrapper
	echo '<div class="jv-wrapper">';
		
	//Header
	echo '<div class="jv-header">';
	echo '<h3>WP JV Custom Email Settings</h3>';	
	echo '</div>'; //jv-header end

	echo '<div class="jv-content">';	
	
	$wp_jv_ces_user_notification=null;
	if (!empty($user->ID)) {
		$wp_jv_ces_user_notification=checked(1,get_user_meta($user->ID, 'wp_jv_ces_user_notification',true),false);
	}
	
	echo '<input type="checkbox" name="wp_jv_ces_user_notification" value="1" '. $wp_jv_ces_user_notification. '>'.__('Notify me by e-mail when a new post is published','wp-jv-custom-email-settings'). '</input><br>';

	echo '</div>'; //jv-content end
	
	echo '</div>'; //jv-wrapper end	
	
}
add_action( 'show_user_profile', 'wp_jv_ces_user_profile' );
add_action( 'edit_user_profile', 'wp_jv_ces_user_profile' );

//Save Profile settings
function wp_jv_ces_save_user_profile( $user_id ) {	
	if ( !current_user_can( 'edit_user', $user_id ) ) { return; }
	$notify=null;
	if (isset($_POST['wp_jv_ces_user_notification'])) {		
		$notify=$_POST['wp_jv_ces_user_notification'];
	}
	update_user_meta( $user_id, 'wp_jv_ces_user_notification', $notify );
	
	//Check if notification value saved successfully
	if ( get_user_meta($user_id,  'wp_jv_ces_user_notification', true ) != $notify ) {	wp_die('Something went wrong.<br>[Error: F-01] ');}		
}
add_action( 'personal_options_update', 'wp_jv_ces_save_user_profile' );
add_action( 'edit_user_profile_update', 'wp_jv_ces_save_user_profile' );

/************************************************************************************************************/
/* Add subscription to notifications to Add New User screen */
/************************************************************************************************************/
add_action('user_new_form','wp_jv_ces_user_profile');
add_action('user_register','wp_jv_ces_save_user_profile');

/************************************************************************************************************/
/* Add subscription to notifications to All Users screen */
/************************************************************************************************************/

//Add column
function wp_jv_ces_all_users_column_register( $columns ) {
    $columns['wp_jv_ces_notification'] = 'WP JV Email notifications';
    return $columns;
}

//Add rows
function wp_jv_ces_all_users_column_rows( $empty, $column_name, $user_id ) {
    if ( 'wp_jv_ces_notification' != $column_name ) {
        return $empty;
	}
	$wp_jv_user_notification=get_user_meta($user_id,'wp_jv_ces_user_notification',true);
	if (empty($wp_jv_user_notification) || $wp_jv_user_notification!=1) {return '<input type="checkbox" disabled></>';} 
	else { return '<input type="checkbox" checked="checked" disabled></>';}	
}
add_filter( 'manage_users_columns', 'wp_jv_ces_all_users_column_register' );
add_filter( 'manage_users_custom_column', 'wp_jv_ces_all_users_column_rows', 10, 3 );

/************************************************************************************************************/ 
/* Adds a JV Custom Email Settings box to Edit Post screen */
/************************************************************************************************************/

function wp_jv_ces_add_metabox_head() {
	add_meta_box('wp_jv_ces_sectionid', 'WP JV Custom Email Settings','wp_jv_ces_add_metabox',	'post','side','high');	
}
add_action( 'add_meta_boxes', 'wp_jv_ces_add_metabox_head' );

function wp_jv_ces_add_metabox(){
	// Add an nonce field so we can check for it later
	$post_id=get_the_ID();	
	wp_nonce_field( 'wp_jv_ces_meta_box', 'wp_jv_ces_meta_box_nonce');	
	$notification_sent=get_post_meta( $post_id, 'wp_jv_ces_notification_sent', true);	
	$pstatus=wp_jv_ces_post_status($post_id);
	$recipients=wp_jv_ces_prepare_mail_recipients($post_id);
	$hasrecipient=!empty($recipients);
	if ((!empty($notification_sent) || $notification_sent=='1') && in_array($pstatus, array('Public','Password protected','Private')) && $hasrecipient) {	  		
		echo '<input type="button" id="btnSendNotification" class="button-secondary" value="'.__('Re-send notification email(s)','wp-jv-custom-email-settings').'" />';
	} 
	else {
		if (in_array($pstatus, array('Public','Password protected','Private')) && (empty($notification_sent) || $notification_sent=='0') && $hasrecipient) {		
			echo '<input type="button" id="btnSendNotification" class="button-secondary" value="'.__('Send notification email(s)','wp-jv-custom-email-settings').'"/>';
			} 
			else if (empty($notification_sent) || $notification_sent=='0') {
					echo '<input type="button" id="btnSendNotification" class="button-secondary" value="'.__('Send notification email(s)','wp-jv-custom-email-settings').'" disabled/>';
				}
				else if (!empty($notification_sent) || $notification_sent=='1') {
						echo '<input type="button" id="btnSendNotification" class="button-secondary" value="'.__('Re-send notification email(s)','wp-jv-custom-email-settings').'" disabled/>';
				}
		}	
	//Pass post id to jQuery
	echo '<div id="wraphidden">';
	echo '<input id="jv-notification-postid" type="hidden" value="'. $post_id. '">';			
	echo '<span id="dProgress" style="display:none; margin-top: 5px;" >';
	echo '<img id="spnSendNotifications" src="'. admin_url() . '/images/wpspin_light.gif">';		
	echo '</span>';		
	echo '<span id="jv-ces-message" style="display: none; margin-left: 5px;">'.__('Sending email notifications...','wp-jv-custom-email-settings').'</span>';
	echo '</div>';	

	//to debug uncomment the following 
	/*
	if (in_array($pstatus, array('Public','Password protected','Private'))) {echo 'Can send mail';} else {echo 'Should not send mail';}
	echo '<br>';
	if (empty($notification_sent) || $notification_sent=='0') {echo 'Not yet sent';} else {echo 'Already sent';}
	echo '<br>';
	if ($hasrecipient) { echo 'Recipient exists';}
	else { echo 'No recipient';}	
	*/
}

/************************************************************************************************************/ 
/* Posts ->  JV CES Notification log
/************************************************************************************************************/

function wp_jv_ces_notification_log_menu() {
	$plugin_page=add_submenu_page( 'edit.php', 'JV CES Notification Email '.__('Log','wp-jv-custom-email-settings'), 'JV CES Email '. __('Log','wp-jv-custom-email-settings'), 'edit_posts', 'wp_jv_ces_notification_log_menu_page','wp_jv_ces_notification_log_menu_page' );
	add_action( 'admin_head-'.$plugin_page, 'wp_jv_ces_admin_head' );	
}
add_action( 'admin_menu', 'wp_jv_ces_notification_log_menu' );

function wp_jv_ces_admin_head() {
	$page = ( isset($_GET['page'] ) ) ? esc_attr( $_GET['page'] ) : false;
	if( 'wp_jv_ces_notification_log_menu_page' != $page ) {return;}
	echo '<style>';
	//echo 'table.wp-list-table { table-layout: auto; }';
	echo '.wp-list-table .column-id { width: 5%; }';
	echo '</style>';
}

function wp_jv_ces_notification_log_menu_page() {
	if ( !current_user_can( 'edit_posts' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	//#TODO: add filter/search
	echo '<div class="wrap">';	
	$wp_jv_ces_notification_log_table = new WP_JV_CES_List_Table();	
	$wp_jv_ces_notification_log_table ->prepare_items();	
	$wp_jv_ces_notification_log_table ->display(); 	
	echo '</div>';
}

//Load WP_List_Table if not loaded
if( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/*Start class WP_JV_CES_List_Table*/
class WP_JV_CES_List_Table extends WP_List_Table {

	function __construct( $args = array()){		
		$args = wp_parse_args($args,  array(
			'singular'  => __( 'Notification email sent' ),     //singular name of the listed records
			'plural'    => __( 'Notification emails sent' ),   //plural name of the listed records
			'ajax'      => false
			));		
	}
	
	function get_columns(){
		$columns = array(					
						'id'				=> '#',
						'post_title' 		=> __('Post Title','wp-jv-custom-email-settings'),
						'time_mail_sent'	=> __('E-mail sent','wp-jv-custom-email-settings'),
						'user_display_name' => __('User Name','wp-jv-custom-email-settings'),
						'user_email' 		=> __('User E-mail','wp-jv-custom-email-settings'),
						'status' 			=> __('Status','wp-jv-custom-email-settings')
						);
		return $columns;
	}
	
	function get_sortable_columns() {
	$sortable_columns = array(						
						'post_title' 		=> array('post_title',false),
						'time_mail_sent' 	=> array('time_mail_sent',false), 
						'user_display_name' => array('user_display_name',false),
						'user_email' 		=> array('user_email',false),
						'status' 			=> array('status',false)
						);
	return $sortable_columns;
    }
	
	function column_default( $item, $column_name ) {
		//return $item[ $column_name ];	
		switch( $column_name ) { 
		case 'post_title':
			if (current_user_can('edit_post',$item['post_id'])) {
			return '<a href="'. get_edit_post_link($item['post_id']). '">'.$item[$column_name]. '</a>';		
			}
			else {
				return '<a href="'. get_permalink($item['post_id']). '">'.$item[$column_name]. '</a>';		
			}
				
		case 'user_display_name': 
			if (current_user_can('list_users')) {
				return '<a href="'. get_edit_user_link($item['user_id']). '">'.ucwords($item[$column_name]). '</a>';
			} 
			else {
				return ucwords($item[$column_name]);
			}
		case 'id':	
		case 'time_mail_sent':		
		case 'user_email':
		case 'status':
		  return $item[ $column_name ];		  
		}
	}	

	function prepare_items() {		
		$per_page = 20;
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array($columns, $hidden, $sortable);	  		
		
		
		global $wpdb;
		$log_items = null;
		//sorting		
		$orderby = 'time_mail_sent';
		$order = 'desc';
		
		if (!empty($_GET['orderby'])) {			
			switch ($_GET['orderby']){
				case 'post_title':
				case 'user_display_name':
				case 'user_email':
				case 'status':
					$orderby=$_GET['orderby'];			
					break;
				default:
					$orderby = 'time_mail_sent';			
					break;
			}					
		}  		
		if (!empty($_GET['order'])) {
			switch ($_GET['order']){
				case 'asc':
				case 'desc':
					$order =$_GET['order'];
					break;
				default:
					$order ='desc';
					break;
			}			
		}		
        $sql = 'SELECT 	l.id as id,
						l.post_id as post_id,
						p.post_title as post_title,
						l.user_id as user_id,
						u.display_name as user_display_name,						
						l.time_mail_sent as time_mail_sent,
						l.user_email as user_email,
						l.status									   
				FROM '.$wpdb->prefix . 'jv_notification_log l,'
					 . $wpdb->prefix .'posts p,'
					 . $wpdb->base_prefix . 'users u
				where 
					l.post_id=p.id
					and l.user_id=u.id
				ORDER BY ' .$orderby. ' '.$order;
        $log_items = $wpdb->get_results($sql,ARRAY_A); //ARRAY_A will ensure that we get associated array instead of stdClass
		$current_page = $this->get_pagenum();
		$total_items = count($log_items);
		$log_items=array_slice($log_items,(($current_page-1)*$per_page),$per_page);
		
		$this->items=$log_items;
		$this->set_pagination_args( array(
            'total_items' => $total_items,                  //WE have to calculate the total number of items
            'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
            'total_pages' => ceil($total_items/$per_page)   //WE have to calculate the total number of pages
        ) );
	}

	function no_items() {
	  echo __( 'No e-mails sent out, yet.','wp-jv-custom-email-settings' );
	}	
	
	function bulk_actions($which = ''){		
		//
	}
		
} 
/*End class WP_JV_CES_List_Table*/

/************************************************************************************************************/
/* Functionality: Email from configuration */
/************************************************************************************************************/

//Replace default <wordpress@yourdomain.com> e-mail address 
function wp_jv_ces_wp_mail_from($email){
	// Get the site domain and get rid of www. 
    $sitename = strtolower( $_SERVER['SERVER_NAME'] ); 
    if ( substr( $sitename, 0, 4 ) == 'www.' ) { 
		$sitename = substr( $sitename, 4 ); 
	} 	
	//Override only default email address - provides compatibility with other plugins
	if ($email=="wordpress@". $sitename) { return get_option('wp_jv_ces_set_email_from_address');}
	else {
		return $email;
	}
}
//Overwrite default e-mail address only if user set new value
if (get_option('wp_jv_ces_set_email_from_address')) { add_filter('wp_mail_from', 'wp_jv_ces_wp_mail_from');}

//Replace default e-mail from "WordPress"
function wp_jv_ces_wp_mail_from_name($from_name){
	//Override only default email from - provides compatibility with other plugins
	if ($from_name=="WordPress") {return get_option('wp_jv_ces_set_email_from');}
	else {
		return $from_name;
	}
}
//Overwrite default e-mail from text only if user set new value
if (get_option('wp_jv_ces_set_email_from')) {add_filter('wp_mail_from_name', 'wp_jv_ces_wp_mail_from_name');}


/************************************************************************************************************/
/* Functionality: Notifications */
/************************************************************************************************************/

//Retrieve post status
function wp_jv_ces_post_status($post_id) {
	$pstatus=get_post_status($post_id);
	if ( $pstatus == 'publish') {
		if (post_password_required($post_id)) {return 'Password protected';} 
		else {return 'Public';}
		} 
	else if ($pstatus=='private') {return 'Private';}				
		else {return 'Do not notify';}		
}

//It extracts from $subject string a sub string marked by $delim character(s) $count times 
function wp_jv_ces_substring_index($subject, $delim, $count){
    if($count < 0){
        return implode($delim, array_slice(explode($delim, $subject), $count));
    }else{
        return implode($delim, array_slice(explode($delim, $subject), 0, $count));
    }
}

//It gets the necessary number of words from the post content
function wp_jv_ces_get_words_from_post($post_id,$number_of_words){
	$content=get_post_field('post_content',$post_id);
	$content=wp_jv_ces_substring_index($content,' ',$number_of_words);
	$content=preg_replace('/\[(.*)/','',preg_replace('/\[(.*)\]/','',preg_replace('/\](.*)\[/','][', strip_tags($content))));	
	return $content;
}

//Number of words a post has
function wp_jv_ces_get_post_word_count($post_id){
	$content=get_post_field('post_content',$post_id);	
	$content=preg_replace('/\[(.*)/','',preg_replace('/\[(.*)\]/','',preg_replace('/\](.*)\[/','][', strip_tags($content))));		
	return count(explode(' ', $content));
}

//Resolve tags
/* Tags available
%title% means title of the post
%permalink% means URL of the post
%title_with_permalink% means URL with title of the post
%author_name% means the name of the post author
%excerpt% means excerpt of the post
%words_n% means the first n (must be an integer number) number of word(s) extracted from the post
%recipient_name% means display name of the user who receives the email
*/
function wp_jv_ces_resolve_tags($text,$post_id, $user_id) {
	$text=nl2br($text); //to keep line breaks
	$text=str_replace('%title%',get_the_title($post_id),$text);
	$text=str_replace('%permalink%','<a href="'. get_permalink($post_id). '">'. get_permalink($post_id). '</a>',$text);
	$text=str_replace('%title_with_permalink%','<a href="'. get_permalink($post_id). '">'. get_the_title($post_id). '</a>',$text);
	$text=str_replace('%author_name%',ucwords(get_userdata(get_post_field('post_author',$post_id))->display_name),$text);
	$text=str_replace('%excerpt%',get_post_field('post_excerpt',$post_id),$text);	
	$text=str_replace('%recipient_name%',ucwords(get_userdata($user_id)->display_name),$text);
	//%words_n%
	$nom=preg_match_all('/\%words_\d+\%/',$text,$matches,PREG_OFFSET_CAPTURE);
	if (count($nom)>0) {
		foreach ($matches[0] as $key=>$value) {	
			$tag=$value[0];
			$now=intval(substr($tag,7,strlen($tag)-8));	//number of words needed
			if ($now>0) {
				//Check if content is longer than number of words needed and if so, add three dots
				if (wp_jv_ces_get_post_word_count($post_id)<=$now) {{$text=str_replace($tag,'<blockquote><em>'. wp_jv_ces_get_words_from_post($post_id,$now). '</em></blockquote>',$text);}}
				else {$text=str_replace($tag,'<blockquote><em>'. wp_jv_ces_get_words_from_post($post_id,$now). '...</em></blockquote>',$text);}
				//$position=$value[1]; 
			}	
		}
	}			
	return $text;
}

function wp_jv_ces_prepare_mail_recipients($post_id){
	$result=array();
	global $wpdb;
	$pstatus = wp_jv_ces_post_status($post_id);	
	
	if (in_array($pstatus, array('Public','Password protected','Private'))) {
		//Public & Password protected post users
		if (in_array($pstatus, array('Public','Password protected'))) {
		 $result = $wpdb->get_results( "SELECT u.id AS user_id,
											   u.user_email as user_email
										FROM ".$wpdb->base_prefix. "users u, 
											 ".$wpdb->base_prefix. "usermeta um
										WHERE u.id=um.user_id
											  and um.meta_key='wp_jv_ces_user_notification'
											  and um.meta_value=1");
		}
		//Private post users
		if (in_array($pstatus, array('Private'))) {
			if (function_exists("wp_jv_prg_user_can_see_a_post")){				
				$all_users_who_want_notifications = $wpdb->get_results( "SELECT u.id AS user_id,
											   u.user_email as user_email
										FROM ".$wpdb->base_prefix. "users u, 
											 ".$wpdb->base_prefix. "usermeta um
										WHERE u.id=um.user_id
											  and um.meta_key='wp_jv_ces_user_notification'
											  and um.meta_value=1");							
				foreach ($all_users_who_want_notifications as $value) {
					if (wp_jv_prg_user_can_see_a_post($value->user_id,$post_id)) {
						$user_data=new StdClass();
						$user_data->user_id=$value->user_id;
						$user_data->user_email=$value->user_email;
						$result[]=$user_data;
					}										
				}						
			}
		}
	}
	return $result;
}

//It puts together the mail subject
function wp_jv_ces_prepare_mail_subject($post_id, $user_id){
	$template=get_option('wp_jv_ces_set_subject');
	//Avoid characters like dashes replaced with html numeric character references
	remove_filter( 'the_title', 'wptexturize' );
	if (empty($template)) { 
		$template='New post: '. get_the_title($post_id);
	}
	$subject=strip_tags(wp_jv_ces_resolve_tags($template,$post_id, $user_id));
	//Revert to default mode
	add_filter( 'the_title', 'wptexturize' );
	return $subject; 
}

//It puts together the mail content
function wp_jv_ces_prepare_mail_content($post_id, $user_id){
	$template=get_option('wp_jv_ces_set_content');	
	//Apply default template if no content setting present
	if (empty($template)) { 
		$template='Dear %recipient_name%,<br><br> A new post is published. Check it out!<br><br><strong>%title_with_permalink%</strong><br>%words_50%<br><br><small>In case you do not want to receive this kind of notification you can turn it off in your <a href="'.admin_url("profile.php").'">profile</a>.</small>';
		}
	$content=wp_jv_ces_resolve_tags($template,$post_id, $user_id);
	return $content; 
}

//Set Email format to HTML
function wp_jv_ces_html_email(){
		return 'text/html';
}

//Send out emails
function wp_jv_ces_send_mail($post_id){
	$logged_count=0;
	$sent_count=0;
	$sending_error_count=0;
	$result=array (
		'logged_count'=>$logged_count,
		'sent_count'=>$sent_count,
		'sending_error_count'=>$sending_error_count);
		
	$recipients=wp_jv_ces_prepare_mail_recipients($post_id);	
	if (empty($recipients)) {return $result;}
	
	foreach ($recipients as $value) {		
		//Make sure accented characters sent out correctly as well
		$mail_subject=mb_encode_mimeheader(wp_jv_ces_prepare_mail_subject($post_id,$value->user_id),'UTF-8');
		$mail_content=wp_jv_ces_prepare_mail_content($post_id, $value->user_id); 
		//if there is no subject or content then do not send mail
		if (empty($mail_subject)|| empty($mail_content)) {
			if (wp_jv_ces_log_mail_sent($value->user_id, $post_id, $value->user_email, 'Empty mail subject and/or content.')) {
				$sending_error_count=$sending_error_count+1;
				$logged_count=$logged_count+1;
				}
			else {$sending_error_count=$sending_error_count+1;}
		} 
		else {
			//Set mail type
			add_filter('wp_mail_content_type', 'wp_jv_ces_html_email');
			//Send mail							  
			$mail_status = wp_mail($value->user_email, $mail_subject, $mail_content ); 
			//Reinstate mail type
			remove_filter('wp_mail_content_type', 'wp_jv_ces_html_email');
			//Add log entry
			if ($mail_status) {
				$status_text='Successful';
				$sent_count=$sent_count+1;
			}
			else {
				$status_text='Failed';
				$sending_error_count=$sending_error_count+1;
			}
			if (wp_jv_ces_log_mail_sent($value->user_id, $post_id, $value->user_email, $status_text)) { $logged_count=$logged_count+1;}			
		}
	}
	$result=array (
		'logged_count'=>$logged_count,
		'sent_count'=>$sent_count,
		'sending_error_count'=>$sending_error_count);	
	return $result; 
}

//Add log entry
function wp_jv_ces_log_mail_sent($user_id, $post_id, $user_email, $status){	
	$result=false;
	global $wpdb;	
	$result=$wpdb->insert( 
		$table_name = $wpdb->prefix . 'jv_notification_log', 	
		array( 	
			'user_id' => $user_id, 
			'post_id' => $post_id, 
			'time_mail_sent' => current_time( 'mysql' ), 
			'user_email' => $user_email, 
			'status' => $status
			) 
	);	
	return $result;//if false then insert failed
}


function wp_jv_ces_notify($post_id) {
	$result=array();
	$mails=wp_jv_ces_send_mail($post_id);
	$logged_count=intval($mails['logged_count']);
	$sent_count=intval($mails['sent_count']);
	$sending_error_count=intval($mails['sending_error_count']);
	$log_page_url=admin_url('edit.php?page=wp_jv_ces_notification_log_menu_page');
	//to debug uncomment the following 
	/*
	//Test data
	$logged_count=<num_value>;
	$sent_count=<num_value>;
	$sending_error_count=<num_value>;
	*/
		
	//Not logged or sent out successfully any
	if ($logged_count==0 || $sent_count==0) { 
		$result=array(
			'error'	    => true,
			'error_msg'  => 'Not logged and/or sent out successfully any mails.',
			'logged_count'=> $logged_count,
			'sent_count'=> $sent_count,
			'sending_error_count'=> $sending_error_count,				
			'error_code' => 'F-03',
			'log_page_url'=>$log_page_url);			
	}		
	else {
		if ($logged_count==$sent_count && $sending_error_count==0) {
			$result=array(
				'error' => false,
				'logged_count'=> $logged_count,
				'sent_count'=> $sent_count,
				'sending_error_count'=> $sending_error_count,
				'log_page_url'=>$log_page_url);
		}
		else {
			$result=array(
				'error' => true,
				'error_msg'  => 'Something went wrong',
				'logged_count'=> $logged_count,
				'sent_count'=> $sent_count,
				'sending_error_count'=> $sending_error_count,
				'error_code' => 'F-04',
				'log_page_url'=>$log_page_url);
		}
		
		//Note that post notification sent
		$current_status=get_post_meta($post_id,'wp_jv_ces_notification_sent',true);		
		if ($current_status=='0' || empty($current_status) ) {
			$updated = update_post_meta($post_id,'wp_jv_ces_notification_sent','1');
			if (!$updated) { 								
				$result=array(
					'error'	    => true,											
					'error_msg'  => 'Something went wrong',
					'logged_count'=> $logged_count,
					'sent_count'=> $sent_count,
					'sending_error_count'=> $sending_error_count,															
					'error_code' => 'F-05',
					'log_page_url'=>$log_page_url);							
			}
		}			
	}			 	
	return $result;
}

//Sending manual notifications
function wp_jv_ces_send_notification_manual() {	
   //Avoid being easily hacked
	if (!isset($_POST['wp_jv_ces_nonce']) || !wp_verify_nonce($_POST['wp_jv_ces_nonce'],'wp_jv_ces_nonce')) {
		$result=array('error'	   => true,
			 		   'error_msg'  => 'Something went wrong',
					   'logged_count'=> 0,
					   'sent_count'=> 0,
					   'sending_error_count'=> 0,
					   'error_code' => 'F-02');
		header('Content-Type: application/json');
		die(json_encode($result));
	}			
	$result=array();
    $post_id = sanitize_text_field($_POST['post_id']);
	if(isset($_POST['post_id'])) {$result=wp_jv_ces_notify($post_id);}	
	//Post id was not available
	else { 
		$result=array(
			'error'	    => true,
			'error_msg'  => 'Something went wrong',
			'error_code' => 'F-06');		
	}
	header('Content-Type: application/json');
	die(json_encode($result));		
}
add_action('wp_ajax_wp_jv_ces_send_notification_manual','wp_jv_ces_send_notification_manual');

//Sending automatic notifications and display admin notice
function wp_jv_ces_send_notification_auto($post_id ) {
	
	if (empty($post_id)) {
		return;	
	}
	//Notify only in case of posts
	if (get_post($post_id)->post_type !='post') {
		return;
	}
	if (get_option('wp_jv_ces_set_notification_mode')!='Auto') {
		return; 
	}
	else {		
		$notification_sent=get_post_meta( $post_id, 'wp_jv_ces_notification_sent', true);				
		$pstatus=wp_jv_ces_post_status($post_id);
		$recipients=wp_jv_ces_prepare_mail_recipients($post_id);
		$hasrecipient=!empty($recipients);		
		if (in_array($pstatus, array('Public','Password protected','Private')) && (empty($notification_sent) || $notification_sent=='0') && $hasrecipient) {
			$message=null;
			$result=array();
			add_post_meta($post_id,'wp_jv_ces_notification_sent','0',true);
			$result=wp_jv_ces_notify($post_id);
			
			if ($result['error']) {
				//Error
				if ($result['logged_count']==0 || $result['sent_count']==0) {
					$message = '<br>'.__('Error sending notifications.','wp-jv-custom-email-settings').'<a href="'. $result['log_page_url']. '">'.__('Check log','wp-jv-custom-email-settings').'</a> '.__('for details.', 'wp-jv-custom-email-settings' );
				}
				else {
					//Warning
					set_transient('auto_notification_result', 'update-nag');						
					$message = '<br>'.$result['sent_count'].' '. __('email(s) out of','wp-jv-custom-email-settings').' '. ($result['sent_count']+$result['sending_error_count']).' '. __('sent with','wp-jv-custom-email-settings').' '. ($result['sent_count']+$result['sending_error_count']-$result['logged_count']).' ' .__('log issues.','wp-jv-custom-email-settings') .'<a href="'. $result['log_page_url']. '">'.__('Check log','wp-jv-custom-email-settings').'</a> '. __('for details.','wp-jv-custom-email-settings');
				}
			} 
			else {			
				//Emails sent successfully	
				set_transient('auto_notification_result', 'updated');
				$message = '<br>'.$result['sent_count'].' '. __('notification(s) sent.','wp-jv-custom-email-settings').' <a href="'. $result['log_page_url']. '">'.__('Check log','wp-jv-custom-email-settings').'</a> '.__('for details.','wp-jv-custom-email-settings');
			}				
			set_transient('auto_notification_message',$message );
			remove_action('transition_post_status', 'wp_jv_ces_send_notification_auto');
		} 		
		else {
			delete_transient('auto_notification_message');
			delete_transient('auto_notification_result');
		}
	}
}
add_action(  'save_post',  'wp_jv_ces_send_notification_auto', 10, 3 );

function wp_jv_ces_admin_notice_auto_notification() {
	$message=get_transient('auto_notification_message');
    if ( !empty($message)) {
		echo '<div class="'.get_transient('auto_notification_result').'" id="jv_ces_notification_div">';
		echo '<p>'._e( $message, 'wp_jv_ces_textdomain' ). '</p>';
		echo '</div>';		 	
        delete_transient('auto_notification_message');
		delete_transient('auto_notification_result');
    }
}
add_action('admin_notices', 'wp_jv_ces_admin_notice_auto_notification');

//#TODO: add export log functionality
//#TODO: add purge functionality: uncomment related code and configuration options; implement scheduler to truncate logs regularly + manually on view log page
//#TODO: add filter/search for Notification log page
?>