<?php

require_once(dirname(__FILE__).'/config.php');
require_once(dirname(__FILE__).'/tree.php');
require_once(dirname(__FILE__).'/tree_db.php');
require_once(dirname(__FILE__).'/nodes_data.php');
require_once(dirname(__FILE__).'/tree_state.php');

class ApmTreeData{
	
	/**
	 * Instance of ApmTree
	 * @var ApmTree
	 */
	private $apm_tree;
	
	/**
	 * Instance of ApmTreeState
	 * @var ApmTreeState
	 */
	private $tree_state;
	
	/**
	 * Instance of ApmNodeDataDisplayCollection
	 * @var ApmNodeDataDisplayCollection
	 */
	private $nodes_data;	
	
	const root_id = ApmTree::root_id;
	
	public function __construct(){
		$this->apm_tree = new ApmTree();
		$this->tree_state = new ApmTreeState();
		$this->nodes_data = new ApmNodeDataDisplayCollection();
	}
	
	public function get_ready_to_display_tree($root=self::root_id,$json=false,$depth=false,$load_data=true){
		$ready_to_display_tree = array();
		
		if( $this->tree_is_empty() ){
			return $json ? json_encode($ready_to_display_tree) : $ready_to_display_tree;
		}
		
		//TODO: handle any $depth...
		if( $depth !== false ){
			switch( $depth ){
				case 0:
					$this->tree_state->fold_node($root);
					$this->save();
					break;
				case 1:
					$direct_children = $this->apm_tree->get_children($root);
					if( !empty($direct_children) ){
						$this->tree_state->fold_nodes($direct_children);
						$this->save();
					}
					break;
			}
			
		}
		
		$unfolded_nodes = $this->tree_state->get_unfolded_nodes($this->apm_tree->get_nodes_flat($root));
		
		$root_depth = $this->apm_tree->get_node_depth($root);
		
		//Retrieve visible nodes from APM tree (this may retrieve nodes that don't exist in WP database,
		//if deleted from outside the plugin)
		$tree_to_display = $this->apm_tree->get_ready_to_display_tree($root,$root_depth,false,$unfolded_nodes);
		
		$apm_ids_to_display = array_keys($tree_to_display);
		
		if( $load_data ){
			//Load Wordpress data just before display, to be sure to load only necessary data:
			$this->nodes_data->load_multiple($apm_ids_to_display);
			//Now, $this->nodes_data contains only nodes that really exist as WP pages.
			//However, they may have the 'auto-draft' or 'trash' status.
		}
		
		//Retrieve number of first level node to know if we can move nodes :
		//A node is movable only if : depth > 1 OR (depth == 1 AND there is other nodes with depth == 1)  
		$nb_first_level_nodes = $this->get_apm_tree_nb_nodes(true);
		
		//We loop on the nodes existing in our APM tree so that we can warn about pages that
		//exist in APM tree and not in WP tree (deleted from outside the plugin) :
		foreach($tree_to_display as $apm_id=>$node_tree_infos){
			
			$node_data = $this->nodes_data->get($apm_id);
			
			//A node exists in APM tree but not in WP : set special data to warn about it :
			if( empty($node_data) ){
				$node_data = new ApmNodeDataDisplay();
				$node_data->set_intern_data(new ApmNodeDataIntern(array('apm_id'=>$apm_id,'type'=>'page','wp_id'=>$apm_id)));
				$node_data->set_node_is_not_in_wp();
			}
			
			$node_data->set_node_position(array('depth'=>$node_tree_infos['depth'],
												'parent'=>$node_tree_infos['parent'],
												'children'=>$node_tree_infos['children'],
												'nb_children'=>$node_tree_infos['nb_children']
												)
										  );

			$is_folded = $node_tree_infos['nb_children'] > 0 && !in_array($apm_id,$unfolded_nodes);
			$node_data->set_is_folded($is_folded);
			
			$node_data->set_is_movable($node_tree_infos['depth'] > 1 || $nb_first_level_nodes > 1);
			
			$ready_to_display_tree[$apm_id] = $json ? $node_data->get_flattened() : $node_data;
			
		}
		
		return $json ? json_encode($ready_to_display_tree) : $ready_to_display_tree;
	}
	
	/**
	 * Retrieves a node without its descendance.
	 * @param integer $node
	 * @param boolean $json
	 */
	public function get_ready_to_display_node($node,$json=false,$load_data=true){
		
		$node_tree_infos = $this->apm_tree->get_node_tree_infos($node);
		
		if( $load_data ){
			//Load Wordpress data of the node:
			$this->nodes_data->load_one($node);
		}
		
		$node_data = $this->nodes_data->get($node);
			
		$node_data->set_node_position(array('depth'=>$node_tree_infos['depth'],
											'parent'=>$node_tree_infos['parent'],
											'children'=>$node_tree_infos['children'],
											'nb_children'=>$node_tree_infos['nb_children']
											)
									  );

		$is_folded = $node_tree_infos['nb_children'] > 0 && $this->tree_state->node_is_folded($node);
		$node_data->set_is_folded($is_folded);
		
		//Retrieve number of first level node to know if we can move nodes :
		//A node is movable only if : depth > 1 OR (depth == 1 AND there is other nodes with depth == 1)  
		$nb_first_level_nodes = $this->get_apm_tree_nb_nodes(true);
		$node_data->set_is_movable($node_tree_infos['depth'] > 1 || $nb_first_level_nodes > 1);

		return $json ? $node_data->get_flattened() : $node_data;
	}
	
	/**
	 * All data must be loaded (tree, intern and wp) before calling this.
	 */
	public function get_ready_to_display_nodes($json=false){
		
		if( $this->nodes_data->is_empty() ){
			return array();
		}
		
		$unfolded_nodes = $this->tree_state->get_unfolded_nodes($this->nodes_data->get_apm_ids());

		//Retrieve number of first level node to know if we can move nodes :
		//A node is movable only if : depth > 1 OR (depth == 1 AND there is other nodes with depth == 1)  
		$nb_first_level_nodes = $this->get_apm_tree_nb_nodes(true);
		
		$ready_to_display_nodes = array();
		
		foreach($this->nodes_data->get_array() as $apm_id=>$node){
			$node_tree_infos = $this->apm_tree->get_node_tree_infos($apm_id);
			$node->set_node_position($node_tree_infos);
			
			$is_folded = $node_tree_infos['nb_children'] > 0 && !in_array($apm_id,$unfolded_nodes);
			$node->set_is_folded($is_folded);
			
			$node->set_is_movable($node_tree_infos['depth'] > 1 || $nb_first_level_nodes > 1);
			
			$ready_to_display_nodes[$apm_id] = $node;
		}
		
		return $json ? json_encode($ready_to_display_nodes) : $ready_to_display_nodes;
	}
	
	public function get_tree_nodes($root=self::root_id){
		$all_nodes_ids = $this->apm_tree->get_nodes_flat($root);
		sort($all_nodes_ids);
		return $all_nodes_ids;
	}
	
