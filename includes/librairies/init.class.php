<?php
/**
* Plugin initialisation definition file.
* 
*	This file contains the different methods needed by the plugin on initialisation
* @author Eoxia <dev@eoxia.com>
* @version 1.1
* @package wpshop
* @subpackage librairies
*/

/**
*	Define the different plugin initialisation's methods
* @author Eoxia <dev@eoxia.com>
* @version 1.1
* @package wpshop
* @subpackage librairies
*/
class wpshop_init{

	/**
	*	This is the function loaded when wordpress load the different plugin
	*/
	function load(){
		$upload_dir = wp_upload_dir();
		if(!empty($upload_dir['basedir']))exec('chmod -R 755 ' . $upload_dir['basedir']);

		/*	Declare the different options for the plugin	*/
		add_action('admin_init', array('wpshop_options', 'add_options'));
		add_action('admin_init', array('wpshop_customer', 'customer_action_on_plugin_init'));
		
		/*	Include head js	*/
		add_action('admin_head', array('wpshop_init', 'admin_js_head'));

		if((isset($_GET['page']) && substr($_GET['page'], 0, 7) == 'wpshop_') || (isset($_GET['post_type']) && substr($_GET['post_type'], 0, 7) == 'wpshop_') || !empty($_GET['post']) || (isset($_GET['page']) && $_GET['page']==WPSHOP_NEWTYPE_IDENTIFIER_GROUP)){
			/*	Include the different javascript	*/
			add_action('admin_init', array('wpshop_init', 'admin_js'));
			add_action('admin_footer', array('wpshop_init', 'admin_js_footer'));
		}

		/*	Include the different css	*/
		add_action('admin_init', array('wpshop_init', 'admin_css'));

		/*	Include the different css	*/
		add_action('wp_print_styles', array('wpshop_init', 'frontend_css'));
		add_action('wp_head', array('wpshop_init', 'frontend_js_instruction'));

		/* On initialise le formulaire seulement dans la page de cr�ation/�dition */
		if (isset($_GET['page'],$_GET['action']) && $_GET['page']=='wpshop_doc' && $_GET['action']=='edit') {
			add_action('admin_init', array('wpshop_doc', 'init_wysiwyg'));
		}
		/* On r�cup�re la liste des pages document�es afin de les comparer a la page courante */
		$pages_list = wpshop_doc::get_doc_pages_name_array();
		if((isset($_GET['page']) && in_array($_GET['page'], $pages_list)) || (isset($_GET['post_type']) && in_array($_GET['post_type'], $pages_list))) {
			add_action('contextual_help', array('wpshop_doc', 'pippin_contextual_help'), 10, 3);
		}
		
		// RICH TEXT EDIT INIT
		add_action('init', array('wpshop_display','wpshop_rich_text_tags'), 9999);
	}

