<?php
if (!defined('ABSPATH')) exit;

class PDPA_THAILAND_Admin
{
	private $options;

	public function __construct()
	{
		// $scanner = new PDPA_THAILAND_Scanner();

		// OPTIONS
		$this->options = get_option('pdpa_thailand_settings');
		$this->msg = get_option('pdpa_thailand_msg');
		$this->cookies = get_option('pdpa_thailand_cookies');
		$this->appearance = get_option('pdpa_thailand_appearance');
		$this->js_version = get_option('pdpa_thailand_js_version');
		$this->css_version = get_option('pdpa_thailand_css_version');
		$this->temp_path = WP_CONTENT_DIR . '/pdpa-thailand';
		$this->cookie_count = 0;

		// Default txt
		if (isset($this->msg['cookie_consent_message']) && $this->msg['cookie_consent_message'] == '') {
			$this->msg['cookie_consent_message'] = 'เราใช้คุกกี้เพื่อพัฒนาประสิทธิภาพ และประสบการณ์ที่ดีในการใช้เว็บไซต์ของคุณ คุณสามารถศึกษารายละเอียดได้ที่ [dpdpa_policy_page title="นโยบายความเป็นส่วนตัว"] และสามารถจัดการความเป็นส่วนตัวเองได้ของคุณได้เองโดยคลิกที่ [dpdpa_settings title="ตั้งค่า"]';
		}

		if (isset($this->msg['sidebar_message']) && $this->msg['sidebar_message'] == '') {
			$this->msg['sidebar_message'] = 'คุณสามารถเลือกการตั้งค่าคุกกี้โดยเปิด/ปิด คุกกี้ในแต่ละประเภทได้ตามความต้องการ ยกเว้น คุกกี้ที่จำเป็น';
		}

		//

		// For multi site		
		$this->multi_site = '';

		if (is_multisite()) {
			$this->multi_site = '/' . get_current_blog_id();
			$this->temp_path .= $this->multi_site;
		}

		// HOOK
		add_action('admin_head', array($this, 'admin_custom_css_js'));
		add_action('admin_menu', array($this, 'add_menu_links'));
		add_action('admin_init', array($this, 'register_settings'));
		add_action('admin_init', array($this, 'install_plugin'));
		add_action('admin_enqueue_scripts', array($this, 'admin_enqueue'));
		add_action('plugins_loaded', array($this, 'load_plugin_textdomain'));
		add_filter('display_post_states', array($this, 'post_states'), 10, 2);
		add_filter('plugin_action_links_' . PDPA_THAILAND . '/pdpa-thailand.php', array($this, 'settings_link'));
		add_filter('plugin_row_meta', array($this, 'plugin_row_meta'), 10, 2);
		add_filter('admin_footer_text', array($this, 'footer_text'));

		// AJAX
		add_action('wp_ajax_reset_cookie_id', array($this, 'reset_cookie_id'));
		add_action('wp_ajax_rating_saved', array($this, 'rating_saved'));

		// Init unqiue_id
		if ($this->options == '' && !isset($this->options['cookie_unique_id'])) {
			$this->options = array(
				'cookie_unique_id' => uniqid('pdpa_')
			);
			update_option('pdpa_thailand_settings', $this->options);
		}

		if ($this->js_version == '' && !isset($this->js_version)) {
			update_option('pdpa_thailand_js_version', rand());
		}

		if ($this->css_version == '' && !isset($this->css_version)) {
			update_option('pdpa_thailand_css_version', rand());
		}
	}

	public function admin_custom_css_js()
	{
?>
		<style>
			.toplevel_page_pdpa-thailand>div.wp-menu-image:before {
				background: url("data:image/svg+xml,%3Csvg width='82' height='89' viewBox='0 0 82 89' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M52.63 14C52.55 13.64 52.44 13.29 52.34 12.93C52.24 12.57 52.22 12.44 52.15 12.2C50.809 10.023 49.1392 8.06649 47.2 6.4C43.8643 3.50171 39.8134 1.54936 35.4681 0.745724C31.1229 -0.0579131 26.6417 0.316474 22.49 1.83C25.8206 2.42654 28.9657 3.79331 31.6742 5.82125C34.3827 7.84918 36.5798 10.4821 38.09 13.51L38 13.44C37.59 13.04 37.18 12.67 36.77 12.3C32.3725 8.51835 26.7649 6.43878 20.965 6.43878C15.1651 6.43878 9.55747 8.51835 5.16 12.3C4.84 12.57 4.54 12.87 4.25 13.16C3.96 13.45 3.45 13.97 3.05 14.39C2.83 14.63 2.6 14.87 2.39 15.13C1.56 16 0.77 17 0 18C4.39771 16.1012 9.22643 15.4247 13.9766 16.0419C18.7268 16.6591 23.2224 18.547 26.9888 21.5066C30.7553 24.4661 33.6531 28.3874 35.3764 32.8568C37.0997 37.3262 37.5846 42.1779 36.78 46.9C36.78 47.01 36.72 47.12 36.7 47.23C37.48 46.85 38.25 46.43 39 45.97C43.151 43.4535 46.6222 39.9583 49.11 35.79C49.2 35.63 49.28 35.47 49.37 35.32C53.0429 28.8565 54.2036 21.2657 52.63 14Z' fill='%23FF9400'/%3E%3Cpath d='M71.83 15.74C72.46 16.53 73.05 17.34 73.61 18.17C77.3796 23.756 79.508 30.2868 79.7537 37.0212C79.9994 43.7557 78.3525 50.4242 75 56.27C71.3006 62.0939 65.8998 66.6395 59.5299 69.2905C53.16 71.9415 46.129 72.5697 39.39 71.09C37.7347 70.7255 36.1099 70.2341 34.53 69.62C35.5295 70.8662 36.7415 71.9259 38.11 72.75C39.4614 73.5517 40.9444 74.107 42.49 74.39H41.87C41.0633 74.35 40.2533 74.28 39.44 74.18H39.33C37.7656 73.9893 36.2156 73.6953 34.69 73.3L34.45 73.23C33.72 73.04 33 72.83 32.28 72.59H32.22C31.45 72.32 30.68 72.04 29.92 71.72C29.21 71.42 28.5 71.1 27.8 70.72L27.38 70.52C26.67 70.15 25.96 69.77 25.27 69.36C24.58 68.95 23.98 68.56 23.35 68.13L23.19 68L23 67.89C22.48 67.53 22 67.15 21.47 66.76H21.4C17.1644 63.4035 13.7368 59.1385 11.37 54.28C11.02 53.57 10.7 52.85 10.37 52.12L10.13 51.51C9.90001 50.92 9.69001 50.34 9.50001 49.75C9.44001 49.57 9.37001 49.39 9.32001 49.21C9.09001 48.5 8.88001 47.77 8.70001 47.05L8.58001 46.61C8.58001 46.61 7.89001 48.31 7.73001 48.76C6.47686 52.1337 6.09481 55.769 6.61919 59.3295C7.14356 62.89 8.55744 66.2608 10.73 69.13C11.9068 70.6868 13.2933 72.0732 14.85 73.25C12.37 74.32 9.60827 74.5504 6.98529 73.9059C4.3623 73.2614 2.02176 71.7775 0.320007 69.68C3.76287 74.6335 8.17025 78.8409 13.2781 82.0504C18.3859 85.2598 24.089 87.405 30.0457 88.3577C36.0025 89.3103 42.0902 89.0506 47.9442 87.5942C53.7982 86.1378 59.2979 83.5147 64.1139 79.882C68.9298 76.2493 72.9629 71.6818 75.9715 66.4532C78.9801 61.2246 80.9022 55.4424 81.6227 49.4532C82.3432 43.4639 81.8472 37.3909 80.1645 31.5979C78.4818 25.8049 75.647 20.4112 71.83 15.74Z' fill='%23FF3500'/%3E%3Cpath d='M73.61 18.17C73.05 17.34 72.46 16.53 71.83 15.74C71.38 15.19 70.91 14.65 70.44 14.11C63.8419 6.78268 55.0198 1.8253 45.33 0C46.0575 0.788372 46.7451 1.61274 47.39 2.47C47.93 3.19 48.39 3.94 48.89 4.7C50.3068 7.03249 51.4033 9.54504 52.15 12.17C52.22 12.41 52.28 12.66 52.34 12.9C52.4 13.14 52.55 13.61 52.63 13.97C54.2113 21.2452 53.0503 28.8482 49.37 35.32C49.28 35.47 49.2 35.63 49.11 35.79C46.9318 39.4575 43.9924 42.6151 40.49 45.05C40 45.39 39.49 45.71 39 46.05C38.25 46.51 37.48 46.93 36.7 47.31L36.53 47.41C34.8439 48.2337 33.0868 48.9031 31.28 49.41C23.648 51.493 15.5025 50.4833 8.61 46.6L8.73 47.04C8.91 47.76 9.12 48.49 9.35 49.2C9.4 49.38 9.47 49.56 9.53 49.74C9.72 50.33 9.93 50.91 10.16 51.5L10.4 52.11C10.7 52.84 11.02 53.56 11.4 54.27C12.1804 55.8568 13.0761 57.3842 14.08 58.84C14.58 59.58 15.08 60.29 15.68 60.99C17.3876 63.1094 19.33 65.0283 21.47 66.71H21.54C22.04 67.1 22.54 67.48 23.07 67.84L23.22 67.95L23.38 68.07C24.01 68.5 24.64 68.91 25.3 69.3C25.96 69.69 26.7 70.09 27.41 70.46L27.83 70.66C28.53 71.01 29.24 71.33 29.95 71.66C30.71 71.98 31.48 72.26 32.25 72.53H32.31C33.03 72.77 33.75 72.98 34.48 73.17L34.72 73.24C36.2456 73.6353 37.7956 73.9293 39.36 74.12H39.47C40.2833 74.22 41.0933 74.29 41.9 74.33H42.52C41.3722 74.1166 40.2564 73.757 39.2 73.26C38.84 73.08 38.49 72.9 38.14 72.69C36.7715 71.8659 35.5595 70.8062 34.56 69.56C36.1398 70.1741 37.7647 70.6655 39.42 71.03C40.04 71.17 40.65 71.28 41.27 71.38C46.213 72.1848 51.2733 71.8771 56.0825 70.4794C60.8916 69.0816 65.3284 66.629 69.07 63.3C69.68 62.75 70.28 62.17 70.85 61.57C72.4075 59.9469 73.7978 58.1713 75 56.27C78.3525 50.4242 79.9994 43.7557 79.7537 37.0212C79.508 30.2868 77.3796 23.756 73.61 18.17Z' fill='%23FC7E05'/%3E%3C/svg%3E%0A") no-repeat center center;
				background-size: contain;
				width: 16px;
				height: 20px;
				content: "";
			}

			.toplevel_page_pdpa-thailand li:last-child a[href^="https://designilpdpa.com"] {
				background-color: #FC7E08 !important;
				color: #ffffff !important;
			}

			[data-slug="pdpa-thailand"] .go_pro a {
				color: #fc7e05;
				font-weight: bold;
			}
		</style>
		<script>
			jQuery(document).ready(function() {
				// Add target="_blank" to links starting with "https://designilpdpa.com"
				jQuery('.toplevel_page_pdpa-thailand').find('a[href^="https://designilpdpa.com"]').attr('target', '_blank');
			});
		</script>
		</script>
	<?php
	}