	private function do_edit($action,$node_choice,$index_node){
		
		//$synchronize_only_my_children = $this->get_updated_subtrees_roots_on_action($action,$node_choice,$index_node);
		$synchronisation_data = $this->get_synchronisation_data_on_action($action,$node_choice,$index_node);
		
		$went_ok = false;
		
		switch($action){
			case 'insert_before':
				$went_ok = $this->apm_tree->move_node_before($node_choice,$index_node);
				break;
			case 'insert_after':
				$went_ok = $this->apm_tree->move_node_after($node_choice,$index_node);
				break;
			case 'insert_child':
				$went_ok = $this->apm_tree->move_node_as_child($node_choice,$index_node);
				break;
			case 'delete':
				if( $node_choice == $index_node ){
					$nb_deleted = $this->delete_nodes($node_choice);
					if( $nb_deleted > 0 ){
						$resave_data = true;
						$went_ok = true;
					}
				}
				break;
		}
		
		if( $went_ok ){
			$this->synchronize_tree_with_wp_entities($synchronisation_data);
			wp_update_post(array('ID'=>$node_choice)); //So that date_modified changes!
		}
		
		return $went_ok;
	}
	
	public function edit($action,$nodes_choice,$index_node){
		
		$went_ok = true;
		
		if( is_array($nodes_choice) ){
			foreach($nodes_choice as $node_choice){
				$went_ok &= $this->do_edit($action,$node_choice,$index_node);
			}
		}elseif( is_numeric($nodes_choice) ){
			$went_ok &= $this->do_edit($action,$nodes_choice,$index_node);
		}else{
			$went_ok = false;
		}
		
		if( $went_ok ){
			$this->save();
		}
		
		return $went_ok;
	
	}
	
	public function add_new_node($action,$index_node,$type,$wp_id){
		
		if( $wp_id == 0 ){
			return false;
		}
		
		$new_node_apm_id = $wp_id;

		$new_node_tree = new ApmTree($new_node_apm_id);
		
		//$synchronize_only_my_children = $this->get_updated_subtrees_roots_on_action($action,false,$index_node);
		$synchronisation_data = $this->get_synchronisation_data_on_action($action,false,$index_node,$new_node_apm_id);
		
		switch($action){
			case 'insert_before':
				$this->apm_tree->add_before($index_node,$new_node_tree);
				break;
			case 'insert_after':
				$this->apm_tree->add_after($index_node,$new_node_tree);
				break;
			case 'insert_child':
				$this->apm_tree->add_child($index_node,$new_node_tree);
				break;
		}
		
		$this->nodes_data->add($new_node_apm_id,$type,$wp_id);
		
		$this->tree_state->add_node($new_node_apm_id);
		
		$this->save();
		
		$this->synchronize_tree_with_wp_entities($synchronisation_data);
		
		return $new_node_apm_id;
	}
	
	/**
	 * Resets the tree and data attached to its nodes.
	 */
	public function reset_tree_and_data(){
		
		ApmTreeDb::reset_tree();
		ApmTreeState::delete_all();
		
		$this->set_tree_and_nodes_data_from_wp_entities();
		
		$tree_nodes = $this->apm_tree->get_nodes_flat();
		
		$this->tree_state->load_nodes($tree_nodes); 
		
		$this->save();
		$this->nodes_data->load_multiple($tree_nodes);
		
		//To reindex correctly WP pages orders :
		$this->synchronize_tree_with_wp_entities();
	}
	
	/**
	 * Carreful!! We delete WP elements here!
	 * This is unreversible deletion, not trash!
	 * @param integer $root_node
	 * @return integer
	 */
	private function delete_nodes($root_node){
		$nb_deleted_nodes = 0;
		
		$this->nodes_data->load_multiple($root_node);
		
		$root_node_data = $this->nodes_data->get($root_node);
		
		if( $root_node_data !== null ){
			if( $root_node_data->type == 'page' ){
				
				$nodes_to_delete = $this->apm_tree->get_nodes_flat($root_node);
				$this->nodes_data->load_multiple($nodes_to_delete,true,true);
				
				if( !empty($nodes_to_delete) ){
					foreach($nodes_to_delete as $node){
						$page_id = $this->nodes_data->get($node)->wp_id;
						if( $this->nodes_data->get($node)->type == 'page' ){
							wp_delete_post($page_id,true);
						}
					}
					
					$this->apm_tree->delete_sub_tree($root_node);
					$this->nodes_data->delete($nodes_to_delete);
					$this->tree_state->delete_nodes($nodes_to_delete); //TODO: We should delete it from every users metas ???
					
					$nb_deleted_nodes = count($nodes_to_delete);
					
					$this->save();
				}
				
			}
		}else{
			//$root_node exists in APM tree but was not found in WP entities...
			//It must have been deleted from WP by some other way... but we still want
			//to delete it from our APM tree :
			$nodes_to_delete = $this->apm_tree->get_nodes_flat($root_node);
			if( !empty($nodes_to_delete) ){
				$this->apm_tree->delete_sub_tree($root_node);
				$this->tree_state->delete_nodes($nodes_to_delete);
				if( ApmAddons::addon_is_on('flagged_pages') ){
					ApmMarkedNodes::delete_multiple($nodes_to_delete);
				}
				$nb_deleted_nodes = count($nodes_to_delete);
				$this->save();
			}
		}
		
		return $nb_deleted_nodes;
	}
	
	public function delete_multiple_nodes($nodes_to_delete){
		$nb_deleted_nodes = 0;
		foreach($nodes_to_delete as $apm_id){
			$nb_deleted_nodes += $this->delete_nodes($apm_id);
		}
		return $nb_deleted_nodes;
	}
	
	public function untrash_nodes($root_node, $cascading=false){
		
		$nodes_to_untrash = $cascading ? $this->apm_tree->get_nodes_flat($root_node) : array($root_node);

		if( !empty($nodes_to_untrash) ){
			foreach($nodes_to_untrash as $node){
				$page_id = $this->nodes_data->get($node)->wp_id;
				if( $this->nodes_data->get($node)->type == 'page' ){
					wp_untrash_post($page_id);
				}
			}
		}
		
		return $nodes_to_untrash;
	}
	
	/**
	 * This update function updates object attributes AND stores updates in database (post field, template, marked info...)
	 * There is no need to relaod data after this, as object attributes are updated.  
	 * @param string $property
	 * @param string $value
	 * @param array|integer $nodes_to_update
	 * @param boolean $cascading
	 */
	public function update_nodes_property($property,$value,$nodes_to_update=array(),$cascading=false){
		
		if( !is_array($nodes_to_update) && is_numeric($nodes_to_update) ){
			$nodes_to_update = $cascading ? $this->apm_tree->get_nodes_flat($nodes_to_update) : array($nodes_to_update);
		}
		
		$this->nodes_data->update_nodes_property($property,$value,$nodes_to_update);
	}
	
	/**
	 * Idem as update_nodes_property
	 * @param string $status
	 * @param array|integer $nodes_to_update
	 * @param boolean $cascading
	 */
	public function update_nodes_status($status,$nodes_to_update=array(),$cascading=false){
		
		if( !is_array($nodes_to_update) && is_numeric($nodes_to_update) ){
			$nodes_to_update = $cascading ? $this->apm_tree->get_nodes_flat($nodes_to_update) : array($nodes_to_update);
		}

		$this->nodes_data->update_nodes_status($status,$nodes_to_update);
	}
	
