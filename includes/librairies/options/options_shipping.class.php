<?php

/*	Check if file is include. No direct access possible with file url	*/
if ( !defined( 'WPSHOP_VERSION' ) ) {
	die( __('Access is not allowed by this way', 'wpshop') );
}

/**
* Shipping options management
*
* Define the different method to manage the different shipping options
* @author Eoxia <dev@eoxia.com>
* @version 1.0
* @package wpshop
* @subpackage librairies
*/

/**
* Define the different method to manage the different shipping options
* @package wpshop
* @subpackage librairies
*/
class wpshop_shipping_options {
	/**
	*
	*/
	function declare_options(){
		
		add_settings_section('wpshop_shipping_rules', __('Shipping general configuration', 'wpshop'), array('wpshop_shipping_options', 'plugin_section_text'), 'wpshop_shipping_rules');
		register_setting('wpshop_options', 'wpshop_shipping_address_choice', array('wpshop_shipping_options', 'wpshop_shipping_address_validator'));
		add_settings_field('wpshop_shipping_address_choice', __('Shipping address choice', 'wpshop'), array('wpshop_shipping_options', 'wpshop_shipping_address_field'), 'wpshop_shipping_rules', 'wpshop_shipping_rules');
		
	}

	// Common section description
	function plugin_section_text() {
		echo '';
	}

	function wpshop_shipping_address_validator($input){

		return $input;
	}

	function wpshop_shipping_address_field() {
		global $wpdb;
		$choice = get_option('wpshop_shipping_address_choice', unserialize(WPSHOP_SHOP_CUSTOM_SHIPPING));
		$query = $wpdb->prepare('SELECT ID FROM ' .$wpdb->posts. ' WHERE post_name = "' .WPSHOP_NEWTYPE_IDENTIFIER_ADDRESS. '" AND post_type = "' .WPSHOP_NEWTYPE_IDENTIFIER_ENTITIES. '"', '');
		$entity_id = $wpdb->get_var($query);

		$query = $wpdb->prepare('SELECT * FROM ' .WPSHOP_DBT_ATTRIBUTE_SET. ' WHERE entity_id = ' .$entity_id. '', '');
		$content = $wpdb->get_results($query);

		$input_def['name'] = 'wpshop_shipping_address_choice[choice]';
		$input_def['id'] = 'wpshop_shipping_address_choice[choice]';
		$input_def['possible_value'] = $content;
		$input_def['type'] = 'select';
		$input_def['value'] = $choice['choice'];

		$active = !empty($choice['activate']) ? $choice['activate'] : false;

		echo '<input type="checkbox" name="wpshop_shipping_address_choice[activate]" id="wpshop_shipping_address_choice[activate]" '.($active ? 'checked="checked"' :null).'/> <label for="active_shipping_address">'.__('Activate shipping address','wpshop').'</label></br/>
		<div">' .wpshop_form::check_input_type($input_def). '</div>';

	}
}































