<div class="wrap">

	<?php 
		$base_url = ApmOptions::get_base_url(true);
		$base_url_nonced = ApmOptions::get_base_url();
	?>

	<?php 
		$feedback_msg = !empty($_GET['apm_options_msg']) ? ApmOptions::get_msg( $_GET['apm_options_msg'] ) : ApmOptions::$feedback['msg'];
		$feedback_type = !empty($_GET['apm_options_msg_type']) ? ApmOptions::get_msg_type( $_GET['apm_options_msg_type'] ) : ApmOptions::$feedback['type'];
	?>
	<?php if( !empty($feedback_msg) ): ?>
		<div class="<?php echo $feedback_type == 'error' ? 'error' : 'updated' ?>">
			<p><?php echo $feedback_msg ?></p>
		</div>
	<?php endif ?>
	
	<h2><?php _e('Advanced Page Manager settings',ApmConfig::i18n_domain) ?></h2>
	
	<div class="metabox-holder">
		<div class="postbox">
			<h3 class="hndle"><span><?php _e('Addons', ApmConfig::i18n_domain) ?></span></h3>
			<div class="inside">
				<form action="<?php echo $base_url ?>" method="post">
					<?php wp_nonce_field(ApmOptions::save_options_nonce); ?>
					<input type="hidden" name="apm_options_action" value="<?php echo ApmAddons::$posted_options_action ?>" />
					<div class="form-table-wrapper">
						<table class="form-table">
							<?php $addons = ApmAddons::get_addons() ?>
							<?php foreach($addons as $addon): ?>
								<tr>
									<td width="30%"><?php echo $addon['name']?></td>
									<td>
										<select name="<?php echo $addon['option_name']?>">
											<option value="0" <?php echo $addon['activated'] ? '' : 'selected="selected"' ?>><?php _e('Deactivated', ApmConfig::i18n_domain) ?></option>
											<option value="1" <?php echo $addon['activated'] ? 'selected="selected"' : '' ?>><?php _e('Activated', ApmConfig::i18n_domain) ?></option>
										</select>
									</td>
								</tr>
							<?php endforeach ?>
						</table>
					</div>
					<p class="submit">
						<input type="submit" value="<?php _e('Save changes', ApmConfig::i18n_domain) ?>" class="button-primary">
					</p>
				</form>
			</div>
		</div>
	</div>
	
	<div class="metabox-holder">
		<div class="postbox">
			<h3 class="hndle"><span><?php _e('Plugin options', ApmConfig::i18n_domain) ?></span></h3>
			<div class="inside">
				<form action="<?php echo $base_url ?>" method="post">
					<?php wp_nonce_field(ApmOptions::save_options_nonce); ?>
					<input type="hidden" name="apm_options_action" value="save_admin_options" />
					<div class="form-table-wrapper">
						<table class="form-table">
							<?php /* Template choice : only for debug
							<tr>
								<td width="30%"><?php _e('Template for the "All pages" panel', ApmConfig::i18n_domain) ?></td>
								<td>
									<?php $current_template = ApmOptions::get_option('panel_page_template_name') ?>
									<?php $available_templates = array('panel_page','panel_page_sample') //TODO : retrieve this list from templates directory. ?>
									<select name="panel_page_template_name">
										<?php foreach($available_templates as $template): ?>
											<option value="<?php echo $template ?>" <?php echo $current_template == $template ? 'selected="selected"' : '' ?>><?php echo $template ?></option>
										<?php endforeach ?>
									</select>
								</td>
							</tr>
							*/ ?>
							<tr>
								<td><?php _e('Display Mysql queries in AJAX answers', ApmConfig::i18n_domain) ?></td>
								<td>
									<?php $queries_watcher_on = ApmOptions::get_option('queries_watcher_on') ?>
									<select name="queries_watcher_on">
										<option value="0" <?php echo $queries_watcher_on ? '' : 'selected="selected"' ?>><?php _e('Deactivated', ApmConfig::i18n_domain) ?></option>
										<option value="1" <?php echo $queries_watcher_on ? 'selected="selected"' : '' ?>><?php _e('Activated', ApmConfig::i18n_domain) ?></option>
									</select>
								</td>
							</tr>
							<tr>
								<td><?php _e('Display lost pages in the "All pages" panel', ApmConfig::i18n_domain) ?></td>
								<td>
									<?php $display_lost_pages = ApmOptions::get_option('display_lost_pages') ?>
									<select name="display_lost_pages">
										<option value="0" <?php echo $display_lost_pages ? '' : 'selected="selected"' ?>><?php _e('Deactivated', ApmConfig::i18n_domain) ?></option>
										<option value="1" <?php echo $display_lost_pages ? 'selected="selected"' : '' ?>><?php _e('Activated', ApmConfig::i18n_domain) ?></option>
									</select>
								</td>
							</tr>
						</table>
					</div>
					<p class="submit">
						<input type="submit" value="<?php _e('Save changes', ApmConfig::i18n_domain) ?>" class="button-primary">
					</p>
				</form>
			</div>
		</div>
	</div>
	
	<div class="metabox-holder">
		<div class="postbox">
			<h3 class="hndle"><span><?php _e('Lost pages', ApmConfig::i18n_domain) ?></span></h3>
			<div class="inside">
				<div class="form-table-wrapper">
					<?php $lost_pages = ApmOptions::get_lost_pages() ?>
					<table class="form-table">
						<?php if( empty($lost_pages) ): ?>
							<tr>
								<td><?php _e("No lost pages found", ApmConfig::i18n_domain) ?></td>
								<td></td>
							</tr>
						<?php else: ?>
							<?php foreach($lost_pages as $page): ?>
								<tr>
									<td><a href="<?php echo get_edit_post_link($page->ID) ?>"><?php echo $page->post_title ?></a></td>
									<td><a href="<?php echo ApmOptions::get_restore_page_url($page->ID,false) ?>"><?php _e("Restore at the end of the page tree", ApmConfig::i18n_domain) ?></a></td>
								</tr>
							<?php endforeach ?>
						<?php endif ?>
					</table>
				</div>
			</div>
		</div>
	</div>
	
	<?php do_action('apm_bo_option_panel_add_option_panel',ApmOptions::save_options_nonce,$base_url,$base_url_nonced) ?>
	
	<div class="metabox-holder">
		<div class="postbox">
			<h3 class="hndle"><span><?php _e('Plugin data management', ApmConfig::i18n_domain) ?></span></h3>
			<div class="inside">
				<div class="form-table-wrapper">
					<table class="form-table">
						<tr>
							<td><a href="<?php echo add_query_arg(array('apm_options_action'=>'reset_tree_from_wp_pages'),$base_url_nonced) ?>"><?php _e("Reinitialize the tree from Wordpress pages", ApmConfig::i18n_domain) ?></a></td>
							<td></td>
						</tr>
						<tr>
							<td><a href="<?php echo add_query_arg(array('apm_options_action'=>'apm_tree_to_wp_pages_tree'),$base_url_nonced) ?>"><?php _e('Synchronize Wordpress pages tree with the plugin tree', ApmConfig::i18n_domain) ?></a></td>
							<td></td>
						</tr>
						<tr>
							<td><a href="<?php echo add_query_arg(array('apm_options_action'=>'delete_all_data'),$base_url_nonced) ?>"><?php _e('Delete plugin data except options', ApmConfig::i18n_domain) ?></a> : <?php _e('delete plugin tree and fold / unfold data. Doesnt delete plugin options.', ApmConfig::i18n_domain) ?></td>
							<td></td>
						</tr>
						<tr>
							<td><a href="<?php echo add_query_arg(array('apm_options_action'=>'delete_options'),$base_url_nonced) ?>"><?php _e('Delete plugin options', ApmConfig::i18n_domain) ?></a> : <?php _e('reinitialize plugin options.', ApmConfig::i18n_domain) ?></td>
							<td></td>
						</tr>
						<tr>
							<td><a href="<?php echo add_query_arg(array('apm_options_action'=>'delete_folding_infos'),$base_url_nonced) ?>"><?php _e('Delete fold / unfold data', ApmConfig::i18n_domain) ?></a> : <?php _e('fold all pages.', ApmConfig::i18n_domain) ?></td>
							<td></td>
						</tr>
						<?php do_action('apm_bo_option_panel_add_data_action_tr',$base_url_nonced) ?>
					</table>
				</div>
			</div>
		</div>
	</div>
	
	
<?php /* For debug :

	<div class="metabox-holder">
		<div class="postbox">
			<h3 class="hndle"><span><?php _e('Debug data') ?></span></h3>
			<div class="inside">
<pre>

-----------------------------------
Raw tree : 
<?php 
	foreach( ApmOptions::get_raw_tree() as $parent => $children ){
		echo "$parent : [". implode(',',$children) ."]\n";
	}
?>

-----------------------------------
Tree state :
<?php 
	foreach( ApmOptions::get_tree_state() as $apm_id => $state ){
		echo "$apm_id:$state, ";
	}
?>

-----------------------------------
Marked :
<?php 
	foreach( ApmOptions::get_marked() as $apm_id => $marked ){
		echo "$apm_id:$marked, ";
	}
?>

</pre>
			</div>
		</div>
	</div>
	
 */ ?>
	
</div>