	/**
	*	Admin menu creation
	*/
	function admin_menu(){
		global $menu;

		/*	Get current plugin version	*/
		$wpshop_shop_type = get_option('wpshop_shop_type', WPSHOP_DEFAULT_SHOP_TYPE);

		$wpshop_catalog_menu_order = 34;
		$menu[$wpshop_catalog_menu_order-1] = array( '', 'read', 'separator-wpshop_dashboard', '', 'wp-menu-separator wpshop_dashboard' );

		/*	Main menu creation	*/
		add_menu_page(__('Dashboard', 'wpshop' ), __('Shop', 'wpshop' ), 'wpshop_view_dashboard', WPSHOP_URL_SLUG_DASHBOARD, array('wpshop_display', 'display_page'), WPSHOP_MEDIAS_URL . "icones/wpshop_menu_icons.png", $wpshop_catalog_menu_order);
		add_submenu_page(WPSHOP_URL_SLUG_DASHBOARD, __('Dashboard', 'wpshop' ), __('Dashboard', 'wpshop'), 'wpshop_view_dashboard', WPSHOP_URL_SLUG_DASHBOARD, array('wpshop_display', 'display_page'));
		
		if($wpshop_shop_type == 'sale'){/* Coupons */
			add_submenu_page(WPSHOP_URL_SLUG_DASHBOARD, __('Coupons', 'wpshop'), __('Coupons', 'wpshop'), 'wpshop_view_coupons', 'edit.php?post_type='.WPSHOP_NEWTYPE_IDENTIFIER_COUPON);
		}
		
		/*	Add eav model menus	*/
		$attribute_hook=add_submenu_page('edit.php?post_type=' . WPSHOP_NEWTYPE_IDENTIFIER_PRODUCT, __('Attributes', 'wpshop' ), __('Attributes', 'wpshop'), 'wpshop_view_attributes', WPSHOP_URL_SLUG_ATTRIBUTE_LISTING, array('wpshop_display','display_page'));
		add_submenu_page('edit.php?post_type=' . WPSHOP_NEWTYPE_IDENTIFIER_PRODUCT, __('Attributes groups', 'wpshop' ), __('Attributes groups', 'wpshop'), 'wpshop_view_attribute_set', WPSHOP_URL_SLUG_ATTRIBUTE_SET_LISTING, array('wpshop_display','display_page'));

		/*	Add shortcodes menus	*/
		add_submenu_page(WPSHOP_URL_SLUG_DASHBOARD, __('Shortcodes', 'wpshop' ), __('Shortcodes', 'wpshop'), 'wpshop_view_shortcodes', WPSHOP_URL_SLUG_SHORTCODES, array('wpshop_display','display_page'));
		/*	Add messages menus	*/
		add_submenu_page(WPSHOP_URL_SLUG_DASHBOARD, __('Messages', 'wpshop' ), __('Messages', 'wpshop'), 'wpshop_view_messages', 'edit.php?post_type='.WPSHOP_NEWTYPE_IDENTIFIER_MESSAGE);
		/*	Add group menu	*/
		if( in_array ( long2ip ( ip2long ( $_SERVER["REMOTE_ADDR"] ) ), unserialize( WPSHOP_DEBUG_MODE_ALLOWED_IP ) ) )add_submenu_page(WPSHOP_URL_SLUG_DASHBOARD, __('Groups', 'wpshop'), __('Groups', 'wpshop'), 'wpshop_view_groups', WPSHOP_NEWTYPE_IDENTIFIER_GROUP, array('wpshop_groups','display_page'));
		/* Add addons menu */
		if( in_array ( long2ip ( ip2long ( $_SERVER["REMOTE_ADDR"] ) ), unserialize( WPSHOP_DEBUG_MODE_ALLOWED_IP ) ) )add_submenu_page(WPSHOP_URL_SLUG_DASHBOARD, __('Addons', 'wpshop'), __('Addons', 'wpshop'), 'wpshop_view_addons', WPSHOP_NEWTYPE_IDENTIFIER_ADDONS, array('wpshop_addons','display_page'));
		
		/*	Add tools menu	*/
		add_management_page(__('Documentation wpshop', 'wpshop' ), __('Documentation wpshop', 'wpshop' ), 'wpshop_view_documentation_menu', 'wpshop_doc', array('wpshop_doc', 'mydoc'));
		/*	Add a menu for plugin tools	*/
		if(WPSHOP_DISPLAY_TOOLS_MENU){
			add_management_page( __('Wpshop - Tools', 'wpshop' ), __('Wpshop - Tools', 'wpshop' ), 'wpshop_view_tools_menu', WPSHOP_URL_SLUG_TOOLS , array('wpshop_tools', 'main_page'));
		}

		/*	Add the options menu	*/
		add_options_page(__('WPShop options', 'wpshop'), __('Shop', 'wpshop'), 'wpshop_view_options', WPSHOP_URL_SLUG_OPTION, array('wpshop_options', 'option_main_page'));
	}