	public function fold_node($node){
		$this->tree_state->fold_node($node); 
		$this->save_tree_state();
	}
	
	public function fold_all_nodes(){
		$this->tree_state->fold_all(); 
		ApmTreeState::delete_for_current_user();
	}
	
	public function unfold_node($node){
		$this->tree_state->unfold_node($node); 
		$this->save_tree_state();
	}
	
	public function unfold_all_nodes(){
		$this->tree_state->unfold_all(); 
		$this->save_tree_state();
	}
	
	public function open_the_way_to_node($node_to_find){
		$lowerest_node_to_replace = '';
		
		$path_to_node = $this->apm_tree->get_path_to_node($node_to_find);
		if( !empty($path_to_node) ){
			array_pop($path_to_node);
			foreach($path_to_node as $node){
				if( $this->tree_state->node_is_folded($node) ){
					$lowerest_node_to_replace = $node;
					break;
				}
			}
			$this->tree_state->unfold_nodes($path_to_node);
			$this->save_tree_state();
		}else{
			//Node not found in the tree...
			return false;
		}
		
		return $lowerest_node_to_replace;
	}
	
	public function delete_wp_element($wp_id){
		$apm_id = $this->nodes_data->get_node_apm_id_by_wp_id($wp_id);
		if( !empty($apm_id) ){
			$this->delete_nodes($apm_id);
		}
	}
	
	public function get_allowed_target_nodes($moving_node,$action){
		$allowed = $this->apm_tree->get_allowed_target_nodes($moving_node,$action);
		sort($allowed);
		return $allowed;
	}
	
	public static function delete_database_data($including_plugin_options=true){
		if( $including_plugin_options ){
			ApmOptions::delete_database_data();
		}
		ApmTreeDb::delete_database_data();
		ApmTreeState::delete_all();
		if( ApmAddons::addon_is_on('flagged_pages') ){
			ApmMarkedNodes::delete_all_users_marked_nodes();
		}
	}
	
	/**
	 * Launch this to reload and update tree data when re-installing the plugin
	 */
	public static function update_tree_data_on_install(){
		
		//If tree data is found, update it :
		$existing_tree = ApmTreeDb::get_last_tree();
		if( !empty($existing_tree) ){
			$tree = new ApmTreeData();
			$tree->reset_tree_and_data();
		}
		
	}
	
	/**
	 * Insert a given page in the APM tree
	 * @param object $post
	 */
	public static function insert_page_from_outside($page){
		$tree = new ApmTreeData();
		$tree->load_last_tree();
		if( !$tree->is_wp_page_in_tree($page->ID) && in_array($page->post_status,ApmConfig::$allowed_post_status) ){
			$parent_id = empty($page->post_parent) ? ApmTreeData::root_id : $page->post_parent;
			$insert_infos = $tree->get_new_page_insert_infos_from_sibling($parent_id,$page->menu_order);
			$tree->add_new_node($insert_infos['action'],$insert_infos['index_node'],'page',$page->ID);
		}
	}
	
	private function get_new_page_insert_infos_from_sibling($parent_id,$sibling_menu_order){
		
		$sibling_menu_order = (int)$sibling_menu_order;
		
		$insert_infos = array('action'=>'insert_child','index_node'=>$parent_id);
		
		$parent_tree_infos = $this->apm_tree->get_node_tree_infos($parent_id);
		$siblings = $parent_tree_infos['children'];
		
		if( $sibling_menu_order < (count($siblings)-1) ){
			$insert_infos['action'] = 'insert_after';
			$insert_infos['index_node'] = $siblings[$sibling_menu_order];
		}
		
		return $insert_infos;
	}
	
	/**
	 * Checks if a given page id is in the APM tree
	 * @param int $page_wp_id
	 * @return boolean
	 */
	public function is_wp_page_in_tree($page_wp_id){
		//Assumes that apm_id = wp_id
		$tree_nodes = $this->get_tree_nodes();
		return in_array($page_wp_id,$tree_nodes);
	}
	
	/**
	 * If $load_data = true and $no_wp_data = true, will load only internal data (type and wp_id).
	 * @param boolean $load_data
	 * @param boolean $no_wp_data
	 */
	public function load_last_tree($load_data=false,$no_wp_data=false,$no_tree_state_data=false,$only_from_root=''){
		
		$tree = ApmTreeDb::get_last_tree();
		
		if( !empty($tree) ){
			
			$this->apm_tree = new ApmTree($tree);
			
			//For most treatments, we don't need the Wordpress data to be loaded.
			//The Wordpress infos are loaded just before display (see $this->get_ready_to_display_tree()).
			if( $load_data ){
				$this->nodes_data->load_multiple($this->apm_tree->get_nodes_flat($only_from_root),$no_wp_data);
			}
			
			if( !$no_tree_state_data ){
				//Load tree state from user meta data:
				$this->tree_state->load($this->apm_tree->get_nodes_flat());
				
				if( $this->tree_state->is_empty() && empty($only_from_root) ){
					$this->tree_state->load_nodes($this->apm_tree->get_nodes_flat());
					$this->save_tree_state();
				}
			}
			
		}else{
			$this->reset_tree_and_data();
		}
		
	}
	
	/**
	 * Loads given $node_to_load : retrieves tree data, wp_ids (if load_data=true), and
	 * wp data (if load_data=true and $no_wp_data=false).
	 * @param $nodes_to_load
	 * @param $cascading_nodes : nodes we have to retrieve children for.
	 * @param $load_data
	 * @param $no_wp_data
	 */
	public function load_specific_nodes($nodes_to_load,$cascading_nodes,$no_wp_data=false){
		$tree = ApmTreeDb::get_last_tree();
		
		if( !empty($tree) ){
			$this->apm_tree = new ApmTree($tree);
			
			if( !empty($cascading_nodes) ){
				foreach($cascading_nodes as $apm_id){
					if( in_array($apm_id,$nodes_to_load) ){
						$nodes_to_load = array_merge($nodes_to_load,$this->apm_tree->get_children($apm_id));
					}
				}
				$nodes_to_load = array_unique($nodes_to_load);
			}
			
			$this->nodes_data->load_multiple($nodes_to_load,$no_wp_data);
			
			$this->tree_state->load();
		}
		
		return $nodes_to_load;
	}
	
	public function save(){
		$this->save_tree();
		$this->save_tree_state();
	}
	
	private function save_tree(){
		ApmTreeDb::save_tree($this->apm_tree->get_tree());
	}
	
	private function save_tree_state(){
		//Save tree state:
		$this->tree_state->save();
	}
	
