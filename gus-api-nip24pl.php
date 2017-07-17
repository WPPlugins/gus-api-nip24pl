<?php
/**
 * Plugin Name: NIP24 for WooCommerce
 * Plugin URI: http://www.nip24.pl
 * Description: Plugin that integrates WooCommerce with NIP24 Service.
 * Version: 1.2.8
 * Author: nip24.pl
 * Author URI: http://www.nip24.pl
 * 
 * Requires at least: 4.1
 * Tested up to: 4.7.3
 *
 * Text Domain: gus-api-nip24pl
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
	// exit if accessed directly
	exit;
}

if (!class_exists('NIP24WooCommerce')):

/**
 * Main NIP24 integration class
 * @author netcat.pl
 */
final class NIP24WooCommerce {

	public static $VERSION = '1.2.8';
	
	private static $instance = null;
	
	private $nip24;
	
	/**
	 * Get instance of this class
	 * @return plugin object
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
	/**
	 * Constructor
	 */
	private function __construct()
	{
		$this->addAPIs();
		$this->addHooks();
		
		// create new client object
		$this->nip24 = new \NIP24\NIP24Client(get_option('woocommerce_nip24_option_keyid', ''),
			get_option('woocommerce_nip24_option_key', ''));
		
		$this->nip24->setApp('WordPress/' . get_bloginfo('version') . ' WooCommerce/'
			. get_option('woocommerce_version', ''));
		
		// url
		$url = get_option('woocommerce_nip24_option_url', \NIP24\NIP24Client::DEFAULT_URL);
		
		if ($url != \NIP24\NIP24Client::DEFAULT_URL && strlen($url) > 0) {
			$this->nip24->setURL($url);
		}
	}
	
	/**
	 * Import all required APIs
	 */
	private function addAPIs()
	{
		// import NIP24 api
		require_once dirname(__FILE__).'/NIP24/NIP24Client.php';
		
		\NIP24\NIP24Client::registerAutoloader();
	}
	
