<?php
/**
 * Handles retrieval of APM specific pages infos : position in tree, apm_id, etc... 
 * Manages a static list of already loaded pages, so that we don't load the same info twice.
 */
class ApmPage{

	/**
	 * Cache pages infos to avoid load them twice.
	 * @var Array of ApmWpPageTreeData
	 */
	private static $pages_tree_infos = array();
	
	public static function get_page_tree_infos($page_id=0){
		
		$page_tree_infos = null;
		
		if( empty($page_id) ){
			global $post;
			if( $post->post_type == 'page' ){
				$page_id = $post->ID;
			}
		}
			
		if( !empty($page_id) ){
			if( !array_key_exists($page_id,self::$pages_tree_infos) ){
				$page_tree_infos = new ApmWpPageTreeData();
				$page_tree_infos->load_wp_page($page_id,false,true); //Load data for only one node
				self::$pages_tree_infos[$page_id] = $page_tree_infos;
			}else{
				$page_tree_infos = self::$pages_tree_infos[$page_id];
			}
		}
			
		return $page_tree_infos;
	}
	
	public static function get_page_tree_info($info,$page_id=0){
		$page_tree_info = null;

		$page_tree_infos = self::get_page_tree_infos($page_id);
		
		if( $page_tree_infos !== null && $page_tree_infos->$info !== null ){
				$page_tree_info = $page_tree_infos->$info;
		}
		
		return $page_tree_info;
	}
	
	public static function get_page_tree_infos_for_wp_page($page_id=0){
		$page_tree_infos_for_wp_page = array();
		
		$page_tree_infos = self::get_page_tree_infos($page_id);
		if( !empty($page_tree_infos) ){
			$page_tree_infos_for_wp_page = array('node_position'=>$page_tree_infos->node_position,
												 'apm_id'=>$page_tree_infos->apm_id,
												 'marked'=>$page_tree_infos->marked
												 );
		}
		
		return $page_tree_infos_for_wp_page;
	}
	
	public static function load_page_tree_infos_for_wp_pages($wp_pages){
		
		//Don't reload data we already have :
		foreach($wp_pages as $k=>$wp_page){
			if( array_key_exists($wp_page->ID,self::$pages_tree_infos) || $wp_page->post_type != 'page' ){
				unset($wp_pages[$k]);
			}	
		}
		
		$pages_tree_infos = ApmWpPageTreeData::get_multiple_from_wp_pages($wp_pages);
		foreach($pages_tree_infos as $wp_id => $page_tree_infos){
			self::$pages_tree_infos[$wp_id] = $page_tree_infos;
		}
	}
}