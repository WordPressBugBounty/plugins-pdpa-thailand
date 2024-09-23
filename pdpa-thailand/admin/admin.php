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

		// echo '<pre>';
		// print_r($this->options);
		// echo '</pre>';

		// HOOK
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

		// Cookie unqiue id
		add_settings_field(
			'cookie_unique_id',
			sprintf(__('Reset Cookie ID <a href="%s" class="pdpa--link" target="_blank"></a>', 'pdpa-thailand'), 'https://www.designilpdpa.com/documentation/settings/general/'),
			array($this, 'cookie_unique_id_callback'),
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

		// Container size
		add_settings_field(
			'appearance_container_size',
			__('Max popup width <span class="pdpa--thailand-pro">PRO</span> <a href="#popup_max_container_size" class="pdpa--info"></a>', 'pdpa-thailand'),
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

		// Main color
		add_settings_field(
			'appearance_color',
			__('Main color', 'pdpa-thailand'),
			array($this, 'appearance_color_callback'),
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

		// Positioin
		add_settings_field(
			'appearance_position',
			__('Sidebar position <span class="pdpa--thailand-pro">PRO</span>', 'pdpa-thailand'),
			array($this, 'appearance_position_callback'),
			'pdpa-thailand-appearance',
			'pdpa_thailand_appearance'
		);
		/************************ 
		Appearance
		 *************************/


		/************************ 
		Advacned
		 *************************/
		register_setting(
			'pdpa_thailand_advanced_group',
			'pdpa_thailand_advanced',
			''
		);

		// Register A New Section
		add_settings_section(
			'pdpa_thailand_advanced',
			__('', 'pdpa-thailand'),
			array($this, 'pdpa_thailand_advanced_intro'),
			'pdpa-thailand-advanced'
		);

		/************************ 
		Advacned
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
		<div class="form-group">
			<input type="text" name="pdpa_thailand_settings[cookie_unique_id]" value="<?php if (isset($this->options['cookie_unique_id'])) echo $this->options['cookie_unique_id']; ?>" readonly>
			<a href="#" class="button button-primary refresh--cookie">
				<img src="<?php echo PDPA_THAILAND_URL . 'admin/assets/images/refresh.svg'; ?>" alt="">
			</a>
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

	public function cookie_duration_callback()
	{
	?>
		<div class="form-group">
			<input type="text" class="small-text" placeholder="7" name="pdpa_thailand_settings[cookie_duration]" value="7" readonly> <?php _e('Days', 'pdpa-thailand'); ?>
		</div>
	<?php
	}
	/************************ 
	Settings
	 *************************/

	/************************ 
	MSG
	 *************************/
	public function pdpa_thailand_msg_intro()
	{
	}

	public function cookie_policy_page_callback()
	{
		if (isset($this->msg['policy_page'])) {
			$policy_edit = admin_url('post.php?post=' . $this->msg['policy_page'] . '&action=edit');
		}
	?>
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
				<img src="<?php echo PDPA_THAILAND_URL; ?>admin/assets/images/edit.svg" alt="">
			</a>
		</div>
	<?php
	}

	public function cookie_consent_message_callback()
	{
	?>
		<div class="form-group">
			<textarea name="pdpa_thailand_msg[cookie_consent_message]" id="" rows="4" class="large-text"><?php if (isset($this->msg['cookie_consent_message'])) echo $this->msg['cookie_consent_message']; ?></textarea>
			<p class="description"><?php _e('<label>Shortcode</label>[dpdpa_policy_page title="Cookies policy"] For showing link to policy page<br>[dpdpa_settings title="Cookie settings"] For calling sidebar and show cookie settings or Enable settings button ( Tab General )', 'pdpa-thailand'); ?></p>
		</div>
	<?php
	}

	public function sidebar_message_callback()
	{
	?>
		<div class="form-group">
			<textarea name="pdpa_thailand_msg[sidebar_message]" id="" rows="4" class="large-text"><?php if (isset($this->msg['sidebar_message'])) echo $this->msg['sidebar_message']; ?></textarea>
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
			'fb_pixel_script' => $this->pdpa_thailand_recursive_sanitize_text_field(isset($_POST['fb_pixel_script']) ? $_POST['fb_pixel_script'] : array()),
			'fb_pixel_id' => $this->pdpa_thailand_recursive_sanitize_text_field($_POST['fb_pixel_id'])
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

				if (isset($cookies_list['fb_pixel_script'][$i]) &&  $cookies_list['fb_pixel_script'][$i] == 1) {
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
				}

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

							if (isset($cookie_list['fb_pixel_script'][$i])) {
								$fb_pixel_script = $cookie_list['fb_pixel_script'][$i];
							} else {
								$fb_pixel_script = '';
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
								'fb_pixel_script' => $fb_pixel_script,
								'fb_pixel_id' => $cookie_list['fb_pixel_id'][$i],
							);
							$this->cookie_list_default($cookie_set);
						}
					}
				}
				?>
			</ul>
			<div class="pdpa--button">
				<a href="#" class="button button-secondary pdpa--reset-cookie"><?php _e('Reset', 'pdpa-thailand'); ?></a>
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
	public function pdpa_thailand_appearance_intro()
	{
	}

	public function prepare_save_appearance($settings)
	{
		update_option('pdpa_thailand_css_version', rand());
		// echo '<pre>';
		// print_r($settings);
		// echo '</pre>';

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

		// echo '<pre>';
		// print_r($cssString);
		// echo '</pre>';
		// die;

		return $settings;
	}

	public function appearance_logo_callback()
	{
		$src = '';

		if (isset($this->appearance['appearance_logo']) && $this->appearance['appearance_logo'] != '') {
			$src = wp_get_attachment_image_src($this->appearance['appearance_logo'], 'thumbnail')[0];
		}
	?>
		<div class="form-group">
			<div class="dpdpa--logo">
				<div class="dpdpa--logo-box">
					<img src="<?php echo $src; ?>" alt="">
				</div>
			</div>
			<a href="#" class="button-secondary button" id="dpda--upload" disabled>Select / Upload</a>
			<input type="hidden" name="pdpa_thailand_appearance[appearance_logo]" value="<?php if (isset($this->appearance['appearance_logo'])) {
																																											echo $this->appearance['appearance_logo'];
																																										} ?>">

		</div>
		<?php /* <label class="dpdpa--form-group switch">
				<input type="checkbox" name="pdpa_thailand_appearance[appearance_logo]" id="appearance_logo" value="1" 
					<?php if ( isset( $this->appearance['appearance_logo'] ) ) { checked( '1', $this->appearance['appearance_logo'] ); } ?>>				
				<span class="slider round"></span>
			</label> */ ?>
	<?php
	}

	public function appearance_container_size_callback()
	{
		$size = '';
		$point = '';
		$size_point = '';
		if (isset($this->appearance['appearance_container_size']) && strpos($this->appearance['appearance_container_size'], '|') !== false) {
			$size_point = explode('|', $this->appearance['appearance_container_size']);
			$size = $size_point[0];
			$point = $size_point[1];
			$size_point = implode('|', $size_point);
		}
	?>
		<div class="form-group">
			<input type="number" class="small-text" placeholder="1200" name="appearance_container_size" value="1200" readonly>
			<select name="appearance_container_point" disabled>
				<option value="px" <?php if ($point == 'px') echo 'selected'; ?>>px</option>
				<option value="%" <?php if ($point == '%') echo 'selected'; ?>>%</option>
			</select>
			<input type="hidden" name="pdpa_thailand_appearance[appearance_container_size]" value="<?php echo $size_point; ?>">
		</div>
	<?php
	}

	public function appearance_color_callback()
	{
	?>
		<div class="form-group">
			<label>
				<input type="radio" name="pdpa_thailand_appearance[appearance_color]" value="#006ff4" <?php if (isset($this->appearance['appearance_color']) && $this->appearance['appearance_color'] == '#006ff4') {
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
	<?php
	}

	public function appearance_position_callback()
	{
	?>
		<div class="form-group">
			<select name="pdpa_thailand_appearance[appearance_position]" disabled>
				<option value="left" <?php if (isset($this->appearance['appearance_position']) && $this->appearance['appearance_position'] == 'left') {
																echo 'selected';
															} ?>><?php _e('Left', 'pdpa-thailand'); ?></option>
				<option value="right" <?php if (isset($this->appearance['appearance_position']) && $this->appearance['appearance_position'] == 'right') {
																echo 'selected';
															} ?>><?php _e('Right', 'pdpa-thailand'); ?></option>
			</select>
		</div>
	<?php
	}

	public function appearance_mode_callback()
	{
	?>
		<div class="form-group">
			<select name="pdpa_thailand_appearance[appearance_mode]" disabled>
				<option value="light" <?php if (isset($this->appearance['appearance_mode']) && $this->appearance['appearance_mode'] == 'light') {
																echo 'selected';
															} ?>><?php _e('Light', 'pdpa-thailand'); ?></option>
				<option value="dark" <?php if (isset($this->appearance['appearance_mode']) && $this->appearance['appearance_mode'] == 'dark') {
																echo 'selected';
															} ?>><?php _e('Dark', 'pdpa-thailand'); ?></option>
			</select>
		</div>
	<?php
	}
	/************************ 
	Apperance
	 *************************/


	/************************ 
	Advanced
	 *************************/
	public function pdpa_thailand_advanced_intro()
	{
		$template_path = get_template_directory() . '/pdpa-thailand/';
		$plugin_path = PDPA_THAILAND_DIR . 'template/';

		$popup = array(
			'status' => __('Not found', 'pdpa-thailand'),
			'path' => '',
			'class' => 'default'
		);

		if (file_exists($plugin_path . 'popup.php')) {
			$popup = array(
				'status' => __('Default', 'pdpa-thailand'),
				'path' => $plugin_path . 'popup.php',
				'class' => 'default'
			);
		}

		$sidebar = array(
			'status' => '',
			'path' => '',
			'class' => 'default'
		);

		if (file_exists($plugin_path . 'sidebar.php')) {
			$sidebar = array(
				'status' => __('Default', 'pdpa-thailand'),
				'path' => $plugin_path . 'sidebar.php',
				'class' => 'default'
			);
		}
	?>
		<h2><?php _e('Template', 'pdpa-thailand'); ?></h2>
		<p><?php echo sprintf(__('Developers can override PDPA Thailand\'s template by creating a file  in the /wp-content/theme/pdpa-thailand folder. <a href="%s">Read more</a> here <span class="pdpa--thailand-pro">(PRO Only)</span>', 'pdpa-thailand'), 'https://www.designilpdpa.com/documentation/settings/advanced/'); ?></p>
		<table class="widefat pdpa--advanced">
			<thead>
				<th width="200px"><?php _e('Template', 'pdpa-thailand'); ?></th>
				<th width="100px"><?php _e('Status', 'pdpa-thailand'); ?></th>
				<th><?php _e('File', 'designil-dpap'); ?></th>
			</thead>
			<tbody>
				<tr>
					<td><?php _e('Popup', 'designil-dpap'); ?></td>
					<td class="<?php echo $popup['class']; ?>"><?php echo $popup['status']; ?></td>
					<td><?php echo $popup['path']; ?></td>
				</tr>
				<tr>
					<td><?php _e('Sidebar', 'designil-dpap'); ?></td>
					<td class="<?php echo $sidebar['class']; ?>"><?php echo $sidebar['status']; ?></td>
					<td><?php echo $sidebar['path']; ?></td>
				</tr>
			</tbody>
		</table>
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

		if (isset($_GET['settings-updated'])) {
			// Add settings saved message with the class of "updated"
			add_settings_error('pdpa_thailand_settings_saved_message', 'pdpa_thailand_settings_saved_message', __('Settings are Saved', 'pdpa-thailand'), 'updated');
		}

		// Show Settings Saved Message
		settings_errors('pdpa_thailand_settings_saved_message'); ?>

		<div class="wrap">
			<h1>PDPA Thailand</h1>

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
				<a href="https://www.designilpdpa.com/documentation/settings/cookies-detail/" class="nav-tab" target="_blank"><?php _e('Cookies Detail <span class="pdpa--thailand-pro">PRO</span>', 'pdpa-thailand'); ?></a>
				<a href="?page=pdpa-thailand&tab=appearance" class="nav-tab <?php if ($tab == 'appearance') {
																																			echo 'nav-tab-active';
																																		} ?>"><?php _e('Appearance', 'pdpa-thailand'); ?></a>
				<a href="https://www.designilpdpa.com/documentation/settings/logs/" class="nav-tab" target="_blank"><?php _e('Logs <span class="pdpa--thailand-pro">PRO</span>', 'pdpa-thailand'); ?></a>
				<a href="https://www.designilpdpa.com/documentation/settings/request-form/" class="nav-tab" target="_blank"><?php _e('Request Form <span class="pdpa--thailand-pro">PRO</span>', 'pdpa-thailand'); ?></a>
				<a href="https://www.designilpdpa.com/documentation/settings/advanced/" class="nav-tab" target="_blank"><?php _e('Advanced <span class="pdpa--thailand-pro">PRO</span>', 'pdpa-thailand'); ?></a>
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
					case 'advanced':
						// Output nonce, action, and option_page fields for a settings page.
						settings_fields('pdpa_thailand_advanced_group');

						// Prints out all settings sections added to a particular settings page. 
						do_settings_sections('pdpa-thailand-advanced');
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
			<div id="popup_max_container_size" class="white_popup">
				<img src="<?php echo PDPA_THAILAND_URL; ?>admin/assets/images/info-max-container-size.png" alt="" class="appearance--info">
			</div>
			<div id="popup_logo" class="white_popup">
				<img src="<?php echo PDPA_THAILAND_URL; ?>admin/assets/images/info-logo.png" alt="" class="appearance--info">
			</div>
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
		wp_enqueue_style('pdpa-thailand-admin', PDPA_THAILAND_URL . 'admin/assets/css/pdpa-thailand-admin.min.css', '', PDPA_THAILAND_VERSION);
		// Main JS
		wp_enqueue_script('jquery-ui-sortable');
		wp_enqueue_script('pdpa-thailand-magnific', PDPA_THAILAND_URL . 'admin/assets/js/jquery.magnific.min.js', array(), PDPA_THAILAND_VERSION, true);
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
				'settings' => '<a href="' . admin_url('options-general.php?page=pdpa-thailand') . '">' . __('Settings', 'pdpa-thailand') . '</a>'
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
		$fb_pixel_script = '';
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
			$fb_pixel_script = stripslashes($cookie_set['fb_pixel_script']);
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
								<span>Google analytic</span>
							</label>
							<input type="text" name="gg_analytic_id[]" value="<?php echo $gg_analytic_id; ?>" placeholder="UA-XXXXX-Y">
						</div>
						<div class="form-group">
							<label>
								<input type="checkbox" name="fb_pixel_script[]" value="1" <?php if (isset($fb_pixel_script) && $fb_pixel_script == '1') {
																																						echo 'checked';
																																					} ?>>
								<span>Facebook pixel</span>
							</label>
							<input type="text" name="fb_pixel_id[]" value="<?php echo $fb_pixel_id; ?>" placeholder="{your-pixel-id-goes-here}">
						</div>
						<div class="form-group">
						</div>
					</div>
					<div class="pdpa--list-col col-3">
						<div class="form-group">
							<label>
								<?php _e('Code in &lt;head&gt;&lt;/head&gt; <span class="pdpa--thailand-pro">PRO</span>', 'pdpa-thailand'); ?>
							</label>
							<textarea class="regular-text" name="code_in_head[]" rows="5" readonly><?php echo $code_in_head; ?></textarea>
						</div>
						<div class="form-group">
							<label>
								<?php _e('Code next to &lt;body&gt; <span class="pdpa--thailand-pro">PRO</span>', 'pdpa-thailand'); ?>
							</label>
							<textarea class="regular-text" name="code_next_body[]" rows="5" readonly><?php echo $code_next_body; ?></textarea>
						</div>
						<div class="form-group">
							<label>
								<?php _e('Code before &lt;/body&gt; <span class="pdpa--thailand-pro">PRO</span>', 'pdpa-thailand'); ?>
							</label>
							<textarea class="regular-text" name="code_body_close[]" rows="5" readonly><?php echo $code_body_close; ?></textarea>
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
}
