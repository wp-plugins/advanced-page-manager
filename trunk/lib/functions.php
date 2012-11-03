<?php

require_once('apm_page.php');
require_once('bo_context.php');

/**
 * Defines functions that can be called from outside (anywhere in themes and other plugins)
 * to retrieve Advances Pages Manager data.
 */
class Apm{
	
	/**
	 * Same as WP get_page but adding APM page infos to the result.
	 * TODO : our function must propose and handle the same args as WP get_page()
	 * @param int $page_id
	 */
	public static function get_page($page_id=0){
		$page = null;
		
		if( empty($page_id) ){
			global $post;
			if( $post->post_type == 'page' ){
				$page_id = $post->ID;
			}
		}
		
		if( !empty($page_id) ){
			$wp_page = get_page($page_id);
			if( !empty($wp_page) && $wp_page->post_type == 'page' ){
				$page = self::add_apm_infos_to_wp_page($wp_page);
			}
		}
		
		return $page;
	}
	
	/**
	 * Same as WP get_pages but adding APM pages infos to the resulting pages.
	 * @param int $page_id
	 */
	public static function get_pages($args = ''){
		$pages = get_pages($args);
		
		if( !empty($pages) ){
			$pages = self::add_apm_infos_to_wp_pages($pages);
		}
		
		return $pages;
	}
	
	public static function get_page_flag($page_id=0){
		return ApmPage::get_page_tree_info('marked',$page_id);
	}
	
	public static function get_page_tree_positions($page_id=0){
		return ApmPage::get_page_tree_info('node_position',$page_id);
	}
	
	public static function get_page_apm_id($page_id=0){
		return ApmPage::get_page_tree_info('apm_id',$page_id);
	}
	
	public static function get_page_is_in_apm_tree($page_id=0){
		$page_tree_positions = self::get_page_tree_positions($page_id);
		return $page_tree_positions !== null && $page_tree_positions->depth !== false;
	}
	
	public static function get_page_show_in_tree_link($page_id=0){
		
		$show_in_tree_link = '';
		
		if( empty($page_id) ){
			global $post;
			if( !empty($post->post_type) && $post->post_type == 'page' ){
				$page_id = $post->ID;
			}
		}

		if( !empty($page_id) ){
			if ( !current_user_can( 'edit_pages', $page_id ) )
				return '';
			
			$page_apm_id = self::get_page_apm_id($page_id);
			if( !empty($page_apm_id) ){
				$show_in_tree_link = ApmBoContext::get_reach_node_url($page_apm_id);
			}
		}
			
		return $show_in_tree_link;
	}
	
	public static function find_published_page_id_in_list($pages_id_list,$first=true){
		global $wpdb;
		
		$published_page_id = 0;
		
		if( !$first ){
			$pages_id_list = array_reverse($pages_id_list);
		}
		
		$ids_sql = "'". implode("','",$pages_id_list) ."'";
		
		$sql = "SELECT ID FROM $wpdb->posts 
					   WHERE ID IN($ids_sql) AND post_status = 'publish'
					   ORDER BY FIELD(ID,$ids_sql)
					   LIMIT 1
			   ";
		
		$published_page_id = $wpdb->get_var($sql);
		
		return $published_page_id;
	}
	
	public static function add_apm_infos_to_wp_page($wp_page){
		
		$page_with_apm_infos = null; 
		
		if( is_numeric($wp_page) ){
			$wp_page = get_page($wp_page);
		}
		
		if( is_object($wp_page) && $wp_page->post_type == 'page' ){
			$page_tree_infos = ApmPage::get_page_tree_infos_for_wp_page($wp_page->ID);
			
			$page_with_apm_infos = (array)$wp_page;
			foreach($page_tree_infos as $info => $value){
				$page_with_apm_infos[$info] = $value;
			}
			$page_with_apm_infos = (object)$page_with_apm_infos;
		}
		
		return $page_with_apm_infos;
	}
	
	public static function add_apm_infos_to_wp_pages($wp_pages){
		ApmPage::load_page_tree_infos_for_wp_pages($wp_pages);
		foreach($wp_pages as $k=>$wp_page){
			//We loaded all pages tree infos at once with "get_page_tree_infos_for_wp_pages()",
			//No other queries will be done in "add_apm_infos_to_wp_page()" :
			$wp_pages[$k] = self::add_apm_infos_to_wp_page($wp_page);
		}
		return $wp_pages;
	}
	
}
