<?php
/**
* Products management method file
* 
*	This file contains the different methods for products management
* @author Eoxia <dev@eoxia.com>
* @version 1.1
* @package wpshop
* @subpackage librairies
*/

/**
*	This file contains the different methods for products management
* @author Eoxia <dev@eoxia.com>
* @version 1.1
* @package wpshop
* @subpackage librairies
*/
class wpshop_products
{
	/**
	*	Define the current entity code
	*/
	const currentPageCode = 'product';

	/**
	*	Call wordpress function that declare a new post type in order to define the product as wordpress post
	*
	*	@see register_post_type()
	*/
	function create_wpshop_products_type(){
		register_post_type(WPSHOP_NEWTYPE_IDENTIFIER_PRODUCT, array(
			'labels' => array(
				'name' => __('Catalog', 'wpshop'),
				'singular_name' => __('Catalog', 'wpshop'),
				'add_new_item' => __('Add new product', 'wpshop'),
				'add_new' => __( 'Add new product', 'wpshop' ),
				'add_new_item' => __('Add new product', 'wpshop' ),
				'edit_item' => __('Edit product', 'wpshop' ),
				'new_item' => __('New product', 'wpshop' ),
				'view_item' => __('View product', 'wpshop' ),
				'search_items' => __('Search products', 'wpshop' ),
				'not_found' =>  __('No products found', 'wpshop' ),
				'not_found_in_trash' => __( 'No products found in Trash', 'wpshop' ),
				'parent_item_colon' => ''
			),
			'supports' => array('title', 'editor', 'excerpt','thumbnail'),
			'public' => true,
			'has_archive' => true,
			'show_in_nav_menus' => true,
			'rewrite' => array('slug' => 'catalog/%' . WPSHOP_NEWTYPE_IDENTIFIER_CATEGORIES . '%'),
			'taxonomies' => array(WPSHOP_NEWTYPE_IDENTIFIER_CATEGORIES),
			'menu_icon' => WPSHOP_MEDIAS_URL . "icones/logo.png"
		));
	}
	
	/** Set the colums for the custom page
	 * @return array
	*/
	function product_edit_columns($columns){
	  $columns = array(
		'cb' => '<input type="checkbox" />',
		'title' => __('Product name', 'wpshop'),
		'product_price_ttc' => __('Price', 'wpshop'),
		'product_stock' => __('Stock', 'wpshop'),
		'date' => __('Date', 'wpshop'),
		'product_actions' => __('Actions', 'wpshop')
	  );
	 
	  return $columns;
	}
	
	/** Content by colums for the custom page
	 * @return array
	*/
	function product_custom_columns($column){
		global $post;
		
		
		$product = self::get_product_data($post->ID);
		switch ($column) {
			case "product_price_ttc":
				if($product['product_price_ttc'])
					echo number_format($product['product_price_ttc'],2,'.', ' ').' EUR';
				else echo '<strong>-</strong>';
			break;
			
			case "product_stock":
				if($product['product_stock'])
					echo (int)$product['product_stock'].' '.__('unit(s)','wpshop');
				else echo '<strong>-</strong>';
			break;
			
			case "product_actions":
				$buttons = '<p>';
				// Voir la commande
				$buttons .= '<a class="button" href="'.admin_url('post.php?post='.$post->ID.'&action=edit').'">'.__('Edit', 'wpshop').'</a>';
				$buttons .= '</p>';
				echo $buttons;
			break;
		  }
	}

