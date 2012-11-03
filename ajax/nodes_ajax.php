<?php 
/**
 * ApmNodesAjax class
 * @package APM  
 */

require_once(dirname(__FILE__) ."/actions_ajax.php");

/**
 * Handles AJAX actions on nodes
 * Those actions correspond to actions defined on JS side in the $.apm_tree API (see js/tree.js)
 */
class ApmNodesAjax extends ApmActionsAjax{
	
	/**
	 * Sets a property for a given node and possibly its children if cascading is asked.
	 * Used for actions on tree nodes, not list nodes.
	 * $_POST['property'] can be 'node_title', 'node_template' or 'node_marked'
	 */
	public static function nodes_set_node_property(){
		parent::$json_data += array('updated_node'=>'','cascading'=>0,'sub_tree'=>'','sub_tree_nodes'=>array());
		
		if( isset($_POST['root_node']) && is_numeric($_POST['root_node'])
			&& isset($_POST['property']) && isset($_POST['value']) ){
				
			$node_to_update = $_POST['root_node'];
				
			//TODO : can be optimized if no cascading
			
			$tree = new ApmTreeData();
			$tree->load_last_tree(true,false,false,$node_to_update);
			
			$cascading = isset($_POST['cascading']) && $_POST['cascading'] == 1;
			
			$tree->update_nodes_property($_POST['property'],$_POST['value'],$node_to_update,$cascading);
			
			$sub_tree_nodes = array();
			if( $cascading ){
				$sub_tree_nodes = $tree->get_ready_to_display_tree($node_to_update,false,false,false); //Don't reload data!
			}else{
				$sub_tree_nodes = array($tree->get_ready_to_display_node($node_to_update,false,false)); //Don't reload data!
			}
			
			parent::$json_data['updated_node'] = $_POST['root_node'];
			parent::$json_data['cascading'] = $cascading ? 1 : 0;	
			parent::$json_data['sub_tree'] = self::get_html_tree($sub_tree_nodes);
			parent::$json_data['sub_tree_nodes'] = array_keys($sub_tree_nodes);
			
			parent::$json_data['property'] = $_POST['property'];	
			parent::$json_data['value'] = $_POST['value'];	
		}
		
		parent::send_json();
	}
	
	/**
	 * Sets a property for a given set of nodes.
	 * Used for actions on list nodes or specific given nodes in a tree.
	 * $_POST['property'] can be 'node_title', 'node_template' or 'node_marked'
	 */
	public static function nodes_set_nodes_property(){
		parent::$json_data += array('updated_nodes'=>array());
		
		if( !empty($_POST['nodes_to_update']) && is_array($_POST['nodes_to_update'])
			&& isset($_POST['property']) && isset($_POST['value']) ){
				
			$nodes_to_update = $_POST['nodes_to_update'];
			$cascading_nodes = !empty($_POST['cascading_nodes']) ? $_POST['cascading_nodes'] : array();
				
			$tree = new ApmTreeData();
			
			//Load wp data because we already know it will only apply 
			//to $nodes_to_update and not all nodes :
			$nodes_to_update = $tree->load_specific_nodes($nodes_to_update,$cascading_nodes);
			
			$tree->update_nodes_property($_POST['property'],$_POST['value']);
			
			$nodes_list = $tree->get_ready_to_display_nodes();
			
			parent::$json_data['updated_nodes'] = $nodes_to_update;
			parent::$json_data['nodes_html'] = self::get_html_tree($nodes_list,true);	

			parent::$json_data['property'] = $_POST['property'];	
			parent::$json_data['value'] = $_POST['value'];	
			
		}
		
		parent::send_json();
	}
	
	/**
	 * Sets the status for a given node and possibly its children if cascading is asked.
	 * Used for actions on tree nodes, not list nodes.
	 * $_POST['status'] can be 0 ('draft'), 1 (pending), 2 (published)
	 */
	public static function nodes_set_node_status(){
		parent::$json_data += array('updated_node'=>'','cascading'=>0,'sub_tree'=>'','sub_tree_nodes'=>array());
		
		if( isset($_POST['root_node']) && is_numeric($_POST['root_node'])
			&& isset($_POST['status']) ){
				
			$node_to_update = $_POST['root_node'];
				
			//TODO : can be optimized if no cascading
			
			$tree = new ApmTreeData();
			$tree->load_last_tree(true,false,false,$node_to_update); 
			
			$cascading = isset($_POST['cascading']) && $_POST['cascading'] == 1;
			
			$tree->update_nodes_status($_POST['status'],$node_to_update,$cascading);
			
			$sub_tree_nodes = array();
			if( $cascading ){
				$sub_tree_nodes = $tree->get_ready_to_display_tree($node_to_update,false,false,false);
			}else{
				$sub_tree_nodes = array($tree->get_ready_to_display_node($node_to_update,false,false));
			}
			
			parent::$json_data['updated_node'] = $node_to_update;
			parent::$json_data['cascading'] = $cascading ? 1 : 0;
			parent::$json_data['sub_tree'] = self::get_html_tree($sub_tree_nodes);
			parent::$json_data['sub_tree_nodes'] = array_keys($sub_tree_nodes);
		}
		
		parent::send_json();
	}
	
	/**
	 * Sets the status for a given set of nodes.
	 * Used for actions on list nodes or specific given nodes in a tree.
	 * $_POST['status'] can be 0 ('draft'), 1 (pending), 2 (published)
	 */
	public static function nodes_set_nodes_status(){
		parent::$json_data += array('updated_nodes'=>array());
		
		if( !empty($_POST['nodes_to_update']) && is_array($_POST['nodes_to_update'])
			&& isset($_POST['status']) ){
				
			$nodes_to_update = $_POST['nodes_to_update'];
			$cascading_nodes = !empty($_POST['cascading_nodes']) ? $_POST['cascading_nodes'] : array();
				
			$tree = new ApmTreeData();
			
			//Load wp data because we already know it will only apply 
			//to $nodes_to_update and not all nodes :
			$nodes_to_update = $tree->load_specific_nodes($nodes_to_update,$cascading_nodes); 
			
			$tree->update_nodes_status($_POST['status']);
			
			$nodes_list = $tree->get_ready_to_display_nodes();
			
			parent::$json_data['updated_nodes'] = $nodes_to_update;
			parent::$json_data['nodes_html'] = self::get_html_tree($nodes_list,true);
		}
		
		parent::send_json();
	}
	
	public static function nodes_unmark_all(){
		parent::$json_data += array('unmarked_nodes'=>array());
		
		$unmarked_nodes = ApmTreeData::unmark_all_current_user_nodes();
		parent::$json_data['unmarked_nodes'] = $unmarked_nodes;
		
		parent::send_json();
	}
		
}