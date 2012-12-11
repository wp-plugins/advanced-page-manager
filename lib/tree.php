<?php

class ApmTree{
	
	const root_id = 0;
	
	private $tree;
	
	public function __construct($tree=array()){
		if( !is_array($tree) && is_numeric($tree) ){
			$tree = array($tree=>array());
		}
		$this->tree = $tree;
	}
	
	private function add_before_or_after($index_node, ApmTree $to_add, $after=true){
		$index_node_found = false;

		$index_node_father = $this->get_node_father($index_node);
		if( $index_node_father !== false ){
			array_splice($this->tree[$index_node_father],array_search($index_node,$this->tree[$index_node_father])+($after ? 1 : 0),0,$to_add->get_root());
			$index_node_found = true;
		}
		
		if( $index_node_found && !$to_add->is_simple_node() ){
			$this->tree = $this->tree + $to_add->get_tree();
		}
	}
	
	public function add_before($before_node, ApmTree $to_add){
		$this->add_before_or_after($before_node,$to_add,false);
	}
	
	public function add_after($after_node, ApmTree $to_add){
		$this->add_before_or_after($after_node,$to_add);
	}
	
	public function add_child($father_node, ApmTree $to_add, $as_first_child=false){
		if( $father_node === false || $father_node === '' || $father_node === null ){ //$father_node can be 0!
			$father_node = $this->get_root();
		}
		
		if( array_key_exists($father_node,$this->tree) ){
			if( $as_first_child ){
				array_unshift($this->tree[$father_node],$to_add->get_root());
			}else{
				$this->tree[$father_node][] = $to_add->get_root();
			}
		}else{
			$this->tree = $this->tree + array($father_node=>array($to_add->get_root()));
		}
		
		if( !$to_add->is_simple_node() ){
			$this->tree = $this->tree + $to_add->get_tree();
		}
	}
	
	public function move_node_before($node_to_move,$before_node){
		if( empty($node_to_move) || empty($before_node) ){
			return false;
		}
		
		$forbidden_targets_nodes = $this->get_forbidden_target_nodes($node_to_move,'insert_before');
		if( !in_array($before_node,$forbidden_targets_nodes) ){
			$apm_tree_to_move = $this->get_sub_tree($node_to_move);
			$this->delete_sub_tree($node_to_move);
			$this->add_before($before_node,$apm_tree_to_move);
		}else{
			return false;
		}
		
		return true;
	}
	
	public function move_node_after($node_to_move,$after_node){
		if( empty($node_to_move) || empty($after_node) ){
			return false;
		}
		
		$forbidden_targets_nodes = $this->get_forbidden_target_nodes($node_to_move,'insert_after');
		if( !in_array($after_node,$forbidden_targets_nodes) ){
			$apm_tree_to_move = $this->get_sub_tree($node_to_move);
			$this->delete_sub_tree($node_to_move);
			$this->add_after($after_node,$apm_tree_to_move);
		}else{
			return false;
		}
		
		return true;
	}
	
	public function move_node_as_child($node_to_move,$father_node){
		if( empty($node_to_move) || $father_node === false || $father_node === '' || $father_node === null ){ //$father_node can be 0!
			return false;
		}
		
		$forbidden_targets_nodes = $this->get_forbidden_target_nodes($node_to_move,'insert_child');
		if( !in_array($father_node,$forbidden_targets_nodes) ){
			$apm_tree_to_move = $this->get_sub_tree($node_to_move);
			$this->delete_sub_tree($node_to_move);
			$this->add_child($father_node,$apm_tree_to_move);
		}else{
			return false;
		}
		
		return true;
	}
	
	public function get_root(){
		reset($this->tree);
		return $this->is_empty() ? null : key($this->tree);
	}
	
	private function get_sub_tree_raw($sub_tree_root=''){
		if( empty($sub_tree_root) ){
			$sub_tree_root = $this->get_root();
		}
		$sub_tree = array();
		if( array_key_exists($sub_tree_root,$this->tree) ){
			$sub_tree[$sub_tree_root] = array();
			foreach($this->tree[$sub_tree_root] as $child){
				$sub_tree[$sub_tree_root][] = $child;
				if( array_key_exists($child,$this->tree) ){
					$sub_tree = $sub_tree + $this->get_sub_tree_raw($child);
				}
			}
		}else{
			$sub_tree = array($sub_tree_root=>array());
		}
		return $sub_tree;
	}
	
	public function get_sub_tree($sub_tree_root=''){
		return new ApmTree($this->get_sub_tree_raw($sub_tree_root));
	}
	
