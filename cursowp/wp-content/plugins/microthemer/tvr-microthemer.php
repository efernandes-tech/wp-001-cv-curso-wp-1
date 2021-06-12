<?php
/*
Plugin Name: Microthemer
Plugin URI: https://themeover.com/microthemer
Text Domain: microthemer
Domain Path: /languages
Description: Microthemer is a feature-rich visual design plugin for customizing the appearance of ANY WordPress Theme or Plugin Content (e.g. posts, pages, contact forms, headers, footers, sidebars) down to the smallest detail. For CSS coders, Microthemer is a proficiency tool that allows them to rapidly restyle a WordPress theme or plugin. For non-coders, Microthemer's intuitive point and click editing opens the door to advanced theme and plugin customization.
Version: 6.3.7.0
Author: Themeover
Author URI: https://themeover.com
*/

/* Copyright 2017 by Sebastian Webb @ Themeover */

// Stop direct call
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
	die('Please do not call this page directly.');
}

// define active
if (!defined('MT_IS_ACTIVE')) {
	define('MT_IS_ACTIVE', true);
}

// define plugin variation
if (!defined('TVR_MICRO_VARIANT')) {
	define('TVR_MICRO_VARIANT', 'themer');
}

// define dev mode
if (!defined('TVR_DEV_MODE')) {
	define('TVR_DEV_MODE', false);
}

// define debug data mode
if (!defined('TVR_DEBUG_DATA')) {
	define('TVR_DEBUG_DATA', false);
}

// define unique id for media query keys
if (!defined('UNQ_BASE')) {
	define('UNQ_BASE', uniqid());
}

// common class for data needed by front and admin
if (!class_exists('tvr_common')) {
	class tvr_common {

		public static function get_protocol(){
			$isSSL = is_ssl(); // (!empty($_SERVER["HTTPS"]) and $_SERVER["HTTPS"] == "on");
			return 'http' . ($isSSL ? 's' : '') . '://';
		}

	    public static function get_custom_code(){
			return array(
				'hand_coded_css' => array (
					'tab-key' => 'all-browsers',
					'label' => esc_html__('All Browsers', 'microthemer'),
					//'label' => esc_html__('CSS', 'microthemer'),
					'type' => 'css'
				),
				'ie_css' => array(
					'all' => array (
						'tab-key' => 'all',
						'label' => esc_html__('All versions of IE', 'microthemer'),
						'cond' => 'IE',
						'type' => 'css'
					),
					'nine' => array (
						'tab-key' => 'nine',
						'label' => esc_html__('IE9 and below', 'microthemer'),
						'cond' => 'lte IE 9',
						'type' => 'css'
					),
					'eight' => array (
						'tab-key' => 'eight',
						'label' => esc_html__('IE8 and below', 'microthemer'),
						'cond' => 'lte IE 8',
						'type' => 'css'
					),
					'seven' => array (
						'tab-key' => 'seven',
						'label' => esc_html__('IE7 and below', 'microthemer'),
						'cond' => 'lte IE 7',
						'type' => 'css'
					),
				),
				'js' => array (
					'tab-key' => 'js',
					'label' => esc_html__('JS', 'microthemer'),
					'type' => 'javascript'
				),
			);
		}

		// add a param to an existing url if it doesn't exist, using the correct joining char
        public static function append_url_param($url, $param, $val = false){

            // bail if already present
            if (strpos($url, $param) !== false){
                return $url;
            }

            // we do need to add param, so determine joiner
            $joiner = strpos($url, '?') !== false ? '&': '?';

            // is there param val?
	        $param = $val ? $param.'='.$val : $param;

            // return new url
            return $url . $joiner . $param;

        }

		// strip a single parameter from an url (adapted from JS function)
		public static function strip_url_param($url, $param, $withVal = true){

			$param = $withVal ? $param . '(?:=[a-z0-9]+)?' : $param;
			$pattern = '/(?:&|\?)' . $param . '/';
			$url = preg_replace($pattern, '', $url);

			// check we don't have an any params that start with & instead of ?
			if (strpos($url, '&') !== false && strpos($url, '?') === false){
				preg_replace('/&/', '?', $url, 1); // just replaces the first instance of & with ?
			}

			return $url;
		}

		// &preview= and ?preview= cause problems - strip everything after (more heavy handed than above function)
		public static function strip_preview_params($url){
			//$url = explode('preview=', $url); // which didn't support regex (for e.g. elementor)
			$url = preg_split('/(?:elementor-)?preview=/', $url, -1);
			$url = rtrim($url[0], '?&');
			return $url;
		}

		public static function params_to_strip(){
			return array(
				array(
					'param' => '_wpnonce',
					'withVal' => true,
				),
			    array(
                    'param' => 'mt_nonlog',
                    'withVal' => false,
                ),
				array(
					'param' => 'mto2_edit_link',
					'withVal' => true,
				),
				array(
					'param' => 'elementor-preview',
					'withVal' => true,
				),
                array(
				     'param' => 'brizy-edit-iframe', // strip brizy
				     'withVal' => false,
			     ),
                array(
	                'param' => 'et_fb', // strip Divi param which causes iframe to break out of parent
	                'withVal' => true,
                ),
                array(
	                'param' => 'fl_builder', // strip beaver builder
	                'withVal' => false,
                ),
				// oxygen params
				array(
					'param' => 'ct_builder',
					'withVal' => true,
                    'unless' => array('ct_template') // ct_template also requires ct_builder to work
				),
				array(
					'param' => 'ct_inner',
					'withVal' => true,
				),
				/* Keep as necessary for showing specific content
				 * array(
					'param' => 'ct_template',
					'withVal' => true,
				),*/
				array(
					'param' => 'oxygen_iframe',
					'withVal' => true,
				),

               // elementor doesn't pass a parameter to the frontend it runs on the admin side

			);
		}

		// we don't strip params that are required when another param is present
		public static function has_excluded_param($url, $array){

			$unless = !empty($array['unless']) ? $array['unless'] : false;
			if ($unless){
				foreach ($unless as $i => $excl){
					if (strpos($url, $excl) !== false){
						return true;
					}
				}
			}

			return false;
		}

		// strip preview= and page builder parameters
		public static function strip_page_builder_and_other_params($url, $strip_preview = true){

		    // strip preview params (regular and elementor)
			//$url = tvr_common::strip_preview_params($url); // test what happens

			$other_params = tvr_common::params_to_strip();

			foreach ($other_params as $key => $array){

			    // we don't strip params that are required when another param is present
                if (tvr_common::has_excluded_param($url, $array)){
                    continue;
                }

				$url = tvr_common::strip_url_param($url, $array['param'], $array['withVal']);
            }

			// strip brizy
			/*$url = tvr_common::strip_url_param($url, 'brizy-edit-iframe', false);

			// strip Divi param which causes iframe to break out of parent
			$url = tvr_common::strip_url_param($url, 'et_fb', true); // this has issue with divi builder

			// strip beaver builder - NO, we're currently checking fl_builder for JS logic.
			$url = tvr_common::strip_url_param($url, 'fl_builder', false);*/

			return $url;

		}

		// we are adding user google fonts on admin side too so they can be shown in UI (todo)
		public static function add_user_google_fonts($p){

            // use g_url_with_subsets value generated when writing stylesheet
			$google_url = !empty($p['g_url_with_subsets'])
				? $p['g_url_with_subsets']

				// fallback to g_url if user has yet to save settings since g_url_with_subsets was added
				: (!empty($p['gfont_subset']) ? $p['g_url'].$p['gfont_subset'] : $p['g_url']);

			if (!empty($google_url)){
				tvr_common::mt_enqueue_or_add(!empty($p['after_oxy_css']), 'microthemer_g_font', $google_url);
			}

		}

		public static function mt_enqueue_or_add($add, $handle, $url, $data_key = false, $data_val = false){

			global $wp_styles;

			// special case for loading CSS after Oxygen
			if ($add){

				$wp_styles->add($handle, $url);
				$wp_styles->enqueue(array($handle));

				if ($data_key){
					$wp_styles->add_data($handle, $data_key, $data_val);
				}

				// allow CSS to load in footer if O2 is active so MT comes after O2 even when O2 active without O2
                // Note this didn't work on my local install, but did on a customer who reported issue with Agency Tools
                // so better to use a more deliberate action hook e.g. wp_footer
                // Ideally, O2 would enqueue a placeholder stylesheet and replace rather than append to head
				/*if ( !defined( 'SHOW_CT_BUILDER' ) ) {
					$wp_styles->do_items($handle);
				}*/

				// (feels a bit risky, but can add if MT loading before O2 when active by itself causes issue for people)
                $wp_styles->do_items($handle);
			}

			else {
				wp_register_style($handle, $url, false);
				wp_enqueue_style($handle);
			}

		}

		// dequeue rougue styles or scripts loading on MT UI page that cause issues for it
		public static function dequeue_rogue_assets(){

			$conflict_styles = array(

				// admin 2020 plugin assets
				'uikitcss',
				'ma_admin_head_css',
				'ma_admin_editor_css',
				'ma_admin_menu_css',
				'ma_admin_mobile_css',
				'custom_wp_admin_css',
				'ma_admin_media_css',
			);

			foreach ($conflict_styles as $style_handle){
				wp_dequeue_style($style_handle);
			}
		}


	}
}

// only run plugin admin code on admin pages
if ( is_admin() ) {

	// admin class
	if (!class_exists('tvr_microthemer_admin')) {

		// define
		class tvr_microthemer_admin {

			var $version = '6.3.7.0';
			var $db_chg_in_ver = '6.0.6.5';

			var $locale = ''; // current language
			var $lang = array(); // lang strings
			var $time = 0;
			var $current_user_id = -1;
			// set this to true if version saved in DB is different, other actions may follow if new v
			var $new_version = false;
			var $activation_function_ran = false;
			var $minimum_wordpress = '3.6';
			var $users_wp_version = 0;
			var $page_prefix = '';
			var $optimisation_test = false;
			var $optionsName= 'microthemer_ui_settings';
			var $preferencesName = 'preferences_themer_loader';
			var $micro_ver_name = 'micro_revisions_version';
			var $localizationDomain = "microthemer";
			var $globalmessage = array();
			var $outdatedTabIssue = 0;
			var $outdatedTabDebug = '';
			var $ei = 0; // error index
			var $permissionshelp;
			var $microthemeruipage = 'tvr-microthemer.php';
			var $microthemespage = 'tvr-manage-micro-themes.php';
			var $managesinglepage = 'tvr-manage-single.php';
			var $preferencespage = 'tvr-microthemer-preferences.php';
			var $detachedpreviewpage = 'tvr-microthemer-preview-wrap.php';
			var $docspage = 'tvr-docs.php';
			var $fontspage = 'tvr-fonts.php';
			var $demo_video = 'https://themeover.com/videos/?name=gettingStarted';
			var $targeting_video = 'https://themeover.com/videos/?name=targeting';
			var $mt_admin_nonce = '';
			var $wp_ajax_url = '';
			var $total_sections = 0;
			var $total_selectors = 0;
			var $sel_loop_count;
			var $sel_count = 0;
			var $sel_option_count = 0;
			var $group_spacer_count = 0;
			var $sel_lookup = array();
			var $trial = true;
			var $initial_options_html = array();
			var $imported_images = array();
			var $site_url = '';
			var $integrations = array();
			var $version_is_capped = false;

			// @var array $pages Stores all the plugin pages in an array
			var $all_pages = array();
			// @var array $css_units Stores all the possible CSS units
			var $css_units = array();
			//var $css_unit_sets = array();
			var $default_my_props = array();
			// @var array $options Stores the ui options for this plugin
			var $options = array();
			var $serialised_post = array();
			var $propertyoptions = array();
			var $en_propertyoptions = array();
			var $property_option_groups = array();
			var $animatable = array();
			var $shorthand = array();
			var $auto_convert_map = array();
			var $legacy_groups = array();
			var $mob_preview = array();
			var $propAliases = array();
			var $cssFuncAliases = array();
			var $input_wrap_templates = array();
			// @var array $options Stores the "to be merged" options in
			var $to_be_merged = array();
			var $dis_text = '';
			// @var array $preferences Stores the preferences for this plugin
			var $preferences = array();
			var $pre_update_preferences = array();
			// @var array $file_structure Stores the micro theme dir file structure
			var $file_structure = array();
			// polyfills
			var $polyfills = array('pie'); // , boxsizing
			// temporarily keep track of the tabs that are available for the property group.
			// This saves additional processing at various stages
			var $current_pg_group_tabs = array();
			var $subgroup = '';
			// default preferences set in constructor
			var $default_preferences = array();
			var $default_preferences_dont_reset = array();
			// edge mode fixed settings
			var $edge_mode = array();

			// default media queries
			var $unq_base = '';
			var $builder_sync_tabs = array();
			var $default_folders = array();
			var $default_m_queries = array();
			var $mobile_first_mqs = array();
			var $mobile_first_semantic_mqs = array();
			var $bb_mqs = array();
			var $elementor_mqs = array();
			var $elementor_breakpoints = false;
			var $oxygen_mqs = array();
			var $oxygen_breakpoints = false;
			var $mq_sets = array();
			var $comb_devs = array(); // for storing all-devs + MQs in one array
			// set default custom code options (todo make use of this array throughout the program)
			var $custom_code = array();
			var $custom_code_flat = array();
			var $params_to_strip = array();

			// @var strings dir/url paths
			var $wp_content_url = '';
			var $wp_content_dir = '';
			var $wp_plugin_url = '';
			var $wp_plugin_dir = '';
			var $thispluginurl = '';
			var $thisplugindir = '';
			var $multisite_blog_id = false;
			var $micro_root_dir = '';
			var $level_map = '';

			// control debug output here

			var $debug_custom = '';
			var $debug_pulled_data = TVR_DEBUG_DATA;
			var $debug_current = TVR_DEBUG_DATA;
			var $debug_import = TVR_DEBUG_DATA;
			var $debug_merge = TVR_DEBUG_DATA;
			var $debug_save = TVR_DEBUG_DATA;
			var $debug_save_package = TVR_DEBUG_DATA;
			var $debug_selective_export = TVR_DEBUG_DATA;
			var $show_me = ''; // for quickly printing vars in the top toolbar


			// Class Functions

			/**
			 * PHP 4 Compatible Constructor
			function tvr_microthemer_admin(){$this->__construct();}
			 **/

			/**
			 * PHP 5 Constructor
			 */
			function __construct(){

				// Stop the plugin if below requirements
				// @taken from ngg gallery: http://wordpress.org/extend/plugins/nextgen-gallery/
				if ( (!$this->required_version()) or (!$this->check_memory_limit()) or defined('TVR_MICROBOTH') ) {
					return;
				}

				// translatable: apparently one of the commented methods below is correct, but they don't work for me.
				// http://geertdedeckere.be/article/loading-wordpress-language-files-the-right-way
				// JOSE: $this->propertyoptions doesn't get translated if we use init
				load_plugin_textdomain( 'microthemer', false, dirname( plugin_basename(__FILE__) ) . '/languages/' );
				//add_action('init', array($this, 'tvr_load_textdomain'));


				add_action('wp_ajax_mtui', array(&$this, 'microthemer_ajax_actions'));

				// for media queries
				$this->unq_base = uniqid();

				// get lang for non-english exceptions (e.g. showing English property labels too)
				$this->locale = get_locale();

				$this->dis_text = __('DISABLED', 'microthemer');
				$this->level_map = array(
					'section' => __('folder', 'microthemer'),
					'selector' => __('selector', 'microthemer'),
					'tab' => __('tab', 'microthemer'),
					'tab-input' => __('tab', 'microthemer'),
					'group' => __('group', 'microthemer'),
					'pgtab' => __('styles', 'microthemer'),
					'subgroup' => __('styles', 'microthemer'),
					'property' => __('property', 'microthemer'),
					'script' => __('Enqueued Script', 'microthemer')
				);

				// add menu links (all WP admin pages need this)
				if (TVR_MICRO_VARIANT == 'themer') {
					add_action("admin_menu", array(&$this, "microthemer_dedicated_menu"));
				}
				else {
					add_action("admin_menu", array(&$this, "microloader_menu_link"));
				}

				// get the directory paths
				include dirname(__FILE__) .'/get-dir-paths.inc.php';

				// plugin update stuff
				add_filter( 'site_transient_update_plugins', array( $this, 'site_transient_update_plugins' ) );
				add_filter( 'plugins_api_result', array( &$this, 'plugins_api_result' ), 99, 3 );
				add_action( 'in_plugin_update_message-microthemer/' . $this->microthemeruipage,
                    array( &$this, 'plugin_update_message' ), 1, 2
                );


				/***
				limit the amount of code that runs on non-microthemer admin pages
				-- the main functions need to run for the sake of creating
				menu links to the plugin pages, but the code contained within is conditional.
				 ***/
				// save all plugin pages in an array for evaluation throughout the program
				$this->all_pages = array(
					$this->microthemeruipage,
					$this->microthemespage,
					$this->managesinglepage,
					$this->docspage,
					$this->fontspage,
					$this->preferencespage,
                    $this->detachedpreviewpage
				);
				$page = isset($_GET['page']) ? $_GET['page'] : false;

				// use quick method of getting preferences at this stage (maybe shift code around another time)
				$this->preferences = get_option($this->preferencesName);

				// compare if new version
                $this->new_version = (empty($this->preferences['version']) || $this->preferences['version'] != $this->version);

				// add shortcut to Microthemer if preference
				if ( !empty($this->preferences['admin_bar_shortcut']) ) {
					add_action( 'admin_bar_menu', array(&$this, 'custom_toolbar_link'), 999999);
				}

				// activation hook for setting initial preferences (so e.g. Microthemer link appears in top toolbar)
				register_activation_hook( __FILE__, array(&$this, 'microthemer_activated_or_updated') );

				// only initialize on plugin admin pages
				if ( is_admin() and in_array($page, $this->all_pages) ) {

				    // if it's a new version, run the activation/upgrade function (if not done at activation hook)
                    // this will update the translations in the JS cached HTML
                    // and ensures the pre-update settings are saved in the history table
                    if ($this->new_version && !$this->activation_function_ran){
                        $this->microthemer_activated_or_updated();
                    }

					// check if integratable plugins are active
					add_action( 'admin_init', array(&$this, 'check_integrations'));

				    // setup vars that depend on WP being fully loaded
					add_action( 'admin_init', array(&$this, 'setup_wp_dependent_vars'));

					// we don't want the WP admin bar on any Microthemer pages
					add_filter('show_admin_bar', '__return_false');

					/* this may need work, ocassionally breaks: http://stackoverflow.com/questions/5441784/why-does-ob-startob-gzhandler-break-this-website
					 * $this->show_me = 'zlib.output_compression config: ('
						. ini_get('zlib.output_compression')
						. ') gzipping HTTP_ACCEPT_ENCODING: (' . $_SERVER['HTTP_ACCEPT_ENCODING']
						. ') substr_count: ' . substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip');*/
					// only microthemer needs custom jQuery and gzipping

					// enable gzipping on UI page if defined
					if ( $_GET['page'] == basename(__FILE__) and $this->preferences['gzip'] == 1) {
						if (!empty($_SERVER['HTTP_ACCEPT_ENCODING']) and
                            substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip'))
							ob_start("ob_gzhandler");
						else
							ob_start();
					}

					// add scripts and styles
					// Not necessary if this is an ajax call. XDEBUG_PROFILE showed add_js was adding sig time.
					if ( empty($_GET['action']) or $_GET['action'] != 'mtui'){

						add_action('admin_init', array(&$this, 'add_no_cache_headers'), 1);
					    add_action('admin_init', array(&$this, 'add_css'));
                        add_action('admin_head', array(&$this, 'add_dyn_inline_css'));
                        add_action('admin_init', array(&$this, 'add_js'));

						// fix compatibility issues due to a plugin loading scripts or styles on MT interface pages
						add_action('admin_enqueue_scripts', array('tvr_common', 'dequeue_rogue_assets'), 1000);

					} else {
						//echo 'it is an ajax request';
					}

				}
			}

			function check_table_exists($table_name, $also_populated = false){

				global $wpdb;

				$exists = !empty(
				    $wpdb->get_var(
					    $wpdb->prepare("SHOW TABLES LIKE %s", $table_name)
				    )
				);

				if (!$exists || !$also_populated){
					return $exists;
				}

				$wpdb->query("SELECT id FROM $table_name");

				return $exists && $wpdb->num_rows > 0;
			}

			// user's subscription has expired and they are capped at a version
			function is_capped_version(){
			   return !empty($this->preferences['buyer_validated']) and !empty($this->preferences['subscription']['capped_version']);
            }

			// maybe sets 'Automatic update is unavailable for this plugin'
			function site_transient_update_plugins($transient){
			    if ($this->is_capped_version()){
				    global $pagenow;
				    $plugin = 'microthemer/' . $this->microthemeruipage;
				    if ( ('plugins.php' == $pagenow && is_multisite()) or empty($transient->response[$plugin]) ) {
					    return $transient;
				    }
				    $transient->response[$plugin]->package = false;
				    $transient->response[$plugin]->upgrade_notice = 'UPDATE UNAVAILABLE. Please renew your subscription to enable updates.';
                }
				return $transient;
            }

			// maybe removes download button from plugin details popup
            function plugins_api_result($res, $action, $args){
	            if ($this->is_capped_version()){
		            $res->download_link = false;
	            }
				return $res;
			}

			// prompts renewal & unlock if version is capped
            // thanks to Beaver Builder for lighting the way here: https://www.wpbeaverbuilder.com/
			function plugin_update_message($plugin_data, $response){

                if ( empty( $response->package ) ) {

                    $message  = '<span style="display:block;padding:10px 20px;margin:10px 0; background: #d54e21; color: #fff;">';
	                $message .= '<strong>' . __( 'UPDATE UNAVAILABLE!', 'microthemer' ) . '</strong>';
	                $message .= '&nbsp;&nbsp;&nbsp;';
	                $message .= 'Please renew your subscription to enable updates.';
	                $message .= ' ' . sprintf( '<a href="%s" target="_blank" style="color: #fff; text-decoration: underline;">%s </a>', admin_url( '/admin.php?page='.$this->microthemeruipage.'&launch_unlock=1'), __( 'Renew subscription', 'microthemer' ) );
	                $message .= '</span>';

	                echo  $message;
				}
			}

			// ensure preferences are set upon activation
			function microthemer_activated_or_updated(){

				$pd_context = 'microthemer_activated_or_updated';

				// setup program data arrays
                // calls getPreferences() which also sets if nothing to get yet
                // and creates a backup of the settings and preferences if a new version
				include dirname(__FILE__) .'/includes/program-data.php';

				// if non-english, we need to write to program-data.js in current language
				// log success of overwrite
				// (this didn't work properly on some servers, maybe @fopen suppress would work, but this is safer)
				$pref_array = array(
					'inlineJsProgData' => ( strpos($this->locale, 'en_') === false ) //!$this->write_mt_version_specific_js('../js-min')
				);

				$this->savePreferences($pref_array);


				/*if ( strpos($this->locale, 'en_') === false ){


				}*/

				// todo save all lang strings in DB at this point to save CPU later, start with property-options.inc.php

				// ensure micro-themes dir is created with PIE and animation-events.js
				$this->setup_micro_themes_dir(true);

				$this->activation_function_ran = true;

			}

			// add a link to the WP Toolbar (this was copied from frontend class - use better method later)
			function custom_toolbar_link($wp_admin_bar) {

			    if (!current_user_can('administrator')){
					return false;
				}

				if (!empty($this->preferences['top_level_shortcut'])){
					$parent = false;
				} else {
					$parent = 'site-name';
				}

				// root URL to MT UI
				$href = $this->wp_blog_admin_url . 'admin.php?page=' . $this->microthemeruipage;

				// if admin edit post or page - MT should load that page
				$front = $this->get_url_from_edit_screen();

				if ($front){

				    $href.= '&mt_preview_url=' . rawurlencode($front['url'])
					        . '&_wpnonce=' . wp_create_nonce( 'mt-preview-nonce' );

					// not sure how to make a post
				    if ($front['post_status'] === 'auto-draft'){
						$href.= '&auto_save_draft='.$front['postID'];
                    }

					//wp_die('<pre>'.print_r($front, true).'</pre>');
                }

				$args = array(
					'id' => 'wp-mcr-shortcut',
					'title' => 'Microthemer',
					'parent' => $parent,
					'href' => $href,
					'meta' => array(
						'class' => 'wp-mcr-shortcut',
						'title' => __('Jump to the Microthemer interface', 'microthemer')
					)
				);

				$wp_admin_bar->add_node($args);
			}

			function get_url_from_edit_screen(){

				global $post;

			    if ($post && function_exists('get_current_screen')) {

			        $current_screen = get_current_screen();
			        $post_type = $current_screen->post_type;
			        $isPostOrPage = ($post_type === 'post' || $post_type === 'page');
                    $isEditScreen = $isPostOrPage && isset($_GET['action']) && $_GET['action'] === 'edit'
                                    && !empty($_GET['post']);
				    $isAddScreen = $isPostOrPage && $current_screen->action === 'add';

				    //wp_die('<pre>'.print_r($post, true).'</pre>');

			        // if add new or saved draft use preview URL
				    if ($isAddScreen || $post->post_status !== 'publish'){

					    if ( $url = get_preview_post_link($post->ID) ){
						    return array(
                                'url' => $url,
                                'post_status' => $post->post_status,
                                'postID' => $post->ID
                            );
					    }
                    }

                    // get link for published post
                    else if ($isEditScreen){
					    if ( $url = get_permalink( intval($_GET['post']) ) ){
						    return array(
							    'url' => $url,
							    'post_status' => $post->post_status
						    );
					    }
                    }

					//wp_die('<pre>'.print_r(get_current_screen()->id, true).'</pre>');
				}

				return false;
            }

			function log_subscription_check(){

				$s = $this->preferences['subscription'];
				$checks = $this->preferences['subscription_checks'];
			    $pref_array['subscription_checks'] = $checks;
				$pref_array['subscription_checks']['num']++;

				// last try, max attempts reached
                // could add a condition for max 3 days after renewal date, but holding off for now
				if ($pref_array['subscription_checks']['num'] >= $pref_array['subscription_checks']['max']){
					$pref_array['subscription_checks']['stop_attempts'] = true;
					$this->savePreferences($pref_array);
					return 'subscription check failed';
                }

                // add some time before next check
                else {
				    $extra_seconds = 12 * 60 * 60; // 12 hours
	                //$extra_seconds = 10;

	                $inital_time = !empty($checks['next_time']) ? $checks['next_time'] : $this->time;
	                $pref_array['subscription_checks']['next_time'] =
                        $inital_time + ($pref_array['subscription_checks']['num'] * $extra_seconds);
	                $this->savePreferences($pref_array);
	                return 'defer';
                }

            }

			// check subscription if past renewal_check date
			function maybe_check_subscription(){

			    $p = $this->preferences;
			    $s = $p['subscription'];
			    $checks = $p['subscription_checks'];

				// Note: renewal_check is 2 days after their subscription expires (to safely allow for timezone diffs)
				$renewal_time = !empty($checks['next_time']) ? $checks['next_time'] : strtotime($s['renewal_check']);

				// remote check conditions
				$after_renewal_check = (!empty($s['renewal_check']) and $this->time > $renewal_time);
				$higher_than_capped = (!empty($s['capped_version']) and
                                       version_compare($s['capped_version'], $this->version) < 0);
				$retro_check_needed = empty($p['retro_sub_check_done']);

				// if subscription check needed
			    if (
			         ($after_renewal_check or $higher_than_capped or $retro_check_needed) and
                     !empty($p['buyer_email']) and
                     !empty($p['buyer_validated']) and
                     empty($checks['stop_attempts'])
                ){
			        //$this->show_me.= 'doing auto remote check';
				    // check if subscription is still active
                    $this->get_validation_response($p['buyer_email'], 'scheduled');
                } else {
				    //$this->show_me.= 'NOT doing auto remote check';
                }
            }



			function themeover_connection_url($email, $proxy = false){

			    $test_domain = false;

				$domain =  $test_domain ? $test_domain : $this->home_url;

				$base_url = $proxy
					? 'https://validate.themeover.com/'
					: 'https://themeover.com/wp-content/tvr-auto-update/validate.php';

				$params = 'email='.rawurlencode($email)
				          .'&domain='.$domain
				          .'&mt_version='.$this->version;

				return $base_url.'?'.$params;

			}


			/**
			 * Connect to themeover directly or via proxy fallback
			 *
			 * @param      $url
			 * @param $email
			 * @param bool $proxy
			 *
			 * @return false|string
			 */
			function connect_to_themeover($url, $email, $proxy = false){

				//$url = $this->themeover_connection_url($email, $proxy);
				$responseString = wp_remote_fopen($url);
				$response = json_decode($responseString, true);

				//$this->show_me.= 'The response from '. $url . ': '. $responseString;

				// if we have a valid result or we have already tried the fallback proxy script, return result
				if (!empty($response['message']) or $proxy){
					return $responseString;
				}

				// the initial connection was unsuccessful, possibly due to firewall rules, attempt proxy connection
				else {
					return $this->connect_to_themeover(
					        $this->themeover_connection_url($email, true), $email, true
                    );
				}

			}

			// check user can unlock / continue using MT
			function get_validation_response($email, $context = 'unlock'){

				$pref_array = array(
					'buyer_email' => $email
				);
				$was_capped_version = $this->is_capped_version();
				$response = false;
				$url = $this->themeover_connection_url($email);
				$responseString = $this->connect_to_themeover($url, $email);
				//$this->show_me.= $responseString;

				// accommodate new json response format
				if ( strpos($responseString, '{') !== false ){
					$response = json_decode($responseString, true);
					$validation = !empty($response['unlock']) ? $response['unlock'] : false;
				} else {
					// old response format - ha! this will never happen, only older versions of MT get old response format
					$validation = $responseString && strlen($responseString) < 2;
				}

				// if no valid response, check for http issue
				if (empty($response['message'])){
					$response_code = wp_remote_retrieve_response_code( wp_remote_get($url) );
					if ($response_code != 200){
						$response['message'] = 'connection error';
						if (empty($response_code) && !empty($responseString)){
							$response_code  = esc_html($responseString);
						}
					}

					$response['code'] = $response_code;

					// if scheduled subscription check, log num tries and bail if deferring
					if ($context == 'scheduled'){
						$response['message'] = $this->log_subscription_check();
						if ($response['message'] == 'defer'){
							return false;
						}
					}


				}

				// valid response format
				else {

					// save subscription response from server (includes renewal_check date)
					$pref_array['subscription'] = $response;

					// reset subscription checks if manual unlock attempted
					if ($context == 'unlock'){
						$pref_array['subscription_checks'] = $this->subscription_check_defaults;
					}

				}

				$this->change_unlock_status($context, $validation, $pref_array, $response, $was_capped_version);
			}


            function change_unlock_status($context, $validation, $pref_array, $response, $was_capped_version){

                // regardless of unlock/lock no further need for retrospectively checking their subscription renewal
	            $pref_array['retro_sub_check_done'] = 1;

	            /* validation success */
	            if ($validation) {

		            $pref_array['buyer_validated'] = 1;

		            if ($context == 'unlock'){
			            if (!$this->preferences['buyer_validated']) { // not already validated
				            $this->log(
					            esc_html__('Full program unlocked!', 'microthemer'),
					            '<p>' . esc_html__('Your email address has been successfully validated. Microthemer\'s full program features have been unlocked!', 'microthemer') . '</p>',
					            'notice'
				            );
			            } else {

				            if ($was_capped_version){
					            if (empty($response['capped_version'])){
						            $this->log(
							            esc_html__('Updates enabled', 'microthemer'),
							            '<p>' . esc_html__('You can now update Microthemer to the latest version.', 'microthemer') . '</p>',
							            'notice'
						            );
					            } else {
						            $this->log(
							            esc_html__('Version is still limited ', 'microthemer'),
							            '<p>' . esc_html__('Your subscription must be renewed on themeover.com to enable Microthemer updates.', 'microthemer') . '</p>',
							            'warning'
						            );
					            }
				            }

				            else {
					            $this->log(
						            esc_html__('Already validated', 'microthemer'),
						            '<p>' . esc_html__('Your email address has already been validated. The full program is currently active.', 'microthemer') . '</p>',
						            'notice'
					            );
				            }


			            }


		            }

	            }


	            /* validation fail */
	            else {

		            // do checks on why validation failed here and report to user
		            $pref_array['buyer_validated'] = 0;

		            // prevent future subscription checks as we're already in free trial mode
		            $pref_array['subscription']['renewal_check'] = false;

		            $explain = '';
		            $title_prefix = ($context == 'unlock') ? 'Unlock failed' : 'Notice';

		            // check for returned message to give clue about problem
		            if (!empty($response['message'])){

			            $title = $title_prefix . ' - ' . $response['message'];

			            switch ($response['message']) {

				            case "missing info":
					            $explain = "<p>The required unlock credentials were not provided.</p>";
					            break;

				            case "invalid credentials":
					            $explain = '<p>The unlock credentials were invalid. Make sure you are entering 
                                        the email address shown on 
                                        <a target="_blank" href="https://themeover.com/my-account/">My Downloads</a></p>';
					            break;

				            case "subscription expired":
					            $explain = '<p>Your subscription has expired. This means you can only  
                                        use Microthemer in free trial mode. To continue using Microthemer in 
                                        full capacity please renew or upgrade via  
                                        <a target="_blank" href="https://themeover.com/my-account/">My Downloads</a></p>';
					            break;

				            case "incorrect version":
					            $explain = '<p>Your expired subscription does not allow you to use this version 
                                        ('.$this->version.') of Microthemer. You are eligible to use version '
					                       .$response['capped_version'].', which you can download from  
                                        <a target="_blank" href="https://themeover.com/my-account/">My Downloads</a>. 
                                        You can also renew or upgrade your subscription from there if you want to 
                                        use this version of Microthemer.</p>';
					            break;

				            case "please upgrade":

					            $explain = '<p>Domain limit (3) reached. The standard license permits 
                                        installing Microthemer on 3 domains in total, not 3 domains at any one time.</p>';

					            // extra text if they have already exceeded their limit
					            if (count($response['domains']) > 3){
						            $explain.= '<p>We started enforcing this restriction after learning that a 
                                            few people have been unclear about the terms 
                                            of the standard license. No worries if this includes you.</p>';
					            }

					            $explain.= '<p><a class="tvr-button" target="_blank" 
                                        href="https://themeover.com/my-account/">Please upgrade 
                                        to a developer license</a></p>
                                        
                                        <h3>Domains you have installed Microthemer on</h3>';

					            // display domains
					            $domains = '';
					            foreach ($response['domains'] as $key => $arr){
						            $domains.= '
                                            <li>
                                                <span class="domain-name">' . $arr['domain'] . '</span>
                                                <span class="install-date">' . $arr['date'] . '</span>
                                            </li>';
					            }

					            $explain.= '<ol>' . $domains . '</ol>';

					            break;


				            case "connection error":
				            case "proxy connection error":
				            case "subscription check failed":
					            $code_message = !empty($response_code) ? 'HTTP response code: '.$response_code : '';
					            $explain = '<p>The connection to themeover.com was unsuccessful. 
                                '.$code_message.'</p>
                            
                                <p>The connection to Themeover\'s server may have failed due to an 
                                intermittent network error. Please ensure you are connected to the internet, 
                                if working from localhost. <span class="link show-dialog" 
                                rel="unlock-microthemer">Resubmitting your email one 
                                more time</span> may do the trick</p>
                                
                                <p>Or try <b>disabling any security plugins</b> that may be 
                                blocking Microthemer\'s outbound connection. You can re-enable them after you 
                                unlock Microthemer</p>
                                
                                <p>Finally, security settings on your server may block all outgoing PHP 
                                connections to domains not on a trusted whitelist (e.g. sites that are not 
                                wordpress.org). Ask your web host about temporarily unblocking themeover.com.</p>';
					            break;

			            }

		            }

		            // unknown error
		            else {
			            $title = $title_prefix;
			            $explain = '<p>Your email address could not be validated. Make sure you are entering 
                                 the email address shown on <a target="_blank" href="https://themeover.com/my-account/">
                                 My Downloads</a>. If you are still stuck,  
                                <a target="_blank" href="https://themeover.com/support/contact/">please contact 
                                support for assistance</a></p>';
		            }

		            $this->log($title, $explain);
	            }


	            if (!$this->savePreferences($pref_array)) {
		            $this->log(
			            esc_html__('Unlock status not saved', 'microthemer'),
			            '<p>' . esc_html__('Your validation status could not be saved. The program may need to be unlocked again.', 'microthemer') . '</p>'
		            );
	            }

	            return $pref_array['subscription'];
            }

            function invalidLic(){

			    $invalid = array(
                    'c9f79e3d1874f6cc044513ab5357c63d'
                );

			    $current = md5($this->preferences['buyer_email']);

			    if ( in_array($current, $invalid) ){

			        $pref_array = array(
					    'buyer_email' => '',
					    'buyer_validated' => 0,
					    'used_invalid' => 1,
                        'subscription' => array(
	                        'unlock' => false,
	                        'renewal_check' => false,
	                        'message' => ''
                        )
				    );

				    $this->savePreferences($pref_array);

				    return true;
                }

			    return false;
            }

			// set defaults for user's property preferences (this runs on every page load)
			function maybe_set_my_props_defaults(){

				$log = array(
				   'update2' => false
                );

				// for resetting during development
				/*$this->preferences['my_props'] = array();
				$this->preferences['default_sug_values_set'] = 0;*/

				foreach ($this->propertyoptions as $prop_group => $array){

				    foreach ($array as $prop => $meta) {

						// we're only interested in props with default units or suggested values
						if ( !isset($meta['default_unit']) and empty($meta['sug_values']) ){
							continue;
						}

						// ensure that the default unit is set, this will cater for new props too
						if (isset($meta['default_unit']) and
						    (!isset($this->preferences['my_props'][$prop_group]['pg_props'][$prop]['default_unit']) or
						     $this->preferences['my_props'][$prop_group]['pg_props'][$prop]['default_unit'] === 'px (implicit)') // legacy system default
                        ){
							$log['update2'] = true;
							$default_unit = $meta['default_unit']; //$this->is_time_prop($prop) ? 's' : 'px (implicit)';
							$this->preferences['my_props'][$prop_group]['pg_props'][$prop]['default_unit'] = $default_unit;
						}

						// ensure that the suggested values array is set, this will cater for new props too
						$log = $this->prepare_sug_values($log, $meta, $prop, '');
						$log = $this->prepare_sug_values($log, $meta, $prop, '_extra');
					}
				}

				// Save if changes were made to my_props
                if ($log['update2']){

	                $this->savePreferences(
	                    array(
                            'my_props' =>  $this->preferences['my_props'],
                            'units_added_to_suggestions' => $this->preferences['units_added_to_suggestions']
                        )
                    );

	                return true;
                }

				return false;
			}

			function unitCouldBeAdded($value, $unit){

				$isTimeUnit = ($unit === 's' || $unit === 'ms');

				return ( $isTimeUnit || ($value != 0 && $value !== '0') ) && is_numeric($value);
            }

			// Apply the default unit as set in MT to the suggestions (before we mostly had pixels and no unit)
            function ensureSuggestionsHaveUnits(&$suggestions, $meta){

                // if property supports units
				if (isset($meta['default_unit'])){

				    $factoryDefaultUnit = $meta['default_unit'];

				    foreach ($suggestions as $i => $value){

                        if ( $this->unitCouldBeAdded($value, $factoryDefaultUnit) ){
	                        $suggestions[$i] = $value . $factoryDefaultUnit;
                        }
					}
                }
            }

			function prepare_sug_values($log, $meta, $prop, $extra = ''){

			    if ( !empty($meta['sug_values'.$extra]) ){

					// empty arrays by default
					$recent = $sampled = $saved = array();

					// copy MT default suggestions to sug_values key
					$sug_by_default = true; // do show suggestions by default
					$copiedSrc = ($sug_by_default and !empty($meta['select_options'.$extra]))
						? $meta['select_options'.$extra]
						: array();
					$this->ensureSuggestionsHaveUnits($copiedSrc, $meta);

					// note, this system allows EITHER root cat (only used for color) or prop
					$root_cat = !empty($meta['sug_values'.$extra]['root_cat'])
						? $meta['sug_values'.$extra]['root_cat']
						: $prop;

					/* New structure conversion - if root_cat is simple numerical array  */
					if (isset($this->preferences['my_props']['sug_values'][$root_cat]) and
					    (!count($this->preferences['my_props']['sug_values'][$root_cat]) or
					     is_int(key($this->preferences['my_props']['sug_values'][$root_cat])))
					){

						if ($root_cat == 'color') {

							// we only need to grab color once
							if (!empty($log['color_done'])){
								return $log;
							}

							$recent = $this->preferences['my_props']['sug_values'][$root_cat];
							if ( isset($this->preferences['my_props']['sug_values']['site_colors']) ){
								$sampled = $this->preferences['my_props']['sug_values']['site_colors'];
								unset($this->preferences['my_props']['sug_values']['site_colors']);
							} if ( isset($this->preferences['my_props']['sug_values']['saved_colors']) ){
								$saved = $this->preferences['my_props']['sug_values']['saved_colors'];
								unset($this->preferences['my_props']['sug_values']['saved_colors']);
							}

							$log['color_done'] = true;

						}

						// prepare arrays
						$this->preferences['my_props']['sug_values'][$root_cat] = array(
							'recent' => $recent,
							'sampled' => $sampled,
							'saved' => $saved,
							'copiedSrc' => $copiedSrc
						);

						$log['update2'] = true;
					}
					/* End new structure conversion*/

					// no conversion necessary
					else {

						// set root array if not already
						if (!isset($this->preferences['my_props']['sug_values'][$root_cat]) ){

							$this->preferences['my_props']['sug_values'][$root_cat] = array(
								'recent' => $recent,
								'sampled' => $sampled,
								'saved' => $saved,
								'copiedSrc' => $copiedSrc
							);

							// color will be set now, and doesn't need conversion true above
							if ($root_cat === 'color'){
								$log['color_done'] = true;
							}

							$log['update2'] = true;
						}

						// the root array is set
						else {

							// set copiedSrc if not already
						    if (!isset($this->preferences['my_props']['sug_values'][$root_cat]['copiedSrc']) ){
								$this->preferences['my_props']['sug_values'][$root_cat]['copiedSrc'] = $copiedSrc;
								$log['update2'] = true;
							}

							// ensure units have been explicitly added (this came later)
							if ( empty($this->preferences['units_added_to_suggestions']) ){

								$sug_values = $this->preferences['my_props']['sug_values'][$root_cat];

								foreach($sug_values as $sug_key => $suggestions){
									if ($sug_key !== 'sampled' and count($suggestions)){

										$this->ensureSuggestionsHaveUnits(
											$this->preferences['my_props']['sug_values'][$root_cat][$sug_key], $meta
                                        );
									}
								}

								$this->preferences['units_added_to_suggestions'] = 1;
								$log['update2'] = true;
							}
                        }
					}
				}

				return $log;
            }


			// check if an url is valid
			function is_valid_url( $url ) {
				if ( '' != $url ) {
					/* Using a HEAD request, we'll be able to know if the URL actually exists.
					 * the reason we're not using a GET request is because it might take (much) longer. */
					$response = wp_remote_head( $url, array( 'timeout' => 3 ) );
					/* We'll match these status codes against the HTTP response. */
					$accepted_status_codes = array( 200, 301, 302 );

					/* If no error occured and the status code matches one of the above, go on... */
					if ( ! is_wp_error( $response ) &&
					     in_array( wp_remote_retrieve_response_code( $response ), $accepted_status_codes ) ) {
						/* Target URL exists. Let's return the (working) URL */
						return $url;
					}
					/* If we have reached this point, it means that either the HEAD request didn't work or that the URL
					 * doesn't exist. This is a fallback so we don't show the malformed URL */
					return '';
				}
				return $url;
			}




			// load full set of suggested CSS units
			function update_my_prop_default_units($new_css_units){

				$first_in_group_val = '';

                foreach ($this->preferences['my_props'] as $prop_group => $array){

					if ($prop_group == 'sug_values') continue;

					foreach ($this->preferences['my_props'][$prop_group]['pg_props'] as $prop => $arr){

					    // skip props with no default unit
						if (!isset($this->propertyoptions[$prop_group][$prop]['default_unit'])
                            or $this->is_non_length_unit($this->propertyoptions[$prop_group][$prop]['default_unit'], $prop)
                        ){
							continue;
						}

						// get unit
						$new_unit = isset($new_css_units[$prop_group][$prop])
                            ? $new_css_units[$prop_group][$prop]
                            : '';

						// correct for line-height
						if ($new_unit == 'none'){
							$new_unit = '';
                        }

						// set all related the same
						$box_model_rel = false;

						if (!empty($this->propertyoptions[$prop_group][$prop]['rel'])){
							$box_model_rel = $this->propertyoptions[$prop_group][$prop]['rel'];
						} elseif (!empty($this->propertyoptions[$prop_group][$prop]['unit_rel'])){
							$box_model_rel = $this->propertyoptions[$prop_group][$prop]['unit_rel'];
						}

						/*if (!empty($this->propertyoptions[$prop_group][$prop]['sub_label'])){
							$first_in_group_val = $new_unit;
						}*/
						if (!empty($this->propertyoptions[$prop_group][$prop]['unit_sub_label'])){
							$first_in_group_val = $new_unit;
						} elseif (!empty($this->propertyoptions[$prop_group][$prop]['sub_label'])){
							$first_in_group_val = $new_unit;
						}

						if ($box_model_rel){
							$new_unit = $first_in_group_val;
						}

						$this->preferences['my_props'][$prop_group]['pg_props'][$prop]['default_unit'] = $new_unit;

					}
				}
				return $this->preferences['my_props'];
			}

			// ensure all preferences are defined
			function ensure_defined_preferences($full_preferences, $pd_context){

				// copy previous preferences for history backup
				$this->pre_update_preferences = $this->preferences;

				// backup previous version settings as special history entry if new version
                if ($this->new_version && $pd_context == 'microthemer_activated_or_updated'){
	                $this->pre_upgrade_backup();
                }

			    // check if all preference are defined
			    $pref_array = array();
			    $update = false;
			    foreach ($full_preferences as $key => $value){
					if (!isset($this->preferences[$key])){
						$pref_array[$key] = $value;
						$update = true;
					}
				}

				// save new preference definitions if found
				if ($update) {
					$this->savePreferences($pref_array);
                }

				// new CSS props will be added over time and the default unit etc must be assigned.
				$this->maybe_set_my_props_defaults();

				// convert user's non_section config to modern format with meta holding little values
                // meta always gets sent in ajax save
				$this->maybe_do_data_conversions_for_update();

				// ensure view_import_stylesheets list has current theme style.css (saves preferences too)
				$this->update_css_import_urls(get_stylesheet_directory_uri() . '/style.css', 'ensure');
			}

			// create a backup of the user's settings in history and as a design pack
			function pre_upgrade_backup(){

				// no need to backup if no settings have been saved
				global $wpdb;
				$this->maybeCreateOrUpdateRevsTable(); // only creates table if doesn't exist or needs updating
				$wpdb->get_results("select id from ".$wpdb->prefix . "micro_revisions");


				$previous_version = !empty($this->preferences['previous_version'])
                    ? $this->preferences['previous_version']
                    : 'Previous version';

				if ($wpdb->num_rows > 0){

					// add settings before update to revision table, include preferences in this special revision.
					if (!$this->updateRevisions(
						$this->options,
						$this->json_format_ua(
							'display-preferences lg-icon',
							esc_html__($previous_version.' settings (before updating to '.$this->version.')',
                                'microthemer')
						),
						true, // otherwise error on new installs
						$this->preferences, //$backup_preferences,
                        true
					)) {
						$this->log('','','error', 'revisions');
					}

                    // clean any pre-update packs MT created when it using that system
                    $this->clean_pre_upgrade_backup_packs();

					// export the old settings too, to ensure history doesn't get wiped
                    // no, history will suffice as pre_upgrade only gets cleared when another upgrade happens
                    // also this was creating mulitple packs as $alt_name was preventing overwrite
					//$this->update_json_file('Pre-upgrade backup settings', 'new', true, $this->preferences);
				}

			}

		    // clean any pre-update packs MT created when it using that system
			function clean_pre_upgrade_backup_packs(){

			    $pattern = '/pre-upgrade-backup-settings(?:-\d)?/';

			    // loop packs
				foreach ($this->file_structure as $dir => $array) {

					// delete matches
				    if (preg_match($pattern, $dir)) {
						$this->tvr_delete_micro_theme($dir);
					}
				}
            }


			// run data structure updates
			function maybe_do_data_conversions_for_update(){

				// a few minor data format changes were made for the speed version. This runs once.
			    if (empty($this->preferences['speed_conversion_done'])){

					// create backup
					//$this->pre_upgrade_backup(); - this happens on every update

					$non_section = &$this->options['non_section'];
					$keys = array(
						'adv_wizard_focus', 'css_focus', 'device_focus', // just pref now
						'last_save_time' // move to meta
					);

					// remove keys that were hack for non-queued settings save
					foreach ($keys as $key){
						$item = &$this->get_or_update_item($non_section, array('trail' => array($key), 'action' => 'get'));
						//$this->show_me.= '<pre>key '.$key. ' $item: '.$item.'</pre>';
						if ($item){

							// move certain key values to meta
							if ($key === 'last_save_time'){
								$this->get_or_update_item($non_section, array(
									'action' => 'append',
									'trail' => array('meta'),
									'key' => $key,
									'data' => $item
								));
							}

							unset($non_section[$key]);
						}
					}

					// we don't need to track view state outside of regular sel
					if (!empty($non_section['view_state'])){
						unset($non_section['view_state']);
					}

					// we only use active_queries for import/revision restore now
					if (!empty($non_section['active_queries'])){
						unset($non_section['active_queries']);
					}

					// remove recent sug for background_image and list_style_image which will be basename - invalid
					$image_props = array('list_style_image', 'background_image', 'url');
					$types = array('recent', 'copiedSrc');
					foreach ($image_props as $image_prop){
						foreach ($types as $type){
							$this->get_or_update_item(
								$this->preferences['my_props']['sug_values'],
								array(
									'trail' => array($image_prop, $type),
									'action' => 'replace',
									'data' => array()
								)
							);
						}

					}

					// update preferences
					$this->savePreferences(
						array(
							'speed_conversion_done' => true,
							'my_props' =>  $this->preferences['my_props']
						)
					);

					// update DB
					update_option($this->optionsName, $this->options);

					//$this->show_me.= '<pre>modified non_section what '.$this->options['css_focus'].'</pre>';

				}

				// transition to more solid system for connecting MT tabs with page builder device views
				if (empty($this->preferences['builder_site_preview_width_conversion_done'])){

					$m_queries = $this->preferences['m_queries'];

					$map = array(
					     "bb1" => "builder.FLBuilder.medium",
                         "bb2" => "builder.FLBuilder.small",
                         "elem2" => "builder.elementor.tablet",
					     "elem3" => "builder.elementor.mobile",
                         "oxy_page_width" => "builder.oxygen.page-width",
					     "oxy_tablet" => "builder.oxygen.tablet",
					     "oxy_phone_landscape" => "builder.oxygen.phone-landscape",
					     "oxy_phone_portrait" => "builder.oxygen.phone-portrait",
					);

					// remove keys that were hack for non-queued settings save
					foreach ($m_queries as $mq_key => $m_query){

						foreach ($map as $key_suffix => $site_preview_width){

						    if ( preg_match('/'.$key_suffix.'$/', $mq_key) ){
								$m_queries[$mq_key]['site_preview_width'] = $site_preview_width;
							}
						}
					}

					/*wp_die('Old: <pre>$media_queries_list: '.print_r($this->preferences['m_queries'], true). '</pre>'
					. 'New: <pre>$media_queries_list: '.print_r($m_queries, true). '</pre>');*/

					// update preferences
					$this->savePreferences(
						array(
							'builder_site_preview_width_conversion_done' => true,
                            'm_queries' => $m_queries
						)
					);

				}

				// there were some errors with recently viewed pages being badly formatted
                // including an Oxygen issue that could cause data loss, so reset custom_paths if not done already
				if (empty($this->preferences['custom_paths_reset'])){
					$this->savePreferences(
						array(
							'custom_paths_reset' => 1,
							'custom_paths' =>  array('/')
						)
					);
                }

			}

			// manually override user preferences here after code changes
			function maybe_manually_override_preferences(){

				$update_prefs = false;
				if (!empty($this->preferences['pseudo_classes']) and count($this->preferences['pseudo_classes'])){
					$this->preferences['pseudo_classes'] = array();
					$update_prefs = true;
				} if (!empty($this->preferences['pseudo_elements']) and count($this->preferences['pseudo_elements'])){
					$this->preferences['pseudo_elements'] = array();
					$update_prefs = true;
				}

				// we released 5 beta with system for remembering targeting mode on page load,
				// but decided against this, have this hard set for a while to fix for beta testers
				$this->preferences['hover_inspect'] = 0; // simple fix

				if ($update_prefs){
					$this->savePreferences($this->preferences);
				}
			}

			// update viewed_import_stylesheets list array
			function update_css_import_urls($url, $context = 'make top'){

				// if url is already in the array, ensure it's at the top
				$curKey = array_search($url, $this->preferences['viewed_import_stylesheets']);
				if ($context == 'make top'){
					if ($curKey !== false){
						array_splice( $this->preferences['viewed_import_stylesheets'], $curKey, 1 );
					}
					array_unshift( $this->preferences['viewed_import_stylesheets'], $url );
				}

				// unless we're just ensuring e.g. the theme's style.css is in the list
                elseif ($context == 'ensure'){
					if ( !in_array($url, $this->preferences['viewed_import_stylesheets']) ){
						$this->preferences['viewed_import_stylesheets'][] = $url;
					}
				}

				// ensure only 20 items
				$i = 0;
				$pref_array['viewed_import_stylesheets'] = array();
				foreach ($this->preferences['viewed_import_stylesheets'] as $key => $css_url){
					if (++$i > 20) break;
					$pref_array['viewed_import_stylesheets'][] = $css_url;
				}

				//$pref_array['viewed_import_stylesheets'] = array();
				$this->savePreferences($pref_array);
			}


			// @taken from ngg gallery: http://wordpress.org/extend/plugins/nextgen-gallery/
			function required_version() {
				global $wp_version;
				$this->users_wp_version = $wp_version;
				// Check for WP version installation
				$wp_ok = version_compare($wp_version, $this->minimum_wordpress, '>=');
				// if requirements not met
				if ( ($wp_ok == FALSE) ) {
					add_action(
						'admin_notices',
						create_function(
							'',
							'echo \'<div id="message" class="error"><p><strong>' .
							esc_html__('Sorry, Microthemer only runs on WordPress version %s or above. Deactivate Microthemer to remove this message.', 'microthemer') .
							'</strong></p></div>\';'
						)
					);
					return false;
				}
				return true;
			}

			// check the user has a minimal amount of memory
			function check_memory_limit() {

				// get memory limit including unit
				$subject = ini_get('memory_limit'); // e.g. 256M
				//$subject = 268435456; //test
				$pattern = '/([\-0-9]+)/';
				preg_match($pattern, $subject, $matches);
				$this->memory_limit = $matches[0];
				$unit = str_replace($matches[0], '', $subject);

				/* if we have enough return true
				if ($this->memory_limit == 0 or
					$this->memory_limit == -1 or
					$unit == 'G' or
					$unit == 'GB' or
					( ($unit == 'M' or $unit == 'MB') and $this->memory_limit > 16)
				){
					return true;
				}*/

				// cautious memory check that will only throw error if memory is given in MB.
				// Too many variables to safely accommodate all e.g. 0, -1, (int) 268435456, 3GB etc
				if ( ($unit == 'M' or $unit == 'MB') and $this->memory_limit < 16 ){
					// we don't have enough
					add_action(
						'admin_notices',
						create_function(
							'',
							'echo \'<div id="message" class="error"><p><strong>' .
							esc_html__('Sorry, Microthemer has a memory requirement of 16MB or higher to run. Your allocated memory is less than this ('.$subject.'). Deactivate Microthemer to remove this message. Or increase your memory limit.', 'microthemer') .
							'</strong></p></div>\';'
						)
					);
					return false;
				}

				// all good
				return true;
			}

			// Microthemer dedicated menu
			function microthemer_dedicated_menu() {

				// for draft mode and preventing two users overwriting each other's edits
				// get_current_user_id() needs to be here (hooked function)
				$this->current_user_id = get_current_user_id();

				add_menu_page(__('Microthemer UI', 'microthemer'), 'Microthemer', 'administrator', $this->microthemeruipage, array(&$this,'microthemer_ui_page'));
				add_submenu_page('options.php',
					__('Manage Design Packs', 'microthemer'),
					__('Manage Packs', 'microthemer'),
					'administrator', $this->microthemespage, array(&$this,'manage_micro_themes_page'));
				add_submenu_page('options.php',
					__('Manage Single Design Pack', 'microthemer'),
					__('Manage Single Pack', 'microthemer'),
					'administrator', $this->managesinglepage, array(&$this,'manage_single_page'));
				add_submenu_page('options.php',
					__('Microthemer Docs', 'microthemer'),
					__('Documentation', 'microthemer'),
					'administrator', $this->docspage, array(&$this,'microthemer_docs_page'));
				add_submenu_page('options.php',
					__('Microthemer Fonts', 'microthemer'),
					__('Fonts', 'microthemer'),
					'administrator', $this->fontspage, array(&$this,'microthemer_fonts_page'));
				add_submenu_page('options.php',
					__('Microthemer Detached Preview', 'microthemer'),
					__('Detached Preview', 'microthemer'),
					'administrator', $this->detachedpreviewpage, array(&$this,'microthemer_detached_preview_page'));
				add_submenu_page($this->microthemeruipage,
					__('Microthemer Preferences', 'microthemer'),
					__('Preferences', 'microthemer'),
					'administrator', $this->preferencespage, array(&$this,'microthemer_preferences_page'));
			}

			// Add Microloader menu link in appearance menu
			function microloader_menu_link() {
				add_theme_page('Microloader', 'Microloader', 'administrator', $this->microthemespage, array(&$this,'manage_micro_themes_page'));
			}

			// add support for translation (not using this function right now, rightly or wrongly)
			function tvr_load_textdomain() {
				load_plugin_textdomain('microthemer', FALSE, dirname(plugin_basename(__FILE__)).'/languages/');
			}

			// add js
			function add_js() {
				if (TVR_MICRO_VARIANT == 'themer') {

					if (!$this->optimisation_test){
						wp_enqueue_media(); // adds over 1000 lines of code to footer
					}

					// Run pre-wordPress 5.6 jQuery and jQuery UI (temp fix for users with sites that still have issues)
					$runLegacyJquery = !empty($this->preferences['wp55_jquery_version']);

					// jQuery UI scripts
					$jqueryUIScripts = array(
						'core',
						'widget',
						'mouse',
						'sortable',
						'position',
						'slider',
						'menu',
						'autocomplete',
						'button',
						'tooltip',
						'draggable',
						'resizable',
					);

					// core jQuery and migrate script
					$jquery_scripts = array(
						array(
							'h' => 'jquery',
							'alwaysInc' => 1,
							'dequeue' => $runLegacyJquery,
							'f' => $runLegacyJquery
								? '../js-min/legacy-jquery/jquery.js'
								: false,
							//'footer' => true
						),
						array(
							'h' => 'jquery-migrate',
							'dep' => 'jquery',
							'alwaysInc' => 1,
							'dequeue' => $runLegacyJquery,
							'f' => ($runLegacyJquery)
								? '../js-min/legacy-jquery/jquery-migrate-1.4.1-wp.js'
								: false,
							//'footer' => true
						)
					);

					$prevScript = false;
					foreach ($jqueryUIScripts as $jqi){

						$jqueryUIDeps = array('jquery', 'jquery-migrate');
						if (!empty($prevScript)){
							$jqueryUIDeps[] = 'jquery-ui-core';
							$jqueryUIDeps[] = 'jquery-ui-'.$prevScript;
						}

						$jquery_scripts[] = array(
							'h' => 'jquery-ui-'.$jqi,
							'dep' => $jqueryUIDeps,
							'alwaysInc' => 1,
							'dequeue' => $runLegacyJquery,
							'f' => $runLegacyJquery
								? '../js-min/legacy-jquery/jquery-ui/'.$jqi.'.min.js'
								: false,
							//'footer' => false

						);

						$prevScript = $jqi;
					}

					// script map
					$mt_scripts = array(

						//array('h' => 'jquery', 'alwaysInc' => 1),

						// WP 5.5 removed the migrate helper, which caused some issues for the autocomplete menu
						// e.g. clearing a single suggestion also selected the cleared item, thus returning it to the suggestions
						//array('h' => 'mt-jquery-migrate', 'alwaysInc' => 1, 'f' => '../js-min/jquery-migrate-1.4.1-wp.js'),

						// jquery/ui
						/*array('h' => 'jquery-ui-core', 'dep' => 'jquery', 'alwaysInc' => 1),
						array('h' => 'jquery-ui-position', 'dep' => 'jquery', 'alwaysInc' => 1),
						array('h' => 'jquery-ui-sortable', 'dep' => 'jquery', 'alwaysInc' => 1),
						array('h' => 'jquery-ui-slider', 'dep' => 'jquery', 'alwaysInc' => 1),
						array('h' => 'jquery-ui-menu', 'dep' => 'jquery', 'alwaysInc' => 1),
						array('h' => 'jquery-ui-autocomplete', 'dep' => 'jquery', 'alwaysInc' => 1),
						array('h' => 'jquery-ui-button', 'dep' => 'jquery', 'alwaysInc' => 1),
						array('h' => 'jquery-ui-tooltip', 'dep' => 'jquery', 'alwaysInc' => 1),

						// essential for gridstack
						array('h' => 'jquery-ui-widget', 'dep' => 'jquery', 'alwaysInc' => 1),
						array('h' => 'jquery-ui-mouse', 'dep' => 'jquery', 'alwaysInc' => 1),
						array('h' => 'jquery-ui-draggable', 'dep' => 'jquery', 'alwaysInc' => 1),
						array('h' => 'jquery-ui-resizable', 'dep' => 'jquery', 'alwaysInc' => 1),*/


						// mt core namespace
						array('h' => 'tvr_core', 'f' => 'mt-core.js'),

						// js libraries (prefix name with mt- if I've edited the library)
						// use ace2, ace4 and have /ace as sub dir for easy globs in gulp file
						array('h' => 'tvr_ace', 'f' => 'lib/ace4/ace/ace.js'),
						array('h' => 'tvr_ace_lang', 'f' => 'lib/ace4/ace/ext-language_tools.js'),
						array('h' => 'tvr_ace_search', 'f' => 'lib/ace4/ace/ext-searchbox.js'),
						array('h' => 'tvr_gsap', 'f' => 'lib/gsap/gsap.min.js'),
						/*array('h' => 'tvr_widget', 'f' => '../src/js/mt-widget.js'),
						array('h' => 'tvr_transform', 'f' => '../src/js/mt-transform.js'),*/
						array('h' => 'tvr_gridstack', 'f' => 'lib/gridstack/gridstack.js'),
						array('h' => 'tvr_gridstack_ui', 'f' => 'lib/gridstack/gridstack.jQueryUI.js'),
						array('h' => 'tvr_extend_regexp', 'f' => 'lib/extend-native-regexp.js'),
						array('h' => 'tvr_mcth_colorbox', 'f' => 'lib/colorbox/1.6.4/jquery.colorbox-min.js'),
						array('h' => 'tvr_spectrum', 'f' => 'lib/colorpicker/mt-spectrum.js', 'dep' => array( 'jquery' )),

						// https://github.com/beautify-web/js-beautify
						array('h' => 'tvr_html_beautify', 'f' => 'lib/beautify-html.min.js'),
						array('h' => 'tvr_sprintf', 'f' => 'lib/sprintf/sprintf.min.js'),
						array('h' => 'tvr_parser', 'f' => 'lib/parser.js'),
						//array('h' => 'tvr_ast_query', 'f' => 'lib/query-ast.js'), // doesn't play well with gonz
						array('h' => 'tvr_scss_parser', 'f' => 'lib/gonzales.js'),
						array('h' => 'tvr_cssutilities', 'f' => 'lib/mt-cssutilities.js'),
						//array('h' => 'tvr_cssutilities', 'f' => 'lib/CSSUtilities.js'), // for comparing customised

						// try out a new sortable library as jquery seems buggy when there are lots of selectors
						array('h' => 'tvr_sortable', 'f' => 'lib/sortable/mt-sortable.js'),

						// custom modules
						array('h' => 'tvr_mcth_cssprops', 'f' => 'data/program-data.js'), // this will be dyn soon
						array('h' => 'tvr_utilities', 'f' => 'mod/mt-utilities.js'),
						array('h' => 'tvr_widget', 'f' => 'mod/mt-widget.js'),
						//array('h' => 'tvr_transform', 'f' => '../src/js/mt-transform.js'),
						array('h' => 'tvr_init', 'f' => 'mod/mt-init.js'),
						array('h' => 'tvr_mod_ace', 'f' => 'mod/mt-ace.js'),
						array('h' => 'tvr_mod_integrate', 'f' => 'mod/mt-integrate.js'),
						array('h' => 'tvr_mod_loop', 'f' => 'mod/mt-loop.js'),
						array('h' => 'tvr_mod_dd', 'f' => 'mod/mt-dom-data.js'),
						array('h' => 'tvr_mod_save', 'f' => 'mod/mt-save.js'),
						array('h' => 'tvr_mod_sass', 'f' => 'mod/mt-sass.js'),
						array('h' => 'tvr_mod_grid', 'f' => 'mod/mt-grid.js'),
						array('h' => 'tvr_mod_local', 'f' => 'mod/mt-local.js',
						      'page' => array(
							      $this->microthemeruipage,
							      $this->detachedpreviewpage
						      )),

						// page specific (non-min)
						array('h' => 'tvr_main_ui', 'f' => 'page/microthemer.js', 'page' => array($this->microthemeruipage)),
						array('h' => 'tvr_man', 'f' => 'page/packs.js', 'page' => 'other'),
						array('h' => 'tvr_fonts', 'f' => 'page/fonts.js', 'page' => array($this->fontspage)),
						array('h' => 'tvr_detached', 'f' => 'page/detached-preview.js', 'page' => array($this->detachedpreviewpage)),

						// min (deps.js combines all libraries and includes
						// apart from ace that didn't concat well with it's web workers for some reason
						array('h' => 'tvr_ace', 'f' => '../js-min/ace/ace.js', 'min' => 1),
						array('h' => 'tvr_ace_lang', 'f' => '../js-min/ace/ext-language_tools.js', 'min' => 1),
						array('h' => 'tvr_ace_search', 'f' => '../js-min/ace/ext-searchbox.js', 'min' => 1),

						// page specific (min)
						array('h' => 'tvr_sassjs', 'f' => '../js-min/sass/sass.js', 'alwaysInc' => 1, 'ifSASS'),
						array('h' => 'tvr_deps', 'f' => '../js-min/deps.js', 'min' => 1),
						array('h' => 'tvr_mcth_cssprops', 'f' => '../js-min/program-data.js', 'min' => 1,
						      'skipScript' => !empty($this->preferences['inlineJsProgData']) ),
						array('h' => 'tvr_main_ui', 'f' => '../js-min/microthemer.js', 'min' => 1,
						      'page' => array($this->microthemeruipage)),
						array('h' => 'tvr_man', 'f' => '../js-min/packs.js', 'min' => 1, 'page' => 'other'),
						array('h' => 'tvr_fonts', 'f' => '../js-min/fonts.js', 'min' => 1, 'page' => array($this->fontspage)),
						array('h' => 'tvr_detached', 'f' => '../js-min/detached-preview.js', 'min' => 1, 'page' => array($this->detachedpreviewpage)),

					);

					// combine jQuery and other scripts
					$scripts = array_merge($jquery_scripts, $mt_scripts);
					//$scripts = $mt_scripts;

					//wp_die('<pre>'.print_r($scripts, true).'</pre>');

					// output scripts based on various conditions
					$js_path = $this->thispluginurl.'js/';
					$v = '?v='.$this->version;
					foreach ($scripts as $key => $arr){
						$dep = !empty($arr['dep']) ? $arr['dep'] : false;
						$do_script = true;

						// filter out page specific scripts
						if (!empty($arr['page'])){
							if ( is_array($arr['page']) and !in_array($_GET['page'], $arr['page'])){
								$do_script = false;
							}
							if ($arr['page'] == 'other' and
							    ($_GET['page'] == $this->microthemeruipage or
							     $_GET['page'] == $this->fontspage or
							     $_GET['page'] == $this->detachedpreviewpage
							    )){
								$do_script = false;
							}
						}

						// only show correct script for dev/production
						if ( empty($arr['alwaysInc']) ) {
							if ((TVR_DEV_MODE and !empty($arr['min']))
							    or (!TVR_DEV_MODE and empty($arr['min']))
							    or !empty($arr['skipScript'])
							){
								$do_script = false;
							}
						}

						// always inc - check condition
						else {

							// skip sass.js if not enabled
							if ( $arr['alwaysInc'] === 'ifSASS' && !$this->preferences['allow_scss']){
								$do_script = false;
							}
						}

						// register/enqueue
						if ($do_script){
							if (!empty($arr['dequeue'])){
								//wp_dequeue_script( $arr['h'] );
								wp_scripts()->remove( $arr['h'] );
							}
							if (!empty($arr['f'])){
								wp_register_script( $arr['h'], $js_path . $arr['f']. $v, $dep );
							}
							wp_enqueue_script( $arr['h'], '', $dep, $v, !empty($arr['footer']));
						}
					}


/*// for article
$jqueryUIScripts = array(
    'core',
    'widget',
    'mouse',
    'sortable',
    'menu',
    'autocomplete',
);

$prevScript = false;

foreach ($jqueryUIScripts as $scriptName){

    $jqueryUIDeps = array('jquery', 'jquery-migrate');

    if (!empty($prevScript)){
        $jqueryUIDeps[] = 'jquery-ui-core';
        $jqueryUIDeps[] = 'jquery-ui-'.$prevScript;
    }

    wp_scripts()->remove( $scriptName );

    wp_enqueue_script(
        $scriptName, '/wp-content/themes/my-theme/js/temp-fix/'.$scriptName.'.js',
        $jqueryUIDeps
    );

    $prevScript = $scriptName;
}*/


					// load js strings for translation
					include_once $this->thisplugindir . 'includes/js-i18n.inc.php';

				}
			}

			// initiate vars that are wp dependent
			function setup_wp_dependent_vars(){

				// ajax url - requires wp_create_nonce()
				$this->wp_ajax_url = $this->wp_blog_admin_url . 'admin-ajax.php' . '?action=mtui&mcth_simple_ajax=1&page='.$this->microthemeruipage.'&_wpnonce='.wp_create_nonce('mcth_simple_ajax');

				$pd_context = 'setup_wp_dependent_vars';

				// setup program data arrays (program data default MQs are dependent on which builder is active)
				include dirname(__FILE__) .'/includes/program-data.php';

				// Write Microthemer version specific array data to JS file (can be static for each version).
				// This can be done in dev mode only (also, some servers don't like creating JS files)
				if (TVR_DEV_MODE){ // temp disable and false
					$this->write_mt_version_specific_js();
				}

				//$this->get_site_pages(); // for debug

			}

			// check_integrations (on the admin side)
			function check_integrations(){

				$check = array(
					'FLBuilder' => 'bb-plugin/fl-builder.php',
					'FLBuilder_lite' => 'beaver-builder-lite-version/fl-builder.php',
					'elementor' => 'elementor/elementor.php',
					'oxygen' => 'oxygen/functions.php'
				);

				// set config
				foreach ($check as $key => $plugin){
					if ( is_plugin_active( $plugin ) ) {

					    // two versions of BB, try using same key
						$key = ($key === 'FLBuilder_lite') ? 'FLBuilder' : $key;

						$this->integrations[$key] = 1;
					}
				}

				// if BB, provide way to load a BB breakpoint set
				if ( !empty($this->integrations['FLBuilder']) ){

					$bb_global = get_option('_fl_builder_settings');
					$small = !empty($bb_global->responsive_breakpoint)
						? $bb_global->responsive_breakpoint : 768;
					$medium = !empty($bb_global->medium_breakpoint)
						? $bb_global->medium_breakpoint : 992;

					// append BB media query option to
					$this->bb_mqs = array(
						$this->unq_base.'bb1' => array(
							"label" => __('BB Medium', 'microthemer'),
							"query" => "@media (max-width: {$medium}px)",
							"site_preview_width" => "builder.FLBuilder.medium"
						),
						$this->unq_base.'bb2' => array(
							"label" => __('BB Small', 'microthemer'),
							"query" => "@media (max-width: {$small}px)",
							"site_preview_width" => "builder.FLBuilder.small"
						),
					);

					$this->concat_builder_sync_options($this->bb_mqs);

					$this->mq_sets[esc_html__('Beaver Builder MQs', 'microthemer')] = $this->bb_mqs;
				}

				// if Elementor, provide way to load an Elementorbreakpoint set
				if ( !empty($this->integrations['elementor']) ){

					if (method_exists('Elementor\Core\Responsive\Responsive', 'get_breakpoints')){

					    $this->elementor_breakpoints = Elementor\Core\Responsive\Responsive::get_breakpoints();

						// elementor breakpoints to not match the preview screen. They are calculated from lg and md minus 1.
					    $tablet_max = $this->elementor_breakpoints['lg'] - 1;
						$mobile_max = $this->elementor_breakpoints['md'] - 1;

						// Elementor media query option
						$this->elementor_mqs = array(
							 /*// Not using Desktop label as Elmentor has that for full width. Yet 1025 is a custom setting.
							 $this->unq_base.'elem1' => array(
								"label" => __('Max: '.$breakpoints['lg'], 'microthemer'),
								"query" => "@media (max-width: {$breakpoints['lg']}px)",
							),*/
							$this->unq_base.'elem2' => array(
								"label" => __('Elementor Tablet', 'microthemer'),
								"query" => "@media (max-width: {$tablet_max}px)",
								"site_preview_width" => "builder.elementor.tablet"
							),
							$this->unq_base.'elem3' => array(
								"label" => __('Elementor Mobile', 'microthemer'),
								"query" => "@media (max-width: {$mobile_max}px)",
								"site_preview_width" => "builder.elementor.mobile"
							),

						);

						$this->concat_builder_sync_options($this->elementor_mqs);

						$this->mq_sets[esc_html__('Elementor MQs', 'microthemer')] = $this->elementor_mqs;
					}


				}

				// get oxygen breakpoints $media_queries_list (global)
				/*global $media_queries_list_above, $media_queries_list;
$this->show_me = '<pre>$media_queries_list: '.print_r($media_queries_list, true). '</pre>' .
				 '<pre>$media_queries_list_above: '.print_r($media_queries_list_above, true). '</pre>';*/

				// if Oxygen, provide way to load an Oxygen breakpoint set
				if ( !empty($this->integrations['oxygen']) ){

				    global $media_queries_list, $media_queries_list_above;

					// get a copy of Oxygen global media query array so we don't update global instance
                    // when setting maxSize on page-width key using $global_page_width
                    // this is unset because oxygen allows different page widths
                    // but MT doesn't support dynamic MQ tabs
				    $mq_copy = $this->array_clone($media_queries_list);
					$mq_above_copy = $this->array_clone($media_queries_list_above);

					if ( isset($mq_copy) && function_exists('oxygen_vsb_get_page_width') ){

						$global_page_width = oxygen_vsb_get_page_width(true);
						$mq_copy['page-width']['maxSize'] = $global_page_width.'px';
						$tablet_max = $mq_copy['tablet']['maxSize'];
						$phone_landscape_max = $mq_copy['phone-landscape']['maxSize'];
						$phone_portrait_max = $mq_copy['phone-portrait']['maxSize'];
						$page_container_label = __('Page container', 'microthemer');
						$and_below_label = __('and below', 'microthemer');

						// Oxygen media query option
						$this->oxygen_mqs = array(

							$this->unq_base.'_oxy_page_width' => array(
								"label" => $page_container_label, //.' ('.$global_page_width.'px) '.$and_below_label,
								"query" => "@media (max-width: {$global_page_width}px)",
                                "site_preview_width" => "builder.oxygen.page-width"
							),
							$this->unq_base.'_oxy_tablet' => array(
								"label" => $mq_copy['tablet']['title'],
								"query" => "@media (max-width: {$tablet_max})",
								"site_preview_width" => "builder.oxygen.tablet"
							),
							$this->unq_base.'_oxy_phone_landscape' => array(
								"label" => $mq_copy['phone-landscape']['title'],
								"query" => "@media (max-width: {$phone_landscape_max})",
								"site_preview_width" => "builder.oxygen.phone-landscape"
							),
							$this->unq_base.'_oxy_phone_portrait' => array(
								"label" => $mq_copy['phone-portrait']['title'],
								"query" => "@media (max-width: {$phone_portrait_max})",
								"site_preview_width" => "builder.oxygen.phone-portrait"
							),

						);

						$this->concat_builder_sync_options($this->oxygen_mqs);

						// make MQ set available
						$this->mq_sets[esc_html__('Oxygen MQs', 'microthemer')] = $this->oxygen_mqs;

						// save breakpoint data we might need later
						$this->oxygen_breakpoints = $mq_copy;

						$this->show_me = '';
					}


				}


			}

			function concat_builder_sync_options($mqs){

			    foreach ($mqs as $array){
				    $this->builder_sync_tabs[] = $array['site_preview_width'];
                }
            }

			// clone an array - useful if we want to edit global array only locally
			function array_clone($array) {
				return array_map(function($element) {
					return ((is_array($element))
						? $this->array_clone($element)
						: ((is_object($element))
							? clone $element
							: $element
						)
					);
				}, $array);
			}

			// output meta tags to prevent browser back/forward cache from generating
			// false positive multiple tabs warning
			function add_no_cache_headers(){
				header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
				header("Cache-Control: post-check=0, pre-check=0", false);
				header("Pragma: no-cache");
            }

			function add_dyn_inline_css(){

                // color variables
				if (!empty($this->preferences['mt_color_variables_css'])){

				    $ruleSet = '.sp-container, .tvr-input-wrap { '.strip_tags($this->preferences['mt_color_variables_css']).' }';

                    echo '<style id="mt_color_variables">'.$ruleSet.'</style>';
				}

            }

			// add css
			function add_css() {

				// Google fonts
				wp_register_style('tvrGFonts', '//fonts.googleapis.com/css?family=Open+Sans:400,700,700italic,400italic');
				wp_enqueue_style( 'tvrGFonts');

				// if dev mode, enqueue css libraries separately
				if (TVR_DEV_MODE){

					// color picker, colorbox, jquery ui styling
					wp_enqueue_style( 'spectrum-colorpicker',
						$this->thispluginurl . 'js/lib/colorpicker/spectrum.css?v=' . $this->version );
					wp_register_style( 'tvr_mcth_colorbox_styles',
						$this->thispluginurl.'css/colorbox.css?v='.$this->version );
					wp_enqueue_style( 'tvr_mcth_colorbox_styles' );
					wp_register_style( 'tvr_jqui_styles', $this->thispluginurl.'css/jquery-ui1.11.4.css?v='.$this->version );
					wp_enqueue_style( 'tvr_jqui_styles' );

					$main_css_file = $this->thispluginurl.'css/styles.css';
					$fonts_css_file = 'fonts.css';

				} else {

					//wp_register_style( 'tvr_mcth_colorbox_styles',
					//$this->thispluginurl.'js/lib/colorbox/1.3.19/colorbox.css?v='.$this->version );
					//wp_enqueue_style( 'tvr_mcth_colorbox_styles' );

					// in production, all css will be minified and concatenated to style.css
					$main_css_file = $this->thispluginurl.'css/concat-styles.min.css';
					$fonts_css_file = 'fonts.min.css';
				}

				// enqueue main stylesheet
				wp_register_style( 'tvr_mcth_styles', $main_css_file.'?v='.$this->version );
				wp_enqueue_style( 'tvr_mcth_styles' );

				// extra styles for fonts page
				if ($_GET['page'] === $this->fontspage){
					wp_register_style( 'tvr_font_styles', $this->thispluginurl.'css/'.$fonts_css_file.'?v='.$this->version );
					wp_enqueue_style( 'tvr_font_styles' );
				}

				// preferences page doesn't want toolbar hack, so add to stylesheet conditionally
				if ($_GET['page'] != $this->preferencespage){
					$custom_css = "
						html, html.wp-toolbar {
							padding-top:0
						}";
					wp_add_inline_style( 'tvr_mcth_styles', $custom_css );
				}

			}

			// build array for property/value input fields
			function getPropertyOptions() {
				$propertyOptions = array();
				$legacy_groups = array();
				$this->animatable = array(

					array(
						'label' => 'all',
						'category' => ''
					),
					array(
						'label' => 'none',
						'category' => ''
					)
				);
				include $this->thisplugindir . 'includes/property-options.inc.php';
				$this->propertyoptions = $propertyOptions;

				// populate $property_option_groups, $auto_convert_map, and animatable array
				foreach ($propertyOptions as $prop_group => $array){
					foreach ($array as $prop => $meta) {
						// pg group
						if ( !empty($meta['pg_label']) ){
							$pg_label = $meta['pg_label'];
							$this->property_option_groups[$prop_group] = $pg_label;
						}
						// auto convert
						if ( !empty($meta['auto']) ){
							$this->auto_convert_map[$prop] = $meta['auto'];
						}
						// animatable properties
						if ( !empty($meta['animatable']) ){
							$cssf = str_replace('_', '-', $prop);

							// adjust for shorthand
							if ($cssf == 'text-shadow-x'){
								$cssf = 'text-shadow';
							} else if ($cssf == 'box-shadow-x'){
								$cssf = 'box-shadow';
							}

							// include extra shorthand
							else if ($cssf == 'padding-top'){
								$this->animatable[] = array(
									'label' => 'padding',
									'category' => $pg_label
								);
							} else if ($cssf == 'margin-top'){
								$this->animatable[] = array(
									'label' => 'margin',
									'category' => $pg_label
								);
							} else if ($cssf == 'border-top-color'){
								$this->animatable[] = array(
									'label' => 'border-color',
									'category' => $pg_label
								);
							} else if ($cssf == 'border-top-width'){
								$this->animatable[] = array(
									'label' => 'border-width',
									'category' => $pg_label
								);
							} else if ($cssf == 'flex-grow'){
								$this->animatable[] = array(
									'label' => 'flex',
									'category' => $pg_label
								);
							}

							$this->animatable[] = array(
								'label' => $cssf,
								'category' => $pg_label
							);
						}
					}
				}
				$this->legacy_groups = $legacy_groups;

			}

			// output prefixes for a given property or value
			function css_prefixes($property_group_name, $property_slug, $property, $value, $tab, $sty){

				$prefixes = '';
				$before = $tab."	";
				$after = $sty['css_important'].";\n";
				$propArr = $this->propertyoptions[$property_group_name][$property_slug];

				// if there are prefixes
				if ( !empty( $propArr['prefixes'] ) ){

					// if there are property prefixes
					if ( !empty( $propArr['prefixes']['property'] ) ){
						foreach ( $propArr['prefixes']['property'] as $prefixed_property ){
							$prefixes.= $before . $prefixed_property .': ' . $value . $after;
						}
					}

					// if there are value prefixes
					if ( !empty( $propArr['prefixes']['values'][$value] ) ){
						foreach ( $propArr['prefixes']['values'][$value] as $prefixed_value ){
							$prefixes.= $before . $property .': ' . $prefixed_value . $after;
						}
					}
				}

				return $prefixes;
			}

			// update shorthand map array
			function update_shorthand_map($shorthand, $cssf, $prop_group, $prop, $propArr, $sh) {
				//$this->show_me.= print_r($sh[0], true);
				$shorthand[$sh[0]][$cssf] = array(
					'group' => $prop_group,
					'prop' => $prop,
					'index' => $sh[1],
					'config' => !empty($sh[2]) ? $sh[2] : array()
				);
				// signal if prop is !important carrier
				!empty($propArr['important_carrier']) ? $shorthand[$sh[0]][$cssf]['important_carrier'] = 1 : 0;
				// signal if prop can have multiple
				!empty($propArr['multiple']) ? $shorthand[$sh[0]][$cssf]['multiple'] = 1 : 0;
				// and if MT supports this
				!empty($propArr['multiple_sup']) ? $shorthand[$sh[0]][$cssf]['multiple_sup'] = 1 : 0;

				return $shorthand;
			}

			// update the array for checking match criteria when gathering interesting css values from site stylesheets
			function update_gc_css_match($gc_css_match, $type, $val){
				$gc_css_match[] = array(
					'type' => $type,
					'val' => $val
				);
				return $gc_css_match;
			}

			// create static JS file for property options etc that relate to the current version of MT
			function write_mt_version_specific_js($dir = 'js/data', $inline = false) {

				$write_file = '';

				if (!$inline){

					// Create new file if it doesn't already exist
					$js_file = $this->thisplugindir . $dir . '/program-data.js';
					$write_file = @fopen($js_file, 'w');

					if (!$write_file) {
						$this->log(
							esc_html__('Permission Error', 'microthemer'),
							'<p>' . sprintf( esc_html__('WordPress does not have permission to update: %s', 'microthemer'), $this->root_rel($js_file) . '. '.$this->permissionshelp ) . '</p>'
						);

						return 0;
					}
				}

				// some CSS properties need adjustment for jQuery .css() call
				// include any prop that needs special treatment for one reason or another
				$exceptions = array(
					'display' => array('display-flex'),
					'font-family' => 'google-font',
					'grid-template-areas' => 'grid-template-areas-add',
					'list-style-image' => 'list-style-image',
					'text-shadow' => array(
						'text-shadow-x',
						'text-shadow-y',
						'text-shadow-blur',
						'text-shadow-color'),
					'box-shadow' => array(
						'box-shadow-x',
						'box-shadow-y',
						'box-shadow-blur',
						'box-shadow-spread',
						'box-shadow-color',
						'box-shadow-inset'),
					'background-img-full' => array(
						'background-image',
						'gradient-angle',
						'gradient-a',
						'gradient-b',
						'gradient-b-pos',
						'gradient-c'
					),
					'background-position' => 'background-position',
					'background-position-custom' => array(
						'background-position-x',
						'background-position-y'
					),
					'background-repeat' => 'background-repeat',
					'background-attachment' => 'background-attachment',
					'background-size' => 'background-size',
					'background-clip' => 'background-clip',
					'border-top-left-radius' => 'radius-top-left',
					'border-top-right-radius' => 'radius-top-right',
					'border-bottom-right-radius' => 'radius-bottom-right',
					'border-bottom-left-radius' =>'radius-bottom-left',

					'keys' => array(
						'background-position-x' => array(
							'0%' => 'left',
							'100%' => 'right',
							'50%' => 'center'
						),
						'background-position-y' => array(
							'0%' => 'top',
							'100%' => 'bottom',
							'50%' => 'center'
						),
						'gradient-angle' => array(
							'180deg' => 'top to bottom',
							'0deg' => 'bottom to top',
							'90deg' => 'left to right',
							'-90deg' => 'right to left',
							'135deg' => 'top left to bottom right',
							'-45deg' => 'bottom right to top left',
							'-135deg' => 'top right to bottom left',
							'45deg' => 'bottom left to top right'
						),
						// webkit has a different interpretation of the degrees - doh!
						'webkit-gradient-angle' => array(
							'-90deg' => 'top to bottom',
							'90deg' => 'bottom to top',
							'0deg' => 'left to right',
							'180deg' => 'right to left',
							'-45deg' => 'top left to bottom right',
							'135deg' => 'bottom right to top left',
							'-135deg' => 'top right to bottom left',
							'45deg' => 'bottom left to top right'
						)
					)
				);


				$this->propAliases = array(

					'display-flex' => 'display',

					// properties that should go to grid prop in format cssf-grid
					'display-grid' => 'display',
					'justify-items-grid' => 'justify-items',
					'justify-content-grid' => 'justify-content',
					//'justify-self-grid' => 'justify-self', // use grid as default group for this
					'align-items-grid' => 'align-items',
					'align-content-grid' => 'align-content',
					'align-self-grid' => 'align-self',
					'order-grid' => 'order',
					'z-index-grid' => 'z-index',

					// caution order an z-index appear twice here!
					// this has implications for resolve_repeated_property_group (OK for now)

					// properties that should go to grid all fields
					'width-gridall' => 'width',
					'height-gridall' => 'height',
					'grid-area-gridall' => 'grid-area',
					'order-gridall' => 'order',
					'z-index-gridall' => 'z-index'

				);


				$this->cssFuncAliases = array(

					// transform css functions
					'rotate' => 'rotatez', // rotate does the same thing as rotateZ

					// filter css functions
					'opacity-function' => 'opacity',
				);

				// var for storing then writing json data to JS file
				$data = '';

				// shorthand properties in this array (like padding, font etc) also have longhand single props.
				// At the JS end, these single props can be got from tapping the browser's comp CSS
				// unlike only shorthand props in the $exceptions array above.
				$shorthand = array();

				// I should have left space for this in the $shorthand array, I will never learn
				$shorthand_prefixes = array();

				// longhand for checking against regular css properties
				// Also a general deposit for property data we want JavaScript to have access to
				$longhand = array();

				// And object for storing the subgroup keys e.g. just padding, rather than padding and margin
				$sub_group_keys = array();

				// also need to map subgroups to groups so pg_disabed[padding] will load group options
				$sub_group_to_group = array();

				// temporary reference map/storage for style values from site's stylesheets e.g. color palette
				// certain styles are saved to my_props
				$gathered_css = array(
					'eligable' => array(),
					'store' => array(
						//'site_colors' => array(),
						//'saved_colors' => array(),
					),
					'root_cat_keys' => array(),
				);

				// combo array for storing data for comboboxes
				$combo = array();

				// css props for passing jQuery.css() to get computed CSS
				$css_props = array();

				// we need a collection of props for pg tabs
				$pg_tab_props = array();

				// for mapping a pgtab control to a group
				$pg_tab_map = array();

				$unsupported_css_func_map = array(
					'matrix' => 'transform', // unsupported shorthand matrices that cannot be reliably converted to longhand functions
					'matrix3d' => 'transform',
					'rotate3d' => 'transform'
				);

				// for mapping css functions to the shorthand prop
				$css_func_map = array(
					'rotate' => 'transform', // alias for rotateZ
				);

				$sub_slug = '';

				// loop through property options, creating various JS key map arrays
				foreach ($this->propertyoptions as $prop_group => $array) {

					foreach ($array as $prop => $propArr) {

						// new sub group, update and save reference to array
						if (!empty($propArr['sub_slug'])){
							$sub_slug = $propArr['sub_slug'];
							$sub_group_keys[$sub_slug] = array();
							$sub_group_array = &$sub_group_keys[$sub_slug];

							$sub_group_to_group[$sub_slug] = $prop_group;
						}

						// store prop in sub_array
						$sub_group_array[] = $prop;

						// we loop the group props in a specified order so the CSS props are in the same place, so we need group too
						if ($sub_slug !== $prop_group){
							$sub_group_keys[$prop_group][] = $prop;
						}

						// this could be replaced with hardcoded values in property-options.inc.php
						$cssf = str_replace('_', '-', $prop);
						$css_props[$prop_group][] = array(
							'prop' => $prop,
							'cssf' => $cssf
						);

						// update tab control array and grid items map
						if (!empty($propArr['tab_control'])){

							// we're setting the valid syntax here, I think that makes sense as property group is included
							$valid_syntax = !empty($this->propAliases[$cssf])
								? $this->propAliases[$cssf]
								: $cssf;

							$pg_tab_props[$prop_group][$propArr['tab_control']][$prop] = $valid_syntax;
							$pg_tab_map[$propArr['tab_control']] = $prop_group;
						}

						// update shorthand map
						if (!empty($propArr['sh'])){
							// like with border, border-top, and border-color shorthands affecting 1 prop
							if (is_array($propArr['sh'][0])){
								foreach($propArr['sh'] as $n => $sub_sh){

									$shorthand = $this->update_shorthand_map($shorthand, $cssf, $prop_group,
										$prop, $propArr, $propArr['sh'][$n]);

									$shorthand_property = $propArr['sh'][$n][0];

									// also update $gathered_css while we're here
									if (!empty($propArr['sug_values'])){
										$gathered_css['eligable'][$shorthand_property] = 1;
									}

									if (isset($propArr['css_func'])){
										$css_func_map[$shorthand_property] = $prop_group;
									}

								}
							} else {
								// prop with just one shorthand available
								$shorthand = $this->update_shorthand_map($shorthand, $cssf, $prop_group,
									$prop, $propArr, $propArr['sh']);

								$shorthand_property = $propArr['sh'][0];

								// also update $gathered_css while we're here
								if (!empty($propArr['sug_values'])){
									$gathered_css['eligable'][$shorthand_property] = 1;
								}

								if (isset($propArr['css_func'])){
									$css_func_map[$shorthand_property] = $prop_group;
								}

								// update any shorthand prefixes
								if (!empty($propArr['sh'][2]['prefixes'])){
									$shorthand_prefixes[$shorthand_property]['prefixes'] = $propArr['sh'][2]['prefixes'];
								}

							}
						}

						// update longhand map (even with onlyShort)
						//if (empty($propArr['sh'][2]['onlyShort'])){
						$longhand[$cssf] = array(
							'group' => $prop_group,
							'prop' => $prop,
							//'multiple' => !empty($propArr['multiple']) ? 1 : 0,
						);

						// include any vendor prefixes for property
						!empty($propArr['prefixes'])
							? $longhand[$cssf]['prefixes'] = $propArr['prefixes']
							: false;

						// signal if prop can have multiple
						!empty($propArr['multiple']) ? $longhand[$cssf]['multiple'] = 1 : false;

						// signal MT factory default unit as unitless suggestions are based on that
						// the factory default can be used to convert suggested values based on the user's unit choice
						isset($propArr['default_unit'])
							? $longhand[$cssf]['fdu'] = $propArr['default_unit']
							: false;

						// signal if property has special units
						!empty($propArr['special_units'])
							? $longhand[$cssf]['special_units'] = $propArr['special_units']
							: false;

						// css function like rotateX or rotate3d for transform or filter
						if (isset($propArr['css_func'])){
							$longhand[$cssf]['css_func'] = $propArr['css_func'];
							$css_func_map[$prop] = $prop_group;
						}

						// and if MT supports multiple
						!empty($propArr['multiple_sup']) ? $longhand[$cssf]['multiple_sup'] = 1 : 0;

						// attach tab_control data to prop
						!empty($propArr['tab_control'])
							? $longhand[$cssf]['tab_control'] = $propArr['tab_control']
							: false;

						// and attach shorthand so we can check this when resampling page for suggested styles
						if (!empty($propArr['sh'])){
							$longhand[$cssf]['sh'] = $propArr['sh'];
							// also put shorthand prefixes in longhand
						}


						// get sub_slug for checking disabled via JS (among things perhaps)
						$longhand[$cssf]['sub_slug'] = $sub_slug;

						// get combobox type for edge_mode (temp)
						//!empty($propArr['type']) ? $longhand[$cssf]['type'] = $propArr['type'] : 0;

						// get sug_values config for forcing recent / suggestions etc
						!empty($propArr['sug_values']) ? $longhand[$cssf]['sug_values'] = $propArr['sug_values'] : 0;

						//}

						// update the $gathered_css map
						if (!empty($propArr['sug_values'])){

							// straight property match e.g. font-size
							if (!empty($propArr['sug_values']['this'])){
								$gathered_css['eligable'][$cssf] = 1;
							}

							// populate $gathered_css keys and storage arrays ready for getting vals with JS
							$gc_root_cat = !empty($propArr['sug_values']['root_cat'])
								? $propArr['sug_values']['root_cat']
								: $prop;

							$gathered_css['root_cat_keys'][$prop] = $gc_root_cat;
							$gathered_css['store'][$gc_root_cat] = array();

							// create store for e.g. grid line names too
							if (!empty($propArr['sug_values_extra'])){
								$gc_root_cat_extra = $propArr['sug_values_extra']['root_cat'];
								$gathered_css['root_cat_keys'][$prop.'_extra'] = $gc_root_cat_extra;
								$gathered_css['store'][$gc_root_cat_extra] = array();
							}

						}

						// populate combobox array
						if (!empty($array[$prop]['select_options'])){
							$combo[$prop] = $array[$prop]['select_options'];
						} if (!empty($array[$prop]['select_options_extra'])){
							$combo[$prop.'_extra'] = $array[$prop]['select_options_extra'];
						}

						// exceptions for more complicated select items with categories
						else {

							// event options
							if ($prop == 'font_family'){
								$combo[$prop] = $this->system_fonts;
							}

							// get preset animations from include file
                            elseif ($prop == 'animation_name'){
								$animation_names = array();
								include $this->thisplugindir . 'includes/animation/animation-code.inc.php';
								$combo[$prop] = $animation_names;
							}

							// event options
                            elseif ($prop == 'event'){
								$combo[$prop] = $this->browser_events;
							}

							// animatable properties
                            elseif ($prop == 'transition_property'){
								$combo[$prop] = $this->animatable;
							}

						}


					}
				}

				//$this->show_me.= print_r($combo, true);

				$this->shorthand = $shorthand;
				$this->longhand = $longhand;

				// text/box-shadow need to be called as one
				$css_props['shadow'][] = array(
					'prop' => 'text_shadow',
					'cssf' => 'text-shadow'
				);
				$css_props['shadow'][] = array(
					'prop' => 'box_shadow',
					'cssf' => 'box-shadow'
				);
				$css_props['background'][] = array(
					'prop' => 'background_img_full',
					'cssf' => 'background-img-full'
				);
				// for storing full string (inc gradient)
				$css_props['background'][] = array(
					'prop' => 'extracted_gradient',
					'cssf' => 'extracted-gradient'
				);
				// for storing just gradient (for mixed-comp check)
				$css_props['gradient'][] = array(
					'prop' => 'background_image',
					'cssf' => 'background-image'
				);
				// gradient group needs this
				$css_props['gradient'][] = array(
					'prop' => 'background_img_full',
					'cssf' => 'background-img-full'
				);
				// for storing full string (inc gradient)
				$css_props['gradient'][] = array(
					'prop' => 'extracted_gradient',
					'cssf' => 'extracted-gradient'
				);

				// dev option for showing function times
				$combo['show_total_times'] = array('avg_time', 'total_time', 'calls');

				// set options for :lang(language) pseudo class
				$combo['lang_codes'] = $this->country_codes;

				// suggest some handy nth formulas
				$combo['nth_formulas'] = $this->nth_formulas;

				// ready combo for css_units
				$length_units = array();
				$unit_types = $this->lang['css_unit_types'];
				$length_units[$unit_types['none']] = $this->css_units[$unit_types['none']];
				$length_units[$unit_types['common']] = $this->css_units[$unit_types['common']];
				$length_units[$unit_types['other']] = $this->css_units[$unit_types['other']];
				$combo['css_length_units'] = $this->to_autocomplete_arr($length_units);




				// num history saves
				$combo['num_history_points'] = array(
					'50', '75', '100', '150', '200', '300'
				);

				$combo['builder_sync_tabs'] = array_merge(
					array(
						'360', '568', '800'
					),
					$this->builder_sync_tabs
				);

				// example scripts for enqueuing
				$combo['enq_js'] = array( 'jquery', 'jquery-form', 'jquery-color', 'jquery-masonry', 'masonry', 'jquery-ui-core', 'jquery-ui-widget', 'jquery-ui-accordion', 'jquery-ui-autocomplete', 'jquery-ui-button', 'jquery-ui-datepicker', 'jquery-ui-dialog', 'jquery-ui-draggable', 'jquery-ui-droppable', 'jquery-ui-menu', 'jquery-ui-mouse', 'jquery-ui-position', 'jquery-ui-progressbar', 'jquery-ui-selectable', 'jquery-ui-resizable', 'jquery-ui-selectmenu', 'jquery-ui-sortable', 'jquery-ui-slider', 'jquery-ui-spinner', 'jquery-ui-tooltip', 'jquery-ui-tabs', 'jquery-effects-core', 'jquery-effects-blind', 'jquery-effects-bounce', 'jquery-effects-clip', 'jquery-effects-drop', 'jquery-effects-explode', 'jquery-effects-fade', 'jquery-effects-fold', 'jquery-effects-highlight', 'jquery-effects-pulsate', 'jquery-effects-scale', 'jquery-effects-shake', 'jquery-effects-slide', 'jquery-effects-transfer', 'wp-mediaelement', 'schedule', 'suggest', 'thickbox', 'hoverIntent', 'jquery-hotkeys', 'sack', 'quicktags', 'iris', 'json2', 'plupload', 'plupload-all', 'plupload-html4', 'plupload-html5', 'plupload-flash', 'plupload-silverlight', 'underscore', 'backbone' );
				sort($combo['enq_js']);


				// the full program data in one array
				$prog = array(
					'CSSUnits' =>  $this->css_units,
					'browser_event_keys' =>  $this->browser_event_keys,
					'propExc' =>  $exceptions,
					'propAliases' =>  $this->propAliases,
					'cssFuncAliases' =>  $this->cssFuncAliases,
					'subGroupKeys' =>  $sub_group_keys,
					'subToGroup' =>  $sub_group_to_group,
					'sh' =>  $shorthand,
					'sh_prefixes' =>  $shorthand_prefixes,
					'lh' =>  $longhand,
					'gatheredCSS' =>  $gathered_css,
					'CSSProps' =>  $css_props,
					'PGs' =>  $this->property_option_groups,
					'autoMap' =>  $this->auto_convert_map,
					'combo' =>  $combo,
					'mobPreview' =>  $this->mob_preview,
					'CSSFilters' =>  $this->css_filters,
					'custom_code_flat' =>  $this->custom_code_flat,
					'pg_tab_props' =>  $pg_tab_props,
					'pg_tab_map' =>  $pg_tab_map,
					'css_func_map' =>  $css_func_map,
					'unsupported_css_func_map' =>  $unsupported_css_func_map,
					'params_to_strip' =>  $this->params_to_strip,
				);

				$data.= 'TvrMT.data.prog = ' . json_encode($prog) . ';' . "\n\n";
				$data.= 'TvrMT.data.templates = ' . json_encode($this->html_templates()) . ';' . "\n\n";

				// if the server can't overwrite an external JS file, add it inline with other dynamic JS
				if ($inline){
					return $data;
				}

				else {
					fwrite($write_file, $data);
					fclose($write_file);
				}

				return 1;

			}

			// HTML TEMPLATES
			function html_templates(){

			    // full set of templates
			    $templates = array(

                     'notice' =>
                         '<div id="notice-template" class="tvr-message tvr-template-notice tvr-warning test-class">
                            <span class="mt-notice-icon"></span>
                            <span class="mt-notice-text"></span>
                         </div>',

                     'loaders' => $this->loaders_template(),

                     'log_item' => $this->log_item_template(),

                     'enq_js_menu_item' => $this->dyn_item($this->enq_js_structure, 'item', array('display_name' => 'item')),

                     'mqs_menu_item' => $this->dyn_item($this->mq_structure, 'item', array('label' => 'item')),

                     'sectionM' => $this->menu_section_html('selector_section', 'section_label'),

                     'section' => $this->section_html('selector_section', array()),

                     'selectorM' => $this->menu_selector_html('selector_section', 'selector_css', array('selector_code', 'selector_label'), 1),

                     'selector' => $this->single_selector_html('selector_section', 'selector_css', '', true),

                     'property_groups' => $this->property_group_templates(),

                     'icon_inputs' => $this->icon_inputs(),

                     'add_selector_form' => '',

                     'edit_selector_form' => '',

                     'input_wraps' => $this->input_wrap_templates


                );

			    return $templates; //$this->strip_tabs_and_line_breaks($templates);

			    //$templates = $this->strip_tabs_and_line_breaks($templates);

				// print templates to JS object
				//$data = 'TvrMT.data.templates = ' . $this->strip_tabs_and_line_breaks(json_encode($templates)) . ';' . "\n\n";

			    //return $data;
            }

            function strip_tabs_and_line_breaks($string){
			    // this does not work - not sure why.
			    //preg_replace('/[\\t\\r\\n\n\t\r]+/g', "", $string);
	            return $string;
            }

            function icon_inputs(){

			    return array(
			        'section' => array(
				        'disabled'  => $this->icon_control(true, 'disabled', true, 'section', 'selector_section')
                    ),
			        'selector' => array(
				        'disabled'  => $this->icon_control(true, 'disabled', true, 'selector', 'selector_section',
                            'selector_css')
                    ),
			        'tab-input' => array(
				        'disabled'  => $this->icon_control(true, 'disabled', true, 'tab-input', 'selector_section',
					        'selector_css', 'all-devices')
			        ),
			        'group' => array(
				        'pgtab_disabled'  => $this->icon_control(true, 'disabled', true,
                            'group', 'selector_section', 'selector_css',
                            'all-devices', 'group_slug', 'subgroup_slug', 'property_slug',
                            'tvr_mcth', 'pgtab_slug'),

				        'disabled'  => $this->icon_control(true, 'disabled', true, 'group', 'selector_section',
					        'selector_css', 'all-devices', 'group_slug', 'subgroup_slug'),

                        'flexitem'  => $this->icon_control(true, 'flexitem', true, 'group',
                            'selector_section','selector_css', 'all-devices', 'group_slug'),

                        'griditem'  => $this->icon_control(true, 'griditem', true, 'group',
					        'selector_section','selector_css', 'all-devices', 'group_slug'),

                        'nth_option' => '<li class="nth-item-option nth-item-option-0" data-nth="0">
                            <input class="nth-item-radio" type="radio" name="name_placeholder" value="0" />
                            <span class="fake-radio nth-radio-control"></span>
                            <span class="nth-item-label">0</span>
                        </li>'
			        ),
			        'subgroup' => array(
				        'chained'  => $this->icon_control(true, 'chained', true, 'subgroup', 'selector_section',
					        'selector_css', 'all-devices', 'group_slug', 'subgroup_slug')
			        ),
			        'property' => array(
				        'important'  => $this->icon_control(true, 'important', true, 'group', 'selector_section',
					        'selector_css', 'all-devices', 'group_slug', 'subgroup_slug', 'property_slug'),
                        'css_unit' => $this->icon_control(true, 'css_unit', true, 'property', 'selector_section',
					        'selector_css', 'all-devices', 'group_slug', 'subgroup_slug', 'property_slug')
                    )

			    );


            }

			function loaders_template(){

				// loading gif common atts
				$loader_com = 'class="ajax-loader small" src="'.$this->thispluginurl.'/images/';

				return array(
					'default' => '<img id="loading-gif-template" '.$loader_com.'ajax-loader-green.gif" />',
					'wbg' => '<img id="loading-gif-template-wbg" '.$loader_com.'ajax-loader-wbg.gif" />',
					'mgbg' => '<img id="loading-gif-template-mgbg" '.$loader_com.'ajax-loader-mgbg.gif" />',
					'sec' => '<img id="loading-gif-template-sec" '.$loader_com.'sec-ajax-loader-green.gif" />',
					'sel' => '<img id="loading-gif-template-sel" '.$loader_com.'sel-ajax-loader-green.gif" />',
				);

			}

			function log_item_template(){

				// template for displaying save error and error report option
				$short = __('Error saving settings', 'microthemer');
				$long =
					'<p>' . sprintf(
						esc_html__('Please %s. The error report sends us information about your current Microthemer settings, server and browser information, and your WP admin email address. We use this information purely for replicating your issue and then contacting you with a solution.', 'microthemer'),
						'<span id="email-error" class="link">' . __('click this link to email an error report to Themeover', 'microthemer') . '</span>'
					) . '</p>
				<p>' . wp_kses(
						__('<b>Note:</b> reloading the page is normally a quick fix for now. However, unsaved changes will need to be redone.', 'microthemer'),
						array( 'b' => array() )
					). '</p>';


				return $this->display_log_item('error',
					array(
						'short'=> $short,
						'long'=> $long
					),
					0,
					'id="log-item-template"'
				);
			}

            function property_group_templates(){

	            $pg_templates = array();

	            // add property group templates
	            foreach ($this->propertyoptions as $property_group_name => $property_group_array) {

		            // we want root keys only for $property_group_array, to match propertyOptions format
		            $array_keys = array_keys($property_group_array);
		            $property_group_array_root = array();
		            foreach($array_keys as $prop_slug){
			            $property_group_array_root[$prop_slug] = '';
		            }

		            $pg_templates[$property_group_name] = $this->single_option_fields(
			            'selector_section',
			            'selector_css',
			            array(),
			            $property_group_array_root,
			            $property_group_name,
			            '',
			            true
		            );
	            }

	            return $pg_templates;
            }

			// display the css filters
			function display_css_filters(){
				$html = '
				<div class="quick-opts first-quick-opts">
					<div class="quick-opts-inner">';

				$i = 0;
				foreach ($this->css_filters as $key => $arr){
					if ($i === 0){
						$extra = $this->css_filter_list(
							$this->css_filters['pseudo_elements']['items'],
							'pseudo_elements',
							$this->css_filters['pseudo_elements']['label']
						);
					} elseif ($i === 1){
						++$i;
						continue;
					} else {
						$extra = '';
					}
					$html.= '
							<div class="mt-col mt-col'.(++$i).'">'
					        . $this->css_filter_list(
							$arr['items'],
							$key,
							$arr['label']
						). $extra . '
							</div>';
				}

				$clear_text = esc_html__('Clear all', 'microthemer');
				$html.= '
						<span class="clear-filters-wrap"
							  title="'.esc_html__('Clear all selector adjustments', 'microthemer').'">
							<span class="clear-icon tvr-icon clear-css-filters clear-css-filters-icon"></span>
							<span class="clear-css-filters clear-css-filters-text">'.$clear_text.'</span>
						</span>

					</div>
				</div>';

				return $html;

			}

			// output a list of css filters (pseudo classes, elements, page-specific)
			function css_filter_list($filters, $type, $heading) {
				$html = '
				<div class="filter-heading">'.$heading.'</div>';
				$num_items = count($filters);
				$index = 0;

				// there are lots of pseudo, split into 3 columns
				if (($num_items >= 12)){
					$break = $num_items/3;
					$j = -1;
					foreach($filters as $k => $v){
						if (++$j > $break){
							++$index;
							$j = 0;
						}
						$split_filters[$index][$k] = $v;
					}
				} else {
					$split_filters[0] = $filters;
				}

				// loop through normalised $filters
				foreach ($split_filters as $i => $f){
					$html.= '
					<ul class="css-filter-list flist-'.$type.' cssfl-index-'.$i.'">';
					foreach($f as $key => $arr){
						$text = !empty($arr['text']) ? $arr['text'] : $key;
						$edClass = !empty($arr['editable']) ? ' filter-editable' : '';
						$li =
							$this->ui_toggle(
								$type,
								$arr['tip'],
								$arr['tip'],
								// left over enabled
								!empty($this->preferences[$type][$key]),
								'css-filter-item filter-'.$this->pseudo_class_format($text) . $edClass,
								false,
								array(
									'tag' => 'li',
									'dataAtts' => array(
										'filter' => $key,
										'type' => $type,
										'no-save' => $type === 'page_specific' ? 0 : 1
									),
									'text' => $text,
									'inner_icon' => $this->ui_toggle(
										'favourite_filter',
										esc_attr__('Favorite this filter', 'microthemer'),
										esc_attr__('Unfavorite this filter', 'microthemer'),
										!empty($this->preferences['favourite_filter'][$key]),
										'tvr-icon star-icon fav-filter ui-toggle ui-par',
										false,
										array(
											'pref_sub_key' => $key
										)
									),
									'pref_sub_key' => $text,
									'css_filter' => $arr
								)
							);
						$html.= $li;
						// save for favs list if required
						if (!empty($this->preferences['favourite_filter'][$key])){
							// the title is a bit annoying on favourites.
							$this->fav_css_filters.= preg_replace('/title=\"([^"]*)\"/i', '', $li, 1);
						}
					}

					// provide an option to remember the choice
					//$html.= '<li class="filter-choice">'.esc_html__('More', 'microthemer').'</li>';

					$html.= '</ul>';
				}

				return $html;
			}

			// @return array - Retrieve the plugin options from the database.
			function getOptions() {
				// default options (html layout sections only - no default selectors)
				if (!$theOptions = get_option($this->optionsName)) {
					$theOptions = $this->default_folders;
					$theOptions['non_section']['hand_coded_css'] = '';
					// add_option rather than update_option (so autoload can be set to no)
					add_option($this->optionsName, $theOptions, '', 'no');
				}
				$this->options = $theOptions;
			}

			function pseudo_class_format($pseudo){
				return str_replace(array( ':', '(', ')' ), '', $pseudo);
			}

			// @return array - Retrieve the plugin plugin preferences from the database.
			function getPreferences($special_checks = false, $pd_context = false) {

				$full_preferences = array_merge($this->default_preferences, $this->default_preferences_dont_reset);

				// default preferences
				if (!$thePreferences = get_option($this->preferencesName)) {

				    $thePreferences = $full_preferences;

					// add_option rather than update_option (so autoload can be set to no)
					add_option($this->preferencesName, $thePreferences, '', 'no');
				}

				$this->preferences = $thePreferences;

				// checks we only need to do once when this function is first called
				if ($special_checks){

				    /*wp_die('the special '. !empty($this->preferences['version']) . ' '.$this->preferences['version']. ' '.$this->version. ' '.($this->preferences['version'] != $this->version));*/

				    // check if this is a new version of Microthemer
					if ($this->new_version){ // empty($this->preferences['version']) || $this->preferences['version'] != $this->version){

					    //$this->new_version = true;

					    // maybe update revisions table
						$this->maybeCreateOrUpdateRevsTable();

						// signal that all selectors should be recompiled (to ensure latest data structure)
						$this->update_preference('manual_recompile_all_css', 1);
					}

					// ensure preferences are defined (for when I add new preferences that upgrading users won't have)
					$this->ensure_defined_preferences($full_preferences, $pd_context);

					// manually override user preferences after code changes
					$this->maybe_manually_override_preferences();
                }



			}

			// Save the preferences
			function savePreferences($pref_array) {

				// get the full array of preferences
				$thePreferences = get_option($this->preferencesName);

				// update the preferences array with passed values
				foreach ($pref_array as $key => $value) {
					$thePreferences[$key] = $value;
				}

				// store the version so e.g. inactive functions.php code will load most recent PIE / animation-events.js
				$previous_version = empty($thePreferences['version']) ? 0 : $thePreferences['version'];
				if (!$previous_version || $previous_version != $this->version){
					$thePreferences['previous_version'] = $previous_version;
					$thePreferences['version'] = $this->version;
				}

				// we released 5 beta with system for remembering targeting mode on page load,
				// but decided against this, have this hard set for a while to fix in DB for beta testers
				$thePreferences['hover_inspect'] = 0;

				// save in DB and go to relevant page
				// don't do deep escape here as it can run more than once
				update_option($this->preferencesName, $thePreferences);

				// update the global preferences array
				$this->preferences = $thePreferences;

				//$this->show_me = '<pre>after preference saved: '.print_r($this->preferences['my_props']['sug_values']['color'], true).'</pre>';

				return true;
			}

			// common function for outputting yes/no radios
			function preferences_grid($pref_cats, $settings_class){
                
                // labels
				$yes_label = __('Yes', 'microthemer' );
				$no_label = __('No', 'microthemer' );

				// ensure CSS recompile is off by default
				$this->preferences['manual_recompile_all_css'] = 0;

				// generate the HTML
				$html = '
                <ul id="'.$settings_class.'" class="mt-form-settings '.$settings_class.'">';

                    foreach ($pref_cats as $cat_key => $cat_array){
        
                        $html.= '
                        <li class="empty-cell empty-before-'.$cat_key.'"></li>
                        <li class="preference-category pref-cat-'.$cat_key.'">'.$cat_array['label'].'</li>';

                        $opts = $cat_array['items'];

                        foreach ($opts as $key => $array) {

                            // skip edge mode if not available
                            if ($key == 'edge_mode' and !$this->edge_mode['available']){
                                continue;
                            }

                            // common
                            $input_name = 'tvr_preferences['.$key.']';
                            $array['link'] = ( !empty($array['link']) ) ? $array['link'] : '';

                            // if radio
                            if (empty($array['is_text'])){

                                // ensure various vars are defined
                                $li_class = 'fake-radio-parent';
                                $array['label_no'] = ( !empty($array['label_no']) ) ? $array['label_no'] : '';
                                $yes_val = ($key == 'draft_mode') ? $this->current_user_id : 1;
                                $no_val = 0;

                                if (!empty($this->preferences[$key])) {
                                    $yes_checked = 'checked="checked"';
                                    $yes_on = 'on';
                                    $no_checked = $no_on = '';
                                } else {
                                    $no_checked = 'checked="checked"';
                                    $no_on = 'on';
                                    $yes_checked = $yes_on = '';
                                }

                                $form_options = '
                                <span class="yes-wrap p-option-wrap">
                                    <input type="radio" autocomplete="off" class="radio"
                                       name="'.$input_name.'" value="'.$yes_val.'" '.$yes_checked.' />
                                    <span class="fake-radio '.$yes_on.'"></span>
                                    <span class="ef-label">'.$yes_label.'</span>
                                </span>
                                 <span class="no-wrap p-option-wrap">
                                    <input type="radio" autocomplete="off" class="radio"
                                       name="'.$input_name.'" value="'.$no_val.'" '.$no_checked.' />
                                    <span class="fake-radio '.$no_on.'"></span>
                                    <span class="ef-label">'.$no_label.'</span>
                                </span>';

                            }

                            // else if input
                            else {
                                $li_class = 'mt-text-option';

	                            if (!empty($array['one_line'])){
		                            $li_class.= ' one-line';
	                            }

                                $input_id = $input_class = $arrow_class = $class = $rel = $arrow = '';
                                $input_value = ( !empty($this->preferences[$key]) ) ? $this->preferences[$key] : '';
                                $extra_info = '';

                                // does it need a custom id?
                                if (!empty($array['input_id'])){
                                    $input_id = $array['input_id'];
                                }
                                // does it need a custom input class?
                                if (!empty($array['input_class'])){
                                    $input_class = $array['input_class'];
                                }
                                // does it need a custom arrow class?
                                if (!empty($array['arrow_class'])){
                                    $arrow_class = $array['arrow_class'];
                                }
                                // does it need a custom input name?
                                if (!empty($array['input_name'])){
                                    $input_name = $array['input_name'];
                                }
                                // does it need a custom input value?
                                if (!empty($array['input_value'])){
                                    $input_value = $array['input_value'];
                                }
                                // do we want to add a data attribute (quick and dirty way to support one att)
                                if (!empty($array['extra_info'])){
                                    $extra_info = ' data-info="'. $array['extra_info'].'"';
                                }

	                            if (!empty($array['prop'])){
		                            $extra_info.= ' data-prop="'. $array['prop'].'"';
	                            }

                                // exception for css unit set (keep blank)
                                if ($input_id == 'css_unit_set'){
                                    $input_value = '';
                                }

                                // is it a combobox?
                                if (!empty($array['combobox'])){
                                    $class = 'combobox has-arrows';
                                    $rel = 'rel="'.$array['combobox'].'"';
                                    $arrow = '<span class="combo-arrow '.$arrow_class.'"></span>';
                                }

                                if (!empty($input_id)){
                                    $input_id = 'id="'.$input_id.'"';
                                }

                                $form_options = '
                                <span class="tvr-input-wrap">
                                    <input '.$input_id.' type="text" autocomplete="off" name="'.$input_name.'"  
                                    class="'.$class . ' ' . $input_class.'" '.$rel . $extra_info.'
                                    value="'. esc_attr($input_value).'" />'
                                    .$arrow .
                                '</span>';
                            }

	                        // sometimes we use empty cell after
	                        if (!empty($array['empty_before'])){
		                        $html.= '<li class="empty-cell empty-before-'.$key.'"></li>';
	                        }


                            // the option
                            $html.= '
                            <li class="'.$li_class.'">
                                <label>
                                    <span title="'.esc_attr($array['explain']).'">
                                        '.esc_html($array['label']) . $array['link'].'
                                    </span>
                                </label>
                                '.$form_options.'
                            </li>';


                            // sometimes we use empty cell after
	                        if (!empty($array['empty_after'])){
		                        $html.= '<li class="empty-cell empty-after-'.$key.'"></li>';
	                        }
                        }

                    }
                    
                $html.= '
                </ul>';
                    
                return $html;

			}

			// common function for outputting yes/no radios
			function output_radio_input_lis($opts, $hidden = ''){

				foreach ($opts as $key => $array) {

					// ensure various vars are defined
					$array['label_no'] = ( !empty($array['label_no']) ) ? $array['label_no'] : '';
					$array['default'] = ( !empty($array['default']) ) ? $array['default'] : '';
					$array['link'] = ( !empty($array['link']) ) ? $array['link'] : '';
					$yes_val = ($key == 'draft_mode') ? $this->current_user_id : 1;

					// skip edge mode if not available
					if ($key == 'edge_mode' and !$this->edge_mode['available']){
						continue;
					}

					// ensure this setting is off by default
					$this->preferences['manual_recompile_all_css'] = 0;

					?>
                    <li class="fake-radio-parent <?php echo $hidden; ?>" xmlns="http://www.w3.org/1999/html">
                        <label>
							<span title="<?php echo esc_attr($array['explain']); ?>">
								<?php echo esc_html($array['label']) . $array['link']; ?>
							</span>
                        </label>

                        <span class="yes-wrap p-option-wrap">
							<input type='radio' autocomplete="off" class='radio'
                                   name='tvr_preferences[<?php echo $key; ?>]' value='<?php echo $yes_val; ?>'
								<?php
								if ( !empty($this->preferences[$key])) {
									echo 'checked="checked"';
									$on = 'on';
								} else {
									$on = '';
								}
								?>
                            />
							<span class="fake-radio <?php echo $on; ?>"></span>
							<span class="ef-label"><?php esc_html_e('Yes', 'microthemer'); ?></span>
						</span>
                        <span class="no-wrap p-option-wrap">
							<input type='radio' autocomplete="off" class='radio' name='tvr_preferences[<?php echo $key; ?>]' value='0'
								<?php
								if ( (empty($this->preferences[$key]))
									// exception for mq set overwrite as this isn't stored as a global preference
									//and $key != 'overwrite_existing_mqs'
								) {
									echo 'checked="checked"';
									$on = 'on';
								} else {
									$on = '';
								}
								?>
                            />
							<span class="fake-radio <?php echo $on; ?>"></span>
							<span class="ef-label"><?php esc_html_e('No', 'microthemer'); ?></span>
						</span>
                    </li>
					<?php
				}
			}

			// common function for text inputs/combos
			function output_text_combo_lis($opts, $hidden = ''){
				foreach ($opts as $key => $array) {
					$input_id = $input_class = $arrow_class = $class = $rel = $arrow = '';
					$input_name = 'tvr_preferences['.$key.']';
					$input_value = ( !empty($this->preferences[$key]) ) ? $this->preferences[$key] : '';
					$extra_info = '';

					// does it need a custom id?
					if (!empty($array['input_id'])){
						$input_id = $array['input_id'];
					}
					// does it need a custom input class?
					if (!empty($array['input_class'])){
						$input_class = $array['input_class'];
					}
					// does it need a custom arrow class?
					if (!empty($array['arrow_class'])){
						$arrow_class = $array['arrow_class'];
					}
					// does it need a custom input name?
					if (!empty($array['input_name'])){
						$input_name = $array['input_name'];
					}
					// does it need a custom input value?
					if (!empty($array['input_value'])){
						$input_value = $array['input_value'];
					}
					// do we want to add a data attribute (quick and dirty way to support one att)
					if (!empty($array['extra_info'])){
						$extra_info = ' data-info="'. $array['extra_info'].'"';
					}

					// exception for css unit set (keep blank)
					if ($input_id == 'css_unit_set'){
						$input_value = '';
					}

					// is it a combobox?
					if (!empty($array['combobox'])){
						$class = 'combobox has-arrows';
						$rel = 'rel="'.$array['combobox'].'"';
						$arrow = '<span class="combo-arrow '.$arrow_class.'"></span>';
					}

					if (!empty($input_id)){
						$input_id = 'id="'.$input_id.'"';
                    }

					?>
                    <li class="tvr-input-wrap <?php echo $hidden; ?>">
                        <label class="text-label">
							<span title="<?php echo esc_attr($array['explain']); ?>">
								<?php echo esc_html($array['label']); ?>
							</span>
                        </label>
                        <input type='text' autocomplete="off" name='<?php echo esc_attr($input_name); ?>'
                               <?php echo $input_id; ?>
                               class="<?php echo $class . ' ' . $input_class; ?>" <?php echo $rel . $extra_info; ?>
                               value='<?php echo esc_attr($input_value); ?>' />
						<?php echo $arrow; ?>
                    </li>
					<?php
				}
			}

			// create revisions table if it doesn't exist
			function maybeCreateOrUpdateRevsTable() {
				global $wpdb;
				$table_name = $wpdb->prefix . "micro_revisions";
				$micro_ver_num = get_option($this->micro_ver_name);

				/*wp_die('the table should update' . ' ' . ($micro_ver_num > $this->db_chg_in_ver) . ' '  .$micro_ver_num . '  '.$this->db_chg_in_ver);*/

				// only execut following code if table doesn't exist.
				// dbDelta function wouldn't overwrite table,
				// But table version num shouldn't be updated with current plugin version if it already exists
				if( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) != $table_name or
				    $micro_ver_num < $this->db_chg_in_ver) {
					if ( ! empty( $wpdb->charset ) )
						$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
					if ( ! empty( $wpdb->collate ) )
						$charset_collate .= " COLLATE $wpdb->collate";
					$sql = "CREATE TABLE $table_name (
						id mediumint(9) NOT NULL AUTO_INCREMENT,
						time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
						user_action TEXT DEFAULT '' NOT NULL,
						data_size VARCHAR(10) DEFAULT '' NOT NULL,
						settings longtext NOT NULL,
						preferences longtext DEFAULT NULL,
						saved BOOLEAN DEFAULT 0,
						upgrade_backup BOOLEAN DEFAULT 0,
						UNIQUE KEY id (id)
						) $charset_collate;";

					//wp_die('the table should update' . $sql);

					require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
					dbDelta($sql);

					// store the table version in the wp_options table (useful for upgrading the DB)
					add_option($this->micro_ver_name, $this->version);

					// todo dbDelta doesn't overwrite but condition always returns true. Have proper check and add first entry (see below)
					//echo '$wpdb->get_var( "SHOW TABLES LIKE $table_name" ) != $table_name' .$wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) != $table_name;
					//echo '$micro_ver_num < $this->db_chg_in_ver' .$micro_ver_num < $this->db_chg_in_ver;
					// set the first entry to be the state MT is in during initial install (empty default folders)
					//$initial_install = esc_html__('Initial install', 'microthemer');
					// Note: set $tryCreate to false to prevent circular ref
					//$this->updateRevisions( $this->options, $initial_install, false);

					return true;
				}
				else {
					return false;
				}
			}


			// format user action in same json format - see JS function: TvrUi.user_action() - this isn't very DRY. JS templating would be better.
			function json_format_ua($icon, $item, $val = false){
				$json = '{"items":["'.$item.'"],"val":"'.$val.'","icon":"'.$icon.'","main_class":"",';
				$json.= '"icon_html":"<span class=\"h-i no-click '.$icon.'\" ></span>",';
				$json.= '"html":"<span class=\"history-item history_'.$this->to_param($item).'\"><span class=\"his-items\"><span>'.$item.'</span></span>';
				if ($val){
					$json.= '<span class=\"his-val\">'.$val.'</span>';
				}
				$json.= '</span>"}';
				return $json;
			}

			// Update the Revisions Table
			function updateRevisions(
			        $save_data, $user_action = '', $tryCreate = true, $preferences = false, $upgrade_backup = false
            ) {

			    // sometimes we don't want to log an action e.g. if editing a selector's code via the editor
                // the change will be shown in the code editor change history entry
                // and we don't want them to restore one without the other (selector code and editor content)
			    if (is_null($user_action) or $user_action === 'null'){
			       return true; // false would generate an error message
			    }

			    $user_action = html_entity_decode($user_action);

				// create/update revisions table if it doesn't already exist or is out of date
				if ($tryCreate){
					$this->maybeCreateOrUpdateRevsTable();
				}

				// include the user's current media queries for restoring back
				$save_data['non_section']['active_queries'] = $this->preferences['m_queries'];

				// add the revision to the table
				global $wpdb;
				$table_name = $wpdb->prefix . "micro_revisions";
				$serialized_data = serialize($save_data);
				$data_size = round(strlen($serialized_data)/1000).'KB';
				// $wpdb->insert (columns and data should not be SQL escaped): https://developer.wordpress.org/reference/classes/wpdb/insert/
				$rows_affected = $wpdb->insert( $table_name, array(
						'time' => current_time('mysql', false), // use blogs local time - doesn't work on Nelson's site
						//'time' => $this->adjust_unix_timestamp_for_local(time(), 'mysql'), // nor does this
						//'time' => date_i18n('Y-m-d H:i:s'), // or this

						'user_action' => $user_action,
						'data_size' => $data_size,
						'settings' => $serialized_data,

						// pass in preferences when a revision should revert to workspace settings
                        // adding this so users can rollback to a pre-speed version of MT in case of an upgrade issue
						'preferences' => ($preferences ? serialize($preferences) : false),
						'upgrade_backup' => $upgrade_backup,
                ));

				/*$this->log(
					esc_html__('$rows_affected: '.$rows_affected, 'microthemer'),
					'<pre>Hello' . print_r($user_action, true) . '</pre>'
				);*/

				//$this->show_me.= '<pre>$rows_affected:  '.$rows_affected.'</pre>';

                $default_num_revs = 50;
                $max_revisions = !empty($this->preferences['num_history_points'])
                        ? intval($this->preferences['num_history_points'])
                        : $default_num_revs;

                // cap lowest and highest number of revisions
                if ($max_revisions > 300){
                    $max_revisions = 300;
                } if ($max_revisions < 1){
					$max_revisions = 1;
				}

				$maybe_exclude_backups = !$upgrade_backup ? 'and upgrade_backup != 1' : '';

				// check if an old revision needs to be deleted
				$wpdb->get_results("select id from $table_name 
				where saved != 1 $maybe_exclude_backups order by id asc");

				if ($wpdb->num_rows > $max_revisions) {

				    $excess_rows = intval($wpdb->num_rows - $max_revisions);

                    // this will not delete saved or backups for regular saves. And wont delete saved backups ever.
                    $sql = "delete from $table_name 
                    where saved != 1 $maybe_exclude_backups order by id asc limit $excess_rows";
					$wpdb->query($sql);

				}
				return true;
			}

			function updateRevisionSaveStatus($rev_id, $rev_save_status){
			    global $wpdb;
				$table_name = $wpdb->prefix . "micro_revisions";
				$wpdb->query(
					$wpdb->prepare(
					    "update $table_name set saved = %d where id = %d",
                        $rev_save_status, $rev_id
                    )
				);
            }

			// adjust unix time stamp for local time
			function adjust_unix_timestamp_for_local($unix_timestamp, $format = 'timestamp'){
			    $mysql_format = get_date_from_gmt( date( 'Y-m-d H:i:s', $unix_timestamp ) );
			    return $format === 'timesptamp'
                    ? strtotime($mysql_format)
                    : $mysql_format;
				//return strtotime( get_date_from_gmt( date( 'Y-m-d H:i:s', $unix_timestamp ) ));
			}

			// custom function for time diff as we want seconds
			function human_time_diff( $from, $to = '' ) {

				if ( empty( $to ) ) {
					$to = current_time( 'timestamp', false); // use blogs local time
				}

				$diff = (int) abs( $to - $from );

				if ( $diff < 60 ) {
					//$since = $diff . ' secs';
					$since = sprintf( _n( '%s sec', '%s secs', $diff ), $diff );
				} elseif ( $diff < HOUR_IN_SECONDS ) {
					$mins = round( $diff / MINUTE_IN_SECONDS );
					if ( $mins <= 1 )
						$mins = 1;
					/* translators: min=minute */
					$since = sprintf( _n( '%s min', '%s mins', $mins ), $mins );
				} elseif ( $diff < DAY_IN_SECONDS && $diff >= HOUR_IN_SECONDS ) {
					$hours = round( $diff / HOUR_IN_SECONDS );
					if ( $hours <= 1 )
						$hours = 1;
					$since = sprintf( _n( '%s hour', '%s hours', $hours ), $hours );
				} elseif ( $diff < WEEK_IN_SECONDS && $diff >= DAY_IN_SECONDS ) {
					$days = round( $diff / DAY_IN_SECONDS );
					if ( $days <= 1 )
						$days = 1;
					$since = sprintf( _n( '%s day', '%s days', $days ), $days );
				} elseif ( $diff < MONTH_IN_SECONDS && $diff >= WEEK_IN_SECONDS ) {
					$weeks = round( $diff / WEEK_IN_SECONDS );
					if ( $weeks <= 1 )
						$weeks = 1;
					$since = sprintf( _n( '%s week', '%s weeks', $weeks ), $weeks );
				} elseif ( $diff < YEAR_IN_SECONDS && $diff >= MONTH_IN_SECONDS ) {
					$months = round( $diff / MONTH_IN_SECONDS );
					if ( $months <= 1 )
						$months = 1;
					$since = sprintf( _n( '%s month', '%s months', $months ), $months );
				} elseif ( $diff >= YEAR_IN_SECONDS ) {
					$years = round( $diff / YEAR_IN_SECONDS );
					if ( $years <= 1 )
						$years = 1;
					$since = sprintf( _n( '%s year', '%s years', $years ), $years );
				}
				return $since;
			}

			// Get Revisions for displaying in table
			function getRevisions() {

				// create/update revisions table if it doesn't already exist or is out of date
				$this->maybeCreateOrUpdateRevsTable();

				// get the full array of revisions
				global $wpdb;
				$table_name = $wpdb->prefix . "micro_revisions";
				//$revs = $wpdb->get_results("select id, user_action, data_size, date_format(time, '%D %b %Y %H:%i') as datetime
				/*$revs = $wpdb->get_results("select id, user_action, data_size, unix_timestamp(time) as unix_timestamp
				from $table_name order by id desc");*/
				$revs = $wpdb->get_results("select id, user_action, data_size, time, saved from $table_name order by id desc");
				$total_rows = $wpdb->num_rows;
				// if no revisions, explain
				if ($total_rows == 0) {
					return '<span id="revisions-table">' .
					       esc_html__('No Revisions have been created yet. This will happen after your next save.', 'microthemer') .
					       '</span>';
				}
				// if one revision, it's the same as the current settings, explain
				//if ($total_rows == 1) {
				//return '<span id="revisions-table">' .
				//esc_html__('The only revision is a copy of your current settings.', 'microthemer') .
				//'</span>';
				//}
				//<th> | ' . esc_html__('Save', 'microthemer') . '</th>
				//<!--<td class="rev-save">Save</td>-->
				// revisions exist so prepare table
				//<th>' . esc_html__('Num', 'microthemer') . '</th>
				//<th>' . esc_html__('Size', 'microthemer') . '</th>
				$rev_table =
					'
				<table id="revisions-table">
				<thead>
				<tr>
					<th>' . esc_html__('Size', 'microthemer') . '</th>
					<th>' . esc_html__('Time', 'microthemer') . '</th>
					<th colspan="2">' . esc_html__('User Action', 'microthemer') . '</th>
					<th>' . esc_html__('Restore', 'microthemer') . '</th>
					<th>' . esc_html__('Save', 'microthemer') . '</th>
					
				</tr>
				</thead>';

				$i = 0;
				foreach ($revs as $rev) {

					// adjust unix timestamp for blog's GMT timezone offset - no this doesn't make sense
					//$local_timestamp = $this->adjust_unix_timestamp_for_local($rev->unix_timestamp);

					//$local_timestamp = $rev->unix_timestamp;

					$local_timestamp = strtotime($rev->time);
					$time_ago = $this->human_time_diff($local_timestamp);

					//$time_ago = $this->getTimeSince($rev->timestamp);
					// get traditional save or new history which will be in json obj
					$user_action = $rev->user_action;
					$rev_icon = $main_class = '';
					$legacy_new_class = 'legacy-hi';
					if (strpos($user_action, '{"') !== false){
						$ua = $this->json('decode', $rev->user_action); //  json_decode($rev->user_action, true);
						$legacy_new_class = 'new-hi';
						$user_action = $this->unescape_cus_quotes($ua['html'], true);
						$rev_icon = $ua['icon_html'];
						$main_class = $ua['main_class'];
					}

					// saved lock icon
                    $rev_is_saved = !empty($rev->saved);
					$save_rev_pos = esc_html__('Permanently save restore point', 'microthemer');
					$save_rev_neg = esc_html__('Unsave restore point', 'microthemer');
					$rev_is_saved_class = $rev_is_saved ? ' revision-is-saved' : '';
					$rev_save_title = $rev_is_saved ? $save_rev_neg : $save_rev_pos;
					$saved_icon = '<span class="save-revision'.$rev_is_saved_class.'" 
					data-pos="'.$save_rev_pos.'" data-neg="'.$save_rev_neg.'" title="'.$rev_save_title.'"
					rel="'.$rev->id.'"></span>';

					$niceDate = date('l jS \of F Y H:i:s', $local_timestamp);

					//<td class="rev-num">'.$total_rows.'</td>
					//<td class="rev-size">'.$rev->data_size.'</td>
					$rev_table.= '
					<tr class="'.$legacy_new_class.$rev_is_saved_class.'">
						<td class="rev-size">'.$rev->data_size.'</td>
						<td class="rev-time tvr-help" title="'.$niceDate.'">'.
					             sprintf(esc_html__('%s ago', 'microthemer'), $time_ago).'</td>
						<td class="rev-icon '.$main_class.'">'.$rev_icon.'</td>
						<td class="rev-action '.$main_class.'">'.$user_action.'</td>
						<td>
					
						';
					if ($i == 0) {
						$rev_table.= esc_html__('Current', 'microthemer');
					}
					else {
						$rev_table.='<span class="link restore-link" rel="mt_action=restore_rev&tvr_rev='.$rev->id.'">' . esc_html__('Restore', 'microthemer') . '</span>';
					}
					$rev_table.='</td>
                         <td class="rev-save">'.$saved_icon.'</td>
					</tr>';
					--$total_rows;
					++$i;
				}
				$rev_table.= '</table>';
				return $rev_table;
			}

			// update a single preference
			function update_preference($key, $value){
				$pref_array = array();
				$pref_array[$key] = $value;
				$this->savePreferences($pref_array);
            }

			// Restore a revision
			function restoreRevision($rev_key) {
				// get the revision
				global $wpdb;
				$table_name = $wpdb->prefix . "micro_revisions";
				$rev = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $rev_key) );
				$rev->settings = unserialize($rev->settings);

				// add css units, mq keys (extra tabs) etc to settings display correctly
				$filtered_json = $this->filter_incoming_data('restore', $rev->settings);



				// if special revision that also has preferences, restore those too
                if (!empty($rev->preferences)){
	                update_option($this->preferencesName, unserialize($rev->preferences));
                }

                // signal that all selectors should be recompiled (to ensure latest data structure)
                $this->update_preference('manual_recompile_all_css', 1);

				// restore to options DB field
				update_option($this->optionsName, $filtered_json);
				$this->options = get_option($this->optionsName); // this DB interaction doesn't seem necessary...
				return true;
			}

			function css_sass_comment_regex(){
			    $commentSingle      = '\/\/';
				$commentMultiLeft   = '\/\*';
				$commentMultiRight  = '\*\/';
				$commentPattern = $commentMultiLeft . '.*?' . $commentMultiRight;
				return '/' . $commentSingle . '[^\n]*\s*|(' . $commentPattern . ')/isu';
            }

			function css_sass_import_regex(){
				return '/@import\s+(?:"|\')([\w\-.\/ ]+)(?:"|\');/';
			}

            function get_css_sass_comments($content){

			    preg_match_all(
		            $this->css_sass_comment_regex(),
		            $content,
		            $matches,
		            PREG_PATTERN_ORDER
	            );

	            return $matches ? $matches[0] : false;
            }

			function strip_css_sass_comments($content, $onlyImportComments = false){

				if ($matches = $this->get_css_sass_comments($content)){

				    foreach ($matches as $comment){

				        if (!$onlyImportComments || preg_match($this->css_sass_import_regex(), $comment) ){
					        $content = str_replace($comment, '', $content);
                        }

                    }
                }

				return $content;
			}

			// generate sass content for replacing @import rules with then compiling sass on the frontend
			function get_sass_import_paths($content, $cur_path){

				$content = $this->strip_css_sass_comments($content, true);

				preg_match_all(
					$this->css_sass_import_regex(),
					$content,
					$matches,
					PREG_PATTERN_ORDER
				);

				if (!$matches){
				    return false;
                }

                $formatted = array();

				// sometimes root path has ./ when it should be a blank string
				$cur_path = preg_replace('/^\.\//', '', $cur_path);

				foreach ($matches[0] as $i => $rule){

				    $path = $matches[1][$i];
					$resolved_path = $this->normalize_path($cur_path . $path);
					$valid_path = $this->check_sass_file_exists($resolved_path);

					// only get list valid files that do not have .css extension explicitly declared
					if ($valid_path && $this->get_extension($path) !== 'css'){
						$formatted[$i] = array(
							'rule' => $rule,
							'path' => $path,
							'cur_path' => $cur_path,
							'resolved_path' => $valid_path
						);
                    }


                }

                return $formatted;
			}

			// convert import file and sub imports into long strings
			function recursively_scan_import_files($config, $cur_path = ''){

				$rule = $config['import']['rule'];
			    $file_content = $rule; // default to rule as it will not be possible to replace if file path is wrong
				$resolved_path = $config['import']['resolved_path']; // relative path from root with extension
				$abs_path = $this->micro_root_dir . $resolved_path;

				if (file_exists($abs_path)){

					// get file content
				    $file_content = file_get_contents($abs_path);

					// strip commented out @import paths
					$file_content = $this->strip_css_sass_comments($file_content, true);

					// check for sub @imports
					if ($imports = $this->get_sass_import_paths($file_content, $this->get_real_dirname($resolved_path))){

						//$file_content.= '<pre>'.print_r($imports, true).'</pre>';
						//return $file_content;

						foreach ($imports as $i => $arr){

							$sub_content = $this->recursively_scan_import_files(
								array(
									'import' => $arr
								),
								$this->get_real_dirname($arr['resolved_path']) // new current path
							);

							$file_content = str_replace($imports[$i]['rule'], $sub_content, $file_content);

							// debug
							//$file_content.= 'RUULE: '.$rule . 'SUUB: '.$sub_content;
							//$file_content.= 'Prev resolved path: '.$this->get_real_dirname($resolved_path). '<br />';
							//$file_content.= '$imports: <pre>'.print_r($imports, true).'</pre>';

						}
					}
				}

				return $file_content;
			}

			// convert e.g. /path/piece/section/../file.txt to /path/piece/file.txt
			function normalize_path($str){
				$N = 0;
				$A = explode("/",preg_replace("/\/\.\//",'/',$str));  // remove current_location
				$B = array();
				for($i = sizeof($A)-1;$i>=0;--$i){
					if(trim($A[$i]) ===".."){
						$N++;
					}else{
						if($N>0){
							$N--;
						}
						else{
							$B[] = $A[$i];
						}
					}
				}
				return implode("/",array_reverse($B));
			}

			// convert e.g. /path/piece/section/../file.txt to /path/piece/
			function get_real_dirname($path){
				$pathinfo = pathinfo($this->normalize_path($path));
                return !empty($pathinfo['dirname']) ? trailingslashit($pathinfo['dirname']) : '';
            }

			// generate sass content for replacing @import rules with then compiling sass on the frontend
			function get_sass_import_content(){

				$preloaded_sass = array();

				// if there are import paths defined
				if (!empty($this->options['non_section']['hand_coded_css']) &&
				    $imports = $this->get_sass_import_paths($this->options['non_section']['hand_coded_css'], '')
				){

				    // recursively import the content so there is one long string of SASS for each @import
					foreach ($imports as $i => $arr){
						$preloaded_sass[$arr['resolved_path']] = $this->recursively_scan_import_files(
							array(
                                'import' => $arr
                            )
						);
					}
				}

				return $preloaded_sass;
			}

			function client_scss(){
			    return $this->preferences['allow_scss'] && empty($this->preferences['server_scss']);
            }

			function server_scss(){
				return $this->preferences['allow_scss'] && !empty($this->preferences['server_scss']);
			}

			// check if a file exists in /micro-themes dir (various different paths are valid with SASS @imports)
		    function check_sass_file_exists($path){

			    $files = $this->file_structure;
				$parts = explode('/', $path);
				$partsFinalIndex = count($parts)-1;

			    // check for user specified file extension
			    $path_parts = pathinfo($path);
			    $definedExt = !empty($path_parts['extension']) ? $path_parts['extension'] : '';
			    $noExt =  ltrim($path_parts['filename'], '_');

			    // variation dimensions
				$extensions = $definedExt ? array($definedExt) : ['scss', 'sass', 'css'];
				$names = array($noExt, '_'.$noExt);

				// create variations
				$variations = array();
			    foreach ($extensions as $e) {
				    foreach ($names as $n) {
						$partsClone = $parts;
					    $partsClone[$partsFinalIndex] = $n.'.'.$e;
						$variations[] = $partsClone;
				    }
			    }

                // loop through variations
			    foreach ($variations as $variation) {

				    if ($this->get_item(
					    $files,
					    $variation
				    )){
				        return implode('/', $variation); // bingo
                    }
			    }

                return false;
		    }

			// simple wrapper getting data // todo test it works
			function &get_item(&$data, $trail, $startIndex = 0){

				$item = &$this->get_or_update_item(
                            $data, array(
                                'action' => 'get',
                                'trail' => $trail,
                            ), $startIndex
                        );

				return $item;
			}

			// (optionally) update a multidimensional array item using array trail e.g. ['non_section', 'meta'].
            // Returns a reference to the target item. Note '&' must proceed function call for ref rather than copy.
			function &get_or_update_item(&$data, $config, $startIndex = 0){

                $item = &$data;
                $trail = !empty($config['trail']) ? $config['trail'] : array();
                $trail_length = count($trail);

                // to get round PHP error: Only variable references should be returned by reference
                $false = false;

			    for ($x = $startIndex; $x < $trail_length; $x++) {
				    $key = $trail[$x];

				    // if item doesn't exist
				    if (!isset($data[$key])){

				        // bail if we're trying to get an item that doesn't exist
				        if ($config['action'] === 'get'){

				           /* $this->log(
						        esc_html__('Trail lead to undefined item: '.$key, 'microthemer'),
						        '<pre>parent: '  . print_r($data, true) . '</pre>'
						        //'notice'
					        );*/

					        return $false;
                        }

                        // create trail is we're trying to perform an action on a non_existant item
                        else {

				            $data[$key] = array();

	                        /*$this->log(
		                        esc_html__('Previously undefined item added: '.$key, 'microthemer'),
		                        '<pre>parent: '  . print_r($data, true) . '</pre>'
		                        //'notice'
	                        );*/
                        }

                    }

				    $item = &$data[$key];
				    $next_index = $x+1;

				    //$this->show_me.= '<pre>loop key: '.$key. ' $x: '.$x. ' $trail_length: '.$trail_length. ' $item: '.$item.'</pre>';

					if ($next_index < $trail_length){
                        return $this->get_or_update_item($item, $config, $next_index);
                    }
				}

				// optionally update item
				switch($config['action']){
					case 'get':
						return $item;
						break;
					case 'replace':

						/*$this->log(
							esc_html__('The replace item: ', 'microthemer'),
							'<pre>parent: '  . print_r($item, true) . '</pre>'
						//'notice'
						);*/

						$item = $config['data'];
						break;
					case 'delete':
						unset($item[$config['key']]);
						break;
					case 'rename':
					    $this->order_item_properties($item, $config['order'], $config['key'], $config['new_key']);
						break;
					case 'reorder':
						$this->order_item_properties($item, $config['order']);
						break;
					case 'append':
						$item[$config['key']] = $config['data'];
						break;
					case 'array_merge':
						$item = array_merge($item, $config['data']);
						break;
					case 'array_merge_recursive_distinct':

					    // tip for myself, this causes 500 error otherwise
					    if (!is_array($config['data'])){
						    $this->log(
							    esc_html__('Merge data is not an array: ', 'microthemer'),
							    '<pre>Update package: '  . print_r($config, true) . '</pre>'
						    );
						    return $false;
                        }

					    $item = $this->array_merge_recursive_distinct($item, $config['data']);
						break;
					/* unlikely to ned this
					 * array_merge_recursive is a bit weird http://php.net/manual/en/function.array-merge-recursive.php
                     * see explaination of diff with array_merge_recursive_distinct on above PHP page
					 * case 'array_merge_recursive':
						$item = array_merge_recursive($item, $config['data']);
						break;*/
				}

                // return the updated item
				return $item;
            }

            function order_item_properties(&$item, $order, $old_key = false, $new_key = false){
			    $new_item = array();
	            foreach ($order as $i => $key){

	                // don't add undefined keys
	                if (isset($item[$key])){
		                $new_item[(($key == $old_key) ? $new_key : $key)] = $item[$key];
                    } else {
		                /* for debugging
		                 * $this->log(
			                esc_html__('Order key was undefined: '.$key, 'microthemer'),
			                '<pre>parent: '  . print_r($item, true) . '</pre>'
		                );*/
                    }
	            }
	            $item = $new_item;
            }



			// update the ui options using & reference to behave like JS object
			function apply_save_package($savePackage, &$data){

				$before_after = array('### Save Package Before and After ###');

				foreach($savePackage as $update){

				    if ($update['action'] === 'debug'){
					    if ($this->debug_save_package) {
						    $before_after[] = $update['data'];
					    }
					    continue;
                    } elseif ($update['action'] === 'no_new_data'){
					    continue;
				    }

					$before = false;
					if ($this->debug_save_package) {
						$before                 = $this->get_or_update_item($data, array_merge($update, array('action' => 'get')));
						$update[ 'callerFunc' ] = !empty($update[ 'callerFunc' ]) ? $update[ 'callerFunc' ] : '';
					}

					$data_item = &$this->get_or_update_item($data, $update, 0);

					if ($this->debug_save_package) {
						$before_after[] = array(
							'before '.$update['callerFunc'].' (' .$update['action'].')' => $before,
							'after '.$update['callerFunc'].' (' .$update['action'].')' => $data_item,
							'update_package '.$update['callerFunc'].' (' .$update['action'].')' => $update
						);
					}
				}

				if ($this->debug_save_package) {
					$before_after[] = array(
						'Full options:' => $this->options
					);
					$write_file = @fopen($this->debug_dir . 'save-package.txt', 'w');
					fwrite($write_file, print_r($before_after, true));
					fclose($write_file);
				}

			}

            // check the last save time
			function check_last_save_time($last_save_time){

			    // if we have no last_save_time to compare, set it for future reference
			    if (!isset($this->options['non_section']['last_save_time'])){
					$this->options['non_section']['last_save_time'] = time();
				}

				// else we do have a time in the DB and a passed save time to compare
				else if ($last_save_time){

					// do safety check to make sure newer settings haven't been applied in another tab
					// allow passed last save time to be 15 seconds out due to quirk of resave I haven't fully understood
					if ( intval($last_save_time + 10) < intval($this->options['non_section']['last_save_time']) ){

						$this->log(
							esc_html__('Multiple tabs/users issue', 'microthemer'),
							'<p>' . esc_html__('MT settings were updated more recently by another user or browser tab. Saving from this outdated tab could cause data loss. Please reload the page instead of saving from this tab (to get the latest changes).', 'microthemer') . '</p>'
						);

						$this->outdatedTabDebug = 'Last save time: '.intval($last_save_time). ", \n" .
						                          'Stored save time: '.intval($this->options['non_section']['last_save_time'])  . ", \n" .
						                          'Difference: ' . (intval($last_save_time) - intval($this->options['non_section']['last_save_time']));

						$this->outdatedTabIssue = 1;

						return false;
					}

					else {

						$this->outdatedTabDebug = 'Last save time: '.$last_save_time. ", \n" .
						                          'Stored save time: '.$this->options['non_section']['last_save_time'] . ", \n" .
						                          'Difference: ' . (intval($last_save_time) - intval($this->options['non_section']['last_save_time']));

					    // update last save time
						$this->options['non_section']['last_save_time'] = time();



					}



                }

                return true;
            }

			// Save the UI styles to the database - from full or partial save package
			function saveUiOptions2($savePackage, $partial = false, $last_save_time = false){

			    // check last save time
                if (!$this->check_last_save_time($last_save_time)){
                    return false;
                }

			    // plain save if no save package
			    if (!$partial){
				    $this->options = $savePackage;
                }

                // loop through update items making adjustments to $this->options
                else {
                    $this->apply_save_package($savePackage, $this->options);
                }

                // tag version the settings were saved at so e.g. css units can be imported correctly for legacy data
                $this->options['non_section']['mt_version'] = $this->version;

				// update DB
                update_option($this->optionsName, $this->options);

				return true;

			}

			// Resest the options.
			function resetUiOptions(){
				delete_option($this->optionsName);
				$this->getOptions(); // reset the defaults
				$pref_array = array();
				$pref_array['active_theme'] = 'customised';
				$pref_array['theme_in_focus'] = '';
				$pref_array['num_saves'] = 0;
				$pref_array['g_fonts_used'] = false;
				$pref_array['g_url'] = '';
				$pref_array['g_url_with_subsets'] = '';
				$this->savePreferences($pref_array);
				return true;
			}

			// clear the style definitions - leaving all the sections and selectors intact
			function clearUiOptions() {
				if (is_array($this->options['non_section']['view_state'])) {
					foreach($this->options['non_section']['view_state'] as $section_name => $array) {
						// loop through the selector trackers
						if (is_array($array)) {
							foreach ( $array as $css_selector => $view_state) {
								if ($css_selector == 'this') { continue; }
								// reset styles array to defaults
								foreach ($this->property_option_groups as $group => $junk){
									$option_groups[$group] = '';
								}
								$this->options[$section_name][$css_selector]['styles'] = $option_groups;
							}
						}
					}
				}
				// clear the custom code
				$this->options['non_section']['hand_coded_css'] = '';
				$this->options['non_section']['ie_css']['all'] = '';
				$this->options['non_section']['ie_css']['nine'] = '';
				$this->options['non_section']['ie_css']['eight'] = '';
				$this->options['non_section']['ie_css']['seven'] = '';
				$this->options['non_section']['js'] = '';
				// clear all media query settings
				$this->options['non_section']['m_query'] = array();

				// update the options in the DB
				update_option($this->optionsName, $this->options);
				$this->options = get_option($this->optionsName); // necessary?
				return true;
			}

			function log($short, $long, $type = 'error', $preset = false, $vars = array()){
				// some errors are the same, reuse the text
				if ($preset) {
					if ($preset == 'revisions'){
						$this->globalmessage[++$this->ei]['short'] = __('Revision log update failed.', 'microthemer');
						$this->globalmessage[$this->ei]['type'] = 'error';
						$this->globalmessage[$this->ei]['long'] = '<p>' . esc_html__('Adding your latest save to the revisions table failed.', 'microthemer') . '</p>';
					} elseif ($preset == 'json-decode'){
						$this->globalmessage[++$this->ei]['short'] = __('Decode json error', 'microthemer');
						$this->globalmessage[$this->ei]['type'] = 'error';
						$this->globalmessage[$this->ei]['long'] = '<p>' . sprintf(esc_html__('WordPress was not able to convert %s into a usable format.', 'microthemer'), $this->root_rel($vars['json_file']) ) . '</p>
<p>JSON Error code: '. $this->json_last_error() . '</p>';

						//wp_die('<pre>'.$this->globalmessage[++$this->ei].'</pre>');

					}

				} else {
					$this->globalmessage[++$this->ei]['short'] = $short;
					$this->globalmessage[$this->ei]['type'] = $type;
					$this->globalmessage[$this->ei]['long'] = $long;
				}
			}

			function json_last_error(){
				if (function_exists('json_last_error')){
					return json_last_error();
				}

				return '';
			}

			// save ajax-generated global msg in db for showing on next page load
			function cache_global_msg(){
				$pref_array = array();
				$pref_array['returned_ajax_msg'] = $this->globalmessage;
				$pref_array['returned_ajax_msg_seen'] = 0;
				$this->savePreferences($pref_array);
			}

			// display the logs
			function display_log(){

				// if the page is reloading after an ajax request, we may have unseen status messages to show - merge the two
				if (!empty($this->preferences['returned_ajax_msg']) and !$this->preferences['returned_ajax_msg_seen']){
					$cached_global = $this->preferences['returned_ajax_msg'];
					if (is_array($this->globalmessage)){
						$this->globalmessage = array_unique(
							array_merge($this->globalmessage, $cached_global),
							SORT_REGULAR
						);
					} else {
						$this->globalmessage = $cached_global;
					}
					// clear the cached message as it is beign shown
					$pref_array['returned_ajax_msg'] = '';
					$pref_array['returned_ajax_msg_seen'] = 1;
					$this->savePreferences($pref_array);
				}
				$html = '';
				if (!empty($this->globalmessage)) {
					$html.= '<ul class="logs">'; // so 'loading WP site' msg doesn't overwrite
					foreach ($this->globalmessage as $key => $log) {
						if ($log['type'] == 'dev-notice'){
							continue;
						}
						$html .= $this->display_log_item($log['type'], $log, $key);
					}
					$html .= '</ul><span id="data-msg-pending" rel="1"></span>';
				} else {
					$html.= '<ul class="logs"></ul><span id="data-msg-pending" rel="0"></span>';
				}
				return $html;
			}

			// display log item - used as template so need as function to keep html consistent
			function display_log_item($type, $log, $key, $id = ''){
				$html = '
				<li '.$id.' class="tvr-'.$type.' tvr-message row-'.($key+1).'">
					<span class="short">'.$log['short'].'</span>
					<div class="long">'.$log['long'].'</div>
				</li>';
				return $html;
			}

			// circumvent max_input_vars by passing one serialised input that can be unpacked with this function
			function my_parse_str($string, &$result) {
				if($string==='') return false;
				$result = array();
				// find the pairs "name=value"
				$pairs = explode('&', $string);
				foreach ($pairs as $pair) {
					// use the original parse_str() on each element
					parse_str($pair, $params);
					$k=key($params);
					if(!isset($result[$k])) {
						$result+=$params;
					}
					else {
						if (is_array($result[$k])){
							//echo '<pre>key:'. $k . "\n";
							//echo 'params:';
							//print_r($params);
							//$result[$k]+=$params[$k];
							$result[$k] = $this->array_merge_recursive_distinct($result[$k], $params[$k]);
							// 'result:';
							//print_r($result);
							//echo '</pre>';
						}
					} //
					//else $result[$k]+=$params[$k];
				}
				return true;
			}

			// better recursive array merge function listed on the function's PHP page
			function array_merge_recursive_distinct ( array &$array1, array &$array2 ){
				$merged = $array1;
				foreach ( $array2 as $key => &$value )
				{
					if ( is_array ( $value ) && isset ( $merged [$key] ) && is_array ( $merged [$key] ) )
					{
						$merged [$key] = $this->array_merge_recursive_distinct ( $merged [$key], $value );
					}
					else
					{
						$merged [$key] = $value;
					}
				}

				return $merged;
			}

			// if global !important preference changes MT needs to do full recompile
			function preference_settings_changed($keys, $orig, $new){

			    foreach($keys as $key){
			        if (intval($orig[$key]) !== intval($new[$key])){
			            return true;
                    }
                }

			    return false;
            }

			// process preferences form
			function process_preferences_form(){

				$pref_array = $this->deep_unescape($_POST['tvr_preferences'], 0, 1, 1);
				$pref_array['num_saves'] = ++$this->preferences['num_saves'];

				// CSS units need saving in a different way (as my_props is more than just css units)
				$pref_array = $this->update_default_css_units($pref_array);

				// update g_url_with_subsets as manual subset param may have changed
				$pref_array['g_url_with_subsets'] =
					$this->g_url_with_subsets(false, false, $pref_array['gfont_subset']);

				// if they changed !important or SCSS settings do full recompile
				if ($this->preference_settings_changed(['css_important', 'allow_scss', 'server_scss'],
					$this->preferences, $_POST['tvr_preferences'])){
					$pref_array['manual_recompile_all_css'] = 1;
				}

				if ($this->savePreferences($pref_array)) {

					$this->log(
						esc_html__('Preferences saved', 'microthemer'),
						'<p>' . esc_html__('Your Microthemer preferences have been successfully updated.', 'microthemer') . '</p>',
						'notice'
					);

					// the admin bar shortcut needs to be applied here else it will only show on next page load
					if (!empty($this->preferences['admin_bar_shortcut'])) {
						add_action( 'admin_bar_menu', array(&$this, 'custom_toolbar_link'), 999999);
					} else {
						remove_action( 'admin_bar_menu', array(&$this, 'custom_toolbar_link'), 999999 );
					}
				}

				// save last message in database so that it can be displayed on page reload (just once)
				$this->cache_global_msg();
			}

			// update the preferences array with the new units when the user saves the preferences
			function update_default_css_units($pref_array){
				// cache the posted css units
				$new_css_units = $pref_array['new_css_units'];
				// then discard as junk
				unset($pref_array['new_css_units']);
				// update the existing my_props array
				$pref_array['my_props'] = $this->update_my_prop_default_units($new_css_units);
				return $pref_array;
			}

			// process posted zip file (do this on manage and single hence wrapped in a funciton )
			function process_uploaded_zip() {
				if ($_FILES['upload_micro']['error'] == 0) {
					$this->handle_zip_package();
				}
				// there was an error - save in global message
				else {
					$this->log_file_upload_error($_FILES['upload_micro']['error']);
				}
			}

			/*// &preview= and ?preview= cause problems - strip
			function strip_preview_params($url){
				//$url = explode('preview=', $url); // which didn't support regex (for e.g. elementor)
				$url = preg_split('/(?:elementor-)?preview=/', $url, -1);
				$url = rtrim($url[0], '?&');
				return $url;
			}*/

            // check if we're on the demo site
			function is_demo_site(){
				return strpos($this->site_url, 'livedemo.themeover') !== false;
			}

			// prevent errors when admin or frontend doesn't use SSL, but the other does
			function ensure_iframe_protocol_matches_admin(){

			    $preview_url = $this->preferences['preview_url'];
				$preview_plain = strpos($preview_url,'http:') !== false;
			    $admin_ssl = tvr_common::get_protocol() === 'https://';

			    $update = false;

                // SSL alteration
			    if ($admin_ssl and $preview_plain){
	                $preview_url = str_replace('http:', 'https:', $preview_url);
	                $update = true;
                }

                // maybe strip Oxygen template URL


                if ($update){
	                $this->savePreferences(array('preview_url' => $preview_url));
                }

			}

			// update the iframe preview url
			function maybe_set_preview_url($nonce_key = false){

				// update preview url in DB
				$url = strip_tags(rawurldecode($_GET['mt_preview_url']));

				$pref_array['preview_url'] = tvr_common::strip_page_builder_and_other_params($url);

				// path won't be set if this is triggered after user clicked WP Toolbar MT link
				if (!empty($_GET['mt_preview_path'])){

					// get path and strip builder and other params
					$path = strip_tags(rawurldecode($_GET['mt_preview_path']));
					$path = tvr_common::strip_page_builder_and_other_params($path);

					//wp_die('$path: '.$_GET['mt_preview_path']);

					$label = isset($_GET['mt_path_label'])
						? strip_tags(rawurldecode($_GET['mt_path_label']))
						: false;
					$item_id = isset($_GET['mt_item_id'])
						? strip_tags(rawurldecode($_GET['mt_item_id']))
						: false;

					// if the preview URL is 1, we should use the site_url with the path
					// this is used on the live demo
					if (intval($url) === 1){
						$pref_array['preview_url'] = untrailingslashit($this->site_url).$path;
					}

					// remove from array if already exists (as we be prepended at start)
					$existingKey = $this->in_array_column($path, $this->preferences['custom_paths'], 'value');
					if ($existingKey){
						array_splice($this->preferences['custom_paths'], $existingKey,1);
					}

					// insert url at start of custom_paths array
					array_unshift($this->preferences['custom_paths'], array(
						'value' => $path,
						'label' => $label,
						'item_id' => $item_id
					));

					// ensure only x items, and that paths are unique
					$i = 0;
					$paths_done = array();
					foreach ($this->preferences['custom_paths'] as $key => $pathOrObj){

						$custom_path = is_array($pathOrObj) ? $pathOrObj['value'] : $pathOrObj;

						// add custom path if unique
						if (empty($paths_done[$custom_path])){
							$pref_array['custom_paths'][] = $pathOrObj;
							$paths_done[$custom_path] = 1;
							++$i;
						}

						if ($i >= 8) {
							break;
						}
					}
				}

				$this->savePreferences($pref_array);

			}

			// check an array based on nested property with option to search base array for value
			function in_array_column($item, $array, $column = false, $checkFlat = false){

			    if ( $foundWithColumn = array_search( $item, array_column($array, $column) ) ){
			        return $foundWithColumn;
                }

			    if ($checkFlat){
				    return array_search($item, $array);
                }

			    return false;
            }

            function microthemer_ajax_actions(){

	            if ( !current_user_can('administrator') ){
		            wp_die( 'Access denied' );
	            }

	            // simple ajax operations that can be executed from any page, pointing to ui page
	            if (isset($_GET['mcth_simple_ajax'])) {

		            check_ajax_referer( 'mcth_simple_ajax', '_wpnonce' );

		            // workspace preferences
		            if (isset($_POST['tvr_preferences_form'])) {
			            $this->process_preferences_form();
			            wp_die();
		            }

		            // if it's an options save request
		            if (isset($_GET['mt_action']) and $_GET['mt_action'] === 'mt_save_interface') {

			            // remove slashes and custom escaping so that DB data is clean
			            $this->serialised_post =
				            $this->deep_unescape($_POST, 1, 1, 1);

			            if (!empty($this->serialised_post['serialise'])){
				            $this->serialised_post['tvr_mcth'] = $this->json('decode', $this->serialised_post['tvr_mcth']);
                            //json_decode($this->serialised_post['tvr_mcth'], true);
				            /*echo 'show_me from tvr_mcth: <pre> ';
							print_r($_POST);
							echo '</pre>';*/
			            }

			            // bail if no save data was successfully decoded
			            if (empty($this->serialised_post['tvr_mcth'])) {
				            return false;
			            }

			            // strange Kinsta error prompted this but might have been a fleeting issue
			            $partial = !empty($this->serialised_post['partial_data'])
				            ? $this->serialised_post['partial_data']
				            : false;
			            $last_save_time = !empty($this->serialised_post['last_save_time'])
				            ? $this->serialised_post['last_save_time']
				            : false;


			            /*$debug = true;
						if ($debug){
							echo 'show_me from ajax save (before): <pre> ';
							print_r($this->serialised_post);
							echo '</pre>';
						}*/

			            // save settings in DB
			            if (!$this->saveUiOptions2(
				            $this->serialised_post['tvr_mcth'],
				            $partial,
				            $last_save_time
			            )) {

				            // save error
				            $this->log(
					            esc_html__('Settings failed to save', 'microthemer'),
					            '<p>' . esc_html__('Saving your setting to the database failed.', 'microthemer') . '</p>'
				            );
			            }

			            // save successful
			            else {

				            $saveOk = esc_html__('Settings saved', 'microthemer');
				            $this->log(
					            $saveOk,
					            '<p>' . esc_html__('The UI interface settings were successfully saved.', 'microthemer') . '</p>',
					            'notice'
				            );

				            $new_select_option = '';

				            // check if settings need to be exported to a design pack
				            if (!empty($this->serialised_post['export_to_pack'])
				                && $this->serialised_post['export_to_pack'] == 1) {
					            $theme = htmlentities($this->serialised_post['export_pack_name']);
					            $context = 'existing';
					            $do_option_insert = false;
					            if ($this->serialised_post['new_pack'] == 1){
						            $context = 'new';
						            $do_option_insert = true;
					            }
					            // function return sanitised theme name
					            $theme = $this->update_json_file($theme, $context);
					            // save new sanitised theme in span for updating select menu via jQuery
					            if ($do_option_insert) {
						            $new_select_option = $theme;
					            }
					            //$user_action.= sprintf( esc_html__(' & Export to %s', 'microthemer'), '<i>'. $this->readable_name($theme). '</i>');
				            }

				            // else its a standard save of custom settings
				            else {
					            $theme = 'customised';
					            //$user_action.= esc_html__(' (regular)', 'microthemer');
				            }

				            // update active-styles.css
				            $this->update_active_styles2($theme);


				            // update the revisions DB field
				            if (!$this->updateRevisions($this->options, json_encode($this->serialised_post['user_action']))) {
					            $this->log('','','error', 'revisions');
				            }
			            }



			            //echo 'carrots!';
			            //wp_die();

			            // return the globalmessage and then kill the program - this action is always requested via ajax
			            // also fullUIData as an interim way to keep JS ui data up to date (post V5 will have new system with less http)
			            $html = '<div id="microthemer-notice">' . $this->display_log() . '<div class="script-feedback">
								
									<span id="outdated-tab-issue">'.$this->outdatedTabIssue.'</span>
									<span id="returned-save-time">'.$this->options['non_section']['last_save_time'].'</span>
								</div>
							</div>';

			            // we're returning a JSON obejct here, the HTML is added as a property of the object
			            $response = array(
				            //'prefs' => $this->preferences,
				            'html'=> $html,
				            'outdatedTab'=> $this->outdatedTabIssue,
				            'outdatedTabDebug'=> $this->outdatedTabDebug,
				            'returnedSaveTime'=> $this->options['non_section']['last_save_time'],
				            'exportName' => $new_select_option
				            //'uiData'=> $this->options
				            //'uiData'=> array()
			            );

			            echo json_encode($response); //$html;

			            wp_die();
		            }

		            // if it's a silent save request for updating ui options (e.g. last viewed selector)
		            if (isset($_GET['mt_action']) and $_GET['mt_action'] == 'mt_silent_save_interface') {
			            $savePackage = $this->deep_unescape($_POST['savePackage'], 1, 1, 1);
			            /*echo 'show_me from ajax save (before): <pre> ';
						print_r($savePackage);
						echo '</pre>';
						return false;*/
			            $this->apply_save_package($savePackage, $this->options);
			            update_option($this->optionsName, $this->options);
			            wp_die();
		            }


		            // $this->get_site_pages();
		            if (isset($_GET['get_site_pages'])) {

			            // MT posts search should only check title or slug so we get precise results (that appear in top 10 limit)
			            // And because MT will filter out results with no title match on JS side anyway
			            add_filter( 'posts_search', array(&$this, 'search_by_title_or_slug'), 10, 2 );

			            $searchTerm = isset($_GET['search_term'])
				            ? htmlentities($_GET['search_term'])
				            : null;

			            echo json_encode($this->get_site_pages($searchTerm));

			            wp_die();
		            }

		            // ajax - load selectors and/or selector options
		            /*if ( isset($_GET['mt_action']) and $_GET['mt_action'] == 'tvr_microthemer_ui_load_styles') {
						//check_admin_referer('tvr_microthemer_ui_load_styles');
						$section_name = strip_tags($_GET['tvr_load_section']);
						$css_selector = strip_tags($_GET['tvr_load_selector']);
						$array = $this->options[$section_name][$css_selector];
						echo '<div id="tmp-wrap">';
						echo $this->all_option_groups_html($section_name, $css_selector, $array);
						echo '</div>';
						// output pulled data to debug file
						if ($this->debug_pulled_data){
							$debug_file = $this->debug_dir . 'debug-pulled-data.txt';
							$write_file = @fopen($debug_file, 'w');
							$data = '';
							$data.= esc_html__('Custom debug output', 'microthemer') . "\n\n";
							$data.= $this->debug_custom;
							$data.= "\n\n" . esc_html__('Last pulled data', 'microthemer') . "\n\n";
							$data.= print_r($this->options[$section_name][$css_selector], true);
							fwrite($write_file, $data);
							fclose($write_file);
						}
						// kill the program - this action is always requested via ajax. no message necessary
						wp_die();
					}*/

		            // ajax - toggle draft mode
		            if (isset($_GET['draft_mode'])) {

			            $pref_array['draft_mode'] = intval($_GET['draft_mode']);

			            // ned to get current user id again as $this->current_user_id won't be set in ajax request
			            $current_user_id = get_current_user_id();

			            // save current user in array
			            if ($pref_array['draft_mode']){
				            $pref_array['draft_mode_uids'][$current_user_id] = $current_user_id;
			            } else {
				            // reset if draft mode is off
				            $pref_array['draft_mode_uids'] = array();
			            }
			            $this->savePreferences($pref_array);
			            wp_die();
		            }

		            // selname_code_synced
		            if (isset($_GET['load_sass_import'])) {

			            $path = htmlentities(rawurldecode($_GET['load_sass_import']));
			            $imports = $this->get_sass_import_paths('@import "'.$path.'";', '');
			            $content = false;

			            if ($imports){
				            $content = $this->recursively_scan_import_files(
					            array(
						            'import' => $imports[0]
					            )
				            );
			            }

			            $response = array(
				            'error' => !$content,
				            'content' => $content
			            );

			            echo json_encode($response);
			            wp_die();
		            }

		            // selname_code_synced
		            if (isset($_GET['selname_code_synced'])) {
			            $pref_array['selname_code_synced'] = intval($_GET['selname_code_synced']);
			            $this->savePreferences($pref_array);
			            wp_die();
		            }

		            // code_manual_resize
		            if (isset($_GET['code_manual_resize'])) {
			            $pref_array['code_manual_resize'] = intval($_GET['code_manual_resize']);
			            $this->savePreferences($pref_array);
			            wp_die();
		            }

		            // ace full page html
		            if (isset($_GET['wizard_expanded'])) {
			            $pref_array['wizard_expanded'] = intval($_GET['wizard_expanded']);
			            $this->savePreferences($pref_array);
			            wp_die();
		            }

		            // remember the state of the extra icons in the selectors menu
		            if (isset($_GET['show_extra_actions'])) {
			            $pref_array['show_extra_actions'] = intval($_GET['show_extra_actions']);
			            $this->savePreferences($pref_array);
			            wp_die();
		            }

		            // remember the grid highlight status
		            if (isset($_GET['grid_highlight'])) {
			            $pref_array['grid_highlight'] = intval($_GET['grid_highlight']);
			            $this->savePreferences($pref_array);
			            wp_die();
		            }

		            // remember show_sampled_values
		            if (isset($_GET['show_sampled_values'])) {
			            $pref_array['show_sampled_values'] = intval($_GET['show_sampled_values']);
			            $this->savePreferences($pref_array);
			            wp_die();
		            }

		            // remember show_sampled_variables
		            if (isset($_GET['show_sampled_variables'])) {
			            $pref_array['show_sampled_variables'] = intval($_GET['show_sampled_variables']);
			            $this->savePreferences($pref_array);
			            wp_die();
		            }

		            // mt_color_variables_css
		            if (isset($_POST['mt_color_variables_css'])) {
			            $pref_array['mt_color_variables_css'] = strip_tags($_POST['mt_color_variables_css']);
			            $this->savePreferences($pref_array);
			            wp_die();
		            }

		            // wizard footer/right dock
		            if (isset($_GET['dock_wizard_right'])) {
			            $pref_array['dock_wizard_right'] = intval($_GET['dock_wizard_right']);
			            $this->savePreferences($pref_array);
			            wp_die();
		            }

		            // instant hover inspection
		            if (isset($_GET['hover_inspect'])) {
			            $pref_array['hover_inspect'] = intval($_GET['hover_inspect']);
			            $this->savePreferences($pref_array);
			            wp_die();
		            }

		            // ajax - update preview url after page navigation
		            if (isset($_GET['mt_preview_url'])) {
			            $this->maybe_set_preview_url();
			            // kill the program - this action is always requested via ajax. no message necessary
			            wp_die();
		            }

		            // ajax - update preview url after page navigation
		            if (isset($_GET['import_css_url'])) {
			            // update view_import_stylesheets list with possible new stylesheet
			            $this->update_css_import_urls(strip_tags(rawurldecode($_GET['import_css_url'])));
			            wp_die();
		            }

		            // code editor focus
		            if (isset($_GET['show_code_editor'])) {
			            $pref_array = array();
			            $pref_array['show_code_editor'] = intval($_GET['show_code_editor']);
			            $this->savePreferences($pref_array);
			            // kill the program - this action is always requested via ajax. no message necessary
			            wp_die();
		            }

		            // ruler show/hide
		            if (isset($_GET['show_rulers'])) {
			            $pref_array = array();
			            $pref_array['show_rulers'] = intval($_GET['show_rulers']);
			            $this->savePreferences($pref_array);
			            // kill the program - this action is always requested via ajax. no message necessary
			            wp_die();
		            }

		            // specificity preference
		            if (isset($_GET['specificity_preference'])) {
			            $pref_array = array();
			            $pref_array['specificity_preference'] = intval($_GET['specificity_preference']);
			            $this->savePreferences($pref_array);
			            // kill the program - this action is always requested via ajax. no message necessary
			            wp_die();
		            }

		            // dock editor left
		            if (isset($_GET['sidebar_size'])) {
			            $pref_array = array();
			            $pref_array['sidebar_size'] = intval($_GET['sidebar_size']);
			            $pref_array['sidebar_size_category'] = htmlentities($_GET['sidebar_size']);
			            $this->savePreferences($pref_array);
			            // kill the program - this action is always requested via ajax. no message necessary
			            wp_die();
		            }

		            // dock editor left
		            if (isset($_GET['dock_editor_left'])) {
			            $pref_array = array();
			            $pref_array['dock_editor_left'] = intval($_GET['dock_editor_left']);
			            $this->savePreferences($pref_array);
			            // kill the program - this action is always requested via ajax. no message necessary
			            wp_die();
		            }

		            // dock editor left
		            if (isset($_GET['dock_options_left'])) {
			            $pref_array = array();
			            $pref_array['dock_options_left'] = intval($_GET['dock_options_left']);
			            $this->savePreferences($pref_array);
			            // kill the program - this action is always requested via ajax. no message necessary
			            wp_die();
		            }

		            // detach preview -
		            if (isset($_GET['detach_preview'])) {
			            $pref_array = array();
			            $pref_array['detach_preview'] = intval($_GET['detach_preview']);
			            $this->savePreferences($pref_array);
			            // kill the program - this action is always requested via ajax. no message necessary
			            wp_die();
		            }

		            // ruler show/hide
		            if (isset($_GET['show_text_labels'])) {
			            $pref_array = array();
			            $pref_array['show_text_labels'] = intval($_GET['show_text_labels']);
			            $this->savePreferences($pref_array);
			            wp_die();
		            }

		            // show/hide whole interface
		            if (isset($_GET['show_interface'])) {
			            $pref_array = array();
			            $pref_array['show_interface'] = intval($_GET['show_interface']);
			            $this->savePreferences($pref_array);
			            // kill the program - this action is always requested via ajax. no message necessary
			            wp_die();
		            }

		            // active MQ tab
		            if (isset($_GET['manual_recompile_all_css'])) {
			            $pref_array = array();
			            $pref_array['manual_recompile_all_css'] = htmlentities($_GET['manual_recompile_all_css']);
			            $this->savePreferences($pref_array);
			            // kill the program - this action is always requested via ajax. no message necessary
			            wp_die();
		            }

		            // active MQ tab
		            if (isset($_GET['mq_device_focus'])) {
			            $pref_array = array();
			            $pref_array['mq_device_focus'] = htmlentities($_GET['mq_device_focus']);
			            $this->savePreferences($pref_array);
			            // kill the program - this action is always requested via ajax. no message necessary
			            wp_die();
		            }

		            // active MQ tab
		            if (isset($_GET['rev_save_status'])) {
			            $this->updateRevisionSaveStatus(
				            intval($_GET['rev_id']),
				            intval($_GET['rev_save_status'])
			            );
			            wp_die();
		            }

		            // active CSS tab
		            if (isset($_GET['css_focus'])) {
			            $pref_array = array();
			            $pref_array['css_focus'] = htmlentities($_GET['css_focus']);
			            $this->savePreferences($pref_array);
			            // kill the program - this action is always requested via ajax. no message necessary
			            wp_die();
		            }

		            // update_default_unit
                    if (isset($_GET['update_default_unit'])) {
	                    $data = json_decode( stripslashes($_POST['tvr_serialized_data']), true );
	                    $this->preferences['my_props'][$data['group']]['pg_props'][$data['prop']]['default_unit'] = $data['unit'];
	                    $pref_array['my_props'] = $this->preferences['my_props'];
	                    $this->savePreferences($pref_array);

	                    wp_die();
                    }

		            // MT may update custom paths array via JS (e.g. path clear) and then post full array to replace current
		            if (isset($_GET['update_custom_paths'])) {
			            $pref_array['custom_paths'] = json_decode( stripslashes($_POST['tvr_serialized_data']), true );
			            $this->savePreferences($pref_array);
		            }

		            // update suggested values
		            if (isset($_GET['update_sug_values'])) {

			            $pref_array = array();
			            $root_cat = $_GET['update_sug_values'];

			            // tap into WordPress native JSON functions
			            /*if( !class_exists('Moxiecode_JSON') ) {
							require_once($this->thisplugindir . 'includes/class-json.php');
						}

						$json_object = new Moxiecode_JSON();*/

			            $data = json_decode( stripslashes($_POST['tvr_serialized_data']), true );

			            // if we're setting suggested values for all properties
			            if ($root_cat == 'all'){
				            $this->preferences['my_props']['sug_values'] = $data['sug_values'];
				            $this->preferences['my_props']['sug_variables'] = $data['sug_variables'];
			            }  elseif ($root_cat == 'synced_set') {
				            // a set of fields in one go e.g. padding
				            $this->preferences['my_props']['sug_values'] =
					            array_merge($this->preferences['my_props']['sug_values'], $data['synced_set']);
			            } else {
				            // just setting suggestions for a type of property e.g. site_colors

				            if (!empty($data['specific'])){
					            $this->preferences['my_props']['sug_values'][$root_cat] = $data['specific'];
				            }
			            }

			            // update variable if passed
			            if (isset($data['sug_variables'])){
				            $this->preferences['my_props']['sug_variables'] = $data['sug_variables'];
				            $pref_array['default_sug_variables_set'] = 1;
			            }

			            $pref_array['default_sug_values_set'] = 1;
			            $pref_array['my_props'] = $this->preferences['my_props'];
			            $this->savePreferences($pref_array);

			            //echo '<pre>posted array: '.print_r($data, true).'</pre>';

			            // kill the program - this action is always requested via ajax. no message necessary
			            wp_die();
		            }

		            // save google/typekit fonts config
		            if (isset($_GET['save_font_config'])) {

			            // tap into WordPress native JSON functions
			            /*if( !class_exists('Moxiecode_JSON') ) {
							require_once($this->thisplugindir . 'includes/class-json.php');
						}

						$json_object = new Moxiecode_JSON();*/

			            $data = json_decode( stripslashes($_POST['tvr_serialized_data']), true );
			            $pref_array = array();
			            $key = $_GET['save_font_config'] == 'google' ? 'google' : 'typekit';
			            $pref_array['font_config'][$key] = $data;

			            $this->savePreferences($pref_array);

			            //echo '<pre>posted array: '.print_r($data, true).'</pre>';

			            // kill the program - this action is always requested via ajax. no message necessary
			            wp_die();
		            }

		            // active property group
		            if (isset($_GET['pg_focus'])) {
			            $pref_array = array();
			            $pref_array['pg_focus'] = htmlentities($_GET['pg_focus']);
			            $this->savePreferences($pref_array);
			            // kill the program - this action is always requested via ajax. no message necessary
			            wp_die();
		            }

		            // active generated_css_focus
		            if (isset($_GET['generated_css_focus'])) {
			            $pref_array = array();
			            $pref_array['generated_css_focus'] = intval($_GET['generated_css_focus']);
			            $this->savePreferences($pref_array);
			            // kill the program - this action is always requested via ajax. no message necessary
			            wp_die();
		            }

		            // remember selector wizard tab
		            if (isset($_GET['adv_wizard_tab'])) {
			            $pref_array = array();
			            $pref_array['adv_wizard_tab'] = htmlentities($_GET['adv_wizard_tab']);
			            $this->savePreferences($pref_array);
			            // kill the program - this action is always requested via ajax. no message necessary
			            wp_die();
		            }

		            // remember selector wizard tab
		            if (isset($_GET['grid_focus'])) {
			            $pref_array = array();
			            $pref_array['grid_focus'] = htmlentities($_GET['grid_focus']);
			            $this->savePreferences($pref_array);
			            // kill the program - this action is always requested via ajax. no message necessary
			            wp_die();
		            }

		            // remember transform tab
		            if (isset($_GET['transform_focus'])) {
			            $pref_array = array();
			            $pref_array['transform_focus'] = htmlentities($_GET['transform_focus']);
			            $this->savePreferences($pref_array);
			            // kill the program - this action is always requested via ajax. no message necessary
			            wp_die();
		            }

		            // last viewed selector
		            /*if (isset($_GET['last_viewed_selector'])) {
						$pref_array = array();
						$pref_array['last_viewed_selector'] = htmlentities($_GET['last_viewed_selector']);
						$this->savePreferences($pref_array);
						// kill the program - this action is always requested via ajax. no message necessary
						wp_die();
					}*/

		            // download pack
		            if (!empty($_GET['mt_action']) and
		                $_GET['mt_action'] == 'tvr_download_pack') {
			            if (!empty($_GET['dir_name'])) {
				            // first of all, copy any images from the media library
				            $pack = $_GET['dir_name'];
				            $dir = $this->micro_root_dir . $pack;
				            $json_config_file = $dir . '/config.json';
				            if ($library_images = $this->get_linked_library_images($json_config_file)){
					            foreach($library_images as $key => $path){
						            // strip site_url rather than home_url in this case coz using with ABSPATH
						            $root_rel_path = $this->root_rel($path, false, true, true);
						            $basename = basename($root_rel_path);
						            $orig = rtrim(ABSPATH,"/"). $root_rel_path;
						            $img_paths[] = $new = $dir . '/' . $basename;
						            $replacements[$path] = $this->root_rel(
							            $this->micro_root_url . $pack . '/' . $basename, false, true
						            );
						            if (!copy($orig, $new)){
							            $this->log(
								            esc_html__('Library image not downloaded', 'microthemer'),
								            '<p>' . sprintf(esc_html__('%s could not be copied to the zip download file', 'microthemer'), $root_rel_path) . '</p>',
								            'warning'
							            );
							            $download_status = 0;
						            }
					            }
					            // cache original config file data
					            $orig_json_data = $this->get_file_data($json_config_file);

					            // update image paths in config.json for zip only (we'll restore shortly)
					            $this->replace_json_paths($json_config_file, $replacements, $orig_json_data);
				            }

				            // now zip the contents
				            if (
				            $this->create_zip(
					            $this->micro_root_dir,
					            $pack,
					            $this->thisplugindir.'zip-exports/')
				            ){
					            $download_status = 1;
				            } else {
					            $download_status = 0;
				            }
			            }
			            else {
				            $download_status = 0;
			            }
			            // delete any media library images temporarily copied to the directory
			            if ($library_images){
				            // restore orgin config.json paths
				            $this->write_file($json_config_file, $orig_json_data);
				            // delete images
				            foreach ($img_paths as $key => $path){
					            if (!unlink($path)){
						            $this->log(
							            esc_html__('Temporary image could not be deleted.', 'microthemer'),
							            '<p>' . sprintf( esc_html__('%s was temporarily copied to your theme pack before download but could not be deleted after the download operation finished.', 'microthemer'), $this->root_rel($root_rel_path) ) . '</p>',
							            'warning'
						            );
					            }
				            }
			            }
			            echo '
							<div id="microthemer-notice">'
			                 . $this->display_log() . '
								<span id="download-status" rel="'.$download_status.'"></span>
							</div>';
			            wp_die();
		            }

		            // delete pack
		            if (!empty($_GET['mt_action']) and
		                $_GET['mt_action'] == 'tvr_delete_micro_theme') {
			            if (!empty($_GET['dir_name']) and $this->tvr_delete_micro_theme($_GET['dir_name'])){
				            $delete_status = 1;
			            } else {
				            $delete_status = 0;
			            }
			            echo '
							<div id="microthemer-notice">'
			                 . $this->display_log() . '
								<span id="delete-status" rel="'.$delete_status.'"></span>
							</div>';
			            wp_die();
		            }

		            // download remote css file
		            if (!empty($_GET['mt_action']) and
		                $_GET['mt_action'] == 'tvr_get_remote_css') {
			            $config['allowed_ext'] = array('css');
			            $r = $this->get_safe_url(rawurldecode($_GET['url']), $config);
			            echo '
							<div id="microthemer-notice">'
			                 . $this->display_log() . '
								<div id="remote-css">'.(!empty($r['content']) ? $r['content'] : 0).'</div>
							</div>';
			            wp_die();
		            }

		            // if it's an import request
		            if ( !empty($_POST['import_pack_or_css']) ){

			            // if importing raw CSS
			            if (!empty($_POST['stylesheet_import_json'])){

				            $context = esc_attr__('Raw CSS', 'microthemer');
				            $json_str = stripslashes($_POST['stylesheet_import_json']);
				            $p = $_POST['tvr_preferences'];

				            // checkbox values must be explicitly evaluated
				            $p['css_imp_only_selected'] = !empty($p['css_imp_only_selected']) ? 1 : 0;

				            // handle remote image import. See plugins that do this:
				            // https://premium.wpmudev.org/blog/download-remote-images-into-wordpress/
				            if (!empty($_POST['get_remote_images'])){

					            $r_images = explode('|', $_POST['get_remote_images']);
					            $do_copy = false;
					            $remote_images = array();
					            $all_r = array();
					            foreach ($r_images as $i => $both){
						            $tmp = explode(',', $both);
						            $path_in_data = $tmp[0];
						            $full_url = $tmp[1];
						            // save to temp dir first
						            $r = $this->get_safe_url($full_url, array(
							            'allowed_ext' => array('jpg', 'jpeg', 'gif', 'png', 'svg'),
							            'tmp_file' => 1
						            ));

						            if ($r){
							            $remote_images[$path_in_data] = $r['tmp_file'];
							            $do_copy = true;
							            //$all_r[++$i] = $r;
						            }

					            }

					            // do image copy function
					            if ($do_copy){

						            $updated_json_str = $this->import_pack_images_to_library(
							            false,
							            'custom',
							            $json_str,
							            $remote_images
						            );

						            $json_str = $updated_json_str ? $updated_json_str : $json_str;
					            }

				            }

				            // load the json file
				            $this->load_json_file(false, 'custom', $context, $json_str);

				            // save the import preferences
				            $this->savePreferences($p);
			            }

			            // if importing an MT design pack
			            else {


				            $theme_name = sanitize_file_name(sanitize_title(htmlentities($_POST['import_from_pack_name'])));


				            $json_file = $this->micro_root_dir . $theme_name . '/config.json';

				            $context = $_POST['tvr_import_method'];

				            // import any background images that may need moving to the media library and update json
				            $this->import_pack_images_to_library($json_file, $theme_name);

				            // load the json file
				            $this->load_json_file($json_file, $theme_name, $context);

			            }

			            // signal that all selectors should be recompiled (to ensure latest data structure)
			            $this->update_preference('manual_recompile_all_css', 1);

			            // update the revisions DB field
			            if (!$this->updateRevisions($this->options, $this->json_format_ua(
				            'import-from-pack lg-icon',
				            esc_html__('Import', 'microthemer') . ' ('.$context.'):&nbsp;',
				            $this->readable_name($theme_name)
			            ))) {
				            $this->log('','','error', 'revisions');
			            }

			            // save last message in database so that it can be displayed on page reload (just once)
			            $this->cache_global_msg();
			            wp_die();
		            }



		            // if it's a reset request
                    elseif( isset($_GET['mt_action']) and $_GET['mt_action'] == 'tvr_ui_reset'){
			            if ($this->resetUiOptions()) {
				            $this->update_active_styles2('customised');
				            $item = esc_html__('Folders were reset', 'microthemer');
				            $this->log(
					            $item,
					            '<p>' . esc_html__('The default empty folders have been reset.', 'microthemer') . '</p>',
					            'notice'
				            );
				            // update the revisions DB field
				            if (!$this->updateRevisions($this->options, $this->json_format_ua(
					            'folder-reset lg-icon',
					            $item
				            ))) {
					            $this->log(
						            esc_html__('Revision failed to save', 'microthemer'),
						            '<p>' . esc_html__('The revisions table could not be updated.', 'microthemer') . '</p>',
						            'notice'
					            );
				            }
			            }
			            // save last message in database so that it can be displayed on page reload (just once)
			            $this->cache_global_msg();
			            wp_die();
		            }

		            // if it's a clear styles request
                    /*elseif(isset($_GET['mt_action']) and $_GET['mt_action'] == 'tvr_clear_styles'){
			            if ($this->clearUiOptions()) {
				            $this->update_active_styles2('customised');
				            $item = esc_html__('Styles were cleared', 'microthemer');
				            $this->log(
					            $item,
					            '<p>' . esc_html__('All styles were cleared, but your folders and selectors remain fully intact.', 'microthemer') . '</p>',
					            'notice'
				            );
				            // update the revisions DB field
				            if (!$this->updateRevisions($this->options, $item)) {
					            $this->log('', '', 'error', 'revisions');
				            }
			            }
			            // save last message in database so that it can be displayed on page reload (just once)
			            $this->cache_global_msg();
			            wp_die();
		            }*/

		            // if it's an email error report request
                    elseif(isset($_GET['mt_action']) and $_GET['mt_action'] == 'tvr_error_email'){
			            $body = "*** MICROTHEMER ERROR REPORT | ".date('d/m/Y h:i:s a', $this->time)." *** \n\n";
			            $body .= "PHP ERROR \n" . stripslashes($_POST['tvr_php_error']) . "\n\n";
			            $body .= "BROWSER INFO \n" . stripslashes($_POST['tvr_browser_info']) . "\n\n";
			            $body .= "SERIALISED POSTED DATA \n" . stripslashes($_POST['tvr_serialised_data']) . "\n\n";
			            // An error can occur EITHER when saving to DB OR creating the active-styles.css
			            // The php error line number will reveal this. If the latter is true, the DB data contains the posted data too (FYI)
			            $body .= "SERIALISED DATA IN DB \n" . serialize($this->options). "\n\n";
			            // write file to error-reports dir
			            $file_path = 'error-reports/error-'.date('Y-m-d').'.txt';
			            $error_file = $this->thisplugindir . $file_path;
			            $write_file = @fopen($error_file, 'w');
			            fwrite($write_file, $body);
			            fclose($write_file);
			            // Determine from email address. Try to use validated customer email. Don't contact if not Microthemer customer.
			            if ( !empty($this->preferences['buyer_email']) ) {
				            $from_email = $this->preferences['buyer_email'];
				            $body .= "MICROTHEMER CUSTOMER EMAIL \n" . $from_email;
			            }
			            else {
				            $from_email = get_option('admin_email');
			            }
			            // Try to send email (won't work on localhost)
			            $subject = 'Microthemer Error Report | ' . date('d/m/Y', $this->time);
			            $to = 'support@themeover.com';
			            $from = "Microthemer User <$from_email>";
			            $headers = "From: $from";
			            if(@mail($to,$subject,$body,$headers)) {
				            $this->log(
					            esc_html__('Email successfully sent', 'microthemer'),
					            '<p>' . esc_html__('Your error report was successfully emailed to Themeover. Thanks, this really does help.', 'microthemer') . '</p>',
					            'notice'
				            );
			            }
			            else {
				            $error_url = $this->thispluginurl . $file_path;
				            $this->log(
					            esc_html__('Report email failed', 'microthemer'),
					            '<p>' . esc_html__('Your error report email failed to send (are you on localhost?)', 'microthemer') . '</p>
								<p>' .
					            wp_kses(
						            sprintf(
							            __('Please email <a %1$s>this report</a> to %2$s', 'microthemer'),
							            'target="_blank" href="' .$error_url . '"',
							            '<a href="mailto:support@themeover.com">support@themeover.com</a>'
						            ),
						            array( 'a' => array( 'href' => array(), 'target' => array() ) )
					            )
					            . '</p>'
				            );
			            }
			            echo '
						<div id="microthemer-notice">'. $this->display_log() . '</div>';
			            wp_die();
		            }

		            // if it's a restore revision request
		            if(isset($_GET['mt_action']) and $_GET['mt_action'] == 'restore_rev'){
			            $rev_key = $_GET['tvr_rev'];
			            if ($this->restoreRevision($rev_key)) {
				            $item = esc_html__('Previous settings restored', 'microthemer');
				            $this->log(
					            $item,
					            '<p>' . esc_html__('Your settings were successfully restored from a previous save.', 'microthemer') . '</p>',
					            'notice'
				            );
				            $this->update_active_styles2('customised');
				            // update the revisions DB field
				            if (!$this->updateRevisions($this->options, $this->json_format_ua(
					            'display-revisions lg-icon',
					            $item
				            ))) {
					            $this->log('','','error', 'revisions');
				            }
			            }
			            else {
				            $this->log(
					            esc_html__('Settings restore failed', 'microthemer'),
					            '<p>' . esc_html__('Data could not be restored from a previous save.', 'microthemer') . '</p>'
				            );
			            }
			            // save last message in database so that it can be displayed on page reload (just once)
			            $this->cache_global_msg();
			            wp_die();
		            }

		            // if it's a get revision ajax request
                    elseif(isset($_GET['mt_action']) and $_GET['mt_action'] == 'get_revisions'){
			            echo '<div id="tmp-wrap">' . $this->getRevisions() . '</div>'; // outputs table
			            wp_die();
		            }


		            /* PREFERENCES FUNCTIONS MOVED TO MAIN UI */

		            // update the MQs
		            if (isset($_POST['tvr_media_queries_submit'])){

			            $orig_media_queries = $this->preferences['m_queries'];

			            // remove backslashes from $_POST
			            $_POST = $this->deep_unescape($_POST, 0, 1, 1);
			            // get the initial scale and default width for the "All Devices" tab
			            $pref_array['initial_scale'] = $_POST['tvr_preferences']['initial_scale'];
			            $pref_array['all_devices_default_width'] = $_POST['tvr_preferences']['all_devices_default_width'];
			            // reset default media queries if all empty
			            $action = '';
			            if (empty($_POST['tvr_preferences']['m_queries'])) {
				            $pref_array['m_queries'] = $this->default_m_queries;
				            $action = 'reset';
			            } else {
				            $pref_array['m_queries'] = $_POST['tvr_preferences']['m_queries'];
				            $action = 'update';
			            }

			            // are we merging/overwriting with a new media query set
			            if (!empty($_POST['tvr_preferences']['load_mq_set'])){
				            //print_r($this->mq_sets);
				            $action = 'load_set';
				            $new_set = $_POST['tvr_preferences']['load_mq_set'];
				            $new_mq_set = $this->mq_sets[$new_set];
				            $pref_array['overwrite_existing_mqs'] = $_POST['tvr_preferences']['overwrite_existing_mqs'];
				            if (!empty($pref_array['overwrite_existing_mqs'])){
					            $pref_array['m_queries'] = $new_mq_set;
					            $load_action = esc_html__('replaced', 'microthemer');
				            } else {
					            $pref_array['m_queries'] = array_merge($pref_array['m_queries'], $new_mq_set);
					            $load_action = esc_html__('was merged with', 'microthemer');
				            }
			            }

			            // format media query min/max width (height later) and units
			            $pref_array['m_queries'] = $this->mq_min_max($pref_array);

			            // save and preset message
			            $pref_array['num_saves'] = ++$this->preferences['num_saves'];

			            if ($this->savePreferences($pref_array)) {

				            switch ($action) {
					            case 'reset':
						            $this->log(
							            esc_html__('Media queries were reset', 'microthemer'),
							            '<p>' . esc_html__('The default media queries were successfully reset.', 'microthemer') . '</p>',
							            'notice'
						            );
						            break;
					            case 'update':
						            $this->log(
							            esc_html__('Media queries were updated', 'microthemer'),
							            '<p>' . esc_html__('Your media queries were successfully updated.', 'microthemer') . '</p>',
							            'notice'
						            );
						            break;
					            case 'load_set':
						            $this->log(
							            esc_html__('Media query set loaded', 'microthemer'),
							            '<p>' . sprintf( esc_html__('A new media query set %1$s your existing media queries: %2$s', 'microthemer'), $load_action, htmlentities($_POST['tvr_preferences']['load_mq_set']) ) . '</p>',
							            'notice'
						            );
						            break;
				            }

				            // if the user deleted a media query, ensure data is cleaned from the ui data
				            $this->clean_deleted_media_queries($orig_media_queries, $pref_array['m_queries']);

			            }
			            // save last message in database so that it can be displayed on page reload (just once)
			            $this->cache_global_msg();
			            wp_die();
		            }

		            // update the enqueued JS files
		            if (isset($_POST['mt_enqueue_js_submit'])){
			            // remove backslashes from $_POST
			            $_POST = $this->deep_unescape($_POST, 0, 1, 1);
			            $pref_array['enq_js'] = $_POST['tvr_preferences']['enq_js'];
			            $pref_array['num_saves'] = ++$this->preferences['num_saves'];
			            // save and present message
			            if ($this->savePreferences($pref_array)) {
				            $this->log(
					            esc_html__('Enqueued scripts were updated', 'microthemer'),
					            '<p>' . esc_html__('Your enqueued scripts were successfully updated.', 'microthemer') . '</p>',
					            'notice'
				            );
			            }

			            // save last message in database so that it can be displayed on page reload (just once)
			            $this->cache_global_msg();
			            wp_die();
		            }

		            // reset default preferences
		            if (isset($_POST['tvr_preferences_reset'])) {
			            check_admin_referer('tvr_preferences_reset');
			            $pref_array = $this->default_preferences;
			            if ($this->savePreferences($pref_array)) {
				            $this->log(
					            esc_html__('Preferences were reset', 'microthemer'),
					            '<p>' . esc_html__('The default program preferences were reset.', 'microthemer') . '</p>',
					            'notice'
				            );
			            }
		            }


		            // css filter configs
		            $filter_types = array('page_specific', 'pseudo_classes', 'pseudo_elements', 'favourite_filter');
		            foreach ($filter_types as $type){
			            if (isset($_GET[$type])) {
				            $this->preferences[$type][$_GET['pref_sub_key']] = intval($_GET[$type]);
				            $pref_array[$type] = $this->preferences[$type];
				            $this->savePreferences( $pref_array );
				            //echo '<pre>'. print_r($this->preferences[$type], true).'</pre>';
				            wp_die();
			            }
		            }

		            // if we got to hear, the ajax request didn't work as intended, so warn
		            echo 'Yo! The Ajax call failed to trigger any function. Sort it out.';
		            wp_die();

	            }

            }

			// Microthemer UI page
			function microthemer_ui_page() {

				// only run code if it's the ui page
				if ( isset($_GET['page']) and $_GET['page'] == $this->microthemeruipage ) {

					if (!current_user_can('administrator')){
						wp_die('Access denied');
					}

					// validate email todo make this an ajax request, with user feedback
					if (isset($_POST['tvr_ui_validate_submit'])) {

						check_ajax_referer( 'tvr_validate_form', '_wpnonce' );

					    // tvr_validate_form
						$this->get_validation_response($_POST['tvr_preferences']['buyer_email']);
					}

					$this->invalidLic();

					// if user navigates from front to MT via toolbar, set previous front page in preview
					if (isset($_GET['mt_preview_url'])) {

						// if we're on the demo site, skip nonce check (we only allow page to be set, not arbitrary domain)
						if (!$this->is_demo_site()) {
							if (!wp_verify_nonce($_REQUEST['_wpnonce'], 'mt-preview-nonce')) {
								die( 'Security check failed' );
							}
						}

						$this->maybe_set_preview_url();
					}

					// if draft mode is on, but user accessing MT GUI isn't in draft_mode_uids array,
					// add them so they see latest draft changes
					if ($this->preferences['draft_mode'] and
					    !in_array($this->current_user_id, $this->preferences['draft_mode_uids'])){
						$pref_array['draft_mode_uids'] = $this->preferences['draft_mode_uids'];
						$pref_array['draft_mode_uids'][$this->current_user_id] = $this->current_user_id;
						$this->savePreferences($pref_array);
					}

					// ensure Preview URL matches HTTPS in admin
                    $this->ensure_iframe_protocol_matches_admin();

					// maybe check valid subscription
					$this->maybe_check_subscription();

					// Display user interface
					include $this->thisplugindir . 'includes/tvr-microthemer-ui.php';

				}
			}

			// Documentation page
			function microthemer_docs_page(){

				// only run code on docs page
				if ($_GET['page'] == $this->docspage) {

					if (!current_user_can('administrator')){
						wp_die('Access denied');
					}

					include $this->thisplugindir . 'includes/internal-docs.php';
				}
			}

			// fonts page
			function microthemer_fonts_page(){

			    // only run code on docs page
				if ($_GET['page'] == $this->fontspage) {

					if (!current_user_can('administrator')){
						wp_die('Access denied');
					}

					include $this->thisplugindir . 'includes/fonts.php';
				}
			}

			// Documentation menu
			function docs_menu($propertyOptions, $cur_prop_group, $cur_property){
				?>
                <div id="docs-menu">
                    <ul class="docs-menu">
                        <li class="doc-item css-ref-side">
							<?php $this->show_css_index($propertyOptions, $cur_prop_group, $cur_property); ?>
                        </li>
                    </ul>
                </div>
				<?php
			}

			// function for showing all CSS properties
			function show_css_index($propertyOptions, $cur_prop_group, $cur_property) {
				// output all help snippets
				$i = 1;
				foreach ($propertyOptions as $property_group_name => $prop_array) {
					$ul_class = $arrow_cls = '';
					if ($i&1) { $ul_class.= 'odd'; }
					if ($property_group_name == $cur_prop_group) { $ul_class.= ' show-content'; $arrow_cls = 'on'; }
					//if ($property_group_name == 'code') continue;
					?>
                    <ul id="<?php echo $property_group_name; ?>"
                        class="css-index <?php echo $ul_class; ?> accordion-menu">

                        <li class="css-group-heading accordion-heading">
                            <span class="pg-icon pg-icon-<?php echo $property_group_name; ?> no-click"></span>
                            <span class="menu-arrow accordion-menu-arrow tvr-icon <?php echo $arrow_cls; ?>" title="Open/close group"></span>
                            <span class="text-for-group"><?php echo $this->property_option_groups[$property_group_name]; ?></span>
                        </li>

						<?php
						foreach ($prop_array as $property_id => $array) {
							$li_class = '';
							if ($property_id == $cur_property) { $li_class.= 'current'; }
							if (!empty($array['field-class'])) { $li_class.= ' '.$array['field-class']; }
							?>
                        <li class="property-item accordion-item <?php echo $li_class; ?>">
                            <a href="<?php echo 'admin.php?page=' . $this->docspage; ?>&prop=<?php echo $property_id; ?>&prop_group=<?php echo $property_group_name; ?>">
                                <span class="option-icon-<?php echo $property_id; ?> option-icon no-click"></span>
                                <span class="option-text"><?php echo $array['label']; ?></span>
                            </a>

                            </li><?php
						}
						++$i;
						?>
                    </ul>
					<?php
				}
			}



			// Manage Micro Themes page
			function manage_micro_themes_page() {

			    // only run code if it's the manage themes page
				if ( $_GET['page'] == $this->microthemespage ) {

					if (!current_user_can('administrator')){
						wp_die('Access denied');
					}

					// handle zip upload
					if (isset($_POST['tvr_upload_micro_submit'])) {
						check_admin_referer('tvr_upload_micro_submit');
						$this->process_uploaded_zip();
					}


					// notify that design pack was successfully deleted (operation done via ajax on single pack page)
					if (!empty($_GET['mt_action']) and $_GET['mt_action'] == 'tvr_delete_ok') {
						check_admin_referer('tvr_delete_ok');
						$this->log(
							esc_html__('Design pack deleted', 'microthemer'),
							'<p>' . esc_html__('The design pack was successfully deleted.', 'microthemer') . '</p>',
							'notice'
						);
					}

					// handle edit micro selection
					if (isset($_POST['tvr_edit_micro_submit'])) {
						check_admin_referer('tvr_edit_micro_submit');
						$pref_array = array();
						$pref_array['theme_in_focus'] = $_POST['preferences']['theme_in_focus'];
						$this->savePreferences($pref_array);
					}

					// activate theme
					if (
						!empty($_GET['mt_action']) and
						$_GET['mt_action'] == 'tvr_activate_micro_theme') {
						check_admin_referer('tvr_activate_micro_theme');
						$theme_name = $this->preferences['theme_in_focus'];
						$json_file = $this->micro_root_dir . $theme_name . '/config.json';
						$this->load_json_file($json_file, $theme_name);
						// update the revisions DB field
						$user_action = sprintf(
							esc_html__('%s Activated', 'microthemer'),
							'<i>' . $this->readable_name($theme_name) . '</i>'
						);
						if (!$this->updateRevisions($this->options, $user_action)) {
							$this->log('', '', 'error', 'revisions');
						}
					}
					// deactivate theme
					if (
						!empty($_GET['mt_action']) and
						$_GET['mt_action'] == 'tvr_deactivate_micro_theme') {
						check_admin_referer('tvr_deactivate_micro_theme');
						$pref_array = array();
						$pref_array['active_theme'] = '';
						if ($this->savePreferences($pref_array)) {
							$this->log(
								esc_html__('Item deactivated', 'microthemer'),
								'<p>' .
								sprintf(
									esc_html__('%s was deactivated.', 'microthemer'),
									'<i>'.$this->readable_name($this->preferences['theme_in_focus']).'</i>' )
								. '</p>',
								'notice'
							);
						}
					}

					// include manage micro interface (both loader and themer plugins need this)
					include $this->thisplugindir . 'includes/tvr-manage-micro-themes.php';
				}
			}

			// Manage single page
			function manage_single_page() {
				// only run code on preferences page
				if( $_GET['page'] == $this->managesinglepage ) {

					if (!current_user_can('administrator')){
						wp_die('Access denied');
					}

					// handle zip upload
					if (isset($_POST['tvr_upload_micro_submit'])) {
						check_admin_referer('tvr_upload_micro_submit');
						$this->process_uploaded_zip();
					}

					// update meta.txt
					if (isset($_POST['tvr_edit_meta_submit'])) {
						check_admin_referer('tvr_edit_meta_submit');
						$this->update_meta_file($this->micro_root_dir . $this->preferences['theme_in_focus'] . '/meta.txt');
					}

					// update readme.txt
					if (isset($_POST['tvr_edit_readme_submit'])) {
						check_admin_referer('tvr_edit_readme_submit');
						$this->update_readme_file($this->micro_root_dir . $this->preferences['theme_in_focus'] . '/readme.txt');
					}

					// upload a file
					if (isset($_POST['tvr_upload_file_submit'])) {
						check_admin_referer('tvr_upload_file_submit');
						$this->handle_file_upload();
					}

					// delete a file
					if (
						!empty($_GET['mt_action']) and
						$_GET['mt_action'] == 'tvr_delete_micro_file') {
						check_admin_referer('tvr_delete_micro_file');
						// strip site_url rather than home_url in this case coz using with ABSPATH
						$root_rel_path = $this->root_rel($_GET['file'], false, true, true);
						$delete_ok = true;
						// remove the file from the media library
						if ($_GET['location'] == 'library'){
							global $wpdb;
							$img_path = $_GET['file'];
							// We need to get the images meta ID.
							/*$query = "SELECT ID FROM wp_posts where guid = '" . esc_url($img_path)
								. "' AND post_type = 'attachment'";*/
							$query = $wpdb->prepare("SELECT ID FROM wp_posts where guid = '%s' AND post_type = 'attachment'", esc_url($img_path));
							$results = $wpdb->get_results($query);
							// And delete it
							foreach ( $results as $row ) {
								//delete the image and also delete the attachment from the Media Library.
								if ( false === wp_delete_attachment( $row->ID )) {
									$delete_ok = false;
								}
							}
						}
						// regular delete of pack file
						else {
							if ( !unlink(ABSPATH . $root_rel_path) ) {
								$delete_ok = false;
							} else {
								// remove from file_structure array
								$file = basename($root_rel_path);
								if (!$this->is_screenshot($file)){
									$key = $file;
								} else {
									$key = 'screenshot';
									// delete the screenshot-small too
									$thumb = str_replace('screenshot', 'screenshot-small', $root_rel_path);
									if (is_file(ABSPATH . $thumb)){
										unlink(ABSPATH . $thumb);
										unset($this->file_structure[$this->preferences['theme_in_focus']][basename($thumb)]);
									}
								}
								unset($this->file_structure[$this->preferences['theme_in_focus']][$key]);
							}
						}
						if ($delete_ok){
							$this->log(
								esc_html__('File deleted', 'microthemer'),
								'<p>' . sprintf( esc_html__('%s was successfully deleted.', 'microthemer'), htmlentities($root_rel_path) ) . '</p>',
								'notice'
							);
							// update paths in json file
							$json_config_file = $this->micro_root_dir . $this->preferences['theme_in_focus'] . '/config.json';
							$this->replace_json_paths($json_config_file, array($root_rel_path => ''));
						} else {
							$this->log(
								esc_html__('File delete failed', 'microthemer'),
								'<p>' . sprintf( esc_html__('%s was not deleted.', 'microthemer'), htmlentities($root_rel_path) ) . '</p>'
							);
						}
					}


					// include preferences interface (only microthemer)
					if (TVR_MICRO_VARIANT == 'themer') {
						include $this->thisplugindir . 'includes/tvr-manage-single.php';
					}

				}
			}

			// Preferences page
			function microthemer_preferences_page() {

				// only run code on preferences page
				if( $_GET['page'] == $this->preferencespage ) {

					if (!current_user_can('administrator')){
						wp_die('Access denied');
					}

					// this is a separate include because it needs to have separate page for changing gzip
					$page_context = $this->preferencespage;
					echo '
                    <div id="tvr" class="wrap tvr-wrap">
                        <span id="ajaxUrl" rel="' . $this->wp_ajax_url.'"></span>
                        <span id="returnUrl" rel="admin.php?page=' . $this->preferencespage.'"></span>
                        <div id="pref-standalone">
                            <div id="full-logs">
                                '.$this->display_log().'
                            </div>';
					include $this->thisplugindir . 'includes/tvr-microthemer-preferences.php';
					echo '
                        </div>';

					//$this->hidden_ajax_loaders();

					echo '
                    </div>';

				}
			}

			// Detached preview page
			function microthemer_detached_preview_page() {

				// only run code on preferences page
				if( $_GET['page'] == $this->detachedpreviewpage ) {

					if (!current_user_can('administrator')){
						wp_die('Access denied');
					}

					// this is a separate include because it needs to have separate page for changing gzip
					$page_context = $this->detachedpreviewpage;
					$ui_class = '';
					$this->preferences['show_interface'] ? $ui_class.= ' show_interface' : false;
					$this->preferences['show_rulers'] ? $ui_class.= ' show_rulers' : false;
					echo '
                    <div id="tvr" class="wrap tvr-wrap '.$ui_class.'">
                        <span id="ajaxUrl" rel="'.$this->wp_ajax_url.'"></span>
                        <span id="returnUrl" rel="admin.php?page=' . $this->preferencespage.'"></span>
                        <div id="preview-standalone">';

					        include $this->thisplugindir . 'includes/tvr-microthemer-preview-wrap.php';

					        echo '
                        </div>
                    </div>';

				}
			}

			/* add run if admin page condition...? */

			/***
			Generic Functions
			 ***/

			// get min/max media query screen size
			function get_screen_size($q, $minmax) {
				$pattern = "/$minmax-width:\s*([0-9\.]+)\s*(px|em|rem)/";
				if (preg_match($pattern, $q, $matches)) {
					//echo print_r($matches);
					return $matches;
				} else {
					return 0;
				}
			}


			// show need help videos
			function need_help_notice() {
				if ($this->preferences['need_help'] == '1' and TVR_MICRO_VARIANT != 'loader') {
					?>
                    <p class='need-help'><b><?php esc_html_e('Need Help?', 'microthemer'); ?></b>
						<?php echo wp_kses(
							sprintf(
								__('Browse Our <span %1$s>Video Guides</span> and <span %2$s>Tutorials</span> or <span %3$s>Search Our Forum</span>', 'microthemer'),
								'class="help-trigger" rel="' . $this->thispluginurl.'includes/help-videos.php',
								'class="help-trigger" rel="' . $this->thispluginurl.'includes/tutorials.php',
								'class="help-trigger" rel="' . $this->thispluginurl.'includes/search-forum.php'
							),
							array( 'span' => array() )
						); ?></p>
					<?php
				}
			}

			/* Simple function to check for the browser
			For checking chrome faster notice and FF bug if $.browser is deprecated soon
			http://php.net/manual/en/function.get-browser.php */
			function check_browser(){
				$u_agent = $_SERVER['HTTP_USER_AGENT'];
				$ub = 'unknown-browser';
				if(preg_match('/(MSIE|Trident)/i',$u_agent) && !preg_match('/Opera/i',$u_agent)){
					$ub = "MSIE";
				}
                elseif(preg_match('/Firefox/i',$u_agent)){
					$ub = "Firefox";
				}
                elseif(preg_match('/Chrome/i',$u_agent)){
					$ub = "Chrome";
				}
                elseif(preg_match('/Safari/i',$u_agent)){
					$ub = "Safari";
				}
                elseif(preg_match('/Opera/i',$u_agent)){
					$ub = "Opera";
				}
                elseif(preg_match('/Netscape/i',$u_agent)){
					$ub = "Netscape";
				}
				return $ub;
			}

			// ie notice
			/*function ie_notice() {
				// display ie message unless disabled
				//global $is_IE;
				if ($this->preferences['ie_notice'] == '1' and $this->check_browser() != 'Chrome') {
					$this->log(
						esc_html__('Chrome Is Faster', 'microthemer'),
						'<p>' .
						sprintf(
							esc_html__('We\'ve noticed that Microthemer runs considerably faster in Chrome than other browsers. Actions like switching tabs, property groups, and accessing preferences are instant in Chrome but can incur a half second delay on other browsers. Speed improvements will be a major focus in our next phase of development. But for now, you can avoid these issues simply by using Microthemer with %1$s. Internet Explorer 9 and below isn\'t supported at all.', 'microthemer'),
							'<a target="_blank" href="http://www.google.com/intl/' . esc_attr_x('en-US', 'Chrome URL slug: https://www.google.com/intl/en-US/chrome/browser/welcome.html', 'microthemer') . '/chrome/browser/welcome.html">Google Chrome</a>'
						)
						. '</p><p>' .
						wp_kses(__('<b>Note</b>: Web browsers do not conflict with each other, you can install as many as you want on your computer at any one time. But if you love your current browser you can turn this message off on the preferences page.', 'microthemer'), array( 'b' => array() ))
						. '</p>',
						'warning'
					);
				}
			}*/

			// tell them to get validated
			function validate_reminder() {
				if (!$this->preferences['buyer_validated'] and TVR_MICRO_VARIANT == 'themer') {
					?>
                    <div id='validate-reminder' class="error">
                        <p><b><?php esc_html_e('IMPORTANT - Free Trial Mode is Active', 'microthemer'); ?></b><br /> <br />
							<?php echo wp_kses(
								sprintf( __('Please <a %s>validate your purchase to unlock the full program</a>.', 'microthemer'),
									'href="admin.php?page=tvr-microthemer-preferences.php#validate"' ),
								array( 'a' => array( 'href' => array() ) ) ); ?>
                            <br />
							<?php esc_html_e('The Free Trial limits you to editing or creating 15 Selectors.', 'microthemer'); ?>
                        </p>
                        <p><?php echo wp_kses(
								sprintf( __('Purchase a <a %1$s>Standard</a> ($45) or <a %1$s>Developer</a> ($90) License Now!', 'microthemer'),
									'target="_blank" href="http://themeover.com/microthemer/"'),
								array( 'a' => array( 'href' => array(), 'target' => array() ) ) ); ?></p>
                        <p><?php echo wp_kses(
								sprintf( __('<b>This Plugin is Supported!</b> Themeover provides the <a %s>best forum support</a> you\'ll get any where (and it\'s free of course)',
									'microthemer'),
									'target="_blank" href="http://themeover.com/forum/"' ),
								array( 'a' => array( 'href' => array(), 'target' => array() ), 'b' => array() ) ); ?></p>
                    </div>
					<?php
				}
			}

			/*// show server info
			function server_info() {
				global $wpdb;
				// get MySQL version
				$sql_version = $wpdb->get_var("SELECT VERSION() AS version");
				// evaluate PHP safe mode
				if(ini_get('safe_mode')) {
					$safe_mode = 'On';
				}
				else {
					$safe_mode = 'Off';
				}
				?>
				&nbsp;Operating System:<br />&nbsp;<b><?php echo PHP_OS; ?> (<?php echo (PHP_INT_SIZE * 8) ?> Bit)</b><br />

				&nbsp;MySQL Version:<br />&nbsp;<b><?php echo $sql_version; ?></b><br />
				&nbsp;PHP Version:<br />&nbsp;<b><?php echo PHP_VERSION; ?></b><br />
				&nbsp;PHP Safe Mode:<br />&nbsp;<b><?php echo $safe_mode; ?></b><br />
			<?php
			}
			*/

			// get all-devs and the MQS into a single simple array
			function combined_devices(){
				$comb_devs['all-devices'] = array(
					'label' => esc_html__('All Devices', 'microthemer'),
					'query' => esc_html__('General CSS that will apply to all devices', 'microthemer'),
					'min' => 0,
					'max' => 0
				);
				foreach ($this->preferences['m_queries'] as $key => $m_query) {
					$comb_devs[$key] = $m_query;
				}
				return $comb_devs;
			}

			// get micro-themes files
			// get micro-theme dir file structure
			/*function dir_loop($dir, $result = array()) {

				foreach(scandir($dir) as $filename) {
					if ($filename[0] === '.') continue;
					$filePath = $dir . DIRECTORY_SEPARATOR . $filename;
					if (is_dir($filePath)) {
						$result[$filename] = $this->dir_loop($filePath);
					} else {
						if ($this->is_screenshot($filename)){
							$result['screenshot'][$filename] = 1;
						} else {
							$result[$filename] = 1;
						}
					}
				}

				// sort alphabetically
				if (is_array($result)) {
					ksort($result);
				}

				return $result;
			}*/

			function dir_loop($dir, $result = array()) {

				foreach(scandir($dir) as $filename) {

					if ($filename[0] === '.') {
						continue;
					}

					$filePath = $dir . DIRECTORY_SEPARATOR . $filename;

					if (is_dir($filePath)) {
						$result[$filename] = $this->dir_loop($filePath);
					}

					else {
						if ($this->is_screenshot($filename)){
							$result['screenshot'] = $filename;
						} else {
							$result[$filename] = 1;
						}
					}
				}

				// sort alphabetically
				if (is_array($result)) {
					ksort($result);
				}

				return $result;
			}

			// get extension
			function has_extension($file) {
				return count(explode('.', $file)) > 1;
				//return preg_match('/\..+$/', explode('?', $file)[0]);
			}

			// some versions of php don't like empty(trim($input)) so this is workaround
			function trimmedEmpty($input){

				if (empty($input)){
					return true;
				}

				$input = trim($input);

				if (empty($input)){
					return true;
				}

				return false;
			}


			// get micro-theme dir file structure
			/*function dir_loop_old($dir_name) {

				if (empty($this->file_structure)) {
					$this->file_structure = array();
				}

				// check for micro-themes folder, create if doesn't already exist
				if ( !is_dir($dir_name) ) {
					if ( !wp_mkdir_p($dir_name) ) {
						$this->log(
							esc_html__('/micro-themes folder error', 'microthemer'),
							'<p>' .
							sprintf(
								esc_html__('WordPress was not able to create the %s directory.', 'microthemer'),
								$this->root_rel($dir_name)
							) . $this->permissionshelp . '</p>'
						);
						return false;
					}
				}

				// loop over the directory
				if ($handle = opendir($dir_name)) {

					$count = 0;

					while (false !== ($file = readdir($handle))) {
						if ($file != '.' and $file != '..' and $file != '_debug') {
							$file = htmlentities($file); // just in case
							if ($this->is_acceptable($file) or !preg_match('/\./',$file)) {

								// if it's a directory
								if (!preg_match('/\./',$file) ) {
									$this->file_structure[$file] = array();
									$next_dir = $dir_name . $file . '/';
									// loop through the contents of the micro theme
									$this->dir_loop($next_dir);
								}

								// it's a normal file
								else {
									$just_dir = str_replace($this->micro_root_dir, '', $dir_name);
									$just_dir = str_replace('/', '', $just_dir);
									if ($this->is_screenshot($file)){
										$this->file_structure[$just_dir]['screenshot'] = $file;
									} else {
										$this->file_structure[$just_dir][$file] = $file;
									}

									++$count;
								}
							}
						}
					}
					closedir($handle);
				}

				if (is_array($this->file_structure)) {
					ksort($this->file_structure);
				}

				return $this->file_structure;
			}*/


			// display abs file path relative to the root dir (for notifications)
			function root_rel($path, $markup = true, $url = false, $actual_path = false) {

				// normalise \/ slashes
				$abspath_fs = untrailingslashit(str_replace('\\', '/', ABSPATH));
				$path = str_replace('\\', '/', $path);

				if ($markup == true) {
					$rel_path = '<b><i>/' . str_replace($abspath_fs, '', $path) . '</i></b>';
				}
				else {
					$rel_path = str_replace($abspath_fs, '', $path);
				}

				// root relative url (works on mixed ssl sites and if WP is in a subfolder of the doc root - getenv())
				if ($url){

					// WP is sometimes installed in a sub-dir, but pages are served as if from the root.
					// $this->home_url = root, $this->site_url = path to sub-dir
					// We normally want to strip $this->home_url, unless using root rel with ABSPATH (which incs subdir)
					// See https://premium.wpmudev.org/blog/install-wordpress-subdirectory/
					$path_to_strip = $actual_path ? $this->site_url : $this->home_url;

					// we're making an url FILE path root relative. The url WILL contain any subdir, so strip site_url stub
					if ($actual_path){

						// get the path from the www root to the website root. Often this will be the same.
						// But on localhost it might be e.g. /personal/themeover.com/wp-versions/really-fresh/
						// this happens when using sub-dirs without special case above.
						$script_name = getenv("SCRIPT_NAME");

						// $script_name could be be either admin-ajax.php (24) or admin.php (19), which will affect the offset
						$str_offset = strpos($script_name, 'admin-ajax.php') !== false ? -24 : -19;

						// we always strip whole site_url because $script_name will include any sub-dir, we don't want it twice
						$rel_path = substr($script_name, 0, $str_offset) . str_replace($this->site_url, '', $path);
					}

					// we're making an URL path root relative. The URL will NOT contain any subdir, so strip home_url stub
					else {
						$rel_path = str_replace($this->home_url, '', $path);
					}

					/*/*$script_name = getenv("SCRIPT_NAME");
					$rel_path = substr($script_name, 0, -(strlen($script_name))) . str_replace($this->site_url, '', $path);
					if (true){
						$this->show_me.= '(New) $script_name: '. $script_name.'<br />';
						$this->show_me.= '$path: '. $path.'<br />';
						$this->show_me.= 'str_replace($this->site_url, $path): '
							. str_replace($this->site_url, '', $path).'<br />';
						$this->show_me.= 'substr($script_name, 0, $str_offset) : '
							. substr($script_name, 0, $str_offset) .'<br />';
						$this->show_me.= '$this->site_url: '. $this->site_url.'<br />';
						$this->show_me.= '$this->home_url: '. $this->home_url.'<br />';
						$this->show_me.= '$rel_path: '. $rel_path.'<br />';
						$this->log(
							'active-styles debug',
							'<p>' . $this->show_me . '</p>'
						);
					}
					*/


				}

				return $rel_path;
			}

			// get extension
			function get_extension($file) {
				$tmp = explode('?', $file);
				$file = $tmp[0];
				$ext = strtolower(substr($file, strrpos($file, '.') + 1));
				return $ext;
			}

			// use wp_remote_fopen with some validation checks
			function get_safe_url($uri, $config = array(), $msg_type = 'warning') {

				$r = array();

				// bail if not an URL
				if (!preg_match('/^(https?:)?\/\//i', $uri)){
					$this->log(
						esc_html__('Invalid URL', 'microthemer'),
						'<p>'.esc_html($uri).'</p>',
						'error'
					);
					return false;
				}

				// bail if not correct extension
				if (!empty($config['allowed_ext']) && !in_array($this->get_extension($uri), $config['allowed_ext'])) {
					$this->log(
						esc_html__('Disallowed file extension', 'microthemer') . ':' . $this->get_extension($uri),
						'<p>Please enter an URL with one of the following extensions: '.
						implode(', ', $config['allowed_ext']). '</p>',
						'error'
					);
					return false;
				}

				// check if file exists
				if (!$this->url_exists($uri)){
					return false;
				}

				// it seems ok so get contents of file into string
				if (!$r['content'] = wp_remote_fopen($uri)){
					$this->log(
						esc_html__('File is empty', 'microthemer'),
						'<p>'.esc_html($uri).'</p>',
						$msg_type
					);
					return false;
				}

				// do we need to save as file in tmp dir?
				if (!empty($config['tmp_file'])){
					$r['tmp_file'] = $this->thistmpdir . basename($uri);
					$this->write_file($r['tmp_file'], $r['content']);
				}

				return $r;
			}

			// check if an URL exists (WP can return 404 custom page giving illusion file exists)
			function url_exists($url) {
				$response = wp_remote_get( esc_url_raw($url) );
				// my half-done ssl on localhost fails here, so warn others
				if ( is_wp_error( $response ) ) {
					$str = '';
					foreach ($response->errors as $key => $err_arr){
						$str.= '<p>'.$key.': '.implode(', ', $err_arr).'</p>';
					}
					$this->log(
						esc_html__('Could not get file', 'microthemer'),
						'<p>'.esc_html($url). '</p>'
						.$str
					);
					return false;
				}
				if ( 200 != wp_remote_retrieve_response_code( $response ) ) {
					$this->log(
						esc_html__('File does not exist', 'microthemer'),
						'<p>'.esc_html($url).'</p>'
					);
					return false;

				}
				return true;
			}

			// is english
			function is_en() {
				if ($this->locale == 'en_GB' or $this->locale == 'en_US') return true;
				return false;
			}

			// get first item in an associative array
			function get_first_item($array) {
				$item = false;
				foreach ($array as $key => $value){
					$item = $array[$key];
					break;
				}
				return $item;
			}

			// convert one array format to autocomplete with categories format
			function to_autocomplete_arr(
				$orig_array,
				$new_array = array(),
				$config = array()
			){
				foreach ($orig_array as $category => $array){
					foreach ($array as $i => $value){

					    // array may be an array of arrays with the value as the key
					    if (is_array($value)){
						    $data = array_merge(array(
							    'label' => $i,
							    'category' => $category
						    ), $value);
					    }

					    // simple numeric array with single values
					    else {
					       $data = array(
						       'label' => $value,
						       'category' => $category
					       );
					    }

						$new_array[] = $data;
					}
				}
				return $new_array;
			}

			function autocomplete_to_param_keys($autocomplete_array){

				$new_array = array();

				foreach ($autocomplete_array as $i => $array){
					$new_array[$this->to_param($array['label'])] = $array['label'];
				}

				return $new_array;
            }

			// WordPress normalises magic_quotes, if magic_quotes are enabled.
			// Even though deprecated: http://wordpress.stackexchange.com/questions/21693/wordpress-and-magic-quotes
			// Useful WP functions: stripslashes_deep() and add_magic_quotes() (both recursive)
			// $do is for easy dev experimenting.
			function stripslashes($val, $do = false){
				return $do ? stripslashes_deep($val): $val;
			}

			function addslashes($val, $do){
				return $do ? add_magic_quotes($val): $val;
			}

			function normalise_line_breaks($value, $trailing = "\n\n", $leading = ''){
				return $leading.trim($value, "\n\r").$trailing;
			}

			function normalise_tabs($string, $cur_tab_indent, $isSass = false){

			    if (!$isSass){
				    return $string;
                }

				$string = preg_replace("/(?<!^)(\n)/", "\n{$cur_tab_indent}\\2", $string);

				return str_replace("\t}", $cur_tab_indent."}", $string);

                /*// strip all leading white space
				$string = preg_replace("/^[ \t]+/m", "", $string);

				// replace line breaks inside with tab (not starting line break or final before })
				$string = preg_replace("/(?<!^)(\n)([^}])/", "\n{$cur_tab_indent}\t\\2", $string);

				// add back start tab and tab before }
                return str_replace("}", $cur_tab_indent."}", $string);*/

			}

			// WP magic_quotes hack doesn't escape \ properly, so this is my workaround
			function unescape_cus_slashes($val){
				return str_replace('&#92;', '\\', $val);
			}

			// Another workaround I came up with along time ago without fully understanding wp magic_quotes, but works too.
			function unescape_cus_quotes($val, $forAttr = false){

			    $single = $forAttr ? '&#039;' : "'";
				$double = $forAttr ? '&quot;' : '"';

				$val = str_replace('cus-#039;', $single, $val);
				$val = str_replace('cus-quot;', $double, $val);

				return $val;
			}

			// Unescape slashes and cus quotes recursively
			function deep_unescape($array, $cus_quotes = false, $slashes = false, $cus_slashes = false){
				foreach ( (array) $array as $k => $v ) {
					if (is_array($v)) {
						$array[$k] = $this->deep_unescape($v, $cus_quotes, $slashes, $cus_slashes);
					} else {
						if ($cus_quotes and $k !== 'user_action'){ // user action had issue with quotes inside title attribute not being distinguishable from regualr quotes.
							$array[$k] = $this->unescape_cus_quotes($v);
						}
						if ($slashes){
							$array[$k] = stripslashes($array[$k]);
						}
						if ($cus_slashes){
							$array[$k] = $this->unescape_cus_slashes($array[$k]);
						}
					}
				}
				return $array;
			}

			// make server folder readable
			function readable_name($name) {
				$readable_name = str_replace('_', ' ', $name);
				$readable_name = ucwords(str_replace('-', ' ', $readable_name));
				return $readable_name;
			}

			// convert text to param (same as JS function)
			function to_param($str) {
				$str = str_replace(' ', '_', $str);
				$str = strtolower(preg_replace("/[^A-Za-z0-9_]/", '', $str));
				return $str;
			}

			// populate the default folders global array with translated strings
			function set_default_folders() {
				$folders = array(
					'general' => __('General', 'microthemer'), // Auto-create General 2, 3 etc when +25 selectors
					'header' => __('Header', 'microthemer'),
					'main_menu' => __('Main Menu', 'microthemer'),
					'content' => __('Content', 'microthemer'),
					'sidebar' => __('Sidebar', 'microthemer'),
					'footer' => __('Footer', 'microthemer')
				);
				foreach ($folders as $en_slug => $label){
					//$this->default_folders[$this->to_param($label)] = '';
					$this->default_folders[$this->to_param($label)]['this'] = array(
						'label' => $label
					);
				}
			}

			// check if the file is an image
			function is_image($file) {
				$ext = $this->get_extension($file);
				if ($ext == 'jpg' or
				    $ext == 'jpeg' or
				    $ext == 'png' or
				    $ext == 'gif'
				) {
					return true;
				}
				else {
					return false;
				}
			}

			// check if it's a screenshot image
			function is_screenshot($file) {
				if(strpos($file, 'screenshot.', 0) !== false) {
					return true;
				}
				else {
					return false;
				}
			}

			// check a multidimentional array for a value
			function in_2dim_array($str, $array, $target_key){
				foreach ($array as $key => $arr2) {
					if ($arr2[$target_key] == $str) {
						return $key;
					}
				}
				return false;
			}

			//check if the file is acceptable
			function is_acceptable($file) {
				$ext = $this->get_extension($file);
				if ($ext == 'jpg' or
				    $ext == 'jpeg' or
				    $ext == 'png' or
				    $ext == 'gif' or
				    $ext == 'txt' or
				    $ext == 'json' or
				    $ext == 'sass' or
				    $ext == 'scss' or
				    $ext == 'css' or
				    $ext == 'psd' or
				    $ext == 'ai'
				) {
					return true;
				}
				else {
					return false;
				}
			}


			// get list of acceptable file types
			function get_acceptable() {
				$acceptable = array (
					'jpg',
					'jpeg',
					'png',
					'gif',
					'txt',
					'json',
					'sass',
					'scss',
					'css',
					'psd',
					'ai');
				return $acceptable;
			}

			// rename dir if dir with same name exists in same location
			function rename_if_required($dir_path, $name) {
				if ( is_dir($dir_path . $name ) ) {
					$suffix = 1;
					do {
						$alt_name  = substr ($name, 0, 200 - ( strlen( $suffix ) + 1 ) ) . "-$suffix";
						$dir_check = is_dir($dir_path . $alt_name);
						$suffix++;
					} while ( $dir_check );
					return $alt_name;
				}
				return false;
			}

			// rename the to-be-merged section
			function get_alt_section_name($section_name, $orig_settings, $new_settings) {
				$suffix = 2;
				do {
					$alt_name = substr ($section_name, 0, 200 - ( strlen( $suffix ) + 1 ) ) . "_$suffix";
					$context = 'alt-check';
					$conflict = $this->is_name_conflict($alt_name, $orig_settings, $new_settings, $context);
					$suffix++;
				} while ( $conflict );
				// also need to have index value by itself so return array.
				$alt = array(
					'name' => $alt_name,
					'index' => $suffix-1
				);
				return $alt;
				//return $alt_name;
			}

			// check if the section name exists in the orig_settings or the new_settings (possible after name modification)
			function is_name_conflict($alt_name, $orig_settings, $new_settings, $context='') {
				if ( ( isset($orig_settings[$alt_name]) // conflicts with orig settings or
				       or ($context == 'alt-check' and isset($new_settings[$alt_name]) )) // conflicts with new settings (and is an alt name)
				     and $alt_name != 'non_section' // and is a section
				) {
					return true; // conflict
				}
				else {
					return false; // no name conflict
				}
			}

			/***
			Microthemer UI Functions
			 ***/

			// ui dialog html (start)
			function start_dialog($id, $heading, $class = '', $tabs = array() ) {
				$content_class = '';
				// set dialog specific classes
				if ($id != 'manage-design-packs' and $id != 'program-docs' and $id != 'display-css-code'){
					$content_class.= 'scrollable-area';
				}
				if ($id == 'display-css-code' or $id == 'import-from-pack'){
					$content_class.= ' tvr-editor-area';
				}

				if ( !empty( $tabs ) ) {
					$class.= ' has-mt-tabs';
				}
				$html = '<div id="'.$id.'-dialog" class="tvr-dialog '.$class.' hidden">
				<div class="dialog-main">
					<div class="dialog-inner">
						<div class="heading dialog-header">
							<span class="dialog-icon"></span>
							<span class="text">'.$heading.'</span>

							<span class="tvr-icon close-icon close-dialog"></span>
							<span class="dialog-status"><span></span></span>
						</div>';

				// If there are any tabs, the content is preceded by a tab menu
				if ( !empty( $tabs ) ) {

					$dialog_tab_param = str_replace('-', '_', $id);

					// whoops, forgot I started this
					$dialog_tab_val = !empty($this->preferences[$dialog_tab_param])
                        ? $this->preferences[$dialog_tab_param]
                        : 0;

					$active_tab = (!empty($this->preferences['generated_css_focus']) && $dialog_tab_param === 'display_css_code')
						? $this->preferences['generated_css_focus']
						: '0';

					$html.='
					<div class="dialog-tabs query-tabs">
						<input class="dialog-focus" type="hidden"
						name="tvr_mcth[non_section]['.$dialog_tab_param.']"
						value="'.$active_tab.'" />';

					// maybe add functionality to remember pref tab at a later date.
					for ($i = 0; $i < count($tabs); $i++) {
					    $tab_name = $tabs[$i];
					    $tab_class = strtolower(str_replace(' ', '-', $tab_name));
					    $mode = $tab_class;
					    if ($mode === 'previous-scss-compile'){
					        $mode = 'scss';
                        }

						$mode = str_replace('-(min)', '', $mode);

						$html .= '
							<span class="' . ($i == $active_tab ? 'active' : '' )
						         . ' mt-tab dialog-tab dialog-tab-'.$i.' dialog-tab-'.$tab_class.'" 
						         rel="'.''.$i.'" data-mode="'.$mode.'">
								' . $tab_name . '
							</span>';
					}
					$html .= '
					</div>';
				}
				$html .= '<div class="dialog-content '.$content_class.'">';
				return $html;
			}

			function dialog_button($button_text, $type, $class, $title = ''){
				$tAttr = $title ? 'title="'.$title.'"' : '';
				if ($type == 'span'){
					$button = '<span class="tvr-button dialog-button '.$class.'" '.$tAttr.'>'.$button_text.'</span>';
				} else {
					$button = '<input name="tvr_'.strtolower(str_replace('-', '_', $class)).'_submit"
					type="submit" value="'. esc_attr($button_text) .'"
					class="tvr-button dialog-button" '.$tAttr.' />';
				}

				return $button;
			}

			// ui dialog html (end)
			function end_dialog($button_text, $type = 'span', $class = '', $title = '') {
				$button = $this->dialog_button($button_text, $type, $class, $title);
				$html = '

							</div>
							<div class="dialog-footer">
							'.$this->system_menu(true). $button. '
							</div>
						</div>
					</div>
				</div>';
				return $html;
			}

			// output clear icon for section, selector, tab, or pg
			function clear_icon($level, $extra = false){

			    $title = esc_attr__('Clear', 'microthemer') .  ' ' . $this->level_map[$level];

			    $data_att = $extra ? 'data-'.$extra['key'].'="'.$extra['value'].'"' : '';

				return '<span class="tvr-icon clear-icon" title="'.$title.'" data-input-level="'.$level.'" '.$data_att.'></span>';
			}

			function save_icon($class = ''){
				return '<div class="save-button code-save '.$class.' tvr-icon" title="'.$this->text['save-button'].'"></div>';
			}

			function extra_actions_icon($id = false){

				return $this->ui_toggle(
					'show_extra_actions',
					!$id ? 'conditional' : esc_attr__('Show more actions', 'microthemer'),
					!$id ? 'conditional' : esc_attr__('Show less actions', 'microthemer'),
					!$id ? false : $this->preferences['show_extra_actions'],
					'extra-actions-toggle tvr-icon',
					$id,
					array(
						'dataAtts' => array(
							//'no-save' => 1,
							'dyn-tt-root' => $id ? false : 'show_extra_actions'
						)
					)
				);
			}

			// hover inspect button
			// an alias is used in wizard mode so content flows to right of variable width button
			function hover_inspect_button($id = false, $text = false){

				$tip = esc_attr__("(Ctrl+Alt+T)", 'microthemer');

				return $this->ui_toggle(
					'hover_inspect',
					!$id ? 'conditional' : esc_attr__('Enable targeting mode', 'microthemer')." ".$tip,
					!$id ? 'conditional' : esc_attr__('Disable targeting mode', 'microthemer')." ".$tip,
					!$id ? false : $this->preferences['hover_inspect'],
					'hover-inspect-toggle',
					$id,
					array(
						'text' => $text ? $text : esc_html__('Target', 'microthemer'),
						//'text' => 'conditional', // this proved tricky to maintain
						// - see css .hover-inspect-toggle position:fixed hack.
						'dataAtts' => array(
							'no-save' => 1,
							'dyn-tt-root' => $id ? false : 'hover-inspect-toggle',
						)
					)
				);
			}

			// alias for switching to code view ($id will always be false come to think of it)
			function code_view_icon($id = false){

				return $this->ui_toggle(
					'show_code_editor',
					!$id ? 'conditional' : esc_attr__('Code view', 'microthemer'),
					!$id ? 'conditional' : esc_attr__('GUI view', 'microthemer'),
					!$id ? false : $this->preferences['show_code_editor'],
					'toggle-full-code-editor',
					$id,
					array(
						'text' => esc_html__('Code', 'microthemer'),
						'dataAtts' => array(
							'dyn-tt-root' => $id ? false : 'toggle-full-code-editor',


							// would need to dynamically update the aliases text if using this
							//'text-pos' => esc_html__('Code', 'microthemer'),
							//'text-neg' => esc_html__('GUI', 'microthemer')
						)
					)
				);
			}

			function manual_resize_icon(){
				return $this->ui_toggle(
					'code_manual_resize',
					'conditional',
					'conditional',
					false,
					'editor-resize-icon tvr-icon',
					false,
					// instruct tooltip to get content dynamically
					array('dataAtts' => array(
						'dyn-tt-root' => 'code_manual_resize',
						//'editor-type'=> $editorType
					))
				);
			}

			// output feather icon for section, selector, tab, or pg
			function feather_icon($level){
				return '<span class="tvr-icon feather-icon '.$level.'-feather" data-input-level="'.$level.'"></span>';
			}

			// output icon for toggling full interface feature e.g. dock right/bottom
			function ui_toggle($aspect, $pos, $neg, $on, $class, $id = false, $config = array()){

				if ($on){
					$title = $neg;
					$class.= ' on';
				} else {
					$title = $pos;
				}

				$id = $id ? 'id="'.$id.'"' : '';

				// determine tagname
				$tag = !empty($config['tag']) ? $config['tag'] : 'span';

				// css_filter needs to pass
				$pref_sub_key = !empty($config['pref_sub_key']) ? 'data-pref_sub_key="'.$config['pref_sub_key'].'"' : '';

				// e.g. css filter has an inside favourite icon
				$inner_icon = !empty($config['inner_icon']) ? $config['inner_icon'] : '';

				// output arbitrary data atts
				$dataAtts = '';
				if (!empty($config['dataAtts'])){
					foreach ($config['dataAtts'] as $dat => $dat_val){
						$dataAtts.= 'data-'.$dat.'="'.$dat_val.'" ';
					}
				}

				// Note: uit-par may need to be var
				$html = '
				<'.$tag.' '.$id.' class="ui-toggle uit-par '.$class.'" title="'.$title.'"
					  data-pos="'.$pos.'"
					  data-neg="'.$neg.'"
					  '.$dataAtts.'
					  '.$pref_sub_key.'
					  data-aspect="'.$aspect.'">';

				// add text if not an icon
				if (!empty($config['text'])){

					$text = $config['text'];

					// show/hide advanced wizard options uses conditional text, as would most text toggles
					if ($text == 'conditional'){
						$text = $on ? $config['dataAtts']['text-neg'] : $config['dataAtts']['text-pos'];
					}

					// check if an input needs to be added
					if (!empty($config['css_filter']['editable'])){
						$ed = $config['css_filter']['editable'];
						$rel = !empty($ed['combo']) ? 'rel="'.$ed['combo'].'"' : '';
						$combo = '<span class="tvr-input-wrap">'.
						         '<input type="text" class="combobox cssfilter-combo" name="'.$ed['str'].'" '.$rel.'
						value="'.trim($ed['str'], "()").'"
						 />
						</span>';
						// the replace str has brackets to ensure we get the right (n) in e.g. nth-child(n)
						$text = '<span class="pre-edfil ui-toggle">' .
						        str_replace($ed['str'], '(</span>'.$combo.'<span class="post-edfil ui-toggle">)</span>', $text);
					}

					$html.= $inner_icon . $text;
				}

				$html.= '</'.$tag.'>';
				return $html;
			}

			// feather, chain, important, pie, disable icons
			function icon_control(
				$justInput,
				$con,
				$on,
				$level,
				$section_name = '',
				$css_selector = '',
				$key = '',
				$group = '',
				$subgroup = '',
				$prop = '',
				$mq_stem = 'tvr_mcth',
                $tabGroup = ''){

				// common atts
				$icon_id = '';
				$input = '';
				$tracker_class = $con.'-tracker';
				$icon_class = $con.'-toggle input-icon-toggle';
				$icon_inside = '';
				$data_atts_arr = array();
				$pos_title = $neg_title = '';


				// set MQ stub for tab and pg inputs
				$imp_key = '';
				if ($level == 'tab-input' or $level == 'subgroup' or $level == 'property'){
					if ($mq_stem == 'tvr_mcth' and $key != 'all-devices'){
						$mq_stem.= '[non_section][m_query]['.$key.']';
						$imp_key = '[m_query]['.$key.']';
					}
				}

				// icon specific
				if ($con == 'disabled'){
					$icon_class.= ' tvr-icon disabled-icon '.$level.'-disabled';
					$pos_title = esc_attr__('Disable', 'microthemer') .  ' ' . $this->level_map[$level];
					$neg_title = esc_attr__('Enable', 'microthemer') .  ' ' . $this->level_map[$level];
					if ($level === 'pgtab'){
						$data_atts_arr['tab-group'] = $tabGroup;
                    }
				} elseif ($con == 'chained') {
					$icon_class.= ' tvr-icon chained-icon '.$subgroup.'-chained';
					$pos_title = esc_attr__('Link fields', 'microthemer');
					$neg_title = esc_attr__('Unlink fields', 'microthemer');
				} elseif ($con == 'important') {
					$pos_title = esc_attr__('Add !important', 'microthemer');
					$neg_title = esc_attr__('Remove !important', 'microthemer');
					$icon_inside = 'i';
				}

				/*elseif ($con == 'pie') {
					$icon_class.= ' tvr-icon';
					$pos_title = esc_attr__('Turn CSS3 PIE polyfill on', 'microthemer');
					$neg_title = esc_attr__('Turn CSS3 PIE polyfill off', 'microthemer');
				}*/

				elseif ($con == 'flexitem' || $con == 'griditem') {
					$icon_class.= ' dynamic-fields-toggle';
					$pos_title = esc_attr__('Show item fields', 'microthemer');
					$neg_title = esc_attr__('Show container fields', 'microthemer');
					// default icon text is 'container' overridden below if toggle is on
					//$icon_inside = esc_html__('Container', 'microthemer');
					// if flex item/container toggle
					$data_atts_arr['text-pos'] = esc_attr__('Item', 'microthemer');
					$data_atts_arr['text-neg'] = esc_attr__('Container', 'microthemer');

				} elseif ($con == 'gradient') {
					$icon_class.= ' tvr-icon dynamic-fields-toggle';
					$pos_title = esc_attr__('Show gradient fields', 'microthemer');
					$neg_title = esc_attr__('Show background-image fields', 'microthemer');
				} elseif ($con == 'show_css_filters') {
					$icon_class.= ' tvr-icon settings-icon quick-opts-wrap tvr-fade-in click-toggle"';
					$pos_title = esc_attr__('Show selector modifiers', 'microthemer');
					$neg_title = esc_attr__('Hide selector modifiers', 'microthemer');
					$icon_id = 'id="show_css_filters-toggle"';

					// display css filters
					$icon_inside = $this->display_css_filters();

				}

				// generate input if item is on
				$title = $pos_title; // do what toggle is there for
				if (!empty($on)){
					$title = $neg_title; // undo what toggle is there for
					switch ($level) {
						case "section":
							$name = $mq_stem . '['.$section_name.'][this]';
							break;
						case "selector":
							$name = $mq_stem . '['.$section_name.']['.$css_selector.']';
							break;
						case "tab-input":
							$tracker_class.= '-'.$key;
							$name = $mq_stem . '['.$section_name.']['.$css_selector.'][tab]';
							break;
						case "group":
							$name = $mq_stem . '['.$section_name.']['.$css_selector.'][pg_'.$con.']';
							break;
						case "pgtab":
							$name = $mq_stem . '['.$section_name.']['.$css_selector.'][pgtab_'.$con.']['.$tabGroup.']';
							break;
						case "subgroup":
							$name = $mq_stem . '['.$section_name.']['.$css_selector.'][pg_'.$con.']['.$subgroup.']';
							break;
						case "property":
							$name = $mq_stem . '['.$section_name.']['.$css_selector.'][styles]['.$group.']['.$prop.']';
							break;
						case "script":
							$name = 'tvr_preferences[enq_js]['.$section_name.']';
							break;
						default:
							$name = '';
					}
					$name.= '['.$con.']';

					// important is only for props, and has different structure
					if ($con == 'important'){
						$name = 'tvr_mcth[non_section][important]'.$imp_key.'['.$section_name.']['.$css_selector.']['.$group.']['.$prop.']';
					}

					$input = '<input class="input-toggle-tracker '.$tracker_class.'" type="hidden" name="'.$name.'" value="1" />';
					$icon_class.= ' active';
				}

				// output arbitrary data atts
				$dataAtts = '';
				//$test = 'yes' . implode($data_atts_arr);
				if (!empty($data_atts_arr)){
					foreach ($data_atts_arr as $dat => $dat_val){
						$dataAtts.= 'data-'.$dat.'="'.$dat_val.'" ';
					}
					//$test = 'person';
;				}

				// generate icon
				$icon = '<span '.$icon_id.' class="'.$icon_class.'" title="'.$title.'" data-pos="'.$pos_title.'"
				data-neg="'.$neg_title.'"  data-input-type="'.$con.'" '.$dataAtts.' 
							data-input-level="'.$level.'">'.$icon_inside.'</span>';

				// with feather on tabs, icon and input are output separately
				if ($con == 'disabled'){
					if ($level == 'tab'){
						$input = '';
					} elseif ($level == 'tab-input'){
						$icon = '';
					}
				}

				// with important, a placeholder is used for css3 options that only need one 'i'
				if (!empty($this->propertyoptions[$group][$prop]['hide imp'])) {
					$icon = '<span class="imp-placeholder">i</span>';
				}

				// return control
                if ($justInput){
				    return $input;
                }

				return $input . $icon;
			}

			// output header
			function manage_packs_header($page){
				?>
                <ul class="pack-manage-options">
                    <li class="upload">
                        <form name='upload_micro_form' id="upload-micro-form" method="post" enctype="multipart/form-data"
                              action="<?php echo 'admin.php?page='. $page;?>" >
							<?php wp_nonce_field('tvr_upload_micro_submit'); ?>
                            <input id="upload_pack_input" type="file" name="upload_micro" />
                            <input class="tvr-button upload-pack" type="submit" name="tvr_upload_micro_submit"
                                   value="<?php esc_attr_e('+ Upload New', 'microthemer'); ?>" title="<?php esc_attr_e('Upload a new design pack', 'microthemer'); ?>" />
                        </form>
                    </li>
                    <!--<li>
						<a class="tvr-button" target="_blank" title="Submit one of your design packs for sale/downlaod on themeover.com"
							href="https://themeover.com/sell-micro-themes/submit-micro-theme/">Submit To Marketplace</a>
					</li>
					<li>
						<a class="tvr-button" target="_blank" title="Browse Themeover's marketplace of design packs for various WordPress themes and plugins"
							href="http://themeover.com/theme-packs/">Browse Marketplace</a>
					</li>-->
                </ul>
				<?php
			}

			function get_design_packs($packs){
			    $count = 0;
			    $valid_packs = array();
			    $exclude = array('sass', 'scss');
			    foreach($packs as $name => $item){
			        if (is_array($item) && !in_array($name, $exclude)){
			            ++$count;
				        $valid_packs[$name] = $item;
                    }
                }
                return array(
                    'count' => $count,
                    'directories' => $valid_packs
                );
            }

			// output meta spans and logs tmpl for manage pages // todo -  use JS object rather than spans
			function manage_packs_meta(){
				?>
                <span id="ajaxUrl" rel="<?php echo $this->wp_ajax_url; ?>"></span>
                <span id="delete-ok" rel='admin.php?page=<?php echo $this->microthemespage;?>&mt_action=tvr_delete_ok&_wpnonce=<?php echo wp_create_nonce('tvr_delete_ok'); ?>'></span>
                <span id="zip-folder" rel="<?php echo $this->thispluginurl.'zip-exports/'; ?>"></span>
				<?php

				//echo $this->display_log_item('error', array('short'=> '', 'long'=> ''), 0, 'id="log-item-template"');
			}

			function pack_pagination($page, $total_pages, $total_packs, $start, $end) {
				?>
                <ul class="tvr-pagination">
					<?php
					$i = $total_pages;
					while ($i >= 1){
						echo '
						<li class="page-item">';
						if ($i == $page) {
							echo '<span>'.$i.'</span>';
						} else {
							echo '<a href="admin.php?page='. $this->microthemespage . '&packs_page='.$i.'">'.$i.'</a>';
						}
						echo '
						</li>';
						--$i;
					}
					echo '<li class="displaying-x">' .
					     sprintf(esc_html__('Displaying %1$s - %2$s of %3$s', 'microthemer'), $start, $end, $total_packs) . '</li>';

					if (!empty($this->preferences['theme_in_focus']) and $total_packs > 0){
						$url = 'admin.php?page=' . $this->managesinglepage . '&design_pack=' . $this->preferences['theme_in_focus'];
						$name = $this->readable_name($this->preferences['theme_in_focus']);
						?>
                        <li class="last-modified" rel="<?php echo $this->preferences['theme_in_focus']; ?>">
							<?php esc_html_e('Last modified design pack: ', 'microthemer'); ?><a title="<?php printf(esc_attr__('Edit %s', 'microthemer'), $name); ?>"
                                                                                                 href="<?php echo $url; ?>"><?php echo esc_html($name); ?>
                            </a>
                        </li>
						<?php
					}
					?>
                </ul>
				<?php
			}

			/*
			function display_left_menu_icons() {

				if ($this->preferences['buyer_validated']){
					$unlock_class = 'unlocked';
					$unlock_title = esc_attr__('Validate license using a different email address', 'microthemer');
				} else {
					$unlock_class = '';
					$unlock_title = esc_attr__('Enter your PayPal email (or the email listed in My Downloads) to unlock Microthemer', 'microthemer');
				}

				// set 'on' buttons
				$code_editor_class = $this->preferences['show_code_editor'] ? ' on' : '';
				$ruler_class = $this->preferences['show_rulers'] ? ' on' : '';


				//
				$html = '
					<div class="unlock-microthemer '.$unlock_class.' v-left-button show-dialog popup-show" rel="unlock-microthemer" title="'.$unlock_title.'"></div>

					<div id="save-interface" class="save-interface v-left-button" title="' . esc_attr__('Manually save UI settings (Ctrl+S)', 'microthemer') . '"></div>

					<div id="toggle-highlighting" class="v-left-button"
					title="'. esc_attr__('Toggle highlighting', 'microthemer') .'"></div>

					<div id="toggle-rulers" class="toggle-rulers v-left-button '.$ruler_class.'"
						title="'. esc_attr__('Toggle rulers on/off', 'microthemer') .'"></div>

					<div class="ruler-tools v-left-button tvr-popright-wrap">

						<div class="tvr-popright">
							<div class="popright-sub">
								<div id="remove-guides" class="remove-guides v-left-button"
						title="'. esc_attr__('Remove all guides', 'microthemer') .'"></div>
							</div>
						</div>
					</div>


					<div class="code-tools v-left-button tvr-popright-wrap popup-show">

						<div id="edit-css-code" class="edit-css-code v-left-button new-icon-group '.$code_editor_class.'"
						title="'. esc_attr__('Code editor view', 'microthemer') .'"></div>

						<div class="tvr-popright">
							<div class="popright-sub">
								<div id="display-css-code" class="display-css-code v-left-button show-dialog popup-show" rel="display-css-code" title="' . esc_attr__('View the CSS code Microthemer generates', 'microthemer') . '"></div>
							</div>
						</div>
					</div>


					<div class="preferences-tools v-left-button tvr-popright-wrap popup-show">

						<div class="display-preferences v-left-button show-dialog popup-show" rel="display-preferences" title="' . esc_attr__('Set your global Microthemer preferences', 'microthemer') . '"></div>

						<div class="tvr-popright">
							<div class="popright-sub">

								<div class="edit-media-queries v-left-button show-dialog popup-show" rel="edit-media-queries"
					title="' . esc_attr__('Edit Media Queries', 'microthemer') . '"></div>

							</div>
						</div>
					</div>


					<div class="pack-tools v-left-button tvr-popright-wrap popup-show">

						<div class="manage-design-packs v-left-button show-dialog new-icon-group popup-show" rel="manage-design-packs" title="' . esc_attr__('Install & Manage your design packages', 'microthemer') . '"></div>

						<div class="tvr-popright">
							<div class="popright-sub">

								<div class="import-from-pack v-left-button show-dialog popup-show" rel="import-from-pack" title="' . esc_attr__('Import settings from a design pack', 'microthemer') . '"></div>

					<div class="export-to-pack v-left-button show-dialog popup-show" rel="export-to-pack" title="' . esc_attr__('Export your work as a design pack', 'microthemer') . '"></div>

							</div>
						</div>
					</div>


					<!--<div class="display-share v-left-button show-dialog" rel="display-share" title="' . esc_attr__('Spread the word about Microthemer', 'microthemer') . '"></div>-->




					<div class="reset-tools v-left-button tvr-popright-wrap popup-show">

						<div class="display-revisions v-left-button show-dialog new-icon-group popup-show" rel="display-revisions" title="' . esc_attr__("Restore settings from a previous save point", 'microthemer') . '"></div>

						<div class="tvr-popright">
							<div class="popright-sub">
								<div id="ui-reset" class="v-left-button folder-reset"
								title="' . esc_attr__("Reset the interface to the default empty folders", 'microthemer') . '"></div>
								<div id="clear-styles" class="v-left-button styles-reset"
								title="' . esc_attr__("Clear all styles, but leave folders and selectors intact", 'microthemer') . '"></div>
							</div>
						</div>
					</div>


					<div data-docs-index="1" class="program-docs v-left-button show-dialog new-icon-group popup-show" rel="program-docs"
					title="' . esc_attr__("Learn how to use Microthemer", 'microthemer') . '"></div>

					<div class="toggle-full-screen v-left-button" rel="toggle-full-screen"
					title="' . esc_attr__("Full screen mode", 'microthemer') . '"></div>

					<a class="back-to-wordpress v-left-button" title="' . esc_attr__("Return to WordPress dashboard", 'microthemer') . '"
					href="'.$this->wp_blog_admin_url.'"></a>';
				return $html;

			}
			*/


			// display the main menu
			function system_menu($dialog_footer = false){

				$html = '<ul class="mt-options-menu">';

				// menu groups
				foreach ($this->menu as $group_key => $arr){

					$html.= '
					<li class="mt-group '.$group_key.'">
						<span class="mt-group-heading mt-group-heading-'.$group_key.'">'. $arr['name'] . '</span>';
					if (!empty($arr['sub'])){
						$html.= '<ul class="mt-sub mt-sub-'.$group_key.'">';

						// menu items
						foreach ($arr['sub'] as $item_key => $arr2){

							// dialog footer only needs a subset of options
							if ($dialog_footer and empty($arr2['dialog'])) continue;

							// format the data attributes
							$data_attr = '';
							if ( !empty($arr2['data_attr']) ){
								foreach($arr2['data_attr'] as $da_key => $da_value){
									$data_attr.= 'data-'.$da_key.'="'.$da_value.'"';
								}
							}

							// format rel, class, id, data
							$rel = !empty($arr2['dialog']) ? 'rel="'.$arr2['class'].'"' : '';
							$id = !empty($arr2['id']) ? 'id="'.$arr2['id'].'"' : '';
							$link_id = !empty($arr2['link_id']) ? 'id="'.$arr2['link_id'].'"' : '';
							$link_target = !empty($arr2['link_target']) ? 'target="'.$arr2['link_target'].'"' : '';
							$icon_id = !empty($arr2['icon_id']) ? 'id="'.$arr2['icon_id'].'"' : '';
							$icon_class = !empty($arr2['class']) ? $arr2['class'] : '';
							$text_class = !empty($arr2['text_class']) ? $arr2['text_class'] : '';
							$text_attr = !empty($arr2['short_name']) ?
								'data-sl="'.$arr2['short_name'].'" data-ll="'.$arr2['name'].'"' : '';
							$class = 'item-' . $icon_class;
							$class.= (isset($arr2['toggle'])) ? ' mt-toggle' : '';
							$class.= (isset($arr2['item_link'])) ? ' item-link' : '';
							$class.= (!empty($arr2['new_set'])) ? ' new-set' : '';
							$show_dialog = $class.= (!empty($arr2['dialog'])) ? ' show-dialog' : '';
							$icon_title = !empty($arr2['icon_title']) ? 'title="'.$arr2['icon_title'].'"' : '';
							$sup_checkboxes = !empty($arr2['checkboxes']) ? $arr2['checkboxes'] : false;

							// item
							$html.= '<li '.$id.' '.$data_attr.' '.$rel.' class="mt-item '.$item_key.' '.$class.'"
							>';

							// should a link wrap the icon and text?
							if (!empty($arr2['item_link'])){
								$html.= '<a '.$link_id.' '.$link_target.' href="'.$arr2['item_link'].'">';
							}

							// icon
							$html.= '<span '.$icon_id.' class="mt-menu-icon '.$icon_class.' '.$show_dialog.'" '.$icon_title.'></span>';

							// text label
							$colon = isset($arr2['toggle']) & ($item_key!= 'highlighting') ? ':' : '';
							$html.= '<span class="mt-menu-text '.$show_dialog.' '.$text_class.'"
							title="'.$arr2['title'].'" '.$text_attr.'>'
							        .$arr2['name'].$colon.'</span>';

							// do we need toggle?
							if (isset($arr2['toggle'])){
								$html.= $this->toggle($item_key, $arr2);
							}

							// do we display keyboard shortcut
							if (isset($arr2['keyboard_shortcut'])){
								$html.= '<span class="keyboard-sh">'.$arr2['keyboard_shortcut'].'</span>';
							}

							// do we need input?
							if (isset($arr2['input'])){
								$input_id = !empty($arr2['input_id']) ? 'id="'.$arr2['input_id'].'"' : '';
								$input_name = !empty($arr2['input_name']) ? $arr2['input_name'] : '';
								$input_placeholder = !empty($arr2['input_placeholder']) ? $arr2['input_placeholder'] : '';
								$html.= '
								<div class="combobox-wrap tvr-input-wrap">
								
								    '.$this->maybe_output_supplementary_checkboxes($sup_checkboxes).' 
									<input type="text" name="'.$input_name.'" 
									placeholder="'.$input_placeholder.'"
									'.$input_id.' class="combobox has-arrows"
									rel="'.$arr2['combo_data'].'"
									value="'.$arr2['input'].'" />
									<span class="combo-arrow"></span>
									<span class="tvr-button '.$arr2['button']['class'].'">
								    '.$arr2['button']['text'].'
								    </span>
								    
								</div>
								';
							}

							// custom display value
							if (isset($arr2['display_value'])){
								$html.= $arr2['display_value'];
							}

							if (!empty($arr2['item_link'])){
								$html.= '</a>';
							}

							$html.= '</li>';
						}
						$html.= '</ul>';
					}
					$html.= '</li>';
				}
				$html.= '</ul>';
				return $html;
			}

			function maybe_output_supplementary_checkboxes($checkboxes){

			    if (!$checkboxes){
			        return '';
                }

			    $html = '';

			    if (!empty($checkboxes)){
			        foreach ($checkboxes as $item){
				        $html.= '<div class="menu-supplementary-checkbox tvr-clearfix">
                            <input type="checkbox" name="'.$item['name'].'"> 
                            <span class="fake-checkbox "></span>
                            <span class="ef-label">'.$item['label'].'</span>
                        </div>';
                    }
                }

                return $html;

            }

			function toggle($item_key, $arr){
				$on = $arr['toggle'] ? 'on' : '';
				$id = !empty($arr['toggle_id']) ? 'id="'.$arr['toggle_id'].'"' : '';
				// set dynamic title (adding this feature slowly)
				$pos_neg = $title = '';
				if( !empty($arr['data-pos']) ){
					$pos_neg = 'data-pos="'.$arr['data-pos'].'" data-neg="'.$arr['data-neg'].'" ';
					$title = !$on ? $arr['data-pos'] : $arr['data-neg'];
					$title = 'title="'.$title.'"';
				}

				$html = '';
				//$html.= print_r($on, true);
				$html.= '
				<div '.$id.' class="mtonoffswitch ui-toggle uit-par '.$on.'"
				data-aspect="'.$item_key.'" '.$pos_neg.' '.$title.'>
					<input type="checkbox" name="mtonoffswitch" class="mtonoffswitch-checkbox"
					id="mymtonoffswitch-'.$item_key.'">
					<label class="mtonoffswitch-label ui-toggle" for="mymtonoffswitch-'.$item_key.'">
						<span class="mtonoffswitch-inner ui-toggle"></span>
						<span class="mtonoffswitch-switch ui-toggle"></span>
					</label>
				</div>';
				return $html;
			}

			// Resolve property/value input fields
			function resolve_input_fields(
				$section_name,
				$css_selector,
				$array,
				$property_group_array,
				$property_group_name,
				$property,
				$value,
				$con = 'reg',
				$key = 1,
				$sel_code = 'selector_code') {
				$html = '';

				// get value object, array, and string value
                $valueArray = false;
				$valueObject = $value;
				$value = is_array($valueObject) && !empty($valueObject['value'])
                    ? $valueObject['value']
                    : ''; // should this be

                if (is_array($value)){
	                $valueArray = $value;
	                $value = !empty($valueArray[0]) ? $valueArray[0] : '';
                }

				// don't display legacy properties or the image display field
				if (!$this->is_legacy_prop($property_group_name, $property) and !strpos($property, 'img_display') ){
					include $this->thisplugindir . 'includes/resolve-input-fields.inc.php';
				}
				return $html;
			}

			// search posts by title or slug so we get precise search results
			function search_by_title_or_slug( $search, $wp_query ) {

				if ( ! empty( $search ) && ! empty( $wp_query->query_vars['search_terms'] ) ) {

				    global $wpdb;

					$q = $wp_query->query_vars;
					$n = ! empty( $q['exact'] ) ? '' : '%';
					$search = array();

					foreach ( ( array ) $q['search_terms'] as $term ) {

					    $sql_term = $n . $wpdb->esc_like( $term ) . $n;

						$search[] = $wpdb->prepare(
                        "$wpdb->posts.post_title LIKE %s or 
                            $wpdb->posts.post_name LIKE %s or 
                            $wpdb->posts.post_type LIKE %s",
                            $sql_term,
                            $sql_term,
                            $sql_term
						);
                    }

					if ( ! is_user_logged_in() ){
						$search[] = "$wpdb->posts.post_password = ''";
                    }

					$search = ' AND ' . implode( ' AND ', $search );
				}

				//echo 'mysearch: ' . $search;

				return $search;
			}

			function get_custom_post_types(){

			    $args = array(
					'public'   => true,
					'_builtin' => false,
				);

				$output = 'objects'; // names or objects, note names is the default
				$operator = 'and'; // 'and' or 'or'

				return get_post_types( $args, $output, $operator );
			}

			function format_posts_of_type(
			    $post_type, $category, $permalink_structure, $common_config, &$urls
            ){

			    $isCustomPosts = ($post_type !== 'page' && $post_type !== 'post');
			    $customPostsPathPrefix = $isCustomPosts
                    ? '/'.$post_type
                    : '';

			    $items = get_posts(
					array_merge($common_config, array('post_type'=> $post_type))
				);

				foreach($items as $item){

				    // I noticed a strange bug whereby "kit" should have got an elementor template post in 'My Templates'
                    // category, but also got the search result in all other post categories inc regular page/post
                    if ($item->post_type !== $post_type or $item->post_status === 'auto-draft'){
                        continue;
                    }

					$label = $item->post_title;

                    if ($item->post_status === 'draft'){
	                    $label.= ' — Draft';
                    }


					$path = $customPostsPathPrefix.'/'.$item->post_name.'/';
					//$url = rtrim($root_home_url, '/') . $path;

					// if non-standard permalink structure, we have to use the DB method of getting the URL
					if ($permalink_structure !== '/%postname%/' or
                        $item->post_type === 'ct_template' or
                        $item->post_status === 'draft'){

					    // format URL as draft preview
					    if ($item->post_status === 'draft'){
						    $url = $item->guid.'&preview=true'; // maybe build this using id and pos_type
                        }

                        // it seems to be a quirk of Oxygen that the template admin screen must be loaded first
					    elseif ($item->post_type === 'ct_template'){
							$url = $this->wp_blog_admin_url . 'post.php?post=' . $item->ID.'&action=edit';
						    //$url = get_permalink($item).'&ct_builder=true';
                            // enable this after fixing forever loading issue (maybe due to post locking...)
						}

						// non-standard permalink structure
						else {
							$url = get_permalink($item);
                        }

						//$url = get_permalink($item);

						$path = $this->root_rel($url, false, true);
					}

					// exception for Oxygen - template pages produce PHP error if loaded on frontend
					// without ct_builder parameter
					/*if ($item->post_type === 'ct_template'){
						$path = tvr_common::append_url_param($path, 'ct_builder', 'true');
					}*/

					$urls[$post_type][$label] = array(
						'label' => $label,
						'value' => $path,
						'category' => $category,
						'item_id' => !empty($item->ID) ? $item->ID : false,
						//'all' => $item, // debug
                        //'config' => array_merge($common_config, array('post_type'=> $post_type))
					);
				}

				// sort alphabetically within category - actually no, have recently modified near top
				// ksort($urls[$post_type]);

			}

			// get example category, author, archive, 404
			function get_resource_example(){
                // todo
            }

			function get_site_pages($searchTerm = null){

				// get URL vars
                $blog_details = function_exists('get_blog_details')
                    ? get_blog_details( $this->multisite_blog_id )
                    : false;
				/*$root_home_url = $blog_details
                    ? rtrim($blog_details->path, '/')
                    : $this->home_url;*/

                $permalink_structure = get_option('permalink_structure');
				$users_can_register = get_option('users_can_register');
			    $common_config = array(
                    'post_status' => array('publish', 'draft'), // we want user to be able to access drafts
                    'numberposts' => 8,
                    'suppress_filters' => false,
                    'orderby' => 'modified',
                    'order' => 'DESC',
                    's' => $searchTerm
                );

			    // Get data
                $urls = array();
				$formatted_urls = array();
                $urlTypes = array(
	                'page' => esc_html__('Pages', 'microthemer'),
	                'post' => esc_html__('Posts', 'microthemer'),
	                'wordpress' => esc_html__('WordPress', 'microthemer'),
	                'custom_posts' => esc_html__('Custom posts', 'microthemer'),
                    'general' => esc_html__('General', 'microthemer'),
                );
                $custom_post_types = $this->get_custom_post_types();

                foreach ($urlTypes as $key => $category){

                    // regular post or page
                    if ($key === 'page' || $key === 'post'){
                        $post_type = $key;
                        $this->format_posts_of_type(
	                        $post_type, $category, $permalink_structure, $common_config, $urls
                        );
                    }

	                // custom posts
                    elseif ($key === 'custom_posts'){

		                //$urls[$key] = $custom_post_types;
		                foreach ($custom_post_types as $index => $custom_post_type){
                            $category = $custom_post_type->label;
			                $post_type = $custom_post_type->name;
			                $this->format_posts_of_type(
				                $post_type, $category, $permalink_structure, $common_config, $urls
			                );
		                }

                    }

                    // general / wordpress
                    elseif ($key === 'general' || $key === 'wordpress'){

	                    $custom_links = array();

	                    // WordPress auth pages
	                    if ($key === 'wordpress'){

		                    $custom_links = array(
			                    array(
				                    'label' => esc_html__('Login page', 'microthemer'),
				                    'value' => $this->root_rel(wp_login_url(), false, true),
			                    ),
			                    array(
				                    'label' => esc_html__('Lost password page', 'microthemer'),
				                    'value' => $this->root_rel(wp_lostpassword_url(), false, true),
			                    )
		                    );

		                    // if registration is supported
		                    if ($users_can_register){
			                    $custom_links[] = array(
				                    'label' => esc_html__('Registration page', 'microthemer'),
				                    'value' => $this->root_rel(wp_registration_url(), false, true),
			                    );
		                    }

                        }

	                    // General types of page - finish later
	                    /*elseif ($key === 'general'){

	                        $custom_links = array(
			                    array(
				                    'label' => esc_html__('Home page', 'microthemer'),
				                    'value' => '/',
			                    ),
			                    array(
				                    'label' => esc_html__('Search page', 'microthemer'),
				                    'value' => '/?s=test',
			                    )
		                    );
	                    }*/

	                    // add the category and merge with urls array
	                    foreach ($custom_links as $j => $custom_links_array){
		                    $urls[$key][] = array_merge($custom_links_array, array('category' => $category));
                        }

                    }

                }

				// add category urls to flat array
				foreach ($urls as $key => $array){
				    if (!empty($urls[$key])){
					    $formatted_urls = array_merge($formatted_urls, array_values($urls[$key]));
                    }

				}

                //wp_die('<pre>'.print_r($urls, true).'</pre>');

                //return $urls;

                return $formatted_urls;

            }

			// Global system for creating dynamic menus (data, structure, config)
			// Note: passing array/objs into PHP/JS functions over lots of params should become standard practice
			function dyn_menu($d, $s, $c) {

				$base_key = !empty($s['base_key']) ? 'data-base-key="'.$s['base_key'].'"' : '';
				$html = '<div id="dyn-wrap-'.$s['slug'].'" class="dyn-wrap"
				data-slug="'.$s['slug'].'" '.$base_key.'>';

				// add controls if required
				if (!empty($c['controls'])){
					$input_class = !empty($s['add_button']) ? 'combobox' : '';
					$input_placeholder = !empty($s['input_placeholder']) ? 'placeholder="'.$s['input_placeholder'].'"' : '';
					$combo_arrow = '';
					if (!empty($s['combo_add_arrow'])) {
						$combo_arrow = '<span class="combo-arrow"></span>';
						$input_class.= ' has-arrows';
					}
					$html.= '
					<div class="tvr-new-item">
						<span class="tvr-input-wrap">
							<input type="text" class="new-item-input '.$input_class.'" '.$input_placeholder.' 
							name="new_item[name]" rel="'.$s['slug'].'">
							'.$combo_arrow.'
						</span>
						<span class="new-item-add tvr-button" title="'.$s['add_button'].'">'.$s['add_button'].'</span>
					</div>';
				}


				// loop through data (maybe try to make this a recursive function)
				$html.= '
				<ul id="'.$s['slug'].'-dyn-menu" class="tvr-dyn-menu">';

				foreach ($d as $k => $arr){

					$html.= $this->dyn_item($s, $k, $arr);
				}

				$html.= '
				</ul>
				</div>'; // dyn-wrap

				return $html;
			}

			function dyn_item($s, $k, $arr){

				$fields = $s['items']['fields'];

				// resolve display name, class etc
				$display_name = !empty($arr['display_name']) ? $arr['display_name'] : $arr['label'];
				$name_class = !empty($fields['label']['name_class']) ? ' '.$fields['label']['name_class'] : '';

				$html = '';
				// li item
				$html.= '
				<li id="'.$s['slug'].'-'.$k.'" class="dyn-item '.$s['level'].'-tag '.$s['slug'].' '.$s['slug'].'-'.$s['level'].'">';

				// row with sortable icon, name
				$dis_class = !empty($this->preferences['enq_js'][$k]['disabled']) ? 'item-disabled' : '';
				$html.= '
				<div class="'.$s['level'].'-row item-row '.$dis_class.'">
					<span class="'.$s['slug'].'-icon tvr-icon sortable-icon" title="'.$s['items']['icon']['title'].'"></span>
					<span class="name-text '.$s['level'].'-name'.$name_class.'">'.esc_html($display_name).'</span>';

				$html.= '
				<span class="manage-'.$s['level'].'-icons manage-icons">';

				// do action icons
				foreach ($s['items']['actions'] as $action => $a_arr){

					// output icon control e.g. disabled
					if (!empty($a_arr['icon_control'])){
						$html.= $this->icon_control(
							false,
							$action,
							!empty($this->preferences['enq_js'][$k]['disabled']),
							$s['level'],
							$k,
							'',
							'all-devices', // just to skip mq stuff
							'',
							'',
							$s['name_stub']
						);
					} else {
						// regular icon
						$a_class = !empty($a_arr['class']) ? $a_arr['class'] : '';
						$html.= '
						<span class="'.$a_class. ' '.$action.'-'.$s['level'].' tvr-icon '.$action.'-icon"
						title="'.$a_arr['title'].'"></span>';
					}

				}

				// end action icons and row
				$html.= '
					</span>
				</div>';

				// edit fields (for enq_js just hidden input so no need to have edit icon)
				$html.= '
				<div class="edit-item-form float-form hidden">';

				// editing or hidden input fields
				foreach ($s['items']['fields'] as $input_name => $f_arr){
					$input_type = $f_arr['type'];
					$val = !empty($arr[$input_name]) ? $arr[$input_name] : '';
					$input_class = !empty($f_arr['input_class']) ? ' '.$f_arr['input_class'] : '';
					$input_rel = !empty($f_arr['input_rel']) ? ' rel="'.$f_arr['input_rel'].'"' : '';
					$input = '<input type="'.$input_type.'" class="'.$s['level'].'-'.$input_name.$input_class.'"
								'.$input_rel.' name="'.$s['name_stub'].'['.$k.']['.$input_name.']" value="'.esc_html($val).'">';

					if (!empty($f_arr['input_arrows'])){
						$input.= $f_arr['input_arrows'];
                    }

					// just input if hidden
					if ($input_type == 'hidden'){
						$html.= $input;
					} else {
						// form fields
						$f_class = !empty($f_arr['field_class']) ? ' '.$f_arr['field_class'] : '';
						//$f_class = $input_type == 'checkbox' ? 'mq-checkbox-wrap' : '';
						$html.= '
						<p class="'.$f_class.'">
							<label title="'.$f_arr['label_title'].'">'.$f_arr['label'].':</label>';
						// regular text input, checkbox
						if ($input_type != 'textarea'){
							$html.= $input;
							if ($input_type == 'checkbox'){
								$html.= '<span class="fake-checkbox "></span>
									<span class="ef-label">'.$f_arr['label2'].'</span>';
							}
						} else {
							// text area
							$html.= '<textarea class="'.$s['level'].'-'.$input_name.'"
								name="'.$s['name_stub'].'['.$k.']['.$input_name.']">'.esc_html($val).'</textarea>';
						}
						$html.= '
						</p>';
					}
				}

				// maybe add recursive functionality for sub items here if needed/possible

				$html.= '
				</li>';

				return $html;
			}

			// menu section html
			function menu_section_html($section_name, $array) {

				$section_name = esc_attr($section_name); //=esc

				// get folder display name
				$display_section_name = $this->get_folder_name_inc_legacy($section_name, $array);

				$selector_count_state = $this->selector_count_state($array);

				// generate html code for sections in this loop to save having to do a 2nd loop later
				$this->initial_options_html[$this->total_sections] = $this->section_html($section_name, $array);

				$sec_class = '';

				// user disabled
				$disabled = false;
				if (!empty($array['this']['disabled'])){
					$disabled = true;
					$sec_class.= ' item-disabled';
				}

				// should feather be displayed?
				if ($selector_count_state > 0 ) {
					// need deep search of values in selectors
					if ($this->section_has_values($section_name, $array, true)){
						$sec_class.= ' hasValues';
					}
				}
				$folder_title = esc_attr__("Reorder folder", 'microthemer');

				ob_start();
				?>
                <li id="<?php echo 'strk-'.$section_name; ?>" class="section-tag strk strk-sec <?php echo $sec_class; ?>">
                    <!--<input type="hidden" class="register-section" name="tvr_mcth[<?php //echo $section_name; ?>]" value="" />-->
                    <input type="hidden" class="section-display-name"
                           name="tvr_mcth[<?php echo $section_name;?>][this][label]"
                           value="<?php echo esc_attr($display_section_name); ?>" />

                    <!--<input type='hidden' class='view-state-input section-tracker'
                           name='tvr_mcth[non_section][view_state][<?php /*echo $section_name; */?>][this]' value='0' />-->

                    <div class="sec-row item-row">
                        <span class="menu-arrow folder-menu-arrow tvr-icon"></span>
                        <span class="folder-icon tvr-icon sortable-icon" title="<?php echo $folder_title; ?>" data-title="<?php echo $folder_title; ?>"></span>

						<?php //echo $this->feather_icon('section'); ?>
                        <span class="section-name item-name">
						    <span class="name-text selector-count-state"
                              rel="<?php echo $selector_count_state; ?>"><?php echo $display_section_name; ?></span><?php
							if ($selector_count_state > 0) {
								echo '<span class="folder-count-wrap count-wrap"> (<span class="folder-state-count state-count">'.$selector_count_state.'</span>)</span>';
							}
							// update global $total_selectors count
							$this->total_selectors = $this->total_selectors + $selector_count_state;
							?>
						</span>
                        <span class="edit-section-form hidden">
							<input type='text' class='rename-input' name='rename_section'
                                   value='<?php echo $display_section_name; ?>' />
								<span class='rename-button tvr-button' title="<?php esc_attr_e("Rename folder", 'microthemer'); ?>">
									<?php printf( esc_html__('Rename', 'microthemer') ); ?>
								</span>
								<span class='cancel-rename-section cancel link' title="<?php esc_attr_e("Cancel rename", 'microthemer'); ?>">
									<?php printf( esc_html__('Cancel', 'microthemer') ); ?>
								</span>
						</span>
                        <span class="manage-section-icons manage-icons">

							<?php
							// toggle for extra action icons
							echo $this->extra_actions_icon();
							/*echo $this->ui_toggle(
								'show_extra_actions',
								'conditional', // only wizard toggle has title/on class (easier than maintaining dynamically)
								'conditional',
								false,
								'extra-actions-toggle tvr-icon',
								false,
								// instruct tooltip to get content dynamically
								array('dataAtts' => array(
									'dyn-tt-root' => 'show_extra_actions',
									'no-save' => 1
								))
							);
							*/
							?>

                            <span class="extra-row-actions">
								<span class="reveal-add-selector tvr-icon add-icon" title="<?php esc_attr_e("Add selector to this folder", 'microthemer'); ?>"></span>
								<span class="copy-section tvr-icon copy-icon" title="<?php esc_attr_e("Copy Folder", 'microthemer'); ?>"></span>
								<span class="delete-section tvr-icon delete-icon" title="<?php esc_attr_e("Delete folder", 'microthemer'); ?>"></span>
								<?php echo $this->clear_icon('section'); ?>

							</span>


							<?php echo $this->icon_control(false,'disabled', $disabled, 'section', $section_name); ?>

                            <span class="toggle-rename-section tvr-icon edit-icon" title="<?php esc_attr_e("Rename Folder", 'microthemer'); ?>"></span>

						</span>
                    </div>

                    <ul class="selector-sub">
                        <li class="add-selector-list-item">
                            <div class="sel-row item-row"><?php
								$tip = esc_html__('Non-coders should use the selector wizard instead of using these form fields.', 'microthemer') . '<br />' . esc_html__('Just double-click something on your site!');
								if (!$this->optimisation_test){
									$this->selector_add_modify_form('add', $tip);
								}

								?></div>

                        </li>
						<?php
						/*if (!$this->optimisation_test){
							if ( is_array($array) ) {
								$sel_loop_count = 0;
								foreach ( $array as $css_selector => $array) {
									if ($css_selector == 'this') continue;
									++$sel_loop_count;
									++$this->sel_count;
									// selector list item
									echo $this->menu_selector_html($section_name, $css_selector, $array, $sel_loop_count);
								}
							}
						}*/
						?>

                    </ul>
					<?php

					?>

                </li>
				<?php

                return ob_get_clean();
			}



			// menu single selector html
			function menu_selector_html($section_name, $css_selector, $array, $sel_loop_count) {

				ob_start();

				$sel_class = '';

				/* determine which style groups are active
				$style_count_state = 0;
				foreach ($this->propertyoptions as $property_group_name => $junk) {
					if ($this->pg_has_values_inc_legacy_inc_mq($section_name, $css_selector, $array, $property_group_name)) {
						++$style_count_state;
					}
				}*/

				$style_count_state = $this->selector_has_values($section_name, $css_selector, $array, true);

				// trial disabled (all sels will be editable even in free trial in future)
				if (!$this->preferences['buyer_validated'] and $this->sel_count > 15 ) {
					$sel_class.= 'trial-disabled'; // visually signals disabled and, prevents editing
				}

				// user disabled
				$disabled = false;
				if (!empty($array['disabled'])){
					$disabled = true;
					$sel_class.= ' item-disabled';
				}

				// should feather be displayed?
				if ($style_count_state > 0) {
					$sel_class.= ' hasValues';
				}

				// can't recall why I went down this route of storing label and code in piped single value.
				if (is_array($array) and !empty($array['label'])){
					$labelCss = explode('|', $array['label']);
					// convert my custom quote escaping in recognised html encoded single/double quotes
					$selector_title = esc_attr(str_replace('cus-', '&', $labelCss[1]));
				} else {
					$labelCss = array('', '');
					$array['label'] = '';
					$selector_title = '';
				}

				?>
                <li id="<?php echo 'strk-'.$section_name.'-'.$css_selector; ?>" class="selector-tag strk strk-sel <?php echo $sel_class; ?>">

                    <!--<input type='hidden' class='register-selector' name='tvr_mcth[<?php /*echo $section_name; */?>][<?php /*echo $css_selector; */?>]' value='' />-->

                    <!--<input type='hidden'
                           class='view-state-input selector-tracker' name='tvr_mcth[non_section][view_state][<?php /*echo $section_name;*/?>][<?php /*echo $css_selector;*/?>]' value='0' />-->

                    <input type='hidden' class='selector-label' name='tvr_mcth[<?php echo $section_name; ?>][<?php echo $css_selector; ?>][label]' value='<?php echo $array['label']; ?>' />

                    <div class="sel-row item-row">
                        <span class="tvr-icon selector-sortable-icon sortable-icon" title="<?php esc_attr_e("Reorder selector", 'microthemer'); ?>"></span>
						<?php echo $this->feather_icon('selector'); ?>
                        <span class="selector-name item-name change-selector" title="<?php echo $selector_title; ?>">
						<span class="name-text style-count-state change-selector"
                              rel="<?php echo $style_count_state; ?>"><?php echo esc_html($labelCss[0]); ?></span>
							<?php
							/* FEATHER SYSTEM SUPERSEDES
							if ($style_count_state > 0) {
								echo ' <span class="count-wrap change-selector">(<span class="state-count change-selector">'.$style_count_state.'</span>)</span>';
							}
							*/
							?>
						</span>
                        <span class="manage-selector-icons manage-icons">

							<?php
							// toggle for extra action icons
							echo $this->extra_actions_icon();
							/*echo $this->ui_toggle(
								'show_extra_actions',
								'conditional', // only wizard toggle has title/on class (easier than maintaining dynamically)
								'conditional',
								false,
								'extra-actions-toggle tvr-icon',
								false,
								// instruct tooltip to get content dynamically
								array('dataAtts' => array(
									'dyn-tt-root' => 'show_extra_actions'
								))
							);
							*/
							?>

                            <span class="extra-row-actions">
								<span class="tvr-icon selector-icon retarget-selector" title="<?php esc_attr_e("Re-target selector ", 'microthemer'); ?>"></span>
								<span class="copy-selector tvr-icon copy-icon" title="<?php esc_attr_e("Copy selector", 'microthemer'); ?>"></span>
							<span class="delete-selector tvr-icon delete-icon" title="<?php esc_attr_e("Delete selector", 'microthemer'); ?>"></span>
								<?php echo $this->clear_icon('selector'); ?>
							</span>

							<?php echo $this->icon_control(false,'disabled', $disabled, 'selector', $section_name, $css_selector); ?>

                            <span class="toggle-modify-selector tvr-icon edit-icon" title="<?php esc_attr_e("Edit selector", 'microthemer'); ?>"></span>

							<span class="tvr-icon hightlight-icon highlight-preview" title="<?php esc_attr_e("Highlight selector", 'microthemer'); ?>"></span>
						</span>
						<?php
						$tip = esc_html__('Give your selector a better descriptive name and/or modify the CSS selector code.', 'microthemer');
						if (!$this->optimisation_test){
							$this->selector_add_modify_form('edit', $tip, $labelCss, $section_name, $css_selector);
						}
						?>
                    </div>
                </li>
				<?php

				return ob_get_clean();

			}

			// add/modify selector form
			function selector_add_modify_form($con, $tip, $labelCss = '', $section_name ='', $css_selector = '') {
				$display = ucwords($con);
				if (is_array($labelCss)) {
					$display_selector_name = esc_attr($labelCss[0]);
					// convert my custom quote escaping in recognised html encoded single/double quotes
					$selector_css = esc_attr(str_replace('cus-', '&', $labelCss[1]));
				} else {
					$display_selector_name = '';
					$selector_css = '';
				}

				// save current sels in quick lookup array
				if (!empty($selector_css)){
					$this->sel_lookup[$selector_css] = 'strk-'.$section_name.'-'.$css_selector;
				}

				?>
                <div class='<?php echo $con; ?>-selector-form float-form hidden'>
                    <!--<p class="tip">

						<span><?php echo $tip; ?></span>

					</p>-->
                    <p class="menu-sel-name-edit">
                        <label><?php esc_html_e('Label:', 'microthemer'); ?></label>
                        <span class="tvr-input-wrap selector-name-input-wrap">
							<input type='text' class='selector-name-input' name='<?php echo $con; ?>_selector[label]' value='<?php echo esc_attr($display_selector_name); ?>' />
						</span>
                    </p>
                    <p>
                        <label><?php esc_html_e('Code:', 'microthemer'); ?></label>
                        <span class="tvr-input-wrap selector-css-input-wrap">
							<input type='text' class='selector-css-textarea' name='<?php echo $con; ?>_selector[css]' value='<?php echo $selector_css; ?>' />
						</span>

                    </p>

					<?php echo $this->ui_toggle(
						'selname_code_synced',
						'conditional', // only wizard toggle has title/on class (easier than maintaining dynamically)
						'conditional',
						false,
						'code-chained-icon tvr-icon selname-code-sync',
						false,
						// instruct tooltip to get content dynamically
						array('dataAtts' => array(
							'dyn-tt-root' => 'selname_code_synced'
						))
					); ?>

                    <p class="sel-toggles">
						<?php
						if ($con == 'edit'){
							?>
                            <span class="polyfills">
								<?php
								/*foreach ($this->polyfills as $poly){
									if ($this->preferences[$poly.'_by_default'] != 1){
										$on = false;
										if (!empty($this->options[$section_name][$css_selector][$poly])) {
											$on = true;
										}
										echo $this->icon_control(false, $poly, $on, 'selector', $section_name, $css_selector);
									}
								}*/
								?>
							</span>
							<?php
							// output any disabled tab inputs
							foreach ($this->combined_devices() as $key => $m_query){
								// normalise $opts array for checking
								if ($key == 'all-devices'){
									$opts = $this->options;
								} else {
									if (empty($this->options['non_section']['m_query'][$key])){
										continue;
									}
									$opts = $this->options['non_section']['m_query'][$key];
								}
								if (!empty($opts[$section_name][$css_selector]['tab']['disabled'])){
									echo $this->icon_control(false,'disabled', true, 'tab-input', $section_name, $css_selector, $key);
								}
							}
						}


						// translation friendly button display
						$button_name = __("Create Selector", 'microthemer');
						if ($display == 'Edit') {
							$button_name = __("Save Selector", 'microthemer');
						}
						?>
                        <span class='<?php echo $con; ?>-selector tvr-button'
                              title="<?php echo esc_attr($button_name); ?>">
							<?php echo esc_html($button_name); ?>
						</span>
                        <span class="cancel-<?php echo $con; ?>-selector cancel link"><?php esc_html_e('Cancel', 'microthemer'); ?></span>
                    </p>
                </div>
				<?php
			}

			// section html
			function section_html($section_name, $array) {
				$html = '';
				$html.= '
				<li id="opts-'.$section_name.'" class="section-wrap section-tag">
					'.$this->all_selectors_html($section_name, $array).'
				</li>';
				return $html;
			}

			function all_selectors_html($section_name, $array, $force_load = 0) {
				$html = '';
				$html.= '<ul class="selector-sub">';
				// loop the CSS selectors if they exist
				/*if (!$this->optimisation_test){
					if ( is_array($array) ) {
						$this->sel_loop_count = 0; // reset count of selector in section
						foreach ($array as $css_selector => $sel_array) {
							if ($css_selector == 'this') continue;
							++$this->sel_loop_count;
							$html .= $this->single_selector_html($section_name, $css_selector, $sel_array, $force_load);
						}
					}
				}*/
				$html.= '</ul>';
				return $html;
			}

			// selector html
			function single_selector_html($section_name, $css_selector, $array, $force_load = 0) {
				++$this->sel_option_count;
				$html = '';
				$css_selector = esc_attr($css_selector); //=esc
				// disable sections locked by trial
				if (!$this->preferences['buyer_validated'] and $this->sel_option_count > 15) {
					$trial_disabled = 'trial-disabled';
				} else {
					$trial_disabled = '';
				}
				$html.= '<li id="opts-'.$section_name.'-'.$css_selector.'"
				class="selector-tag selector-wrap '.$trial_disabled.'">';

				// only load style options if we need to force load
				if ($force_load == 1) {
					$html.= $this->all_option_groups_html($section_name, $css_selector, $array);
				}
				$html.= '</li>';
				return $html;
			}

			// determine the number of selectors in the array
			function selector_count_state($array) {

			    // with empty folder, might not be an array
			    if (!is_array($array)){
			        return 0;
                }

			    $selector_count_state = count($array);
				$selector_count_recursive = count($array, COUNT_RECURSIVE);

				// if the 2 values are the same, the $selector_count_state variable refers to an empty value
				if ($selector_count_state == $selector_count_recursive) {
					$selector_count_state = 0;
				}
				// [this] will be counted as extra selector. Fix.
				if ($selector_count_state > 0 and array_key_exists('this', $array)){
					--$selector_count_state;
				}
				return $selector_count_state;
			}

			function css_group_icons(){

			    // display pg icons
				$html = '
				<ul class="styling-option-icons">';

				// display the pg icons
				$i = -1;
				$done = array();
				foreach ($this->propertyoptions as $property_group_name => $junk) {

					$i++;
					$class = '';

					// check if we are starting a new property group category
					$first_item = $this->get_first_item( $this->propertyoptions[$property_group_name] );
					$new_pg_cat = (!empty($first_item['new_pg_cat']) and empty($done[$property_group_name]))
						? $first_item['new_pg_cat']
						: false;
					$close_pg_cat_li = $i === 0 ? '' : '</ul></li>';

					// if new cat, close previous and start new
					if ($new_pg_cat){
						$html.= $close_pg_cat_li . '
                        <li class="new-pg-cat">
                            <ul class="pg-cat-sub">
                                <li class="new-pg-cat-label">'.$new_pg_cat.'</li>';
						$done[$property_group_name] = true;
					}

					// icon
					$html.='
						 <li class="pg-icon pg-icon-'.$property_group_name.' '.$class.'"
						 rel="'.$property_group_name.'" title="'.$this->property_option_groups[$property_group_name].'">
						 </li>';
				}

				// close new-pg-cat item and list
				$html.='
                    </ul></li>
				</ul>';

				return $html;
            }


			// display property group icons and options
			function all_option_groups_html($section_name, $css_selector, $array){

				// get the last viewed property group
				$pg_focus = ( !empty($array['pg_focus']) ) ? $array['pg_focus'] : '';

				// display actual fields
				$html ='
				<ul class="styling-options">';

				// do all-device and MQ fields
				foreach ($this->propertyoptions as $property_group_name => $junk) {
					$html.= $this->single_option_group_html(
						$section_name,
						$css_selector,
						$array,
						$property_group_name,
						$pg_focus);
				}

				$html.=

                    // whole property group
                    $this->mt_hor_scroll_buttons('style', 'li') .

                    // single template/auto column
                    $this->mt_hor_scroll_buttons('gridcolumns', 'li') .

                    // single template/auto row
                    $this->mt_hor_scroll_buttons('gridrows', 'li') .
                    //$this->sidebar_size_controls() .

                 '</ul>';

				return $html;
			}

			// Haven't found a nice way to slot this in.
            // There are CSS issues with resizable needing scroll and absolute positing not fixing to bottom with scroll
            // Using CSS resize because jQuery resize is really jerky
			function sidebar_size_controls(){

			    $sizes = array(
				   '0' => esc_attr__('Collapse', 'microthemer'),
                   'sm' => esc_attr__('Small', 'microthemer'),
                   'md' => esc_attr__('Medium', 'microthemer'),
                   'lg' => esc_attr__('Large', 'microthemer'),
                   //'xl' => esc_attr__('Exra large', 'microthemer'),
                );

				$sizes_html = '';
			    foreach ($sizes as $key => $label){
				    $sizes_html.= '<span class="mt-sc-option mt-sc-option-'.$key.'" data-type="'.$key.'"
				    title="'.$label.'"
				    >'.$key.'</span>';
                }

				return '
                <li class="mt-sidebar-controls">
                    <span class="mt-sc-label">'.esc_html__('Size', 'microthemer').': </span>
                    '.$sizes_html.'
                    <span class="mt-sc-drag-handle" title="Drag sidebar size"></span>
                </li>';
			}

			function mt_hor_scroll_buttons($type, $el = 'li'){
				return '
                    <'.$el.' class="scroll-lr-buttons scroll-lr-buttons-'.$type.'">
                        <span class="mt-scroll-row mt-scroll-left mt-scroll-'.$type.'" data-type="'.$type.'"></span>
                        <span class="mt-scroll-row mt-scroll-right mt-scroll-'.$type.'" data-type="'.$type.'"></span>
                    </'.$el.'>';
			}

			// if a pg group has loaded but no values were added we don't want to load it into the dom
			function pg_has_values($array){
				if (empty($array) or !is_array($array)){
					return false;
				}
				$no_values = true;
				foreach ($array as $key => $value){
					// must allow zero values!
					if ( !empty($value) or $value === 0 or $value === '0'){
						$no_values = false;
						break;
					}
				}
				return $no_values ? false : true;
			}

			// are legacy values present for pg group?
			function has_legacy_values($styles, $property_group_name){
				$legacy_values = false;
				if (!empty($this->legacy_groups[$property_group_name]) and is_array($this->legacy_groups[$property_group_name])){
					foreach ($this->legacy_groups[$property_group_name] as $leg_group => $array){
						// check if the pg has values and they are specifically ones have have moved to this pg
						if ( !empty($styles[$leg_group]) and
						     $this->pg_has_values($styles[$leg_group]) and
						     $this->props_moved_to_this_pg($styles[$leg_group], $array)){
							$legacy_values = $styles[$leg_group];
							break;
						}
					}
				}
				return $legacy_values;
			}

			function pg_has_values_inc_legacy($array, $property_group_name){
				$styles_found = false;
				if (!empty($array['styles'][$property_group_name]) and $this->pg_has_values($array['styles'][$property_group_name])) {
					$styles_found['cur_leg'] = 'current';
				} elseif (!empty($array['styles']) and $this->has_legacy_values($array['styles'], $property_group_name)){
					$styles_found['cur_leg'] = 'legacy';
				}
				return $styles_found;
			}

			// look for any values in property group, including legacy values - and optionally, media query values
			function pg_has_values_inc_legacy_inc_mq($section_name, $css_selector, $array, $property_group_name){

				// first just look for values in all devices (most likely)
				if ($styles_found = $this->pg_has_values_inc_legacy($array, $property_group_name)) {
					$styles_found['dev_mq'] = 'all-devices';
					return $styles_found;
				} else {
					// look for media query tabs with values too
					// - use preferences mqs for loop because any mq keys in options not in there will not be output
					// also active_queries doesn't currently get updated after deleting an MQ tab via popup
					if (is_array($this->preferences['m_queries'])) {
						foreach ($this->preferences['m_queries'] as $mq_key => $junk) {
							// now check $options
							if (!empty($this->options['non_section']['m_query'][$mq_key][$section_name][$css_selector])){
								$array = $this->options['non_section']['m_query'][$mq_key][$section_name][$css_selector];
								if ($styles_found = $this->pg_has_values_inc_legacy($array, $property_group_name)) {
									$styles_found['dev_mq'] = 'mq';
									break;
								}
							}
						}
					}

					/*
					if (!empty($this->options['non_section']['active_queries']) and
						is_array($this->options['non_section']['active_queries'])) {
						foreach ($this->options['non_section']['active_queries'] as $mq_key => $junk) { // here
							if (!empty($this->options['non_section']['m_query'][$mq_key][$section_name][$css_selector])){
								$array = $this->options['non_section']['m_query'][$mq_key][$section_name][$css_selector];
								if ($styles_found = $this->pg_has_values_inc_legacy($array, $property_group_name)) {
									$styles_found['dev_mq'] = 'mq';
									break;
								}
							}
						}
					}*/
					return $styles_found;
				}
			}

			// does the selector contain any styles?
			function selector_has_values($section_name, $css_selector, $array, $deep){

			    return !empty($array['compiled_css']);

				/*$style_count_state = 0;
				foreach ($this->propertyoptions as $property_group_name => $junk) {
					// ui menus need deep analysis of settings, but stylesheet only looks at mq/regular arrays one at a time
					// and legacy values will have already been dealt with
					if ($deep){
						if ($this->pg_has_values_inc_legacy_inc_mq($section_name, $css_selector, $array, $property_group_name)) {
							++$style_count_state;
						}
					} else {
						if (!empty($array['styles'][$property_group_name]) and
						    $this->pg_has_values($array['styles'][$property_group_name])) {
							++$style_count_state;
						}
					}
				}
				return $style_count_state;*/
			}

			// does the folder contain any styles?
			function section_has_values($section_name, $array, $deep){
				$style_count_state = 0;
				if ( is_array($array) ) {
					foreach ($array as $css_selector => $sel_array) {
						if ($this->selector_has_values($section_name, $css_selector, $sel_array, $deep)){
							++$style_count_state;
						}
					}
				}
				return $style_count_state;
			}

			// does the $ui_data array have values?
			function ui_data_has_values($ui_data, $deep){
				$style_count_state = 0;
				if (!empty($ui_data) and is_array($ui_data)){
					foreach ($ui_data as $section_name => $array){
						if ($this->section_has_values($section_name, $array, $deep)){
							++$style_count_state;
						}
					}
				}
				return $style_count_state;
			}

			// ensure that specific legacy props have moved to this pg
			function props_moved_to_this_pg($leg_group_styles, $array){
				// loop through legacy props to see if style values exist
				if (is_array($array)){
					foreach ($array as $legacy_prop => $legacy_prop_legend_key){
						if (!empty($leg_group_styles[$legacy_prop])){
							return true;
						}
					}
				}
				return false;
			}

			// determine if options property is legacy or not
			function is_legacy_prop($property_group_name, $property){
				$legacy = false;
				foreach ($this->legacy_groups as $new_group => $array){
					foreach ($array as $leg_group => $arr2){
						foreach ($arr2 as $leg_prop => $legacy_prop_legend_key) {
							if ($property_group_name == $leg_group and $property == $leg_prop) {
								$legacy = $new_group; // return new group for legacy property
								break;
							}
						}
					}
				}
				return $legacy;
			}

			// function to get legacy value (inc !important) if it exists
			function populate_from_legacy_if_exists($styles, $sel_imp_array, $prop){
				$target_leg_prop = false;
				$legacy_adjusted['value'] = false;
				$legacy_adjusted['imp'] = '';
				foreach ($this->legacy_groups as $pg => $leg_group_array){
					foreach ($leg_group_array as $leg_group => $leg_prop_array){
						// look for prop in value: 1 = same as key
						foreach ($leg_prop_array as $leg_prop => $legend_key){
							// prop may have legacy values
							if ( ($prop == $leg_prop and $legend_key) == 1 or $prop == $legend_key){
								$target_leg_prop = $leg_prop;

							} elseif (is_array($legend_key)){
								// loop through array
								if (in_array($prop, $legend_key)){
									$target_leg_prop = $leg_prop;
								}
							}
							// if the property had a previous location, check for a value
							if ($target_leg_prop){
								if (!empty($styles[$leg_group][$target_leg_prop])){
									$legacy_adjusted['value'] = $styles[$leg_group][$target_leg_prop];
									if (!empty($sel_imp_array[$leg_group][$target_leg_prop])){
										$legacy_adjusted['imp'] = $sel_imp_array[$leg_group][$target_leg_prop];
									}
									break 3; // break out of all loops
								}
							}
						}
					}
				}
				return $legacy_adjusted;
			}

			// new system that doesn't restrict section name format
			function get_folder_name_inc_legacy($section_name, $array){
				// legacy 1
				$display_section_name = ucwords(str_replace('_', ' ', $section_name));
				// legacy 2 (abandoned because I don't like having this stored in non_section)
				if (!empty($this->options['non_section']['display_name'][$section_name])) {
					$display_section_name = $this->options['non_section']['display_name'][$section_name];
				}
				// current
				if (!empty($this->options[$section_name]['this']['label'])) {
					$display_section_name = $this->options[$section_name]['this']['label'];
				}
				return $display_section_name;
			}


			// output all the options for a given property group
			function single_option_group_html(
				$section_name,
				$css_selector,
				$array,
				$property_group_name,
				$pg_focus){

				// check if the property group should be "active" (in focus)
				$pg_show_class = ( $property_group_name == $pg_focus ) ? 'show' : '';

				// main pg wrapper
				$html ='
				<li id="opts-'.$section_name.'-'.$css_selector.'-'.$property_group_name.'"
						 class="group-tag group-tag-'.$property_group_name.' hidden '.$pg_show_class.'">';

				// output all devices and MQ fields
				$html.= $this->single_device_fields(
					$section_name,
					$css_selector,
					$array,
					$property_group_name,
					$pg_show_class);

				$html.= '
				</li>';

				return $html;
			}

			// function for outputting all devices and MQs without repeating code
			function single_device_fields(
				$section_name,
				$css_selector,
				$array,
				$property_group_name,
				$pg_show_class){

				$html = '';

				// output all fields
				foreach ($this->combined_devices() as $key => $m_query){

					$property_group_array = false;
					$con = 'reg';

					// get array val if MQ
					if ($key != 'all-devices'){
						$con = 'mq';
						$array = false;
						if (!empty($this->options['non_section']['m_query'][$key][$section_name][$css_selector])) {
							$array = $this->options['non_section']['m_query'][$key][$section_name][$css_selector];
						}
					}

					// need to check for existing styles (inc legacy)
					if ( $array and $styles_found = $this->pg_has_values_inc_legacy(
							$array,
							$property_group_name) ) {

						// if there are current styles for the all devices tab, retrieve them
						if ($styles_found['cur_leg'] == 'current'){
							$property_group_array = $array['styles'][$property_group_name];
						}

						// if only legacy values exist set empty array so inputs are displayed
						else {
							$property_group_array = array();
						}
					}

					// show fields even if no values if tab is current
					if ( !$property_group_array and $this->preferences['mq_device_focus'] == $key and $pg_show_class){
						$property_group_array = array();
					}

					/*$this->debug_custom.= $section_name.'> '.$css_selector .'> '
						. $m_query['label'] .' ('.$key .') > '. $property_group_name .'> '
						. print_r( $property_group_array, true ). 'Arr: ' . is_array($array) . "\n\n";*/

					// output fields if needed
					if ( is_array( $property_group_array ) ) {

						// visible if tab is active
						$show_class = ( $this->preferences['mq_device_focus'] == $key ) ? 'show' : '';

						// pass current CSS selector
						$sel_code = '';
						if (!empty($array['label'])){ // not sure why this would be - troubleshoot
							$sel_meta = explode('|', $array['label']);
							$sel_code = !empty($sel_meta[1]) ? $sel_meta[1] : '';
						}

						// this is contained in a separate function because the li always needs to exist
						// as a wrapper for the tmpl div
						$html.= $this->single_option_fields(
							$section_name,
							$css_selector,
							$array,
							$property_group_array,
							$property_group_name,
							$show_class,
							false,
							$key,
							$con,
							$sel_code);

					}
				}

				return $html;
			}

			// the options fields part of the property group (which can be added as templates)
			function single_option_fields(
				$section_name,
				$css_selector,
				$array,
				$property_group_array,
				$property_group_name,
				$show_class,
				$template = false,
				$key = 'all-devices',
				$con = 'reg',
				$sel_code = 'selector_code'){

				// is this template HTML?
				$id = ( $template ) ? 'id="option-group-template-'.$property_group_name. '"' : '';

				// add certain classes based on property values
				$conditional_classes = '';

				if (!$template){

					// rotation of flex icons
					$special_flex_direction = $this->array_matches(
						$property_group_array,
						'flex_direction',
						'contains',
						array('column', 'row-reverse')
					);

					if ($special_flex_direction){
						$conditional_classes.= ' flex-direction-'.$special_flex_direction;
					}

					// show container or item fields (flex and grid)
					$contItem = array('flexitem', 'griditem');
					foreach ($contItem as $item_type){
						if ($this->array_matches( $array,'pg_'.$item_type)){
							$conditional_classes.= ' show-'.$item_type;
						}
					}
				}

				// do all-devices fields
				$html = '
				<div '.$id.' class="property-fields hidden property-'.$property_group_name.'
				property-fields-'. $key . ' ' . $conditional_classes. ' ' . $show_class.'">
				<div class="pg-inner">
					';

				// merge to allow for new properties added to property-options.inc.php (array with values must come 2nd)
				$property_group_array = array_merge($this->propertyoptions[$property_group_name], $property_group_array);

				$this->group_spacer_count = 0;

				// output individual property fields
				foreach ($property_group_array as $property => $value) {

					// filter prop
					$property = esc_attr($property);

					/* if a new CSS property has been added with array_merge(), $value will be something like:
					Array ( [label] => Left [default_unit] => px [icon] => position_left )
					- so just set to nothing if it's an array
					*/
					//$value = ( !is_array($value) ) ? esc_attr($value) : ''; todo I hope this is OK...

					// format input fields
					$html.= $this->resolve_input_fields(
						$section_name,
						$css_selector,
						$array,
						$property_group_array,
						$property_group_name,
						$property,
						$value,
						$con,
						$key,
						$sel_code
					);
				}

				$html.= '
				</div></div>';

				return $html;
			}

			// check if an array key/value matches
			function array_matches($array, $key, $logic = 'isset', $value = null) {

				// false if not set
				if ( !isset($array[$key]) ) return false;

				// true if only checking if set
				if ($logic == 'isset') return true;

				// compare values
				$arr_val = $array[$key];
				switch ($logic) {
					case 'contains':
						if (is_array($value)){
							foreach ($value as $v){
								if (strpos($arr_val, $v) !== false){
									return $arr_val;
								}
							}
						} else {
							if ( strpos($arr_val, $value) !== false) {
								return $arr_val;
							}
						}
					case 'is':
						if ( $arr_val == $value ) {
							return $arr_val;
						}
					case 'isnot':
						if ( $arr_val != $value ) {
							return $arr_val;
						}
						break;
				}

				return false;
			}



			// format media query min/max width (height later) and units
			function mq_min_max($pref_array){
				// check the media query min/max values
				foreach($pref_array['m_queries'] as $key => $mq_array) {
					$m_conditions = array('min', 'max');
					foreach ($m_conditions as $condition){
						$matches = $this->get_screen_size($mq_array['query'], $condition);
						$pref_array['m_queries'][$key][$condition] = 0;
						if ($matches){
							$pref_array['m_queries'][$key][$condition] = intval($matches[1]);
							$pref_array['m_queries'][$key][$condition.'_unit'] = $matches[2];
						}
					}
				}
				return $pref_array['m_queries'];
			}

			// compare the original set of media queries with a new config to detect deleted mqs
			function deleted_media_queries($orig_media_queries, $new_media_queries){

			    $deleted = false;

                foreach($orig_media_queries as $key => $array){
                    if (empty($new_media_queries[$key])){
	                    $deleted[] = $key;
                    }
                }

                return $deleted;
            }

			// compare the original set of media queries with a new config to detect deleted mqs
			function clean_deleted_media_queries($orig_media_queries, $new_media_queries){

			    if ($deleted = $this->deleted_media_queries($orig_media_queries, $new_media_queries)){

				    $non_section = &$this->options['non_section'];

				    foreach($deleted as $i => $key){
					    if (!empty($non_section['m_query'][$key])){
						    unset($non_section['m_query'][$key]);
					    }
					    if (!empty($non_section['important']['m_query'][$key])){
						    unset($non_section['important']['m_query'][$key]);
					    }
				    }

				    // save
                    update_option($this->optionsName, $this->options);

				    /*$this->log(
					    esc_html__('Deleted media query cleaned', 'microthemer'),
					    '<pre>' . print_r($deleted, true) . print_r($this->options['non_section']['m_query'], true). '</pre>',
					    'notice'
				    );*/

			    }

			}

			// The new UI always shows the MQ tabs.
			// This happens even when no selectors are showing, so a different approach is needed
			function global_media_query_tabs(){

				$html = '
                <div class="query-tabs-wrap">
                
                <span class="edit-mq show-dialog"
                        title="' . esc_attr__('Edit media queries', 'microthemer') . '" rel="edit-media-queries">
                        </span>
                
                
                    <div class="query-tabs">';

				/*<input class="device-focus" type="hidden"
                        name="tvr_mcth[non_section][device_focus]"
                        value="'.$this->preferences['mq_device_focus'].'" />*/

				//$html.= print_r($this->combined_devices(), true);

				// display tabs
				foreach ($this->combined_devices() as $key => $m_query){

					// don't show if hidden by the user
					if ( isset($m_query['hide']) ) continue;

					// should the tab be active? - let JS handle this
					//$class = ($this->preferences['mq_device_focus'] == $key) ? 'active' : '';

					$html.= '
                                <span class="quick-opts-wrap tvr-fade-in mt-tab mq-tab mq-tab-'.$key.'" rel="'.$key.'">' .
					        // disabled check is always done with JS after loading the selector, no need to check item-disabled class
					        '<span class="mt-tab-txt mq-tab-txt">' . $m_query['label']. '</span>
                                     <span class="quick-opts tvr-dots dots-above">
                                        <div class="quick-opts-inner">'
					        . $this->icon_control(false,'disabled', false, 'tab')
					        . $this->clear_icon('tab'). '
                                            <span class="tvr-icon info-icon" title="'.$m_query['query'].'"></span>
                                        </div>
                                     </span>
                                </span>';
				}


				$html.= '
    
                        <div class="clear"></div>
    
                    </div>
                   
				</div>' . $this->mt_hor_scroll_buttons('responsive', 'div');

				return $html;
			}

			// check for 2 values on border-radius corner
			function check_two_radius($radius, $c2){
				$check = explode(' ', $radius);
				// if there are more than two values
				if (!empty($check[1])){
					$radius = $check[0];
					$c2[] = $check[1];
				} else {
					// if ANY 2nd corners have been found so far, but not on this occasion, default to the existing radius
					if ($c2){
						$c2[] = $radius;
					}
				}
				$corner = array($radius, $c2);
				return $corner;
			}

			// check if e.g. box-sahdow-x has none/inherit/initial
			function is_single_keyword($val) {
				$keywords = array('none', 'initial', 'inherit');
				// $strict is needed to prevent 0 returning true
				// https://stackoverflow.com/questions/16787762/in-array-returns-true-if-needle-is-0
				return in_array($val, $keywords, true);
			}

			function is_time_prop($property){
				return strpos($property, 'duration') !== false || strpos($property, 'delay') !== false;
			}

			function is_non_length_unit($factoryUnit, $prop){
				return $factoryUnit === 's' || $factoryUnit === 'deg' || ($factoryUnit === ''); //&& $prop !== 'line_height' -
                // we removed line-height too as this is just for setting units globally,
                // and we don't want to change line-height from default non-unit when using 'ALL Units' option
			}

			// check if !important should be used for CSS3 line
			function tvr_css3_imp($section_name, $css_selector_slug, $property_group_name, $prop, $con, $mq_key) {
				if ($this->preferences['css_important'] != '1') {
					if ($con == 'mq') {
						$important_val = !empty($this->options['non_section']['important']['m_query'][$mq_key][$section_name][$css_selector_slug][$property_group_name][$prop]) ? '1' : '';
					} else {
						$important_val = !empty($this->options['non_section']['important'][$section_name][$css_selector_slug][$property_group_name][$prop]) ? '1' : '';
					}
					if ($important_val == '1') {
						$css_important = ' !important';
					}
					else {
						$css_important = '';
					}
				} else {
					$css_important = ' !important';
				}
				return $css_important;
			}

			// transform MT form settings into stylesheet data
			function convert_ui_data($ui_data, $sty, $con, $key = '1') {

				$tab = $sec_breaks = $mq_key = "";

				if ($con == 'mq') {

				    // don't output media query if no values inside
					if (!$this->ui_data_has_values($ui_data, false)){
						return $sty;
					}

					$mq_key = $key;
					$mq_label = $this->preferences['m_queries'][$key]['label'];
					$mq_query = $this->preferences['m_queries'][$key]['query'];
					$tab = "\t";
					$sec_breaks = "";
					$mq_line = "\n/*( $mq_label )*/\n$mq_query {\n";

					$sty['data'].= $mq_line;

					if ($this->client_scss()){
						$sty['scss_data'].= $mq_line;
					}

				}

				// loop through the sections
				foreach ( $ui_data as $section_name => $array) {

					// skip non_section stuff or empty sections
					if ($section_name == 'non_section' or
					    !$this->section_has_values($section_name, $array, false)) {
						continue;
					}

					// get the section name, accounting for legacy data structure
					$display_section_name = $this->get_folder_name_inc_legacy($section_name, $array);

					// check if section been disabled on regular ui array as this happens on that level
					!empty($this->options[$section_name]['this']['disabled'])
						? $display_section_name.= ' ('.$this->dis_text.')' : false;

					// make sections same width by adding extra = and accounting for char length
					$eq_str = $this->eq_str($display_section_name);
					$section_comment = $sec_breaks."\n$tab/*= $display_section_name $eq_str */\n\n";

					$sty['data'].= $section_comment;

					if ($this->client_scss()){
						$sty['scss_data'].= $section_comment;
					}

					// if section disabled, continue
					if (!empty($this->options[$section_name]['this']['disabled'])) { continue; }

					// loop the CSS selectors - section_has_values() already tells us array is good
					foreach ( $array as $css_selector => $sub_array ) {

						// skip this or empty selectors
						if ($css_selector == 'this' or
						    !$this->selector_has_values($section_name, $css_selector, $sub_array, false)) {
							continue;
						}

						// sort out the css selector - need to get css label/code from regular ui array
						if ($con == 'mq') {
							$sub_array['label'] = $this->options[$section_name][$css_selector]['label'];
						}
						$label_array = explode('|', $sub_array['label']);
						$css_label = $label_array[0]; // ucwords(str_replace('_', ' ', $label_array[0]));
						//$sel_code = $label_array[1];

						//$opening_sel = $sel_code . ' {';
						$sel_disabled = false;
						if (!empty($sub_array['tab']['disabled']) or
						    !empty($this->options[$section_name][$css_selector]['disabled'])) {
							$sel_disabled = true;
							//$opening_sel = '';
							$css_label.= ' ('.$this->dis_text.')';
						}

						$selector_comment = "$tab/** $display_section_name >> $css_label **/\n$tab";

						// output sel comment
						$sty['data'].= $selector_comment;

						if ($this->client_scss()){
							$sty['scss_data'].= $selector_comment;
						}

						// move on if sel disabled
						if ($sel_disabled) { continue; }

						// output JS compiled CSS selector code
						$sty['data'].= $this->normalise_tabs(
						   $this->normalise_line_breaks($sub_array['compiled_css']), $tab
                        );

						//$sty['data'].= $this->normalise_line_breaks($sub_array['compiled_css']);

						if ($this->client_scss()){
							$sty['scss_data'].= $this->normalise_tabs(
								$this->normalise_line_breaks($sub_array['raw_scss']), $tab, true
							);
						}

						//$sty['data'].= "$tab}\n";

						// output any post_sel JS event CSS
						/*if (!empty($array['post_sel_css'])){
							$sty['data'].=  $array['post_sel_css'] . "\n";
						}*/

						// output comma'd selectors on different lines (let it be maybe)
						// $sel_code = str_replace(", ", ",\n", $sel_code);
						// convert custom escaped single & double quotes back to normal ( [type="submit"] )

						// already done with deep at save point, keep for legacy reasons for while (Nov 17, 2015)
						//$sel_code = $this->unescape_cus_quotes($sel_code);

						/*

						$count_styles = count($array);

						// check for use of curly braces
						$curly = strpos($sel_code, '{');


						// adjust selector if curly braces are present
						if ($curly!== false) {
							$curly_array = explode("{", $sel_code);
							$sel_code = $curly_array[0];
							// save custom styles in an array for later output
							$cusStyles = explode(";", str_replace('}', '', $curly_array[1]) );
						} else {
							$cusStyles = false;
						}

						// media_query_buttons

						// if there are styles or the user has entered hand-coded styles
						if ( ($con != 'mq' and $count_styles > 2)
							or ($con != 'mq' and $curly!== false )
							or ($con == 'mq' and $count_styles > 1) ) {
							// If disabled, warn and don't ouput opening {
							// The individual tab may be disabled. Or the whole selector may be disabled.
							// Check global selector using regular ui array
						}
						*/

					}


				}
				// return the modified $sty array
                $close_mq = "\n}\n\n";
				if ($con == 'mq') {
					$sty['data'].= $close_mq;
					if ($this->client_scss()){
						$sty['scss_data'].= $close_mq; // scss selectors don't have trailing line break
					}
				}
				return $sty;
			}

			// write to a file (make more use of this function)
			function write_file($file, $data){
				// the file will be created if it doesn't exist. otherwise it is overwritten.
				$write_file = @fopen($file, 'w');
				// if write is unsuccessful for some reason
				if (false === fwrite($write_file, $data)) {
					$this->log(
						esc_html__('File write error', 'microthemer'),
						'<p>' . sprintf(esc_html__('Writing to %s failed.', 'microthemer'),
							$this->root_rel($file)) . $this->permissionshelp.'</p>'
					);
					return false;
				}
				fclose($write_file);
				return true;
			}

			function eq_str($name){
				$eq_signs = 25-strlen($name);
				$eq_signs = $eq_signs > -1 ? $eq_signs : 0;
				$eq_str = '';
				for ($x = $eq_signs; $x >= 0; $x--) {
					$eq_str.= '=';
				}
				return $eq_str;
			}

			// update active-styles.css
			function update_active_styles2($activated_from, $context = '') {

			    // get path to active-styles.css
				$act_styles = $this->micro_root_dir.'active-styles.css';

				// check for micro-themes folder and create if it doesn't exist
				$this->setup_micro_themes_dir();

				// bail if stylesheet isn't writable
				if ( !is_writable($act_styles) ) {
					$this->log(
						esc_html__('Write stylesheet error', 'microthemer'),
						'<p>' . esc_html__('WordPress does not have "write" permission for: ', 'microthemer')
						. '<span title="'.$act_styles.'">'. $this->root_rel($act_styles) . '</span>
						. '.$this->permissionshelp.'</p>'
					);
					return false;
				}

				// setup vars
				$sty['data'] = '';
				$sty['scss_data'] = '';
				$title = '/*  MICROTHEMER STYLES  */' . "\n\n";

				// check if hand coded have been set - output before other css
				$scss_custom_code = '';
				$custom_code = '';
				if ( !empty($this->options['non_section']['hand_coded_css']) &&
                     !empty(trim($this->options['non_section']['hand_coded_css'])) ){

				    // format comment
				    $name = esc_attr_x('Full Code Editor CSS', 'CSS comment', 'microthemer');
					$eq_str = $this->eq_str($name);
					$custom_code_comment = "/*= $name $eq_str */\n\n";

					// if the scss compiles in the browser
					if ($this->client_scss()){

					    // log raw SCSS for writing to active-styles.scss
					    $scss_custom_code.= $custom_code_comment . $this->options['non_section']['hand_coded_css'] ."\n";

						// include already compiled CSS
					    if (!empty($this->options['non_section']['hand_coded_css_compiled'])){
							$custom_code.= $custom_code_comment . $this->options['non_section']['hand_coded_css_compiled'] ."\n";
						}
                    }

                    // server-side scss or no scss support
                    else {
	                    $custom_code.= $custom_code_comment . $this->options['non_section']['hand_coded_css'] ."\n";
                    }

				}

				// convert ui data to regular css output
				$sty = $this->convert_ui_data($this->options, $sty, 'regular');

				// convert ui data to media query css output
				if (!empty($this->options['non_section']['m_query']) and is_array($this->options['non_section']['m_query'])) {
					foreach ($this->preferences['m_queries'] as $key => $m_query) {
						// process media query if it has been in use at all
						if (!empty($this->options['non_section']['m_query'][$key]) and
						    is_array($this->options['non_section']['m_query'][$key])){
							$sty = $this->convert_ui_data($this->options['non_section']['m_query'][$key], $sty, 'mq', $key);
						}
					}
				}

				// any animations have been found after iterating GUI options, include if necessary
				$anim_keyframes = '';

				if ( !empty($this->options['non_section']['meta']['animations']['names']) and
                     count($this->options['non_section']['meta']['animations']['names']) ){

					// flag section with CSS comment
					$name = esc_attr_x('Animations', 'CSS comment', 'microthemer');
					$eq_str = $this->eq_str($name);
					$anim_keyframes.= "/*= $name $eq_str */\n\n";

					// get array of animation code
					$animations = array();
					include $this->thisplugindir . 'includes/animation/animation-code.inc.php';

					foreach ($this->options['non_section']['meta']['animations']['names'] as $animation_name => $one){

						// if we recognise the animation name, include the keyframe code
						if (!empty($animations[$animation_name])){
							$anim_keyframes.= $animations[$animation_name]['code'];
						}

					}
				}

				// join title, animations, custom code and GUI output in correct order
				$sty['data'] = $title . $anim_keyframes . $custom_code . $sty['data'];
				$sty['scss_data'] = $title . $anim_keyframes . $scss_custom_code . $sty['scss_data'];

                /** UPDATE PREFERENCES */

                $pref_array = array();

                // save the google font values
				$g_fonts = $this->get_item(
					$this->options,
					array('non_section', 'meta', 'g_fonts')
				);

				$gf_keys = array('g_fonts_used', 'g_url', 'g_url_with_subsets', 'found_gf_subsets');
				foreach($gf_keys as $index => $key){
					$pref_array[$key] = !empty($g_fonts[$key]) ? $g_fonts[$key] : '';
				}

				// store any js events
				$pref_array['active_events'] = !empty($this->options['non_section']['active_events'])
                    ? $this->options['non_section']['active_events']
                    : array();

				if ($activated_from != 'customised' and $context != __('Merge', 'microthemer')) {
					$pref_array['theme_in_focus'] = $activated_from;
					$pref_array['active_theme'] = $activated_from;
				}

				if ($context == __('Merge', 'microthemer') or $activated_from == 'customised') {
					$pref_array['active_theme'] = 'customised'; // a merge means a new custom configuration
				}

				$pref_array['num_saves'] = ++$this->preferences['num_saves'];

				if ($this->savePreferences($pref_array) and $activated_from != 'customised') {
					$this->log(
						esc_html__('Design pack activated', 'microthemer'),
						'<p>' . esc_html__('The design pack was successfully activated.', 'microthemer') . '</p>',
						'dev-notice'
					);
				}

				/*// debug subsets
				$this->log(
					esc_html__('The new url', 'microthemer'),
					'<p>' . $this->preferences['g_url_with_subsets'] . '<br >'
                    . $pref_array['g_url_with_subsets'] . '<br />' . print_r($subsets, true) . '</p>'
				);*/


				// get user config for draft
                $css_files = array();
				$file_stub = $this->preferences['draft_mode'] ? 'draft' : 'active';

				// if scss is enabled, write to SCSS file
                if ($this->preferences['allow_scss']){

                    // add uncompiled scss to array
	                $uncompiled = $this->client_scss() ? $sty['scss_data'] : $sty['data'];
	                $file_name = $file_stub . '-styles.scss';
	               /* $css_files[] = array(
		                'name' => $file_name,
		                'data' => $uncompiled
	                );*/

	                // write data to file so they can see their mistake
	                $this->write_file($this->micro_root_dir . $file_name, $uncompiled);
                }

				// if server-side scss is enabled, compile on the server
				if ($this->server_scss()){

					$scss = '';
					require $this->thisplugindir . "includes/scssphp/mt-scss.php";

					// catch any compilation errors
					try {
						$sty['data'] = $scss->compile($sty['data']);
					} catch (Exception $e) {

						// get the line number from error message
						preg_match('/line:? ([0-9]+)$/', $e->getMessage(), $matches);
						$line = !empty($matches[1]) ? $matches[1] : 0;
						$action = esc_html__('View the error line (Ctrl + Alt + G)', 'microthemer');
						$this->log(
							esc_html__('Sass compilation error', 'microthemer'),
							'<p>'.esc_html__('An error was found in your SCSS code which prevented it from
							being compiled into regular CSS code.', 'microthemer') . '</p>
							<p><b>' . htmlentities($e->getMessage(), ENT_QUOTES, 'UTF-8').'</b></p>
							<p>
							<span id="scss-line-error" class="link show-dialog" rel="display-css-code"
							data-tab="0" data-line="'.$line.'" title="'.$action.'">'
							.$action
							. '</span>
							</p>'
						);
						return false;
					}
				}

				// add compiled or raw css to array
				$targetFile = $file_stub . '-styles.css';
				$css_files[] = array(
					'name' => $targetFile,
					'data' => $sty['data']
				);

				// do we minify?
				if ($this->preferences['minify_css']){
					if (version_compare(PHP_VERSION, '5.3') < 0) {
						$css_files[] = array(
							'name' => 'min.'.$targetFile,
							'data' => '/* ' .esc_html__('Minification is not supported. Your version of PHP is below 5.3. ', '') .
							          esc_html__('Please upgrade PHP to version 5.3+ to use minification') . " */\n\n" . $sty['data']
						);
					} else {
						require $this->thisplugindir . "includes/min-css-js/mt-minify.php";
					}
				}

				// write all necessary files
				foreach ($css_files as $key => $file){
					$this->write_file($this->micro_root_dir . $file['name'], $file['data']);
				}

				// write to the ie specific stylesheets if user defined
				$this->update_ie_sheets();

				// write any js code to external script file
				$this->update_javascript();
			}


			function g_url_with_subsets($g_url = false, $found_gf_subsets = false, $gfont_subset = false){

			    $g_url = $g_url !== false ? $g_url : $this->preferences['g_url'];

			    if (empty($g_url)){
			        return '';
                }

			    $found_gf_subsets = $found_gf_subsets !== false ? $found_gf_subsets : $this->preferences['found_gf_subsets'];
				$gfont_subset = $gfont_subset !== false ? $gfont_subset : $this->preferences['gfont_subset'];
				$subsets = array();

				// add custom fonts subset url param if defined in preferences
				if (!empty($gfont_subset)) {
					preg_match('/subset=(.+)/', $gfont_subset, $matches);
					if (!empty($matches[1])){
						$subsets[] = $matches[1];
					}
				}

				// combine with subsets found in MT settings
				if (!empty($found_gf_subsets) and is_array($found_gf_subsets) and count($found_gf_subsets)){
					$subsets = array_merge($subsets, $found_gf_subsets);
					$subsets = array_unique($subsets);
				}

				if (count($subsets)){
					$g_url.= '&subset=' . implode(',', $subsets);
				}

				return $g_url;
            }

			// update the external JS file or add to head if that is enabled
			// some hosts block the creation of .js files with PHP
			function update_javascript() {
				$val = !empty($this->options['non_section']['js']) ?
					trim($this->options['non_section']['js']) : '';
				if (!empty($val)) {
					$pref_array['load_js'] = 1;
				} else {
					// script editor is empty
					$pref_array['load_js'] = 0;
				}
				// always update file otherwise JS can't be cleared
				$file_stub = $this->preferences['draft_mode'] ? 'draft' : 'active';
				$script = $this->micro_root_dir.$file_stub.'-scripts.js';
				$this->write_file($script, $val);
				/*if (!$this->write_file($script, $val)){
					$this->log(
						esc_html__('Host may block JS created with PHP', 'microthemer'),
						'<p>' . esc_html__('I.', 'microthemer').'</p>'
					);
				}*/
				// update the preferences so that the script is/isn't called in the <head>
				$this->savePreferences($pref_array);
			}

			// update ie specific stylesheets
			function update_ie_sheets() {
				if ( !empty($this->options['non_section']['ie_css']) and
				     is_array($this->options['non_section']['ie_css']) ) {
					foreach ($this->options['non_section']['ie_css'] as $key => $val) {
						// if has custom styles
						$trim_val = trim($val);
						if (!empty($trim_val)) {
							$pref_array['ie_css'][$key] = $this->custom_code['ie_css'][$key]['cond'];
						} else {
							// no value for stylesheet specified
							$pref_array['ie_css'][$key] = 0;
						}
						// always update file otherwise CSS can't be cleared
						$file_stub = $this->preferences['draft_mode'] ? 'draft-' : '';
						$stylesheet = $this->micro_root_dir.$file_stub.'ie-'.$key.'.css';
						$this->write_file($stylesheet, $val);
					}
					// update the preferences so that the stylesheets are called in the <head>
					$this->savePreferences($pref_array);
				}
			}


			// write settings to .json file
			function update_json_file($theme, $context = '', $export_full = false, $preferences = false) {

				$theme = sanitize_file_name(sanitize_title($theme));

				// create micro theme of 'new' has been requested
				if ($context == 'new') {
					// Check for micro theme with same name
					if ($alt_name = $this->rename_if_required($this->micro_root_dir, $theme)) {
						$theme = $alt_name; // $alt_name is false if no rename was required
					}
					if (!$this->create_micro_theme($theme, 'export', ''))
						return false;
				}

				// Create new file if it doesn't already exist
				$json_file = $this->micro_root_dir.$theme.'/config.json';
				$task = 'updated';
				if (!file_exists($json_file)) {
					$task = 'created';
					if (!$write_file = @fopen($json_file, 'w')) { // this creates a blank file for writing
						$this->log(
							esc_html__('Create json error', 'microthemer'),
							'<p>' . esc_html__('WordPress does not have permission to create: ', 'microthemer')
							. $this->root_rel($json_file) . '. '.$this->permissionshelp.'</p>'
						);
						return false;
					}
				}

				// check if json file is writable
				if (!is_writable($json_file)){
					$this->log(
						esc_html__('Write json error', 'microthemer'),
						'<p>' . esc_html__('WordPress does not have "write" permission for: ', 'microthemer')
						. $this->root_rel($json_file) . '. '.$this->permissionshelp.'</p>'
					);
					return false;
				}

				// tap into WordPress native JSON functions
				/*if( !class_exists('Moxiecode_JSON') ) {
					require_once($this->thisplugindir . 'includes/class-json.php');
				}
				$json_object = new Moxiecode_JSON();*/

				// copy full options to var for filtering
				$json_data = $this->options;

				// include the user's current media queries form importing back
				$json_data['non_section']['active_queries'] = $this->preferences['m_queries'];

				// unless full, loop through full options - removing sections
				if (!$export_full){

					foreach ($this->options as $section_name => $array) {

						// if the section wasn't selected, remove it from json data var (along with the view_state var)
						if ( empty($this->serialised_post['export_sections'])
						     or (!array_key_exists($section_name, $this->serialised_post['export_sections']) )
						        and $section_name != 'non_section') {

							// remove the regular section data and view states
							unset($json_data[$section_name]);
							unset($json_data['non_section']['view_state'][$section_name]);

							// need to remove all media query settings for unchecked sections too
							if (!empty($json_data['non_section']['m_query']) and
							    is_array($json_data['non_section']['m_query'])) {
								foreach ($json_data['non_section']['m_query'] as $m_key => $array) {
									unset($json_data['non_section']['m_query'][$m_key][$section_name]);
								}
							}

							// and all of the important values
							if (!empty($json_data['non_section']['important']['m_query']) and
							    is_array($json_data['non_section']['important']['m_query'])) {
								foreach ($json_data['non_section']['important']['m_query'] as $m_key => $array) {
									unset($json_data['non_section']['important']['m_query'][$m_key][$section_name]);
								}
							}
						}
					}
                }

				// include preferences in export if passed in
                if ($preferences){
	                $json_data['non_section']['exported_preferences'] = $preferences;
                }

				// set handcoded css to nothing if not marked for export
				if ( empty($this->serialised_post['export_sections']['hand_coded_css'])) {
					$json_data['non_section']['hand_coded_css'] = '';
				}

				// set js to nothing if not marked for export
				if ( empty($this->serialised_post['export_sections']['js'])) {
					$json_data['non_section']['js'] = '';
				}

				// ie too
				foreach ($this->preferences['ie_css'] as $key => $value) {
					if ( empty($this->serialised_post['export_sections']['ie_css'][$key])) {
						$json_data['non_section']['ie_css'][$key] = '';
					}
				}

				// create debug selective export file if specified at top of script
				if ($this->debug_selective_export) {
					$data = '';
					$debug_file = $this->debug_dir . 'debug-selective-export.txt';
					$write_file = @fopen($debug_file, 'w');
					$data.= esc_html__('The Selectively Exported Options', 'microthemer') . "\n\n";
					$data.= print_r($json_data, true);
					$data.= "\n\n" . esc_html__('The Full Options', 'microthemer') . "\n\n";
					$data.= print_r($this->options, true);
					fwrite($write_file, $data);
					fclose($write_file);
				}

				// write data to json file
				if ($data = json_encode($json_data)) {
					// the file will be created if it doesn't exist. otherwise it is overwritten.
					$write_file = @fopen($json_file, 'w');
					fwrite($write_file, $data);
					fclose($write_file);
					// report
					if ($task == 'updated'){
						$this->log(
							esc_html__('Settings exported', 'microthemer'),
							'<p>' . esc_html__('Your settings were successfully exported to: ',
								'microthemer') . '<b>'.$theme.'</b></p>',
							'notice'
						);
					}
				}
				else {
					$this->log(
						esc_html__('Encode json error', 'microthemer'),
						'<p>' . esc_html__('WordPress failed to convert your settings into json.', 'microthemer') . '</p>'
					);
				}



				return $theme; // sanitised theme name
			}

			// pre-process import or restore data
			function filter_incoming_data($con, $data){

				$filtered_json = $data;

				// Unitless css values may need to be auto-adjusted, including MQs
				/*$filtered_json = $this->filter_json_css_units($data);
				if (!empty($filtered_json['non_section']['m_query']) and
				    is_array($filtered_json['non_section']['m_query'])) {
					foreach ($filtered_json['non_section']['m_query'] as $m_key => $array) {
						$filtered_json['non_section']['m_query'][$m_key] = $this->filter_json_css_units($array);
					}
				}*/

				// compare media queries in import/restore to existing
				$mq_analysis = $this->analyse_mqs(
					$filtered_json['non_section']['active_queries'],
					$this->preferences['m_queries']
				);

				// check if enq_js needs to be added
				if ($this->new_enq_js(
					$this->preferences['enq_js'],
					$filtered_json['non_section']['active_enq_js']
				)){
					$pref_array['enq_js'] = array_merge(
						$this->preferences['enq_js'],
						$filtered_json['non_section']['active_enq_js']
					);
					if ($this->savePreferences($pref_array)) {
						$this->log(
							esc_html__('JS libraries added', 'microthemer'),
							'<p>' . esc_html__('The settings you added depend on JavaScript libraries that are different from your current setup. These have been imported to ensure correct functioning.',
								'microthemer') . '</p>',
							'warning'
						);
					}
				}

				// check if the import/restore contains the same media queries but with different keys
				// if so, set the keys the same.
				// new queries also trigger this because new queries get assigned fresh keys
				if ($mq_analysis['replacements_needed']){
					foreach ($mq_analysis['replacements'] as $student_key => $role_model_key){
						$filtered_json = $this->replace_mq_keys($student_key, $role_model_key, $filtered_json);
					}
				}

				// check for new media queries in the import
				if ($mq_analysis['new']) {

					// merge the new queries with the current workspace mqs
					$pref_array['m_queries'] = array_merge(
						$this->preferences['m_queries'],
						$mq_analysis['new']
					);

					// format media query min/max width (height later) and units
					$pref_array['m_queries'] = $this->mq_min_max($pref_array);

					// save the new queries
					if ($this->savePreferences($pref_array)) {
						$this->log(
							esc_html__('Media queries added', 'microthemer'),
							'<p>' . esc_html__('The settings you added contain media queries that are different from the ones in your current setup. In order for all styles to display correctly, these additional media queries have been imported into your workspace.',
								'microthemer') . '</p>
								<p>' . wp_kses(
								sprintf(
									__('Please <span %s>review (and possibly rename) the imported media queries</span>. Note: they are marked with "(imp)", which you can remove from the label name once you\'ve reviewed them.', 'microthemer'),
									'class="link show-dialog" rel="edit-media-queries"' ),
								array( 'span' => array() )
							) . ' </p>',
							'warning'
						);
					}
				}

				// active_queries are just used for import now. Unset as they have served their purpose
                unset($filtered_json['non_section']['active_queries']);

				// just for debugging
				if ($this->debug_import) {

					// get this before modifying in any way
					//$debug_mqs['incoming_active_queries'] = $data['non_section']['active_queries'];
					$debug_mqs['orig'] = $this->preferences['m_queries'];
					$debug_mqs['new'] = $mq_analysis['new'];
					$debug_mqs['merged'] = $debug_mqs['new'] ? $pref_array['m_queries'] : false;
					$debug_mqs['mq_analysis'] = $mq_analysis;

					$debug_file = $this->debug_dir . 'debug-'.$con.'.txt';
					$write_file = @fopen($debug_file, 'w');
					$data = '';
					$data.= "\n\n### 1. Key Debug Analysis \n\n";
					$data.= print_r($debug_mqs, true);
					$data.= "\n\n### 2. The UNMODIFIED incoming data\n\n";
					$data.= print_r($data, true);
					$data.= "\n\n### 3. The (potentially) MODIFIED incoming data\n\n";
					$data.= print_r($filtered_json, true);
					fwrite($write_file, $data);
					fclose($write_file);
				}

				// return the filtered data and mq analysis
				return $filtered_json;
			}

			// load .json file - or json data if already got
			function load_json_file($json_file, $theme_name, $context = '', $data = false) {

				// if json data wasn't passed in to function, get it
				if ( !$data ){

					// bail if file is missing or cannot read
					if ( !$data = $this->get_file_data( $json_file ) ) {
						return false;
					}
				}

				// tap into WordPress native JSON functions
				/*if( !class_exists('Moxiecode_JSON') ) {
					require_once($this->thisplugindir . 'includes/class-json.php');
				}
				$json_object = new Moxiecode_JSON();*/

				// convert to array
				if (!$json_array = $this->json('decode', $data)) { // json_decode($data, true)
					//$this->log('', '', 'error', 'json-decode', array('json_file', $json_file));
					return false;
				}

				// json decode was successful

                // if the export included workspace settings, save preferences and remove from data
                // this is insurance agaist upgrade problems
                if (!empty($json_array['non_section']['exported_preferences'])){
				    update_option($this->preferencesName, $json_array['non_section']['exported_preferences']);
				    unset($json_array['non_section']['exported_preferences']);
                }

				// replace mq keys, add new to the UI, add css units if necessary.
				$filtered_json = $this->filter_incoming_data('import', $json_array);

				// merge the arrays if merge (must come after key analysis/replacements)
				if ($context == __('Merge', 'microthemer') or $context == esc_attr__('Raw CSS', 'microthemer')) {
					$filtered_json = $this->merge($this->options, $filtered_json);
				} else {
					// Only update theme_in_focus if it's not a merge
					$pref_array['theme_in_focus'] = $theme_name;
					$this->savePreferences($pref_array);
				}

				// updates options var, save settings, and update stylesheet
				$this->options = $filtered_json;
				$this->saveUiOptions2($this->options);
				$this->update_active_styles2($theme_name, $context);

				// import success
				$this->log(
					esc_html__('Settings were imported', 'microthemer'),
					'<p>' . esc_html__('The design pack settings were successfully imported.', 'microthemer') . '</p>',
					'notice'
				);

			}

			// ensure mq keys in pref array and options match
			//- NOTE A SIMPLER SOLUTION WOULD BE TO CONVERT ARRAY INTO STRING AND THEN DO str_replace (may have side effects though)
			function replace_mq_keys($student_key, $role_model_key, $options) {
				$old_new_mq_map[$student_key] = $role_model_key;
				// replace the relevant array keys - unset() doesn't work on $this-> so slightly convoluted solution used
				$cons = array('active_queries', 'm_query');
				$updated_array = array();
				foreach ($cons as $stub => $context) {
					unset($updated_array);
					if (is_array($options['non_section'][$context])) {
						foreach ($options['non_section'][$context] as $cur_key => $array) {
							if ($cur_key == $student_key) {
								$key = $role_model_key;
							} else {
								$key = $cur_key;
							}
							$updated_array[$key] = $array;
						}
						$options['non_section'][$context] = $updated_array; // reassign main array with updated keys array
					}
				}
				// and also the !important media query keys
				$updated_array = array();
				if (!empty($options['non_section']['important']['m_query']) and
				    is_array($options['non_section']['important']['m_query'])) {
					foreach ($options['non_section']['important']['m_query'] as $cur_key => $array) {
						if ($cur_key == $student_key) {
							$key = $role_model_key;
						} else {
							$key = $cur_key;
						}
						$updated_array[$key] = $array;
					}
					$options['non_section']['important']['m_query'] = $updated_array; // reassign main array with updated keys array
				}
				// annoyingly, I also need to do a replace on device_focus key values for all selectors
				/*foreach($options as $section_name => $array) {
					if ($section_name == 'non_section') { continue; }
					// loop through the selectors
					if (is_array($array)) {
						foreach ($array as $css_selector => $sub_array) {
							if (is_array($sub_array['device_focus'])) {
								foreach ( $sub_array['device_focus'] as $prop_group => $value) {
									// replace the value if it is an old key
									if (!empty($old_new_mq_map[$value])) {
										$options[$section_name][$css_selector]['device_focus'][$prop_group] = $old_new_mq_map[$value];
									}
								}
							}
						}
					}
				}*/
				return $options;
			}


			// merge the new settings with the current settings
			function merge($orig_settings, $new_settings) {
				// create debug merge file if set at top of script
				if ($this->debug_merge) {
					$debug_file = $this->debug_dir . 'debug-merge.txt';
					$write_file = @fopen($debug_file, 'w');
					$data = '';
					$data.= "\n\n" . __('### The to existing options (before merge)', 'microthemer') . "\n\n";
					$data.= print_r($orig_settings, true);

					$data .= "\n\n" . esc_html__('### The imported options (before any folder renaming)', 'microthemer') . "\n\n";
					$data .= print_r($new_settings, true);

				}
				if (is_array($new_settings)) {
					// check if search needs to be done on important and m_query arrays
					$mq_arr = $imp_arr = false;
					if (!empty($new_settings['non_section']['m_query']) and is_array($new_settings['non_section']['m_query'])){
						$mq_arr = $new_settings['non_section']['m_query'];
					}
					if (!empty($new_settings['non_section']['important']) and is_array($new_settings['non_section']['important'])){
						$imp_arr = $new_settings['non_section']['important'];
					}

					// loop through new sections to check for section name conflicts
					foreach($new_settings as $section_name => $array) {
						// if a name conflict exists
						if ( $this->is_name_conflict($section_name, $orig_settings, $new_settings, 'first-check') ) {
							// create a non-conflicting new name
							$alt = $this->get_alt_section_name($section_name, $orig_settings, $new_settings);
							$alt_name = $alt['name'];
							$alt_index = $alt['index'];
							// rename the to-be-merged section and the corresponding non_section extras
							$new_settings[$alt_name] = $new_settings[$section_name];
							$new_settings[$alt_name]['this']['label'] = $new_settings[$alt_name]['this']['label'].' '.$alt_index;
							$new_settings['non_section']['view_state'][$alt_name] = $new_settings['non_section']['view_state'][$section_name];
							unset($new_settings[$section_name]);
							unset($new_settings['non_section']['view_state'][$section_name]);
							// also rename all the corresponding [m_query] folder names (ouch)
							if ($mq_arr){
								foreach ($mq_arr as $mq_key => $arr){
									foreach ($arr as $orig_sec => $arr){
										// if the folder name exists in the m_query array, replace
										if ($section_name == $orig_sec){
											$new_settings['non_section']['m_query'][$mq_key][$alt_name] = $new_settings['non_section']['m_query'][$mq_key][$section_name];
											unset($new_settings['non_section']['m_query'][$mq_key][$section_name]);
										}
									}
								}
							}
							// and the [important] folder names (double ouch)
							if ($imp_arr){
								foreach ($imp_arr as $orig_sec => $arr){
									// if it's MQ important values
									if ($orig_sec == 'm_query'){
										foreach ($imp_arr['m_query'] as $mq_key => $arr){
											foreach ($arr as $orig_sec => $arr){
												// if the folder name exists in the m_query array, replace
												if ($section_name == $orig_sec){
													$new_settings['non_section']['important']['m_query'][$mq_key][$alt_name] = $new_settings['non_section']['important']['m_query'][$mq_key][$section_name];
													unset($new_settings['non_section']['important']['m_query'][$mq_key][$section_name]);
												}
											}
										}
									} else {
										// regular important value
										$new_settings['non_section']['important'][$alt_name] = $new_settings['non_section']['important'][$section_name];
										unset($new_settings['non_section']['important'][$section_name]);
									}
								}
							}
						}
					}


					if ($this->debug_merge) {
						$data .= "\n\n" . esc_html__('### The imported options (after folder renaming)', 'microthemer') . "\n\n";
						$data .= print_r($new_settings, true);
					}

					// now that we've checked for and corrected possible name conflicts
					// merge the arrays (recursively to avoid overwriting)
					$merged_settings = $this->array_merge_recursive_distinct($orig_settings, $new_settings);

					// the hand-coded CSS of the imported settings needs to be appended to the original
					foreach ($this->custom_code as $key => $arr){
						// if regular main custom css or JS
						if ($key == 'hand_coded_css' or $key == 'js'){
							$new_code = trim($new_settings['non_section'][$key]);
							if (!empty($new_code)) {
								$merged_settings['non_section'][$key] =
									$orig_settings['non_section'][$key]
									. "\n\n/* " . esc_html_x('Imported CSS', 'CSS comment', 'microthemer') . " */\n"
									. $new_settings['non_section'][$key];
							} else {
								// the imported pack has no custom code so keep the original
								$merged_settings['non_section'][$key] = $orig_settings['non_section'][$key];
							}
						}
						// if ie css
                        elseif ($key == 'ie_css'){
							foreach ($arr as $key2 => $arr2){
								$new_code = trim($new_settings['non_section'][$key][$key2]);
								if (!empty($new_code)) {
									$merged_settings['non_section'][$key][$key2] =
										$orig_settings['non_section'][$key][$key2]
										. "\n\n/* " . esc_html_x('Code from design pack imported with Merge', 'CSS comment', 'microthemer') . " */\n"
										. $new_settings['non_section'][$key][$key2];
								} else {
									// the imported pack has no custom code so keep the original
									$merged_settings['non_section'][$key][$key2] = $orig_settings['non_section'][$key][$key2];
								}
							}
						}
					}
				}
				// maybe do some more processing here

				if ($this->debug_merge) {
					$data.= "\n\n" . __('### The Merged options', 'microthemer') . "\n\n";
					$data.= print_r($merged_settings, true);
					fwrite($write_file, $data);
					fclose($write_file);
				}
				return $merged_settings;
			}

			// add js deps in import if not
			function new_enq_js($cur_enq_js, $imp_enq_js){
				foreach ($imp_enq_js as $k => $arr){
					if (empty($cur_enq_js[$k])) return true;
				}
				return false;
			}

			// get an array of current mq keys paired with replacements -
			// compare against 'role model' to base current array on
			function analyse_mqs($student_mqs, $role_model_mqs){
				$mq_analysis['new'] = false;
				$mq_analysis['replacements_needed'] = false;
				$i = 0;
				if (!empty($student_mqs) and is_array($student_mqs)) {
					foreach ($student_mqs as $student_key => $student_array){
						$replacement_key = $this->in_2dim_array($student_array['query'], $role_model_mqs, 'query');
						// if new media query
						if ( !$replacement_key ) {
							// ensure key is unique by using unique base from last page load
							// otherwise previously exported keys could overwrite rather add to existing MQs (if the query was changed after exporting)
							$new_key = $this->unq_base.++$i;
							$mq_analysis['new'][$new_key]['label'] = $student_array['label']. esc_html_x(' (imp)', '(imported media query)', 'microthemer');
							$mq_analysis['new'][$new_key]['query'] = $student_array['query'];
							// as we're defining new keys, the ui data keys will need replacing too
							$mq_analysis['replacements_needed'] = true;
							$mq_analysis['replacements'][$student_key] = $new_key;
						}
						// else store replacement key
						else {
							if ($replacement_key != $student_key){
								$mq_analysis['replacements_needed'] = true;
								$mq_analysis['replacements'][$student_key] = $replacement_key;
							}
						}
					}
				}
				return $mq_analysis;
			}


			/***
			Manage Micro Theme Functions
			 ***/

			function setup_micro_themes_dir($activated = false){
				if ( !is_dir($this->micro_root_dir) ) {
					// create root micro-themes dir
					if ( !wp_mkdir_p( $this->micro_root_dir, 0755 ) ) {
						$this->log(
							esc_html__('/micro-themes create error', 'microthemer'),
							'<p>' . sprintf(
								esc_html__('WordPress was not able to create the %s directory.', 'microthemer'),
								$this->root_rel($this->micro_root_dir)
							) . $this->permissionshelp . '</p>'
						);
						return false;
					}
				} else {
					// micro-themes dir does exist, clean lose pack files that may exist due to past bug
					$this->maybe_clean_micro_root(); // 7.7.2016 - we can remove after a few months.
				}

				// create _scss dir if it doesn't exist (at some point)

				// copy pie or animation-events over if needed
				if (!$this->maybe_copy_to_micro_root($activated)) return false;

				// also create blank active-styles else 404 before user adds styles
				$prime_files = array(
					$this->micro_root_dir.'active-styles.css',
					$this->micro_root_dir.'min.active-styles.css',
					$this->micro_root_dir.'draft-styles.css',
					$this->micro_root_dir.'min.draft-styles.css',
					$this->micro_root_dir.'active-styles.scss',
				);
				if (!$this->maybe_create_stylesheet($prime_files)) return false;

				// all good
				return true;
			}

			// clean lose pack files that may exist due to past bug
			function maybe_clean_micro_root(){
				$files = array(
					'meta.txt',
					'debug-save.txt',
					'debug-current.txt',
					'debug-pulled-data.txt',
					'debug-selective-export.txt',
					'debug-merge.txt',
					'debug-overwrite.txt'
				);
				foreach ($files as $key => $file){
					$file = $this->micro_root_dir . $file;
					if (file_exists($file)){
						unlink($file);
					}
				}
			}

			// create active-styles if it doesn't already exist
			function maybe_create_stylesheet($prime_files){
				if (is_array($prime_files)){
					foreach($prime_files as $key => $file){
						if (!file_exists($file)) {
							if (!$write_file = @fopen($file, 'w')) {
								$this->log(
									esc_html__('Create stylesheet error', 'microthemer'),
									'<p>' . esc_html__('WordPress does not have permission to create: ', 'microthemer') .
									$this->root_rel($file) . '. '.$this->permissionshelp.'</p>'
								);
								return false;
							}
							fclose($write_file);
						}
					}
				}
				return true;
			}


			// copy files from Microthemer plugin dir to micro-themes for use when MT is inactive
			function maybe_copy_to_micro_root($activated){

				$orig_files = array('/js-min/animation-events.js');
				$new_files = array('animation-events.js');
				$i = 0;

				foreach($orig_files as $file){

					$orig = $this->thisplugindir .  $file;
					$new = $this->micro_root_dir . $new_files[$i];
					$i++;

					// on activation overwrite existing, otherwise it can be skipped.
					if (!$activated and file_exists($new)){
						continue;
					}

					// warn if copy fails
					if (!copy($orig, $new)){
						$this->log(
							esc_html__('File not copied', 'microthemer'),
							'<p>' . sprintf(
								esc_html__('Plugin file (%s) could not be copied to the 
								/micro-themes directory.', 'microthemer'),
								$file
							) . '</p>',
							'error'
						);
						return false;
					}
				}
				return true;
			}


			// copy pie files so Microthemer styles can still be used following uninstall
			/*function maybe_copy_pie(){
				$pie_files = array('PIE.php', 'PIE.htc');
				foreach($pie_files as $file){
					$orig = $this->thisplugindir . '/pie/' . $file;
					$new = $this->micro_root_dir . $file;
					if (file_exists($new)){
						continue;
					}
					if (!copy($orig, $new)){
						$this->log(
							esc_html__('CSS3 PIE not copied', 'microthemer'),
							'<p>' . sprintf(
								esc_html__('CSS3 PIE (%s) could not be copied to correct location. This is needed to support gradients, rounded corners and box-shadow in old versions of Internet Explorer.', 'microthemer'),
								$file
							) . '</p>',
							'error'
						);
						return false;
					}
				}
				return true;
			}*/

			// create micro theme
			function create_micro_theme($micro_name, $action, $temp_zipfile) {
				// sanitize dir name
				$name = sanitize_file_name( $micro_name );
				$error = false;
				// extra bit need for zip uploads (removes .zip)
				if ($action == 'unzip') {
					$name = substr($name, 0, -4);
				}
				// check for micro-themes folder and create if doesn't exist
				$error = !$this->setup_micro_themes_dir() ? true : false;

				// check if the micro-themes folder is writable
				if ( !is_writeable( $this->micro_root_dir ) ) {
					$this->log(
						esc_html__('/micro-themes write error', 'microthemer'),
						'<p>' . sprintf(
							esc_html__('The directory %s is not writable.', 'microthemer'),
							$this->root_rel($this->micro_root_dir)
						) . $this->permissionshelp . '</p>'
					);
					$error = true;
				}
				// Check for micro theme with same name
				if ($alt_name = $this->rename_if_required($this->micro_root_dir, $name)) {
					$name = $alt_name; // $alt_name is false if no rename was required
				}
				// abs path
				$this_micro_abs = $this->micro_root_dir . $name;
				// Create new micro theme folder
				if ( !wp_mkdir_p ( $this_micro_abs ) ) {
					$this->log(
						esc_html__('design pack create error', 'microthemer'),
						'<p>' . sprintf(
							esc_html__('WordPress was not able to create the %s directory.', 'microthemer'), $this->root_rel($this_micro_abs)
						). '</p>'
					);
					$error = true;
				}
				// Check folder permission
				if ( !is_writeable( $this_micro_abs ) ) {
					$this->log(
						esc_html__('design pack write error', 'microthemer'),
						'<p>' . sprintf(
							esc_html__('The directory %s is not writable.', 'microthemer'), $this->root_rel($this_micro_abs)
						) . $this->permissionshelp . '</p>'
					);
					$error = true;
				}

				/*if (SAFE_MODE and $this->preferences['safe_mode_notice'] == '1') {
					$this->log(
						esc_html__('Safe-mode is on', 'microthemer'),
						'<p>' . esc_html__('The PHP server setting "Safe-Mode" is on.', 'microthemer')
						. '</p><p>' . wp_kses(
							sprintf(
								__('<b>This isn\'t necessarily a problem. But if the design pack "%1$s" hasn\'t been created</b>, please create the directory %2$s manually and give it permission code 777.', 'microthemer'),
								$this->readable_name($name), $this->root_rel($this_micro_abs)
							),
							array( 'b' => array() )
						) . $this->permissionshelp
						. '</p>',
						'warning'
					);
					$error = true;
				}
				*/

				// unzip if required
				if ($action == 'unzip') {
					// extract the files
					$this->extract_files($this_micro_abs, $temp_zipfile);
					// get the final name of the design pack from the meta file
					$name = $this->rename_from_meta($this_micro_abs . '/meta.txt', $name);
					if ($name){
						// import bg images to media library and update paths if any are found
						$json_config_file = $this->micro_root_dir . $name . '/config.json';
						$this->import_pack_images_to_library($json_config_file, $name);
					}
					// add the dir to the file structure array
                    $this->file_structure[$name] = $this->dir_loop($this->micro_root_dir . $name);
					ksort($this->file_structure);
					//$this->dir_loop($this->micro_root_dir . $name);


				}

				// if creating blank shell or exporting UI settings, need to create meta.txt and readme.txt
				if ($action == 'export') {
					// set the theme name value
					$_POST['theme_meta']['Name'] = $this->readable_name($name);
					$this->update_meta_file($this_micro_abs . '/meta.txt');
					$this->update_readme_file($this_micro_abs . '/readme.txt');

				}
				// update the theme_in_focus value in the preferences table
				$this->savePreferences(
					array(
						'theme_in_focus' => $name,
					)
				);

				// if still no error, the action worked
				if ($error != true) {
					if ($action == 'create') {
						$this->log(
							esc_html__('Design pack created', 'microthemer'),
							'<p>' . esc_html__('The design pack directory was successfully created on the server.', 'microthemer') . '</p>',
							'notice'
						);
					}
					if ($action == 'unzip') {
						$this->log(
							esc_html__('Design pack installed', 'microthemer'),
							'<p>' . esc_html__('The design pack was successfully uploaded and extracted. You can import it into your Microthemer workspace any time using the') .
							' <span class="show-parent-dialog link" rel="import-from-pack">' . esc_html__('import option', 'microthemer') . '</span>'.
							'<span id="update-packs-list" rel="' . $this->readable_name($name) . '"></span>.</p>',
							'notice'
						);
					}
					if ($action == 'export') {
						$this->log(
							esc_html__('Settings exported', 'microthemer'),
							'<p>' . esc_html__('Your settings were successfully exported as a design pack directory on the server.', 'microthemer') . '</p>',
							'notice'
						);
					}
				}
				return true;
			}

			// rename zip form meta.txt name value
			function rename_from_meta($meta_file, $name){
				$orig_name = $name;
				if (is_file($meta_file) and is_readable($meta_file)) {
					$meta_info = $this->read_meta_file($meta_file);
					$name = strtolower(sanitize_file_name( $meta_info['Name'] ));
					// rename the directory if it doesn't already have the correct name
					if ($orig_name != $name){
						if ($alt_name = $this->rename_if_required($this->micro_root_dir, $name)) {
							$name = $alt_name; // $alt_name is false if no rename was required
						}
						rename($this->micro_root_dir . $orig_name, $this->micro_root_dir . $name);
					}
					return $name;
				} else {
					// no meta file error
					$this->log(
						esc_html__('Missing meta file', 'microthemer'),
						'<p>' . sprintf(
							esc_html__('The zip file doesn\'t contain a necessary %s file or it could not be read.', 'microthemer'),
							$this->root_rel($meta_file)
						) . '</p>'
					);
					return false;
				}
			}

			// read the data from a file into a string
			function get_file_data($file){
				if (!is_file($file)){
					$this->log(
						esc_html__('File doesn\'t exist', 'microthemer'),
						'<p>' . sprintf(
							esc_html__('%s does not exist on the server.', 'microthemer'),
							$this->root_rel($file)
						) . '</p>'
					);
					return false;
				}
				if (!is_readable($file)){
					$this->log(
						esc_html__('File not readable', 'microthemer'),
						'<p>' . sprintf(
							esc_html__(' %s could not be read.', 'microthemer'),
							$this->root_rel($file)
						) . '</p>'
						. $this->permissionshelp
					);
					return false;
				}
				$fh = @fopen($file, 'r');
				$data = fread($fh, filesize($file));
				fclose($fh);
				return $data;
			}

			// get image paths from the config.json file
			function get_image_paths($data){

				$img_array = array();

				// look for images
				preg_match_all('/"(background_image|list_style_image|border_image_src)":"([^none][A-Za-z0-9 _\-\.\\/&\(\)\[\]!\{\}\?:=]+)"/',
					$data,
					$img_array,
					PREG_PATTERN_ORDER);

				// ensure $img_array only contains unique images
				foreach ($img_array[2] as $key => $config_img_path) {

					// if it's not unique, remove
					if (!empty($already_got[$config_img_path])){
						unset($img_array[2][$key]);
					}
					$already_got[$config_img_path] = 1;
				}

				if (count($img_array[2]) > 0) {
					return $img_array;
				} else {
					return false;
				}
			}

			// get media library images linked to from the config.json file
			function get_linked_library_images($json_config_file){

				// get config data
				if (!$data = $this->get_file_data($json_config_file)) {
					return false;
				}

				// get images from the config file that should be imported
				if (!$img_array = $this->get_image_paths($data)) {
					return false;
				}

				// loop through the image array, remove any images not in the media library
				foreach ($img_array[2] as $key => $config_img_path) {
					// has uploads path and doesn't also exist in pack dir (yet to be moved) - may be an unnecessary check
					if (strpos($config_img_path, '/uploads/')!== false and !is_file($this->micro_root_dir . $config_img_path)){
						$library_images[] = $config_img_path;
					}
				}
				if (is_array($library_images)){
					return $library_images;
				} else {
					return false;
				}

			}

			// encode or decode json todo replace other $json_object actions with this function (and test)
			function json($action, $data, $json_file = ''){

				// convert to array
				if ($action == 'decode'){

				    // if we can't decode using native PHP function
				    if (!$json_array = json_decode($data, true)) {

						// MT may be trying to decode data encoded by an older custom JSON class, rather than PHP native
                        // so attempt to decode using legacy class
						if( !class_exists('Moxiecode_JSON') ) {
							require_once($this->thisplugindir . 'includes/class-json.php');
						}
						$json_object = new Moxiecode_JSON();

						// we still can't decode the data
						if (!$json_array = $json_object->decode($data)) {
							$this->log('', '', 'error', 'json-decode', array('json_file', $json_file));
							return false;
						}

					    /*$this->log(
						    esc_html__('Legacy format data successfully decoded', 'microthemer'),
						    '<p>' . esc_html__('Please contact themeover.com for help', 'microthemer') . '</p>',
                           'info'
					    );*/

					}
					return $json_array;
				}

				// convert to json string
                elseif ($action == 'encode'){
					if (!$json_str = json_encode($data)) {
						$this->log(
							esc_html__('Encode json error', 'microthemer'),
							'<p>' . esc_html__('WordPress failed to convert your settings into json.', 'microthemer') . '</p>'
						);
						return false;
					}
					return $json_str;
				}
			}

			// import images in a design pack to the media library and update image paths in config.json
			function import_pack_images_to_library($json_config_file, $name, $data = false, $remote_images = false){

				// reset imported images
				$this->imported_images = array();

				// get config data if not passed in
				if (!$data) {
					if (!$data = $this->get_file_data($json_config_file)) {
						return false;
					}
				}

				// get images from the config file if not passed in
				if (!$remote_images) {
					if (!$img_array = $this->get_image_paths($data)) {
						return false;
					}
					$img_array = $img_array[2];
				} else {
					$img_array = $remote_images;
				}


				// loop through the image array
				foreach ($img_array as $key => $img_path) {

					$just_image_name = basename($img_path);

					// if remote image found in stylesheet downloaded to /tmp dir
					if ($remote_images){
						$tmp_image = $img_path; // C:/
						$orig_config_path = $key; // url
					} else {
						// else pack image found in zip
						$tmp_image = $this->micro_root_dir . $name . '/' . $just_image_name; // C:/
						$orig_config_path = $img_path; // url
					}

					// import the file to the media library if it exists
					if (file_exists($tmp_image)) {
						$this->imported_images[$just_image_name]['orig_config_path'] = $orig_config_path;

						// note import_image_to_library() updates 'success' and 'new_config_path'
						$id = $this->import_image_to_library($tmp_image, $just_image_name);

						// report wp error if problem
						if ( $id === 0 or is_wp_error($id) ) {
							if (is_wp_error($id)){
								$wp_error = '<p>'. $id->get_error_message() . '</p>';
							} else {
								$wp_error = '';
							}
							$this->log(
								esc_html__('Move to media library failed', 'microthemer'),
								'<p>' . sprintf(
									esc_html__('%s was not imported due to an error.', 'microthemer'),
									$this->root_rel($tmp_image)
								) . '</p>'
								. $wp_error
							);
						}
					}
				}

				// first report successfully moved images
				$moved_list =
					'<ul>';
				$moved = false;
				foreach ($this->imported_images as $just_image_name => $array){
					if (!empty($array['success'])){
						$moved_list.= '
						<li>
							'.$just_image_name.'
						</li>';
						$moved = true;
						// also update the json data string
						$replacements[$array['orig_config_path']] = $array['new_config_path'];
					}
				}
				$moved_list.=
					'</ul>';

				// move was successful, update paths
				if ($moved){
					$this->log(
						esc_html__('Images transferred to media library', 'microthemer'),
						'<p>' . esc_html__('The following images were transferred from the design pack to your WordPress media library:', 'microthemer') . '</p>'
						. $moved_list,
						'notice'
					);
					// update paths in json file
					return $this->replace_json_paths($json_config_file, $replacements, $data, $remote_images);
				}
			}

			// update paths in json file
			function replace_json_paths($json_config_file, $replacements, $data = false, $remote_images = false){

				if (!$data){
					if (!$data = $this->get_file_data($json_config_file)) {
						return false;
					}
				}

				// replace paths in string
				$replacement_occurred = false;
				foreach ($replacements as $orig => $new){
					if (strpos($data, $orig) !== false){
						$replacement_occurred = true;
						$data = str_replace($orig, $new, $data);
					}
				}
				if (!$replacement_occurred){
					return false;
				}

				// just return updated json data if loading css stylesheet
				if ($remote_images){
					$this->log(
						esc_html__('Image paths updated', 'microthemer'),
						'<p>' . esc_html__('Images paths were successfully updated to reflect the new location or deletion of an image(s).', 'microthemer') . '</p>',
						'notice'
					);
					return $data;
				}

				// update the config.json image paths for images successfully moved to the library
				if (is_writable($json_config_file)) {
					if ($write_file = @fopen($json_config_file, 'w')) {
						if (fwrite($write_file, $data)) {
							fclose($write_file);
							$this->log(
								esc_html__('Images paths updated', 'microthemer'),
								'<p>' . esc_html__('Images paths were successfully updated to reflect the new location or deletion of an image(s).', 'microthemer') . '</p>',
								'notice'
							);
							return true;
						}
						else {
							$this->log(
								esc_html__('Image paths failed to update.', 'microthemer'),
								'<p>' . esc_html__('Images paths could not be updated to reflect the new location of the images transferred to your media library. This happened because Microthemer could not rewrite the config.json file.', 'microthemer') . '</p>' . $this->permissionshelp
							);
							return false;
						}
					}
				}
			}

			// Unitless css values need to be auto-adjusted to explicit pixels if the user's preference
			// for the prop is not 'px (implicit)' and the value is a unitless number
			// Conversely, px values need to be removed if implicit pixels is set (and not custom code value)
			// Note: we can't do e.g. em conversion here as we don't know the DOM context
			/*function filter_json_css_units($data, $context = 'reg'){

			    $filtered_json = $data;
				$possible_units = array_merge(array_keys($this->css_units), $this->special_css_units);
				$before_units_change = empty($filtered_json['non_section']['mt_version']);

				foreach ($filtered_json as $section_name => $array){
					if ($section_name == 'non_section') {
						continue;
					}
					if (is_array($array)) {
						foreach ($array as $css_selector => $arr) {
							if ( is_array( $arr['styles'] ) ) {
								foreach ($arr['styles'] as $prop_group => $arr2) {
									if (is_array($arr2)) {
										foreach ($arr2 as $prop => $value) {

										    // data structure was updated
										    $value = !isset($value['value']) ? $value : $value['value'] ;

										    // if the property has a default unit
											if (isset($this->preferences['my_props'][$prop_group]['pg_props'][$prop]['default_unit'])){

											    $default_unit = $this->preferences['my_props'][$prop_group]['pg_props'][$prop]['default_unit'];

											    // if the unit is included in the value, remove it and add to unit key
                                                // todo limit to single values with css unit
												preg_match('/('.implode('|', $possible_units).')\s*$/', $value, $unit_match);

												if ($unit_match){
												    $extracted_css_unit = $unit_match[1];
                                                    $unitless_value = preg_replace(
                                                            '/'.$extracted_css_unit.'\s*$/', '', $value
                                                    );
													$filtered_json[$section_name][$css_selector]['styles'][$prop_group][$prop]['value'] = $unitless_value;
													$filtered_json[$section_name][$css_selector]['styles'][$prop_group][$prop]['unit'] = $extracted_css_unit;
												}

												// if the value is a unitless number from before MT updated the units system,
                                                // apply px so user's current default_unit setting doesn't change things to pixels
												else if ($before_units_change && $prop !== 'line_height' && is_numeric($value) && $value != 0){ //
													$filtered_json[$section_name][$css_selector]['styles'][$prop_group][$prop]['unit'] = 'px';
												}

											}
										}
									}
								}
							}
						}
					}
				}
				return $filtered_json;
			}*/

			//Handle an individual file import.
			function import_image_to_library($file, $just_image_name, $post_id = 0, $import_date = false) {
				set_time_limit(60);
				// Initially, Base it on the -current- time.
				$time = current_time('mysql', 1);
				// A writable uploads dir will pass this test. Again, there's no point overriding this one.
				if ( ! ( ( $uploads = wp_upload_dir($time) ) && false === $uploads['error'] ) ) {
					$this->log(
						esc_html__('Uploads folder error', 'microthemer'),
						$uploads['error']
					);
					return 0;
				}

				$wp_filetype = wp_check_filetype( $file, null );
				$type = $ext = false;
				extract( $wp_filetype );
				if ( ( !$type || !$ext ) && !current_user_can( 'unfiltered_upload' ) ) {
					$this->log(
						esc_html__('Wrong file type', 'microthemer'),
						'<p>' . esc_html__('Sorry, this file type is not permitted for security reasons.', 'microthemer') . '</p>'
					);
					return 0;
				}

				//Is the file already in the uploads folder?
				if ( preg_match('|^' . preg_quote(str_replace('\\', '/', $uploads['basedir'])) . '(.*)$|i', $file, $mat) ) {
					$filename = basename($file);
					$new_file = $file;

					$url = $uploads['baseurl'] . $mat[1];

					$attachment = get_posts(array( 'post_type' => 'attachment', 'meta_key' => '_wp_attached_file', 'meta_value' => ltrim($mat[1], '/') ));
					if ( !empty($attachment) ) {
						$this->log(
							esc_html__('Image already in library', 'microthemer'),
							'<p>' . sprintf(
								esc_html__('%s already exists in the WordPress media library and was therefore not moved', 'microthemer'),
								$filename
							) . '</p>',
							'warning'
						);
						return 0;
					}
					//OK, Its in the uploads folder, But NOT in WordPress's media library.
				} else {
					$filename = wp_unique_filename( $uploads['path'], basename($file));

					// copy the file to the uploads dir
					$new_file = $uploads['path'] . '/' . $filename;
					if ( false === @rename( $file, $new_file ) ) {
						$this->log(
							esc_html__('Move to library failed', 'microthemer'),
							'<p>' . sprintf(
								esc_html__('%1$s could not be moved to %2$s', 'microthemer'),
								$filename,
								$uploads['path']
							) . '</p>',
							'warning'
						);
						return 0;
					}


					// Set correct file permissions
					$stat = stat( dirname( $new_file ));
					$perms = $stat['mode'] & 0000666;
					@ chmod( $new_file, $perms );
					// Compute the URL
					$url = $uploads['url'] . '/' . $filename;
				}

				//Apply upload filters
				$return = apply_filters( 'wp_handle_upload', array( 'file' => $new_file, 'url' => $url, 'type' => $type ) );
				$new_file = $return['file'];
				$url = $return['url'];
				$type = $return['type'];

				$title = preg_replace('!\.[^.]+$!', '', basename($new_file));
				$content = '';

				// update the array for replacing paths in config.json
				$this->imported_images[$just_image_name]['success'] = true;
				$this->imported_images[$just_image_name]['new_config_path'] = $this->root_rel($url, false, true);

				// use image exif/iptc data for title and caption defaults if possible
				if ( $image_meta = @wp_read_image_metadata($new_file) ) {
					//if ( '' != trim($image_meta['title']) )
					//$title = trim($image_meta['title']);
					if ( '' != trim($image_meta['caption']) )
						$content = trim($image_meta['caption']);
				}

				//=sebcus the title should reflect a possible file rename e.g. image1 - happens above ^
				//$title = str_replace('.'.$ext, '', $filename);

				if ( $time ) {
					$post_date_gmt = $time;
					$post_date = $time;
				} else {
					$post_date = current_time('mysql');
					$post_date_gmt = current_time('mysql', 1);
				}

				// Construct the attachment array
				$attachment = array(
					'post_mime_type' => $type,
					'guid' => $url,
					'post_parent' => $post_id,
					'post_title' => $title,
					'post_name' => $title,
					'post_content' => $content,
					'post_date' => $post_date,
					'post_date_gmt' => $post_date_gmt
				);

				$attachment = apply_filters('afs-import_details', $attachment, $file, $post_id, $import_date);

				//Win32 fix:
				$new_file = str_replace( strtolower(str_replace('\\', '/', $uploads['basedir'])), $uploads['basedir'], $new_file);

				// Save the data
				$id = wp_insert_attachment($attachment, $new_file, $post_id);
				if ( !is_wp_error($id) ) {
					$data = wp_generate_attachment_metadata( $id, $new_file );
					wp_update_attachment_metadata( $id, $data );
				}
				//update_post_meta( $id, '_wp_attached_file', $uploads['subdir'] . '/' . $filename );

				return $id;
			}

			// handle zip package
			function handle_zip_package() {
				$temp_zipfile = $_FILES['upload_micro']['tmp_name'];
				$filename = $_FILES['upload_micro']['name']; // it won't be this name for long
				// Chrome return a empty content-type : http://code.google.com/p/chromium/issues/detail?id=6800
				if ( !preg_match('/chrome/i', $_SERVER['HTTP_USER_AGENT']) ) {
					// check if file is a zip file
					if ( !preg_match('/(zip|download|octet-stream)/i', $_FILES['upload_micro']['type']) ) {
						@unlink($temp_zipfile); // del temp file
						$this->log(
							esc_html__('Faulty zip file', 'microthemer'),
							'<p>' . esc_html__('The uploaded file was faulty or was not a zip file.', 'microthemer') . '</p>
						<p>' . esc_html__('The server recognised this file type: ', 'microthemer') . $_FILES['upload_micro']['type'].'</p>'
						);
						return false;
					}
				}
				$this->create_micro_theme($filename, 'unzip', $temp_zipfile);
			}

			// handle zip extraction
			function extract_files($dir, $file) {
				// tap into native WP zip handling
				if( !class_exists('PclZip')) {
					require_once(ABSPATH . 'wp-admin/includes/class-pclzip.php');
				}
				$archive = new PclZip($file);
				// extract all files in one folder - the callback functions
				// (tvr_microthemer_getOnlyValid)
				// have to be external to this class
				if ($archive->extract(PCLZIP_OPT_PATH, $dir, PCLZIP_OPT_REMOVE_ALL_PATH,
						PCLZIP_CB_PRE_EXTRACT, 'tvr_micro'.TVR_MICRO_VARIANT.'_getOnlyValid') == 0) {
					$this->log(
						esc_html__('Extract zip error', 'microthemer'),
						'<p>' . esc_html__('Error : ', 'microthemer') . $archive->errorInfo(true).'</p>'
					);
				}
			}

			// handle zip archiving
			function create_zip($path_to_dir, $dir_name, $zip_store) {
				$error = false;
				if( !class_exists('PclZip')) {
					require_once(ABSPATH . 'wp-admin/includes/class-pclzip.php');
				}
				// check if the /zip-exports dir is writable first
				if (is_writable($zip_store)) {
					$archive = new PclZip($zip_store.$dir_name.'.zip');
					// create zip
					$v_list = $archive->create($path_to_dir.$dir_name,
						PCLZIP_OPT_REMOVE_PATH, $path_to_dir);
					if ($v_list == 0) {
						$error = true;
						$this->log(
							esc_html__('Create zip error', 'microthemer'),
							'<p>' . esc_html__('Error : ', 'microthemer') . $archive->errorInfo(true).'</p>'
						);
					}
					else {
						$this->log(
							esc_html__('Zip package created', 'microthemer'),
							'<p>' . esc_html__('Zip package successfully created.', 'microthemer') .
							'<a href="'.$this->thispluginurl.'zip-exports/'.$dir_name.'.zip">' .
							esc_html__('Download zip file', 'microthemer') . '</a>
						</p>',
							'notice'
						);
					}
				}
				else {
					$error = true;
					$this->log(
						esc_html__('Zip store error', 'microthemer'),
						'<p>' . sprintf(
							esc_html__('The directory %s is not writable.', 'microthemer'),
							$this->root_rel($zip_store)
						) . $this->permissionshelp . '</p>'
					);
				}
				// verdict
				if ($error){
					return false;
				} else {
					return true;
				}
			}

			// read meta data from file
			function read_meta_file($meta_file) {
				// create default meta.txt file if it doesn't exist
				if (!is_file($meta_file)) {
					$_POST['theme_meta']['Name'] = $this->readable_name($this->preferences['theme_in_focus']);
					$this->update_meta_file($this->micro_root_dir . $this->preferences['theme_in_focus'].'/meta.txt');
				}
				if (is_file($meta_file)) {
					// check if it's readable
					if ( is_readable($meta_file) ) {
						//disable wptexturize
						remove_filter('get_theme_data', 'wptexturize');
						return $this->flx_get_theme_data( $meta_file );
					}
					else {
						$abs_meta_path = $this->micro_root_dir . $this->preferences['theme_in_focus'].'/meta.txt';

						$this->log(
							esc_html__('Read meta.txt error', 'microthemer'),
							'<p>' . esc_html__('WordPress does not have permission to read: ', 'microthemer') .
							$this->root_rel($abs_meta_path) . '. '.$this->permissionshelp.'</p>'
						);
						return false;
					}
				}
			}

			// read readme.txt data from file
			function read_readme_file($readme_file) {
				// create default readme file if it doesn't exist
				if (!is_file($readme_file)) {
					$this->update_readme_file($this->micro_root_dir . $this->preferences['theme_in_focus'].'/readme.txt');
				}
				if (is_file($readme_file)) {
					// check if it's readable
					if ( is_readable($readme_file) ) {
						$fh = @fopen($readme_file, 'r');
						$length = filesize($readme_file);
						if ($length == 0) {
							$length = 1;
						}
						$data = fread($fh, $length);
						fclose($fh);
						return $data;
					}
					else {
						$abs_readme_path = $this->micro_root_dir . $this->preferences['theme_in_focus'].'/readme.txt';
						$this->log(
							esc_html__('Read readme.txt error', 'microthemer'),
							'<p>' . esc_html__('WordPress does not have permission to read: ', 'microthemer'),
							$this->root_rel($abs_readme_path) . '. '.$this->permissionshelp.'</p>'
						);
						return false;
					}
				}
			}

			// adapted WordPress function for reading and formattings a template file
			function flx_get_theme_data( $theme_file ) {
				$default_headers = array(
					'Name' => 'Theme Name',
					'PackType' => 'Pack Type',
					'URI' => 'Theme URI',
					'Description' => 'Description',
					'Author' => 'Author',
					'AuthorURI' => 'Author URI',
					'Version' => 'Version',
					'Template' => 'Template',
					'Status' => 'Status',
					'Tags' => 'Tags'
				);
				// define allowed tags
				$themes_allowed_tags = array(
					'a' => array(
						'href' => array(),'title' => array()
					),
					'abbr' => array(
						'title' => array()
					),
					'acronym' => array(
						'title' => array()
					),
					'code' => array(),
					'em' => array(),
					'strong' => array()
				);
				// get_file_data() - WP 2.8 compatibility function created for this
				$theme_data = get_file_data( $theme_file, $default_headers, 'theme' );
				$theme_data['Name'] = $theme_data['Title'] = wp_kses( $theme_data['Name'], $themes_allowed_tags );
				$theme_data['PackType'] = wp_kses( $theme_data['PackType'], $themes_allowed_tags );
				$theme_data['URI'] = esc_url( $theme_data['URI'] );
				$theme_data['Description'] = wp_kses( $theme_data['Description'], $themes_allowed_tags );
				$theme_data['AuthorURI'] = esc_url( $theme_data['AuthorURI'] );
				$theme_data['Template'] = wp_kses( $theme_data['Template'], $themes_allowed_tags );
				$theme_data['Version'] = wp_kses( $theme_data['Version'], $themes_allowed_tags );
				if ( empty($theme_data['Status']) )
					$theme_data['Status'] = 'publish';
				else
					$theme_data['Status'] = wp_kses( $theme_data['Status'], $themes_allowed_tags );

				if ( empty($theme_data['Tags']) )
					$theme_data['Tags'] = array();
				else
					$theme_data['Tags'] = array_map( 'trim', explode( ',', wp_kses( $theme_data['Tags'], array() ) ) );

				if ( empty($theme_data['Author']) ) {
					$theme_data['Author'] = $theme_data['AuthorName'] = __('Anonymous');
				} else {
					$theme_data['AuthorName'] = wp_kses( $theme_data['Author'], $themes_allowed_tags );
					if ( empty( $theme_data['AuthorURI'] ) ) {
						$theme_data['Author'] = $theme_data['AuthorName'];
					} else {
						$theme_data['Author'] = sprintf( '<a href="%1$s" title="%2$s">%3$s</a>', $theme_data['AuthorURI'], esc_html__( 'Visit author homepage' ), $theme_data['AuthorName'] );
					}
				}
				return $theme_data;
			}

			// delete theme
			function tvr_delete_micro_theme($dir_name) {
				$error = false;
				// loop through files if they exist
				if (is_array($this->file_structure[$dir_name])) {
					foreach ($this->file_structure[$dir_name] as $file => $junk) {
						if (!unlink($this->micro_root_dir . $dir_name.'/'.$file)) {
							$this->log(
								esc_html__('File delete error', 'microthemer'),
								'<p>' . esc_html__('Unable to delete: ', 'microthemer') .
								$this->root_rel($this->micro_root_dir .
								                $dir_name.'/'.$file) . print_r($this->file_structure[$dir_name], true). '</p>'
							);
							$error = true;
						}
					}
				}
				if ($error != true) {
					$this->log(
						'Files successfully deleted',
						'<p>' . sprintf(
							esc_html__('All files within %s were successfully deleted.', 'microthemer'),
							$this->readable_name($dir_name)
						) . '</p>',
						'dev-notice'
					);
					// attempt to delete empty directory
					if (!rmdir($this->micro_root_dir . $dir_name)) {
						$this->log(
							esc_html__('Delete directory error', 'microthemer'),
							'<p>' . sprintf(
								esc_html__('The empty directory: %s could not be deleted.', 'microthemer'),
								$this->readable_name($dir_name)
							) . '</p>'
						);
						$error = true;
					}
					else {
						$this->log(
							esc_html__('Directory successfully deleted', 'microthemer'),
							'<p>' . sprintf(
								esc_html__('%s was successfully deleted.', 'microthemer'),
								$this->readable_name($dir_name)
							) . '</p>',
							'notice'
						);

						// reset the theme_in_focus value in the preferences table
						$pref_array['theme_in_focus'] = '';
						if (!$this->savePreferences($pref_array)) {
							// not much cause for a message
						}
						if ($error){
							return false;
						} else {
							return true;
						}
					}
				}
			}

			// update the meta file
			function update_meta_file($meta_file) {
				// check if the micro theme dir needs to be renamed
				if (isset($_POST['prev_micro_name']) and ($_POST['prev_micro_name'] != $_POST['theme_meta']['Name'])) {
					$orig_name = $this->micro_root_dir . $this->preferences['theme_in_focus'];
					$new_theme_in_focus = sanitize_file_name(sanitize_title($_POST['theme_meta']['Name']));
					// need to do unique dir check here too
					// Check for micro theme with same name
					if ($alt_name = $this->rename_if_required($this->micro_root_dir, $new_theme_in_focus)) {
						$new_theme_in_focus = $alt_name;
						// The dir had to be automatically renamed so update the visible name
						$_POST['theme_meta']['Name'] = $this->readable_name($new_theme_in_focus);
					}
					$new_name = $this->micro_root_dir . $new_theme_in_focus;
					// if the directory is writable
					if (is_writable($orig_name)) {
						if (rename($orig_name, $new_name)) {
							// if rename is successful...

							// the meta file will have a different location now
							$meta_file = str_replace($this->preferences['theme_in_focus'], $new_theme_in_focus, $meta_file);

							// update the files array directory key
							$cache = $this->file_structure[$this->preferences['theme_in_focus']];
							$this->file_structure[$new_theme_in_focus] = $cache;
							unset($this->file_structure[$this->preferences['theme_in_focus']]);

							// update the value in the preferences table
							$pref_array = array();
							$pref_array['theme_in_focus'] = $new_theme_in_focus;
							if ($this->savePreferences($pref_array)) {
								$this->log(
									esc_html__('Design pack renamed', 'microthemer'),
									'<p>' . esc_html__('The design pack directory was successfully renamed on the server.', 'microthemer') . '</p>',
									'notice'
								);
							}
						}
						else {
							$this->log(
								esc_html__('Directory rename error', 'microthemer'),
								'<p>' . sprintf(
									esc_html__('The directory %s could not be renamed for some reason.', 'microthemer'),
									$this->root_rel($orig_name)
								) . '</p>'
							);
						}
					}
					else {
						$this->log(
							esc_html__('Directory rename error', 'microthemer'),
							'<p>' . sprintf(
								esc_html__('WordPress does not have permission to rename the directory %1$s to match your new theme name "%2$s".', 'microthemer'),
								$this->root_rel($orig_name),
								htmlentities($this->readable_name($_POST['theme_meta']['Name']))
							) . $this->permissionshelp.'.</p>'
						);
					}
				}


				// Create new file if it doesn't already exist
				if (!file_exists($meta_file)) {
					if (!$write_file = @fopen($meta_file, 'w')) {
						$this->log(
							sprintf( esc_html__('Create %s error', 'microthemer'), 'meta.txt' ),
							'<p>' . sprintf(esc_html__('WordPress does not have permission to create: %s', 'microthemer'), $this->root_rel($meta_file) . '. '.$this->permissionshelp ) . '</p>'
						);
					}
					else {
						fclose($write_file);
					}
					$task = 'created';
					// set post variables if undefined (might be following initial export)

					if (!isset($_POST['theme_meta']['Description'])) {

						$current_user = wp_get_current_user();

						//global $user_identity;
						//get_currentuserinfo();
						/* get the user's website (fallback on site_url() if null)
						$user_info = get_userdata($user_ID);
						if ($user_info->user_url != '') {
							$author_uri = $user_info->user_url;
						}
						else {
							$author_uri = site_url();
						}*/
						// get parent theme name and version
						//$theme_data = wp_get_theme(get_stylesheet_uri());
						// $template = $theme_data['Name'] . ' ' . $theme_data['Version'];
						//$template = $theme_data['Name'];
						$_POST['theme_meta']['Description'] = "";
						$_POST['theme_meta']['PackType'] ='';
						$_POST['theme_meta']['Author'] = $current_user->display_name;
						$_POST['theme_meta']['AuthorURI'] = '';
						// $_POST['theme_meta']['Template'] = get_current_theme();
						$_POST['theme_meta']['Template'] = '';
						$_POST['theme_meta']['Version'] = '1.0';
						$_POST['theme_meta']['Tags'] = '';

					}
				}
				else {
					$task = 'updated';
				}



				// check if it's writable - // need to remove carriage returns
				if ( is_writable($meta_file) ) {

					/*
					note: if DateCreated is missing the pack was made before june 12.
					This may or may not be useful information.
					*/

					//removed Theme URI: '.strip_tags(stripslashes($_POST['theme_meta']['URI'])).'

					$Name = !empty($_POST['theme_meta']['Name']) ? $_POST['theme_meta']['Name'] : '';
					$PackType = !empty($_POST['theme_meta']['PackType']) ? $_POST['theme_meta']['PackType'] : '';
					$Description = !empty($_POST['theme_meta']['Description']) ? $_POST['theme_meta']['Description'] : '';
					$Author = !empty($_POST['theme_meta']['Author']) ? $_POST['theme_meta']['Author'] : '';
					$AuthorURI = !empty($_POST['theme_meta']['AuthorURI']) ? $_POST['theme_meta']['AuthorURI'] : '';
					$Template = !empty($_POST['theme_meta']['Template']) ? $_POST['theme_meta']['Template'] : '';
					$Version = !empty($_POST['theme_meta']['Version']) ? $_POST['theme_meta']['Version'] : '';
					$Tags = !empty($_POST['theme_meta']['Tags']) ? $_POST['theme_meta']['Tags'] : '';

					$data = '/*
Theme Name: '.strip_tags(stripslashes($Name)).'
Pack Type: '.strip_tags(stripslashes($PackType)).'
Description: '.strip_tags(stripslashes(str_replace(array("\n", "\r"), array(" ", ""), $Description))).'
Author: '.strip_tags(stripslashes($Author)).'
Author URI: '.strip_tags(stripslashes($AuthorURI)).'
Template: '.strip_tags(stripslashes($Template)).'
Version: '.strip_tags(stripslashes($Version)).'
Tags: '.strip_tags(stripslashes($Tags)).'
DateCreated: '.date('Y-m-d').'
*/';

					// the file will be created if it doesn't exist. otherwise it is overwritten.
					$write_file = @fopen($meta_file, 'w');
					fwrite($write_file, $data);
					fclose($write_file);
					// success message
					$this->log(
						'meta.txt '.$task,
						'<p>' . sprintf( esc_html__('The %1$s file for the design pack was %2$s', 'microthemer'), 'meta.txt', $task ) . '</p>',
						'dev-notice'
					);
				}
				else {
					$this->log(
						sprintf( esc_html__('Write %s error', 'microthemer'), 'meta.txt'),
						'<p>' . esc_html__('WordPress does not have "write" permission for: ', 'microthemer') .
						$this->root_rel($meta_file) . '. '.$this->permissionshelp.'</p>'
					);
				}

			}

			// update the readme file
			function update_readme_file($readme_file) {
				// Create new file if it doesn't already exist
				if (!file_exists($readme_file)) {
					if (!$write_file = @fopen($readme_file, 'w')) {
						$this->log(
							sprintf( esc_html__('Create %s error', 'microthemer'), 'readme.txt'),
							'<p>' . sprintf(
								esc_html__('WordPress does not have permission to create: %s', 'microthemer'),
								$this->root_rel($readme_file) . '. '.$this->permissionshelp
							) . '</p>'
						);
					}
					else {
						fclose($write_file);
					}
					$task = 'created';
					// set post variable if undefined (might be defined if theme dir has been
					// created manually and then user is submitting readme info for the first time)
					if (!isset($_POST['tvr_theme_readme'])) {
						$_POST['tvr_theme_readme'] = '';
					}
				}
				else {
					$task = 'updated';
				}
				// check if it's writable
				if ( is_writable($readme_file) ) {
					$data = stripslashes($_POST['tvr_theme_readme']); // don't use striptags so html code can be added
					// the file will be created if it doesn't exist. otherwise it is overwritten.
					$write_file = @fopen($readme_file, 'w');
					fwrite($write_file, $data);
					fclose($write_file);
					// success message
					$this->log(
						'readme.txt '.$task,
						'<p>' . sprintf(
							esc_html__('The %1$s file for the design pack was %2$s', 'microthemer'),
							'readme.txt', $task
						) . '</p>',
						'dev-notice'
					);
				}
				else {
					$this->log(
						sprintf( esc_html__('Write %s error', 'microthemer'), 'readme.txt'),
						'<p>' . esc_html__('WordPress does not have "write" permission for: ', 'microthemer') .
						$this->root_rel($readme_file) . '. '.$this->permissionshelp.'</p>'
					);
				}
			}

			// handle file upload
			function handle_file_upload() {
				// if no error
				if ($_FILES['upload_file']['error'] == 0) {
					$file = $_FILES['upload_file']['name'];
					// check if the file has a valid extension
					if ($this->is_acceptable($file)) {
						$dest_dir = $this->micro_root_dir . $this->preferences['theme_in_focus'].'/';
						// check if the directory is writable
						if (is_writeable($dest_dir) ) {
							// copy file if safe
							if (is_uploaded_file($_FILES['upload_file']['tmp_name'])
							    and copy($_FILES['upload_file']['tmp_name'], $dest_dir . $file)) {
								$this->log(
									esc_html__('File successfully uploaded', 'microthemer'),
									'<p>' . wp_kses(
										sprintf(
											__('<b>%s</b> was successfully uploaded.', 'microthemer'),
											htmlentities($file)
										),
										array( 'b' => array() )
									) . '</p>',
									'notice'
								);
								// update the file_structure array
								$this->file_structure[$this->preferences['theme_in_focus']][$file] = 1;

								// resize file if it's a screeshot
								if ($this->is_screenshot($file)) {
									$img_full_path = $dest_dir . $file;
									// get the screenshot size, resize if too big
									list($width, $height) = getimagesize($img_full_path);
									if ($width > 896 or $height > 513){
										$this->wp_resize(
											$img_full_path,
											896,
											513,
											$img_full_path);
									}
									// now do thumbnail
									$thumbnail = $dest_dir . 'screenshot-small.'. $this->get_extension($file);
									$root_rel_thumb = $this->root_rel($thumbnail);
									if (!$final_dimensions = $this->wp_resize(
										$img_full_path,
										145,
										83,
										$thumbnail)) {
										$this->log(
											esc_html__('Screenshot thumbnail error', 'microthemer'),
											'<p>' . wp_kses(
												sprintf(
													__('Could not resize <b>%s</b> to thumbnail proportions.', 'microthemer'),
													$root_rel_thumb
												),
												array( 'b' => array() )
											) . $img_full_path .
											esc_html__(' thumb: ', 'microthemer') .$thumbnail.'</p>'
										);
									}
									else {
										// update the file_structure array
										$file = basename($thumbnail);
										$this->file_structure[$this->preferences['theme_in_focus']][$file] = 1;
										$this->log(
											esc_html__('Screenshot thumbnail successfully created', 'microthemer'),
											'<p>' . sprintf(
												esc_html__('%s was successfully created.', 'microthemer'),
												$root_rel_thumb
											) . '</p>',
											'notice'
										);
									}
								}


							}
						}
						// it's not writable
						else {
							$this->log(
								esc_html__('Write to directory error', 'microthemer'),
								'<p>'. esc_html__('WordPress does not have "Write" permission to the directory: ', 'microthemer') .
								$this->root_rel($dest_dir) . '. '.$this->permissionshelp.'.</p>'
							);
						}
					}
					else {
						$this->log(
							esc_html__('Invalid file type', 'microthemer'),
							'<p>' . esc_html__('You have uploaded a file type that is not allowed.', 'microthemer') . '</p>'
						);

					}
				}
				// there was an error - save in global message
				else {
					$this->log_file_upload_error($_FILES['upload_file']['error']);
				}
			}

			// log file upload problem
			function log_file_upload_error($error){
				switch ($error) {
					case 1:
						$this->log(
							esc_html__('File upload limit reached', 'microthemer'),
							'<p>' . esc_html__('The file you uploaded exceeded your "upload_max_filesize" limit. This is a PHP setting on your server.', 'microthemer') . '</p>'
						);
						break;
					case 2:
						$this->log(
							esc_html__('File size too big', 'microthemer'),
							'<p>' . esc_html__('The file you uploaded exceeded your "max_file_size" limit. This is a PHP setting on your server.', 'microthemer') . '</p>'
						);
						break;
					case 3:
						$this->log(
							esc_html__('Partial upload', 'microthemer'),
							'<p>' . esc_html__('The file you uploaded only partially uploaded.', 'microthemer') . '</p>'
						);
						break;
					case 4:
						$this->log(
							esc_html__('No file uploaded', 'microthemer'),
							'<p>' . esc_html__('No file was detected for upload.', 'microthemer') . '</p>'
						);
						break;
				}
			}

			// resize image using wordpress functions
			function wp_resize($path, $w, $h, $dest, $crop = true){
				$image = wp_get_image_editor( $path );
				if ( ! is_wp_error( $image ) ) {
					$image->resize( $w, $h, $crop );
					$image->save( $dest );
					return true;
				} else {
					return false;
				}
			}

			// resize image
			function resize($img, $max_width, $max_height, $newfilename) {
				//Check if GD extension is loaded
				if (!extension_loaded('gd') && !extension_loaded('gd2')) {
					$this->log(
						esc_html__('GD not loaded', 'microthemer'),
						'<p>' . esc_html__('The PHP extension GD is not loaded.', 'microthemer') . '</p>'
					);
					return false;
				}
				//Get Image size info
				$imgInfo = getimagesize($img);
				switch ($imgInfo[2]) {
					case 1: $im = imagecreatefromgif($img); break;
					case 2: $im = imagecreatefromjpeg($img); break;
					case 3: $im = imagecreatefrompng($img); break;
					default:
						$this->log(
							esc_html__('File type error', 'microthemer'),
							'<p>' . esc_html__('Unsuported file type. Are you sure you uploaded an image?', 'microthemer') . '</p>'
						);

						return false; break;
				}
				// orig dimensions
				$width = $imgInfo[0];
				$height = $imgInfo[1];
				// set proportional max_width and max_height if one or the other isn't specified
				if ( empty($max_width)) {
					$max_width = round($width/($height/$max_height));
				}
				if ( empty($max_height)) {
					$max_height = round($height/($width/$max_width));
				}
				// abort if user tries to enlarge a pic
				if (($max_width > $width) or ($max_height > $height)) {
					$this->log(
						esc_html__('Dimensions too big', 'microthemer'),
						'<p>' . sprintf(
							esc_html__('The resize dimensions you specified (%1$s x %2$s) are bigger than the original image (%3$s x %4$s). This is not allowed.', 'microthemer'),
							$max_width, $max_height, $width, $height
						) . '</p>'
					);
					return false;
				}

				// proportional resizing
				$x_ratio = $max_width / $width;
				$y_ratio = $max_height / $height;
				if (($width <= $max_width) && ($height <= $max_height)) {
					$tn_width = $width;
					$tn_height = $height;
				}
				else if (($x_ratio * $height) < $max_height) {
					$tn_height = ceil($x_ratio * $height);
					$tn_width = $max_width;
				}
				else {
					$tn_width = ceil($y_ratio * $width);
					$tn_height = $max_height;
				}
				// for compatibility
				$nWidth = $tn_width;
				$nHeight = $tn_height;
				$final_dimensions['w'] = $nWidth;
				$final_dimensions['h'] = $nHeight;
				$newImg = imagecreatetruecolor($nWidth, $nHeight);
				/* Check if this image is PNG or GIF, then set if Transparent*/
				if(($imgInfo[2] == 1) or ($imgInfo[2]==3)) {
					imagealphablending($newImg, false);
					imagesavealpha($newImg,true);
					$transparent = imagecolorallocatealpha($newImg, 255, 255, 255, 127);
					imagefilledrectangle($newImg, 0, 0, $nWidth, $nHeight, $transparent);
				}
				imagecopyresampled($newImg, $im, 0, 0, 0, 0, $nWidth, $nHeight, $imgInfo[0], $imgInfo[1]);
				// Generate the file, and rename it to $newfilename
				switch ($imgInfo[2]) {
					case 1: imagegif($newImg,$newfilename); break;
					case 2: imagejpeg($newImg,$newfilename); break;
					case 3: imagepng($newImg,$newfilename); break;
					default:
						$this->log(
							esc_html__('Image resize failed', 'microthemer'),
							'<p>' . esc_html__('Your image could not be resized.', 'microthemer') . '</p>'
						);
						return false;
						break;
				}
				return $final_dimensions;
			}
			// next function


		} // End Class
	} // End if class exists statement

	/***
	PCLZIP only seems to accept non Class member functions, hence the following functions appear here
	 ***/

	// check file types (microthemer)
	if (!function_exists('tvr_microthemer_getOnlyValid')) {
		function tvr_microthemer_getOnlyValid($p_event, &$p_header) {

		    // avoid null byte hack (THX to Dominic Szablewski)
			if ( strpos($p_header['filename'], chr(0) ) !== false ){
				$p_header['filename'] = substr ( $p_header['filename'], 0, strpos($p_header['filename'], chr(0) ));
            }

			$info = pathinfo($p_header['filename']);

			// check for extension
			$ext = array('jpeg', 'jpg', 'png', 'gif', 'txt', 'json', 'psd', 'ai');
			$check_ext = strtolower($info['extension']);
			if ( in_array($check_ext, $ext) ) {
				// For MAC skip the ".image" files
				if ($info['basename'][0] == '.' ){
					return 0;
                }

				else {
					return 1;
                }

			}

			// ----- all other files are skipped
			else {
				return 0;
			}
		}
	}

	/*if (!function_exists('tvr_microloader_getOnlyValid')) {
		// check file types (microloader)
		function tvr_microloader_getOnlyValid($p_event, &$p_header) {
			// avoid null byte hack (THX to Dominic Szablewski)
			if ( strpos($p_header['filename'], chr(0) ) !== false )
				$p_header['filename'] = substr ( $p_header['filename'], 0, strpos($p_header['filename'], chr(0) ));
			$info = pathinfo($p_header['filename']);
			// check for extension
			$ext = array('jpeg', 'jpg', 'png', 'gif', 'txt', 'json', 'psd', 'ai');
			if ( in_array( strtolower($info['extension']), $ext) ) {
				// For MAC skip the ".image" files
				if ($info['basename']{0} == '.' )
					return 0;
				else
					return 1;
			}
			// ----- all other files are skipped
			else {
				return 0;
			}
		}
	}
	*/
	// PCLZIP_CB_POST_EXTRACT is an option too: http://www.phpconcept.net/pclzip/user-guide/49

	/***
	INSTANTIATE THE ADMIN CLASS
	 ***/
	if (class_exists('tvr_microthemer_admin')) {
		$tvr_microthemer_admin_var = new tvr_microthemer_admin();
	}

} // ends 'if is_admin()' condition

// frontend code - insert active-styles.css in head section if active
if (!is_admin()) {

    // no need to run MT frontend script on Oxygen intermediate iframe
    // only one the actual site preview
    if (isset($_GET['ct_builder']) && !isset($_GET['oxygen_iframe'])){
        return false;
    }

	// admin class
	if (!class_exists('tvr_microthemer_frontend')) {
		// define
		class tvr_microthemer_frontend {

			// @var string The preferences string name for this plugin
			var $time = 0;
			var $preferencesName = 'preferences_themer_loader';
			// @var array $preferences Stores the ui options for this plugin
			var $preferences = array();
			var $version = '6.3.7.0';
			var $microthemeruipage = 'tvr-microthemer.php';
			var $file_stub = '';
			var $min_stub = '';
			var $num_save_append = '';
			var $menu_item_counts = array(); // for adding first/last classes to menus
			var $menu_item_count = 0;


			/**
			 * PHP 5+ Constructor
			 */

			function __construct(){

				$this->time = time();

				// check that styles are active
				$this->preferences = get_option($this->preferencesName);

				// translatable: apparently one of the commented methods below is correct, but they don't work for me.
				// http://geertdedeckere.be/article/loading-wordpress-language-files-the-right-way
				// JOSE: $this->propertyoptions doesn't get translated if we use init
				load_plugin_textdomain( 'microthemer', false, dirname( plugin_basename(__FILE__) ) . '/languages/' );
				//add_action('init', array($this, 'tvr_load_textdomain'));

				// get path variables
				include dirname(__FILE__) .'/get-dir-paths.inc.php';

				// get custom code var
				$this->custom_code = tvr_common::get_custom_code();

				// default WP action order
				$action_order = 999999;
				$action_hook = 'wp_enqueue_scripts';

				// logged out page view mode
				add_action('init',  array(&$this, 'mt_nonlog'), $action_order);

				// earliest action hook $post object is set, useful for redirects
				add_action('wp',  array(&$this, 'mt_redirect'), $action_order);

				// get draft, minify, and num saves
				add_action( 'plugins_loaded', array(&$this, 'init_mt_vars'));

				// add active-styles.css (if not preview)
				if (!isset($_GET['tvr_micro'])) {

				    // note changed from wp_print_styles on Feb 22nd 2018 as discovered it is deprecated
					// the inactive code was using wp_enqueue_scripts, so now the will be consistent

                    // alternative order if user specifies stylesheet must come after Oxygen
                    if (!empty($this->preferences['after_oxy_css'])){
	                    $action_order = 11000000; // 11m
	                    $action_hook = 'wp_head';
                    }

                    // add css
                    add_action( $action_hook, array(&$this, 'add_css'), $action_order);

                    // add MT global stylesheet to login page unless specified otherwise
                    if (!empty($this->preferences['global_styles_on_login'])){
	                    add_action('login_enqueue_scripts', array(&$this, 'add_css'), $action_order);
                    }

				}

				// add shortcut to Microthemer
				if (!empty($this->preferences['admin_bar_shortcut'])) {
					add_action( 'admin_bar_menu', array(&$this, 'custom_toolbar_link'), 999999);
				}

				// add viewport = 1 if set in preferences
				if ($this->preferences['initial_scale'] == 1) {
					add_action('wp_head', array(&$this, 'viewport_meta') );
				}

				// add meta_tag if logged in - else undefined iframeUrl variable creates break error
				add_action( 'wp_head', array(&$this, 'add_frontend_data'));

				// also add to login page
				add_action( 'login_head', array(&$this, 'add_frontend_data'));

				// add <base> URL for when WP installs use a sub-directory
				/*if (!empty($this->preferences['relative_base_url'])) {
					add_action('wp_head', array(&$this, 'add_base_url_tag') );
				}*/

				// add frontend script
				add_action( 'wp_enqueue_scripts', array(&$this, 'add_js'), 999999);

				// add MT global stylesheet to login page unless specified otherwise
				add_action('login_enqueue_scripts', array(&$this, 'add_js'), 999999);

				// add mt body classes (page-id and slug)
				add_filter( 'body_class', array(&$this, 'add_body_classes') );

				// insert dynamic classes to menus if preferred todo this if fixed, tell forum user
				// https://themeover.com/forum/topic/microthemer-conflicting-with-plugin-responsive-menu/#post-9046
				if (!function_exists('add_first_and_last')) {
					if (!empty($this->preferences['first_and_last'])) {
						add_filter('nav_menu_css_class', array(&$this, 'add_first_and_last_classes'), 10, 3);
					}
				}

				// filter the HTML just before it's sent to the browser - no need for now.
				/*if (false) {
					add_action('get_header', array(&$this, 'tvr_head_buffer_start'));
					add_action('wp_head', array(&$this, 'tvr_head_buffer_end'));
				}*/

			} // end constructor

			/* remove parent style.css and replace with microthemer reset.css
			function tvr_head_buffer_callback($buffer) {
				// modify buffer here, and then return the updated code
				$buffer = str_replace(get_stylesheet_uri(), $this->thispluginurl.'css/frontend/reset.css', $buffer);
				return $buffer;
			}
			// start buffer
			function tvr_head_buffer_start() {
				ob_start(array(&$this, "tvr_head_buffer_callback"));
			}
			// end buffer
			function tvr_head_buffer_end() {
				ob_end_flush();
			}
			*/

			// non-logged in mode
			function mt_nonlog(){

				if (isset($_GET['mt_nonlog'])) {

					$nonce = !empty($_GET['_wpnonce']) ? $_GET['_wpnonce'] : false;
					if (current_user_can("administrator") and wp_verify_nonce( $nonce, 'mt_nonlog_check' ) ) {
						wp_set_current_user(-1);
					} else {
						die('Permission denied');
					}
				}
            }

            // perform redirect for e.g. Oxygen edit URL params for the current post
            // this is more performant than getting the edit links in the quick edit menu
            function mt_redirect(){

			    // redirect to Oxygen edit page
			    if ( isset($_GET['mto2_edit_link']) && function_exists('oxygen_add_posts_quick_action_link') ){

				    $nonce = !empty($_GET['_wpnonce']) ? $_GET['_wpnonce'] : false;
				    if ( current_user_can("administrator") and wp_verify_nonce( $nonce, 'mt_builder_redirect_check' ) ) {
				        global $post;

					    // try to get link
					    $edit_link = oxygen_add_posts_quick_action_link(array(), $post);

					    // we have a valid URL
					    if (!empty($edit_link['oxy_edit'])){

						    preg_match('/href="(.+?)"/', $edit_link['oxy_edit'], $matches);

                            if (!empty($matches[1])){

                                $edit_url = $matches[1];

	                            wp_redirect( esc_url($edit_url) );
                            }

					    }

					    else {
					        // warn that edit lock might be in place
                        }

				    } else {
					    die('Permission denied');
				    }



                }

            }

			function init_mt_vars(){
				// get_current_user_id() needs to be here (hooked function)
				$this->current_user_id = get_current_user_id();
				$this->file_stub = ($this->preferences['draft_mode'] and
				                    in_array($this->current_user_id, $this->preferences['draft_mode_uids'])) ? 'draft' : 'active';
				$this->min_stub = $this->preferences['minify_css'] ? 'min.': '';
				$num_saves = !empty($this->preferences['num_saves']) ? $this->preferences['num_saves'] : 0;
				$this->num_save_append = '?mts=' . $num_saves;
			}

			// add a link to the WP Toolbar
			function custom_toolbar_link($wp_admin_bar) {

				if (!current_user_can('administrator')){
					return false;
				}

				if (!empty($this->preferences['top_level_shortcut'])){
					$parent = false;
				} else {
					$parent = 'site-name';
				}

				$currentPageURL = tvr_common::strip_page_builder_and_other_params($this->currentPageURL());

				// strip Beaver Builder ?fl_builder param as users may not want BB to load most of the time
				/*$currentPageURL = tvr_common::strip_url_param($this->currentPageURL(), 'fl_builder', false);

				// strip Divi param which causes iframe to break out of parent
				$currentPageURL = tvr_common::strip_url_param($currentPageURL, 'et_fb', true); //temp disabled for debugging*/

				// MT admin page with front page param passed in for quick editing
				$href = $this->wp_blog_admin_url . 'admin.php?page=' . $this->microthemeruipage .
				        '&mt_preview_url=' . rawurlencode(esc_url($currentPageURL))
				        . '&_wpnonce=' . wp_create_nonce( 'mt-preview-nonce' );

				$args = array(
					'id' => 'wp-mcr-shortcut',
					'title' => 'Microthemer',
					'parent' => $parent,
					'href' => $href,
					'meta' => array(
						'class' => 'wp-mcr-shortcut',
						'title' => __('Jump to the Microthemer interface', 'microthemer')
					)
				);
				$wp_admin_bar->add_node($args);
			}

			// determine dependent style sheets - sometimes a theme includes style.css AFTER active-styles.css (e.g. classipress)
			function dep_stylesheets() {
				/*
				// redundant, but kept as an example in case this method is ever needed
				$cur_theme = strtolower(get_current_theme());
				switch ($cur_theme) {
					case "classipress":
						$deps = array('at-main', 'at-color');
						break;
					default:
						$deps = false;
				}
				return $deps;
				*/
				return false;
			}


			// add stylesheet function
			function add_css() {

				if ( !empty($this->preferences['active_theme']) ) {

					// if it's a preview don't cache the css file
					if (is_user_logged_in()) {
						$append = '?nomtcache=' . $this->time;
					} else {
						$append = $this->num_save_append;
					}

					// special case for loading CSS after Oxygen
					$add_styles = !empty($this->preferences['after_oxy_css']);

					// check if Google Fonts stylesheet needs to be called
					if (!empty($this->preferences['g_fonts_used'])) {
						tvr_common::add_user_google_fonts($this->preferences);
					}

					// add active-styles
					$url = $this->micro_root_url. $this->min_stub . $this->file_stub .'-styles.css';
					$formatted_url = $url . $append;
					$mt_handle = 'microthemer';
					tvr_common::mt_enqueue_or_add($add_styles, $mt_handle, $formatted_url);

					// check if ie-specific stylesheets need to be called
					global $is_IE;
					if ( $is_IE ) {

						foreach ($this->preferences['ie_css'] as $key => $cond){

							if (!empty($this->preferences['ie_css'][$key])) {
								$file_stub = ($this->file_stub == 'draft') ? $this->file_stub.'-' : '';
								$path = $this->micro_root_url.$file_stub.'ie-'.$key.'.css'.$append;
								$ie_handle = 'tvr_ie_'.$key;
								tvr_common::mt_enqueue_or_add(true, $ie_handle, $path, 'conditional', $cond);
							}
						}
					}

					// UI frontend CSS
					if (is_user_logged_in() || isset($_GET['mt_nonlog'])) {
						$min = !TVR_DEV_MODE ? '.min' : '';
						$ui_frontend_handle = 'micro'.TVR_MICRO_VARIANT.'-overlay-css';
						$ui_frontend_url = $this->thispluginurl.'css/frontend'.$min.'.css?v='.$this->version;
						tvr_common::mt_enqueue_or_add($add_styles, $ui_frontend_handle, $ui_frontend_url);
					}

				}

			}

			// get the current page for iframe-meta and loading WP page after clicking WP admin MT option
			function currentPageURL() {

			    return tvr_common::get_protocol() . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

			    /*$isSSL = (!empty($_SERVER["HTTPS"]) and $_SERVER["HTTPS"] == "on");

				// new system of getting page URL - didn't work with wp-login.php
				global $wp;
				$curUrl = add_query_arg(
					$_SERVER['QUERY_STRING'],
					'',
					trailingslashit( home_url($wp->request) )
				);

				// home_url() should get correct protocol, but just to be sure
				if ($isSSL) {
					$curUrl = str_replace('http:', 'https:', $curUrl);
				}

				return $curUrl;*/
			}

			// add viewport intial scale = 1
			function viewport_meta() {
				?>
                <!-- Microthemer viewport setting -->
                <meta name="viewport" content="width=device-width, initial-scale=1"/>
				<?php
			}

			// add meta iframe-url tracker (for remembering the preview page)
			function add_frontend_data($any) {

			    if ( is_user_logged_in() || isset($_GET['mt_nonlog']) ) {

			        global $wp;

				    $MTDynFrontData = array(
					    'iframe-url' => rawurlencode(
						    esc_url(
						      tvr_common::strip_page_builder_and_other_params($this->currentPageURL()),
                              null,
                              'read'
                            )
                        ),
					    'mt-show-admin-bar' => intval($this->preferences['admin_bar_preview']),
                        'page-title' => get_the_title()
				    );

				    // get Oxygen page width
                    if ( function_exists('oxygen_vsb_get_page_width') ){

	                   /* Maybe come back to this is issues using oxygen toolbar link for URL to load
	                   $template_id = function_exists('oxygen_vsb_get_page_width')
		                    ? oxygen_vsb_get_page_width()
		                    : null;
	                    $template_posts = function_exists('ct_get_templates_post')
		                    ? ct_get_templates_post($template_id)
		                    : null;*/

	                    $MTDynFrontData['oxygen'] = array(
		                    'page-width' => intval( oxygen_vsb_get_page_width() ),
                           /* 'template_id' => $template_id,
                            'template_posts' => $template_posts,*/
                        );
                    }

				    ?>
                    <script> window.MTDynFrontData = <?php echo json_encode( $MTDynFrontData ); ?>; </script>
                    <?php
				}
			}


			// add meta iframe-url tracker (for remembering the preview page)
			/*function add_base_url_tag() {
			    echo '<base href="'.esc_attr($this->preferences['relative_base_url']).'">';
			}*/

			// add firebug style overlay js if user is logged in
			function add_js() {

				$frontendJS_deps = array('jquery');
				$min = !TVR_DEV_MODE ? '-min' : '/page';
				// todo maybe make frontend.js dep (if looged in) so MT can catch errors
				$in_footer = !empty($this->preferences['active_scripts_footer']);

				// if the user has used MTs animation events feature, include JS file
				if (!empty($this->preferences['active_events'])){
					wp_enqueue_script('mt_animation_events',
						$this->thispluginurl . 'js'.$min.'/animation-events.js?v='.$this->version,
						array('jquery')
					);
					wp_localize_script( 'mt_animation_events', 'MT_Events_Data',
						json_decode($this->preferences['active_events']) );

					$frontendJS_deps = array('jquery');

				}

				if ( is_user_logged_in() || isset($_GET['mt_nonlog'])) {
					// testing only - swap default jQuery with 2.x for future proofing
					/*
					$jq2 = false;
					if ($jq2){
						wp_deregister_script('jquery');
						wp_register_script('jquery', ($this->thispluginurl.'js/jq2.js'));
					}*/

					wp_enqueue_script( 'jquery' );
					wp_register_script( 'tvr_mcth_frontend',
						$this->thispluginurl.'js'.$min.'/frontend.js?v='.$this->version, $frontendJS_deps );
					wp_enqueue_script( 'tvr_mcth_frontend' );
				}

				// enqueue any native wp libraries the user has specified
				$deps = !empty($this->preferences['active_scripts_deps'])
					? preg_split("/[\s,]+/", $this->preferences['active_scripts_deps'])
					: array();
				if (!empty($this->preferences['enq_js']) and is_array($this->preferences['enq_js'])){
					foreach ($this->preferences['enq_js'] as $k => $arr){
						if (empty($arr['disabled'])){
							wp_enqueue_script($arr['display_name']);
							$deps[] = $arr['display_name'];
						}
					}
				}

				// enqueue user custom js if needed
				if (!empty($this->preferences['load_js'])) {
					// add minification support soon
					$path = $this->micro_root_url . $this->file_stub . '-scripts.js' . $this->num_save_append;
					wp_register_script('mt_user_js', $path);
					wp_enqueue_script('mt_user_js', false, $deps, $this->num_save_append, $in_footer);
				}
			}

			// add page/post id for easy page targeting (and slug as ids change on development)
			function add_body_classes($classes){
				global $post;
				if ( isset( $post ) ) {
					$classes[] = 'mt-'.$post->ID;
					$classes[] = 'mt-'.$post->post_type.'-'.$post->post_name;
				}
				return $classes;
			}

			// add first and last classes to menus
			function add_first_and_last_classes( $classes, $item, $args ) {

                //wp_die('The menu stuff: '. '<pre>'.print_r($args->menu).'</pre>');

				// store menu item count if not done already
				if (empty($this->menu_item_counts[ $args->menu->slug ])){
					$this->menu_item_counts[ $args->menu->slug ] = $args->menu->count;
					$this->menu_item_count = 0;
				}

				// add first or last item
				if ( $this->menu_item_count === 0 ) {
					$classes[] = 'menu-item-first';
				} else if ( $this->menu_item_count === $this->menu_item_counts[ $args->menu->slug ]-1 ) {
					$classes[] = 'menu-item-last';
				}

				$this->menu_item_count++;

				//echo '<pre>$args: '.print_r($args, true).'</pre>';
				//echo '<pre>$item: '.print_r($item, true).'</pre>';
				return $classes;
			}

			/*function add_first_and_last( $items ) {
				$position = strrpos($items, 'class="menu-item', -1);
				$items=substr_replace($items, 'menu-item-last ', $position+7, 0);
				$position = strpos($items, 'class="menu-item');
				$items=substr_replace($items, 'menu-item-first ', $position+7, 0);
				return $items;
			}*/

		} // end class
	} // end 'if(!class_exists)'

	// instantiate the frontend class
	if (class_exists('tvr_microthemer_frontend')) {
		$tvr_microthemer_frontend_var = new tvr_microthemer_frontend();
	}

} // end 'is_admin()'

?>
