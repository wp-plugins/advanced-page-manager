<?php
class ApmBoContext{
	
	private static $current_page = '';
	
	public static function get_current_page(){
		return self::$current_page;
	}
	
	public static function set_current_page_infos($menu_page_slug){
		
		if( $menu_page_slug == 'apm_browse_pages_menu' ){
			self::$current_page = 'browse';
		}
		
		self::$current_page = apply_filters('apm_bo_context_current_page',self::$current_page,$menu_page_slug);
	}
	
	public static function get_browse_url(){
		return get_option('siteurl') . ApmConstants::browse_pages_url;
	}
	
	public static function get_reach_node_url($node_apm_id){
		return self::get_browse_url() . '&go_to_node='. $node_apm_id;
	}
	
	public static function get_requested_go_to_node($default=0){
		return !empty($_GET['go_to_node']) && is_numeric($_GET['go_to_node']) ? $_GET['go_to_node'] : $default;
	}
}