	/**
	 * Synchronizes our separate arbo with WP "real" pages
	 * !!Careful!! $this->nodes_data must be loaded before calling this!
	 */
	public function synchronize_tree_with_wp_entities($synchronisation_data=array(),$synchronize_only_my_children=array()){
		global $wpdb;
		
		if( empty($synchronisation_data) && empty($synchronize_only_my_children) ){
			//Synchronize all tree!
		
			$tree = $this->apm_tree->get_tree();
			
			//To avoid calling $this->nodes_data->get([node])->wp_id each time:
			$wp_ids = $this->nodes_data->get_wp_ids_from_apm_ids($this->apm_tree->get_nodes_flat(self::root_id));
			foreach($tree as $parent=>$children){
				$parent_wp_id = $wp_ids[$parent]; 
				$children_wp_ids = array();
				foreach($children as $child){
					$children_wp_ids[] = $wp_ids[$child];
				}
				if( !empty($children_wp_ids) ){
					//We want to set order too... so we have to make one request per child!!
					//$sql = "UPDATE ". $wpdb->posts ." SET post_parent='$parent_wp_id' WHERE ID IN ('". implode("','",$children_wp_ids) ."')";
					//$results = $wpdb->query($sql);
					$cpt=0;
					foreach($children_wp_ids as $child_wp_id){
						$sql = "UPDATE ". $wpdb->posts ." SET post_parent='$parent_wp_id', menu_order='$cpt' WHERE ID='$child_wp_id'";
						$results = $wpdb->query($sql);
						$cpt++;
					}
				}
			}
			
		}else{
			
			if( !empty($synchronisation_data) ){
				
				//Optimized synchronisation :
				
				$wp_ids = $this->nodes_data->get_wp_ids_from_apm_ids($synchronisation_data['all_apm_ids']);
				
				foreach($synchronisation_data['actions'] as $apm_id => $actions){
					
					$set = array();
					$ok_to_update = true;
					
					foreach($actions as $action=>$value){
						switch($action){
							case 'order':
								if($value === '--'){
									$set[] = "menu_order=menu_order-1";
								}elseif($value === '++'){
									$set[] = "menu_order=menu_order+1";
								}else{
									$set[] = "menu_order='$value'";
								}
								break;
							case 'parent':
								if( array_key_exists($value,$wp_ids) ){
									$set[] = "post_parent='". $wp_ids[$value] ."'";
								}else{
									$ok_to_update = false;
								}
								break;
						}
					}
					
					
					
					$sql_set = implode(", ",$set);
					
					if( $ok_to_update && array_key_exists($apm_id,$wp_ids) ){
						$sql = "UPDATE ". $wpdb->posts ." SET $sql_set WHERE ID='". $wp_ids[$apm_id] ."'";
						$results = $wpdb->query($sql);
					}
						
				}
				
			}else{
				
				//Synchronise only direct children of the fathers nodes passed in $synchronize_only_my_children:
				//When comming from an editing action, $synchronize_only_my_children contains 1 or 2 items.
				foreach($synchronize_only_my_children as $father){
					$children = $this->apm_tree->get_children($father);
					
					//To avoid calling $this->nodes_data->get([node])->wp_id each time:
					$wp_ids = $this->nodes_data->get_wp_ids_from_apm_ids(array_merge($children,array($father)));
					
					$cpt=0;
					foreach($children as $child){
						$sql = "UPDATE ". $wpdb->posts ." SET post_parent='". $wp_ids[$father] ."', menu_order='$cpt' WHERE ID='". $wp_ids[$child] ."'";
						$results = $wpdb->query($sql);
						$cpt++;
					}
				}
				
			}
		}
	}
	
	/**
	 * THE function that builds the APM tree from Wordpress existing pages  
	 * on plugin initialization, or when tree data reset is asked.
	 */
	public function set_tree_and_nodes_data_from_wp_entities($allow_autodrafts=false){
		global $wpdb;
		
		$root_apm_id = self::root_id;
		
		$tree = array($root_apm_id=>array());
		$this->nodes_data->add($root_apm_id,'root',0,true);
		
		$allowed_post_status = ApmConfig::$allowed_post_status;
		
		if( $allow_autodrafts ){
			$allowed_post_status[] = 'auto-draft';
		}
		
		$allowed_post_status = apply_filters('apm_allowed_post_status',$allowed_post_status,'set_tree_and_nodes_data_from_wp_entities');
		
		$allowed_post_status = array_map("addslashes",$allowed_post_status);
		
		$sql_status = " AND post_status IN ('". implode("','",$allowed_post_status) ."') ";
		
		$sql = "SELECT ID,post_parent 
					FROM $wpdb->posts 
					WHERE post_type='page' $sql_status 
					ORDER BY post_parent ASC, menu_order ASC";
		
		$pages = $wpdb->get_results($sql);
		
		if( !empty($pages) ){

			foreach($pages as $page){
				$page_apm_id = $page->ID;
				if( $page->post_parent == 0 ){
					$tree[$root_apm_id][] = $page_apm_id; 
				}else{
					@$tree[$page->post_parent][] = $page_apm_id;
				}
				$this->nodes_data->add($page_apm_id,'page',$page->ID,true);
			}
			
		}
		
		$this->apm_tree = new ApmTree($tree);
		
	}
	
