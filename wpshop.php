<?php
/**
* Plugin Name: wpshop
* Plugin URI: http://eoxia.com/
* Description: With this plugin you will be able to manage the products you want to sell and user would be able to buy this products
* Version: 1.2
* Author: Eoxia
* Author URI: http://eoxia.com/
*/

/**
* Plugin main file.
* 
*	This file is the main file called by wordpress for our plugin use. It define the basic vars and include the different file needed to use the plugin
* @author Eoxia <dev@eoxia.com>
* @version 1.1
* @package wpshop
*/

/**
*	First thing we define the main directory for our plugin in a super global var	
*/
DEFINE('WPSHOP_PLUGIN_DIR', basename(dirname(__FILE__)));

/*	Include the config file	*/
require(WP_PLUGIN_DIR . '/' . WPSHOP_PLUGIN_DIR . '/includes/config.php');

/*	Include the main including file	*/
require(WP_PLUGIN_DIR . '/' . WPSHOP_PLUGIN_DIR . '/includes/include.php');

/*	Check and set (if needed) administrator(s) permissions' each time the plugin is launched. Admin role has all right	*/
wpshop_permissions::set_administrator_role_permission();

/*	Call main initialisation function	*/
add_action('init', array('wpshop_init', 'load'));

/*	Call function to create the main left menu	*/
add_action('admin_menu', array('wpshop_init', 'admin_menu'));

/*	Call function for new wordpress element creating [term (product_category) / post (product)]	*/
add_action('init', array('wpshop_init', 'add_new_wp_type'));

/*	Call function allowing to change element front output	*/
add_action('the_content', array('wpshop_frontend_display', 'products_page'), 1);
add_action('archive_template', array('wpshop_categories', 'category_template_switcher'));
add_action('add_meta_boxes', array('someClass','add_some_meta_box'));

/*	On plugin activation call the function for default configuration creation	*/
include(WPSHOP_LIBRAIRIES_DIR . 'install.class.php');
register_activation_hook( __FILE__ , array('wpshop_install', 'install_wpshop') );
/*	On plugin deactivation call the function to clean the wordpress installation	*/
register_deactivation_hook( __FILE__ , array('wpshop_install', 'uninstall_wpshop') );

add_action('admin_init', array('wpshop_install', 'update_wpshop'));
// add_action('admin_init', array('wpshop_database', 'check_database'));

// Gestion des ShortCode
add_shortcode('wpshop_att_val', array('wpshop_attributes', 'wpshop_att_val_func'));
add_shortcode('wpshop_product', array('wpshop_products', 'wpshop_product_func'));
add_shortcode('wpshop_category', array('wpshop_categories', 'wpshop_category_func'));
add_shortcode('wpshop_att_group', array('wpshop_attributes_set', 'wpshop_att_group_func'));


/** 
 * The Class
 */
class someClass
{
    /**
     * Adds the meta box container
     */
    public function add_some_meta_box()
    {
        add_meta_box( 
             'some_meta_box_name', __('Shortcodes insertion', 'wpshop'),
            array('someClass', 'render_meta_box_content'), 'page', 'side', 'default'
        );
    }

    /**
     * Render Meta Box content
     */
    public function render_meta_box_content() 
    {
		// Products list
		$product_string = wpshop_products::product_list(true);
		// Attributs list
		$products_attr_string=wpshop_products::product_list_attr(true);
		// Groups list
		$groups_string = wpshop_products::product_list_group_attr(true);
		
		$content = '
		<div id="superTab">
			<ul>
				<li><a href="#wpshop_product_category-1">'.__('Products','wpshop').'</a></li>
				<li><a href="#wpshop_product_category-2">'.__('Attributs','wpshop').'</a></li>
				<li><a href="#wpshop_product_category-3">'.__('Attributs groups','wpshop').'</a></li>
			</ul>
			
			<div id="wpshop_product_category-1" class="simple">
				<input type="text" value="" placeholder="'.__('Recherche...','wpshop').'" id="search_products" />
				<div>
				<ul id="products_selected">
					'.$product_string.'
				</ul>
				</div><br />
				<label><input type="radio" checked="checked" name="product_display_type" value="list" /> Liste</label> &nbsp;
				<label><input type="radio" name="product_display_type" value="grid" /> Grille</label>
				<a class="preview button" id="insert_products">'.__('Insert','wpshop').'</a><br /><br />
			</div>
			
			<div id="wpshop_product_category-2" class="simple">
				<input type="text" value="" placeholder="'.__('Recherche...','wpshop').'" id="search_attr" />
				<div>
				<ul id="attr_selected">
					'.$products_attr_string.'
				</ul>
				</div><br />
				<a class="preview button" id="insert_attr">'.__('Insert','wpshop').'</a><br /><br />
			</div>
			
			<div id="wpshop_product_category-3" class="simple">
				<input type="text" value="" placeholder="'.__('Recherche...','wpshop').'" id="search_groups" />
				<div>
				<ul id="groups_selected">
					'.$groups_string.'
				</ul>
				</div><br />
				<a class="preview button" id="insert_groups">'.__('Insert','wpshop').'</a><br /><br />
			</div>
		</div>';
		
        echo $content;
    }
}
?>