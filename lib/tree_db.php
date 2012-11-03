<?php

class ApmTreeDb{
	
	private static function get_tree_from_options($post_type='page'){
		$apm_tree = array();
		
		$apm_trees = get_option(ApmConfig::tree_option_db_id);
		
		if( $apm_trees !== false && is_array($apm_trees) && array_key_exists($post_type,$apm_trees) ){
			if( !empty($apm_trees[$post_type]) ){
				$apm_tree = $apm_trees[$post_type];
			}
		}
		
		return $apm_tree;
	}
	
	private static function save_tree_to_options($tree,$post_type='page'){
	
		$apm_trees = get_option(ApmConfig::tree_option_db_id);
		
		if( $apm_trees !== false && is_array($apm_trees) ){
			$apm_trees[$post_type] = $tree;
		    update_option(ApmConfig::tree_option_db_id, $apm_trees);
		}else{
			$apm_trees = array($post_type=>$tree);
			add_option(ApmConfig::tree_option_db_id, $apm_trees, '', 'no');
		}
		
	}
	
	public static function get_last_tree($post_type='page'){		
		return self::get_tree_from_options($post_type);
	}
	
	public static function save_tree($tree,$post_type='page'){
		if( !empty($tree) ){ //To empty the tree, use reset_tree()
			self::save_tree_to_options($tree,$post_type);
		}
	}
	
	public static function reset_tree($post_type='page'){
		self::save_tree_to_options(array(),$post_type);
	}
	
	public static function delete_database_data(){
		delete_option(ApmConfig::tree_option_db_id);
	}
	
}