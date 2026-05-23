<?php

class PDPA_THAILAND_Public
{
    private $options;
    private $msg;
    private $cookies;
    private $appearance;
    private $css_version;
    private $js_version;
    private $license_status;
    private $temp_path_url;
    private $cookie;
    private $multi_site;
    private $duration;
    private $cookie_set;
    private $choices;
    private $cookie_count;
    private $cookie_list;
    private $cookie_list_js;
    private $cookie_necessary;

    public function __construct()
    {
        // Vartiable
        $this->options = get_option('pdpa_thailand_settings');
        $this->msg = get_option('pdpa_thailand_msg');
        $this->cookies = get_option('pdpa_thailand_cookies');
        $this->appearance = get_option('pdpa_thailand_appearance');
        $this->css_version = get_option('pdpa_thailand_css_version');
        $this->js_version = get_option('pdpa_thailand_js_version');
        $this->license_status  = get_option('pdpa_thailand_license_status');
        $this->temp_path_url = WP_CONTENT_URL . '/pdpa-thailand';
        $this->cookie = '';
        $this->multi_site = '';
        $this->duration = 7;

        // For multi site		
        $this->multi_site = '';

        if (is_multisite()) {
            $this->multi_site = '/' . get_current_blog_id();
            $this->temp_path_url .= $this->multi_site;
        }

        // Get consent from user by cookie
        if ( isset( $_COOKIE['dpdpa_consent'] ) ) {
            $decoded = json_decode( wp_unslash( $_COOKIE['dpdpa_consent'] ), true );
            $this->cookie = is_array( $decoded ) ? $this->pdpa_thailand_recursive_sanitize_text_field( $decoded ) : array();
        }



        // Set cookie
        $this->cookie_set = array();
        $this->choices = array();
        $this->cookie_count = 0;
        $this->cookie_list = array();
        $this->cookie_list_js = '';

        // Set script in array set (legacy serialized option — read-only, never modified on load).
        if ( isset( $this->cookies['cookie_list'] ) ) {
            $this->cookie_list = pdpa_thailand_maybe_unserialize_option( $this->cookies['cookie_list'] );

            if ( isset( $this->cookie_list['cookie_name'] ) && is_array( $this->cookie_list['cookie_name'] ) ) {
                $this->cookie_count = count( $this->cookie_list['cookie_name'] );
            }
        }

        // Set cookie array
        if ( $this->cookie_count > 0 ) {
            $cookie_set = array();

            for ( $i = 0; $i < $this->cookie_count; $i++ ) {
                if ( empty( $this->cookie_list['cookie_name'][ $i ] ) ) {
                    continue;
                }

                $cookie_set[ $this->cookie_list['cookie_name'][ $i ] ] = array(
                    'consent_title'       => isset( $this->cookie_list['consent_title'][ $i ] ) ? $this->cookie_list['consent_title'][ $i ] : '',
                    'consent_description' => isset( $this->cookie_list['consent_description'][ $i ] ) ? $this->cookie_list['consent_description'][ $i ] : '',
                    'code_in_head'        => '',
                    'code_next_body'      => '',
                    'code_body_close'     => '',
                );
            }

            $this->cookie_set = $cookie_set;
        }

        // Set label for cookie necessary
        $this->cookie_necessary = array(
            'cookie_necessary_title' => '',
            'cookie_necessary_description' => ''
        );

        if ( isset( $this->cookies['cookie_necessary'] ) ) {
            $cookie_necessary = pdpa_thailand_maybe_unserialize_option( $this->cookies['cookie_necessary'] );
            $this->cookie_necessary = wp_parse_args( $cookie_necessary, $this->cookie_necessary );
        }

        // Cookie list
        $code_in_head = array();
        $code_next_body = array();
        $code_body_close = array();

        // Preaparing code in array
        if ( $this->cookie_count > 0 ) {
            for ( $i = 0; $i < $this->cookie_count; $i++ ) {
                if ( empty( $this->cookie_list['cookie_name'][ $i ] ) ) {
                    continue;
                }

                $code_in_head[ $this->cookie_list['cookie_name'][ $i ] ][]      = '';
                $code_next_body[ $this->cookie_list['cookie_name'][ $i ] ][]   = '';
                $code_body_close[ $this->cookie_list['cookie_name'][ $i ] ][] = '';
            }
        }

        $this->cookie_list_js = json_encode(array(
            'code_in_head' => '',
            'code_next_body' => '',
            'code_body_close' => '',
        ));

        // Always register frontend assets (legacy behaviour). Popup visibility still uses is_enable in JS.
        add_action( 'wp_enqueue_scripts', array( $this, 'public_enqueue' ) );
        add_action( 'wp_footer', array( $this, 'cookie_template' ) );

        // SHORTCODE
        add_shortcode('dpdpa_settings', array($this, 'shortcode_dpdpa_settings'));
        add_shortcode('dpdpa_policy_page', array($this, 'shortcode_dpdpa_policy_page'));

        // TEXT DOMAIN
        add_action('plugins_loaded', array($this, 'load_plugin_textdomain'));
    }