	public function get_node_order($node){
		$node_order = 0;
		
		$siblings = $this->get_siblings($node);
		if( !empty($siblings) && count($siblings) > 1 ){
			$node_order = array_search($node,$siblings);
		}
		
		return $node_order;
	}
	
	public function get_siblings($node,$node_included=true){
		$siblings = array();
		foreach($this->tree as $father=>$children){
			$k = array_search($node,$children);
			if( $k !== false ){
				if( !$node_included ){
					unset($children[$k]);
				}
				$siblings = $children;
				break;
			}
		}
		
		$siblings = array_values($siblings); //Reindex
		
		return $siblings;
	}
	
	public function get_siblings_after($node,$node_included=true){
		$siblings_after = array();
		
		if( $node_included ){
			$siblings_after[] = $node;
		}
		
		$siblings = $this->get_siblings($node);
		if( !empty($siblings) && count($siblings) > 1 ){
			$node_position = array_search($node,$siblings);
			foreach($siblings as $k=>$sibling){
				if( $k > $node_position ){
					$siblings_after[] = $sibling;
				}
			}
		}
		
		return $siblings_after;
	}
	
	public function get_siblings_before($node,$node_included=true){
		$siblings_before = array();
		
		$siblings = $this->get_siblings($node);
		if( !empty($siblings) && count($siblings) > 1 ){
			$node_position = array_search($node,$siblings);
			foreach($siblings as $k=>$sibling){
				if( $k < $node_position ){
					$siblings_before[] = $sibling;
				}
			}
		}
		
		if( $node_included ){
			$siblings_before[] = $node;
		}
		
		return $siblings_before;
	}
	
	public function are_siblings($node1,$node2){
		$are_siblings = false;
		
		$node1_siblings = $this->get_siblings($node1,false);
		if( !empty($node1_siblings) && in_array($node2,$node1_siblings) ){
			$are_siblings = true;
		}
		
		return $are_siblings;
	}
	
	/*
	 * Returns + 1 if $node1 is before $node2, -1 if $node2 is before $node1,
	 * and false if $node1 and $node2 are not siblings.
	 */
	public function compare_siblings_order($node1,$node2){
		$siblings_orger = false;
		
		$siblings = $this->get_siblings($node1);
		if( !empty($siblings) && in_array($node2,$siblings) ){
			//They are siblings, check relative order :
			$node1_position = array_search($node1,$siblings);
			$node2_position = array_search($node2,$siblings);
			$siblings_order = $node1_position < $node2_position ? 1 : -1;
		}
		
		return $siblings_order;
	}
	
	public function get_siblings_interval($node_left,$node_right,$node_left_included=true,$node_right_included=true){
		$interval = array();
		
		if( $node_left == $node_right ) {
			return array();
		}
    
		$siblings = $this->get_siblings($node_left);
		if( !empty($siblings) && in_array($node_right,$siblings) ){
			
			$node_left_position = array_search($node_left,$siblings);
			$node_right_position = array_search($node_right,$siblings);
			
			if( $node_left_position > $node_right_position ){
				$memory_left_position = $node_left_position;
				$node_left_position = $node_right_position;
				$node_right_position = $memory_left_position;
				
				$memory_left_included = $node_left_included;
				$node_left_included = $node_right_included;
				$node_right_included = $memory_left_included;
			}
			
			$offset = $node_left_included ? $node_left_position : $node_left_position + 1;
			$length = $node_right_position - $node_left_position + 1;
			if( !$node_left_included ){
				$length--;
			}
			if( !$node_right_included ){
				$length--;
			}
			
			$interval = array_slice($siblings, $offset, $length);
			
		}else{
			return false;
		}
		
		return $interval;
	}
	
	public function get_children($node){
		$children = array();
		if( array_key_exists($node,$this->tree) ){
			$children = $this->tree[$node];
		}
		return $children;
	}
	
	public function get_nb_children($node){
		return array_key_exists($node,$this->tree) ? count($this->tree[$node]) : 0;
	}
	
	public function get_node_father($node){
		if( $node == $this->get_root() ){
			return false;
		}
		
		foreach($this->tree as $father=>$children){
			if( in_array($node,$children) ){
				return $father; 
			}
		}
		
		return false;
	}
	
	public function get_path_to_node($node_to_find){
		$path = array();
		
		if( $this->node_exists($node_to_find) ){
			while( $node_to_find !== false ){
				array_unshift($path,(int)$node_to_find);
				$node_to_find = $this->get_node_father($node_to_find);
			}
		}
		
		return $path;
	}
	
