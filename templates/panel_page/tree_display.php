<?php
/**
 * Template for displaying the tree.
 * $nodes is an array containing the tree nodes.
 *
 * Node status can be : -1:auto-draft, 0:draft, 1:waiting for approval,
 *  2:published, 3:private, 4:trash, -2:in APM tree but not in WP anymore
 */

require_once( ABSPATH .'/wp-admin/includes/theme.php' );
require_once( 'functions.php' );

$total_nodes = count( $nodes );

?>
<?php if( $total_nodes > 0 ) : ?>
	<?php foreach($nodes as $node) : ?>

		<?php
		$has_children = $node->nb_children;
		$depth =( $node->depth <= 5 ) ? $node->depth : 5;

		$link_display = get_bloginfo('wpurl').'/?p='.$node->wp_id;
		$link_preview = $link_display.'&preview=true';
		?>

		<?php if( $node->depth > 0 ) : ?>

			<tr id="apm-<?php echo $node->apm_id ?>" class="post-1 post type-post status-publish format-standard hentry category-non-classe alternate iedit author-self" valign="top">

                <!-- Select checkbox cell -->
                <th scope="row" class="check-column check-column-tree">
					<div class="overlay_host"></div>
					<input type="checkbox" name="post[]" class="row_select" value="1">
					<?php if($has_children): ?>
						<span class="view-actions"></span>
						<div class="apm-sub-action sub-action-check">
							<ul>
								<li><a class="ckeck-sub-pages" href="#"><?php _e('Select Subpages', ApmConfig::i18n_domain); ?></a></li>
								<li><a class="unckeck-sub-pages" href="#"><?php _e('Unselect Subpages', ApmConfig::i18n_domain); ?></a></li>
							</ul>
						</div>
					<?php endif ?>
				</th>

				<!-- Title cell -->
				<td class="apm-page-slot-td column-title apm-page-shadow-depth_<?php echo $depth ?>">
                    <div class="apm-page-slot apm-page-slot-margin_<?php echo $depth ?>">

                        <!-- Unfold/fold arrow -->
                        <div class="apm-has-subpages">
                            <?php if( $node->nb_children > 0 ) : ?>
                                <?php if( $node->is_folded ) : ?>
                                	<a href="#" class="unfold_node"></a>
                                <?php else : ?>
                                	<a href="#" class="fold_node"></a>
                                <?php endif; ?>
                                <span class="picto-subpage"></span>
								<?php else : ?>
                                &nbsp;
                            <?php endif; ?>
                        </div> <!-- End apm-has-subpages -->

                        <!-- Title -->
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
							<div class="row-actions">

								<?php if( $node->status >= 0 && $node->status < 4 ) : ?>
									<span class="rename"><a href="#" class="action_rename" title="<?php _e('Rename', ApmConfig::i18n_domain); ?>"><?php _e('Rename', ApmConfig::i18n_domain); ?></a>&nbsp;|&nbsp;</span>
								<?php endif ?>

								<?php if( $node->status < 4 ): ?>
									<?php if( $node->status > 1 ) : ?>
										<span class="display"><a href="<?php echo $link_display ?>" class="action_display" title="<?php _e('View', ApmConfig::i18n_domain); ?>"><?php _e('View', ApmConfig::i18n_domain); ?></a>&nbsp;|&nbsp;</span>
									<?php elseif( $node->status >= 0 ) : ?>
										<span class="previews"><a href="<?php echo $link_preview ?>" class="action_previews" title="<?php _e('Preview', ApmConfig::i18n_domain); ?>"><?php _e('Preview', ApmConfig::i18n_domain); ?></a>&nbsp;|&nbsp;</span>
									<?php endif; ?>
								<?php endif ?>

								<?php if( $node->status > 1 ) : ?>
									<?php if( $node->status != 3 ) : //private ?>
										<?php if( $node->status == 4 ) : ?>
											<span class=""><a href="#" class="action_unpublish" title="<?php _e('Restore', ApmConfig::i18n_domain); ?>"><?php _e('Restore', ApmConfig::i18n_domain); ?></a>&nbsp;|&nbsp;</span>
										<?php else: ?>
											<span class=""><a href="#" class="action_unpublish" title="<?php _e('Unpublish', ApmConfig::i18n_domain); ?>"><?php _e('Unpublish', ApmConfig::i18n_domain); ?></a>&nbsp;|&nbsp;</span>
										<?php endif ?>
									<?php endif ?>
								<?php elseif( $node->status >= 0 ) : ?>
									<span class=""><a href="#" class="action_publish" title="<?php _e('Publish', ApmConfig::i18n_domain); ?>"><?php _e('Publish', ApmConfig::i18n_domain); ?></a>&nbsp;|&nbsp;</span>
								<?php endif; ?>

								<?php if( $node->status > -2 && $node->status < 4 ) : ?>
									<span class="edit"><a href="<?php echo get_bloginfo('wpurl').'/wp-admin/post.php?post='.$node->wp_id.'&action=edit' ?>" title="<?php _e('Edit', ApmConfig::i18n_domain); ?>" class="action_edit"><?php _e('Edit', ApmConfig::i18n_domain); ?></a>&nbsp;|&nbsp;</span>
								<?php endif; ?>

								<?php if( $node->status >= 0 && $node->status < 4 ) : ?>
									<span class="action_change_template"><a href="#"><?php _e('Template'); ?></a>&nbsp;|&nbsp;</span>
								<?php endif ?>


								<?php
								// Drag / undrag actions available when tree has more a page.
								if( $node->is_movable ) : ?>
									<?php if( $node->status > -2 && $node->status < 4 ) : ?>
										<span class="drag"><a href="#" title="<?php _e('Move', ApmConfig::i18n_domain); ?>" class="action-drag"><?php _e('Move', ApmConfig::i18n_domain); ?></a>&nbsp;|&nbsp;</span>
									<?php endif ?>
								<?php endif; ?>

								<?php if( have_right() ) : ?>
									<span class="delete"><a href="#" title="<?php _e('Delete', ApmConfig::i18n_domain); ?>" class="action-delete-page"><?php _e('Delete', ApmConfig::i18n_domain); ?></a></span>
								<?php endif; ?>

				                <?php
				                	//Hook to add custom actions links : see also "apm_list_row_actions" (list_display.php) to do the same on APM lists displays.
				                	do_action('apm_tree_row_actions', $node->wp_id, $node);
				                ?>

							</div> <!-- End row-actions -->
                        </div> <!-- End apm-title-wrapper -->
                    </div> <!-- End  apm-page-slot -->
				</td>

			    <?php if( $node->status > -2 ) : ?>
			    	
			    	<?php
				    	//Use 'apm_manage_pages_custom_column' hook to add a column td :
				    	ApmCustomColumns::echo_custom_column_td('before_status',$node->wp_id,$node);
				    ?>
			    
					<td class="etat column-etat">
						<?php if( $node->status > 1  && $node->status < 4 && $node->status != 3 ) : ?>
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
				    	ApmCustomColumns::echo_custom_column_td('before_template',$node->wp_id,$node);
				    ?>

					<td class="tags column-tags">
						<p class="template-name-<?php echo $node->template ?>"><?php cached_page_template_drowpdown($node->template); ?></p>
					</td>

					<?php
				    	//Use 'apm_manage_pages_custom_column' hook to add a column td :
				    	ApmCustomColumns::echo_custom_column_td('before_add_page',$node->wp_id,$node);
				    ?>
				    
					<td><input type="submit" name="" class="button-secondary action-add-page" value="<?php _e('Add New', ApmConfig::i18n_domain); ?>"></td>
					
					<?php
				    	//Use 'apm_manage_pages_custom_column' hook to add a column td :
				    	ApmCustomColumns::echo_custom_column_td('after_add_page',$node->wp_id,$node);
				    ?>
				    
				<?php else: ?>
					<td colspan="<?php echo 3 + ApmCustomColumns::get_nb_custom_columns() ?>"></td>
				<?php endif ?>

			</tr>

		<?php endif ?>
	<?php endforeach; ?>

<?php endif; ?>