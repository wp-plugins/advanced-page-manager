<?php
/**
 * Template for displaying a list.
 * $nodes is an array containing the list nodes.
 */

require_once( ABSPATH .'/wp-admin/includes/theme.php' );
require_once( 'functions.php' );

$cpt = 1;
?>

<?php if( count( $nodes ) > 0 ) : ?>
	<?php foreach($nodes as $node) : ?>

		<?php

		$has_children = $node->nb_children;
		$depth =( $node->depth <= 5 ) ? $node->depth : 5;

		$link_display = get_bloginfo('wpurl').'/?p='.$node->wp_id;
		$link_preview = $link_display.'&preview=true';

		$where_is_page_link = get_bloginfo('wpurl').'/wp-admin/edit.php?post_type=page&page=apm_browse_pages_menu&go_to_node='.$node->apm_id;
		$alternate = '';
		if( $cpt % 2 == 0 ) $alternate = 'alternate';

		?>

		<?php if( $node->depth > 0 ) : ?>
			<tr id="apm-<?php echo $node->apm_id ?>" class="post-1 post type-post status-publish format-standard hentry category-non-classe <?php echo $alternate ?> iedit author-self" valign="top">
				<th scope="row" class="check-column check-column-tree">
					<input type="checkbox" name="post[]" class="row_select" value="1">
				</th>
				<td class="apm-page-slot-td column-title apm-page-shadow-depth_1">
					<div class="apm-page-slot apm-page-slot-margin_1">
						<div class="apm-has-subpages">
							<?php if( $node->nb_children > 0 ) : ?>

								<span class="picto-subpage"></span>

							<?php else : ?>
								&nbsp;
							<?php endif; ?>
						</div>
						<div class="apm-title-wrapper">
							<strong>
								<?php if( $node->status > -2 ) : ?>
									<?php if( $node->status == 4 ) : ?>
										<span class="node-trashed"><?php echo '['. __('Trash', ApmConfig::i18n_domain) .'] '. $node->title ?></span>
									<?php else: ?>
										<a class="row-title" href="<?php echo get_bloginfo('wpurl').'/wp-admin/post.php?post='.$node->wp_id.'&action=edit' ?>" title="<?php echo $node->title ?>"><?php echo $node->title ?></a>
									<?php endif ?>
								<?php else: ?>
									<span class="node-deleted-from-wp"><?php echo $node->title ?></span>
								<?php endif?>
							</strong>
							<div class="wrap-edit-title field-rename">
								<input type="text" value="<?php echo $node->title ?>" />
								<input type="button" value="OK" />
								<a href="#" class="cross-delete"><?php _e('Cancel', ApmConfig::i18n_domain); ?></a>
							</div>

							<div class="row-actions apm-row-actions">
								<?php if( $node->status >= 0 && $node->status < 4 ) : ?>
									<span class="rename"><a href="#" class="action_rename" title="<?php _e('Rename', ApmConfig::i18n_domain); ?>"><?php _e('Rename', ApmConfig::i18n_domain); ?></a>&nbsp;|&nbsp;</span>
								<?php endif ?>

								<?php if( $node->status < 4 ): ?>
									<?php if( $node->status > 1 ) : ?>
										<span class="display"><a href="<?php echo $link_display ?>" class="action_display" title="<?php _e('View', ApmConfig::i18n_domain); ?>"><?php _e('View', ApmConfig::i18n_domain); ?></a>&nbsp;|&nbsp;</span>
									<?php elseif( $node->status >= 0 ) : ?>
										<span class="display"><a href="<?php echo $link_preview ?>" class="action_display" title="<?php _e('Preview', ApmConfig::i18n_domain); ?>"><?php _e('Preview', ApmConfig::i18n_domain); ?></a>&nbsp;|&nbsp;</span>
									<?php endif; ?>
								<?php endif; ?>

								<?php if( $node->status > 1 ) : ?>
									<?php if( $node->status != 3 ) : //private ?>
										<?php if( $node->status == 4 ) : ?>
											<span class=""><a href="#" class="action_unpublish" title="<?php _e('Restore', ApmConfig::i18n_domain); ?>"><?php _e('Restore', ApmConfig::i18n_domain); ?></a>&nbsp;|&nbsp;</span>
										<?php else: ?>
											<span class=""><a href="#" class="action_unpublish" title="<?php _e('Unpublish', ApmConfig::i18n_domain); ?>"><?php _e('Unpublish', ApmConfig::i18n_domain); ?></a>&nbsp;|&nbsp;</span>
										<?php endif?>
									<?php endif?>
								<?php elseif( $node->status >= 0 ) : ?>
									<span class=""><a href="#" class="action_publish" title="<?php _e('Publish', ApmConfig::i18n_domain); ?>"><?php _e('Publish', ApmConfig::i18n_domain); ?></a>&nbsp;|&nbsp;</span>
								<?php endif; ?>

								<?php if( $node->status > -2 && $node->status < 4 ) : ?>
									<span class="edit"><a href="<?php echo get_bloginfo('wpurl').'/wp-admin/post.php?post='.$node->wp_id.'&action=edit' ?>" title="<?php _e('Edit', ApmConfig::i18n_domain); ?>" class="action_edit"><?php _e('Edit', ApmConfig::i18n_domain); ?></a>&nbsp;|&nbsp;</span>
								<?php endif; ?>

								<?php if( $node->status >= 0 && $node->status < 4 ) : ?>
									<span class="action_change_template"><a href="#"><?php _e('Template'); ?></a>&nbsp;|&nbsp;</span>
								<?php endif ?>

								<?php if( have_right() ) : ?>
									<span class="delete"><a href="#" title="<?php _e('Delete', ApmConfig::i18n_domain); ?>" class="action-delete-page"><?php _e('Delete', ApmConfig::i18n_domain); ?></a></span>
								<?php endif; ?>
								
								<?php
					                //Hook to add custom actions links : see also "apm_tree_row_actions" (tree_display.php) to do the same on APM tree display.
					                do_action( 'apm_list_row_actions', $node->wp_id, $node );
				                ?>
				                
							</div><!-- End row-actions -->

                        </div> <!-- End apm-title-wrapper -->

                    </div> <!-- End  apm-page-slot -->
				</td>

				<?php
			    	//Use 'apm_manage_pages_custom_column' hook to add a column td :
			    	ApmCustomColumns::echo_custom_column_td('before_status',$node->wp_id,$node);
			    ?>

				<td class="etat column-etat">
					<?php if( $node->status > 1 && $node->status < 4 && $node->status != 3 ) : ?>
						<div class="picto-publish"></div>
						<strong><?php echo $status[$node->status] ?></strong>
					<?php else : ?>
						<div class="picto-unpublish"></div>
						<strong><?php echo $status[$node->status] ?></strong>
					<?php endif; ?>
					<p><?php echo mysql2date(__( 'Y/m/d' ), $node->publication_date, true);?></p>
				</td>

				<?php
			    	//Use 'apm_manage_pages_custom_column' hook to add a column td :
			    	ApmCustomColumns::echo_custom_column_td('before_date',$node->wp_id,$node);
			    ?>
				    
				<td>
					<p><?php echo apm_get_page_date($node) ?></p>
				</td>

				<?php
			    	//Use 'apm_manage_pages_custom_column' hook to add a column td :
			    	ApmCustomColumns::echo_custom_column_td('before_template',$node->wp_id,$node);
			    ?>
			    
				<td class="tags column-tags">
					<p class="template-name-<?php echo $node->template ?>"><?php cached_page_template_drowpdown($node->template); ?></p>
				</td>

				<?php
			    	//Use 'apm_manage_pages_custom_column' hook to add a column td :
			    	ApmCustomColumns::echo_custom_column_td('before_add_page',$node->wp_id,$node);
			    ?>
			    
				<td>
					<a href="<?php echo $where_is_page_link ?>" class="button-secondary action-go-page"><?php _e('Where is it ?', ApmConfig::i18n_domain); ?></a>
				</td>
				
				<?php
			    	//Use 'apm_manage_pages_custom_column' hook to add a column td :
			    	ApmCustomColumns::echo_custom_column_td('after_add_page',$node->wp_id,$node);
			    ?>
			</tr>
		<?php endif; ?>
		<?php $cpt++ ?>
	<?php endforeach; ?>

<?php endif; ?>