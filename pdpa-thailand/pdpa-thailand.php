<?php
/**
 * Plugin Name: PDPA Thailand
 * Plugin URI: https://www.designilpdpa.com
 * Description: Support Thai PDPA law by manage cookie systematic and allow to ask consent from user
 * Author: do action
 * Author URI: https://doaction.co.th
 * Version: 2.0
 * Text Domain: pdpa-thailand
 * Domain Path: /languages
 * License: GPL v2 - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Define constants
 *
 * @since 1.1
 */
if ( ! defined( 'PDPA_THAILAND_VERSION' ) ) 		define( 'PDPA_THAILAND_VERSION', '2.0' ); // Plugin version constant
if ( ! defined( 'PDPA_THAILAND' ) )		define( 'PDPA_THAILAND'		, trim( dirname( plugin_basename( __FILE__ ) ), '/' ) ); // Name of the plugin folder eg - 'pdpa-thailand'
if ( ! defined( 'PDPA_THAILAND_DIR' ) )	define( 'PDPA_THAILAND_DIR'	, plugin_dir_path( __FILE__ ) ); // Plugin directory absolute path with the trailing slash. Useful for using with includes eg - /var/www/html/wp-content/plugins/pdpa-thailand/
if ( ! defined( 'PDPA_THAILAND_URL' ) )	define( 'PDPA_THAILAND_URL'	, plugin_dir_url( __FILE__ ) ); // URL to the plugin folder with the trailing slash. Useful for referencing src eg - http://localhost/wp/wp-content/plugins/pdpa-thailand/

class PDPA_THAILAND
{

    public function __construct()
    {
        $this->options = get_option('pdpa_thailand_settings');
        $this->loader(); 
    }

    public function loader()
    {
        // ADMIN
        if (is_admin()) {
            require_once(PDPA_THAILAND_DIR . 'admin/admin.php');
            // require_once(PDPA_THAILAND_DIR . 'admin/admin-scanner.php');
            new PDPA_THAILAND_Admin;            
            // new PDPA_THAILAND_Scanner;

            register_activation_hook( __FILE__, array($this, 'activate_plugin'));        
        }

        // PUBLIC
        require_once(PDPA_THAILAND_DIR . 'public/public.php');
        new PDPA_THAILAND_Public;
    }

	public function activate_plugin() 
	{   
        deactivate_plugins( '/designil-pdpa/designil-pdpa.php' );
	}
}

new PDPA_THAILAND;