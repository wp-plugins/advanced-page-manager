<?php

require_once( 'functions.php' );

?>

<!-- Panel page Advanced page manager -->
<div class="wrap">
	<div id="icon-edit-pages" class="icon32 icon32-posts-page"><br></div>

	<?php if($is_browse) : ?>
		<h2><?php _e('Pages', ApmConfig::i18n_domain); ?><span class="subtitle result-search-info"> <?php _e('Search results for', ApmConfig::i18n_domain); ?><strong>&laquo;&raquo;</strong></span></h2>
	<?php else : ?>
		<h2><?php _e('Marked Pages', ApmConfig::i18n_domain); ?></h2>
	<?php endif; ?>

	<?php if( ApmOptions::get_option('display_lost_pages') && $lost_pages = ApmOptions::get_lost_pages() ): ?>
		<div id="browse_lost_pages" class="updated">
			<?php $nb_lost_pages = count($lost_pages) ?>
			<h3><?php echo $nb_lost_pages ?> page<?php echo $nb_lost_pages > 1 ? 's' : '' ?> hors arborescence :</h3>
		    <table>
			<?php foreach($lost_pages as $page): ?>
				<tr>
					<td><a href="<?php echo get_edit_post_link($page->ID) ?>"><?php echo $page->post_title ?></a></td>
					<td><a href="<?php echo add_query_arg(array('apm_options_action'=>'restore_page','wp_id'=>$page->ID,'redirect_to_page_in_tree'=>true),ApmOptions::get_base_url()) ?>">Repositionner en fin d'arborescence</a></td>
				</tr>
			<?php endforeach ?>
			</table>
		</div>
	<?php endif ?>

	<!-- left panel -->
	<!-- todo : Remove the 250 px hack  -->
    <div class="column-panel" id="left-panels-wrapper" style="width:250px;">
		<div class="apm-panel" id="left-panels">
			<div class="left-panel panel-add-page postbox">
				<span class="add-page-end cross-close"></span>
				<h3 class="hndle"><span><?php _e('Add New', ApmConfig::i18n_domain); ?></span></h3>

				<div id="notice" class="error below-h2 error-add-page"><p><?php _e('Click After, Before or Subpage position button', ApmConfig::i18n_domain); ?></p></div>
				<div id="notice" class="error below-h2 error-add-first-page"><p><?php _e('Your page needs a title', ApmConfig::i18n_domain); ?></p></div>

				<p class="display-count-page-checked"><?php _e('Selected page', ApmConfig::i18n_domain); ?>:&nbsp;<span class="add-page-title"></span></p>
				<div>
					<p class="label-add-page-panel"><label><?php _e('Title', ApmConfig::i18n_domain); ?></label></p>
					<input type="text" name="add-page-title" id="add-page-title" />
				</div>

				<div>
					<p class="label-add-page-panel"><label><?php _e('Template', ApmConfig::i18n_domain); ?></label></p>
					<select name="add-page-model" id="add-page-model">
						<option value='default'><?php _e('Default Template'); ?></option>
						<?php page_template_dropdown(); ?>
					</select>
				</div>

				<div>
					<p class="label-add-page-panel"><label><?php _e('Position', ApmConfig::i18n_domain); ?></label></p>
					<div id="position-radio-button">
						<input type="radio" name="add-page-position" class="add-page-position" id="add-page-position-after" value="1" checked="1" />&nbsp;<label for="add-page-position-after"><?php _e('After', ApmConfig::i18n_domain); ?></label><br/>
						<input type="radio" name="add-page-position" class="add-page-position" id="add-page-position-before" value="0" />&nbsp;<label for="add-page-position-before"><?php _e('Before', ApmConfig::i18n_domain); ?></label><br/>
						<input type="radio" name="add-page-position" class="add-page-position" id="add-page-position-subpage" value="2" />&nbsp;<label for="add-page-position-subpage"><?php _e('Subpage', ApmConfig::i18n_domain); ?></label><br/>
					</div>
				</div>

				<div>
					<p class="label-add-page-panel"><label><?php _e('Number', ApmConfig::i18n_domain); ?></label></p>
					<select name="add-number-page" id="add-number-page">
						<?php for($i = 0; $i < MAX_ADD_PAGES; $i++) : ?>
						<option value="<?php echo $i+1; ?>"><?php echo $i + 1; ?></option>
						<?php endfor; ?>
					</select>
				</div>
				<div class="form-buttons">
					<input type="button" name="add-page-button" id="add-page-button" class="button button-primary" value="<?php _e('Add', ApmConfig::i18n_domain); ?>" />
					<input type="button" name="add-page-end" class="add-page-end button right" value="<?php _e('Done', ApmConfig::i18n_domain); ?>" /><br />
					
					<!-- 
					<a href="#" class="add-page-end right"><?php _e('Cancel', ApmConfig::i18n_domain); ?></a>
					-->

					<div style="clear:both"></div>
				</div>
			</div>

			<div class="left-panel panel-change-template postbox">
				<span class="add-page-end cross-close"></span>
				<h3 class="hndle"><span><?php _e('Change Template', ApmConfig::i18n_domain); ?></span></h3>

				<p class="display-count-page-checked"><span class="panel-count-page-checked">&nbsp; <?php _e('No page selected', ApmConfig::i18n_domain); ?></span></p>

				<p>
					<p class="label-current-template"><?php _e('Current Template', ApmConfig::i18n_domain); ?></p>
					<span class="name-current-template">
						<?php _e('Selected pages have different templates', ApmConfig::i18n_domain); ?>
					</span>
				</p>

				<p class="label-template-list"><?php _e('New Template', ApmConfig::i18n_domain); ?></p>
				<div class="template-list">
					<!-- Display template list -->
					<?php
						$templates_list = array(
							'default' => __('Default Template')
						);
						$templates_list += array_flip( get_page_templates() );
					?>

					<ul>
						<?php foreach($templates_list as $key => $value) : ?>
						<li id="template-file-<?php echo $key ?>"><a href="#"><?php echo $value ?></a></li>
						<?php endforeach; ?>
					</ul>
				</div>

				<div>
					<input type="button" name="add-template-button" id="add-template-button" class="button button-primary" value="<?php _e('Change', ApmConfig::i18n_domain); ?>" />
					<input type="button" name="add-page-end" class="add-page-end button right" value="<?php _e('Done', ApmConfig::i18n_domain); ?>" /><br />
					
					<!--
					<a href="#" class="add-page-end right"><?php _e('Cancel', ApmConfig::i18n_domain); ?></a>
					-->

					<div style="clear:both"></div>
				</div>
			</div>
		</div>
	</div>
	<div style="float:left; width: 80%; z-index:100; position:relative;">
	    
		<!-- TODO : drop this code if bug confirmed solved -->
	    <!--
	    <p class="result-search-info">
		    <?php _e('Search results for', ApmConfig::i18n_domain); ?>
		    <strong>&laquo;&raquo;</strong>
	    </p>
		-->

	    <div style="clear:both"></div>

	    <!-- actions lists -->
	    <ul class="subsubsub">

		    <?php
			    $cpt = 1;
			    $current = '';
		    ?>
		    <?php foreach( $filters_list as $key => $action ) : ?>

				    <?php
				    $href = isset( $action['href'] ) ? $action['href'] : '#';
				    ?>

				    <?php if(isset($action['current']) && $action['current'] == true) : ?>
					    <?php $current = 'class="current"' ?>
				    <?php else : ?>
					    <?php $current = '' ?>
				    <?php endif; ?>

				    <li class="<?php echo $key ?>">
					    <a href="<?php echo $href ?>" id="action_<?php echo $key ?>" <?php echo $current ?> ><?php echo $action['label'] ?></a>

					    <?php if( isset( $action['count'] ) ) : ?>
						    <span class="count">( <?php echo $action['count'] ?> )</span>
					    <?php endif; ?>
					    <?php if( $cpt < $total_actions ) : ?>&nbsp;|<?php endif; ?>
				    </li>
				    <?php $cpt++; ?>

		    <?php endforeach; ?>
	    </ul>

	    <!-- Search -->
	    <p class="search-box">
		    <label class="screen-reader-text" for="post-search-input"><?php _e('Search Pages', ApmConfig::i18n_domain); ?></label>
		    <input type="search" id="post-search-input" name="s" value="">
		    <input type="submit" name="" id="search-submit" class="button" value="<?php _e('Search Pages', ApmConfig::i18n_domain); ?>">
	    </p>

	    <!-- Bulk Actions -->
	    <div class="tablenav top">
		    <div class="alignleft actions">
			    <a href="#" id="display-actions-all"><?php _e('Bulk Actions', ApmConfig::i18n_domain); ?> <span class="view-actions"></span></a>

				&nbsp;|&nbsp;<a href="#" id="apm-action-all-unselect"><?php _e('Unselect All', ApmConfig::i18n_domain); ?></a>

			    <?php if( $is_browse ) : ?>
			    	<span class="only-browse">&nbsp;|&nbsp;<a href="#" id="apm-action-all-fold"><?php _e('Fold All', ApmConfig::i18n_domain); ?></a></span>
			    <?php else: ?>
				    &nbsp;|&nbsp;<a href="#" id="apm-action-all-untagged"><?php _e('Unmark All', ApmConfig::i18n_domain); ?></a>
			    <?php endif; ?>

			    <div class="apm-grouped-action" style="display: none; ">
				    <ul>

					    <?php do_action('apm_panel_page_grouped_actions_menu') ?>

					    <li><a id="apm-action-all-publish" href="#"><?php _e('Publish', ApmConfig::i18n_domain); ?></a></li>
					    <li><a id="apm-action-all-unpublish" href="#"><?php _e('Unpublish', ApmConfig::i18n_domain); ?></a></li>
					    <li><a id="apm-action-all-models" href="#"><?php _e('Change Template', ApmConfig::i18n_domain); ?></a></li>

					    <?php if(have_right()) : ?>
						    <li><a id="apm-action-all-delete" href="#"><?php _e('Delete', ApmConfig::i18n_domain); ?></a></li>
					    <?php endif; ?>

				    </ul>
			    </div>
		    </div>

		    <!-- Pagination -->
		    <?php echo get_template_pagination(); ?>

		    <?php if( $is_browse ): ?>
			    <div class="container-pagination-browse">
				    <span class="displaying-num nb-selected-rows">0</span>&nbsp;<span class="displaying-num"><?php _e('Selected Item(s)', ApmConfig::i18n_domain); ?></span>
			    </div>
		    <?php endif ?>

	    </div>

	    <!-- Tree -->
	    <div class="container-list">
		    <div class="container-list-big-loader">
			    <div class="apm-working-msg"><?php _e("Working", ApmConfig::i18n_domain); ?>...</div>
		    </div>

		    <div id="drag-container-selected-template" class="drag-container-selected">
			    <p>
				    <strong><?php  _e('You have chosen to move this page (and its subpages) elsewhere', ApmConfig::i18n_domain); ?></strong>
				    <a href="#" class="cancel-drag button-secondary"><?php _e('Cancel', ApmConfig::i18n_domain); ?></a>
			    </p>
		    </div>

		    <div id="drag-container-add-template" class="drag-container-add">
			    <p>
				    <strong><?php  _e('You are adding pages relative to this one', ApmConfig::i18n_domain); ?></strong>&nbsp;&nbsp;
					<a href="#" class="add-page-end button-secondary"><?php  _e('Cancel', ApmConfig::i18n_domain); ?></a>
			    </p>
		    </div>

		    <div id="drop-container-template" class="drop-container">
			    <p>
		    		<span class="apm-subpages-controls">
		    			<a href="#"></a>
		    			<span class="picto-subpage"></span>
		    		</span>
				    <strong style="margin-left:0px;"><?php _e('Move my page', ApmConfig::i18n_domain); ?> :&nbsp;</strong>
				    <input type="button" name="drop-after" class="drop-after button" value="<?php _e('After', ApmConfig::i18n_domain); ?>" />
				    <input type="button" name="drop-before" class="drop-before button" value="<?php _e('Before', ApmConfig::i18n_domain); ?>" />
				    <input type="button" name="drop-sub" class="drop-sub button" value="<?php _e('As a subpage', ApmConfig::i18n_domain); ?>" />
				    <a href="#" class="cancel-drag"><?php _e('Cancel', ApmConfig::i18n_domain); ?></a>
			    </p>
		    </div>

		    <table class="wp-list-table widefat fixed posts" cellspacing="0">
			    <thead>
			    <tr id="page-list-headers">
				    <th scope="col" id="cb" class="column-cb check-column" style=""><input class="select-all" type="checkbox"></th>

				    <th scope="col" id="pages" class="manage-column column-pages <?php echo $is_browse ? 'sortable desc' : 'asc sorted' ?>">
					    <a href="#">
						    <span><?php _e('Pages', ApmConfig::i18n_domain); ?></span>
						    <span class="sorting-indicator"></span>
					    </a>
				    </th>

				    <?php
				    	//Use 'apm_manage_pages_columns' hook to add a column th :
				    	ApmCustomColumns::echo_custom_column_th('before_status');
				    ?>

				    <th scope="col" id="etat" class="manage-column column-etat sortable desc">
					    <a href="#">
						    <span><?php _e('Status', ApmConfig::i18n_domain); ?></span>
						    <span class="sorting-indicator"></span>
					    </a>
				    </th>
				    
				    <?php
				    	//Use 'apm_manage_pages_columns' hook to add a column th :
				    	ApmCustomColumns::echo_custom_column_th('before_date');
				    ?>
				    
				    <th scope="col" id="date" class="manage-column column-date sortable desc">
					    <a href="#">
						    <span><?php _e('Date', ApmConfig::i18n_domain); ?></span>
						    <span class="sorting-indicator"></span>
					    </a>
				    </th>
				    
				    <?php
				    	//Use 'apm_manage_pages_columns' hook to add a column th :
				    	ApmCustomColumns::echo_custom_column_th('before_template');
				    ?>
				    
				    <th scope="col" id="models" class="manage-column column-models sortable desc">
					    <a href="#">
						    <span><?php _e('Template', ApmConfig::i18n_domain); ?></span>
						    <span class="sorting-indicator"></span>
					    </a>
				    </th>
				    
				    <?php
				    	//Use 'apm_manage_pages_columns' hook to add a column th :
				    	ApmCustomColumns::echo_custom_column_th('before_add_page');
				    ?>
				    
				    <th scope="col" id="add_action" class="manage-column column-add sortable desc"><span></span></th>
				    
				    <?php
				    	//Use 'apm_manage_pages_columns' hook to add a column th :
				    	ApmCustomColumns::echo_custom_column_th('after_add_page');
				    ?>
				    
			    </tr>
			    </thead>

			    <tbody id="the-list">
				    <tr>
					    <th colspan="6" class="big_loader"></th>
				    </tr>
			    </tbody>

			    <tfoot>
			    <tr>
				    <th scope="col" id="cb" class="column-cb check-column"><input class="select-all" type="checkbox"></th>

				    <th scope="col" id="pages" class="manage-column column-pages <?php echo $is_browse ? 'sortable desc' : 'asc sorted' ?>">
					    <a href="#">
						    <span><?php _e('Pages', ApmConfig::i18n_domain); ?></span>
						    <span class="sorting-indicator"></span>
					    </a>
				    </th>

				    <?php
				    	//Use 'apm_manage_pages_columns' hook to add a column th :
				    	ApmCustomColumns::echo_custom_column_th('before_status');
				    ?>

				    <th scope="col" id="etat" class="manage-column column-etat sortable desc">
					    <a href="#">
						    <span><?php _e('Status', ApmConfig::i18n_domain); ?></span>
						    <span class="sorting-indicator"></span>
					    </a>
				    </th>
				    
				    <?php
				    	//Use 'apm_manage_pages_columns' hook to add a column th :
				    	ApmCustomColumns::echo_custom_column_th('before_date');
				    ?>
				    
				    <th scope="col" id="date" class="manage-column column-date sortable desc">
					    <a href="#">
						    <span><?php _e('Date', ApmConfig::i18n_domain); ?></span>
						    <span class="sorting-indicator"></span>
					    </a>
				    </th>
				    
				    <?php
				    	//Use 'apm_manage_pages_columns' hook to add a column th :
				    	ApmCustomColumns::echo_custom_column_th('before_template');
				    ?>
				    
				    <th scope="col" id="models" class="manage-column column-models sortable desc">
					    <a href="#">
						    <span><?php _e('Template', ApmConfig::i18n_domain); ?></span>
						    <span class="sorting-indicator"></span>
					    </a>
				    </th>
				    
				    <?php
				    	//Use 'apm_manage_pages_columns' hook to add a column th :
				    	ApmCustomColumns::echo_custom_column_th('before_add_page');
				    ?>
				    
				    <th scope="col" id="add_action" class="manage-column column-models sortable desc"><span></span></th>
				    
				    <?php
				    	//Use 'apm_manage_pages_columns' hook to add a column th :
				    	ApmCustomColumns::echo_custom_column_th('after_add_page');
				    ?>
			    </tr>
			    </tfoot>
		    </table>
	    </div>
	    <div class="tablenav bottom">
		    <?php echo get_template_pagination('bottom'); ?>

		    <?php if( $is_browse ): ?>
			    <div class="container-pagination-browse">
				    <span class="displaying-num nb-selected-rows">0</span>&nbsp;<span class="displaying-num"><?php _e('Selected Item(s)', ApmConfig::i18n_domain); ?></span>
			    </div>
		    <?php endif ?>
	    </div>
	</div>
</div>

<script>
jQuery().ready(function(){
	var $ = jQuery;

	$.apm_common.init();

	// Initialize tree by default
	<?php if( $is_browse ) : ?>
		<?php
			$node_to_go_to = ApmBoContext::get_requested_go_to_node('');
			if( !empty($node_to_go_to) ){
				$node_to_go_to = ",'','',$node_to_go_to";
			}
		?>
		$.apm_common.switch_type('tree'<?php echo $node_to_go_to ?>);
	<?php else : ?>
		$.apm_common.switch_type('list', 'tagged');
	<?php endif; ?>

	$.apm_common.update_counters();
});
</script>