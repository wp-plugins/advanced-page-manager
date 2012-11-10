<?php
/**
 * ApmTreeAjax class
 * @package APM  
 */

require_once(dirname(__FILE__) ."/actions_ajax.php");

/**
 * Handles AJAX actions on the tree
 * Those actions correspond to actions defined on JS side in the $.apm_tree API (see js/tree.js)
 */
class ApmTreeAjax extends ApmActionsAjax{
	
	/**
	 * Retrieves the tree as a sequential array of HTML nodes, ready to be displayed
	 * in the back office "Pages" panel template.  
	 */
	public static function tree_load(){
		parent::$json_data += array('tree'=>'','total_displayed_nodes'=>0,'go_to_node'=>0);
		
		$tree = new ApmTreeData();
		$tree->load_last_tree();
		
		if( !empty($_POST['go_to_node']) ){
			$node_to_go_to = $_POST['go_to_node'];
			if( $tree->open_the_way_to_node($node_to_go_to) === false ){
				parent::add_error('Node "'. $node_to_go_to .'" not found.');
			}
			parent::$json_data['go_to_node'] = $node_to_go_to;
		}
		
		$tree_nodes = $tree->get_ready_to_display_tree();
		
		parent::$json_data['tree'] = self::get_html_tree($tree_nodes);
		parent::$json_data['total_displayed_nodes'] = count($tree_nodes);
		
		parent::send_json();
	}
	
	/**
	 * To add a new node somewhere in the tree. 
	 * It creates the WP page and insert it in the tree.
	 * Handles multiple insertions at once.
	 */
	public static function tree_add_new_node(){
		
		$tree = new ApmTreeData();
		$tree->load_last_tree();
		
		switch( $_POST['node_type'] ){
			case 'page':
				$new_node_name = trim(stripslashes($_POST['node_id']));
				
				$default_node_data = array('node_template'=>'');
				$new_node_data = isset($_POST['node_data']) && !empty($_POST['node_data']) ? $_POST['node_data'] : $default_node_data;
				$new_node_template = array_key_exists('node_template',$new_node_data) ? $new_node_data['node_template'] : '';
				
				parent::$json_data['insert_type'] = $_POST['edit_action'];
				
				if( isset($_POST['nodes_number']) && !empty($_POST['nodes_number']) && is_numeric($_POST['nodes_number']) && $_POST['nodes_number'] > 1 ){
					
					if( !empty($new_node_name) ){
						
						$new_node_ids = array();

						$nb_digits = strlen(strval($_POST['nodes_number']));
						if( $nb_digits == 1 ){
							$nb_digits = 2; //We still want 01, 02, 03 ... if nodes_number < 10
						}
						
						for($i=1;$i<=$_POST['nodes_number'];$i++){
							$insert_after = $_POST['edit_action'] == 'insert_after';
							
							$node_name = sprintf($new_node_name .' - %0'. $nb_digits . 'd', !$insert_after ? $i : $_POST['nodes_number'] - $i + 1 );
							$new_page_id = ApmTreeData::insert_wp_page($node_name,$new_node_template);
						
							if( !empty($new_page_id) ){
								if( is_numeric($_POST['index_node']) ){
									$new_node_ids[] = $tree->add_new_node($_POST['edit_action'],$_POST['index_node'],'page',$new_page_id);
									ApmTreeData::force_wp_page_slug_from_title($new_page_id); //Call this after WP tree synchronisation
								}
							} 
						}
						
						parent::$json_data['new_nodes'] = $insert_after ? array_reverse($new_node_ids) : $new_node_ids;
					}
					
				}else{
					if( !empty($new_node_name) ){
						
						$new_page_id = ApmTreeData::insert_wp_page($new_node_name,$new_node_template);
					
						if( !empty($new_page_id) ){
							if( is_numeric($_POST['index_node']) ){
								$new_node_id = $tree->add_new_node($_POST['edit_action'],$_POST['index_node'],'page',$new_page_id);
								ApmTreeData::force_wp_page_slug_from_title($new_page_id); //Call this after WP tree synchronisation
							}
						}
						
						parent::$json_data['new_node'] = $new_node_id;
					}
				}
				
				break;	
		}
		
		parent::send_json();
	}
	