	public function add_menu_links()
	{
		add_menu_page(
			__('PDPA Thailand', 'pdpa-thailand'),
			__('PDPA Thailand', 'pdpa-thailand'),
			'update_core',
			'pdpa-thailand',
			array($this, 'admin_interface_render'),
			''
		);

		add_submenu_page(
			'pdpa-thailand',
			esc_html__('Premium Features', 'pdpa-thailand'),
			esc_html__('Premium Features', 'pdpa-thailand'),
			'manage_options',
			admin_url('admin.php?page=pdpa-thailand&tab=freevspro')
		);

		add_submenu_page(
			'pdpa-thailand',
			esc_html__('Upgrade 30% Off!', 'pdpa-thailand'),
			esc_html__('Upgrade 30% Off!', 'pdpa-thailand'),
			'manage_options',
			'https://designilpdpa.com/checkout?edd_action=add_to_cart&download_id=29&edd_options%5Bprice_id%5D=1&discount=UPGRADE'
		);
	}

	public function install_plugin()
	{
		if (!get_option('pdpa_thailand_installed')) {
			include_once(PDPA_THAILAND_DIR . 'admin/template/policy-page.php');
			$policy_page_id = wp_insert_post(array(
				'post_title'     => 'นโยบายความเป็นส่วนตัว',
				'post_type'      => 'page',
				'post_name'      => 'privacy-policy',
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
				'post_content'   => pdpa_thailand_policy_page(),
				'post_status'    => 'publish',
				'post_author'    => get_current_user_id(),
				'menu_order'     => 0,
			));

			update_option('pdpa_thailand_msg',  array(
				'policy_page' => $policy_page_id,
				'cookie_consent_message' => 'เราใช้คุกกี้เพื่อพัฒนาประสิทธิภาพ และประสบการณ์ที่ดีในการใช้เว็บไซต์ของคุณ คุณสามารถศึกษารายละเอียดได้ที่ [dpdpa_policy_page title="นโยบายความเป็นส่วนตัว"] และสามารถจัดการความเป็นส่วนตัวเองได้ของคุณได้เองโดยคลิกที่ [dpdpa_settings title="ตั้งค่า"]',
				'sidebar_message' => 'คุณสามารถเลือกการตั้งค่าคุกกี้โดยเปิด/ปิด คุกกี้ในแต่ละประเภทได้ตามความต้องการ ยกเว้น คุกกี้ที่จำเป็น'
			));
			update_option('pdpa_thailand_installed', true);
		}
	}

	public function register_settings()
	{
		/************************ 
		Settings || General
		 *************************/

		register_setting(
			'pdpa_thailand_settings_group',
			'pdpa_thailand_settings',
			''
		);

		add_settings_section(
			'pdpa_thailand_settings',
			__('', 'pdpa-thailand'),
			array($this, 'pdpa_thailand_settings_intro'),
			'pdpa-thailand'
		);

		// General Settings
		add_settings_field(
			'is_enable',
			__('Enable PDPA Thailand', 'pdpa-thailand'),
			array($this, 'is_enable_callback'),
			'pdpa-thailand',
			'pdpa_thailand_settings'
		);

		// Cookie unqiue id
		add_settings_field(
			'cookie_unique_id',
			sprintf(__('Reset Cookie ID <a href="%s" class="pdpa--link" target="_blank"></a>', 'pdpa-thailand'), 'https://www.designilpdpa.com/documentation/settings/general/'),
			array($this, 'cookie_unique_id_callback'),
			'pdpa-thailand',
			'pdpa_thailand_settings'
		);

		// Testing mode
		add_settings_field(
			'admin_only',
			sprintf(__('Enable admin only mode <span class="pdpa--thailand-pro">PRO</span>', 'pdpa-thailand'), 'https://www.designilpdpa.com/documentation/settings/general/'),
			array($this, 'admin_only_callback'),
			'pdpa-thailand',
			'pdpa_thailand_settings'
		);

		// Always on
		add_settings_field(
			'always on',
			sprintf(__('Enable always on <span class="pdpa--thailand-pro">PRO</span>', 'pdpa-thailand'), 'https://www.designilpdpa.com/documentation/settings/general/'),
			array($this, 'always_on_callback'),
			'pdpa-thailand',
			'pdpa_thailand_settings'
		);

		// Disable button
		add_settings_field(
			'settings_button',
			__('Enable settings button <span class="pdpa--thailand-pro">PRO</span>', 'pdpa-thailand'),
			array($this, 'settings_button_callback'),
			'pdpa-thailand',
			'pdpa_thailand_settings'
		);

		// Disable button
		add_settings_field(
			'reject_button',
			__('Enable reject all button <span class="pdpa--thailand-pro">PRO</span>', 'pdpa-thailand'),
			array($this, 'reject_button_callback'),
			'pdpa-thailand',
			'pdpa_thailand_settings'
		);

		// Disable button
		add_settings_field(
			'disable_auto_check',
			__('Disable auto-check cookies <span class="pdpa--thailand-pro">PRO</span>', 'pdpa-thailand'),
			array($this, 'disable_auto_check_callback'),
			'pdpa-thailand',
			'pdpa_thailand_settings'
		);

		// Cookie duration
		add_settings_field(
			'cookie_duration',
			__('Cookies duration <span class="pdpa--thailand-pro">PRO</span>', 'pdpa-thailand'),
			array($this, 'cookie_duration_callback'),
			'pdpa-thailand',
			'pdpa_thailand_settings'
		);
		/************************ 
		Settings
		 *************************/


		/************************ 
		Message
		 *************************/
		register_setting(
			'pdpa_thailand_msg_group',
			'pdpa_thailand_msg',
			''
		);

		add_settings_section(
			'pdpa_thailand_msg',
			__('', 'pdpa-thailand'),
			array($this, 'pdpa_thailand_msg_intro'),
			'pdpa-thailand-msg'
		);

		// Cookie policy
		add_settings_field(
			'policy_page',
			__('Policy page', 'pdpa-thailand'),
			array($this, 'cookie_policy_page_callback'),
			'pdpa-thailand-msg',
			'pdpa_thailand_msg'
		);

		// Popup description
		add_settings_field(
			'cookie_consent_message',
			__('Cookie consent message', 'pdpa-thailand'),
			array($this, 'cookie_consent_message_callback'),
			'pdpa-thailand-msg',
			'pdpa_thailand_msg'
		);

		// Popup settings description
		add_settings_field(
			'sidebar_message',
			__('Sidebar message', 'pdpa-thailand'),
			array($this, 'sidebar_message_callback'),
			'pdpa-thailand-msg',
			'pdpa_thailand_msg'
		);

		// Button
		add_settings_field(
			'button_allow',
			__('Allow button <span class="pdpa--thailand-pro">PRO</span>', 'pdpa-thailand'),
			array($this, 'button_allow_callback'),
			'pdpa-thailand-msg',
			'pdpa_thailand_msg'
		);
		add_settings_field(
			'button_settings',
			__('Settings button <span class="pdpa--thailand-pro">PRO</span>', 'pdpa-thailand'),
			array($this, 'button_settings_callback'),
			'pdpa-thailand-msg',
			'pdpa_thailand_msg'
		);
		add_settings_field(
			'button_reject',
			__('Reject button <span class="pdpa--thailand-pro">PRO</span>', 'pdpa-thailand'),
			array($this, 'button_reject_callback'),
			'pdpa-thailand-msg',
			'pdpa_thailand_msg'
		);
		add_settings_field(
			'button_allow_all',
			__('Allow All button <span class="pdpa--thailand-pro">PRO</span>', 'pdpa-thailand'),
			array($this, 'button_allow_all_callback'),
			'pdpa-thailand-msg',
			'pdpa_thailand_msg'
		);
		add_settings_field(
			'button_save',
			__('Save button <span class="pdpa--thailand-pro">PRO</span>', 'pdpa-thailand'),
			array($this, 'button_save_callback'),
			'pdpa-thailand-msg',
			'pdpa_thailand_msg'
		);
		/************************ 
		Message
		 *************************/




		/************************ 
		Cookie
		 *************************/
		register_setting(
			'pdpa_thailand_cookies_group',
			'pdpa_thailand_cookies',
			array($this, 'prepare_save_cookies')
		);

		add_settings_section(
			'pdpa_thailand_cookies',
			__('', 'pdpa-thailand'),
			array($this, 'pdpa_thailand_cookies_intro'),
			'pdpa-thailand-cookies'
		);

		// Cookie list
		add_settings_field(
			'cookie_list',
			sprintf(__('Cookies list <a href="%s" class="pdpa--link" target="_blank"></a>', 'pdpa-thailand'), 'https://www.designilpdpa.com/documentation/settings/cookies/'),
			array($this, 'cookie_list_callback'),
			'pdpa-thailand-cookies',
			'pdpa_thailand_cookies'
		);

		// Cookie list
		add_settings_field(
			'cookie_necessary',
			__('', 'pdpa-thailand'),
			array($this, 'cookie_necessary_callback'),
			'pdpa-thailand-cookies',
			'pdpa_thailand_cookies'
		);
		/************************ 
		Cookie
		 *************************/



		/************************ 
		Appearance
		 *************************/
		register_setting(
			'pdpa_thailand_appearance_group',
			'pdpa_thailand_appearance',
			array($this, 'prepare_save_appearance')
		);

		// Register A New Section
		add_settings_section(
			'pdpa_thailand_appearance',
			__('', 'pdpa-thailand'),
			array($this, 'pdpa_thailand_appearance_intro'),
			'pdpa-thailand-appearance'
		);

		// Main color
		add_settings_field(
			'appearance_color',
			__('Main color', 'pdpa-thailand'),
			array($this, 'appearance_color_callback'),
			'pdpa-thailand-appearance',
			'pdpa_thailand_appearance'
		);

		// Popup style
		add_settings_field(
			'appearance_popup_mode',
			__('Popup mode <span class="pdpa--thailand-pro">PRO</span>', 'pdpa-thailand'),
			array($this, 'appearance_popup_mode_callback'),
			'pdpa-thailand-appearance',
			'pdpa_thailand_appearance'
		);


		// Popup layout
		add_settings_field(
			'appearance_popup_layout',
			__('Popup layout <span class="pdpa--thailand-pro">PRO</span>', 'pdpa-thailand'),
			array($this, 'appearance_popup_layout_callback'),
			'pdpa-thailand-appearance',
			'pdpa_thailand_appearance'
		);

		// Sidebar style
		add_settings_field(
			'appearance_sidebar_layout',
			__('Sidebar layout <span class="pdpa--thailand-pro">PRO</span>', 'pdpa-thailand'),
			array($this, 'appearance_sidebar_layout_callback'),
			'pdpa-thailand-appearance',
			'pdpa_thailand_appearance'
		);

		// Container size
		add_settings_field(
			'appearance_container_size',
			__('Max popup width <span class="pdpa--thailand-pro">PRO</span>', 'pdpa-thailand'),
			array($this, 'appearance_container_size_callback'),
			'pdpa-thailand-appearance',
			'pdpa_thailand_appearance'
		);

		// Popup settings logo
		add_settings_field(
			'appearance_logo',
			__('Logo <span class="pdpa--thailand-pro">PRO</span>', 'pdpa-thailand'),
			array($this, 'appearance_logo_callback'),
			'pdpa-thailand-appearance',
			'pdpa_thailand_appearance'
		);

		// BG - Blur Transparent
		add_settings_field(
			'appearance_bg_blur',
			__('BG blur & transparent <span class="pdpa--thailand-pro">PRO</span>', 'pdpa-thailand'),
			array($this, 'appearance_bg_blur_callback'),
			'pdpa-thailand-appearance',
			'pdpa_thailand_appearance'
		);

		// Hide close
		add_settings_field(
			'appearance_hide_close',
			__('Hide close button <span class="pdpa--thailand-pro">PRO</span>', 'pdpa-thailand'),
			array($this, 'appearance_hide_close_callback'),
			'pdpa-thailand-appearance',
			'pdpa_thailand_appearance'
		);

		// Main color
		add_settings_field(
			'appearance_color',
			__('Main color', 'pdpa-thailand'),
			array($this, 'appearance_color_callback'),
			'pdpa-thailand-appearance',
			'pdpa_thailand_appearance'
		);

		// Main color
		add_settings_field(
			'appearance_accept_color',
			__('Accept button color', 'pdpa-thailand'),
			array($this, 'appearance_accept_color_callback'),
			'pdpa-thailand-appearance',
			'pdpa_thailand_appearance'
		);

		// Mode
		add_settings_field(
			'appearance_mode',
			__('Mode <span class="pdpa--thailand-pro">PRO</span>', 'pdpa-thailand'),
			array($this, 'appearance_mode_callback'),
			'pdpa-thailand-appearance',
			'pdpa_thailand_appearance'
		);

		// Effect
		add_settings_field(
			'appearance_effect',
			__('Popup effect <span class="pdpa--thailand-pro">PRO</span>', 'pdpa-thailand'),
			array($this, 'appearance_effect_callback'),
			'pdpa-thailand-appearance',
			'pdpa_thailand_appearance'
		);
		/************************ 
		Appearance
		 *************************/


		/************************ 
		Free VS PRO
		 *************************/
		register_setting(
			'pdpa_thailand_freevspro_group',
			'pdpa_thailand_advanced',
			''
		);

		// Register A New Section
		add_settings_section(
			'pdpa_thailand_freevspro',
			__('', 'pdpa-thailand'),
			array($this, 'pdpa_thailand_freevspro_intro'),
			'pdpa-thailand-freevspro'
		);

		/************************ 
		Free VS PRO
		 *************************/
	}

