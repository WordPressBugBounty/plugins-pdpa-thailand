<?php

class PDPA_THAILAND_Public
{

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
        if (isset($_COOKIE['dpdpa_consent'])) {
            $this->cookie = json_decode($_COOKIE['dpdpa_consent']);
            $this->cookie = $this->pdpa_thailand_recursive_sanitize_text_field($this->cookie);
        }



        // Set cookie
        $this->cookie_set = array();
        $this->choices = array();
        $this->cookie_count = 0;
        $this->cookie_list = array();
        $this->cookie_list_js = '';

        // Set script in array set
        if (isset($this->cookies['cookie_list'])) {
            $this->cookie_list = unserialize($this->cookies['cookie_list']);

            $cookie_count = 0;
            if (isset($this->cookie_list['cookie_name']))
                $this->cookie_count = count($this->cookie_list['cookie_name']);
        }

        // Set cookie array
        if ($this->cookie_count != 0) {
            for ($i = 0; $i <= ($this->cookie_count - 1); $i++) {
                $cookie_set[$this->cookie_list['cookie_name'][$i]] = array(
                    'consent_title' => $this->cookie_list['consent_title'][$i],
                    'consent_description' => $this->cookie_list['consent_description'][$i],
                    'code_in_head' => '',
                    'code_next_body' => '',
                    'code_body_close' => ''
                );
                // $this->cookie_list_default($cookie_set);
            }
            $this->cookie_set = $cookie_set;
        }

        // Set label for cookie necessary
        $this->cookie_necessary = array(
            'cookie_necessary_title' => '',
            'cookie_necessary_description' => ''
        );

        if (isset($this->cookies['cookie_necessary'])) {
            $this->cookie_necessary = unserialize($this->cookies['cookie_necessary']);
        }

        // Cookie list
        $code_in_head = array();
        $code_next_body = array();
        $code_body_close = array();

        // Preaparing code in array
        if ($this->cookie_count != -1) {
            for ($i = 0; $i <= ($this->cookie_count - 1); $i++) {
                $code_in_head[$this->cookie_list['cookie_name'][$i]][] = '';
                $code_next_body[$this->cookie_list['cookie_name'][$i]][] = '';
                $code_body_close[$this->cookie_list['cookie_name'][$i]][] = '';
            }
        }

        $this->cookie_list_js = json_encode(array(
            'code_in_head' => '',
            'code_next_body' => '',
            'code_body_close' => '',
        ));

        add_action('wp_enqueue_scripts', array($this, 'public_enqueue'));
        add_action('wp_footer', array($this, 'cookie_template'));

        // SHORTCODE
        add_shortcode('dpdpa_settings', array($this, 'shortcode_dpdpa_settings'));
        add_shortcode('dpdpa_policy_page', array($this, 'shortcode_dpdpa_policy_page'));

        // TEXT DOMAIN
        add_action('plugins_loaded', array($this, 'load_plugin_textdomain'));
    }

    public function pdpa_thailand_recursive_sanitize_text_field($array)
    {
        if (!empty($array)) {
            foreach ($array as $key => &$value) {
                if (is_array($value)) {
                    $value = $this->pdpa_thailand_recursive_sanitize_text_field($value);
                } else {
                    $value = sanitize_text_field($value);
                }
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
            echo '<img src="' . $src . '">';
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
            echo $this->msg['sidebar_message'];
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

        return '<a href="#" class="dpdpa--popup-settings">' . $atts["title"] . '</a>';
    }

    public function shortcode_dpdpa_policy_page($atts)
    {
        if (!isset($atts["title"]))
            $atts["title"] = __('Privacy policy', 'pdpa-thailand');

        return '<a href="' . get_the_permalink($this->msg['policy_page']) . '">' . $atts["title"] . '</a>';
    }

    public function public_enqueue()
    {
        // Main CSS
        wp_enqueue_style('pdpa-thailand-public', PDPA_THAILAND_URL . 'public/assets/css/pdpa-thailand-public.min.css', '', PDPA_THAILAND_VERSION);
        wp_add_inline_style('pdpa-thailand-public', get_transient('pdpa_thailand_style'));
        // Main JS
        wp_enqueue_script('pdpa-thailand-js-cookie', PDPA_THAILAND_URL . 'public/assets/js/js-cookie.min.js', array(), PDPA_THAILAND_VERSION, true);
        wp_enqueue_script('pdpa-thailand-public', PDPA_THAILAND_URL . 'public/assets/js/pdpa-thailand-public.js', array(), PDPA_THAILAND_VERSION, true);
        wp_add_inline_script('pdpa-thailand-public', get_transient('pdpa_thailand_script'));

        $enable = 0;

        if (isset($this->options['is_enable'])) {
            $enable = $this->options['is_enable'];
        }

        wp_localize_script(
            'pdpa-thailand-public',
            'pdpa_thailand',
            array(
                'url'   => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('pdpa_thailand_nonce'),
                'unique_id' => $this->options['cookie_unique_id'],
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