	/**
	 * Alias of self::tree_add_new_node() used for multiple nodes insertion.
	 */
	public static function tree_add_multiple_nodes(){
		self::tree_add_new_node();
	}
	
	/**
	 * Edits (insert/move/delete) a node in the tree
	 */
	public static function tree_edit(){
		
		$tree = new ApmTreeData();
		$tree->load_last_tree();
		if( is_numeric($_POST['node_choice']) && is_numeric($_POST['index_node']) ){
			$tree->edit($_POST['edit_action'],$_POST['node_choice'],$_POST['index_node']);
		}
		
		parent::send_json();
	}

	/**
	 * Moves multiples nodes to a same destination
	 */
	public static function tree_move_multiple_nodes(){
		
		$tree = new ApmTreeData();
		$tree->load_last_tree();
		if( !empty($_POST['nodes_to_move']) ){
			if( $_POST['edit_action'] != 'delete' ){
				$tree->edit($_POST['edit_action'],$_POST['nodes_to_move'],$_POST['index_node']);
			}else{
				parent::add_error("Please use tree_delete_nodes() to delete multiple nodes!");
			}
		}else{
			parent::add_error("Please provide some nodes to move!");
		}
	
		parent::send_json();
	}	
	
	/**
	 * Deletes mutliple nodes and their children. To delete only one node and its children, use
	 * self::tree_edit() with "edit_action" = "delete"
	 */
	public static function tree_delete_nodes(){
		parent::$json_data += array('nb_deleted_nodes'=>array());
		
		$tree = new ApmTreeData();
		$tree->load_last_tree();
		
		if( !empty($_POST['nodes_to_delete']) ){
			parent::$json_data['nb_deleted_nodes'] = $tree->delete_multiple_nodes($_POST['nodes_to_delete']);
		}
		
		parent::send_json();
	}
	
	/**
	 * Retrieves nodes ids for a given root node : the result is an array containing ids of
	 * the root node and its children. 
	 */
	public static function tree_get_nodes(){
		parent::$json_data += array('tree_nodes'=>array(),'root_node'=>'');
		
		$root_node = isset($_POST['root_node']) && is_numeric($_POST['root_node']) ? $_POST['root_node'] : '';
		
		$tree = new ApmTreeData();
		$tree->load_last_tree(false,true,true,$root_node);
		
		parent::$json_data['tree_nodes'] = $root_node === '' ? $tree->get_tree_nodes() : $tree->get_tree_nodes($root_node);
		parent::$json_data['root_node'] = $root_node;
		
		parent::send_json();
	}
	
	/**
	 * Fresh start with a new tree synchronized with WP pages.
	 */
	public static function tree_reset(){
		
		$tree = new ApmTreeData();
		$tree->reset_tree_and_data();
		
		parent::send_json();
	}
	
	/**
	 * To kwnow which target nodes we can chose when moving a node in the tree.
	 * Return an array of the allowed nodes ids.
	 */
	public static function tree_get_allowed(){
		parent::$json_data += array('allowed_nodes'=>'');
		
		$tree = new ApmTreeData();
		$tree->load_last_tree();
		parent::$json_data['allowed_nodes'] = $tree->get_allowed_target_nodes($_POST['moving_node'],$_POST['edit_action']);
		
		parent::send_json();
	}
	
	/**
	 * Folds a given node and returns the HTML of the folded node.
	 */
	public static function tree_fold_node(){
		
		parent::$json_data += array('folded_node'=>'',
									'folded_sub_tree_nodes'=>array(),
						   		    'folded_sub_tree'=>'',
									'total_displayed_nodes'=>0
									);
		
		$tree = new ApmTreeData();
		$tree->load_last_tree();
		if( isset($_POST['node_to_fold']) && is_numeric($_POST['node_to_fold']) ){
			
			$node_to_fold = $_POST['node_to_fold'];
			
			$tree->fold_node($node_to_fold);
			
			parent::$json_data['folded_node'] = $node_to_fold;
			parent::$json_data['folded_sub_tree_nodes'] = $tree->get_tree_nodes($node_to_fold); //TODO: return only the visible nodes
			
			//Retrieve folded subtree to return it: 
			$tree_nodes = $tree->get_ready_to_display_tree($node_to_fold);
			
			parent::$json_data['folded_sub_tree'] = self::get_html_tree($tree_nodes);
			
			parent::$json_data['total_displayed_nodes'] = $tree->get_visible_nodes_number();	
			
		}else{
			parent::add_error("Please send a \"node_to_fold\" as POST variable.");
		}
		
		parent::send_json();
	}
	
