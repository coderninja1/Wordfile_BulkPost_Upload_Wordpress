<?php
   /*
   Plugin Name: Publish Bulk Posts
   Plugin URI: https://www.wmsindia.com/
   Version: 1
   Author: WMSIndia
   Author URI: https://www.wmsindia.com/
   */ 
error_reporting(0);
function ss_options_install() {
    global $wpdb;
    $table_name = $wpdb->prefix . "file_status";
	$sql = "CREATE TABLE $table_name (
			`id` int(255) NOT NULL,
			`file_name` varchar(255) NOT NULL,
			`upload_status` enum('0','1') NOT NULL DEFAULT '0',
			`publish_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
	) ENGINE=InnoDB DEFAULT CHARSET=latin1;";
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta($sql);
}
register_activation_hook(__FILE__, 'ss_options_install');

add_action('admin_menu','bulk_post_publish');
function bulk_post_publish(){
	add_menu_page('Bulk Post Publish',
	'Bulk Post Publish', 
	'manage_options', 
	'bulk_post_publish_setting', 
	'bulk_post_publish_setting',
	 plugins_url( 'post-doc/css/images/icon.png' )
	);	
}



define('ROOTDIR', plugin_dir_path(__FILE__));
require_once(ROOTDIR . 'bulk_publish_setting.php');
?>
