<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class PDPA_THAILAND_Scanner
{

    public function __construct()
    {        
        add_action( 'admin_menu', array($this, 'add_menu_links') );		
        add_action( 'admin_enqueue_scripts', array($this, 'admin_enqueue') );
    }

    public function admin_enqueue()
    {
        // Main CSS
		wp_enqueue_style( 'pdpa-thailand-admin', PDPA_THAILAND_URL . 'admin/assets/css/pdpa-thailand-admin.min.css', '', PDPA_THAILAND_VERSION );
    }

    public function add_menu_links()
    {            
        add_submenu_page(
            'pdpa-thailand',
            __( 'Cookie Scanner', 'designil-ddpa' ),
            __( 'Cookie Scanner (PRO)', 'designil-ddpa' ),
            'manage_options',
            'pdpa-thailand-scanner',
            array($this, 'scanner_callback')
        );
    }

    public function scanner_callback()
    {
        ?>
        <div class="wrap">	
			<h1>PDPA Thailand - Cookie Scanner</h1>
            <hr>
            <?php if (isset($_COOKIE)) : ?>
                <p class="pdpa--found-cookie"><?php printf( __( 'Found <b>%s cookies(s)</b> in this website', 'pdpa-thailand' ), count($_COOKIE) ); ?></p>
            <?php endif; ?>
            <div class="pdpa--cookie-list">
                <table>
                    <thead>
                        <th><?php _e('Cookie Name' ,'pdpa-thailand'); ?></th>                        
                    </thead>
                    <tbody>
                        <tr>
                            <td><a href="https://designilpdpa.com" target="_blank"><?php _e('Get pro version to see more', 'pdpa-thailand'); ?></a></td>                                
                        </tr>
                    </tbody>
                </table>
            </div>            
        </div>
        <?php
    }
    
}