	/**
	*	Create the different bow for the product management page looking for the attribute set to create the different boxes
	*/
	function add_meta_boxes(){
		global $post, $currentTabContent;

		add_meta_box('wpshop_product_main_infos', __('Main information', 'wpshop'), array('wpshop_products', 'main_information_meta_box_content'), WPSHOP_NEWTYPE_IDENTIFIER_PRODUCT, 'normal', 'high');
		add_meta_box('wpshop_related_products', __('Related products', 'wpshop'), array('wpshop_products', 'related_products_meta_box_content'), WPSHOP_NEWTYPE_IDENTIFIER_PRODUCT, 'normal', 'high');
		add_meta_box('wpshop_product_picture_management', __('Picture management', 'wpshop'), array('wpshop_products', 'meta_box_picture'), WPSHOP_NEWTYPE_IDENTIFIER_PRODUCT, 'normal', 'default');
		add_meta_box('wpshop_product_document_management', __('Document management', 'wpshop'), array('wpshop_products', 'meta_box_document'), WPSHOP_NEWTYPE_IDENTIFIER_PRODUCT, 'normal', 'default');
		// Actions
		add_meta_box('wpshop_product_actions', __('Actions', 'wpshop'), array('wpshop_products', 'product_actions_meta_box_content'), WPSHOP_NEWTYPE_IDENTIFIER_PRODUCT, 'side', 'default');

		/*	Get the attribute set list for the current entity	*/
		$attributeEntitySetList = wpshop_attributes_set::get_attribute_set_list_for_entity(wpshop_entities::get_entity_identifier_from_code(self::currentPageCode));
		/*	Check if the meta information of the current product already exists 	*/
		$post_attribute_set_id = get_post_meta($post->ID, WPSHOP_PRODUCT_ATTRIBUTE_SET_ID_META_KEY, true);
		/*	Check if the product has been saved without meta information set	*/
		$attribute_set_id = wpshop_attributes::get_attribute_value_content('product_attribute_set_id', $post->ID, self::currentPageCode);

		/*	Check if an attribute has already been choosen for the curernt entity or if the user has to choose a entity set before continuing	*/
		if((count($attributeEntitySetList) == 1) || ((count($attributeEntitySetList) > 1) && (($post_attribute_set_id > 0) || (isset($attribute_set_id->value) && ($attribute_set_id->value > 0))))){
			if((count($attributeEntitySetList) == 1) || (($post_attribute_set_id <= 0) && ($attribute_set_id->value <= 0))){
				$post_attribute_set_id = $attributeEntitySetList[0]->id;
			}
			elseif(($post_attribute_set_id <= 0) && ($attribute_set_id->value > 0)){
				$post_attribute_set_id = $attribute_set_id->value;
			}
			$currentTabContent = wpshop_attributes::getAttributeFieldOutput($post_attribute_set_id, self::currentPageCode, $post->ID);
			/*	Get all the other attribute set for hte current entity	*/
			if(isset($currentTabContent['box']) && count($currentTabContent['box']) > 0){
				foreach($currentTabContent['box'] as $boxIdentifier => $boxTitle){
					add_meta_box('wpshop_product_' . $boxIdentifier, __($boxTitle, 'wpshop'), array('wpshop_products', 'meta_box_content'), WPSHOP_NEWTYPE_IDENTIFIER_PRODUCT, 'normal', 'default', array('boxIdentifier' => $boxIdentifier));
				}
			}
		}
		elseif(count($attributeEntitySetList) > 1){
			$input_def['id'] = 'product_attribute_set_id';
			$input_def['name'] = 'product_attribute_set_id';
			$input_def['value'] = '';
			$input_def['type'] = 'select';
			$input_def['possible_value'] = $attributeEntitySetList;
			$input_def['value'] = '';
			$currentTabContent['boxContent']['attribute_set_selector'] = '
<div class="attribute_set_selector" >
	<div class="wpshopRequired bold" >' . __('You have to choose one of existing attribute set. You won\'t be able to change it later.', 'wpshop') . '</div>
	<br/>
	<div class="bold" >' . __('Attributes associated to the selected set will be outputed when product will be saved', 'wpshop') . '</div>
	' . wpshop_form::check_input_type($input_def, self::currentPageCode) . '
</div>';

			add_meta_box('wpshop_product_attribute_set_selector', __('Attribute set for the current product', 'wpshop'), array('wpshop_products', 'meta_box_content'), WPSHOP_NEWTYPE_IDENTIFIER_PRODUCT, 'side', 'high', array('boxIdentifier' => 'attribute_set_selector'));
		}
	}
	
	/**
	*	Define the content of the product main information box
	*/
	function related_products_meta_box_content(){
		global $currentTabContent,$post;
		
		if(!empty($post->ID)) {
			$related_products_id = get_post_meta($post->ID, WPSHOP_PRODUCT_RELATED_PRODUCTS, true);
			if(!empty($related_products_id))
				$related_products_data = self::product_list($formated=false, $related_products_id);
		}
		
		echo '
			<input type="text" id="demo-input-wpshop-theme" name="blah2" />
			<input type="hidden" id="related_products_list" name="related_products_list" value="" />
			<script type="text/javascript">
			jQuery(document).ready(function() {
				jQuery("#demo-input-wpshop-theme").tokenInput(WPSHOP_AJAX_FILE_URL, {
					theme: "wpshop"
				});
			});
			</script>
		';
		
		// Si la liste n'est pas vide
		if(!empty($related_products_data)) {
			echo '<script type="text/javascript">jQuery(document).ready(function() {';
			foreach($related_products_data as $p) {
				echo 'jQuery("#demo-input-wpshop-theme").tokenInput("add", {id: '.$p->ID.', name: "'.$p->post_title.'"});';
			}
			echo '});</script>';
		}
	}
	
	/**
	* Traduit le shortcode et affiche les produits demand�s
	* @param array $atts
	* @return string
	**/
	function wpshop_product_func($atts) {
		global $wpdb;
		$products = explode(',', $atts['pid']);
		if(!empty($products))
		{
			$string='';
			foreach($products as $p):
				$query = '
					SELECT wp_term_taxonomy.term_id
					FROM wp_term_taxonomy
					LEFT JOIN wp_term_relationships ON wp_term_taxonomy.term_taxonomy_id=wp_term_relationships.term_taxonomy_id
					WHERE wp_term_taxonomy.taxonomy="wpshop_product_category" AND wp_term_relationships.object_id='.$p.'
				';
				$categories = $wpdb->get_results($query);
				$string .= wpshop_products::product_mini_output($p, $categories[0]->term_id, $atts['type']);
			endforeach;
			return do_shortcode($string);
		}
		return;
	}
	
	/**
	* Traduit le shortcode et affiche les produits en relation demand�
	*
	* @param array $atts {
	*	pid : id du produit en question
	*	display_mode : type d'affichage (grid ou list)
	* }
	*
	* @return string
	*
	**/
	function wpshop_related_products_func($atts) {
		global $wpdb;
		
		$product_id = !empty($atts['pid']) ? $atts['pid'] : get_the_ID();
		$display_mode = !empty($atts['display_mode']) && in_array($atts['display_mode'],array('list','grid')) ? $atts['display_mode'] : 'grid';
		
		$pids = get_post_meta($product_id, WPSHOP_PRODUCT_RELATED_PRODUCTS, true);
		include_once(wpshop_display::get_template_file('product_related.tpl.php'));
		
		return;
	}
	
	/**
	* Traduit le shortcode et affiche les produits demand�
	*
	* @param array $atts {
	*	limit : limite de r�sultats de la requete
	*	order : param�tre de tri
	*	sorting : sens du tri (asc, desc)
	*	type : type d'affichage (grid, list), seulement pour display=normal
	*	display : taille d'affichage, normal (gd format avec images) ou mini (petit format sans image)
	* }
	*
	* @return string
	*
	**/
	function wpshop_products_func($atts) {
		global $wpdb;
		
		/* On r�cup�re la valeur num�rique des arguments dont on a besoin */
		$pagination = isset($atts['pagination']) ? intval($atts['pagination']) : 20;
		$limit = isset($atts['limit']) ? intval($atts['limit']) : 0;
		
		$order = $atts['sorting']=='desc'?'DESC':'ASC';
		$type = $atts['type']=='list'?'list':'grid';
			
		// Param�tre de tri
		if($atts['order']=='date') $orderby = 'date';
		elseif($atts['order']=='price') $orderby = 'price';
		elseif($atts['order']=='random') $orderby = 'rand';
		elseif($atts['order']=='title') $orderby = 'title';
		else $orderby = 'title';
			
		$string = '
			<div class="sorting_div">
				'.__('Sorting','wpshop').' : <select name="sorting_criteria">
					<option value="" selected="selected">'.__('Choose...','wpshop').'</option>
					<option value="title">'.__('Name','wpshop').'</option>
					<option value="price">'.__('Price','wpshop').'</option>
					<option value="date">'.__('Add date','wpshop').'</option>
					<option value="stock">'.__('Stock','wpshop').'</option>
					<option value="rand">'.__('Random','wpshop').'</option>
				</select>
				<input type="hidden" name="display_type" value="'.$type.'" />
				<input type="hidden" name="orderby" value="'.$orderby.'" />
				<input type="hidden" name="order" value="'.$order.'" />
				<input type="hidden" name="products_per_page" value="'.$pagination.'" />
				<input type="hidden" name="page_number" value="1" />
				<a href="#" id="reverse_sorting" class="ui-icon product_asc_listing" title="'.__('Reverse','wpshop').'">&nbsp;</a>
				<img src="'.WPSHOP_TEMPLATES_URL.'wpshop/medias/icones/list_display_mode.png" class="change_display_mode'.($type=='list'?' wpshopHide':null).'" id="list_display" alt="'.__('Change to list display','wpshop').'" title="'.__('Change to list display','wpshop').'" />
				<img src="'.WPSHOP_TEMPLATES_URL.'wpshop/medias/icones/grid_display_mode.png" class="change_display_mode'.($type=='grid'?' wpshopHide':null).'" id="grid_display" alt="'.__('Change to grid display','wpshop').'" title="'.__('Change to grid display','wpshop').'" />
				<span id="loading">&nbsp;</span>
			</div>
		';
		query_posts(array(
			'post_type' => 'wpshop_product',
			'posts_per_page' => $limit==0?$pagination:$limit, 
			'orderby' => $orderby, 
			'order' => $order,
			'paged' => 1
		));
			
		if (have_posts()) {			
			$string .= '<div id="products_list"><div>';
			if($atts['display']=='mini'){
				$string = '<ul>';
				while (have_posts()) : the_post();
					$string .= '<li>'.get_the_title().'</li>';
				endwhile;
				$string .= '</ul>';
				//wp_reset_query(); // important
			}
			else { 
				while (have_posts()) : the_post();
					$post_id = get_the_ID();
					$cats = get_the_terms($post_id, WPSHOP_NEWTYPE_IDENTIFIER_CATEGORIES);
					$cat_id = empty($cats) ? 0 : $cats[0]->term_id;
					$string .= self::product_mini_output($post_id, $cat_id, $type);
				endwhile;
				//wp_reset_query(); // important
			}
			$string .= '</div><br style="clear:left;" /></div>';
				
			// On calcule et on affiche la pagination
			if($limit==0) {
				$query = "SELECT COUNT(".$wpdb->prefix."posts.ID) FROM ".$wpdb->prefix."posts WHERE ".$wpdb->prefix."posts.post_type='wpshop_product' AND ".$wpdb->prefix."posts.post_status='publish'";
				$number_of_page = ceil($wpdb->get_var($query)/$pagination);
				$string .= '<ul class="pagination"><li class="active"><a href="#">1</a></li>';
				if($number_of_page>1) for($i=2;$i<=$number_of_page;$i++) $string .= '<li><a href="#">'.$i.'</a></li>';
				$string .= '</ul>';
			}
		}
		else {
			$string = '<p>'.__('Sorry, no product matched your criteria.', 'wpshop').'</p>';
		}
		wp_reset_query(); // important
			
		return do_shortcode($string);
	}
	
	function wpshop_get_product_by_criteria($criteria, $display_type, $order='ASC', $page_number, $products_per_page=0) {	
		global $wpdb;
			
		if($criteria=='title') $criteria='product_name';
		elseif($criteria=='price') $criteria='product_price_ttc';
		elseif($criteria=='date') $criteria='post_date';
		elseif($criteria=='stock') $criteria='product_stock';
		elseif($criteria=='rand') $criteria='RAND()';
		else $criteria='product_name';
		
		if($products_per_page>0)
			$limit = 'LIMIT '.(($page_number-1)*$products_per_page).','.$products_per_page;
		else $limit = null;
			
		$query = "
			SELECT ".$wpdb->prefix."posts.ID AS ID, ".$wpdb->prefix."posts.post_title AS product_name, ".$wpdb->prefix."posts.post_date AS post_date,
			
				(SELECT ATT_DEC.value FROM " . WPSHOP_DBT_ATTRIBUTE_VALUES_DECIMAL . " AS ATT_DEC
					INNER JOIN " . WPSHOP_DBT_ATTRIBUTE . " AS ATT ON (ATT.id = ATT_DEC.attribute_id)
				WHERE ATT_DEC.entity_id = ".$wpdb->prefix."posts.ID AND ATT.code = '" . WPSHOP_PRODUCT_PRICE_TTC . "') AS product_price_ttc,
				
				(SELECT ATT_DEC.value FROM " . WPSHOP_DBT_ATTRIBUTE_VALUES_DECIMAL . " AS ATT_DEC
					INNER JOIN " . WPSHOP_DBT_ATTRIBUTE . " AS ATT ON (ATT.id = ATT_DEC.attribute_id)
				WHERE ATT_DEC.entity_id = ".$wpdb->prefix."posts.ID AND ATT.code = 'product_stock') AS product_stock
				
			FROM ".$wpdb->prefix."posts
			WHERE 
				".$wpdb->prefix."posts.post_type='wpshop_product' AND 
				".$wpdb->prefix."posts.post_status='publish'
			ORDER BY ".$criteria." ".$order."
			".(!empty($limit)?$limit:null)."
		";
			
		$data = $wpdb->get_results($query,ARRAY_A);
		$string='';
		$display_type = (!empty($display_type) && in_array($display_type,array('grid','list'))) ? $display_type : 'grid';
		if (!empty($data)) {
			foreach($data as $p):
				$post_id = $p['ID'];
				$cats = get_the_terms($post_id, WPSHOP_NEWTYPE_IDENTIFIER_CATEGORIES);
				$cat_id = empty($cats) ? 0 : $cats[0]->term_id;
				$string .= self::product_mini_output($post_id, $cat_id, $display_type);
			endforeach;
		}
		return $string;
	}
	
	/** Reduce the product qty to the qty given in the arguments
	 * @return array
	*/
	function reduce_product_stock_qty($product_id, $qty) {
	
		global $wpdb;
		
		$product = self::get_product_data($product_id);
		if (!empty($product)) {
			$newQty = $product['product_stock']-$qty;
			if ($newQty >= 0) {
				$query = '
					SELECT wp_wpshop__attribute_value_decimal.value_id 
					FROM wp_wpshop__attribute_value_decimal
					LEFT JOIN wp_wpshop__attribute ON wp_wpshop__attribute_value_decimal.attribute_id = wp_wpshop__attribute.id
					WHERE 
						wp_wpshop__attribute_value_decimal.entity_id='.$product_id.' AND wp_wpshop__attribute.code="product_stock"
					LIMIT 1
				';
				$data = $wpdb->get_results($query);
				$value_id = $data[0]->value_id;
				// On met � jour le stock dans la base
				//$wpdb->query('UPDATE wp_wpshop__attribute_value_decimal SET wp_wpshop__attribute_value_decimal.value = '.wpshop_tools::wpshop_clean($newQty).' WHERE wp_wpshop__attribute_value_decimal.value_id='.$value_id);
				
				$update = $wpdb->update('wp_wpshop__attribute_value_decimal', array(
					'value' => wpshop_tools::wpshop_clean($newQty)
				), array(
					'value_id' => $$value_id
				));
			}
		}
	}
	
	/** Get the product data
	 * @return array or false
	*/
	function get_product_data($product_id) {
		global $wpdb;
		
		$query = '
			SELECT wp_posts.post_title, wp_posts.post_name FROM wp_posts
			WHERE 
				wp_posts.ID='.$product_id.' AND 
				wp_posts.post_type="wpshop_product" AND 
				wp_posts.post_status="publish"
			LIMIT 1
		';
		$products = $wpdb->get_results($query);
		
		if(!empty($products)) {
		
			$query = $wpdb->prepare("
			SELECT
				(SELECT ATT_DEC.value FROM " . WPSHOP_DBT_ATTRIBUTE_VALUES_DECIMAL . " AS ATT_DEC
					INNER JOIN " . WPSHOP_DBT_ATTRIBUTE . " AS ATT ON (ATT.id = ATT_DEC.attribute_id)
				WHERE ATT_DEC.entity_id = %d AND ATT.code = '" . WPSHOP_PRODUCT_PRICE_HT . "') AS product_price_ht,
					
				(SELECT ATT_DEC.value FROM " . WPSHOP_DBT_ATTRIBUTE_VALUES_DECIMAL . " AS ATT_DEC
					INNER JOIN " . WPSHOP_DBT_ATTRIBUTE . " AS ATT ON (ATT.id = ATT_DEC.attribute_id)
				WHERE ATT_DEC.entity_id = %d AND ATT.code = '" . WPSHOP_PRODUCT_PRICE_TTC . "') AS product_price_ttc,
					
				(SELECT ATT_DEC.value FROM " . WPSHOP_DBT_ATTRIBUTE_VALUES_DECIMAL . " AS ATT_DEC
					INNER JOIN " . WPSHOP_DBT_ATTRIBUTE . " AS ATT ON (ATT.id = ATT_DEC.attribute_id)
				WHERE ATT_DEC.entity_id = %d AND ATT.code = 'product_stock') AS product_stock,
				
				(SELECT ATT_OPT.value FROM ".WPSHOP_DBT_ATTRIBUTE_VALUE_OPTIONS." AS ATT_OPT WHERE id = (
					SELECT ATT_INT.value FROM " . WPSHOP_DBT_ATTRIBUTE_VALUES_INTEGER . " AS ATT_INT
					INNER JOIN " . WPSHOP_DBT_ATTRIBUTE . " AS ATT ON (ATT.id = ATT_INT.attribute_id)
					WHERE ATT_INT.entity_id = %d AND ATT.code = '" . WPSHOP_PRODUCT_PRICE_TAX . "')) AS product_tax_rate,
					
				(SELECT ATT_DEC.value FROM " . WPSHOP_DBT_ATTRIBUTE_VALUES_DECIMAL . " AS ATT_DEC
					INNER JOIN " . WPSHOP_DBT_ATTRIBUTE . " AS ATT ON (ATT.id = ATT_DEC.attribute_id)
				WHERE ATT_DEC.entity_id = %d AND ATT.code = '" . WPSHOP_PRODUCT_PRICE_TAX_AMOUNT . "') AS product_tax_amount,
				
				(SELECT ATT_VAR.value FROM " . WPSHOP_DBT_ATTRIBUTE_VALUES_VARCHAR . " AS ATT_VAR
					INNER JOIN " . WPSHOP_DBT_ATTRIBUTE . " AS ATT ON (ATT.id = ATT_VAR.attribute_id)
				WHERE ATT_VAR.entity_id = %d AND ATT.code = 'product_reference') AS product_reference,
				
				(SELECT ATT_DEC.value FROM " . WPSHOP_DBT_ATTRIBUTE_VALUES_DECIMAL . " AS ATT_DEC
					INNER JOIN " . WPSHOP_DBT_ATTRIBUTE . " AS ATT ON (ATT.id = ATT_DEC.attribute_id)
				WHERE ATT_DEC.entity_id = %d AND ATT.code = 'cost_of_postage') AS product_shipping_cost,
				
				(SELECT ATT_OPT.value FROM ".WPSHOP_DBT_ATTRIBUTE_VALUE_OPTIONS." AS ATT_OPT WHERE id = (
					SELECT ATT_INT.value FROM " . WPSHOP_DBT_ATTRIBUTE_VALUES_INTEGER . " AS ATT_INT
					INNER JOIN " . WPSHOP_DBT_ATTRIBUTE . " AS ATT ON (ATT.id = ATT_INT.attribute_id)
					WHERE ATT_INT.entity_id = %d AND ATT.code = 'declare_new')) AS product_declare_new,
				
				(SELECT ATT_DATETIME.value FROM " . WPSHOP_DBT_ATTRIBUTE_VALUES_DATETIME . " AS ATT_DATETIME
					INNER JOIN " . WPSHOP_DBT_ATTRIBUTE . " AS ATT ON (ATT.id = ATT_DATETIME.attribute_id)
				WHERE ATT_DATETIME.entity_id = %d AND ATT.code = 'set_new_from') AS product_set_new_from,
				
				(SELECT ATT_DATETIME.value FROM " . WPSHOP_DBT_ATTRIBUTE_VALUES_DATETIME . " AS ATT_DATETIME
					INNER JOIN " . WPSHOP_DBT_ATTRIBUTE . " AS ATT ON (ATT.id = ATT_DATETIME.attribute_id)
				WHERE ATT_DATETIME.entity_id = %d AND ATT.code = 'set_new_to') AS product_set_new_to,
				
				(SELECT ATT_DEC.value FROM " . WPSHOP_DBT_ATTRIBUTE_VALUES_DECIMAL . " AS ATT_DEC
					INNER JOIN " . WPSHOP_DBT_ATTRIBUTE . " AS ATT ON (ATT.id = ATT_DEC.attribute_id)
				WHERE ATT_DEC.entity_id = %d AND ATT.code = 'product_weight') AS product_weight
				
			", $product_id, $product_id, $product_id, $product_id, $product_id, $product_id, $product_id, $product_id, $product_id, $product_id, $product_id);
			
			$data = $wpdb->get_results($query);
			
			return array(
				'post_name'=> $products[0]->post_name,
				'product_reference' => !empty($data[0]->product_reference) ? $data[0]->product_reference : 0,
				'product_name' => $products[0]->post_title,
				'product_price_ht' => !empty($data[0]->product_price_ht) ? $data[0]->product_price_ht : 0,
				'product_price_ttc' => !empty($data[0]->product_price_ttc) ? $data[0]->product_price_ttc : 0,
				'product_tax_rate' => !empty($data[0]->product_tax_rate) ? $data[0]->product_tax_rate : 0,
				'product_tax_amount' => !empty($data[0]->product_tax_amount) ? $data[0]->product_tax_amount : 0,
				'product_stock' => !empty($data[0]->product_stock) ? $data[0]->product_stock : 0,
				'product_shipping_cost' => !empty($data[0]->product_shipping_cost) ? $data[0]->product_shipping_cost : 0,
				'product_declare_new' => !empty($data[0]->product_declare_new) ? $data[0]->product_declare_new : 0,
				'product_set_new_from' => !empty($data[0]->product_set_new_from) ? $data[0]->product_set_new_from : 0,
				'product_set_new_to' => !empty($data[0]->product_set_new_to) ? $data[0]->product_set_new_to : 0,
				'product_weight' => !empty($data[0]->product_weight) ? $data[0]->product_weight : 0
			);
		}
		else return false;
	}
	
	function duplicate_the_product($pid) {
		global $wpdb;
		
		// Get the product post info
		$query_posts = $wpdb->prepare('SELECT * FROM '.$wpdb->prefix.'posts WHERE ID='.$pid);
		$data_posts = $wpdb->get_row($query_posts,ARRAY_A);
		$data_posts['ID'] = NULL;
		$data_posts['post_date'] = date('Y-m-d H:i:s');
		$data_posts['post_date_gmt'] = date('Y-m-d H:i:s');
		$data_posts['post_modified'] = date('Y-m-d H:i:s');
		$data_posts['post_modified_gmt'] = date('Y-m-d H:i:s');
		$data_posts['guid'] = NULL;
			
		// Get others features like thumbnails
		$query_posts_more = $wpdb->prepare('SELECT * FROM '.$wpdb->prefix.'posts WHERE post_parent='.$pid.' AND post_type="attachment"');
		$data_posts_more = $wpdb->get_results($query_posts_more,ARRAY_A);
		
		// Postmeta
		$postmeta = get_post_meta($pid,WPSHOP_PRODUCT_ATTRIBUTE_META_KEY, true);
		$related_products = get_post_meta($pid,WPSHOP_PRODUCT_RELATED_PRODUCTS, true);
		// Datetime	
		$query_eav_datetime = $wpdb->prepare('SELECT * FROM '.WPSHOP_DBT_ATTRIBUTE_VALUES_DATETIME.' WHERE entity_id='.$pid);
		$data_eav_datetime = $wpdb->get_results($query_eav_datetime,ARRAY_A);
		// Decimal		
		$query_eav_decimal = $wpdb->prepare('SELECT * FROM '.WPSHOP_DBT_ATTRIBUTE_VALUES_DECIMAL.' WHERE entity_id='.$pid);
		$data_eav_decimal = $wpdb->get_results($query_eav_decimal,ARRAY_A);
		// Integer		
		$query_eav_integer = $wpdb->prepare('SELECT * FROM '.WPSHOP_DBT_ATTRIBUTE_VALUES_INTEGER.' WHERE entity_id='.$pid);
		$data_eav_integer = $wpdb->get_results($query_eav_integer,ARRAY_A);
		// Options		
		$query_eav_options = $wpdb->prepare('SELECT * FROM '.WPSHOP_DBT_ATTRIBUTE_VALUES_OPTIONS.' WHERE entity_id='.$pid);
		$data_eav_options = $wpdb->get_results($query_eav_options,ARRAY_A);
		// Text	
		$query_eav_text = $wpdb->prepare('SELECT * FROM '.WPSHOP_DBT_ATTRIBUTE_VALUES_TEXT.' WHERE entity_id='.$pid);
		$data_eav_text = $wpdb->get_results($query_eav_text,ARRAY_A);
		// Varchar	
		$query_eav_varchar = $wpdb->prepare('SELECT * FROM '.WPSHOP_DBT_ATTRIBUTE_VALUES_VARCHAR.' WHERE entity_id='.$pid);
		$data_eav_varchar = $wpdb->get_results($query_eav_varchar,ARRAY_A);
		
		$wpdb->insert($wpdb->prefix.'posts', $data_posts);
		$new_pid = $wpdb->insert_id;
		
		// Replace the old product id by the new one
		foreach($data_posts_more as $k=>$v) {
			$data_posts_more[$k]['ID'] = NULL;
			$data_posts_more[$k]['post_parent'] = $new_pid;
			$data_posts_more[$k]['post_date'] = date('Y-m-d H:i:s');
			$data_posts_more[$k]['post_date_gmt'] = date('Y-m-d H:i:s');
			$data_posts_more[$k]['post_modified'] = date('Y-m-d H:i:s');
			$data_posts_more[$k]['post_modified_gmt'] = date('Y-m-d H:i:s');
			$wpdb->insert($wpdb->prefix.'posts', $data_posts_more[$k]);
		}
		
		update_post_meta($new_pid, WPSHOP_PRODUCT_ATTRIBUTE_META_KEY, $postmeta);
		update_post_meta($new_pid, WPSHOP_PRODUCT_RELATED_PRODUCTS, $related_products);
		
		// Replace the old product id by the new one
		foreach($data_eav_datetime as $k=>$v) {
			$data_eav_datetime[$k]['value_id'] = NULL;
			$data_eav_datetime[$k]['entity_id'] = $new_pid;
			$wpdb->insert(WPSHOP_DBT_ATTRIBUTE_VALUES_DATETIME, $data_eav_datetime[$k]);
		}
		foreach($data_eav_decimal as $k=>$v) {
			$data_eav_decimal[$k]['value_id'] = NULL;
			$data_eav_decimal[$k]['entity_id'] = $new_pid;
			$wpdb->insert(WPSHOP_DBT_ATTRIBUTE_VALUES_DECIMAL, $data_eav_decimal[$k]);
		}
		foreach($data_eav_integer as $k=>$v) {
			$data_eav_integer[$k]['value_id'] = NULL;
			$data_eav_integer[$k]['entity_id'] = $new_pid;
			$wpdb->insert(WPSHOP_DBT_ATTRIBUTE_VALUES_INTEGER, $data_eav_integer[$k]);
		}
		foreach($data_eav_options as $k=>$v) {
			$data_eav_options[$k]['value_id'] = NULL;
			$data_eav_options[$k]['entity_id'] = $new_pid;
			$wpdb->insert(WPSHOP_DBT_ATTRIBUTE_VALUES_OPTIONS, $data_eav_options[$k]);
		}
		foreach($data_eav_text as $k=>$v) {
			$data_eav_text[$k]['value_id'] = NULL;
			$data_eav_text[$k]['entity_id'] = $new_pid;
			$wpdb->insert(WPSHOP_DBT_ATTRIBUTE_VALUES_TEXT, $data_eav_text[$k]);
		}
		foreach($data_eav_varchar as $k=>$v) {
			$data_eav_varchar[$k]['value_id'] = NULL;
			$data_eav_varchar[$k]['entity_id'] = $new_pid;
			$wpdb->insert(WPSHOP_DBT_ATTRIBUTE_VALUES_VARCHAR, $data_eav_varchar[$k]);
		}
	}
	
	/**
	* Retourne une liste de produit
	* @param boolean $formated : formatage du r�sultat oui/non
	* @param string $product_search : recherche demand�e
	* @return mixed
	**/
	function product_list($formated=false, $product_search=null) {
		global $wpdb;
		if(!empty($product_search)) {
			if(is_array($product_search)) {
				$query = '
					SELECT ID, post_title FROM '.$wpdb->prefix.'posts 
					WHERE post_type="wpshop_product" AND post_status="publish" AND ID IN ('.implode(",",$product_search).')
				';
			}
			else {
			$query = '
					SELECT ID, post_title FROM '.$wpdb->prefix.'posts 
					WHERE post_type="wpshop_product" AND post_status="publish" AND post_title LIKE "%'.$product_search.'%"
				';
			}
		}
		else {
			$query = 'SELECT ID, post_title FROM '.$wpdb->prefix.'posts WHERE post_type="wpshop_product" AND post_status="publish"';
		}
		$data = $wpdb->get_results($query);
		
		// Si le formatage est demand�
		if($formated) {
			$product_string='';
			foreach($data as $d) {
				$product_string.= '<li><label><input type="checkbox" value="'.$d->ID.'" name="products[]" /> '.$d->post_title.'</label></li>';
			}
		}
		return $formated?$product_string:$data;
	}
	
	/**
	* Retourne une liste d'attributs pour chaque produit
	* @param boolean $formated : formatage du r�sultat oui/non
	* @param string $product_search : recherche demand�e
	* @return mixed
	**/
	function product_list_attr($formated=false, $product_search=null) {
		global $wpdb;
		$query = '
		SELECT '.WPSHOP_DBT_ATTRIBUTE.'.data_type, '.WPSHOP_DBT_ATTRIBUTE.'.id AS id_attribut, '.$wpdb->prefix.'posts.post_title, '.$wpdb->prefix.'posts.ID, '.WPSHOP_DBT_ATTRIBUTE.'.frontend_label, '.WPSHOP_DBT_ATTRIBUTE_VALUES_DECIMAL.'.value AS value_decimal, '.WPSHOP_DBT_ATTRIBUTE_VALUES_DATETIME.'.value AS value_datetime, '.WPSHOP_DBT_ATTRIBUTE_VALUES_INTEGER.'.value AS value_integer, '.WPSHOP_DBT_ATTRIBUTE_VALUES_TEXT.'.value AS value_text, '.WPSHOP_DBT_ATTRIBUTE_VALUES_VARCHAR.'.value AS value_varchar, '.WPSHOP_DBT_ATTRIBUTE_UNIT.'.unit AS unit
		FROM '.WPSHOP_DBT_ATTRIBUTE_DETAILS.'
			LEFT JOIN '.WPSHOP_DBT_ATTRIBUTE.' ON '.WPSHOP_DBT_ATTRIBUTE_DETAILS.'.attribute_id='.WPSHOP_DBT_ATTRIBUTE.'.id
			
			LEFT JOIN '.WPSHOP_DBT_ATTRIBUTE_VALUES_DECIMAL.' ON '.WPSHOP_DBT_ATTRIBUTE_VALUES_DECIMAL.'.attribute_id='.WPSHOP_DBT_ATTRIBUTE.'.id
			LEFT JOIN '.WPSHOP_DBT_ATTRIBUTE_VALUES_DATETIME.' ON '.WPSHOP_DBT_ATTRIBUTE_VALUES_DATETIME.'.attribute_id='.WPSHOP_DBT_ATTRIBUTE.'.id
			LEFT JOIN '.WPSHOP_DBT_ATTRIBUTE_VALUES_INTEGER.' ON '.WPSHOP_DBT_ATTRIBUTE_VALUES_INTEGER.'.attribute_id='.WPSHOP_DBT_ATTRIBUTE.'.id
			LEFT JOIN '.WPSHOP_DBT_ATTRIBUTE_VALUES_TEXT.' ON '.WPSHOP_DBT_ATTRIBUTE_VALUES_TEXT.'.attribute_id='.WPSHOP_DBT_ATTRIBUTE.'.id
			LEFT JOIN '.WPSHOP_DBT_ATTRIBUTE_VALUES_VARCHAR.' ON '.WPSHOP_DBT_ATTRIBUTE_VALUES_VARCHAR.'.attribute_id='.WPSHOP_DBT_ATTRIBUTE.'.id
			
			LEFT JOIN '.$wpdb->prefix.'posts ON (
				'.WPSHOP_DBT_ATTRIBUTE_VALUES_DECIMAL.'.entity_id='.$wpdb->prefix.'posts.ID
				OR '.WPSHOP_DBT_ATTRIBUTE_VALUES_DATETIME.'.entity_id='.$wpdb->prefix.'posts.ID
				OR '.WPSHOP_DBT_ATTRIBUTE_VALUES_INTEGER.'.entity_id='.$wpdb->prefix.'posts.ID
				OR '.WPSHOP_DBT_ATTRIBUTE_VALUES_TEXT.'.entity_id='.$wpdb->prefix.'posts.ID
				OR '.WPSHOP_DBT_ATTRIBUTE_VALUES_VARCHAR.'.entity_id='.$wpdb->prefix.'posts.ID
			)
			
			LEFT JOIN '.WPSHOP_DBT_ATTRIBUTE_UNIT.' ON (
				'.WPSHOP_DBT_ATTRIBUTE_UNIT.'.id='.WPSHOP_DBT_ATTRIBUTE_VALUES_DECIMAL.'.unit_id 
				OR '.WPSHOP_DBT_ATTRIBUTE_UNIT.'.id='.WPSHOP_DBT_ATTRIBUTE_VALUES_DATETIME.'.unit_id
				OR '.WPSHOP_DBT_ATTRIBUTE_UNIT.'.id='.WPSHOP_DBT_ATTRIBUTE_VALUES_INTEGER.'.unit_id
				OR '.WPSHOP_DBT_ATTRIBUTE_UNIT.'.id='.WPSHOP_DBT_ATTRIBUTE_VALUES_TEXT.'.unit_id
				OR '.WPSHOP_DBT_ATTRIBUTE_UNIT.'.id='.WPSHOP_DBT_ATTRIBUTE_VALUES_VARCHAR.'.unit_id
			)
		WHERE 
			'.WPSHOP_DBT_ATTRIBUTE_DETAILS.'.status="valid"
			AND '.WPSHOP_DBT_ATTRIBUTE.'.status="valid"
			AND '.$wpdb->prefix.'posts.post_type="'.WPSHOP_NEWTYPE_IDENTIFIER_PRODUCT.'" AND '.$wpdb->prefix.'posts.post_status="publish"
			'.(!empty($product_search)?'AND '.$wpdb->prefix.'posts.post_title LIKE "%'.$product_search.'%"':null).'
		';
		$data = $wpdb->get_results($query);
		$products=array();
		foreach($data as $d) {
			if(!isset($products[$d->ID])) {
				$products[$d->ID]['id'] = $d->ID;
				$products[$d->ID]['name'] = $d->post_title;
			}
			$products[$d->ID]['attributs'][]=array(
				'id' => $d->id_attribut,
				'type' => $d->data_type,
				'label' => __($d->frontend_label, 'wpshop'), 
				'value' => $d->value_decimal.$d->value_datetime.$d->value_integer.$d->value_text.$d->value_varchar,
				'unit' => $d->unit
			);
		}
		unset($data);
		
		// Si le formatage est demand�
		if($formated) {
			$products_attr_string='';
			foreach($products as $p) {
				$products_attr_string .= '<li><b>'.$p['name'].'</b>
						<ul>';
				foreach($p['attributs'] as $p2) {
					$products_attr_string .= '<li><label><input type="checkbox" value="'.$p['id'].'-'.$p2['id'].'-'.$p2['type'].'" name="attributs[]" /> '.$p2['label'].'</label></li>';
				}
				$products_attr_string .= '</ul></li>';
			}
		}
		
		return $formated?$products_attr_string:$products;
	}
	
	/**
	* Retourne une liste de groupe d'attributs
	* @param boolean $formated : formatage du r�sultat oui/non
	* @param string $product_search : recherche demand�e
	* @return mixed
	**/
	function product_list_group_attr($formated=false, $product_search=null) {
		global $wpdb;
		$query = '
			SELECT '.WPSHOP_DBT_ATTRIBUTE_GROUP.'.id, '.WPSHOP_DBT_ATTRIBUTE_GROUP.'.code, '.WPSHOP_DBT_ATTRIBUTE_GROUP.'.name
			FROM '.WPSHOP_DBT_ATTRIBUTE_GROUP.'
			WHERE '.WPSHOP_DBT_ATTRIBUTE_GROUP.'.status="valid"
		';
		$data = $wpdb->get_results($query);
		
		// Si le formatage est demand�
		if($formated) {
			$products = self::product_list(false, $product_search);
			$groups_string='';
			foreach($products as $d) {
				$groups_string .= '<li><b>'.$d->post_title.'</b>
						<ul>';
				foreach($data as $g) {
					$groups_string .= '<li><label><input type="checkbox" value="'.$d->ID.'-'.$g->id.'" name="groups[]" /> '.__($g->name, 'wpshop').'</label></li>';
				}
				$groups_string .= '</ul></li>';
			}
		}
		
		return $formated?$groups_string:$data;
	}
	/**
	*	Define the content of the product actions
	*/
	function product_actions_meta_box_content(){
		global $currentTabContent,$post;
		
		echo '<input type="hidden" name="pid" value="'.$post->ID.'" /><a class="button" href="#" id="duplicate_the_product">'.__('Duplicate the product', 'wpshop').'</a>';
	}
	
	/**
	*	Define the content of the product main information box
	*/
	function main_information_meta_box_content(){
		global $currentTabContent,$post;

		add_action('admin_footer', array('wpshop_init', 'admin_js_footer'));

		/*	Add the extra fields defined by the default attribute group in the general section	*/
			/*	Get the general attribute set for outputting the result	*/
		if(is_array($currentTabContent['generalTabContent'])){
			$the_form_general_content .= implode('
			', $currentTabContent['generalTabContent']);

			$input_def['id'] = 'product_attribute_set_id';
			$input_def['name'] = 'product_attribute_set_id';
			$input_def['value'] = '';
			$input_def['type'] = 'hidden';
			$input_def['value'] = get_post_meta($post->ID, WPSHOP_PRODUCT_ATTRIBUTE_SET_ID_META_KEY, true);
			if($input_def['value'] == ''){
				$attribute_set_id = wpshop_attributes::get_attribute_value_content('product_attribute_set_id', $post->ID, self::currentPageCode);
				if($attribute_set_id > 0){
					$input_def['value'] = $attribute_set_id->value;
				}
				else{
					$attributeEntitySetList = wpshop_attributes_set::get_attribute_set_list_for_entity(wpshop_entities::get_entity_identifier_from_code(self::currentPageCode));
					$input_def['value'] = $attributeEntitySetList[0]->id;
				}
			}
			$the_form_general_content .= wpshop_form::check_input_type($input_def, self::currentPageCode);

			echo '
			<div><strong>'.__('Product shortcode').'</strong> - <a href="#" class="show-hide-shortcodes">Afficher</a>
				<div class="shortcodes_container wpshopHide"><br />
				
					<label>'.__('Product insertion code', 'wpshop').'</label> 
					<code>[wpshop_product pid="'.$post->ID.'" type="list"]</code> '.__('or', 'wpshop').' <code>[wpshop_product pid="'.$post->ID.'" type="grid"]</code> '.__('or', 'wpshop').'<br /><br />
					
					<label>'.__('Product insertion PHP code', 'wpshop').'</label>
					<code>&lt;?php echo do_shortcode(\'[wpshop_product pid="'.$post->ID.'" type="list"]\'); ?></code> '.__('or', 'wpshop').' <code>&lt;?php echo do_shortcode(\'[wpshop_product pid="'.$post->ID.'" type="grid"]\'); ?></code>
				
				</div>
			</div><br />
			<div class="wpshop_extra_field_container" >' . $the_form_general_content . '</div>';
		}
	}
	/**
	*	Define the metabox for managing products pictures
	*/
	function meta_box_picture($post, $metaboxArgs){
		global $post;
		$product_picture_galery_metabox_content = '';

		$product_picture_galery_metabox_content = '
<a href="media-upload.php?post_id=' . $post->ID . '&amp;type=image&amp;TB_iframe=1&amp;width=640&amp;height=566" class="thickbox" title="Manage Your Product Images" >' . __('Add pictures for the product', 'wpshop' ) . '</a>
<div class="alignright reload_box_attachment" ><img src="' . WPSHOP_MEDIAS_ICON_URL . 'reload_vs.png" alt="' . __('Reload the box', 'wpshop') . '" title="' . __('Reload the box', 'wpshop') . '" class="reload_attachment_box" id="reload_box_picture" /></div>
<ul id="product_picture_list" class="product_attachment_list clear" >' . self::product_attachement_by_type($post->ID, 'image/', 'media-upload.php?post_id=' . $post->ID . '&amp;tab=library&amp;type=image&amp;TB_iframe=1&amp;width=640&amp;height=566') . '</ul>
<script type="text/javascript" >
	wpshop(document).ready(function(){
		jQuery(".reload_box_attachment img").click(function(){
			jQuery(this).attr("src", "' . admin_url('images/loading.gif') . '");
			jQuery("#product_picture_list").load(WPSHOP_AJAX_FILE_URL,{
				"post": "true",
				"elementCode": "product_attachment",
				"elementIdentifier": "' . $post->ID . '",
				"elementType": "product",
				"attachement_type": "image/",
				"part_to_reload": "reload_box_picture"
			});
		});
	});
</script>';

		echo $product_picture_galery_metabox_content;
	}
	/**
	*	Define the metabox for managing products documents
	*/
	function meta_box_document($post, $metaboxArgs){
		global $post;
		$product_document_galery_metabox_content = '';

		$product_document_galery_metabox_content = '
<a href="media-upload.php?post_id=' . $post->ID . '&amp;TB_iframe=1&amp;width=640&amp;height=566" class="thickbox clear" title="Manage Your Product Document" >' . __('Add documents for the document', 'wpshop' ) . '</a> (Seuls les documents <i>.pdf</i> seront pris en compte)
<div class="alignright reload_box_attachment" ><img src="' . WPSHOP_MEDIAS_ICON_URL . 'reload_vs.png" alt="' . __('Reload the box', 'wpshop') . '" title="' . __('Reload the box', 'wpshop') . '" class="reload_attachment_box" id="reload_box_document" /></div>
<ul id="product_document_list" class="product_attachment_list clear" >' . self::product_attachement_by_type($post->ID, 'application/pdf', 'media-upload.php?post_id=' . $post->ID . '&amp;tab=library&amp;TB_iframe=1&amp;width=640&amp;height=566') . '</ul>
<script type="text/javascript" >
	wpshop(document).ready(function(){
		jQuery(".reload_box_attachment img").click(function(){
			jQuery(this).attr("src", "' . admin_url('images/loading.gif') . '");
			jQuery("#product_document_list").load(WPSHOP_AJAX_FILE_URL,{
				"post": "true",
				"elementCode": "product_attachment",
				"elementIdentifier": "' . $post->ID . '",
				"elementType": "product",
				"attachement_type": "application/pdf",
				"part_to_reload": "reload_box_document"
			});
		});
	});
</script>';

		echo $product_document_galery_metabox_content;
	}
	
	/**
	*	Define the content of the product main information box
	*/
	function meta_box_content($post, $metaboxArgs){
		global $currentTabContent;

		/*	Add the extra fields defined by the default attribute group in the general section	*/
		echo '<div class="wpshop_extra_field_container" >' . $currentTabContent['boxContent'][$metaboxArgs['args']['boxIdentifier']] . '</div>';
	}


	/**
	*	Save the different values for the attributes affected to the product
	*/
	function save_product_custom_informations(){
		global $wpdb;
		if(isset($_REQUEST[self::currentPageCode . '_attribute']) && (count($_REQUEST[self::currentPageCode . '_attribute']) > 0)){

			/*	Fill the product reference automatically if nothing is sent	*/
			if(trim($_REQUEST[self::currentPageCode . '_attribute']['varchar']['product_reference']) == ''){
				$query = $wpdb->prepare("SELECT MAX(ID) AS PDCT_ID FROM " . $wpdb->posts);
				$last_ref = $wpdb->get_var($query);
				$_REQUEST[self::currentPageCode . '_attribute']['varchar']['product_reference'] = WPSHOP_PRODUCT_REFERENCE_PREFIX . str_repeat(0, WPSHOP_PRODUCT_REFERENCE_PREFIX_NB_FILL) . $last_ref;
			}

			// Traduction des virgule en point pour la base de donn�es!
			foreach($_REQUEST[self::currentPageCode . '_attribute']['decimal'] as $attributeName => $attributeValue){
				/*	Check the product price before saving into database	*/
				if((WPSHOP_PRODUCT_PRICE_PILOT == 'HT') && ($attributeName == WPSHOP_PRODUCT_PRICE_HT)){
					$ht_amount = str_replace(',', '.', $attributeValue);
					$query = $wpdb->prepare("SELECT value FROM " . WPSHOP_DBT_ATTRIBUTE_VALUE_OPTIONS . " WHERE id = %d", $_REQUEST[self::currentPageCode . '_attribute']['integer'][WPSHOP_PRODUCT_PRICE_TAX]);
					$tax_rate = 1 + ($wpdb->get_var($query) / 100);

					$_REQUEST[self::currentPageCode . '_attribute']['decimal'][WPSHOP_PRODUCT_PRICE_TTC] = $ht_amount * $tax_rate;
					$_REQUEST[self::currentPageCode . '_attribute']['decimal'][WPSHOP_PRODUCT_PRICE_TAX_AMOUNT] = $_REQUEST[self::currentPageCode . '_attribute']['decimal'][WPSHOP_PRODUCT_PRICE_TTC] - $ht_amount;
				}
				if((WPSHOP_PRODUCT_PRICE_PILOT == 'TTC') && ($attributeName == WPSHOP_PRODUCT_PRICE_TTC)){
					$ttc_amount = str_replace(',', '.', $attributeValue);
					$query = $wpdb->prepare("SELECT value FROM " . WPSHOP_DBT_ATTRIBUTE_VALUE_OPTIONS . " WHERE id = %d", $_REQUEST[self::currentPageCode . '_attribute']['integer'][WPSHOP_PRODUCT_PRICE_TAX]);
					$tax_rate = 1 + ($wpdb->get_var($query) / 100);

					$_REQUEST[self::currentPageCode . '_attribute']['decimal'][WPSHOP_PRODUCT_PRICE_HT] = $ttc_amount / $tax_rate;
					$_REQUEST[self::currentPageCode . '_attribute']['decimal'][WPSHOP_PRODUCT_PRICE_TAX_AMOUNT] = $attributeValue - $_REQUEST[self::currentPageCode . '_attribute']['decimal'][WPSHOP_PRODUCT_PRICE_HT];
				}

				if(!is_array($attributeValue)){
					$_REQUEST[self::currentPageCode . '_attribute']['decimal'][$attributeName] = str_replace(',','.',$_REQUEST[self::currentPageCode . '_attribute']['decimal'][$attributeName]);
				}
			}

			/*	Save the attributes values into wpshop eav database	*/
			wpshop_attributes::saveAttributeForEntity($_REQUEST[self::currentPageCode . '_attribute'], wpshop_entities::get_entity_identifier_from_code(self::currentPageCode), $_REQUEST['post_ID'], get_locale());

			/*	Save the attributes values into wordpress post metadata database in order to have a backup and to make frontend search working	*/
			$productMetaDatas = array();
			foreach($_REQUEST[self::currentPageCode . '_attribute'] as $attributeType => $attributeValues){
				foreach($attributeValues as $attributeCode => $attributeValue){
					$productMetaDatas[$attributeCode] = $attributeValue;
				}
			}
			update_post_meta($_REQUEST['post_ID'], WPSHOP_PRODUCT_ATTRIBUTE_META_KEY, $productMetaDatas);
		}

		/*	Update the attribute set id for the current product	*/
		update_post_meta($_REQUEST['post_ID'], WPSHOP_PRODUCT_ATTRIBUTE_SET_ID_META_KEY, $_REQUEST[self::currentPageCode]['product_attribute_set_id']);
		
		/*	Update the related products list*/
		if(isset($_REQUEST['related_products_list'])) {
			$products_id = explode(',', $_REQUEST['related_products_list']);
			$products_id = array_unique($products_id);
			update_post_meta($_REQUEST['post_ID'], WPSHOP_PRODUCT_RELATED_PRODUCTS, $products_id);
		}

		flush_rewrite_rules();
	}
	/**
	*	Allows to define a specific permalink for each product by checking the parent categories
	*
	*	@param mixed $permalink The actual permalink of the element
	* @param object $post The post we want to set the permalink for
	*	@param void
	*
	*	@return mixed The new permalink for the current element
	*/
	function set_product_permalink($permalink, $post, $unknown){
		global $wp_query;

		$product_category_slug = WPSHOP_UNCATEGORIZED_PRODUCT_SLUG;

		if ($post->post_type != WPSHOP_NEWTYPE_IDENTIFIER_PRODUCT) 
			return $permalink;

		$product_categories = wp_get_object_terms( $post->ID, WPSHOP_NEWTYPE_IDENTIFIER_CATEGORIES );
		if(count($product_categories) == 1){/*	Product has only one category we get the only available slug	*/
			$product_category_slug = $product_categories[0]->slug;
		}
		else{																/*	Product has several categories choose the slug of the we want	*/
			$product_category_slugs = array();
			foreach($product_categories as $product_category){
				$product_category_slugs[] = $product_category->slug;
			}
			$product_category_slug = self::currentPageCode;
		}

		$permalink = str_replace('%' . WPSHOP_NEWTYPE_IDENTIFIER_CATEGORIES . '%', $product_category_slug, $permalink);
		return apply_filters('wpshop_' . WPSHOP_NEWTYPE_IDENTIFIER_PRODUCT . '_permalink', $permalink, $post->ID );
	}


	/**
	*	Get the aproduct attachement list for a given product and a given attachement type
	*
	*	@param string $attachement_type The attachement type we want to get for the product
	*
	*	@return mixed $product_attachement_list The attachement list for the current product and for the defined type
	*/
	function product_attachement_by_type($product_id, $attachement_type = 'image/', $url_on_click = ''){
		$product_attachement_list = '';

		$attachments = get_posts(array('post_type' => 'attachment', 'numberposts' => -1, 'post_status' => null, 'post_parent' => $product_id));
		if(is_array($attachments) && (count($attachments) > 0)){
			$product_thumbnail = get_post_thumbnail_id($product_id);
			$attachmentsNumber = 0;
			foreach ($attachments as $attachment){
				if(is_int(strpos($attachment->post_mime_type, $attachement_type))){
					$url = $attachment->guid;
					$link_option = '';
					if($url_on_click != ''){
						$url = $url_on_click;
						$link_option = ' class="thickbox" ';
					}
					/*	Build the attachment output with the different parameters	*/
					$attachment_icon = 0;
					$attachement_more_informations = '';
					if($attachement_type == 'image/'){
						if($link_option == ''){
							$link_option = 'rel="appendix"';
						}
						$li_class = "product_picture_item";
						if($product_thumbnail == $attachment->ID){
							$attachement_more_informations = '<br/><span class="product_thumbnail_indicator" >' . __('Product thumbnail', 'wpshop') . '</span>';
						}
					}
					else{
						if($link_option == ''){
							$link_option = 'target="product_document"';
						}
						$li_class = "product_document_item";
						$attachment_icon = 1;
						$attachement_more_informations = '<br/><span>' . $attachment->post_title . '</span>';
					}

					/*	Add the attchment to the list	*/
					$product_attachement_list .= '<li class="' . $li_class . '" ><a href="' . $url . '" ' . $link_option . ' >' . wp_get_attachment_image($attachment->ID, 'full', $attachment_icon) . '</a>' . $attachement_more_informations . '</li>';
					$attachmentsNumber++;
				}
			}

			if($attachmentsNumber <= 0){
				$product_attachement_list .= '<li class="product_document_item" >' . __('No attachement were found for this product', 'wpshop') . '</li>';
			}
		}
		return $product_attachement_list;
	}


	/**
	*	Define output for product
	*
	*	@param mixed $initialContent The initial product content defined into wordpress basic admin interface
	*	@param integer $product_id The product identifier we want to get and output attribute for
	*
	*	@return mixed $content The content to add or to modify the product output in frontend
	*/
	function product_complete_sheet_output($initialContent, $product_id){
		global $wp_query;
		$content = $attributeContentOutput = '';

		/*	Get the product thumbnail	*/
		if(has_post_thumbnail($product_id)){
			$thumbnail_url = wp_get_attachment_image_src(get_post_thumbnail_id(), 'full');
			$productThumbnail = '<a href="' . $thumbnail_url[0] . '" id="product_thumbnail" >' . get_the_post_thumbnail($product_id, 'thumbnail') . '</a>';
		}
		else{
			$productThumbnail = '<img src="' . WPSHOP_DEFAULT_PRODUCT_PICTURE . '" alt="product has no image" class="default_picture_thumbnail" />';
		}

		/*	Get attachement file for the current product	*/
		$product_picture_galery = $product_document_galery = '';
		$attachments = get_posts(array('post_type' => 'attachment', 'numberposts' => -1, 'post_status' => null, 'post_parent' => $product_id));
		if(is_array($attachments) && (count($attachments) > 0)){
			$picture_number = $document_number = 0;
			foreach ($attachments as $attachment){
				if(is_int(strpos($attachment->post_mime_type, 'image/'))){
					/*	Include the product sheet template	*/
					ob_start();
					require(wpshop_display::get_template_file('product_attachment_picture_line.tpl.php'));
					$product_attachment_main_galery = ob_get_contents();
					ob_end_clean();
					$product_picture_galery .= $product_attachment_main_galery;

					$picture_number++;
				}
				if(is_int(strpos($attachment->post_mime_type, 'application/pdf'))){
					/*	Include the product sheet template	*/
					ob_start();
					require(wpshop_display::get_template_file('product_attachment_document_line.tpl.php'));
					$product_attachment_main_galery = ob_get_contents();
					ob_end_clean();
					$product_document_galery .= $product_attachment_main_galery;
					$document_number++;
				}
			}
			if($picture_number > 0){
				$product_gallery_main_title = __('Associated pictures', 'wpshop');
				$gallery_type = 'product_picture';
				$gallery_content = $product_picture_galery;
				/*	Include the product sheet template	*/
				ob_start();
				require(wpshop_display::get_template_file('product_attachment_main_galery.tpl.php'));
				$product_attachment_main_galery = ob_get_contents();
				ob_end_clean();
				$product_picture_galery = $product_attachment_main_galery;
			}
			else{
				$product_picture_galery = '&nbsp;';
			}
			if($document_number > 0){
				$gallery_type = 'product_document';
				$product_gallery_main_title = __('Associated document', 'wpshop');
				$gallery_content = $product_document_galery;
				/*	Include the product sheet template	*/
				unset($product_attachment_main_galery);
				ob_start();
				require(wpshop_display::get_template_file('product_attachment_main_galery.tpl.php'));
				$product_attachment_main_galery = ob_get_contents();
				ob_end_clean();
				$product_document_galery = $product_attachment_main_galery;
			}
			else{
				$product_document_galery = '&nbsp;';
			}
		}

		/*	Get the different attribute affected to the product	*/
		$product_atribute_list = wpshop_attributes::getElementWithAttributeAndValue(wpshop_entities::get_entity_identifier_from_code(self::currentPageCode), $product_id, get_locale(), '', 'frontend');
		if(is_array($product_atribute_list) && (count($product_atribute_list) > 0)){
			foreach($product_atribute_list[$product_id] as $attributeSetSectionName => $attributeSetContent){
				$attributeToShowNumber = 0;
				$attributeOutput = '';
				foreach($attributeSetContent['attributes'] as $attributeId => $attributeDefinition){
					/*	Check the value type to check if empty or not	*/
					if($attributeDefinition['data_type'] == 'int'){
						$attributeDefinition['value'] = (int)$attributeDefinition['value'];
					}
					elseif($attributeDefinition['data_type'] == 'decimal'){
						$attributeDefinition['value'] = (float)$attributeDefinition['value'];
					}

					/*	Output the field if the value is not null	*/
					if((trim($attributeDefinition['value']) != '') && ($attributeDefinition['value'] > '0')){
						$attribute_unit_list = '';
						if(($attributeDefinition['unit'] != '')){
							$attribute_unit_list = '&nbsp;(' . $attributeDefinition['unit'] . ')';
						}
						$attribute_value = $attributeDefinition['value'];
						if($attributeDefinition['data_type'] == 'datetime'){
							$attribute_value = mysql2date('d/m/Y', $attributeDefinition['value'], true);
						}
						$attributeOutput .= '<li><span class="' . self::currentPageCode . '_frontend_attribute_label ' . $attributeDefinition['attribute_code'] . '_label" >' . __($attributeDefinition['frontend_label'], 'wpshop') . '</span>&nbsp;:&nbsp;<span class="' . self::currentPageCode . '_frontend_attribute_value ' . $attributeDefinition['attribute_code']. '_value" >' . $attribute_value . $attribute_unit_list . '</span></li>';

						$attributeToShowNumber++;
					}
				}
				
				$product_atribute_list[$product_id][$attributeSetSectionName]['count']=$attributeToShowNumber;
				$product_atribute_list[$product_id][$attributeSetSectionName]['output']=$attributeOutput;
			}
			
			// Gestion de l'affichage
			$tab_list = $content_list = '';
			foreach($product_atribute_list[$product_id] as $attributeSetSectionName => $attributeSetContent){
				if(!empty($attributeSetContent['count'])>0) {
						$tab_list .= '
						<li>
							<a href="#'.$attributeSetContent['code'].'">'.__($attributeSetSectionName, 'wpshop').'</a>
						</li>';
						$content_list .= '
							<div id="'.$attributeSetContent['code'].'">
								'.$attributeSetContent['output'].'
							</div>
						';
				}
			}
			if($tab_list != ''){
			$attributeContentOutput = '
				<div id="wpshopFormManagementContainer">
					<ul>' . $tab_list . '</ul>
					' . $content_list . '
				</div>';
			}

		}
		
		$product = self::get_product_data($product_id);
		$productPrice = $product['product_price_ttc'];
		$productStock = intval($product['product_stock']);
		$productCurrency = wpshop_tools::wpshop_get_currency();

		/*	Include the product sheet template	*/
		ob_start();
		require_once(wpshop_display::get_template_file('product.tpl.php'));
		$content = ob_get_contents();
		ob_end_clean();

		return $content;
	}
	/**
	*	Display a product not a list
	*/
	function product_mini_output($product_id, $category_id, $output_type = 'list'){
		$content = $product_information = '';

		/*	Get the product thumbnail	*/
		if(has_post_thumbnail($product_id)){
			$productThumbnail = get_the_post_thumbnail($product_id, 'thumbnail');
		}
		else $productThumbnail = '<img src="' . WPSHOP_DEFAULT_PRODUCT_PICTURE . '" alt="product has no image" class="default_picture_thumbnail" />';

		/*	Get the product information for output	*/
		$product = get_post($product_id);
		if(!empty($product)) {
			$product_title = $product->post_title;
			if($category_id==0)
				$product_link = 'catalog/product/' . $product->post_name;
			else $product_link = get_term_link((int)$category_id , WPSHOP_NEWTYPE_IDENTIFIER_CATEGORIES) . '/' . $product->post_name;
			$product_more_informations = $product->post_content;
			$product_excerpt = $product->post_excerpt;
			if(strpos($product->post_content, '<!--more-->')){
				$post_content = explode('<!--more-->', $product->post_content);
				$product_more_informations = $post_content[0];
			}
		}
		else{
			$productThumbnail = '<img src="' . WPSHOP_PRODUCT_NOT_EXIST . '" alt="product has no image" class="default_picture_thumbnail" />';
			$product_title = '<i>'.__('This product does not exist', 'wpshop').'</i>';
			$product_link = '';
			$product_more_informations = '';
			$product_excerpt = '';
		}
		
		$product = self::get_product_data($product_id);
		$productPrice = $product['product_price_ttc'];
		$productStock = intval($product['product_stock']);
		$productCurrency = wpshop_tools::wpshop_get_currency();
		$productCategory = get_the_category($product_id);
		
		$product_declare_new = $product['product_declare_new'];
		$product_set_new_from = $product['product_set_new_from'];
		$product_set_new_to = $product['product_set_new_to'];

		/*	Make some treatment in case we are in grid mode	*/
		if($output_type == 'grid'){
			/*	Determine the width of a component in a line grid	*/
			$element_width = (100 / WPSHOP_DISPLAY_GRID_ELEMENT_NUMBER_PER_LINE);
			$item_width = (round($element_width) - 1) . '%';
		}

		/*	Include the product sheet template	*/
		ob_start();
		require(wpshop_display::get_template_file('product-mini-' . $output_type . '.tpl.php'));
		$content = ob_get_contents();
		ob_end_clean();

		return $content;
	}

	/**
	*	Get the products (post) of a given category
	*
	*	@param string $category_slug The category slug we want to get the product list for
	*
	*	@return mixed $widget_content The output for the product list
	*/
	function get_product_of_category($category_slug, $category_id){
		global $top_categories;
		$widget_content = '';

		$args = array('post_type' => WPSHOP_NEWTYPE_IDENTIFIER_PRODUCT, WPSHOP_NEWTYPE_IDENTIFIER_CATEGORIES => $category_slug);
		$products = get_posts($args);
		if(is_array($products) && (count($products) > 0)){
			foreach($products as $product){
				ob_start();
				require(wpshop_display::get_template_file('categories_products-widget.tpl.php'));
				$widget_content .= ob_get_contents();
				ob_end_clean();
			}
		}

		echo $widget_content;
	}

}