    public function pdpa_thailand_recursive_sanitize_text_field( $array ) {
        if ( ! is_array( $array ) ) {
            return sanitize_text_field( (string) $array );
        }

        foreach ( $array as $key => &$value ) {
            if ( is_array( $value ) ) {
                $value = $this->pdpa_thailand_recursive_sanitize_text_field( $value );
            } else {
                $value = sanitize_text_field( (string) $value );
            }
        }

        return $array;
    }

    public function load_plugin_textdomain()
    {
        load_plugin_textdomain('pdpa-thailand', false, PDPA_THAILAND . '/languages/');
    }

    public function show_logo()
    {
        if (isset($this->appearance['appearance_logo']) && $this->appearance['appearance_logo'] != '') {
            // Get small thumbnail
            $src = wp_get_attachment_image_src($this->appearance['appearance_logo'], 'thumbnail')[0];
            echo '<img src="' . esc_url( $src ) . '" alt="">';
        }
    }

    public function show_cookie_consent_message()
    {
        if (isset($this->msg['cookie_consent_message']))
            echo do_shortcode($this->msg['cookie_consent_message']);
    }

    public function show_sidebar_message()
    {
        if (isset($this->msg['sidebar_message']))
            echo pdpa_thailand_kses_description( $this->msg['sidebar_message'] );
    }

    public function reject_button()
    {
        if (isset($this->options['reject_button']) && $this->options['reject_button'] == 1)
            echo '<a href="#" class="dpdpa--popup-button" id="dpdpa--popup-reject-all">' . __('Reject All', 'pdpa-thailand') . '</a>';
    }

    public function shortcode_dpdpa_settings($atts)
    {
        if (!isset($atts["title"]))
            $atts["title"] = __('Cookies settings', 'pdpa-thailand');

        return '<a href="#" class="dpdpa--popup-settings">' . esc_html( $atts['title'] ) . '</a>';
    }

    public function shortcode_dpdpa_policy_page($atts)
    {
        if (!isset($atts["title"]))
            $atts["title"] = __('Privacy policy', 'pdpa-thailand');

        $policy_page = isset( $this->msg['policy_page'] ) ? absint( $this->msg['policy_page'] ) : 0;
        $permalink   = $policy_page ? get_the_permalink( $policy_page ) : '';

        if ( $permalink ) {
            return '<a href="' . esc_url( $permalink ) . '">' . esc_html( $atts['title'] ) . '</a>';
        }

        // Legacy fallback: show label text when policy page is not configured yet.
        return esc_html( $atts['title'] );
    }

    public function public_enqueue()
    {
        // Main CSS
        wp_enqueue_style('pdpa-thailand-public', PDPA_THAILAND_URL . 'public/assets/css/pdpa-thailand-public.min.css', '', PDPA_THAILAND_VERSION);
        wp_add_inline_style('pdpa-thailand-public', get_transient('pdpa_thailand_style'));
        // Main JS
        wp_enqueue_script('pdpa-thailand-js-cookie', PDPA_THAILAND_URL . 'public/assets/js/js-cookie.min.js', array(), PDPA_THAILAND_VERSION, true);
        wp_enqueue_script('pdpa-thailand-public', PDPA_THAILAND_URL . 'public/assets/js/pdpa-thailand-public.min.js', array(), PDPA_THAILAND_VERSION, true);
        wp_add_inline_script('pdpa-thailand-public', get_transient('pdpa_thailand_script'));

        $enable = pdpa_thailand_is_consent_enabled( is_array( $this->options ) ? $this->options : array() ) ? 1 : 0;

        wp_localize_script(
            'pdpa-thailand-public',
            'pdpa_thailand',
            array(
                'url'   => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('pdpa_thailand_nonce'),
                'unique_id' => isset( $this->options['cookie_unique_id'] ) ? $this->options['cookie_unique_id'] : '',
                'enable' => $enable,
                'duration' => $this->duration,
                'cookie_list' => $this->cookie_list_js,
            )
        );
    }

    // Load template
    public function cookie_template()
    {
        include_once(PDPA_THAILAND_DIR . "template/popup.php");
        include_once(PDPA_THAILAND_DIR . "template/sidebar.php");
    }
}
