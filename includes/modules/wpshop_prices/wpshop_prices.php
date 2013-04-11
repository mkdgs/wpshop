<?php
/**
 * Plugin Name: WP-Shop-prices
 * Plugin URI: http://www.eoxia.com/wpshop-simple-ecommerce-pour-wordpress/
 * Description: WpShop Prices
 * Version: 0.1
 * Author: Eoxia
 * Author URI: http://eoxia.com/
 */

/**
 * Marketing messages bootstrap file
 * @author Jérôme ALLEGRE - Eoxia dev team <dev@eoxia.com>
 * @version 0.1
 * @package includes
 * @subpackage modules
 *
 */
 
if ( !defined( 'WPSHOP_VERSION' ) ) {
	die( __("You are not allowed to use this service.", 'wp_easy_extends') );
}
if ( !class_exists("wpshop_prices") ) {
	class wpshop_prices {
		function __construct() {
			add_action('wsphop_options', array('wpshop_prices', 'declare_options'));
		}
		
		function declare_options () {
			register_setting('wpshop_options', 'wpshop_catalog_product_option', array('wpshop_prices', 'wpshop_options_validate_prices'));
			add_settings_field('wpshop_catalog_product_option_discount', __('Activate the discount on products', 'wpshop'), array('wpshop_prices', 'wpshop_activate_discount_prices_field'), 'wpshop_catalog_product_option', 'wpshop_catalog_product_section');
		}
		
		function wpshop_options_validate_prices($input) {
			global $wpdb;
			$query = $wpdb->prepare('SELECT * FROM ' .WPSHOP_DBT_ATTRIBUTE. ' WHERE code = %s OR code = %s OR  code = %s OR code = %s OR code = %s', 'discount_amount', 'discount_rate', 'special_price', 'special_from', 'special_to' );
			$discount_attributes_status = $wpdb->get_results($query);
			if ( !empty($discount_attributes_status) ) {
				foreach ( $discount_attributes_status as $discount_attribute_status ) {
					if ( !empty($input) && !empty($input['discount']) && $input['discount'] == 'on' ) {
						$update = $wpdb->prepare('UPDATE ' .WPSHOP_DBT_ATTRIBUTE. ' SET status = "valid" WHERE code = %s', $discount_attribute_status->code);
						$wpdb->query($update);
					}
					else {
						$update = $wpdb->prepare('UPDATE ' .WPSHOP_DBT_ATTRIBUTE. ' SET status = "notused" WHERE code = %s', $discount_attribute_status->code);
						$wpdb->query($update);
					}
				}
			}
			return $input;
		}
		
		function wpshop_activate_discount_prices_field() {
			$product_discount_option = get_option('wpshop_catalog_product_option');
			
			$output  = '<input type="checkbox" id="wpshop_catalog_product_option_discount" name="wpshop_catalog_product_option[discount]" ' .( (!empty($product_discount_option) && !empty($product_discount_option['discount'])) ? 'checked="checked"' : '' ). ' />';
			$output .= '<a class="wpshop_infobulle_marker" title="' .__('Activate the possibility to create discount on products', 'wpshop'). '" href="#">?</a>';
			echo $output;
		}
		
		/**
		 * Check the product price, return price
		 * @param unknown_type $product
		 */
		function check_product_price ( $product ) {
			global $wpdb;
			$prices = array();
			$price_ati = $price_et = $tva = 0;
			if ( !empty($product) ) {
				$product_meta = get_post_meta($product['product_id'], '_wpshop_variations_attribute_def', true);
				if ( !empty($product_meta) ) {
					$parent_product = wpshop_products::get_parent_variation( $product['product_id']);
					if ( !empty($parent_product) && !empty($parent_product['parent_post']) && !empty($parent_product['parent_post_meta']) ) {
						//parent informations
						$parent_post = $parent_product['parent_post'];
						$parent_post_meta = $parent_product['parent_post_meta'];
						// Check the options for the price of a variation
						$variation_post_meta = get_post_meta($product['product_id'], '_wpshop_product_metadata', true);
						$variation_options = get_post_meta($parent_post->ID, '_wpshop_variation_defining', true);
						
						if ( !empty($variation_options) && !empty($variation_options['options']) && !empty($variation_options['options']['price_behaviour']) ) {
							if ( $variation_options['options']['price_behaviour'][0] == 'addition') {
								$price_ati = $parent_post_meta['product_price'] + $variation_post_meta['product_price'];
								$price_et = $parent_post_meta['price_ht'] + $variation_post_meta['price_ht'];
								$tva_id = $parent_post_meta['tx_tva'];
							}
							else {
								$price_ati = $variation_post_meta['product_price'];
								$price_et = $variation_post_meta['price_ht'];
								$tva_id = $parent_post_meta['tx_tva'];
							}
						}
						elseif (  !empty($variation_options) && empty($variation_options['options']) && empty($variation_options['options']['price_behaviour']) )  {
							if ($variation_post_meta['product_price'] == 0 && $variation_post_meta['price_ht'] == 0) {
								$price_ati = $parent_post_meta['product_price'];
								$price_et = $parent_post_meta['price_ht'];
								$tva_id = $parent_post_meta['tx_tva'];
							}
							else {
								$price_ati = $variation_post_meta['product_price'];
								$price_et = $variation_post_meta['price_ht'];
								$tva_id = $parent_post_meta['tx_tva'];
							}
						}
						else {
							$price_ati = $variation_post_meta['product_price'];
							$price_et = $variation_post_meta['price_ht'];
							$tva_id = $parent_post_meta['tx_tva'];
						}
						
						
					}
				}
				else {
					// It's a product without variations
					$price_ati = $product[WPSHOP_PRODUCT_PRICE_TTC];
					$price_et = $product[WPSHOP_PRODUCT_PRICE_HT];
					$product_metadata = get_post_meta($product['product_id'], WPSHOP_PRODUCT_ATTRIBUTE_META_KEY, true);
					$tva_id = $product_metadata[WPSHOP_PRODUCT_PRICE_TAX];
				}
			}
			$query = $wpdb->prepare('SELECT value FROM ' .WPSHOP_DBT_ATTRIBUTE_VALUES_OPTIONS. ' WHERE id = %d', $tva_id);
			$tva_rate = $wpdb->get_var($query);
			
			if ( $price_ati == 0 &&  $price_et != 0) {
				$price_ati = $price_et * ( 1+($tva_rate/100) );
			}
			elseif ( $price_et == 0 && $price_ati != 0) {
				$price_et = $price_ati * ( 1-($tva_rate/100) );
			}
			
			$tva = $price_ati - $price_et;
			
			/** Discount Part */
			$calcul_discount = wpshop_prices::get_discount_amount($product['product_id'],$price_et);
			$discount_exist = false;
			$discount_ati_price = $discount_et_price = $discount_tva = 0;
			
			if ( !empty($calcul_discount) && !empty($calcul_discount[0]) && $calcul_discount[0]) {
				$discount_exist = $calcul_discount[0];
				$discount_et_price = $calcul_discount[1];
				$discount_ati_price = $discount_et_price * ( 1 + ($tva_rate/100) );
				$discount_tva = $discount_ati_price - $discount_et_price;
			}
			
			$prices = array('ati' => number_format((float)$price_ati, 2, '.', ''), 'et' => number_format((float)$price_et, 2, '.', ''), 'tva' => $tva, 'discount' => array( 'discount_exist' => $discount_exist,'discount_ati_price' => $discount_ati_price, 'discount_et_price' => $discount_et_price, 'discount_tva' => $discount_tva) );
			return $prices;
		}
		
		/**
		 * Allows to get the correct price for a product
		 *
		 * @param object $product An object with the product definition
		 * @param string $return_type The type the price have to be returned under
		 * @param string $output_type The current output type (mini | complete)
		 *
		 * @return boolean|string Boolean: If the product price is set for cart adding | String: An error message if the price is not well set OR The product price
		 */
		function get_product_price($product, $return_type, $output_type = '', $only_price = false) {
			global  $wpdb;
			$productCurrency = wpshop_tools::wpshop_get_currency();
			$wpshop_price_piloting_option = get_option('wpshop_shop_price_piloting');
			$tpl_component = array();
			$tpl_component['CROSSED_OUT_PRICE'] = '';
			$tpl_component['TAX_PILOTING'] = ( !empty($wpshop_price_piloting_option) && $wpshop_price_piloting_option == 'HT')  ? __('ET', 'wpshop') : '';
				
				
			if ( $return_type == 'check_only' ) {
				/*
				 * Check if the product price has been set
				*/
				if(isset($product[WPSHOP_PRODUCT_PRICE_TTC]) && $product[WPSHOP_PRODUCT_PRICE_TTC] === '') return __('This product cannot be purchased - the price is not yet announced', 'wpshop');
				/*
				 * Check if the product price is coherent (not less than 0)
				*/
				if(isset($product[WPSHOP_PRODUCT_PRICE_TTC]) && $product[WPSHOP_PRODUCT_PRICE_TTC] < 0) return __('This product cannot be purchased - its price is negative', 'wpshop');
		
				return true;
			}
			else if ( $return_type == 'price_display' ) {          
				$price_infos = wpshop_prices::check_product_price($product);
				$the_price = ( !empty($wpshop_price_piloting_option) && $wpshop_price_piloting_option == 'HT') ? $price_infos['et'] : $price_infos['ati'];
				$discount_exist = false;
		        if ( !empty($price_infos['discount']) && !empty($price_infos['discount']['discount_exist']) && $price_infos['discount']['discount_exist'] ) {
		        	$the_price = ( !empty($wpshop_price_piloting_option) && $wpshop_price_piloting_option == 'HT') ? $price_infos['discount']['discount_et_price'] : $price_infos['discount']['discount_ati_price'];
		        	$discount_exist = true;
		        }
				$display_type = $output_type;
		
				if ( !empty($output_type) && is_array($output_type) ) {
					$display_type = $output_type[0];
					$display_sub_type = $output_type[1];
				}
		
				/** Get the definition for attribute price: allows to define if the price have to displayed or not	*/
				$price_attribute = wpshop_attributes::getElement(WPSHOP_PRODUCT_PRICE_TTC, "'valid'", 'code');
		
				/** Check price configuration for output	*/
				$price_display = wpshop_attributes::check_attribute_display( (($display_type == 'mini_output' ) ? $price_attribute->is_visible_in_front_listing : $price_attribute->is_visible_in_front), $product['custom_display'], 'attribute', WPSHOP_PRODUCT_PRICE_TTC, $display_type);
		
				/** Check the current output type and the price attribute configuration for knowing the output to take	*/
				if ( !$price_display ) {
					$price_display = '';
				}
				else {
					$price = !empty( $the_price ) ? wpshop_display::format_field_output('wpshop_product_price', $the_price) . ' ' . $productCurrency : __('Unknown price','wpshop');
					if ( $discount_exist ) {
						$crossed_out_price = ( (!empty($wpshop_price_piloting_option) && $wpshop_price_piloting_option == 'HT') ? $price_infos['et'] : $price_infos['ati'] ).' '. $productCurrency;
						$tpl_component['CROSSED_OUT_PRICE'] = wpshop_display::display_template_element('product_price_template_crossed_out_price', array('CROSSED_OUT_PRICE_VALUE' => $crossed_out_price));
					}
					/** Template parameters	*/
					$template_part = 'product_price_template_' . $display_type;
					$tpl_component['PRODUCT_PRICE'] = $price;
					$tpl_component['PRODUCT_ORIGINAL_PRICE'] = ($price != __('Unknown price','wpshop')) ? $price : '';
		
					/** For each attribute in price set section: create an element for display	*/
					$atribute_list = wpshop_attributes::get_attribute_list_in_same_set_section( WPSHOP_PRODUCT_PRICE_TTC );
					if ( !empty($atribute_list) && is_array($atribute_list) ) {
						foreach ( $atribute_list as $attribute) {
							if ( !empty($product[$attribute->code]) && wpshop_attributes::check_attribute_display( (($display_type == 'mini_output' ) ? $attribute->is_visible_in_front_listing : $attribute->is_visible_in_front), $product['custom_display'], 'attribute', $attribute->code, $display_type) ) {
								$tpl_component['PRODUCT_PRICES_' . strtoupper($attribute->code)] = wpshop_display::format_field_output('wpshop_product_price', $product[$attribute->code]) . ' ' . $productCurrency;
							}
							else {
								$tpl_component['PRODUCT_PRICES_' . strtoupper($attribute->code)] = '';
							}
						}
					}
						
					/**	Check if there are variaton for current product	*/
					$current_product_variation = wpshop_products::get_variation( $product['product_id'] );
					if ( !empty($current_product_variation) ) {
						$head_wpshop_variation_definition = get_post_meta( $product['product_id'], '_wpshop_variation_defining', true );
						$product_post_meta = get_post_meta( $product['product_id'], '_wpshop_product_metadata', true );
						/** Check if the price to display must be the lowest price of variation */
						$catalog_product_option = get_option('wpshop_catalog_product_option');
						if ( ( !empty($catalog_product_option) && !empty($catalog_product_option['price_display']) && !empty($catalog_product_option['price_display']['lower_price']) ) || (!empty($head_wpshop_variation_definition['options']['price_display']) && !empty($head_wpshop_variation_definition['options']['price_display']['lower_price']) && ($head_wpshop_variation_definition['options']['price_display']['lower_price'] == 'on')) ) {
							$lower_price = $discount_lower_price = 0;
							$price_index = constant('WPSHOP_PRODUCT_PRICE_' . WPSHOP_PRODUCT_PRICE_PILOT);
							foreach ($current_product_variation as $variation_id => $variation_definition) {
								if ( !empty($variation_definition['variation_dif']) && !empty($variation_definition['variation_dif'][$price_index]) ) {
									if ( empty($lower_price) || $variation_definition['variation_dif'][$price_index] < $lower_price ) {
										if ( !empty($head_wpshop_variation_definition) && !empty($head_wpshop_variation_definition['options']) && !empty($head_wpshop_variation_definition['options']['price_behaviour']) && $head_wpshop_variation_definition['options']['price_behaviour'][0] == 'addition') {
											$lower_price = ( (!empty($wpshop_price_piloting_option) && $wpshop_price_piloting_option == 'HT') ? $product_post_meta['price_ht'] : $product_post_meta['product_price']) + $variation_definition['variation_dif'][$price_index];				
										}
										else {
											$lower_price = $variation_definition['variation_dif'][$price_index];
										}
		
									}
								}
								if ( !empty($variation_definition['variation_dif']) ) {
									foreach ($variation_definition['variation_dif'] as $attribute_code => $attribute_value_for_variation) {
										$attribute = wpshop_attributes::getElement($attribute_code, "'valid'", 'code');
										if ( !empty($attribute_value_for_variation) && wpshop_attributes::check_attribute_display( (($display_type == 'mini_output' ) ? $attribute->is_visible_in_front_listing : $attribute->is_visible_in_front), $product['custom_display'], 'attribute', $attribute_code, $display_type) ) {
											$tpl_component['PRODUCT_PRICES_' . strtoupper($attribute_code)] = wpshop_display::format_field_output('wpshop_product_price', $attribute_value_for_variation) . ' ' . $productCurrency;
										}
										else {
											$tpl_component['PRODUCT_PRICES_' . strtoupper($attribute_code)] = '';
										}
									}
								}
							}
							$tpl_component['PRODUCT_PRICE'] = ( $lower_price > 0 ) ? wpshop_display::format_field_output('wpshop_product_price', $lower_price) . ' ' . $productCurrency : $price;
							if ( $lower_price > 0 && $discount_exist ) {
								$tpl_component['CROSSED_OUT_PRICE'] = wpshop_display::display_template_element('product_price_template_crossed_out_price', array('CROSSED_OUT_PRICE_VALUE' => $tpl_component['PRODUCT_PRICE']));
								$lower_price = ( !empty($wpshop_price_piloting_option) && $wpshop_price_piloting_option == 'HT') ? $lower_price : ( $lower_price / (1 + ($product[WPSHOP_PRODUCT_PRICE_TAX]/100) ) );
								$discount_price = wpshop_prices::get_discount_amount($product['product_id'], $lower_price);
								$tpl_component['PRODUCT_PRICE'] = wpshop_display::format_field_output('wpshop_product_price', ( ( !empty($wpshop_price_piloting_option) && $wpshop_price_piloting_option == 'HT') ? $discount_price[1] : ($discount_price[1] * (1 + ($product[WPSHOP_PRODUCT_PRICE_TAX]/100) )) )   ). ' ' . $productCurrency;
							}
								
						}
		
						/**	Check if the text price from must be displayed before price	*/
		
						if ( (!empty($catalog_product_option) && !empty($catalog_product_option['price_display']) && !empty($catalog_product_option['price_display']['text_from'])) || ( !empty($head_wpshop_variation_definition['options']['price_display']) && ($head_wpshop_variation_definition['options']['price_display']['text_from'] == 'on') ) ) {
							
							$tpl_component['PRODUCT_PRICE'] = __('Price from', 'wpshop') . ' ' .(( $discount_exist && !empty($crossed_out_price) ) ? $tpl_component['CROSSED_OUT_PRICE'] : ''). ' ' . $tpl_component['PRODUCT_PRICE'];
							$tpl_component['CROSSED_OUT_PRICE'] = '';
						}
					}
					$tpl_component['TAX_PILOTING'] = ( !empty($wpshop_price_piloting_option) && $wpshop_price_piloting_option == 'HT')  ? __('ET', 'wpshop') : '';
					$price_display = wpshop_display::display_template_element($template_part, $tpl_component);
					unset($tpl_component);
		
					/** Build template	*/
					if ( $only_price ) {
						$price_display = $price;
					}
					else {
						$tpl_to_check = ($display_type == 'complete_sheet') ? 'product_complete_tpl' : 'product_mini_' . $display_sub_type;
						$tpl_way_to_take = wpshop_display::check_way_for_template($tpl_to_check);
						if ( $tpl_way_to_take[0] && !empty($tpl_way_to_take[1]) ) {
							$price_display = $price;
						}
						else if ( is_file(get_stylesheet_directory() . '/wpshop/wpshop_elements_template.tpl.php') ) {
							$file_path = get_stylesheet_directory() . '/wpshop/wpshop_elements_template.tpl.php';
		
							require($file_path);
							if ( !empty($tpl_element) && !empty($tpl_element[$tpl_to_check]) ) {
								$price_display = $price;
							}
						}
					}
				}
				return $price_display;
			}
			return false;
		}
		
		/** Calculate the ET product price with the discount rules
		 * 
		 * @param integer $product_id
		 * @param integer $product_price_et
		 * @return integer 
		 */
		function get_discount_amount ( $product_id, $product_price_et ) {
			global $wpdb;
			$exist_discount = false;
			$product_discount_date_from = $product_discount_date_to = 0;
			$discount_infos = array();
			$wpshop_price_piloting_option = get_option('wpshop_shop_price_piloting');
			if ( !empty($product_id) && !empty($product_price_et) ) {
				$product_post_meta = get_post_meta($product_id, WPSHOP_PRODUCT_ATTRIBUTE_META_KEY, true);
				$discount_options = get_option('wpshop_catalog_product_option');
				if ( !empty($discount_options) && !empty($discount_options['discount']) ) {
					/** Check if the product is a variation */
					$product_variation_meta = get_post_meta( $product_id, '_wpshop_variations_attribute_def', true);
					if ( !empty($product_variation_meta) ) {
						$parent_product_infos = wpshop_products::get_parent_variation( $product_id );
						if ( !empty($parent_product_infos) && !empty($parent_product_infos['parent_post']) ) {
							$parent_post = $parent_product_infos['parent_post'];
							$parent_product_post_meta = get_post_meta( $parent_post->ID, WPSHOP_PRODUCT_ATTRIBUTE_META_KEY, true);
						}
					}
					else {
						$parent_product_post_meta  = get_post_meta( $product_id, WPSHOP_PRODUCT_ATTRIBUTE_META_KEY, true);
					}
					$product_discount_date_from = ( !empty($parent_product_post_meta['special_from']) ) ? $parent_product_post_meta['special_from'] : 0;
					$product_discount_date_to = ( !empty($parent_product_post_meta['special_from']) ) ? $parent_product_post_meta['special_to'] : 0;
					$current_date = date('Y-m-d');
					if ( ( empty($product_discount_date_from) && empty($product_discount_date_to) ) || (strtotime($product_discount_date_from) < strtotime($current_date) && strtotime($current_date) < strtotime($product_discount_date_to) ) ) {
						$discount_amount = ( !empty($parent_product_post_meta['discount_amount']) ) ? $parent_product_post_meta['discount_amount'] : 0;
						$discount_rate = ( !empty($parent_product_post_meta['discount_rate']) ) ? $parent_product_post_meta['discount_rate'] : 0;
						$special_price = ( !empty($parent_product_post_meta['special_price']) ) ? $parent_product_post_meta['special_price'] : 0;
						if ( !empty($special_price) && $special_price > 0 ) {
							$tva_rate_id = $product_post_meta['tx_tva'];
							$query = $wpdb->prepare('SELECT value FROM ' .WPSHOP_DBT_ATTRIBUTE_VALUES_OPTIONS. ' WHERE id = %d',  $tva_rate_id);
							$tva_rate = $wpdb->get_var($query);
							$product_price_et = ( !empty($wpshop_price_piloting_option) && $wpshop_price_piloting_option == 'HT') ? $special_price : $special_price / ( 1 + ($tva_rate/100) );
							$exist_discount = true;
							$discount_infos[] = array('discount_type' => 'special_price', 'amount' => $special_price);
						}
						else {
							if ( !empty($discount_amount) && $discount_amount > 0 ) {
								$product_price_et = $product_price_et - $discount_amount;
								$exist_discount = true;
								$discount_infos[] = array('discount_type' => 'discount_amount', 'amount' => $discount_amount);
							}
							if ( !empty($discount_rate) && $discount_rate > 0 ) {
								$product_price_et = $product_price_et / (1 + ($discount_rate / 100) );
								$exist_discount = true;
								$discount_infos[] = array('discount_type' => 'discount_rate', 'amount' => $discount_rate);
							}
						}
					}
				}
				
			}
			return array($exist_discount, number_format(str_replace(',', '.',$product_price_et), 5, '.', ''), array('discount_infos'=> $discount_infos, 'discount_date_from' => $product_discount_date_from, 'discount_date_to'=> $product_discount_date_to));	
		}
		
	}
 }
/**	Instanciate the module utilities if not	*/
if ( class_exists("wpshop_prices") ) {
	$wpshop_prices = new wpshop_prices();
}