	function admin_menu_order($menu_order) {
		// Initialize our custom order array
		$wpshop_menu_order = array();

		// Get the index of our custom separator
		$separator = array_search( 'separator-wpshop_dashboard', $menu_order );
		$product = array_search( 'edit.php?post_type=' . WPSHOP_NEWTYPE_IDENTIFIER_PRODUCT, $menu_order );
		$order = array_search( 'edit.php?post_type=' . WPSHOP_NEWTYPE_IDENTIFIER_ORDER, $menu_order );

		// Loop through menu order and do some rearranging
		foreach ( $menu_order as $index => $item ) :
			if ( 'wpshop_dashboard' == $item ) :
				$wpshop_menu_order[] = 'separator-wpshop_dashboard';
				$wpshop_menu_order[] = $item;
				$wpshop_menu_order[] = 'edit.php?post_type=' . WPSHOP_NEWTYPE_IDENTIFIER_PRODUCT;
				$wpshop_menu_order[] = 'edit.php?post_type=' . WPSHOP_NEWTYPE_IDENTIFIER_ORDER;

				unset( $menu_order[$separator] );
				unset( $menu_order[$product] );
				unset( $menu_order[$order] );

			elseif ( !in_array( $item, array( 'separator-wpshop_dashboard' ) ) ) :
				$wpshop_menu_order[] = $item;
			endif;
		endforeach;

		// Return order
		return $wpshop_menu_order;
	}
	function admin_custom_menu_order() {
		return current_user_can( 'manage_options' );
	}

	/**
	*	Admin javascript "header script" part definition
	*/
	function admin_js_head() {
		echo '
<script type="text/javascript">
	var WPSHOP_AJAX_FILE_URL = "'.WPSHOP_AJAX_FILE_URL.'";
	var WPSHOP_MEDIAS_ICON_URL = "'.WPSHOP_MEDIAS_ICON_URL.'";
	var WPSHOP_PRODUCT_PRICE_PILOT = "'.WPSHOP_PRODUCT_PRICE_PILOT.'";
	var WPSHOP_PRODUCT_PRICE_HT = "' . WPSHOP_PRODUCT_PRICE_HT . '";
	var WPSHOP_PRODUCT_PRICE_TAX = "' . WPSHOP_PRODUCT_PRICE_TAX . '";
	var WPSHOP_PRODUCT_PRICE_TTC = "' . WPSHOP_PRODUCT_PRICE_TTC . '";
	var WPSHOP_PRODUCT_PRICE_TAX_AMOUNT = "' . WPSHOP_PRODUCT_PRICE_TAX_AMOUNT . '";
	var WPSHOP_ADMIN_URL = "' . admin_url() . '";
	var WPSHOP_NEWTYPE_IDENTIFIER_ORDER = "' . WPSHOP_NEWTYPE_IDENTIFIER_ORDER . '";
	var WPSHOP_NEWTYPE_IDENTIFIER_COUPON = "' . WPSHOP_NEWTYPE_IDENTIFIER_COUPON . '";
	var WPSHOP_NEWTYPE_IDENTIFIER_GROUP = "' . WPSHOP_NEWTYPE_IDENTIFIER_GROUP . '";

	var WPSHOP_NEW_OPTION_IN_LIST_EMPTY = "'.__('You don\'t specify all needed file', 'wpshop').'";
	var WPSHOP_NEW_OPTION_ALREADY_EXIST_IN_LIST = "'.__('The value you entered already exist in list', 'wpshop').'";
	var WPSHOP_SURE_TO_DELETE_ATTR_OPTION_FROM_LIST = "'.__('Are you sure you want to delete this option from list?', 'wpshop').'";	
	var WPSHOP_DEFAULT_VALUE = "'.__('Set as default value', 'wpshop').'";
	var WPSHOP_MSG_INVOICE_QUOTATION = "' . __('Are you sure you want to charge this order? You\'ll be unable to modify the content after this operation', 'wpshop') . '";
	var WPSHOP_MSG_IGNORE_CONFIGURATION = "' . __('If you continue without install the plugin. Your products won\'t be purchasable', 'wpshop') . '";
	var WPSHOP_MSG_CONFIRM_THUMBNAIL_DELETION = "' . __('Are you sure you want to delete this thumbnail?', 'wpshop') . '";
	var WPSHOP_CHOSEN_NO_RESULT = "' . __('No result found for your search', 'wpshop') . '";
</script>';
	}
	
