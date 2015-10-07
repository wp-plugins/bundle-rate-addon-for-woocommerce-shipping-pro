<?php
/*
	Plugin Name: Bundle Rate AddOn For WooCommerce Shipping Pro  
	Plugin URI: http://www.wooforce.com
	Description: AddOn plugin for WooCommerce Shipping Pro. Designed to handle complex bundling. Can configure Free shipping for certain shipping class if another set of shipping class available in the cart.
	Version: 1.0.1
	Author: WooForce
	Author URI: http://www.wooforce.com
	Copyright: 2014-2015 WooForce.
	*/

class wf_bundle_rate_addon_setup {
	public function __construct() {
		add_action( 'woocommerce_init', array( $this, 'wf_woocommerce_init' ));
		add_filter( 'wf_woocommerce_shipping_pro_shipping_costs',  array( $this, 'wf_woocommerce_shipping_pro_shipping_costs_method') );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );			
	}
	
	public function wf_woocommerce_init(){
		include 'class-wf-settings-shipping-pro.php';	
	}
	
	public function wf_woocommerce_shipping_pro_shipping_costs_method( $costs ) {
		$wf_add_on_enabled = get_option( 'wf_add_on_enabled' );
		
		if(empty($wf_add_on_enabled) || $wf_add_on_enabled == 'no') 
			return $costs; 
		
		$wf_add_on_matrix = get_option( 'wf_add_on_matrix' );
		if(empty($wf_add_on_matrix)) 
			return $costs; 
		
		$shipping_pro_settings = get_option( 'woocommerce_wf_woocommerce_shipping_pro_settings' );
		$allowed_calculation_mode = array('per_category_max_cost','per_category_min_cost','per_shipping_class_max_cost','per_shipping_class_min_cost');
		if(empty($shipping_pro_settings) || !isset($shipping_pro_settings['calculation_mode']) || !in_array($shipping_pro_settings['calculation_mode'],$allowed_calculation_mode))
			return $costs;
		
		$calc_mode = $shipping_pro_settings['calculation_mode'];
		
		$shippingcass_mapping = array();
		$shippingclass_costs = array();
		if(!empty($wf_add_on_matrix)){
			$key_mode = '';
			$value_mode = '';
			if($calc_mode === 'per_category_max_cost' || $calc_mode === 'per_category_min_cost'){
				$key_mode = 'set_for_product_category';
				$value_mode = 'if_exists_product_category';		
			}elseif($calc_mode === 'per_shipping_class_max_cost' || $calc_mode === 'per_shipping_class_min_cost'){
				$key_mode = 'set_for_shipping_class';
				$value_mode = 'if_exists_shipping_class';		
			}			
			foreach($wf_add_on_matrix as $matrix_value){
				if(!empty($matrix_value) && isset($matrix_value[$key_mode]) && isset($matrix_value[$value_mode]) && isset($matrix_value['cost'])){
					$shippingcass_mapping[$matrix_value[$key_mode]]  = $matrix_value[$value_mode];
					$shippingclass_costs[$matrix_value[$key_mode]] = $matrix_value['cost'];
				}
			}
		}
		
		if(empty($shippingcass_mapping) || empty($costs))
			return $costs ;
		
		foreach ($costs as $method_group => $method_cost) {
				if(isset($method_cost['shipping_name']) && isset($method_cost['cost'])){
					foreach($method_cost['cost'] as $shipping_group => $shipping_cost){
						if(array_key_exists($shipping_group,$shippingcass_mapping) && !empty( $shippingcass_mapping[ $shipping_group ] )){
							if($this->wf_woocommerce_shipping_pro_eligibile_to_remove($shippingcass_mapping[ $shipping_group ],$method_cost['cost'])){
								$costs[$method_group]['cost'][$shipping_group] = $shippingclass_costs[$shipping_group];
							}
						}
					}					
				}												
			}
		return $costs;
	}

	private function wf_woocommerce_shipping_pro_eligibile_to_remove($groups_to_be_checked, $verify_in_this_list){
		foreach($groups_to_be_checked as $to_be_checked){
			if (array_key_exists($to_be_checked , $verify_in_this_list)) return true;			
		}
		return false;
	}	
	public function plugin_action_links( $links ) {
			$plugin_links = array(
				'<a href="' . admin_url( 'admin.php?page=' . wf_get_settings_url() . '&tab=wf_shipping_pro_addon' ) . '">' . __( 'Settings', 'wf_shipping_pro_addon' ) . '</a>',
				'<a href="http://www.wooforce.com/pages/contact/">' . __( 'Support', 'wf_shipping_pro_addon' ) . '</a>',
			);
			return array_merge( $plugin_links, $links );
		}	
		
	private function wf_get_settings_url()
	{
		return version_compare(WC()->version, '2.1', '>=') ? "wc-settings" : "woocommerce_settings";
	}
}

if (!function_exists('wf_get_settings_url')){
	function wf_get_settings_url(){
		return version_compare(WC()->version, '2.1', '>=') ? "wc-settings" : "woocommerce_settings";
	}
}
	
new wf_bundle_rate_addon_setup();
