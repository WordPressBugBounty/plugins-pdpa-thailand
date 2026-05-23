<?php
/**
 * Plugin Name: PDPA Thailand
 * Plugin URI: https://www.designilpdpa.com
 * Description: Support Thai PDPA law by manage cookie systematic and allow to ask consent from user
 * Author: do action
 * Author URI: https://doaction.co.th
 * Version: 2.0.2
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
if ( ! defined( 'PDPA_THAILAND_VERSION' ) ) 		define( 'PDPA_THAILAND_VERSION', '2.0.2' ); // Plugin version constant
if ( ! defined( 'PDPA_THAILAND' ) )		define( 'PDPA_THAILAND'		, trim( dirname( plugin_basename( __FILE__ ) ), '/' ) ); // Name of the plugin folder eg - 'pdpa-thailand'
if ( ! defined( 'PDPA_THAILAND_DIR' ) )	define( 'PDPA_THAILAND_DIR'	, plugin_dir_path( __FILE__ ) ); // Plugin directory absolute path with the trailing slash. Useful for using with includes eg - /var/www/html/wp-content/plugins/pdpa-thailand/
if ( ! defined( 'PDPA_THAILAND_URL' ) )	define( 'PDPA_THAILAND_URL'	, plugin_dir_url( __FILE__ ) ); // URL to the plugin folder with the trailing slash. Useful for referencing src eg - http://localhost/wp/wp-content/plugins/pdpa-thailand/

/**
 * Allowed HTML for short labels (titles). No script/style/event handlers.
 *
 * @return array<string, array<string, bool>>
 */
function pdpa_thailand_allowed_title_html() {
	return array(
		'a'      => array(
			'href'   => true,
			'title'  => true,
			'target' => true,
			'rel'    => true,
		),
		'strong' => array(),
		'em'     => array(),
		'br'     => array(),
	);
}

/**
 * Sanitize content that may include safe HTML (descriptions, sidebar text).
 * Use on save AND on output.
 */
function pdpa_thailand_kses_description( $content ) {
	return wp_kses_post( (string) $content );
}

/**
 * Sanitize titles that may include limited inline HTML.
 */
function pdpa_thailand_kses_title( $content ) {
	return wp_kses( (string) $content, pdpa_thailand_allowed_title_html() );
}

/**
 * Recursively apply wp_kses_post() to array values.
 *
 * @param mixed $data Scalar or array.
 * @return mixed
 */
function pdpa_thailand_recursive_kses_description( $data ) {
	if ( ! is_array( $data ) ) {
		return pdpa_thailand_kses_description( $data );
	}

	foreach ( $data as $key => &$value ) {
		if ( is_array( $value ) ) {
			$value = pdpa_thailand_recursive_kses_description( $value );
		} else {
			$value = pdpa_thailand_kses_description( $value );
		}
	}

	return $data;
}

/**
 * Safely read serialized plugin option data saved by older versions.
 *
 * @param mixed $data Raw option value.
 * @return array
 */
function pdpa_thailand_maybe_unserialize_option( $data ) {
	if ( is_array( $data ) ) {
		return $data;
	}

	if ( ! is_string( $data ) || '' === $data ) {
		return array();
	}

	$value = maybe_unserialize( $data );

	return is_array( $value ) ? $value : array();
}

/**
 * Whether the consent UI should be active (matches legacy JS enable flag).
 *
 * @param array $settings pdpa_thailand_settings option.
 * @return bool
 */
function pdpa_thailand_is_consent_enabled( $settings ) {
	if ( ! is_array( $settings ) ) {
		return false;
	}

	return isset( $settings['is_enable'] ) && (int) $settings['is_enable'] === 1;
}

class PDPA_THAILAND
{
    private $options;

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