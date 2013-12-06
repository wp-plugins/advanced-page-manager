<?php
/**
 * Modified version of wp-admin/includes/meta-boxes.php::page_attributes_meta_box() 
 */
?>
<?php
$post_type_object = get_post_type_object($post->post_type);

if( $post->post_type == 'page' ){
		
	$is_page_in_apm_tree = Apm::get_page_is_in_apm_tree();
	
	if( $is_page_in_apm_tree ){
	
		$marked = Apm::get_page_flag();
		$marked = $marked > 0;
		
		$page_tree_positions = Apm::get_page_tree_positions();
		$page_apm_id = Apm::get_page_apm_id();
		
		?>
		
		<?php if( ApmAddons::addon_is_on('flagged_pages') ): ?>
			<div id="apm_flag_links_wrapper">
				<a href="#" id="apm_unflag_page" style="<?php echo $marked ? '' : 'display:none' ?>" rel="<?php echo $page_apm_id ?>"><img src="<?php echo plugin_dir_url(dirname(dirname(__FILE__))) ?>img/red_tag.png" alt="<?php _e('Page flagged : click here to unflag',ApmConfig::i18n_domain) ?>" title="<?php _e('Page flagged : click here to unflag',ApmConfig::i18n_domain) ?>"></a>
				<a href="#" id="apm_flag_page" style="<?php echo $marked ? 'display:none' : '' ?>" rel="<?php echo $page_apm_id ?>"><img src="<?php echo plugin_dir_url(dirname(dirname(__FILE__))) ?>img/tag_inactive.png" alt="<?php _e('Page not flagged : click here to flag',ApmConfig::i18n_domain) ?>" title="<?php _e('Page not flagged : click here to flag',ApmConfig::i18n_domain) ?>"></a>
			</div>
		<?php endif ?>
		
		<?php if( count(get_page_templates()) ): ?>
			<?php 
				$template_meta = get_post_meta($post->ID, '_wp_page_template', true);
				$template = !empty($template_meta) ? $template_meta : false;
			?>
			<p id="apm_page_attributes_template"><strong><?php _e('Template') ?> :</strong></p>
			<label class="screen-reader-text" for="apm_super_page_template"><?php _e('Page Template') ?></label>
			<select name="page_template" id="apm_super_page_template">
				<option value='default'><?php _e('Default Template'); ?></option>
				<?php page_template_dropdown($template); ?>
			</select>
		<?php else: ?>
			<?php 
				/*
				 	//Native edit page panel displays nothing if there's no page templates in current theme. Let's do the same.
					<p id="apm_page_attributes_template"><span><?php _e('No Page Template found in current theme directory!') ?></span></p> 
				*/ 
			?>
		<?php endif ?>
		
		<div id="apm_page_attributes_reach">
			<?php if( !empty($page_apm_id) ): ?>
				<a class="button" href="<?php echo ApmBoContext::get_reach_node_url($page_apm_id) ?>"><?php _e("Where is it ?",ApmConfig::i18n_domain) ?></a>
			<?php endif ?>
		</div>
		
		<div id="apm_page_attributes_family_nav">
			<?php $link = !empty($page_tree_positions->parent) ? get_edit_post_link($page_tree_positions->parent) : '' ?>
			<div class="apm_page_attributes_nav_button_wrapper">
				<a class="button <?php echo empty($link) ? 'disabled': '' ?>" href="<?php echo !empty($link) ? $link : '#' ?>" <?php echo empty($link) ? 'onclick="return false"' : '' ?> <?php echo empty($link) ? 'disabled="disabled"': '' ?>><?php _e('Edit Parent',ApmConfig::i18n_domain) ?></a>
			</div>
			<div class="apm_page_attributes_nav_button_wrapper">
				<?php $link = !empty($page_tree_positions->previous_sibling) ? get_edit_post_link($page_tree_positions->previous_sibling) : '' ?>
				<a class="button <?php echo empty($link) ? 'disabled': '' ?>" href="<?php echo !empty($link) ? $link : '#' ?>" <?php echo empty($link) ? 'onclick="return false"' : '' ?> <?php echo empty($link) ? 'disabled="disabled"': '' ?>><?php _e('Edit Previous',ApmConfig::i18n_domain) ?></a>
				<?php $link = !empty($page_tree_positions->next_sibling) ? get_edit_post_link($page_tree_positions->next_sibling) : '' ?>
				<a class="button <?php echo empty($link) ? 'disabled': '' ?>" href="<?php echo !empty($link) ? $link : '#' ?>" <?php echo empty($link) ? 'onclick="return false"' : '' ?> <?php echo empty($link) ? 'disabled="disabled"': '' ?>><?php _e('Edit Next',ApmConfig::i18n_domain) ?></a>
			</div>
			<div class="apm_page_attributes_subpages_wrapper">
				<strong><?php _e('Subpage(s)',ApmConfig::i18n_domain) ?></strong>
				<div class="apm_page_attributes_subpages">
				<?php if( !empty($page_tree_positions->children) ): ?>
					<?php 
						//Retrieve current page children ordered list :
						//TODO : Move this to a function... or "apm_get_subpages()" template tag should do it,
						//but don't forget to take the 100 limitation into account.
						
						global $wpdb;
						$children_sql = "'". implode("','",$page_tree_positions->children) ."'";
						
						$sql = "SELECT ID,post_title FROM $wpdb->posts  
														 WHERE ID IN ($children_sql) 
														 ORDER BY FIELD(ID,$children_sql) 
														 LIMIT 100
														 "; //100 for perf security measure : don't display too much pages...
						
						$sub_pages = $wpdb->get_results($sql);
					?>
					<select id="apm_page_attributes_subpage_select">
						<?php foreach($sub_pages as $page): ?>
							<option value="<?php echo get_edit_post_link($page->ID) ?>"><?php echo $page->post_title?></option>
						<?php endforeach ?>
					</select>
				<?php else: ?>
					<?php _e('No Subpage',ApmConfig::i18n_domain) ?>
				<?php endif ?>
				</div>
				<a id="apm_page_attributes_subpage_link" class="button <?php echo empty($page_tree_positions->children) ? 'disabled' : '' ?>" href="#" <?php echo empty($page_tree_positions->children) ? 'onclick="return false"' : '' ?> <?php echo empty($page_tree_positions->children) ? 'disabled="disabled"': '' ?>><?php _e('Edit Subpage',ApmConfig::i18n_domain) ?></a>
			</div>
		</div>	
		<?php
	}else{
		//The page is not in APM tree :
		$allowed_post_status = ApmConfig::$allowed_post_status;
		$allowed_post_status = apply_filters('apm_allowed_post_status',$allowed_post_status,'page_attributes_meta_box');
		$message = __('This page is not in the Advanced Page Manager tree.',ApmConfig::i18n_domain);
		$message = apply_filters('apm_page_attributes_metabox_not_in_tree_message',$message);
		?>
			<div id="apm_page_not_in_apm_tree">
				<?php echo $message ?>
				<?php if( in_array($post->post_status,$allowed_post_status) ): ?>
					<br/><br/><a class="button" href="<?php echo ApmOptions::get_restore_page_url($post->ID) ?>"><?php _e('Insert this page as last page of the tree',ApmConfig::i18n_domain) ?></a>
				<?php endif ?>
			</div>
		<?php
	}
		
} 
?>