	public function prepare_save_settings($settings)
	{
		// Sanitize text field
		// $settings['text_input'] = sanitize_text_field($settings['text_input']);		
	}

	// Reset cookie unique ID
	public function reset_cookie_id()
	{
		check_ajax_referer('pdpa_thailand_nonce', 'nonce');

		$unique_id =  uniqid('pdpa_');
		echo $unique_id;

		wp_die();
	}

	/************************ 
	Settings
	 *************************/
	public function pdpa_thailand_settings_intro()
	{
		// echo '<p>' . __('A long description for the settings section goes here.', 'pdpa-thailand') . '</p>';
	}

	public function cookie_unique_id_callback()
	{
	?>
		<div class="pdpa--list-container">
			<div class="form-group">
				<input type="text" name="pdpa_thailand_settings[cookie_unique_id]" value="<?php if (isset($this->options['cookie_unique_id'])) echo $this->options['cookie_unique_id']; ?>" readonly>
				<a href="#" class="button button-primary refresh--cookie">
					<img src="<?php echo PDPA_THAILAND_URL . 'admin/assets/images/refresh.svg'; ?>" alt="">
				</a>
			</div>
		</div>
	<?php
	}

	public function is_enable_callback()
	{
	?>
		<label class="dpdpa--form-group switch">
			<input type="checkbox" name="pdpa_thailand_settings[is_enable]" id="is_enable" value="1" <?php if (isset($this->options['is_enable'])) {
																																																	checked('1', $this->options['is_enable']);
																																																} ?>>
			<span class="slider round"></span>
		</label>
	<?php
	}

	public function admin_only_callback()
	{
	?>
		<label class="dpdpa--form-group switch">
			<input type="checkbox" name="designil_pdpa_settings[admin_only]" id="admin_only" value="1" disabled>
			<span class="slider round"></span>
		</label>
	<?php
	}

	public function always_on_callback()
	{
	?>
		<label class="dpdpa--form-group switch">
			<input type="checkbox" name="designil_pdpa_settings[always_on]" id="always_on" value="1" disabled>
			<span class="slider round"></span>
		</label>
	<?php
	}

	public function settings_button_callback()
	{
	?>
		<label class="dpdpa--form-group switch">
			<input type="checkbox" name="pdpa_thailand_settings[settings_button]" id="settings_button" value="1" disabled>
			<span class="slider round"></span>
		</label>
	<?php
	}

	public function reject_button_callback()
	{
	?>
		<label class="dpdpa--form-group switch">
			<input type="checkbox" name="pdpa_thailand_settings[reject_button]" id="reject_button" value="1" disabled>
			<span class="slider round"></span>
		</label>
	<?php
	}

	public function disable_auto_check_callback()
	{
	?>
		<label class="dpdpa--form-group switch">
			<input type="checkbox" name="designil_pdpa_settings[disable_auto_check]" id="disable_auto_check" value="1" <?php if (isset($this->options['disable_auto_check'])) {
																																																										checked('1', $this->options['disable_auto_check']);
																																																									} ?> disabled>
			<span class="slider round"></span>
		</label>
	<?php
	}

	public function cookie_duration_callback()
	{
	?>
		<div class="form-group">
			<input type="text" class="small-text" placeholder="7" name="pdpa_thailand_settings[cookie_duration]" value="7" readonly disabled> <?php _e('Days', 'pdpa-thailand'); ?>
		</div>
	<?php
	}
	/************************ 
	Settings
	 *************************/

	/************************ 
	MSG
	 *************************/
	public function pdpa_thailand_msg_intro() {}

	public function cookie_policy_page_callback()
	{
		if (isset($this->msg['policy_page'])) {
			$policy_edit = admin_url('post.php?post=' . $this->msg['policy_page'] . '&action=edit');
		}
	?>
		<div class="pdpa--list-container">
			<div class="form-group">
				<select name="pdpa_thailand_msg[policy_page]">
					<?php
					//Custom Query
					$args = array(
						'post_type' => 'page',
						'posts_per_page' => -1
					);
					$q = new WP_Query($args);

					if ($q->have_posts()) :
						while ($q->have_posts()) : $q->the_post();

							$selected = '';
							if (isset($this->msg['policy_page']) && $this->msg['policy_page'] == get_the_ID())
								$selected = 'selected';

							echo '<option value="' . get_the_ID() . '" ' . $selected . '>' . get_the_title() . '</option>';
						endwhile;
					endif;
					?>
				</select>
				<a href="<?php echo $policy_edit; ?>" class="policy--page-edit">
					<svg width="22" height="22" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
						<path d="M6 34.5v7.5h7.5l22.13-22.13-7.5-7.5-22.13 22.13zm35.41-20.41c.78-.78.78-2.05 0-2.83l-4.67-4.67c-.78-.78-2.05-.78-2.83 0l-3.66 3.66 7.5 7.5 3.66-3.66z" fill="#FC7E05" />
						<path d="M0 0h48v48h-48z" fill="none" />
					</svg>
				</a>
			</div>
		</div>
	<?php
	}

	public function cookie_consent_message_callback()
	{
	?>
		<div class="pdpa--list-container">
			<div class="form-group">
				<textarea name="pdpa_thailand_msg[cookie_consent_message]" id="" rows="4" class="large-text"><?php if (isset($this->msg['cookie_consent_message'])) echo $this->msg['cookie_consent_message']; ?></textarea>
				<p class="description"><?php _e('<label>Shortcode</label>[dpdpa_policy_page title="Cookies policy"] For showing link to policy page<br>[dpdpa_settings title="Cookie settings"] For calling sidebar and show cookie settings or Enable settings button ( Tab General )', 'pdpa-thailand'); ?></p>
			</div>
		</div>
	<?php
	}

