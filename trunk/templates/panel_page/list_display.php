<?php
/**
 * Template for displaying the tree.
 * The "$tree_nodes" is an array containing all the tree nodes.
 *
 * For each node, the following properties can be retrieved:
 * 		- apm_id : unique, internal node id
 * 		- wp_id : if the node is linked to a WP entity (page, collection...)
 * 		- type : page, collection...
 * 		- depth : depth in the tree
 * 		- parent : parent node
 * 		- nb_children : number of children
 * 		- children : array of childen ids
 * 		- is_folded : true if the node has children and is folded, false otherwise
 * 		- status : Can be 'Online', 'Online (waiting for approval)', 'Trash', or 'Offline'
 * 		- title
 * 		- template
 * 		- publication_date
 * 		- visibility (not implemented yet)
 * 		- author (not implemented yet)
 * 		- description (not implemented yet)
 * 		- url_front : url of the entity in front office
 * 		- url_edit : url of the entity in back office
 *
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
				<th scope="row" class="check-column">
					<input type="checkbox" name="post[]" class="row_select" value="1">
				</th>

				<td class="page-title column-title">
					<div class="has_subpage">
						<?php if( $node->nb_children > 0 ) : ?>

							<span class="picto-subpage"></span>

						<?php else : ?>
							&nbsp;
						<?php endif; ?>
					</div>
					<strong>
						<a class="row-title" href="<?php echo get_bloginfo('wpurl').'/wp-admin/post.php?post='.$node->wp_id.'&action=edit' ?>" title="<?php echo $node->title ?>"><?php echo $node->title ?></a>
					</strong>
					<div class="wrap-edit-title field-rename">
						<input type="text" value="<?php echo $node->title ?>" />
						<input type="button" value="OK" />
						<a href="#" class="cross-delete"><?php _e('Cancel', ApmConfig::i18n_domain); ?></a>
					</div>
					<div class="row-actions">
						<span class="rename"><a href="#" class="action_rename" title="Renommer"><?php _e('Rename', ApmConfig::i18n_domain); ?></a>&nbsp;|&nbsp;</span>

						<?php if( $node->status > 1 ) : ?>
							<span class="display"><a href="<?php echo $link_display ?>" class="action_display" title="Afficher"><?php _e('View', ApmConfig::i18n_domain); ?></a>&nbsp;|&nbsp;</span>
						<?php else : ?>
							<span class="display"><a href="<?php echo $link_preview ?>" class="action_display" title="Apercu"><?php _e('Preview', ApmConfig::i18n_domain); ?></a>&nbsp;|&nbsp;</span>
						<?php endif; ?>

						<?php if( $node->status > 1 ) : ?>
							<span class=""><a href="#" class="action_unpublish" title="Dépublier"><?php _e('Unpublish', ApmConfig::i18n_domain); ?></a>&nbsp;|&nbsp;</span>
						<?php else : ?>
							<span class=""><a href="#" class="action_publish" title="Publier"><?php _e('Publish', ApmConfig::i18n_domain); ?></a>&nbsp;|&nbsp;</span>
						<?php endif; ?>

						<span class="edit"><a href="<?php echo get_bloginfo('wpurl').'/wp-admin/post.php?post='.$node->wp_id.'&action=edit' ?>" title="Modifier" class="action_edit"><?php _e('Edit', ApmConfig::i18n_domain); ?></a>&nbsp;|&nbsp;</span>

						<span class="action_change_template"><a href="#"><?php _e('Template'); ?></a>&nbsp;|&nbsp;</span>

						<?php if(have_right()) : ?>
							<span class="delete"><a href="#" title="Supprimer" class="action-delete-page"><?php _e('Delete', ApmConfig::i18n_domain); ?></a></span>
						<?php endif; ?>
					</div>
				</td>

				<?php 
			    	//Hook to add a column td :
			    	do_action('apm_panel_page_add_col_after_2nd_td',$node);
			    ?>

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

				<td>
					<p><?php echo preg_replace('|(\d{4}).(\d{2}).(\d{2}) (\d{2}).(\d{2}).(\d{2})|','$3/$2/$1',$node->publication_date) ?></p>
				</td>

				<td class="tags column-tags">
					<p class="template-name-<?php echo $node->template ?>"><?php cached_page_template_drowpdown($node->template); ?></p>
				</td>

				<td>
					<a href="<?php echo $where_is_page_link ?>" class="button-secondary action-go-page"><?php _e('Where is it ?', ApmConfig::i18n_domain); ?></a>
				</td>
			</tr>
		<?php endif; ?>
		<?php $cpt++ ?>
	<?php endforeach; ?>

<?php endif; ?>