	/**
	 * Unfolds a node and returns the HTML of the unfolded node and its children.
	 */
	public static function tree_unfold_node(){
		
		parent::$json_data += array('unfolded_node'=>'',
								    'unfolded_sub_tree_nodes'=>array(),
									'unfolded_sub_tree'=>'',
									'total_displayed_nodes'=>0
									);
		
		$tree = new ApmTreeData();
		$tree->load_last_tree();
		if( isset($_POST['node_to_unfold']) && is_numeric($_POST['node_to_unfold']) ){
			
			$tree->unfold_node($_POST['node_to_unfold']);
			
			parent::$json_data['unfolded_node'] = $_POST['node_to_unfold'];
			
			//Retrieve unfolded subtree to return it: 
			$tree_nodes = $tree->get_ready_to_display_tree($_POST['node_to_unfold']);
			
			parent::$json_data['unfolded_sub_tree_nodes'] = array_keys($tree_nodes);
			
			parent::$json_data['unfolded_sub_tree'] = self::get_html_tree($tree_nodes);
			
			parent::$json_data['total_displayed_nodes'] = $tree->get_visible_nodes_number();
			
		}else{
			parent::add_error("Please send a \"node_to_unfold\" as POST variable.");
		}
		
		parent::send_json();
	}
	
	/**
	 * Folds all nodes
	 */
	public static function tree_fold_all_nodes(){
	
		$tree = new ApmTreeData();
		$tree->load_last_tree();
		$tree->fold_all_nodes();
		
		parent::send_json();
	}
	
	/**
	 * Unfolds all nodes
	 */
	public static function tree_unfold_all_nodes(){
		
		$tree = new ApmTreeData();
		$tree->load_last_tree();
		$tree->unfold_all_nodes();
		
		parent::send_json();
	}
	
	/**
	 * Opens (unfolds) the necessary nodes to access to a given node, and 
	 * returns the HTML of the first unfolded node and its children.
	 */
	public static function tree_find_node(){
		parent::$json_data += array('node_to_find'=>'','node_to_replace'=>'',
						            'tree_to_replace_node_with'=>'','tree_nodes'=>array());
							
		$tree = new ApmTreeData();
		$tree->load_last_tree();
		
		$node_to_find = $_POST['node_to_find'];
		parent::$json_data['node_to_find'] = $node_to_find;
		
		$node_to_replace = $tree->open_the_way_to_node($node_to_find);
		if( $node_to_replace !== false ){
			parent::$json_data['node_to_replace'] = $node_to_replace;
			$tree_nodes = $tree->get_ready_to_display_tree($node_to_replace);
			parent::$json_data['tree_nodes'] = array_keys($tree_nodes);
			parent::$json_data['tree_to_replace_node_with'] = self::get_html_tree($tree_nodes);
		}else{
			parent::add_error("Node [$node_to_find] not found");
		}
		
		parent::send_json();
	}
	
	/**
	 * Loads a subtree given its root.
	 * Not used yet TODO: see if we remove this function 
	 */
	public static function tree_load_sub_tree(){
		parent::$json_data += array('sub_tree'=>'','node_to_replace'=>'','sub_tree_nodes'=>array());
		
		$root_node = isset($_POST['root_node']) && is_numeric($_POST['root_node']) ? $_POST['root_node'] : ApmTreeData::root_id;
		
		$tree = new ApmTreeData();
		$tree->load_last_tree();
		
		//Set the "tree_nodes" variable retrieved and used in the following included template:
		$sub_tree_nodes = $tree->get_ready_to_display_tree($root_node);
		
		parent::$json_data['sub_tree'] = self::get_html_tree($sub_tree_nodes);
		parent::$json_data['node_to_replace_by_subtree'] = $root_node;
		parent::$json_data['sub_tree_nodes'] = array_keys($sub_tree_nodes);
		
		parent::send_json();
	}
	
}