	public function sidebar_message_callback()
	{
	?>
		<div class="pdpa--list-container">
			<div class="form-group">
				<textarea name="pdpa_thailand_msg[sidebar_message]" id="" rows="4" class="large-text"><?php if (isset($this->msg['sidebar_message'])) echo $this->msg['sidebar_message']; ?></textarea>
			</div>
		</div>
	<?php
	}

	public function button_allow_callback()
	{
	?>
		<div class="form-group">
			<input type="text" name="designil_pdpa_msg[button_allow]" disabled placeholder="<?php _e('Allow', 'pdpa-thailand'); ?>">
		</div>
	<?php
	}

	public function button_settings_callback()
	{
	?>
		<div class="form-group">
			<input type="text" name="designil_pdpa_msg[button_settings]" disabled placeholder="<?php _e('Settings', 'pdpa-thailand'); ?>">
		</div>
	<?php
	}

	public function button_reject_callback()
	{
	?>
		<div class="form-group">
			<input type="text" name="designil_pdpa_msg[button_reject]" disabled placeholder="<?php _e('Reject', 'pdpa-thailand'); ?>">
		</div>
	<?php
	}

	public function button_allow_all_callback()
	{
	?>
		<div class="form-group">
			<input type="text" name="designil_pdpa_msg[button_allow_all]" disabled placeholder="<?php _e('Allow All', 'pdpa-thailand'); ?>">
		</div>
	<?php
	}

	public function button_save_callback()
	{
	?>
		<div class="form-group">
			<input type="text" name="designil_pdpa_msg[button_save]" disabled placeholder="<?php _e('Save', 'pdpa-thailand'); ?>">
		</div>
	<?php
	}
	/************************ 
	MSG
	 *************************/


	/************************ 
	COOKIES
	 *************************/
	public function prepare_save_cookies($settings)
	{
		$cookie_neccesary = array(
			'cookie_necessary_title' => sanitize_text_field($_POST['cookie_necessary_title']),
			'cookie_necessary_description' => sanitize_text_field($_POST['cookie_necessary_description'])
		);
		$settings['cookie_necessary'] = serialize($cookie_neccesary);

		// if ( isset($_POST['gg_analytic_script'] ) )
		// 	$gg_analytic_script = 1;
		// else
		// 	$gg_analytic_script = '';

		// Set cookie 
		$cookies_list = array(
			'cookie_name' => $this->pdpa_thailand_recursive_sanitize_text_field($_POST['cookie_name']),
			'consent_title' => $this->pdpa_thailand_recursive_sanitize_text_field($_POST['consent_title']),
			'consent_description' => $this->pdpa_thailand_recursive_sanitize_text_field($_POST['consent_description']),
			'code_in_head' => '',
			'code_next_body' => '',
			'code_body_close' => '',
			'gg_analytic_script' => $this->pdpa_thailand_recursive_sanitize_text_field(isset($_POST['gg_analytic_script']) ? $_POST['gg_analytic_script'] : array()),
			'gg_analytic_id' => $this->pdpa_thailand_recursive_sanitize_text_field($_POST['gg_analytic_id']),
		);

		$settings['cookie_list'] = serialize($cookies_list);

		// Prepare for JS Value		
		$this->cookie_count = count($cookies_list['cookie_name']);

		$code_in_head = array();
		$code_next_body = array();
		$code_body_close = array();

		// Preaparing code in array
		if ($this->cookie_count != -1) {
			for ($i = 0; $i <= ($this->cookie_count - 1); $i++) {

				$js_code = '';

				if (isset($cookies_list['gg_analytic_script'][$i]) && $cookies_list['gg_analytic_script'][$i] == 1) {
					$js_code .= "
					<!-- Google Analytics -->
						<script>
							(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
							(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
							m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
							})(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

							ga('create', '" . $cookies_list['gg_analytic_id'][$i] . "', 'auto');
							ga('send', 'pageview');
						</script>
					<!-- End Google Analytics -->
					";
				}

				/*if (isset($cookies_list['fb_pixel_script'][$i]) &&  $cookies_list['fb_pixel_script'][$i] == 1) {
					$js_code .= "
					<!-- Facebook Pixel Code -->
						<script>
							!function(f,b,e,v,n,t,s)
							{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
							n.callMethod.apply(n,arguments):n.queue.push(arguments)};
							if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
							n.queue=[];t=b.createElement(e);t.async=!0;
							t.src=v;s=b.getElementsByTagName(e)[0];
							s.parentNode.insertBefore(t,s)}(window, document,'script',
							'https://connect.facebook.net/en_US/fbevents.js');
							fbq('init', '" . $cookies_list['fb_pixel_id'][$i]  . "');
							fbq('track', 'PageView');
						</script>
					
						<noscript>
							<img height='1' width='1' style='display:none' 
								src='https://www.facebook.com/tr?id=" . $cookies_list['fb_pixel_id'][$i]  . "'&ev=PageView&noscript=1'/>
						</noscript>
					<!-- End Facebook Pixel Code -->
					";
				}*/

				$code_in_head[$cookies_list['cookie_name'][$i]][] = $js_code;
				$code_next_body[$cookies_list['cookie_name'][$i]][] = '';
				$code_body_close[$cookies_list['cookie_name'][$i]][] = '';
			}
		}

		$js_value = json_encode(array(
			'code_in_head' => $code_in_head,
			'code_next_body' => '',
			'code_body_close' => '',
		));

		update_option('pdpa_thailand_js_version', rand());
		set_transient('pdpa_thailand_script', 'function callCookieList() { return cookie_list = ' . $js_value . '; }', 0);

		// Prepare for JS Value	

		return $settings;
	}

	public function pdpa_thailand_recursive_sanitize_text_field($array)
	{
		foreach ($array as $key => &$value) {
			if (is_array($value)) {
				$array[$key] = $this->pdpa_thailand_recursive_sanitize_text_field($value);
			}
		}

		return $array;
	}

	public function pdpa_thailand_cookies_intro()
	{
	?>

	<?php
	}

	public function cookie_necessary_callback()
	{
	?>
		<input type="hidden" name="pdpa_thailand_settings[necessary]" id="cookie_list">
	<?php
	}

	public function cookie_list_callback()
	{
		if (isset($this->cookies['cookie_list']))
			$cookie_list = $this->cookies['cookie_list'];
		else
			$cookie_list = '';

		$cookie_neccesary = array('cookie_necessary_title' => '', 'cookie_necessary_description' => '');
		if (isset($this->cookies['cookie_necessary'])) {
			$cookie_neccesary = unserialize($this->cookies['cookie_necessary']);

			if ($cookie_neccesary['cookie_necessary_title'] == '' && $cookie_neccesary['cookie_necessary_description'] == '')
				$cookie_neccesary = array('cookie_necessary_title' => 'คุกกี้ที่จำเป็น', 'cookie_necessary_description' => 'ประเภทของคุกกี้มีความจำเป็นสำหรับการทำงานของเว็บไซต์ เพื่อให้คุณสามารถใช้ได้อย่างเป็นปกติ และเข้าชมเว็บไซต์ คุณไม่สามารถปิดการทำงานของคุกกี้นี้ในระบบเว็บไซต์ของเราได้');
		} else {
			$cookie_neccesary = array('cookie_necessary_title' => 'คุกกี้ที่จำเป็น', 'cookie_necessary_description' => 'ประเภทของคุกกี้มีความจำเป็นสำหรับการทำงานของเว็บไซต์ เพื่อให้คุณสามารถใช้ได้อย่างเป็นปกติ และเข้าชมเว็บไซต์ คุณไม่สามารถปิดการทำงานของคุกกี้นี้ในระบบเว็บไซต์ของเราได้');
		}

	?>
		<div class="pdpa--list-container">
			<div class="pdpa--force">
				<div class="form-group">
					<label>
						<?php _e('Strictly necessary cookies title', 'pdpa-thailand'); ?>
					</label>
					<input type="text" class="large-text" name="cookie_necessary_title" value="<?php echo $cookie_neccesary['cookie_necessary_title']; ?>" placeholder="">
				</div>
				<div class="form-group">
					<label>
						<?php _e('Strictly necessary cookies description', 'pdpa-thailand'); ?>
					</label>
					<textarea name="cookie_necessary_description" class="large-text" rows="4" placeholder=""><?php echo $cookie_neccesary['cookie_necessary_description']; ?></textarea>
				</div>
			</div>

			<h3>
				<svg width="20px" height="20px" viewBox="0 0 16 16" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
					<g id="-" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
						<g id="Icon/playBig" fill="#000000">
							<path d="M6,11 L11,8 L6,5 L6,11 Z M8,14.6 C4.4,14.6 1.4,11.6 1.4,8 C1.4,4.4 4.4,1.4 8,1.4 C11.6,1.4 14.6,4.4 14.6,8 C14.6,11.6 11.6,14.6 8,14.6 L8,14.6 Z M8,0 C3.6,0 0,3.6 0,8 C0,12.4 3.6,16 8,16 C12.4,16 16,12.4 16,8 C16,3.6 12.4,0 8,0 L8,0 Z" id="Fill-1">

							</path>
						</g>
					</g>
				</svg>
				<span><?php _e('Code inside the boxes below will run <u>before</u> user\'s consent', 'pdpa-thailand'); ?></span>
				<?php echo sprintf(__('<a href="%s" class="pdpa--link" target="_blank"></a>', 'pdpa-thailand'), 'https://www.designilpdpa.com/documentation/settings/cookies/') ?>
			</h3>
			<div class="pdpa--list-without-consent">
				<div class="pdpa--list-inner init">
					<div class="pdpa--list-col col-3">
						<div class="form-group">
							<label>
								<?php _e('Code in &lt;head&gt;&lt;/head&gt; <span class="pdpa--thailand-pro">PRO</span>', 'pdpa-thailand'); ?>
							</label>
							<textarea class="regular-text" name="init_code_in_head" value="" rows="5" disabled></textarea>
						</div>
						<div class="form-group">
							<label>
								<?php _e('Code next to &lt;body&gt; <span class="pdpa--thailand-pro">PRO</span>', 'pdpa-thailand'); ?>
							</label>
							<textarea class="regular-text" name="init_code_next_body" rows="5" disabled></textarea>
						</div>
						<div class="form-group">
							<label>
								<?php _e('Code before &lt;/body&gt; <span class="pdpa--thailand-pro">PRO</span>', 'pdpa-thailand'); ?>
							</label>
							<textarea class="regular-text" name="init_code_body_close" rows="5" disabled></textarea>
						</div>
					</div>
				</div>
			</div>

			<h3>
				<svg fill="#000000" width="20px" height="20px" viewBox="0 0 15 15" xmlns="http://www.w3.org/2000/svg">
					<g>
						<path d="M13,3.05A7,7,0,1,0,13,13,7,7,0,0,0,13,3.05ZM7,11H5.6V5H7Zm3.4,0H9V5h1.4Z" />
					</g>
				</svg>
				<span><?php _e('Code inside the boxes below will run <u>after</u> user\'s consent', 'pdpa-thailand'); ?></span>
				<?php echo sprintf(__('<a href="%s" class="pdpa--link" target="_blank"></a>', 'pdpa-thailand'), 'https://www.designilpdpa.com/documentation/settings/cookies/') ?>
			</h3>
			<ul class="pdpa--list">
				<?php
				if ($cookie_list == '') {
					$this->cookie_list_default();
				} else {

					$cookie_list = unserialize($this->cookies['cookie_list']);
					$cookie_count = 0;

					if (isset($cookie_list['cookie_name']))
						$cookie_count = count($cookie_list['cookie_name']);

					if ($cookie_count != -1) {
						for ($i = 0; $i <= ($cookie_count - 1); $i++) {

							if (isset($cookie_list['gg_analytic_script'][$i])) {
								$gg_analytic_script = $cookie_list['gg_analytic_script'][$i];
							} else {
								$gg_analytic_script = '';
							}

							$cookie_set = array(
								'cookie_name' => $cookie_list['cookie_name'][$i],
								'consent_title' => $cookie_list['consent_title'][$i],
								'consent_description' => $cookie_list['consent_description'][$i],
								'code_in_head' => '',
								'code_next_body' => '',
								'code_body_close' => '',
								'gg_analytic_script' => $gg_analytic_script,
								'gg_analytic_id' => $cookie_list['gg_analytic_id'][$i],
								'fb_pixel_script' => '',
								'fb_pixel_id' => '',
							);
							$this->cookie_list_default($cookie_set);
						}
					}
				}
				?>
			</ul>
			<div class="pdpa--button">
				<a href="#" class="button button-secondary pdpa--add-cookie" disabled><?php _e('Add cookies <span class="pdpa--thailand-pro">PRO</span>', 'pdpa-thailand'); ?></a>
			</div>
			<input type="hidden" name="pdpa_thailand_settings[cookie_list]">
		</div>
	<?php
	}
	/************************ 
	COOKIES
	 *************************/


	/************************ 
	Apperance
	 *************************/
	public function pdpa_thailand_appearance_intro() {}

	public function prepare_save_appearance($settings)
	{
		update_option('pdpa_thailand_css_version', rand());

		$container = '';
		$main_color = '';
		$dark_mode = '';
		$posiiton = '';

		if ($settings['appearance_color']) {
			// Link on popup
			$main_color = '.dpdpa--popup-text a, .dpdpa--popup-text a:visited { color: ' . $settings['appearance_color'] . '; }';
			$main_color .= '.dpdpa--popup-text a:hover { color: ' . $this->darken_color($settings['appearance_color'], 1.1) . '; }';
			$main_color .= '.dpdpa--popup-action.text { color: ' . $settings['appearance_color'] . '; }';

			// Button
			$main_color .= 'a.dpdpa--popup-button, a.dpdpa--popup-button, a.dpdpa--popup-button:visited  { background-color: ' . $settings['appearance_color'] . '; }';
			$main_color .= 'a.dpdpa--popup-button:hover { background-color: ' . $this->darken_color($settings['appearance_color'], 1.1) . '; }';

			// Switch
			$main_color .= '.dpdpa--popup-switch input:checked + .dpdpa--popup-slider { background-color: rgba(' . implode(',', sscanf($settings['appearance_color'], "#%02x%02x%02x")) . ', 0.3); border-color: ' . $settings['appearance_color'] . '; }';
			$main_color .= '.dpdpa--popup-switch input:checked + .dpdpa--popup-slider:before { background-color: ' . $settings['appearance_color'] . '; }';
		}

		$CSS = $container . $main_color . $dark_mode . $posiiton;
		set_transient('pdpa_thailand_style', $CSS, 0);

		return $settings;
	}

	public function appearance_color_callback()
	{
	?>
		<div class="pdpa--list-container">
			<div class="form-group color">
				<label>
					<input type="radio" name="pdpa_thailand_appearance[appearance_color]" value="#006ff4" <?php if (!isset($this->appearance['appearance_color']) || (isset($this->appearance['appearance_color']) && $this->appearance['appearance_color'] == '#006ff4')) {
																																																	echo 'checked';
																																																} ?>>
					<span class="appearance_color" style="background-color:#006ff4"></span>
				</label>
				<label>
					<input type="radio" name="pdpa_thailand_appearance[appearance_color]" value="#444444" <?php if (isset($this->appearance['appearance_color']) && $this->appearance['appearance_color'] == '#444444') {
																																																	echo 'checked';
																																																} ?>>
					<span class="appearance_color" style="background-color:#444444"></span>
				</label>
				<label>
					<input type="radio" name="pdpa_thailand_appearance[appearance_color]" disabled>
					<input type="color" class="appearance_color_pick"> Custom <span class="pdpa--thailand-pro">PRO</span>
				</label>
				<!-- <input type="color" class="color--picker" name="pdpa_thailand_appearance[appearance_color]" value="<?php if (isset($this->appearance['appearance_color'])) {
																																																									echo $this->appearance['appearance_color'];
																																																								} ?>"> -->
			</div>
		</div>
	<?php
	}