	/**
	 * Add all required actions and filters hooks
	 */
	private function addHooks()
	{
		add_action('plugins_loaded', array($this, 'pluginsLoadedAction'));
		add_action('admin_enqueue_scripts', array($this, 'adminEnqueueScriptsAction'));
		add_action('wp_enqueue_scripts', array($this, 'enqueueScriptsAction'));
		
		add_action('wp_ajax_nip24_check_vatid', array($this, 'ajaxNip24CheckVatIdAction'));
		add_action('wp_ajax_nopriv_nip24_check_vatid', array($this, 'ajaxNip24CheckVatIdAction'));
		
		add_action('wp_ajax_nip24_invoice_data', array($this, 'ajaxNip24InvoiceDataAction'));
		add_action('wp_ajax_nopriv_nip24_invoice_data', array($this, 'ajaxNip24InvoiceDataAction'));
		
		add_action('woocommerce_before_checkout_billing_form', array($this, 'beforeCheckoutBillingFormAction'));
		add_action('woocommerce_checkout_process', array($this, 'checkoutProcessAction'));
		add_action('woocommerce_checkout_update_order_meta', array($this, 'checkoutUpdateOrderMetaAction'));
		add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'adminOrderDataAction'));
		
		add_filter('woocommerce_checkout_fields', array($this, 'checkoutFieldsFilter'));
		add_filter('woocommerce_general_settings', array($this, 'generalSettingsFilter'));
	}
	
	/**
	 * Load plugin textdomain
	 */
	public function pluginsLoadedAction()
	{
		$locale = apply_filters('plugin_locale', get_locale(), 'gus-api-nip24pl');
		
		load_textdomain('gus-api-nip24pl', WP_LANG_DIR . '/gus-api-nip24pl/gus-api-nip24pl-' . $locale . '.mo');
		load_plugin_textdomain('gus-api-nip24pl', false, dirname(plugin_basename(__FILE__)) . '/languages');
	}
	
	/**
	 * Add script includes for back office
	 * @param unknown $hook
	 */
	public function adminEnqueueScriptsAction($hook)
	{
		if ($hook != 'post.php') {
			return;
		}
		
		wp_enqueue_style('gus-api-nip24pl', plugins_url('assets/css/gus-api-nip24pl.css', __FILE__));
	}
	
	/**
	 * Add script includes for frontend
	 */
	public function enqueueScriptsAction()
	{
		wp_enqueue_script('gus-api-nip24pl', plugins_url('assets/js/gus-api-nip24pl.js', __FILE__));
		
		wp_localize_script('gus-api-nip24pl', 'nip24var', array(
			'ajaxurl'	=> admin_url('admin-ajax.php')
		));
		
		wp_enqueue_style('gus-api-nip24pl', plugins_url('assets/css/gus-api-nip24pl.css', __FILE__));
	}
	
	/**
	 * Action for checking VAT ID value
	 * @return json response with verification result
	 */
	public function ajaxNip24CheckVatIdAction()
	{
		$res = array();
		
		if (\NIP24\NIP::isValid($_POST['nip'])) {
			$res['result'] = 'OK';
		}
		else {
			$res['result'] = 'ERR';
		}
		
		wp_send_json($res);
	}
	
	/**
	 * Action for fetching invoice data
	 * @return json response with invoice data
	 */
	public function ajaxNip24InvoiceDataAction()
	{
		// get data
		$data = $this->nip24->getInvoiceDataExt(\NIP24\Number::NIP, $_POST['nip'], filter_var($_POST['force'], FILTER_VALIDATE_BOOLEAN));
		
		if (!$data) {
			$res = array();
		}
		else {
			// TODO: make this map configurable at back office
			$res = array(
				'billing_company_vat_id'	=> $data->nip,
				'billing_company' 			=> $data->name,
				'billing_first_name'		=> $data->firstname,
				'billing_last_name'			=> $data->lastname,
				'billing_phone'				=> $data->phone,
				'billing_email'				=> $data->email,
				'billing_address_1'			=> $data->street . " " . $data->streetNumber,
				'billing_address_2'			=> $data->houseNumber,
				'billing_postcode'			=> $data->postCode,
				'billing_city'				=> $data->postCity
			);
		}

		wp_send_json($res);
	}
	
	/**
	 * Action for checkout billing form modifications
	 * @param unknown $checkout
	 */
	public function beforeCheckoutBillingFormAction($checkout)
	{
		echo '<div id="woocommerce_nip24_checkout_block">';
		
		echo '<p>' . __('Enter VAT ID number and press <b>Fetch data</b> button.', 'gus-api-nip24pl') . '</p>';
		
		woocommerce_form_field('billing_company_vat_id', array(
			'type'				=> 'text',
			'label'				=> __('VAT ID', 'gus-api-nip24pl'),
			'class'				=> array('form-row-first'),
			'required'			=> false
			),
			$checkout->get_value('billing_company_vat_id')
		);
		
		echo '<p id="woocommerce_nip24_search_button_field" class="form-row form-row form-row-last">'
			. '<label for="woocommerce_nip24_search_button">&nbsp;</label>'
			. '<input id="woocommerce_nip24_search_button" class="button alt" type="button" value="'
			. __('Fetch data', 'gus-api-nip24pl') . '" onclick="nip24GetInvoiceData(false);"></input>'
			. '</p>';
		
		echo '<ul id="woocommerce_nip24_error_msg" class="woocommerce-error" style="display:none;float:left;width:100%"><li><strong>'
			. __('VAT ID', 'gus-api-nip24pl')
			. '</strong> '
			. __(' is invalid', 'gus-api-nip24pl')
			. '</li></ul>';
		
		echo '<p id="woocommerce_nip24_search_link" style="display:none;">'
			. __('Data outdated? <a href="javascript:nip24GetInvoiceData(true);">Click here!</a>',
			'gus-api-nip24pl') . '</p>';
		
		echo '</div>';
	}
	
	/**
	 * Filter for checkout fields customization
	 * @param unknown $fields
	 * @return array
	 */
	public function checkoutFieldsFilter($fields)
	{
		// reorder fields
		$order = array(
			'billing_company',
			'billing_first_name',
			'billing_last_name',
			'billing_email',
			'billing_phone',
			'billing_country',
			'billing_address_1',
			'billing_address_2',
			'billing_postcode',
			'billing_city',
			'billing_state',
		);
		
		foreach ($order as $field) {
			$ordered_fields[$field] = $fields['billing'][$field];
		}
		
		$fields['billing'] = $ordered_fields;

		return $fields;
	}
	
	/**
	 * Action for checkout process validation
	 */
	public function checkoutProcessAction()
	{
		// nip must be valid if specified
		$nip = $_POST['billing_company_vat_id'];
		
		if (isset($nip) && strlen($nip) > 0 && !\NIP24\NIP::isValid($nip)) {
			wc_add_notice(__('Specified company VAT ID number is invalid.'), 'error');
		}
	}
	
	/**
	 * Action for saving additional checkout fields
	 */
	public function checkoutUpdateOrderMetaAction($order_id)
	{
		$nip = $_POST['billing_company_vat_id'];
		
		if (!empty($nip)) {
			update_post_meta($order_id, 'billing_company_vat_id', sanitize_text_field(\NIP24\NIP::normalize($nip)));
			
			// update contact info
			$this->nip24->updateContactData($nip, $_POST['billing_phone'], $_POST['billing_email'], null);
		}
	}
	
	/**
	 * Action for adding additional fields to the order form
	 * @param unknown $order
	 */
	public function adminOrderDataAction($order)
	{
		$nip = get_post_meta($order->id, 'billing_company_vat_id', true);
		
		echo '<div class="woocommerce_nip24_order_block">';
		
		echo '<p><strong>' . __('VAT ID', 'gus-api-nip24pl') . ':</strong> '
			. $nip . '</p>';

		echo '<p><strong>' . __('Company status', 'gus-api-nip24pl') . ':</strong> '
			. ($this->nip24->isActive($nip) ? __('Active', 'gus-api-nip24pl')
			: __('Suspended or terminated', 'gus-api-nip24pl')) . '</p>';
		
		echo '</div>';
	}
	
	/**
	 * Filter for general settings tab
	 * @param unknown $settings
	 */
	public function generalSettingsFilter($settings)
	{
		$settings[] = array(
		    'id'	=> 'woocommerce_nip24_options',
		    'type'	=> 'title',
			'name'	=> __('NIP24 Service', 'gus-api-nip24pl'),
		    'desc'	=> __('The following options are required for integration with NIP24 service.', 'gus-api-nip24pl'),
		);
		
		$settings[] = array(
			'id'		=> 'woocommerce_nip24_option_url',
			'type'		=> 'text',
			'name'		=> __('Service address', 'gus-api-nip24pl'),
			'desc'		=> __('This sets the NIP24 Service URL address. Default is: https://www.nip24.pl/api.', 'gus-api-nip24pl'),
			'desc_tip'	=> true,
			'default'	=> \NIP24\NIP24Client::DEFAULT_URL
		);
		
		$settings[] = array(
			'id'		=> 'woocommerce_nip24_option_keyid',
			'type'		=> 'text',
			'name'		=> __('API Key identifier', 'gus-api-nip24pl'),
			'desc'		=> __('This sets the NIP24 API access key identifier.', 'gus-api-nip24pl'),
			'desc_tip'	=> true
		);
		
		$settings[] = array(
			'id'		=> 'woocommerce_nip24_option_key',
			'type'		=> 'text',
			'name'		=> __('API Key value', 'gus-api-nip24pl'),
			'desc'		=> __('This sets the NIP24 API access key value.', 'gus-api-nip24pl'),
			'desc_tip'	=> true
		);
		
		$settings[] = array(
			'type'	=> 'sectionend',
			'id'	=> 'general_options'
		);
		
		return $settings;
	}
}

endif;

/**
 * Shortcut to the main instance of this plugin
 * @return NIP24 plugin class
 */
function NIP24()
{
	return NIP24WooCommerce::getInstance();
}

/**
 * Enable plugin only if WooCommerce is installed and activated 
 */
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
	NIP24();
}

// EOF