	public function get_updated_subtrees_roots_on_action($action,$node_choice,$index_node){
		$updated_subtrees_roots = array();
		switch($action){
			case 'insert_before':
			case 'insert_after':
				if( $node_choice !== false ){
					$updated_subtrees_roots[] = $this->apm_tree->get_node_father($node_choice);
				}
				$updated_subtrees_roots[] = $this->apm_tree->get_node_father($index_node);
				break;
			case 'insert_child':
				if( $node_choice !== false ){
					$updated_subtrees_roots[] = $this->apm_tree->get_node_father($node_choice);
				}
				$updated_subtrees_roots[] = (int)$index_node;
				break;
			case 'delete':
				$updated_subtrees_roots[] = $this->apm_tree->get_node_father($node_choice);
				break;
		}
		return array_unique($updated_subtrees_roots);
	}
	
	
	/**
	 * Retrieves updates on parent and order to apply to WP entities to synchronize WP tree with our tree
	 * on edit actions (inserts/delete).
	 */
	public function get_synchronisation_data_on_action($action,$node_choice,$index_node,$new_node=0){
		$synchronisation_data = array('all_apm_ids'=>array(),'actions'=>array());
		
		switch($action){
			case 'insert_before':
				
				$index_node_order = $this->apm_tree->get_node_order($index_node);
				
				if( $node_choice !== false && $this->apm_tree->are_siblings($node_choice,$index_node) ){
					
					if( $this->apm_tree->compare_siblings_order($node_choice,$index_node) > 0 ){
						$synchronisation_data['actions'][$node_choice] = array('order'=>$index_node_order-1);
						$synchronisation_data['all_apm_ids'][] = $node_choice;
						$siblings_to_update = $this->apm_tree->get_siblings_interval($node_choice,$index_node,false,false);
						foreach($siblings_to_update as $apm_id){
							$synchronisation_data['actions'][$apm_id] = array('order'=>'--');
							$synchronisation_data['all_apm_ids'][] = $apm_id;
						}
					}else{
						$synchronisation_data['actions'][$node_choice] = array('order'=>$index_node_order);
						$synchronisation_data['all_apm_ids'][] = $node_choice;
						$siblings_to_update = $this->apm_tree->get_siblings_interval($index_node,$node_choice,true,false);
						foreach($siblings_to_update as $apm_id){
							$synchronisation_data['actions'][$apm_id] = array('order'=>'++');
							$synchronisation_data['all_apm_ids'][] = $apm_id;
						}
					}
					
				}else{
				
					$index_node_father = $this->apm_tree->get_node_father($index_node);
					
					if( $node_choice !== false ){
						$siblings_to_update = $this->apm_tree->get_siblings_after($node_choice,false);
						foreach($siblings_to_update as $apm_id){
							$synchronisation_data['actions'][$apm_id] = array('order'=>'--');
							$synchronisation_data['all_apm_ids'][] = $apm_id;
						}
						
						$synchronisation_data['actions'][$node_choice] = array('order'=>$index_node_order,
																			   'parent'=>$index_node_father
																			   );
						$synchronisation_data['all_apm_ids'][] = $node_choice;
						
					}elseif( !empty($new_node) ){
						
						$synchronisation_data['actions'][$new_node] = array('order'=>$index_node_order,
																			'parent'=>$index_node_father
																			);
						$synchronisation_data['all_apm_ids'][] = $new_node;
					}
					
					$synchronisation_data['all_apm_ids'][] = $index_node_father;
					
					$siblings_to_update = $this->apm_tree->get_siblings_after($index_node,true);
					foreach($siblings_to_update as $apm_id){
						$synchronisation_data['actions'][$apm_id] = array('order'=>'++');
						$synchronisation_data['all_apm_ids'][] = $apm_id;
					}
					
				}
				
				break;
				
			case 'insert_after':
				
				$index_node_order = $this->apm_tree->get_node_order($index_node);
				
				if( $node_choice !== false && $this->apm_tree->are_siblings($node_choice,$index_node) ){
					
					if( $this->apm_tree->compare_siblings_order($node_choice,$index_node) > 0 ){
						$synchronisation_data['actions'][$node_choice] = array('order'=>$index_node_order);
						$synchronisation_data['all_apm_ids'][] = $node_choice;
						$siblings_to_update = $this->apm_tree->get_siblings_interval($node_choice,$index_node,false,true);
						foreach($siblings_to_update as $apm_id){
							$synchronisation_data['actions'][$apm_id] = array('order'=>'--');
							$synchronisation_data['all_apm_ids'][] = $apm_id;
						}
					}else{
						$synchronisation_data['actions'][$node_choice] = array('order'=>$index_node_order+1);
						$synchronisation_data['all_apm_ids'][] = $node_choice;
						$siblings_to_update = $this->apm_tree->get_siblings_interval($index_node,$node_choice,false,false);
						foreach($siblings_to_update as $apm_id){
							$synchronisation_data['actions'][$apm_id] = array('order'=>'++');
							$synchronisation_data['all_apm_ids'][] = $apm_id;
						}
					}
					
				}else{
				
					$index_node_father = $this->apm_tree->get_node_father($index_node);
					
					if( $node_choice !== false ){
						$siblings_to_update = $this->apm_tree->get_siblings_after($node_choice,false);
						foreach($siblings_to_update as $apm_id){
							$synchronisation_data['actions'][$apm_id] = array('order'=>'--');
							$synchronisation_data['all_apm_ids'][] = $apm_id;
						}
						
						$synchronisation_data['actions'][$node_choice] = array('order'=>$index_node_order+1,
																			   'parent'=>$index_node_father
																			   );
						$synchronisation_data['all_apm_ids'][] = $node_choice;
						
					}elseif( !empty($new_node) ){
						
						$synchronisation_data['actions'][$new_node] = array('order'=>$index_node_order+1,
																			'parent'=>$index_node_father
																			);
						$synchronisation_data['all_apm_ids'][] = $new_node;
					}
					
					$synchronisation_data['all_apm_ids'][] = $index_node_father;
					
					$siblings_to_update = $this->apm_tree->get_siblings_after($index_node,false);
					foreach($siblings_to_update as $apm_id){
						$synchronisation_data['actions'][$apm_id] = array('order'=>'++');
						$synchronisation_data['all_apm_ids'][] = $apm_id;
					}
					
					$synchronisation_data['all_apm_ids'][] = $index_node;
				}
				
				break;
				
			case 'insert_child':
				
				$new_order = $this->apm_tree->get_nb_children($index_node);
				
				if( $node_choice !== false ){
					
					if( $this->apm_tree->get_node_father($node_choice) == $index_node ){
						$new_order--;
					}
					
					$siblings_to_update = $this->apm_tree->get_siblings_after($node_choice,false);
					foreach($siblings_to_update as $apm_id){
						$synchronisation_data['actions'][$apm_id] = array('order'=>'--');
						$synchronisation_data['all_apm_ids'][] = $apm_id;
					}
					
					$synchronisation_data['actions'][$node_choice] = array('order'=>$new_order,
																		   'parent'=>$index_node
																		   );
					$synchronisation_data['all_apm_ids'][] = $node_choice;
					
				}elseif( !empty($new_node) ){
					
					$synchronisation_data['actions'][$new_node] = array('order'=>$new_order,
																		'parent'=>$index_node
																		);
					$synchronisation_data['all_apm_ids'][] = $new_node;
				}
				
				$synchronisation_data['all_apm_ids'][] = $index_node;
				
				break;
				
			case 'delete':
				
				$siblings_to_update = $this->apm_tree->get_siblings_after($node_choice,false);
				foreach($siblings_to_update as $apm_id){
					$synchronisation_data['actions'][$apm_id] = array('order'=>'--');
					$synchronisation_data['all_apm_ids'][] = $apm_id;
				}
					
				break;
		}
		
		$synchronisation_data['all_apm_ids'] = array_unique($synchronisation_data['all_apm_ids']);
		
		return $synchronisation_data;
	}
	
	public static function insert_wp_page($page_name,$page_template=''){
		
		remove_action('wp_insert_post',  array('advanced_page_manager','wp_insert_post'));
		
		$post_type = 'page'; //TODO dynamise post_type
		
		//Use of "default_title" hook so that the default title may still be customized using "default_title" hook with a higher priority than 10.
		$callback = create_function('$title,$post', 'return $post->post_type == "'. $post_type .'" ? "'. $page_name .'" : $title;');
		add_filter('default_title', $callback, 10, 2);
		$post = get_default_post_to_edit($post_type);
		remove_filter('default_title', $callback);
		
		$new_page_id = wp_insert_post((array)$post);
		
		add_action('wp_insert_post',  array('advanced_page_manager','wp_insert_post'),10,2);
		
		if( !empty($page_template) ){
			ApmNodeDataDisplay::set_page_template($new_page_id,$page_template);
		}
		
		return $new_page_id;
	}
	
	/**
	 * Allows to set a WP page slug even if it is not published
	 * @param int $page_id
	 */
	public static function force_wp_page_slug_from_title($page_id){
		global $wpdb;
		
		$sql_page_id = addslashes($page_id);
		
		$sql = "SELECT post_title,post_parent FROM $wpdb->posts WHERE ID = '$sql_page_id' LIMIT 1";
		$page = $wpdb->get_row($sql); 
		if( !empty($page) && !empty($page->post_title) ){
			
			$page_slug = sanitize_title($page->post_title);
			
			//We cheat a little bit hear : force 'publish' to wp_unique_post_slug or it won't do anything!
			$page_slug = wp_unique_post_slug($page_slug, $page_id, 'publish', 'page', $page->post_parent);
			
			$sql = "UPDATE $wpdb->posts SET post_name = '". addslashes($page_slug) ."' WHERE ID = '$sql_page_id'";
			$results = $wpdb->get_results($sql);
			
		}
	}
	
