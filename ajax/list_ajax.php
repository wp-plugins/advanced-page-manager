<?php
/**
 * ApmListAjax class
 * @package APM  
 */

require_once(dirname(__FILE__) ."/actions_ajax.php");

/**
 * Handles AJAX actions on nodes lists
 * Those actions correspond to actions defined on JS side in the $.apm_tree API (see js/tree.js)
 */
class ApmListAjax extends ApmActionsAjax{
	
	/**
	 * Loads a list according to the given filters, pagination, and orders data.
	 * At least one filter must be provided 
	 * @see ApmListData::load_with_filters() to see available filters
	 */
	public static function list_load(){
		parent::$json_data += array('list'=>'','total_displayed_nodes'=>'',
									'total_items'=>'','current_page'=>'','total_pages'=>'','nb_per_page'=>'');
		
		if( !empty($_POST['filters']) ){
			$list = new ApmListData();
			
			$pagination = array();
			if( !empty($_POST['pagination']) ){
				if( !empty($_POST['pagination']['nb_per_page']) ){
					$pagination['nb_per_page'] = $_POST['pagination']['nb_per_page'];
				}
				if( !empty($_POST['pagination']['nb_per_page']) ){
					$pagination['current_page'] = $_POST['pagination']['current_page'];
				}
			}
			
			$orders = !empty($_POST['orders']) ? $_POST['orders'] : array();

			//Load list elements (including nodes WP data) :
			$result_infos = $list->load_with_filters($_POST['filters'],$orders,$pagination);
			
			$list_nodes = $list->get_ready_to_display_list(true);
			//"true" because wp data are loaded in the previous call to $list->load_with_filters(...)
			
			parent::$json_data['list'] = self::get_html_tree($list_nodes);
			parent::$json_data['total_displayed_nodes'] = count($list_nodes);

			parent::$json_data['total_items'] = $result_infos['total_items'];
			parent::$json_data['current_page'] = $result_infos['current_page'];
			parent::$json_data['total_pages'] = $result_infos['total_pages'];
			parent::$json_data['nb_per_page'] = $result_infos['nb_per_page'];
			
		}else{
			parent::add_error('At least one filter must be provided!');
		}
			
		parent::send_json();
	}
	
	/**
	 * Retrieves nodes id of the list defined by the given filter.
	 * This allows to retrieve ALL (ie without pagination) nodes ids for a list.
	 * @see ApmListData::load_with_filters() to see available filters 
	 */
	public static function list_get_all_nodes(){
		parent::$json_data += array('list_nodes'=>0);
		
		if( !empty($_POST['filters']) ){
			$list = new ApmListData();
			$list_nodes = $list->get_list_nodes_with_filters($_POST['filters']);
			parent::$json_data['list_nodes'] = $list_nodes;
		}else{
			parent::add_error('At least one filter must be provided!');
		}
		
		parent::send_json();
	}
	
	/**
	 * Retrieves totals for 'online', 'offline' and 'marked' lists. 
	 * To make the union of 2 types use for example 'marked+online'. 
	 * $_POST['types'] must be an array : for example : ['marked','online','offline'] or ['marked+online','marked+offline'] 
	 */
	public static function list_get_total(){
		parent::$json_data += array('total_items'=>0,'types'=>array());
		
		if( !empty($_POST['types']) && is_array($_POST['types']) ){
			
			$total_items = array();
			foreach( $_POST['types'] as $type ){
				$total_items[$type] = ApmListData::get_total(explode('+',$type));
			}
			
			parent::$json_data['total_items'] = $total_items;
			parent::$json_data['types'] = $_POST['types'];
		}
		
		parent::send_json();
	}
}