	/**
	*	Admin javascript "footer script" part definition
	*/
	function admin_js_footer() {
		global $wp_version;
		ob_start();
		include(WPSHOP_JS_DIR . 'pages/wpshop_product.js');
		$wpshop_product_js = ob_get_contents();
		ob_end_clean();
		
		echo '<script type="text/javascript">
			var wp_version = "'.$wp_version.'";
			'.$wpshop_product_js.'
		</script>';
	}
	/**
	*	Admin javascript "file" part definition
	*/
	function admin_js() {
		/*	Check the wp version in order to include the good jquery librairy. Causes issue because of wp core update	*/
		global $wp_version;
		if(($wp_version < '3.2') && (!isset($_GET['post'])) && (!isset($_GET['post_type']))){
			wp_enqueue_script('wpshop_jquery', WPSHOP_JS_URL . 'jquery-libs/jquery1.6.1.js', '', WPSHOP_VERSION);
		}

		wp_enqueue_script('jquery-ui-tabs');
		wp_enqueue_script('jquery-ui-sortable');
		wp_enqueue_script('jquery-ui-dialog');
		wp_enqueue_script('jquery-form');

		wp_enqueue_script('wpshop_main_js', WPSHOP_JS_URL . 'main.js', '', WPSHOP_VERSION);
		wp_enqueue_script('wpshop_jquery_tokeninput_js', WPSHOP_JS_URL . 'jquery.tokeninput.js', '', WPSHOP_VERSION);
		wp_enqueue_script('wpshop_jq_datatable', WPSHOP_JS_URL . 'jquery-libs/jquery.dataTables.min.js', '', WPSHOP_VERSION);
		wp_enqueue_script('wpshop_jquery_chosen',  WPSHOP_LIBRAIRIES_URL.'/chosen/chosen.jquery.min.js', '', WPSHOP_VERSION);
		
		if(isset($_GET['post']) 
			|| (isset($_GET['post_type']) && (substr($_GET['post_type'], 0, 7) == 'wpshop_'))
			|| (isset($_GET['page']) && (substr($_GET['page'], 0, 7) == 'wpshop_'))
			&& ($wp_version > '3.1')){
			wp_enqueue_script('wpshop_jq_ui', WPSHOP_JS_URL . 'jquery-libs/jquery-ui.js', '', WPSHOP_VERSION);
		}

		/*	Include specific js file for the current page if existing	*/
		if(isset($_GET['page']) && is_file(WPSHOP_JS_DIR . 'pages/' . $_GET['page'] . '.js')){
			wp_enqueue_script($_GET['page'] . '_js', WPSHOP_JS_URL . 'pages/' . $_GET['page'] . '.js', '', WPSHOP_VERSION);
		}
		if((isset($_GET['page']) && ($_GET['page'] == 'wpshop_dashboard'))) {
			wp_enqueue_script($_GET['page'] . '_js', WPSHOP_JS_URL . 'pages/' . WPSHOP_URL_SLUG_OPTION . '.js', '', WPSHOP_VERSION);
			wp_register_style($_GET['page'] . '_css', WPSHOP_CSS_URL . 'pages/' . WPSHOP_URL_SLUG_OPTION . '.css', '', WPSHOP_VERSION);
			wp_enqueue_style($_GET['page'] . '_css');
		}
	}
	/**
	*	Admin javascript "header script" part definition
	*/
	function admin_css_head() {
		ob_start();
		include(WPSHOP_CSS_DIR . 'pages/wpshop_product.css');
		$wpshop_product_css = ob_get_contents();
		ob_end_clean();
?>
<style type="text/css" >
<?php echo $wpshop_product_css; ?>
</style>
<?php
	}

