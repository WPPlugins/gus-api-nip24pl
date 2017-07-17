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

/**
 * Fetch invoice data and fill form
 * @param force false - get current data, true - force refresh
 */
function nip24GetInvoiceData(force)
{
	// validate
	var nip = jQuery('#billing_company_vat_id').val();
	
	jQuery.post(nip24var.ajaxurl, {
			'action': 'nip24_check_vatid',
			'nip': nip
		},
		function(res) {
			if (res.result != 'OK') {
				jQuery('#woocommerce_nip24_error_msg').show();
				jQuery('#woocommerce_nip24_search_link').hide();

				return;
			}

			// valid, fetch
			jQuery('body').css('cursor', 'wait');
			
			jQuery.post(nip24var.ajaxurl, {
					'action': 'nip24_invoice_data',
					'nip': nip,
					'force': force
				},
				function(data) {
					jQuery.each(data, function(id, value) {
						if (jQuery("#" + id).length > 0) {
							jQuery('#' + id).val(value);
						}
					});
	
					jQuery('body').css('cursor', 'default');
					
					jQuery('#woocommerce_nip24_error_msg').hide();
					jQuery('#woocommerce_nip24_search_link').show();
				},
				'json'
			);	
		},
		'json'
	);	
}