	public function get_visible_nodes_number(){
		return count($this->apm_tree->get_visible_nodes(self::root_id,$this->tree_state->get_unfolded_nodes()));
	}
	
	/**
	 * To be called after sell::load_last_tree() or it has no real meaning...
	 */
	public function tree_is_empty(){
		return $this->apm_tree->is_empty(true);
	}

	public static function unmark_all_current_user_nodes(){
		$unmark_nodes = array();
		if( ApmAddons::addon_is_on('flagged_pages') ){
			$unmark_nodes = ApmMarkedNodes::unmark_all_user_nodes();
		}
		return $unmark_nodes;
	}
	
	/**
	 * Retrieves the total number of nodes. Warning : it includes the root node.
	 * Note : $this->apm_tree must be loaded before calling this.
	 * @param boolean $only_first_level Set to true to count only first level nodes (the ones just under the root).
	 */
	private function get_apm_tree_nb_nodes($only_first_level=false){
		$nb_nodes = 0;
		
		if( !empty($this->apm_tree) ){
			if( $only_first_level ){
				$nb_nodes = $this->apm_tree->count_node_direct_children(self::root_id);
			}else{
				$nb_nodes = $this->apm_tree->count_nodes();
			}
		}
		
		return $nb_nodes;
	}
	
}

class ApmListData{

	/**
	 * Instance of ApmNodeDataDisplayCollection
	 * @var ApmNodeDataDisplayCollection
	 */
	private $nodes_data;	
	
	public function __construct(){
		$this->nodes_data = new ApmNodeDataDisplayCollection();
	}
	
	/**
	 * Just a list of nodes, no tree!
	 * @param array $nodes_to_load
	 * @param boolean $load_data
	 * @param boolean $no_wp_data
	 */
	public function load_list($nodes_to_load,$no_wp_data=false){
		if( !is_array($nodes_to_load) ){
			$nodes_to_load = array($nodes_to_load);
		}
		
		if( !empty($nodes_to_load) ){
			$this->nodes_data->load_multiple($nodes_to_load,$no_wp_data,false,true);
		}
	}
	
	public function load_list_from_wp_pages($wp_pages){
		$this->nodes_data->load_multiple_from_wp_pages($wp_pages);
	}
	
	/**
	 * //TODO : Can be optimized!
	 * @param array $filters Possible values : 'search', 'recent', 'marked', 'node_state'
	 * @param array $orders Possible keys : 'title','marked','node_state','date','template'. Values : ASC or DESC.
	 * @param array $pagination Keys : 'current_page','nb_per_page'
	 */
	public function load_with_filters($filters,$orders=array(),$pagination=array(),$only_get_nodes=false){
		global $wpdb;
		
		//Add a sort by date to any order :
		if( !array_key_exists('date',$orders) ){
			$orders['date'] = 'DESC';
		}
		
		$result_infos = array('total_items'=>0,'current_page'=>0,'total_pages'=>0,'nb_per_page'=>0);
		
		$apm_ids = array();
		
		if( array_key_exists('search',$filters) ){
			$pages = self::search_pages($filters['search'],$orders);
			if( !empty($pages) ){
				//Note : this loads WP data :
				$this->load_list_from_wp_pages($pages);
				$apm_ids = $this->nodes_data->get_apm_ids();
			}else{
				if( $only_get_nodes ){
					$result_infos = array();
				}else{
					//No results for seach, no need to go further...
					$result_infos['total_items'] = 0;
					$result_infos['current_page'] = 0;
					$result_infos['nb_per_page'] = !empty($pagination['nb_per_page']) ? $pagination['nb_per_page'] : 10;
					$result_infos['total_pages'] = 0;
					$this->load_list(array()); 
				}
				
				return $result_infos;
			}
			unset($filters['search']);
			
		}elseif( array_key_exists('recent',$filters) ){
			
			if( !$only_get_nodes ){
			
				$recent_pages_data = self::get_recent_pages($orders,$pagination);
				$pages = $recent_pages_data['pages'];
				$pagination = $recent_pages_data['pagination'];
				
				if( !empty($pages) ){
					
						//Note : this loads WP data :
						$this->load_list_from_wp_pages($pages);
	
						$result_infos['total_items'] = $pagination['total_items'];
						$result_infos['current_page'] = $pagination['current_page'];
						$result_infos['nb_per_page'] = $pagination['nb_per_page'];
						$result_infos['total_pages'] = $pagination['total_pages'];
						
						return $result_infos;
				}
			}else{
				return self::get_all_pages_ids();
			}
			
			unset($filters['recent']);
		} 
		
		$marked_apm_ids = array();
		if( array_key_exists('marked',$filters) && ApmAddons::addon_is_on('flagged_pages') ){
			$marked = new ApmMarkedNodes();
			switch($filters['marked']){
				case 'yes':
					$marked_apm_ids = array_keys($marked->get_marked_nodes());
				break;
			}
			
			if( !empty($marked_apm_ids) ){
				
				if( !empty($apm_ids) ){
					foreach($apm_ids as $k=>$apm_id){
						if( !in_array($apm_id,$marked_apm_ids) ){
							unset($apm_ids[$k]);
						}
					}
				}else{
					$apm_ids = $marked_apm_ids;
				}
				
			}else{
				
				if( $only_get_nodes ){
					$result_infos = array();
				}else{
					//No results for marked pages, no need to go further...
					$result_infos['total_items'] = 0;
					$result_infos['current_page'] = 0;
					$result_infos['nb_per_page'] = !empty($pagination['nb_per_page']) ? $pagination['nb_per_page'] : 10;
					$result_infos['total_pages'] = 0;
					$this->load_list(array()); 
				}
				
				return $result_infos;
				
			}
			
		}
		
		if( array_key_exists('node_state',$filters) ){
			$apm_ids = self::get_nodes_with_status($filters['node_state'],$orders,$apm_ids);
		}
		
		$apm_ids = array_values($apm_ids);
		
		//Manage Order for filters and orders set to marked:
		if( array_key_exists('marked',$orders) ){
			$apm_ids = self::order_by_marked($apm_ids,$orders['marked'],$marked_apm_ids);
		}
		if( array_key_exists('marked',$filters) ){
			$apm_ids = self::order_apm_ids_from_wp_data($apm_ids,$orders);
		}
		
		$total_apm_ids = count($apm_ids);
		$result_infos['total_items'] = $total_apm_ids;
		
		//Pagination : TODO: handle pagination at the moment we retrieve the data!!
		if( !empty($pagination) ){ 
			$current_page = !empty($pagination['current_page']) ? $pagination['current_page'] : 1;
			$nb_per_page = !empty($pagination['nb_per_page']) ? $pagination['nb_per_page'] : 10;
			
			$total_pages = $nb_per_page > 0 ? ceil($total_apm_ids/$nb_per_page) : 0;
			
			if( $current_page > $total_pages ){
				$current_page = $total_pages;
			}
			
			if( !empty($apm_ids) ){
				$paginated_apm_ids = array();
				$start = ($current_page-1)*$nb_per_page;
				for($i=($current_page-1)*$nb_per_page; $i<$start+$nb_per_page && $i<$total_apm_ids; $i++ ){
					$paginated_apm_ids[] = $apm_ids[$i]; 
				}
				$apm_ids = $paginated_apm_ids;
			}
							
			$result_infos['current_page'] = $current_page;
			$result_infos['nb_per_page'] = $nb_per_page;
			$result_infos['total_pages'] = $total_pages;
		}
		
		if( !$only_get_nodes ){
			//Note : this loads WP data :
			$this->load_list($apm_ids);
			return $result_infos;
		}else{
			return $apm_ids;
		}
		
	}
	