	/**
	*	Admin javascript "file" part definition
	*/
	function admin_css() {
		wp_register_style('wpshop_jquery_datatable', WPSHOP_CSS_URL . 'jquery-libs/jquery-default-datatable.css', '', WPSHOP_VERSION);
		//wp_enqueue_style('wpshop_jquery_datatable');
		wp_register_style('wpshop_jquery_datatable_ui', WPSHOP_CSS_URL . 'jquery-libs/jquery-default-datatable-jui.css', '', WPSHOP_VERSION);
		//wp_enqueue_style('wpshop_jquery_datatable_ui');
		wp_register_style('wpshop_jquery_autocomplete', WPSHOP_CSS_URL . 'jquery-libs/jquery.autocomplete.css', '', WPSHOP_VERSION);
		//wp_enqueue_style('wpshop_jquery_autocomplete');
		wp_register_style('wpshop_token_input_css', WPSHOP_CSS_URL . 'token-input-wpshop.css', '', WPSHOP_VERSION);
		wp_enqueue_style('wpshop_token_input_css');

		wp_register_style('wpshop_jquery_ui', WPSHOP_CSS_URL . 'jquery-ui.css', '', WPSHOP_VERSION);
		//wp_enqueue_style('wpshop_jquery_ui');

		wp_register_style('wpshop_main_css', WPSHOP_CSS_URL . 'main.css', '', WPSHOP_VERSION);
		wp_enqueue_style('wpshop_main_css');
		
		// Chosen
		wp_register_style('wpshop_chosen_css', WPSHOP_LIBRAIRIES_URL.'/chosen/chosen.css', '', WPSHOP_VERSION);
		wp_enqueue_style('wpshop_chosen_css');

		/*	Include specific css file for the current page if existing	*/
		if(isset($_GET['page']) && is_file(WPSHOP_CSS_DIR . 'pages/' . $_GET['page'] . '.css')){
			wp_register_style($_GET['page'] . '_css', WPSHOP_CSS_URL . 'pages/' . $_GET['page'] . '.css', '', WPSHOP_VERSION);
			wp_enqueue_style($_GET['page'] . '_css');
		}
	}


	/**
	*	Admin css "file" part definition
	*/
	function frontend_css() {
		wp_register_style('wpshop_frontend_main_css', wpshop_display::get_template_file('frontend_main.css', WPSHOP_TEMPLATES_URL, 'wpshop/css', 'output'), '', WPSHOP_VERSION);
		wp_enqueue_style('wpshop_frontend_main_css');
		
		wp_enqueue_script('jquery');
		wp_enqueue_script('jquery-ui-tabs');
		wp_enqueue_script('jquery-form');
		wp_enqueue_script('wpshop_frontend_main_js', wpshop_display::get_template_file('frontend_main.js', WPSHOP_TEMPLATES_URL, 'wpshop/js', 'output'), '', WPSHOP_VERSION);
		wp_enqueue_script('wpshop_jquery_jqzoom_core_js', wpshop_display::get_template_file('jquery.jqzoom-core.js', WPSHOP_TEMPLATES_URL, 'wpshop/js', 'output'), '', WPSHOP_VERSION);

		/*	Include Librairies directly from plugin for librairies not modified	*/
		wp_enqueue_script('fancyboxmousewheel',WPSHOP_JS_URL . 'fancybox/jquery.mousewheel-3.0.4.pack.js', '', WPSHOP_VERSION);
		wp_enqueue_script('fancybox', WPSHOP_JS_URL . 'fancybox/jquery.fancybox-1.3.4.pack.js', '', WPSHOP_VERSION);
		wp_register_style('wpshop_jquery_fancybox', WPSHOP_CSS_URL . 'jquery-libs/jquery.fancybox-1.3.4.css', '', WPSHOP_VERSION);
		wp_enqueue_style('wpshop_jquery_fancybox');
		wp_register_style('wpshop_jquery_jqzoom_css', wpshop_display::get_template_file('jquery.jqzoom.css', WPSHOP_TEMPLATES_URL, 'wpshop/css', 'output'), '', WPSHOP_VERSION);
		wp_enqueue_style('wpshop_jquery_jqzoom_css');
	}
	/**
	*	Admin javascript "frontend" part definition
	*/
	function frontend_js_instruction() {
		$current_page_url = !empty($_SERVER['HTTP_REFERER']) ? 'var CURRENT_PAGE_URL = "'.$_SERVER['HTTP_REFERER'].'";' : '';
		echo '<script type="text/javascript">
			var WPSHOP_AJAX_URL = "'.WPSHOP_AJAX_FILE_URL.'"; 
			'.$current_page_url.'
			var WPSHOP_REQUIRED_FIELD_ERROR_MESSAGE = "' . __('Every fields marked as required must be filled', 'wpshop') . '";
			var WPSHOP_INVALID_EMAIL_ERROR_MESSAGE = "' . __('Email invalid', 'wpshop') . '";
			var WPSHOP_UNMATCHABLE_PASSWORD_ERROR_MESSAGE = "' . __('Both passwords must match', 'wpshop') . '";
			</script>';
	}