	public function appearance_logo_callback()
	{
		$src = '';

		if (isset($this->appearance['appearance_logo']) && $this->appearance['appearance_logo'] != '') {
			$src = wp_get_attachment_image_src($this->appearance['appearance_logo'], 'thumbnail')[0];
		}
	?>
		<div class="form-group">
			<div class="pdpa--list-container">
				<div class="dpdpa--logo">
					<div class="dpdpa--logo-box">
						<img src="<?php echo $src; ?>" alt="">
					</div>
					<a href="#" class="dpdpa--logo-delete">
						<svg viewBox="0 0 511.992 511.992" xmlns="http://www.w3.org/2000/svg">
							<path d="m415.402344 495.421875-159.40625-159.410156-159.40625 159.410156c-22.097656 22.09375-57.921875 22.09375-80.019532 0-22.09375-22.097656-22.09375-57.921875 0-80.019531l159.410157-159.40625-159.410157-159.40625c-22.09375-22.097656-22.09375-57.921875 0-80.019532 22.097657-22.09375 57.921876-22.09375 80.019532 0l159.40625 159.410157 159.40625-159.410157c22.097656-22.09375 57.921875-22.09375 80.019531 0 22.09375 22.097657 22.09375 57.921876 0 80.019532l-159.410156 159.40625 159.410156 159.40625c22.09375 22.097656 22.09375 57.921875 0 80.019531-22.097656 22.09375-57.921875 22.09375-80.019531 0zm0 0" fill="#D63638" />
						</svg>
					</a>
				</div>
				<a href="#" class="button-secondary button dpdpa--outline" id="dpdpa--upload" disabled><?php _e('Select / Upload', 'pdpa-thailand'); ?></a>
				<input type="hidden" name="designil_pdpa_appearance[appearance_logo]" value="<?php if (isset($this->appearance['appearance_logo'])) {
																																												echo $this->appearance['appearance_logo'];
																																											} ?>">
			</div>
		</div>
	<?php
	}

	public function appearance_alwayson_callback()
	{
	?>
		<div class="form-group">
			<div class="pdpa--list-container">
				<div class="dpdpa--logo">
					<div class="dpdpa--alwayson-box">

					</div>
					<a href="#" class="dpdpa--alwayson-delete">
						<svg viewBox="0 0 511.992 511.992" xmlns="http://www.w3.org/2000/svg">
							<path d="m415.402344 495.421875-159.40625-159.410156-159.40625 159.410156c-22.097656 22.09375-57.921875 22.09375-80.019532 0-22.09375-22.097656-22.09375-57.921875 0-80.019531l159.410157-159.40625-159.410157-159.40625c-22.09375-22.097656-22.09375-57.921875 0-80.019532 22.097657-22.09375 57.921876-22.09375 80.019532 0l159.40625 159.410157 159.40625-159.410157c22.097656-22.09375 57.921875-22.09375 80.019531 0 22.09375 22.097657 22.09375 57.921876 0 80.019532l-159.410156 159.40625 159.410156 159.40625c22.09375 22.097656 22.09375 57.921875 0 80.019531-22.097656 22.09375-57.921875 22.09375-80.019531 0zm0 0" fill="#D63638" />
						</svg>
					</a>
				</div>
				<a href="#" class="button-secondary button dpdpa--outline" id="dpdpa--alwayson-upload" disabled><?php _e('Select / Upload', 'pdpa-thailand'); ?></a>
				<input type="hidden" name="designil_pdpa_appearance[appearance_alwayson]" value="">
			</div>
		</div>
	<?php
	}

	public function appearance_popup_mode_callback()
	{
		$popup_mode = array(
			'default' => __('Default Mode', 'pdpa-thailand'),
			'minimalist' => __('Minimalist', 'pdpa-thailand'),
			'cutie' => __('Cutie', 'pdpa-thailand'),
			'force' => __('Force', 'pdpa-thailand'),
		);
	?>
		<div class="form-group mode pdpa--list-container">
			<div class="popup--mode">
				<?php foreach ($popup_mode as $key => $val) : ?>
					<div class="form-group">
						<input type="radio" id="appearance_popup_mode_<?php echo $key; ?>" <?php echo $key != 'default' ? 'disabled' : ''; ?> name="designil_pdpa_appearance[appearance_popup_mode]" <?php if (isset($this->appearance['appearance_popup_mode']) && $this->appearance['appearance_popup_mode'] == $key) echo 'checked';
																																																																																													else if (!isset($this->appearance['appearance_popup_mode']) && $key == 'default') echo 'checked'; ?> value="<?php echo $key; ?>">
						<label for="appearance_popup_mode_<?php echo $key; ?>">
							<div class="pdpa--thumbnail">
								<img src="<?php echo PDPA_THAILAND_URL; ?>admin/assets/images/mode-<?php echo $key; ?>.svg">
							</div>
							<span>
								<div class="dpdpa--theme-text"><?php echo $val; ?></div>
							</span>
						</label>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	<?php
	}

	public function appearance_popup_layout_callback()
	{
		$popup_layout = array(
			1 => __('Full Bottom', 'pdpa-thailand'),
			2 => __('Full Top', 'pdpa-thailand'),
			3 => __('Center Bottom', 'pdpa-thailand'),
			4 => __('Center Top', 'pdpa-thailand'),
			5 => __('Box Bottom Right', 'pdpa-thailand'),
			6 => __('Box Top Right', 'pdpa-thailand'),
			7 => __('Box Bottom Left', 'pdpa-thailand'),
			8 => __('Box Top Left', 'pdpa-thailand'),
			9 => __('Blend in', 'pdpa-thailand'),
			// 10 => __('Minimal Right','designil-pdpa-themes'),
			// 11 => __('Minimal Left','designil-pdpa-themes'),
		);
	?>

		<div class="form-group mode pdpa--list-container">
			<div class="popup--mode">
				<?php foreach ($popup_layout as $key => $val) { ?>
					<div class="form-group">
						<input type="radio" id="appearance_popup_layout_<?php echo $key; ?>" <?php echo $key != 1 ? 'disabled' : 'checked'; ?> name="designil_pdpa_appearance[appearance_popup_layout]" value="<?php echo $key; ?>">
						<label for="appearance_popup_layout_<?php echo $key; ?>">
							<div class="pdpa--thumbnail">
								<img src="<?php echo PDPA_THAILAND_URL; ?>admin/assets/images/layout-popup-<?php echo $key; ?>.svg">
							</div>
							<span>
								<div class="dpdpa--theme-text"><?php echo $val; ?></div>
							</span>
						</label>
						<?php if ($key == 9) { ?>
							<input type="text" name="appearance_popup_shortcode" class="shortcode" value="[dpdpa_consent]" readonly disabled>
							<p></p>
						<?php } ?>
					</div>
				<?php } ?>
			</div>
		</div>
	<?php
	}

	public function appearance_sidebar_layout_callback()
	{
		$sidebar_layout = array(
			1 => __('Sidebar Left', 'designil-pdpa-themes'),
			2 => __('Sidebar Right', 'designil-pdpa-themes'),
			3 => __('Float Center', 'designil-pdpa-themes'),
		);
	?>
		<div class="form-group mode pdpa--list-container">
			<div class="popup--mode">
				<?php foreach ($sidebar_layout as $key => $val) { ?>
					<div class="form-group">
						<input type="radio" id="appearance_sidebar_layout_<?php echo $key; ?>" <?php echo $key != 1 ? 'disabled' : 'checked'; ?> name="designil_pdpa_appearance[appearance_sidebar_layout]" value="<?php echo $key; ?>">
						<label for="appearance_sidebar_layout_<?php echo $key; ?>">
							<div class="pdpa--thumbnail">
								<img src="<?php echo PDPA_THAILAND_URL; ?>admin/assets/images/layout-sidebar-<?php echo $key; ?>.svg">
							</div>
							<span>
								<div class="dpdpa--theme-text"><?php echo $val; ?></div>
							</span>
						</label>
					</div>
				<?php } ?>
			</div>
		</div>
	<?php
	}

	public function appearance_container_size_callback()
	{
	?>
		<div class="pdpa--list-container">
			<div class="form-group group--input size">
				<input type="number" class="small-text" placeholder="1200" name="appearance_container_size" disabled value="1200">
				<select name="appearance_container_point" disabled>
					<option value="px">px</option>
					<option value="%">%</option>
				</select>
			</div>
		</div>
	<?php
	}
	public function appearance_hide_close_callback()
	{
	?>
		<label class="dpdpa--form-group switch">
			<input type="checkbox" name="designil_pdpa_appearance[appearance_hide_close]" value="1" disabled>
			<span class="slider round"></span>
		</label>
	<?php
	}
	public function appearance_bg_blur_callback()
	{
	?>

		<label class="dpdpa--form-group switch">
			<input type="checkbox" name="designil_pdpa_appearance[appearance_bg_blur]" id="appearance_bg_blur" value="1" disabled>
			<span class="slider round"></span>
		</label>
	<?php
	}

	public function appearance_accept_color_callback()
	{
	?>
		<div class="form-group">
			<input type="color" class="color--picker" name="designil_pdpa_appearance[appearance_accept_color]" disabled>
		</div>
	<?php
	}

	public function appearance_effect_callback()
	{
	?>
		<div class="form-group">
			<select name="designil_pdpa_appearance[appearance_effect]" disabled>
				<option value="bottom-top"><?php _e('Bottom to top', 'pdpa-thailand'); ?></option>
				<option value="fade"><?php _e('Fade', 'pdpa-thailand'); ?></option>
				<option value="bottom-top-fade"><?php _e('Bottom to top & Fade', 'pdpa-thailand'); ?></option>
			</select>
		</div>
	<?php
	}

	public function appearance_mode_callback()
	{
	?>
		<label class="dpdpa--form-group switch icon">
			<input type="checkbox" name="designil_pdpa_appearance[appearance_mode]" id="appearance_mode" value="1" disabled>
			<span class="slider round">
				<img src="<?php echo PDPA_THAILAND_URL; ?>admin/assets/images/mode-light.svg" class="left">
				<img src="<?php echo PDPA_THAILAND_URL; ?>admin/assets/images/mode-dark.svg" class="right">
			</span>
		</label>
	<?php
	}
	/************************ 
	Apperance
	 *************************/


	/************************ 
	Advanced
	 *************************/
	public function pdpa_thailand_freevspro_intro()
	{
		$free_vs_pro = array(
			// array(
			// 	'text' => __('License Price* You will save <b>300฿ off</b> the initial purchase price, and get <b>30% discount</b> when renewing the license.', 'pdpa-thailand'),
			// 	'free' => '0<span>฿</span>',
			// 	'pro' => '990<span>฿</span>',
			// ),
			array(
				'text' => __('Capable of setting up using a popup and resetting the cookie ID.', 'pdpa-thailand'),
				'free' => true,
				'pro' => true,
			),
			array(
				'text' => __('Provides the option to configure a policy page.', 'pdpa-thailand'),
				'free' => true,
				'pro' => true,
			),
			array(
				'text' => __('Displays a message in a popup, sidebar, or necessary cookie.', 'pdpa-thailand'),
				'free' => true,
				'pro' => true,
			),
			array(
				'text' => __('Enables management of tracking for Google Analytics.', 'pdpa-thailand'),
				'free' => true,
				'pro' => true,
			),
			array(
				'text' => __('Supports the use of a <b>Cookie Wizard</b>, simplifying the installation of tracking.', 'pdpa-thailand'),
				'free' => false,
				'pro' => true,
			),
			array(
				'text' => __('Includes a <b>Wizard Consent Message</b> feature.', 'pdpa-thailand'),
				'free' => false,
				'pro' => true,
			),
			array(
				'text' => __('Personalize popups with 4 modes, adjust 9 popup templates, apply background blur, add effects on popup load, and toggle between dark/light mode, plus much more.', 'pdpa-thailand'),
				'free' => false,
				'pro' => true,
			),
			array(
				'text' => __('Store consent logs, set a maximum retention period, and provide options for downloading, viewing, filtering, and displaying them in a table.', 'pdpa-thailand'),
				'free' => false,
				'pro' => true,
			),
			array(
				'text' => __('Utilize a request form for revoking consent, with a notification system for submissions and tools to manage and display progress.', 'pdpa-thailand'),
				'free' => false,
				'pro' => true,
			),
			array(
				'text' => __('Override popup templates.', 'pdpa-thailand'),
				'free' => false,
				'pro' => true,
			),
			array(
				'text' => __('Multi-language support for PolyLang and WPML.', 'pdpa-thailand'),
				'free' => false,
				'pro' => true,
			),
			array(
				'text' => __('Import/Export settings in JSON format.', 'pdpa-thailand'),
				'free' => false,
				'pro' => true,
			),
			array(
				'text' => __('Allows grouping of cookies before and after consent is provided.', 'pdpa-thailand'),
				'free' => false,
				'pro' => true,
			),
			array(
				'text' => __('Enables admin mode for displaying content exclusively to admins during testing.', 'pdpa-thailand'),
				'free' => false,
				'pro' => true,
			),
			array(
				'text' => __('Offers a mini popup mode post consent or after closing.', 'pdpa-thailand'),
				'free' => false,
				'pro' => true,
			),
			array(
				'text' => __('Provides control over the settings and reject buttons, with options to disable auto consent.', 'pdpa-thailand'),
				'free' => false,
				'pro' => true,
			),
			array(
				'text' => __('Manages cookie expiration times and descriptions effectively.', 'pdpa-thailand'),
				'free' => false,
				'pro' => true,
			),
			array(
				'text' => __('Customizes every button text.', 'pdpa-thailand'),
				'free' => false,
				'pro' => true,
			),
		);
	?>
		<div class="dpdpap--log-table">
			<table>
				<thead>
					<th width="80%"></th>
					<th><?php _e('Free', 'pdpa-thailand'); ?></th>
					<th><?php _e('Pro', 'pdpa-thailand'); ?></th>
				</thead>
				<tbody>
					<?php if (is_array($free_vs_pro)) {
						foreach ($free_vs_pro as $key => $value) {
					?>
							<tr>
								<td><?php echo $value['text']; ?></td>
								<td>
									<?php if (is_bool($value['free']) && $value['free']) { ?>
										<div class="dpdpa--ok"></div>
									<?php } else if (is_bool($value['free'])) { ?>
										<div class="dpdpa--no"></div>
									<?php } else { ?>
										<div class="dpdpa--price"><?php echo $value['free']; ?></div>
									<?php }  ?>
								</td>
								<td>
									<?php if (is_bool($value['pro']) && $value['pro']) { ?>
										<div class="dpdpa--ok pro"></div>
									<?php } else if (is_bool($value['pro'])) { ?>
										<div class="dpdpa--no pro"></div>
									<?php } else { ?>
										<div class="dpdpa--price pro"><?php echo $value['pro']; ?></div>
									<?php }  ?>
								</td>
							</tr>
					<?php }
					} ?>
					<tr>
						<td colspan="3">
							<div class="dpdpa--button-group">
								<a href="https://designilpdpa.com/checkout?edd_action=add_to_cart&download_id=29&edd_options%5Bprice_id%5D=1&discount=UPGRADE" class="button button-primary" target="_blank"><?php _e('Upgrade now for 30% Off !', 'pdpa-thailand'); ?></a>
								<a href="https://m.me/doactionco" class="button dpdpa--outline" target="_blank"><?php _e('I have some question', 'pdpa-thailand'); ?></a>
							</div>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	<?php
	}

	/************************ 
	Advanced
	 *************************/

	public function admin_interface_render()
	{
		if (!current_user_can('manage_options')) {
			return;
		}

		$default_tab = null;
		$tab = isset($_GET['tab']) ? $_GET['tab'] : $default_tab;

		// PDPA THAILAND Rating
		$pdpa_thailand_rating = get_transient('pdpa_thailand_rating');

		if (isset($_GET['settings-updated'])) {
			// Add settings saved message with the class of "updated"
			add_settings_error('pdpa_thailand_settings_saved_message', 'pdpa_thailand_settings_saved_message', __('Settings are Saved', 'pdpa-thailand'), 'updated');
		}

		// Show Settings Saved Message
		settings_errors('pdpa_thailand_settings_saved_message'); ?>

		<div class="wrap">
			<div class="dpdpa--logo-head">
				<a href="https://designilpdpa.com" target="_blank" alt="<?php _e('PDPA THAILAND', 'pdpa-thailand'); ?>">
					<img src="<?php echo PDPA_THAILAND_URL; ?>admin/assets/images/pdpa-thailand.svg" alt="PDPA THAILAND">
				</a>
			</div>

			<!-- Check transient $pdpa_thailand_rating -->
			<?php if ($pdpa_thailand_rating === false): ?>
				<div class="notice dpdpa--info">
					<p><?php _e('<strong>Designil PDPA Thailand</strong> If you’re enjoying our plugin, we would greatly appreciate a <span class="star">★★★★★</span> rating! Your support means the world to us!', 'pdpa-thailand'); ?></p>
					<p>
						<button class="button button-primary dpdpa--rating" attr-status="yes">
							<svg height="20px" width="20px" version="1.1" id="_x32_" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 512 512" xml:space="preserve" fill="#000000">
								<g id="SVGRepo_bgCarrier" stroke-width="0" />
								<g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round" />
								<g id="SVGRepo_iconCarrier">
									<style type="text/css">
										.st0 {
											fill: #ffffff;
										}
									</style>
									<g>
										<path class="st0" d="M480.925,279.697c-11.272-12.285-32.272-9.672-46.316,0.716c-12.834,10.695-100.573,68.357-100.573,68.357 H227.397l-0.336,0.168c-5.617-0.186-10.006-4.902-9.84-10.509c0.205-5.618,4.93-10.017,10.51-9.822l-0.335-0.195 c19.065,0,78.569,0,78.569,0c15.773,0,28.571-12.779,28.571-28.542c0-15.792-12.798-28.58-28.571-28.58 c-14.285,0-42.838,0-114.246,0c-71.427,0-94.045,29.771-119.044,54.751l-45.348,39.62c-2.958,2.567-4.65,6.259-4.65,10.184V507.51 c0,1.739,1.042,3.348,2.641,4.083c1.6,0.726,3.479,0.474,4.818-0.688l87.646-75.147c3.088-2.623,7.217-3.739,11.198-3.023 l136.604,24.832c9.523,1.73,19.326-0.455,27.268-6.044c0,0,174.326-121.23,187.216-131.954 C492.327,308.315,492.197,291.983,480.925,279.697z" />
										<path class="st0" d="M216.627,218.333c21.521,14.742,48.604,25.548,48.604,25.548c2.492,0.81,6.343,1.516,7.682,1.516 c1.321,0,5.171-0.706,7.664-1.516c0,0,27.064-10.806,48.603-25.548c32.774-22.34,85.935-66.191,85.935-128.01 c0-62.703-35.472-91.116-74.495-90.306c-29.761,0.539-47.339,18.126-59.132,35.462c-2.158,3.218-5.376,5.273-8.575,5.357 c-3.218-0.084-6.436-2.139-8.575-5.357c-11.793-17.336-29.389-34.923-59.15-35.462c-39.043-0.81-74.477,27.603-74.477,90.306 C130.711,152.142,183.852,195.994,216.627,218.333z M187.368,39.282c2.994-3.673,6.733-6.788,11.011-9.384 c4.223-2.548,9.71-1.2,12.258,3.023c2.568,4.222,1.209,9.719-3.014,12.258c-2.808,1.711-4.873,3.497-6.399,5.384 c-3.106,3.832-8.742,4.399-12.574,1.284C184.838,48.732,184.262,43.115,187.368,39.282z M166.442,96.192 c0-5.97,0.614-11.513,1.934-16.61c1.246-4.781,6.139-7.636,10.901-6.38c4.78,1.237,7.625,6.12,6.398,10.89 c-0.874,3.311-1.376,7.347-1.376,12.1c0,4.706,0.484,10.119,1.506,16.183c1.637,9.71,5.767,18.731,11.811,27.203 c2.865,4.008,1.935,9.588-2.083,12.453c-4.036,2.864-9.598,1.934-12.462-2.083c-7.31-10.203-12.723-21.791-14.881-34.597 C167.037,108.488,166.442,102.126,166.442,96.192z" />
									</g>
								</g>
							</svg>
							<?php _e('OK, you deserve it!', 'pdpa-thailand'); ?></button>
						<button class="button button-secondary dpdpa--rating" attr-status="later">
							<svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
								<g id="SVGRepo_bgCarrier" stroke-width="0" />
								<g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round" />
								<g id="SVGRepo_iconCarrier">
									<path d="M12 21C16.9706 21 21 16.9706 21 12C21 7.02944 16.9706 3 12 3C7.02944 3 3 7.02944 3 12C3 16.9706 7.02944 21 12 21Z" stroke="#2271B1" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
									<path d="M12 6V12" stroke="#2271B1" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
									<path d="M16.24 16.24L12 12" stroke="#2271B1" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
								</g>
							</svg> <?php _e('Maybe later', 'pdpa-thailand'); ?>
						</button>
						<button class="button button-link button-link-delete dpdpa--rating" attr-status="no"><?php _e('Never ask again', 'pdpa-thailand'); ?></button>
					</p>
				</div>
			<?php endif; ?>

			<h2></h2>


			<nav class="nav-tab-wrapper">
				<a href="?page=pdpa-thailand" class="nav-tab <?php if ($tab == '') {
																												echo 'nav-tab-active';
																											} ?>"><?php _e('General', 'pdpa-thailand'); ?></a>
				<a href="?page=pdpa-thailand&tab=msg" class="nav-tab <?php if ($tab == 'msg') {
																																echo 'nav-tab-active';
																															} ?>"><?php _e('Messages', 'pdpa-thailand'); ?></a>
				<a href="?page=pdpa-thailand&tab=cookies" class="nav-tab <?php if ($tab == 'cookies') {
																																		echo 'nav-tab-active';
																																	} ?>"><?php _e('Cookies', 'pdpa-thailand'); ?></a>
				<!-- <a href="https://www.designilpdpa.com/documentation/settings/cookies-detail/" class="nav-tab" target="_blank"><?php _e('Cookies Detail <span class="pdpa--thailand-pro">PRO</span>', 'pdpa-thailand'); ?></a> -->
				<a href="?page=pdpa-thailand&tab=appearance" class="nav-tab <?php if ($tab == 'appearance') {
																																			echo 'nav-tab-active';
																																		} ?>"><?php _e('Appearance', 'pdpa-thailand'); ?></a>
				<a href="?page=pdpa-thailand&tab=freevspro" class="nav-tab <?php if ($tab == 'freevspro') {
																																			echo 'nav-tab-active';
																																		} ?>"><?php _e('Free vs Pro', 'pdpa-thailand'); ?></a>
			</nav>

			<form action="options.php" method="post">
				<?php
				switch ($tab):
					case 'appearance':
						// Output nonce, action, and option_page fields for a settings page.
						settings_fields('pdpa_thailand_appearance_group');

						// Prints out all settings sections added to a particular settings page. 
						do_settings_sections('pdpa-thailand-appearance');

						// Output save settings button
						submit_button(__('Save Settings', 'pdpa-thailand'));
						break;
					case 'msg':
						// Output nonce, action, and option_page fields for a settings page.
						settings_fields('pdpa_thailand_msg_group');

						// Prints out all settings sections added to a particular settings page. 
						do_settings_sections('pdpa-thailand-msg');

						// Output save settings button
						submit_button(__('Save Settings', 'pdpa-thailand'));
						break;
					case 'cookies':
						// Output nonce, action, and option_page fields for a settings page.
						settings_fields('pdpa_thailand_cookies_group');

						// Prints out all settings sections added to a particular settings page. 
						do_settings_sections('pdpa-thailand-cookies');

						// Output save settings button
						echo '<div class="pdpa--right">';
						submit_button(__('Save Settings', 'pdpa-thailand'));
						echo '</div>';
						break;
					case 'freevspro':
						// Output nonce, action, and option_page fields for a settings page.
						settings_fields('pdpa_thailand_freevspro_group');

						// Prints out all settings sections added to a particular settings page. 
						do_settings_sections('pdpa-thailand-freevspro');
						break;
					default:
						// Output nonce, action, and option_page fields for a settings page.
						settings_fields('pdpa_thailand_settings_group');

						// Prints out all settings sections added to a particular settings page. 
						do_settings_sections('pdpa-thailand');

						// Output save settings button
						submit_button(__('Save Settings', 'pdpa-thailand'));
						break;
				endswitch;
				?>
			</form>

			<?php if ($tab == 'cookies') : ?>
				<!-- TEMPLATE -->
				<div class="pdpa--li_template">
					<?php $this->cookie_list_default(); ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	public function admin_enqueue($hook)
	{
		// Load only on Starer Plugin plugin pages
		if ($hook != "toplevel_page_pdpa-thailand") {
			return;
		}
		// Media
		wp_enqueue_media();
		// Main CSS
		wp_enqueue_style('designil-pdpa-google-fonts', 'https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@400;600&display=swap');
		wp_enqueue_style('pdpa-thailand-admin', PDPA_THAILAND_URL . 'admin/assets/css/pdpa-thailand-admin.min.css', '', PDPA_THAILAND_VERSION);
		// Main JS
		wp_enqueue_script('jquery-ui-sortable');
		wp_enqueue_script('pdpa-thailand-admin', PDPA_THAILAND_URL . 'admin/assets/js/pdpa-thailand-admin.min.js', array('jquery'), PDPA_THAILAND_VERSION, true);

		wp_localize_script(
			'pdpa-thailand-admin',
			'pdpa_thailand',
			array(
				'url'   => admin_url('admin-ajax.php'),
				'nonce' => wp_create_nonce('pdpa_thailand_nonce'),
				'policy_edit_url' => admin_url('post.php?post=&action=edit'),
				'delete_layer' => __('Please confirm to delete this row ?', 'pdpa-thailand'),
				'error_cookie_unique' => __('This cookie name is not unique', 'pdpa-thailand'),
				'error_cookie_name' => __('Only allow A-Z, a-z, -, _', 'pdpa-thailand')
			)
		);
	}

	public function load_plugin_textdomain()
	{
		load_plugin_textdomain('pdpa-thailand', false, PDPA_THAILAND . '/languages/');
	}

	public function post_states($post_states, $post)
	{
		if (isset($this->msg['policy_page']) && $post->ID == $this->msg['policy_page']) {
			$post_states[] = __('PDPA Thailand - Policy Page', 'pdpa-thailand');
		}
		return $post_states;
	}

	public function settings_link($links)
	{
		return array_merge(
			array(
				'settings' => '<a href="' . admin_url('options-general.php?page=pdpa-thailand') . '">' . __('Settings', 'pdpa-thailand') . '</a>',
				'go_pro' => '<a href="https://designilpdpa.com/checkout?edd_action=add_to_cart&download_id=29&edd_options%5Bprice_id%5D=1&discount=UPGRADE" target="_blank">' . __('Upgrade 30% Off!', 'pdpa-thailand') . '</a>'
			),
			$links
		);
	}

	public function plugin_row_meta($links, $file)
	{
		if (strpos($file, 'pdpa-thailand.php') !== false) {
			$new_links = array(
				'documentation' => '<a href="https://www.designilpdpa.com/documentation/" target="_blank">Documentation</a>',
				'support' 		=> '<a href="https://www.designilpdpa.com/my-tickets/" target="_blank">Support</a>'
			);
			$links = array_merge($links, $new_links);
		}
		return $links;
	}

	public function footer_text($default)
	{

		// Retun default on non-plugin pages
		$screen = get_current_screen();
		if ($screen->id == "toplevel_page_pdpa-thailand") {
		?>
			<div class="dpdpa--quicklink">
				<a href="#" class="dpdpa--quicklink-button">
					<img src="<?php echo PDPA_THAILAND_URL; ?>admin/assets/images/logo.png" alt="">
				</a>
				<ul class="dpdpa--quicklink-list">
					<li>
						<a href="https://www.designilpdpa.com/documentation" target="_blank">
							<span><?php _e('Documentaion', 'pdpa-thailand'); ?></span>
							<div class="icon">
								<img src="<?php echo PDPA_THAILAND_URL; ?>admin/assets/images/quick-book.svg">
							</div>
						</a>
					</li>
					<li>
						<a href="https://www.designilpdpa.com/#faqs" target="_blank">
							<span><?php _e('FAQs', 'pdpa-thailand'); ?></span>
							<div class="icon">
								<img src="<?php echo PDPA_THAILAND_URL; ?>admin/assets/images/quick-faq.svg">
							</div>
						</a>
					</li>
					<li>
						<a href="https://www.designilpdpa.com/my-tickets/" target="_blank">
							<span><?php _e('Support', 'pdpa-thailand'); ?></span>
							<div class="icon">
								<img src="<?php echo PDPA_THAILAND_URL; ?>admin/assets/images/quick-service.svg">
							</div>
						</a>
					</li>
				</ul>
			</div>
			<script>
				(function($) {
					$(document).ready(function() {
						// Quick link
						$('.dpdpa--quicklink-button').click(function() {
							$(this).parent().toggleClass('active');
						});
					});
				})(jQuery);
			</script>
		<?php
			return 'PDPA Thailand v.' . PDPA_THAILAND_VERSION;
		}

		return $default;
	}

	public function cookie_list_default($cookie_set = array())
	{
		// Default template - cookie list
		$cookie_name = '';
		$consent_title = '';
		$consent_description = '';
		$code_in_head = '';
		$code_next_body = '';
		$code_body_close = '';
		$gg_analytic_script = '';
		$gg_analytic_id = '';
		$fb_pixel_id = '';

		if (count($cookie_set)) {
			$cookie_name = stripslashes($cookie_set['cookie_name']);
			$consent_title = stripslashes($cookie_set['consent_title']);
			$consent_description = stripslashes($cookie_set['consent_description']);
			$code_in_head = stripslashes($cookie_set['code_in_head']);
			$code_next_body = stripslashes($cookie_set['code_next_body']);
			$code_body_close = stripslashes($cookie_set['code_body_close']);
			$gg_analytic_script = stripslashes($cookie_set['gg_analytic_script']);
			$gg_analytic_id = stripslashes($cookie_set['gg_analytic_id']);
			$fb_pixel_id = stripslashes($cookie_set['fb_pixel_id']);
		}
		?>
		<li class="active">
			<div class="pdpa--list-inner">
				<div class="pdpa--list-head">
					<div class="form-group">
						<div class="form-group--title">
							<input type="text" class="regular-text" name="consent_title[]" placeholder="<?php _e('Consent title *', 'pdpa-thailand'); ?>" value="<?php echo $consent_title; ?>">
						</div>
					</div>
					<div class="form-group--action">
						<a href="#" class="accordion">
							<svg width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" class="components-panel__arrow" role="img" aria-hidden="true" focusable="false">
								<path fill="#888" d="M6.5 12.4L12 8l5.5 4.4-.9 1.2L12 10l-4.5 3.6-1-1.2z"></path>
							</svg>
						</a>
					</div>
				</div>
				<div class="pdpa--list-body">
					<div class="form-group">
						<label>
							<?php _e('Consent description *', 'pdpa-thailand'); ?>
						</label>
						<textarea class="regular-text" name="consent_description[]" rows="3"><?php echo $consent_description; ?></textarea>
					</div>
					<div class="form-group">
						<label>
							<?php _e('Cookie name *', 'pdpa-thailand'); ?>
						</label>
						<input type="text" class="regular-text" name="cookie_name[]" value="<?php echo $cookie_name; ?>">
						<label for="cookie_name" id="erorr_cookie_name" class="pdpa--label-error"></label>
					</div>

					<div class="pdpa--list-col col-3">
						<div class="form-group">
							<label>
								<input type="checkbox" name="gg_analytic_script[]" value="1" <?php if (isset($gg_analytic_script) && $gg_analytic_script == '1') {
																																								echo 'checked';
																																							} ?>>
								<span><?php _e('Google analytic', 'pdpa-thailand'); ?></span>
							</label>
							<input type="text" name="gg_analytic_id[]" value="<?php echo $gg_analytic_id; ?>" placeholder="UA-XXXXX-Y">
						</div>
						<div class="form-group">
							<label>
								<input type="checkbox" name="fb_pixel_script[]" value="1" disabled>
								<span><?php _e('Facebook pixel <span class="pdpa--thailand-pro">PRO</span>', 'pdpa-thailand'); ?></span>
							</label>
							<input type="text" name="fb_pixel_id[]" value="" disabled>
						</div>
						<div class="form-group">
						</div>
					</div>
					<div class="pdpa--list-col col-3">
						<div class="form-group">
							<label>
								<?php _e('Code in &lt;head&gt;&lt;/head&gt; <span class="pdpa--thailand-pro">PRO</span>', 'pdpa-thailand'); ?>
							</label>
							<textarea class="regular-text" name="code_in_head[]" rows="5" readonly disabled><?php echo $code_in_head; ?></textarea>
						</div>
						<div class="form-group">
							<label>
								<?php _e('Code next to &lt;body&gt; <span class="pdpa--thailand-pro">PRO</span>', 'pdpa-thailand'); ?>
							</label>
							<textarea class="regular-text" name="code_next_body[]" rows="5" readonly disabled><?php echo $code_next_body; ?></textarea>
						</div>
						<div class="form-group">
							<label>
								<?php _e('Code before &lt;/body&gt; <span class="pdpa--thailand-pro">PRO</span>', 'pdpa-thailand'); ?>
							</label>
							<textarea class="regular-text" name="code_body_close[]" rows="5" readonly disabled><?php echo $code_body_close; ?></textarea>
						</div>
					</div>
				</div>
			</div>
		</li>
<?php
	}

	function darken_color($rgb, $darker = 2)
	{
		$hash = (strpos($rgb, '#') !== false) ? '#' : '';
		$rgb = (strlen($rgb) == 7) ? str_replace('#', '', $rgb) : ((strlen($rgb) == 6) ? $rgb : false);
		if (strlen($rgb) != 6) return $hash . '000000';
		$darker = ($darker > 1) ? $darker : 1;

		list($R16, $G16, $B16) = str_split($rgb, 2);

		$R = sprintf("%02X", floor(hexdec($R16) / $darker));
		$G = sprintf("%02X", floor(hexdec($G16) / $darker));
		$B = sprintf("%02X", floor(hexdec($B16) / $darker));

		return $hash . $R . $G . $B;
	}

	// Rating saved
	public function rating_saved()
	{

		check_ajax_referer('pdpa_thailand_nonce', 'nonce');

		$status = sanitize_text_field($_POST['status']);
		// Save if yes or no forever if later have and expired transient
		if ($status == 'yes') {
			set_transient('pdpa_thailand_rating', $status, 60 * 60 * 24 * 365);
		} else if ($status == 'no') {
			set_transient('pdpa_thailand_rating', $status, 60 * 60 * 24 * 365);
		} else if ($status == 'later') {
			set_transient('pdpa_thailand_rating', $status, 60 * 60 * 24);
		}

		echo $status;

		die();
	}
}