	public function node_exists($node){
		foreach($this->tree as $father=>$children){
			if( $node == $father || in_array($node,$children) ){
				return true; 
			}
		}
		return false;
	}
	
	public function get_nodes_flat($sub_tree_root=''){
		$tree_nodes = array();
		$apm_tree = $this->get_sub_tree($sub_tree_root);
		foreach($apm_tree->get_tree() as $root=>$children){
			if( !in_array($root,$tree_nodes) ){
				$tree_nodes[] = $root;
			}
			foreach($children as $child){
				if( !in_array($child,$tree_nodes) ){
					$tree_nodes[] = $child;
				}
			}
		}
		return $tree_nodes;
	}
	
	public function count_nodes(){
		return count($this->get_nodes_flat());
	}
	
	public function count_node_direct_children($node){
		$nb_direct_children = 0;
		if( array_key_exists($node,$this->tree) ){
			$nb_direct_children = count($this->tree[$node]);
		}
		return $nb_direct_children;
	}
	
	private function delete_node_descendance($sub_tree_root){
		if( array_key_exists($sub_tree_root,$this->tree) ){
			foreach($this->tree[$sub_tree_root] as $child){
				if( array_key_exists($child,$this->tree) ){
					$this->delete_node_descendance($child);
				}
			}
			unset($this->tree[$sub_tree_root]);
		}
	}
	
	public function delete_sub_tree($sub_tree_root,$delete_root=true){
		$this->delete_node_descendance($sub_tree_root);
		if( $delete_root ){
			$sub_tree_root_father = $this->get_node_father($sub_tree_root);
			if( $sub_tree_root_father !== false ){
				unset($this->tree[$sub_tree_root_father][array_search($sub_tree_root,$this->tree[$sub_tree_root_father])]);
				$this->tree[$sub_tree_root_father] = array_values($this->tree[$sub_tree_root_father]); //To reindex the array after unset!
				if( $sub_tree_root_father != self::root_id && empty($this->tree[$sub_tree_root_father]) ){
					unset($this->tree[$sub_tree_root_father]);
        		}
			}
		}
	}
	
	public function is_empty($considered_empty_if_only_root = false){
		$empty = empty($this->tree);
		
		if( $considered_empty_if_only_root ){
			$empty |= count($this->tree) == 1 && array_key_exists(self::root_id,$this->tree) && empty($this->tree[self::root_id]);
		}
		
		return $empty;
	}
	
	private function is_simple_node(){
		$count = count($this->tree);
		if( $count != 1 ){
			return false;
		}
		return empty($this->tree[$this->get_root()]);
	}
	
	public function get_max_node(){
		$max = 0;
		foreach($this->tree as $father=>$children){
			$max = $father > $max ? $father : $max;
			foreach($children as $child){
				$max = $child > $max ? $child : $max;
			}
		}
		return $max;
	}
	
	public function get_forbidden_target_nodes($moving_node,$action){
		$forbidden_nodes = $this->get_nodes_flat($moving_node);
		switch( $action ){
			case 'insert_before':
			case 'insert_after':
				$root = $this->get_root();
				if( !in_array($root,$forbidden_nodes) ){
					$forbidden_nodes[] = $root;
				}
				break;
			case 'insert_child':
				break;
		}
		return $forbidden_nodes;
	}
	
	public function get_allowed_target_nodes($moving_node,$action){
		$forbidden = $this->get_forbidden_target_nodes($moving_node,$action);
		$all = $this->get_nodes_flat($this->get_root());
		$allowed = array();
		foreach($all as $node){
			if( !in_array($node,$forbidden) ){
				$allowed[] = $node;
			}
		}
		return $allowed;
	}
	
	public function get_tree(){
		return $this->tree;
	}
	
	public function get_node_depth($node,$relative_to_node=self::root_id){
		if( $node != $relative_to_node ){
			$node_father = $this->get_node_father($node);
			if( $node_father !== false ){
				return $this->get_node_depth($node_father,$relative_to_node) + 1;
			}else{
				//We reached the top of the tree without finding our "$relative_to_node"...
				//So, $node is not a descendant of $relative_to_node, we can't compute its depth relatively to it!
				return false;  
			}
		}else{
			return 0;
		}
	}
	
