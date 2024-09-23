<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * Everything in uninstall.php will be executed when user decides to delete the plugin. 
 * @since		1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// If uninstall not called from WordPress, then die.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) die;

/**
 * Delete database settings
 *
 * @since		1.0
 */ 

// Delete temp file
// $files = glob(WP_CONTENT_URL . '/pdpa-thailand'); // get all file names
// if ( isset($files) ) {
//     foreach ( $files as $file ) { // iterate files
//         if ( is_file($file) ) {
//             unlink($file); // delete file
//         }
//     }
// }

delete_option('pdpa_thailand_settings');
delete_option('pdpa_thailand_msg');
delete_option('pdpa_thailand_cookies');
delete_option('pdpa_thailand_appearance');
delete_option('pdpa_thailand_js_version');
delete_option('pdpa_thailand_css_version');	
delete_option('pdpa_thailand_license_key');
delete_option('pdpa_thailand_license_status');
delete_option('pdpa_thailand_settings');
delete_option('pdpa_thailand_installed');