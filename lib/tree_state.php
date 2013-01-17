<?php
/**
 * Handles the tree fold/unfold logic, on a per user basis.
 */
class ApmTreeState{
	
	private $tree_state = array();
	
	public function __construct($tree_state=array()){
		if( !empty($tree_state) && is_array($tree_state) ){
			$this->tree_state = $tree_state;
		}
	}
	
	public function load($tree_nodes=array()){
		global $current_user;
		$user_tree_state = get_user_meta($current_user->ID,
										 ApmConfig::tree_state_user_meta,
										 true);
										 
		if( !empty($user_tree_state) ){
			if( !empty($tree_nodes) ){
				//Check if new pages have been created by other users.
				//TODO : see performance impact of this array_diff at every tree state load.
				$new_nodes = array_diff($tree_nodes,array_keys($user_tree_state)); 
				if( !empty($new_nodes) ){
					//Add new nodes (that have been created since tree state has been saved for the last time) :
					$this->tree_state = $user_tree_state;
					foreach($new_nodes as $new_node){
						$this->tree_state[$new_node] = 0; //New nodes folded by default
					}
					$this->save();
				}else{
					$this->tree_state = $user_tree_state;
				}
			}else{
				$this->tree_state = $user_tree_state;
			}
		}
	}
	
	public function save(){
		global $current_user;
		update_user_meta($current_user->ID,
						 ApmConfig::tree_state_user_meta,
						 $this->tree_state);
	}	
	
	public static function delete_all(){
		delete_metadata('user',0,ApmConfig::tree_state_user_meta,'',true);
	}
	
	public static function delete_for_current_user(){
		global $current_user;
		delete_metadata('user',$current_user->ID,ApmConfig::tree_state_user_meta,'',false);
	}
	
	public static function current_user_has_tree_state(){
		global $current_user;
		$user_tree_state = get_user_meta($current_user->ID,
										 ApmConfig::tree_state_user_meta,
										 true);
		return !empty($user_tree_state);
	}
	
	public function is_empty(){
		return empty($this->tree_state);
	}
	
	public function get_tree_state(){
		return $this->tree_state;
	}
	
	public function load_nodes($nodes,$node_states=array(),$append=false){
		if( !empty($nodes) ){
			if( !$append ){
				$this->tree_state = array();
			}
			foreach($nodes as $node){
				if( is_array($node_states) && array_key_exists($node,$node_states) ){
					$this->tree_state[$node] = $node_states[$node];
				}else{
					if( $node == ApmTreeData::root_id ){
						$this->tree_state[$node] = 1; //Root unfolded by default
					}else{
						$this->tree_state[$node] = is_int($node_states) ? $node_states : 0; //Folded by default
					}
				}
			}
		}
	}
	
	public function add_node($node,$node_state=null){
		if( !in_array($node,$this->tree_state) ){
			$this->tree_state[$node] = !empty($node_state) ? $node_state : 1; //unfolded by default
		}
	}
	
	public function delete_nodes($nodes){
		foreach($nodes as $node){
			unset($this->tree_state[$node]);
		}
	}
	
	public function delete_node($node){
		$this->delete_nodes(array($node));
	}
	
	public function get_unfolded_nodes($only_nodes=array()){
		$unfolded_nodes = array();
		
		foreach($this->tree_state as $node=>$state){
			if( !empty($only_nodes) ){
				if( in_array($node,$only_nodes) ){
					if($state == 1){
						$unfolded_nodes[] = $node;
					}
				}
			}else{
				if($state == 1){
					$unfolded_nodes[] = $node;
				}
			}
		}
		return $unfolded_nodes;
	}
	
	public function fold_node($node){
		if( array_key_exists($node,$this->tree_state) ){
			$this->tree_state[$node] = 0;
		}
	}
	
	public function fold_nodes($nodes){
		foreach($nodes as $node){
			$this->fold_node($node);
		}
	}
	
	public function fold_all(){
		foreach($this->tree_state as $node=>$state){
			if( $node == ApmTreeData::root_id ){ //We don't fold the root node
				$this->tree_state[$node] = 1;
			}else{
				$this->tree_state[$node] = 0;
			}
		}
	}
	
	public function unfold_node($node){
		if( array_key_exists($node,$this->tree_state) ){
			$this->tree_state[$node] = 1;
		}
	}
	
	public function unfold_nodes($nodes){
		foreach($nodes as $node){
			$this->unfold_node($node);
		}
	}
	
	public function unfold_all(){
		foreach($this->tree_state as $node=>$state){
			$this->tree_state[$node] = 1;
		}
	}
	
	public function node_is_folded($node){
		return array_key_exists($node,$this->tree_state) && $this->tree_state[$node] == 0;
	}
	
}