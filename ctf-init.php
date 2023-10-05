<?php
/**
 * Plugin Name: CodeCanyon to Freemius - License Converter
 * Description: Convert legacy CodeCanyon license codes to Freemius license codes and emails them to the users.
 * Text Domain: np-ctf
 * Version: 0.0.5
 * Author: Arindo Duque, Marcelo Assis - NextPress
 * Author URI: http://nextpress.co/
 * Copyright: NextPress
 * GitHub Plugin URI: https://github.com/next-press/codecanyon-to-freemius
 *
 * License: GPL3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 *
 * @author   Marcelo Assis, Arindo Duque - NextPress
 * @category Core
 * @package  Codecanyon_To_Freemius
 * @version  0.0.1
 */

if (!defined('ABSPATH')) {

	exit; // Exit if accessed directly.

} // end if;

if (!class_exists('Codecanyon_To_Freemius')) :

	/**
	 * Here starts our plugin.
	 */
	class Codecanyon_To_Freemius {

		/**
		 * Version of the Plugin
		 *
		 * @var string
		 */
		public $version = '0.0.1';

		/**
		 * WP_Error messages
		 *
		 * @var WP_Error
		 */
		public $messages;

		/**
		 * Makes sure we are only using one instance of the plugin
		 *
		 * @var object Codecanyon_To_Freemius
		 */
		public static $instance;

		/**
		 * Returns the instance of Codecanyon_To_Freemius
		 *
		 * @return object A Codecanyon_To_Freemius instance
		 */
		public static function get_instance() {

			if (null === self::$instance) {

				self::$instance = new self();

			} // end if;

			return self::$instance;

		} // end get_instance;

		/**
		 * Initializes the plugin
		 */
		public function __construct() {
			// Load the text domain
			load_plugin_textdomain('wu-ctf', false, dirname(plugin_basename(__FILE__)) . '/lang');

			/**
			 * Require Files
			 */
			require_once $this->path('inc/freemius/Freemius.php');
			require_once $this->path('inc/ctf-api.php');
			require_once $this->path('inc/class-ctf-logger.php');

			$this->messages = new WP_Error();

			// Run Forest, run!
			$this->hooks();

		}  // end __construct;

		/**
		 * Return url to some plugin subdirectory
		 *
		 * @since 0.0.1
		 *
		 * @param string $dir Directory.
		 *
		 * @return string Url to passed path
		 */
		public function path($dir) {

			return plugin_dir_path(__FILE__) . '/' . $dir;

		} // end path;

		/**
		 * Return url to some plugin subdirectory
		 *
		 * @since 0.0.1
		 *
		 * @param string $dir Directory.
		 *
		 * @return string Url to passed path
		 */
		public function url($dir) {

			return plugin_dir_url(__FILE__) . '/' . $dir;

		} // end url;

		/**
		 * Return full URL relative to some file in assets
		 *
		 * @since 0.0.1
		 *
		 * @param string $asset Name asset.
		 * @param string $assets_dir Directory asset.
		 *
		 * @return string Full URL to path
		 */
		public function get_asset($asset, $assets_dir = 'img') {

			return $this->url("assets/$assets_dir/$asset");

		} // end get_asset;

		/**
		 * Render Views
		 *
		 * @param string $view View to be rendered.
		 * @param Array  $vars Variables to be made available on the view escope, via extract().
		 */
		public function render($view, $vars = false) {

			// Make passed variables available
			if (is_array($vars)) {

				extract($vars); // phpcs:ignore

			} // end if;

			// Load our view
			include $this->path("views/$view.php");

		} // end render;

		/**
		 * Add the hooks we need to make this work
		 */
		public function hooks() {

			add_shortcode('ctf_form', array($this, 'render_shortcode'));

			add_action('init', array($this, 'handle_form_submission'));

			add_action('ctf_success_message', array($this, 'render_success'));

		} // end hooks;

		/**
		 * Render view page calling on action ctf_success_message if form was submited successfully
		 *
		 * @since 0.0.1
		 *
		 * @param string $message Succesfully Message.
		 *
		 * @return void
		 */
		public function render_success($message) {

			if (isset($_REQUEST['ctf_success'])) {

				$this->render('success', array(
					'message' => $message
				));

			} // end if;

		} // end render_success;

		/**
		 * Function render converter CodeCanyon to Freemius
		 *
		 * @since 0.0.1
		 *
		 * @param array $atts Attributes of shortcode.
		 *
		 * @return string
		 */
		public function render_shortcode($atts) {

			$atts = shortcode_atts(array(
				'id'                   => '1',
				'form_email_label'     => __('Enter your email address', 'np-ctf'),
				'form_license_label'   => __('Enter your license key from CodeCanyon', 'np-ctf'),
				'form_button_label'    => __('Convert License', 'np-ctf'),
				'form_success_message' => __('Confirmation email was successfully sent with your new access credentials and license key!', 'np-ctf'),
			), $atts, 'ctf_form');

			ob_start();

			$this->render('main', array(
				'atts' => $atts,
			));

			return ob_get_clean();

		}  // end render_shortcode;

		/**
		 * Validate fields from action submit form
		 *
		 * @since 0.0.1
		 *
		 * @param array $data Fields form $_POST.
		 *
		 * @return boolean
		 */
		public function validation($data) {

			if (!isset($data['email']) || empty($data['email'])) {

				$this->messages->add('empty-email', __('You need to enter a valid email address.', 'np-ctf'));
				CTF_Logger::add('ctf_log_error', implode('<br>', $this->messages->get_error_messages()));
				return false;

			} // end if;

			if (!isset($data['license_key']) || empty($data['license_key'])) {

				$this->messages->add('empty-license', __('You need to enter a valid license key.', 'np-ctf'));
				CTF_Logger::add('ctf_log_error', implode('<br>', $this->messages->get_error_messages()));
				return false;

			} // end if;

			if ($this->is_license_key_already_used($data['license_key'])) {

				$this->messages->add('used-license', __('This license key has already been used.', 'np-ctf'));
				CTF_Logger::add('ctf_log_error', implode('<br>', $this->messages->get_error_messages()));
				return false;

			} // end if;

			if (!isset($data['codecanyon_slug_plugin']) || !isset($data['codecanyon_api_key'])) {

				$this->messages->add('invalid-codecanyon-data', __('An error happened. Please contact support.', 'np-ctf'));
				CTF_Logger::add('ctf_log_error', implode('<br>', $this->messages->get_error_messages()));
				return false;

			} // end if;

			if (!isset($data['freemius_plugin_pk_apikey']) || !isset($data['freemius_plugin_sk_apikey']) || !isset($data['freemius_plugin_id']) ||
			!isset($data['freemius_plugin_plan_id']) || !isset($data['freemius_plugin_pricing_id']) || !isset($data['freemius_plugin_expires_at']) ) {

				$this->messages->add('invalid-freemius-plugin-data', __('An error happened. Please contact support.', 'np-ctf'));
				CTF_Logger::add('ctf_log_error', implode('<br>', $this->messages->get_error_messages()));
				return false;

			} // end if;

			if (!isset($data['freemius_dev_pk_apikey']) || !isset($data['freemius_dev_sk_apikey']) || !isset($data['freemius_dev_id']) ) {

				$this->messages->add('invalid-freemius-plugin-data', __('An error happened. Please contact support.', 'np-ctf'));
				CTF_Logger::add('ctf_log_error', implode('<br>', $this->messages->get_error_messages()));
				return false;

			} // end if;

			return true;

		} // end validation;

		/**
		 * Allow us to debug how the licenses are being fixed.
		 *
		 * @return boolean
		 * @since
		 */
		public function is_debug_mode() {

			return isset($_GET['debug']) && $_GET['debug'] === 'SECRET!!!!';

		} // end is_debug_mode;

		/**
		 * Check if license key already been used
		 *
		 * @since 0.0.1
		 *
		 * @param string $license_key License key.
		 *
		 * @return boolean
		 */
		public function is_license_key_already_used($license_key) {

			if ($this->is_debug_mode()) {

				return false;

			} // end if;

			$used_licenses = get_option('ctf_used_licenses');

			return is_array($used_licenses) ? in_array($license_key, $used_licenses) : false;

		} // end is_license_key_already_used;

		/**
		 * Get ctf constants defined in wp-config.php
		 *
		 * @since 0.0.1
		 *
		 * @return array
		 */
		public function get_constants_by_id() {

			return array(
				'freemius_dev_pk_apikey'     => defined("FS__DEV_PK_APIKEY") ? constant("FS__DEV_PK_APIKEY") : '',
				'freemius_dev_sk_apikey'     => defined("FS__DEV_SK_APIKEY") ? constant("FS__DEV_SK_APIKEY") : '',
				'freemius_dev_id'            => defined("FS__DEV_ID") ? constant("FS__DEV_ID") : '',

				'freemius_plugin_pk_apikey'  => defined("FS__PLUGIN_PK_APIKEY") ? constant("FS__PLUGIN_PK_APIKEY") : '',
				'freemius_plugin_sk_apikey'  => defined("FS__PLUGIN_SK_APIKEY") ? constant("FS__PLUGIN_SK_APIKEY") : '',
				'freemius_plugin_id'         => defined("FS__PLUGIN_ID") ? constant("FS__PLUGIN_ID") : '',
				'freemius_plugin_plan_id'    => defined("FS__PLUGIN_PLAN_ID") ? constant("FS__PLUGIN_PLAN_ID") : '',
				'freemius_plugin_pricing_id' => defined("FS__PLUGIN_PRICING_ID") ? constant("FS__PLUGIN_PRICING_ID") : '',
				'freemius_plugin_expires_at' => defined("FS__PLUGIN_EXPIRES_AT") ? constant("FS__PLUGIN_EXPIRES_AT") : '',

				'codecanyon_api_key'         => defined("CODECANYON_API_KEY") ? constant("CODECANYON_API_KEY") : '',
				'codecanyon_slug_plugin'     => defined("CODECANYON_SLUG_PLUGIN") ? constant("CODECANYON_SLUG_PLUGIN") : '',
			);

		} // end get_constants_by_id;

		/**
		 * Handle of form submit
		 *
		 * @since 0.0.1
		 *
		 * @return mixed
		 */
		public function handle_form_submission() {

			if (!isset($_REQUEST['action']) || $_REQUEST['action'] !== 'np-ctf') {
				return;
			} // end if;

			if (!wp_verify_nonce($_REQUEST['_wpnonce'], 'np-ctf-nonce')) {
				return;
			} // end if;

			$data = array(
				'license_key' => $_POST['license_key'],
				'email'       => $_POST['email'],
			);

			$data = array_merge($data, $this->get_constants_by_id());

			if (!$this->validation($data)) {
				return;
			} // end if;

			$ctf_api = new CTF_Api($data);

			$license = $ctf_api->verify_envato_purchase_code($data['license_key']);

			if ($this->is_debug_mode()) {


			} // end if;

			if (!$license || $license->success == false) {

				$this->messages->add('invalid-license', __('This license key is invalid.', 'np-ctf'));
				CTF_Logger::add('ctf_log_error', implode('<br>', $this->messages->get_error_messages()));
				return;

			} // end if;

			$existing_user = $ctf_api->verify_freemius_exists_user($data['email']);

			if (!$existing_user) {

				$user = $ctf_api->create_freemius_user($data['email']);

			} else {

				$user = $existing_user;

			} // end if;
			
			$existing_license = $ctf_api->get_licences_by_user_id($user->id);

			// check user has freemius license exists
			if (is_null($existing_license)) {

				$license = $ctf_api->create_freemius_license($user->email);

			} else {

				$this->messages->add('existing-license', sprintf(__('This user email: %s was already converted.', 'np-ctf'), $user->email));
				CTF_Logger::add('ctf_log_error', implode('<br>', $this->messages->get_error_messages()));
				return;

			} // end if;

			$this->add_new_used_license_to_blog_option($data['license_key']);

			CTF_Logger::add('ctf_log_success', 'Email: ' . $user->email . ' CodeCanyon License Key: ' . $data['license_key'] . ' New Freemius License Key: ' . $license->secret_key);

			$url = get_permalink();

			wp_redirect($url . '?ctf_success=1');

			die;

		} // end handle_form_submission;

		/**
		 * Add a licence key to array of used licenses
		 *
		 * @param string $licence_key License key.
		 * @return void
		 */
		public function add_new_used_license_to_blog_option($licence_key) {

			if ($this->is_debug_mode()) {

				return false;

			} // end if;

			$used_licenses = get_option('ctf_used_licenses');

			if (!is_array($used_licenses)) {

				$used_licenses = array();

			} // end if;

			$used_licenses[] = $licence_key;

			update_option('ctf_used_licenses', $used_licenses);

		} // end add_new_used_license_to_blog_option;

	}  // end class Codecanyon_To_Freemius;

	/**
	 * Initialize the Plugin
	 */
	add_action('plugins_loaded', 'wu_ctf_init', 1);

	/**
	 * Initializes the plugin
	 *
	 * @since 0.0.1
	 *
	 * @return mixed
	 */
	function wu_ctf_init() {

		// Set global
		$GLOBALS['Codecanyon_To_Freemius'] = Codecanyon_To_Freemius::get_instance();

	} // end wu_ctf_init;

endif;