	public function get_list_nodes_with_filters($filters){
		return $this->load_with_filters($filters,array(),array(),true);
	}
	
	private static function get_nodes_with_status($status,$orders=array(),$in_apm_ids = array(),$allow_autodrafts=false){
		global $wpdb;
			
		$apm_ids = array();
		
		$sql_post_status = '';
		if( $status == 'online' ){
			$sql_post_status = " AND p.post_status = 'publish' " ;
		}else if( $status == 'offline' ){
			$sql_post_status = " AND p.post_status != 'publish' " ;
		}else{
			return $apm_ids;
		}
		
		$orderby_data = self::get_sql_orderby_data($orders);
		$order_join = $orderby_data['join'];
		$order_by = $orderby_data['order_by'];
	
		//TODO : this "autodrafts" test can be removed since when we're here, 
		//the status can only be "online" or "offline".  
		$sql_autodrafts = " AND post_status != 'auto-draft' ";
		if( $allow_autodrafts ){
			$sql_autodrafts = '';
		}
		
		$sql = "SELECT ID FROM $wpdb->posts AS p
								  $order_join
								  WHERE post_type='page' $sql_autodrafts $sql_post_status
				";
		
		if( !empty($in_apm_ids) ){
			$sql .= " AND p.ID IN('". implode("','",$in_apm_ids) ."') ";
		}
		
		if( !empty($order_by) ){
			$sql .= $order_by;
		}
		
		$apm_ids = $wpdb->get_col($sql);
		
		return $apm_ids;
	}
	
	private static function count_nodes_with_status($status,$in_apm_ids = array(),$allow_autodrafts=false){
		global $wpdb;
			
		$apm_ids = array();
		
		$sql_post_status = '';
		if( $status == 'online' ){
			$sql_post_status = " AND p.post_status = 'publish' " ;
		}else if( $status == 'offline' ){
			$sql_post_status = " AND p.post_status != 'publish' " ;
		}else{
			return $apm_ids;
		}
		
		$sql_autodrafts = " AND post_status != 'auto-draft' ";
		if( $allow_autodrafts ){
			$sql_autodrafts = '';
		}
		
		$sql = "SELECT count(*) AS total FROM $wpdb->posts AS p
								  WHERE post_type='page' $sql_autodrafts $sql_post_status";
		
		if( !empty($in_apm_ids) ){
			$sql .= " AND p.ID IN('". implode("','",$in_apm_ids) ."') ";
		}
		
		$total = $wpdb->get_var($sql);
		
		return $total;
	}
	
	private static function count_nodes($allow_autodrafts=false){
		global $wpdb;
			
		$sql_autodrafts = " AND post_status != 'auto-draft' ";
		if( $allow_autodrafts ){
			$sql_autodrafts = '';
		}
		
		$sql = "SELECT count(*) AS total FROM $wpdb->posts WHERE post_type='page' $sql_autodrafts";
		
		$total = $wpdb->get_var($sql);
		
		return $total;
	}
	
	public function get_ready_to_display_list($no_wp_data=false,$no_tree_data=false,$json=false){
		
		if( !$no_wp_data ){
			$this->nodes_data->load_wp_data();
		}
		
		if( !$no_tree_data ){
			$this->nodes_data->set_nodes_positions_from_last_tree();
		}
		
		$ready_to_display_list = $this->nodes_data->get_array();
		
		return $json ? json_encode($ready_to_display_list) : $ready_to_display_list;
	}
	
	public function update_nodes_property($property,$value){
		$this->nodes_data->update_nodes_property($property,$value);
	}
	
	public function update_nodes_status($status){
		$this->nodes_data->update_nodes_status($status);
	}
	
	public static function search_pages($search,$orders=array(),$allow_autodrafts=false){
		global $wpdb;
		
		$pages_found = array();
		if( !empty($search) ){
			
			$orderby_data = self::get_sql_orderby_data($orders);
			$join = $orderby_data['join'];
			$order_by = $orderby_data['order_by'];
			
			$sql_autodrafts = " AND post_status != 'auto-draft' ";
			if( $allow_autodrafts ){
				$sql_autodrafts = '';
			}
		
			$sql = "SELECT p.* FROM $wpdb->posts AS p 
							   $join 
  						   	   WHERE ((p.post_title LIKE '%". addslashes($search) ."%') 
  						   		   	   OR (p.post_content LIKE '%". addslashes($search) ."%'))  
  								      AND p.post_type = 'page'
  								      $sql_autodrafts
  							   $order_by
  					";

			$pages_found = $wpdb->get_results($sql);
		}
		
		return $pages_found;
	}
	
	public static function get_recent_pages($orders,$pagination,$allow_autodrafts=false){
		global $wpdb;
		
		if( !array_key_exists('date',$orders) ){
			$orders['date'] = 'DESC';
		}
		
		$sql_autodrafts = " AND post_status != 'auto-draft' ";
		if( $allow_autodrafts ){
			$sql_autodrafts = '';
		}
		
		$total_items = $wpdb->get_var("SELECT count(*) FROM $wpdb->posts WHERE post_type = 'page' $sql_autodrafts");
		
		$current_page = !empty($pagination['current_page']) ? $pagination['current_page'] : 1;
		$nb_per_page = !empty($pagination['nb_per_page']) ? $pagination['nb_per_page'] : 10;
		
		$total_pages = $nb_per_page > 0 ? ceil($total_items/$nb_per_page) : 0;
			
		if( $current_page > $total_pages ){
			$current_page = $total_pages;
		}
		
		$offset = $nb_per_page * ($current_page-1);
		
		$orderby_data = self::get_sql_orderby_data($orders);
		$join = $orderby_data['join'];
		$order_by = $orderby_data['order_by'];
		
		
		//Retrieve Recent page, then cache retrieved data:
		//Cannot use get_pages() here because we want to filter/order by Templates,
		//marked page infos etc... but we're forgiven if we cache the data after, no?
		
		$sql = "SELECT * FROM $wpdb->posts AS p 
						 $join
						 WHERE post_type = 'page' 
						 $sql_autodrafts
						 $order_by 
						 LIMIT $offset, $nb_per_page
						 ";

		$pages = $wpdb->get_results($sql);
		
		//Inspired from WP get_pages() :
		//Sanitize before caching so it'll only get done once
		$num_pages = count($pages);
		for ($i = 0; $i < $num_pages; $i++) {
			$pages[$i] = sanitize_post($pages[$i], 'raw');
		}
	
		// Update cache.
		update_post_cache($pages);
		
		return array('pages'=>$pages,'pagination'=>compact('current_page','nb_per_page','total_items','total_pages'));
	}
	