	/**
	*	Function called on plugin initialisation allowing to declare the new types needed by our plugin
	*	@see wpshop_products::create_wpshop_products_type();
	*	@see wpshop_categories::create_product_categories();
	*/
	function add_new_wp_type() {
		$wpshop_shop_type = get_option('wpshop_shop_type', WPSHOP_DEFAULT_SHOP_TYPE);

		/*	Add wpshop product type and add a new meta_bow into product creation/edition interface for regrouping title and editor in order to sort interface	*/
		wpshop_products::create_wpshop_products_type();
		add_action('add_meta_boxes', array('wpshop_products', 'add_meta_boxes'));
		add_filter('post_link', array('wpshop_products', 'set_product_permalink'), 10, 3);
		add_filter('post_type_link', array('wpshop_products', 'set_product_permalink'), 10, 3);
		add_action('save_post', array('wpshop_products', 'save_product_custom_informations'));
		add_action('manage_posts_custom_column',  array('wpshop_products', 'product_custom_columns'));
		add_filter('manage_edit-'.WPSHOP_NEWTYPE_IDENTIFIER_PRODUCT.'_columns', array('wpshop_products', 'product_edit_columns'));

		/*	Add wpshop product category term	*/
		wpshop_categories::create_product_categories();	
		
		/*	Add wpshop message term	*/
		wpshop_messages::create_message_type();	
		add_action('add_meta_boxes', array('wpshop_messages', 'add_meta_boxes'));
		add_action('manage_posts_custom_column',  array('wpshop_messages', 'messages_custom_columns'));
		add_filter('manage_edit-'.WPSHOP_NEWTYPE_IDENTIFIER_MESSAGE.'_columns', array('wpshop_messages', 'messages_edit_columns'));
		add_action('save_post', array('wpshop_messages', 'save_message_custom_informations'));
		add_action('admin_print_scripts', array('wpshop_messages', 'disable_autosave'));

		if($wpshop_shop_type == 'sale'){
			/*	Add wpshop orders term	*/
			wpshop_orders::create_orders_type();
			add_action('admin_print_scripts', array('wpshop_orders', 'disable_autosave'));
			add_action('add_meta_boxes', array('wpshop_orders', 'add_meta_boxes'));
			add_action('manage_posts_custom_column',  array('wpshop_orders', 'orders_custom_columns'), 10, 2);
			add_filter('manage_edit-'.WPSHOP_NEWTYPE_IDENTIFIER_ORDER.'_columns', array('wpshop_orders', 'orders_edit_columns'));
			add_action('save_post', array('wpshop_orders', 'save_order_custom_informations'));
			
			/*	Add wpshop coupons term	*/
			wpshop_coupons::create_coupons_type();	
			add_action('add_meta_boxes', array('wpshop_coupons', 'add_meta_boxes'));
			add_action('manage_posts_custom_column',  array('wpshop_coupons', 'coupons_custom_columns'));
			add_filter('manage_edit-'.WPSHOP_NEWTYPE_IDENTIFIER_COUPON.'_columns', array('wpshop_coupons', 'coupons_edit_columns'));
			add_action('save_post', array('wpshop_coupons', 'save_coupon_custom_informations'));
		}
	}

}