<?php
/**
* Plugin librairies include file.
* 
*	This file will be called in every other file of the plugin and will include every library needed by the plugin to work correctly. If a file is needed in only one script prefer direct inclusion
* @author Eoxia <dev@eoxia.com>
* @version 1.1
* @package wpshop
* @subpackage includes
*/

include(WPSHOP_LIBRAIRIES_DIR . 'tools.class.php');


include(WPSHOP_LIBRAIRIES_DIR . 'doc.class.php');
include(WPSHOP_LIBRAIRIES_DIR . 'db/db_structure_definition.php');
include(WPSHOP_LIBRAIRIES_DIR . 'db/db_data_definition.php');

include(WPSHOP_LIBRAIRIES_DIR . 'init.class.php');

/*	Add needed file to the current theme	*/
add_action('init', array('wpshop_display', 'check_template_file'));

include(WPSHOP_LIBRAIRIES_DIR . 'permissions.class.php');
include(WPSHOP_LIBRAIRIES_DIR . 'options.class.php');
include(WPSHOP_LIBRAIRIES_DIR . 'display/display.class.php');
include(WPSHOP_LIBRAIRIES_DIR . 'display/frontend_display.class.php');
include(WPSHOP_LIBRAIRIES_DIR . 'display/form.class.php');

include(WPSHOP_LIBRAIRIES_DIR . 'display/widgets/categories.widget.php');
add_action('widgets_init', create_function( '', 'return register_widget("WP_Widget_Wpshop_Product_categories");' ) );

include(WPSHOP_LIBRAIRIES_DIR . 'db/database.class.php');

include(WPSHOP_LIBRAIRIES_DIR . 'documents/documents.class.php');
add_action('admin_head', array('wpshop_documents', 'galery_manager_css'));
add_filter('attachment_fields_to_edit', array('wpshop_documents', 'attachment_fields'), 11, 2);
add_filter('gettext', array('wpshop_documents', 'change_picture_translation'), 11, 2);

include(WPSHOP_LIBRAIRIES_DIR . 'catalog/products.class.php');
include(WPSHOP_LIBRAIRIES_DIR . 'catalog/categories.class.php');
add_filter('manage_edit-' . WPSHOP_NEWTYPE_IDENTIFIER_CATEGORIES . '_columns', array('wpshop_categories', 'category_manage_columns'));
add_filter('manage_' . WPSHOP_NEWTYPE_IDENTIFIER_CATEGORIES . '_custom_column', array('wpshop_categories', 'category_manage_columns_content'), 10, 3);
add_action(WPSHOP_NEWTYPE_IDENTIFIER_CATEGORIES . '_edit_form_fields', array('wpshop_categories', 'category_edit_fields'));
add_action('created_' . WPSHOP_NEWTYPE_IDENTIFIER_CATEGORIES, array('wpshop_categories', 'category_fields_saver'), 10 , 2);
add_action('edited_' . WPSHOP_NEWTYPE_IDENTIFIER_CATEGORIES, array('wpshop_categories', 'category_fields_saver'), 10 , 2);

include(WPSHOP_LIBRAIRIES_DIR . 'eav/attributes.class.php');
include(WPSHOP_LIBRAIRIES_DIR . 'eav/attributes_unit.class.php');
include(WPSHOP_LIBRAIRIES_DIR . 'eav/attributes_set.class.php');
include(WPSHOP_LIBRAIRIES_DIR . 'eav/entities.class.php');