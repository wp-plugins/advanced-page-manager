<?php
/**
 * Template for displaying the tree.
 * $nodes is an array containing the tree nodes.
 * 
 * Node status can be : -1:auto-draft, 0:draft, 1:waiting for approval, 
 *  2:published, 3:trash, -2:in APM tree but not in WP anymore
 */

require_once( ABSPATH .'/wp-admin/includes/theme.php' );
require_once( 'functions.php' );

?>
<?php if( count( $nodes ) > 0 ) : ?>
	<?php foreach($nodes as $node) : ?>

		<?php

		$has_children = $node->nb_children;
		$depth =( $node->depth <= 5 ) ? $node->depth : 5;

		$link_display = get_bloginfo('wpurl').'/?p='.$node->wp_id;
		$link_preview = $link_display.'&preview=true';
		?>

		<?php if( $node->depth > 0 ) : ?>
			<tr id="apm-<?php echo $node->apm_id ?>" class="post-1 post type-post status-publish format-standard hentry category-non-classe alternate iedit author-self" valign="top">
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

				<td class="page-title column-title">
					<div class="depth_<?php echo $depth ?>">
					</div>

					<div class="depth-right">
					</div>

					<div class="has_subpage">
						<?php if( $node->nb_children > 0 ) : ?>
							<?php if( $node->is_folded ) : ?>
							<a href="#" style="color:#fff" class="unfold_node"></a>
							<?php else : ?>
							<a href="#" style="color:#fff" class="fold_node"></a>
							<?php endif; ?>

							<span class="picto-subpage"></span>

						<?php else : ?>
							&nbsp;
						<?php endif; ?>
					</div>

					<strong>
						<?php if( $node->status > -2 ) : ?>
							<a class="row-title" href="<?php echo get_bloginfo('wpurl').'/wp-admin/post.php?post='.$node->wp_id.'&action=edit' ?>" title="<?php echo $node->title ?>"><?php echo $node->title ?></a>
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
					
						<?php if( $node->status >= 0 ) : ?>
							<span class="rename"><a href="#" class="action_rename" title="Renommer"><?php _e('Rename', ApmConfig::i18n_domain); ?></a>&nbsp;|&nbsp;</span>
						<?php endif ?>

						<?php if( $node->status > 1 ) : ?>
							<span class="display"><a href="<?php echo $link_display ?>" class="action_display" title="Afficher"><?php _e('View', ApmConfig::i18n_domain); ?></a>&nbsp;|&nbsp;</span>
						<?php elseif( $node->status >= 0 ) : ?>
							<span class="previews"><a href="<?php echo $link_preview ?>" class="action_previews" title="Apercu"><?php _e('Preview', ApmConfig::i18n_domain); ?></a>&nbsp;|&nbsp;</span>
						<?php endif; ?>

						<?php if( $node->status > 1 ) : ?>
							<span class=""><a href="#" class="action_unpublish" title="Dépublier"><?php _e('Unpublish', ApmConfig::i18n_domain); ?></a>&nbsp;|&nbsp;</span>
						<?php elseif( $node->status >= 0 ) : ?>
							<span class=""><a href="#" class="action_publish" title="Publier"><?php _e('Publish', ApmConfig::i18n_domain); ?></a>&nbsp;|&nbsp;</span>
						<?php endif; ?>

						<?php if( $node->status > -2 ) : ?>
							<span class="edit"><a href="<?php echo get_bloginfo('wpurl').'/wp-admin/post.php?post='.$node->wp_id.'&action=edit' ?>" title="Modifier" class="action_edit"><?php _e('Edit', ApmConfig::i18n_domain); ?></a>&nbsp;|&nbsp;</span>
						<?php endif; ?>
						
						<?php if( $node->status >= 0 ) : ?>
							<span class="action_change_template"><a href="#"><?php _e('Template'); ?></a>&nbsp;|&nbsp;</span>
						<?php endif ?>

						<?php if( $node->status > -2 ) : ?>
							<span class="drag"><a href="#" title="Déplacer" class="action-drag"><?php _e('Move', ApmConfig::i18n_domain); ?></a>&nbsp;|&nbsp;</span>
						<?php endif ?>
						
						<?php if(have_right()) : ?>
							<span class="delete"><a href="#" title="Supprimer" class="action-delete-page"><?php _e('Delete', ApmConfig::i18n_domain); ?></a></span>
						<?php endif; ?>
					</div>
				</td>

				<?php 
			    	//Hook to add a column td :
			    	do_action('apm_panel_page_add_col_after_2nd_td',$node);
			    ?>
			    
			    <?php if( $node->status > -2 ) : ?>
					<td class="etat column-etat">
						<?php if( $node->status > 1 ) : ?>
							<div class="picto-publish"></div>
							<strong><?php echo $status[$node->status] ?></strong>
						<?php else : ?>
							<div class="picto-unpublish"></div>
							<strong><?php echo $status[$node->status] ?></strong>
						<?php endif; ?>
						<p><?php echo preg_replace('|(\d{4}).(\d{2}).(\d{2}) (\d{2}).(\d{2}).(\d{2})|','$3/$2/$1',$node->publication_date) ?></p>
					</td>
	
					<td class="tags column-tags">
						<p class="template-name-<?php echo $node->template ?>"><?php cached_page_template_drowpdown($node->template); ?></p>
					</td>
	
					<td><input type="submit" name="" class="button-secondary action-add-page" value="<?php _e('Add New', ApmConfig::i18n_domain); ?>"></td>
				<?php else: ?>
					<td colspan="3"></td>
				<?php endif ?>
				
			</tr>
			
		<?php endif ?>

	<?php endforeach; ?>

<?php endif; ?>
