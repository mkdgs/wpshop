<?php

/**	Tools main page	*/
ob_start();
echo wpshop_display::displayPageHeader(__('Outils pour WP-Shop', 'wpshop'), '', __('Outils pour WP-Shop', 'wpshop'), __('Outils pour WP-Shop', 'wpshop'), false, '', '', 'wpshop-tools');
?><div id="wpshop_configurations_container" class="wpshop_cls" >
	<div id="tools_tabs" class="wpshop_tabs wpshop_full_page_tabs wpshop_tools_tabs" >
		<ul>
			<li class="loading_pic_on_select" ><a href="<?php echo admin_url('admin-ajax.php'); ?>?action=wpshop_tool_db_check" title="wpshop_tools_tab_container" ><?php _e('Database structure check', 'wpshop'); ?></a></li>
			<li class="loading_pic_on_select" ><a href="<?php echo admin_url('admin-ajax.php'); ?>?action=wpshop_tool_default_datas_check" title="wpshop_tools_tab_container" ><?php _e('Default data check', 'wpshop'); ?></a></li>
		</ul>
		<div id="wpshop_tools_tab_container" ></div>
	</div>
</div>
<script type="text/javascript" >
	wpshop(document).ready(function(){
		jQuery("#wpshop_tools_tab_container").html(jQuery("#wpshopLoadingPicture").html());
		jQuery("#tools_tabs").tabs();
		jQuery(".loading_pic_on_select a").click(function(){
			jQuery("#wpshop_tools_tab_container").html(jQuery("#wpshopLoadingPicture").html());
		});

		jQuery(".wpshop_repair_db_version").live("click", function(){
			jQuery(this).after(jQuery("#wpshopLoadingPicture").html());
			var data = {
				action: "wpshop_ajax_db_repair_tool",
				version_id: jQuery(this).attr("id").replace("wpshop_repair_db_version_", ""),
			};
			jQuery.post(ajaxurl, data, function(response){
				if (response) {
					jQuery("#wpshop_tools_tab_container").load("<?php echo admin_url('admin-ajax.php') ?>", {
						"action": "wpshop_tool_db_check",
					});
				}
				else {
					alert(wpshopConvertAccentTojs("<?php _e('An error occured while attempting to repair database', 'wpshop'); ?>"));
				}
			}, 'json');
		});

		jQuery(".wpshop_repair_default_data_cpt").live("click", function(){
			jQuery(this).after(jQuery("#wpshopLoadingPicture").html());
			var data = {
				action: "wpshop_ajax_repair_default_datas",
				type: "<?php echo WPSHOP_NEWTYPE_IDENTIFIER_ENTITIES; ?>",
				identifier: jQuery(this).attr("id").replace("wpshop_repair_default_data_wpshop_cpt_", ""),
			};
			jQuery.post(ajaxurl, data, function(response){
				if (response[0]) {
					jQuery("#" + response[1]).html(response[2]);
				}
				else {
					alert(wpshopConvertAccentTojs("<?php _e('An error occured while attempting to repair default custom post type', 'wpshop'); ?>"));
				}
			}, 'json');
		});
		jQuery(".wpshop_repair_default_data_attributes").live("click", function(){
			jQuery(this).after(jQuery("#wpshopLoadingPicture").html());
			var data = {
				action: "wpshop_ajax_repair_default_datas",
				type: "<?php echo WPSHOP_DBT_ATTRIBUTE; ?>",
				identifier: jQuery(this).attr("id").replace("wpshop_repair_default_data_wpshop_cpt_", ""),
			};
			jQuery.post(ajaxurl, data, function(response){
				if (response[0]) {
					jQuery("#" + response[1]).html(response[2]);
				}
				else {
					alert(wpshopConvertAccentTojs("<?php _e('An error occured while attempting to repair default attributes', 'wpshop'); ?>"));
				}
			}, 'json');
		});
	});
</script><?php
echo wpshop_display::displayPageFooter(false);
$tpl_element['wpshop_admin_tools_main_page'] = ob_get_contents();
ob_end_clean();


ob_start();
?><ul>
	{WPSHOP_TOOLS_CUSTOM_POST_TYPE_LIST}
</ul><?php
$tpl_element['wpshop_admin_tools_default_datas_check_main'] = ob_get_contents();
ob_end_clean();


ob_start();
?><li class="wpshop_tools_default_custom_post_type_main_container{WPSHOP_TOOLS_CUSTOM_POST_TYPE_CONTAINER_CLASS}" id="{WPSHOP_CUSTOM_POST_TYPE_NAME}" >
	{WPSHOP_TOOLS_CUSTOM_POST_TYPE_CONTAINER}
</li><?php
$tpl_element['wpshop_admin_tools_default_datas_check_main_element'] = ob_get_contents();
ob_end_clean();


ob_start();
?><h2>{WPSHOP_CUSTOM_POST_TYPE_IDENTIFIER}</h2>
<ul class="wpshop_tools_default_datas_repair_attribute_container" >
	{WPSHOP_CUSTOM_POST_TYPE_DEFAULT_ATTRIBUTES}
</ul><?php
$tpl_element['wpshop_admin_tools_default_datas_check_main_element_content_no_error'] = ob_get_contents();
ob_end_clean();

ob_start();
?><h2>{WPSHOP_CUSTOM_POST_TYPE_IDENTIFIER}</h2>
<button id="wpshop_repair_default_data_{WPSHOP_CUSTOM_POST_TYPE_NAME}" class="wpshop_repair_default_data_cpt" ><?php _e('Re-create this type of element', 'wpshop'); ?></button><?php
$tpl_element['wpshop_admin_tools_default_datas_check_main_element_content_error'] = ob_get_contents();
ob_end_clean();


ob_start();
?><li><h3 class="wpshop_default_datas_state no_error" ><?php _e('Attributes that are OK', 'wpshop'); ?></h3><br/>{WPSHOP_CUSTOM_POST_TYPE_DEFAULT_ATTRIBUTES_LIST}</li><?php
$tpl_element['wpshop_admin_tools_default_datas_check_main_element_content_attributes_no_error'] = ob_get_contents();
ob_end_clean();

ob_start();
?><li><h3 class="wpshop_default_datas_state error" ><?php _e('Attributes needing attention', 'wpshop'); ?></h3><button id="wpshop_repair_default_data_{WPSHOP_CUSTOM_POST_TYPE_NAME}" class="wpshop_repair_default_data_attributes" ><?php _e('Repair missing attributes', 'wpshop'); ?></button><br/>{WPSHOP_CUSTOM_POST_TYPE_DEFAULT_ATTRIBUTES_LIST}</li><?php
$tpl_element['wpshop_admin_tools_default_datas_check_main_element_content_attributes_error'] = ob_get_contents();
ob_end_clean();
