<?php
/**
 * Encapsulates AJAX answers and common treatments for all ajax actions on trees, lists, nodes and api.
 * This is the mother class of ApmTreeAjax, ApmListAjax, ApmNodesAjax and ApmApiAjax classes.
 * @package APM
 */
class ApmActionsAjax{
	
	/**
	 * Answer that will be converted to json in self::send_json()
	 * An "echoed_before_json" key may be added if some content was echoed before
	 * the json answer (see self::send_json()).
	 * @var array
	 */
	protected static $json_data = array('ok'=>1,'error'=>'');
	
	/**
	 * Errors that will be merged to the json answer in self::send_json()
	 * @var array
	 */
	private static $errors = array();
	
	/**
	 * Builds HTML from tree or list $nodes
	 * @param array $nodes Must be the result of a "get_ready_to_display_[tree|list]" method.
	 * @param boolean $return_array Whether to return an array or a string of html nodes.
	 */
	protected static function get_html_tree($nodes,$return_array=false){
		$html = $return_array ? array() : '';
		
		if( !is_array($nodes) ){
			$nodes = array($nodes);
		}
		
		$template_name = !empty($_POST['tree_template']) ? $_POST['tree_template'] : 'tree_display';
		$tree_template_file = dirname(__FILE__) .'/../templates/'. ApmOptions::get_option('panel_page_template_name') .'/'. $template_name .'.php';
		if( file_exists($tree_template_file) ){
			require_once(dirname(__FILE__).'/../lib/custom_columns.php');
			if( $return_array ){
				$nodes_list = $nodes;
				foreach($nodes_list as $node){
					$nodes = array($node);
					ob_start();
					require($tree_template_file);
					$node_html = ob_get_contents();
					ob_end_clean();
					$html[$node->apm_id] = trim($node_html);
				}
			}else{
				ob_start();
				require($tree_template_file);
				$html = trim(ob_get_contents());
				ob_end_clean();
			}	
		}else{
			self::add_error(__("Template not found",ApmConfig::i18n_domain) . ' : ['. $tree_template_file .']');
		}
		
		return $html;
	}
	
	/**
	 * Converts self::$json_data to json and send it to output with json data http headers.
	 */
	protected static function send_json(){
		
		if( !empty(self::$errors) ){
			self::$json_data['ok'] = 0;
			self::$json_data['error'] = implode("\n",self::$errors);
		}
		
		if( class_exists('ApmQueriesWatcher') ){
			self::$json_data['queries'] = ApmQueriesWatcher::get_queries();
		}
		
		self::$json_data['action'] = isset($_POST['action']) ? $_POST['action'] : '';
		
		//If something was displayed before, clean it so that our answer can
		//be valid json (and store it in an "echoed_before_json" answer key
		//so that we can warn the user about it) :
		$content_already_echoed = ob_get_contents();
		if( !empty($content_already_echoed) ){
			self::$json_data['echoed_before_json'] = $content_already_echoed;
			ob_end_clean();
		}
		
		header('Content-type: application/json');
		echo json_encode(self::$json_data);
	}
	
	/**
	 * To add an error message to the answer
	 * @param string $error_message
	 */
	protected static function add_error($error_message){
		self::$errors[] = $error_message;
	}
	
}