	private static function get_all_pages_ids($allow_autodrafts=false){
		global $wpdb;
		
		$sql_autodrafts = " AND post_status != 'auto-draft' ";
		if( $allow_autodrafts ){
			$sql_autodrafts = '';
		}
		
		$sql = "SELECT ID FROM $wpdb->posts AS p WHERE post_type = 'page' $sql_autodrafts";
						 
		$pages_ids = $wpdb->get_col($sql);
		
		return $pages_ids;
	}
	
	/**
	 * Can be called directly (ApmListData::get_total(...)) OR by AJAX ($.apm_tree.get_list_total(...))
	 * @param string | array $types Can be 'marked', 'online', 'offline' or an array of such
	 */
	public static function get_total($types){
		
		if( !is_array($types) ){
			$types = array($types);
		}
		
		$total = 0;
		$apm_ids = array();
		
		if( in_array('all',$types) ){
			$total = self::count_nodes();
			return $total;
		}
		
		if( in_array('marked',$types) && ApmAddons::addon_is_on('flagged_pages') ){
			$marked = new ApmMarkedNodes();
			if( count($types) == 1 ){
				$total = count($marked->get_marked_nodes());
				return $total;
			}else{
				$marked_nodes = $marked->get_marked_nodes();
				if( !empty($marked_nodes) ){
					$apm_ids = array_keys($marked_nodes);
				}else{
					return 0;
				}
			}
		} 
		
		if( in_array('online',$types) ){
			$total = self::count_nodes_with_status('online',$apm_ids);
		}elseif( in_array('offline',$types) ){
			$total = self::count_nodes_with_status('offline',$apm_ids);
		}
		
		return $total;
	}
	
	private static function get_sql_orderby_data($orders){
		global $wpdb;
		
		$join = '';
		$order_by = '';
		
		if( !empty($orders) ){
			
			if( array_key_exists('template',$orders) ){
				$join = "LEFT JOIN $wpdb->postmeta AS pm ON pm.post_id = p.ID AND pm.meta_key = '_wp_page_template'";
			}
			
			$join = apply_filters('apm_custom_sql_join',$join,$orders);
			
			foreach($orders as $orderby => $order){
				//$orderby can be : 'title','marked','node_state','date','template'
				switch($orderby){
					case 'title':
						$order_by .= (!empty($order_by) ? ', ' : '') . "p.post_title $order";
						break;
					case 'node_state':
						$order = strtolower($order) == 'asc' ? 'DESC' : 'ASC'; //Switch order for node state 
						$order_by .= (!empty($order_by) ? ', ' : '') . "p.post_status $order";
						break;
					case 'date':
						$order_by .= (!empty($order_by) ? ', ' : '') . "p.post_date $order";
						break;
					case 'template':
						$order_by .= (!empty($order_by) ? ', ' : '') . "pm.meta_value $order";
						break;
				}
				
				$custom_order_by = apply_filters('apm_custom_sql_orderby','',$orderby,$order);
				if( !empty($custom_order_by) ){
					$order_by .= (!empty($order_by) ? ', ' : '') . $custom_order_by;
				}
				
			}
			
			if( !empty($order_by) ){
				$order_by = " ORDER BY $order_by";
			}
			
		}
		
		return array('join'=>$join, 'order_by'=>$order_by);
	}
	
	private static function order_by_marked($apm_ids,$order,$marked_apm_ids=array()){
		
		if( !ApmAddons::addon_is_on('flagged_pages') ){
			return $apm_ids;
		}
		
		if( empty($marked_apm_ids) ){
			$apm_marked = new ApmMarkedNodes();
			$marked_apm_ids = array_keys($apm_marked->get_marked_nodes());
		}
		
		$marked = array();
		$not_marked = array();
		
		foreach($apm_ids as $apm_id){
			if( in_array($apm_id,$marked_apm_ids) ){
				$marked[] = $apm_id;
			}else{
				$not_marked[] = $apm_id;
			}
		}
		
		$apm_ids = $order == 'ASC' ? array_merge($not_marked,$marked) : array_merge($marked,$not_marked);
		
		return $apm_ids;
	}
	
	private static function order_apm_ids_from_wp_data($apm_ids,$orders){
		global $wpdb;
		
		$orderby_data = self::get_sql_orderby_data($orders);
		$join = $orderby_data['join'];
		$order_by = $orderby_data['order_by'];
		
		$sql = "SELECT p.ID FROM $wpdb->posts AS p 
								  $join
								  WHERE p.ID IN('". implode("','",$apm_ids) ."')
								  $order_by
			   ";
		
		$apm_ids = $wpdb->get_col($sql);
		
		return $apm_ids;
	}
	
}

class ApmWpPageTreeData{
	
	/**
	 * Instance of ApmTree
	 * @var ApmTree
	 */
	private static $apm_tree = null;
	
	/**
	 * Instance of ApmNodeDataDisplay
	 * @var ApmNodeDataDisplay
	 */
	private $node_data = null;	
	
	public function __construct(ApmNodeDataDisplay $default_node_data=null){
		if( $default_node_data == null ){
			$this->node_data = new ApmNodeDataDisplay();
		}else{
			$this->node_data = clone $default_node_data;
		}
	}
	
	public function __get($property){
		$value = '';
		
		if( !empty($this->node_data) ){
			$value = $this->node_data->$property;
			if( $property == 'node_position' ){
				$value = (object)$value;
			}
		}
		
		return $value;
	}
	
	private function load_last_tree(){
		if( self::$apm_tree == null ){
			$tree = ApmTreeDb::get_last_tree();
			if( !empty($tree) ){
				self::$apm_tree = new ApmTree($tree);			
			}
		}
		return self::$apm_tree !== null;
	}
	
	public function load_wp_page($page_id,$no_marked_infos=false,$no_wp_data=false,$no_position_infos=false,$extended_position_infos=true){
		if( self::load_last_tree() ){
			$page = get_page($page_id);
			if( !empty($page) ){
				$apm_id = $this->node_data->load_from_wp_page($page,$no_marked_infos,$no_wp_data);
				if( !$no_position_infos ){
					$this->node_data->set_node_position(self::$apm_tree->get_node_tree_infos($apm_id,$extended_position_infos));
					$this->node_data->convert_positions_infos_to_wp_ids();
				}
				
			}
		}
	}

	public static function get_multiple_from_wp_pages($wp_pages,$no_marked_infos=false,$no_wp_data=false,$no_position_infos=false,$extended_position_infos=true){
		$nodes_data = array();
		if( self::load_last_tree() ){
			$nodes_data_raw = ApmNodeDataDisplay::get_multiple_from_wp_pages($wp_pages);
			foreach($nodes_data_raw as $wp_id => $node_data){
				if( !$no_position_infos ){
					$node_data->set_node_position(self::$apm_tree->get_node_tree_infos($node_data->apm_id,$extended_position_infos));
					$node_data->convert_positions_infos_to_wp_ids(); //TODO : this is not optimized...
				}
				$nodes_data[$wp_id] = new ApmWpPageTreeData($node_data);
			}
		}
		return $nodes_data;
	}
	
}