	public function get_ready_to_display_tree($root=false,$depth=0,$parent=false,$unfolded_nodes=array()){
		$to_display = array();
		
		if( $root === false ){
			$root = $this->get_root();
		}
		
		$nb_children = array_key_exists($root,$this->tree) ? count($this->tree[$root]) : 0;
		
		$to_display[$root] = array('depth'=>$depth,
								   'parent'=>( $parent===false ? $this->get_node_father($root) : $parent ),
								   'nb_children'=>$nb_children,
								   'children'=>!empty($nb_children) ? $this->tree[$root] : array()
								   );

		if( !empty($unfolded_nodes) && in_array($root,$unfolded_nodes) ){
			if( array_key_exists($root,$this->tree) ){								   
				foreach($this->tree[$root] as $child){
					if( array_key_exists($child,$this->tree) ){ 
						if( !empty($unfolded_nodes) && in_array($child,$unfolded_nodes) ){	
							$to_display = $to_display + $this->get_ready_to_display_tree($child,$depth+1,false,$unfolded_nodes);
						}else{
							$to_display[$child] = array('depth'=>$depth+1,
														'parent'=>$root,
														'nb_children'=>count($this->tree[$child]),
														'children'=>$this->tree[$child]
													);
						}
					}else{
						$to_display[$child] = array('depth'=>$depth+1,
													'parent'=>$root,
													'nb_children'=>0,
													'children'=>array()
													);
					}
				}
			}
		}
		
		return $to_display;
	}
	
	/**
	 * Same as get_ready_to_display_tree() but returns only nodes ids
	 */
	public function get_visible_nodes($root=false,$unfolded_nodes=array()){
		$to_display = array();
		
		if($root === false){
			$root = $this->get_root();
		}
		
		$nb_children = array_key_exists($root,$this->tree) ? count($this->tree[$root]) : 0;
		
		$to_display[] = $root;

		if( !empty($unfolded_nodes) && in_array($root,$unfolded_nodes) ){
			if( array_key_exists($root,$this->tree) ){								   
				foreach($this->tree[$root] as $child){
					if( array_key_exists($child,$this->tree) ){ 
						if( !empty($unfolded_nodes) && in_array($child,$unfolded_nodes) ){	
							$to_display = array_merge($to_display,$this->get_visible_nodes($child,$unfolded_nodes));
						}else{
							$to_display[] = $child;
						}
					}else{
						$to_display[] = $child;
					}
				}
			}
		}
		
		return $to_display;
	}
	
	public function get_node_tree_infos($node,$extended_infos=false){
		
		$children = $this->get_children($node);
		
		$ready_to_display_node = array('depth'=>$this->get_node_depth($node),
									   'parent'=>$this->get_node_father($node),
									   'nb_children'=>count($children),
									   'children'=>$children
									   );
									   
		if( $extended_infos ){
			$siblings_no_current = array(); 
			$siblings_before = array(); 
			$siblings_after = array(); 
			$previous_sibling = false; 
			$next_sibling = false;
			
			$order = 0;
			
			$siblings = $this->get_siblings($node);
			if( !empty($siblings) && count($siblings) > 1 ){
				$node_position = array_search($node,$siblings);
				$order = $node_position;
				foreach($siblings as $k=>$sibling){
					if( $sibling != $node ){
						$siblings_no_current[] = $sibling;
						if( $k < $node_position ){
							$siblings_before[] = $sibling;
						}else{
							$siblings_after[] = $sibling;
						}
					}
				}
				$previous_sibling = isset($siblings[$node_position-1]) ? $siblings[$node_position-1] : false; 
				$next_sibling = isset($siblings[$node_position+1]) ? $siblings[$node_position+1] : false; 
			}
			
			$ready_to_display_node['siblings_no_current'] = $siblings_no_current;
			$ready_to_display_node['all_siblings'] = $siblings;
			$ready_to_display_node['siblings_before'] = $siblings_before;
			$ready_to_display_node['siblings_after'] = $siblings_after;
			$ready_to_display_node['previous_sibling'] = $previous_sibling;
			$ready_to_display_node['next_sibling'] = $next_sibling;
			
			$ready_to_display_node['order'] = $order;
		}
			   
		return $ready_to_display_node;
	}
	
	public function get_nodes_tree_infos($nodes,$extended_infos=false){
		$ready_to_display_nodes = array();
		
		foreach($nodes as $node){
			$ready_to_display_nodes[$node] = $this->get_node_tree_infos($node,$extended_infos);
		}
		
		return $ready_to_display_nodes;
	}
	
	public function __toString(){
		$to_display = $this->get_ready_to_display_tree();
		$str = '';
		foreach($to_display as $node=>$node_tree_infos){
			$str .= '<div style="padding-left:'. $node_tree_infos['depth']*20 .'px">'. $node .'</div>';
		}
		return $str;
	}
	
	
}

?>