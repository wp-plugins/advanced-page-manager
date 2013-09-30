<?php
/**
 * Handles AJAX actions queries, which can be of 3 types : 
 * - tree : handled by the ApmTreeAjax class,
 * - list : handled by the ApmListAjax class,
 * - nodes : handled by the ApmNodesAjax class,
 *  
 * We arrive here via the "template_redirect" hook in the main advanced_page_manager class.
 *  
 * @package APM
 */

header('Content-Type: text/html; charset=UTF-8');

require_once(dirname(__FILE__)."/api_ajax.php");
ApmApiAjax::check_admin_user_connected();

//Check the AJAX nonce
check_ajax_referer('apm_ajax_request');

if ( isset($_POST['apm_action']) ){
	$action = $_POST['apm_action'];
	
	require_once(dirname(__FILE__)."/tree_ajax.php");
	require_once(dirname(__FILE__)."/list_ajax.php");
	require_once(dirname(__FILE__)."/nodes_ajax.php");
	
	if( ApmOptions::get_option('queries_watcher_on') == true ){
		$queries_watcher_file = dirname(__FILE__)."/../lib/queries_watcher.php";
		if( file_exists($queries_watcher_file) ){
			require_once($queries_watcher_file);
			ApmQueriesWatcher::start();
		}
	}
	
	if( method_exists('ApmTreeAjax',$action) ){
		ApmTreeAjax::$action();
	}else if( method_exists('ApmListAjax',$action) ){
		ApmListAjax::$action();
	}else if( method_exists('ApmNodesAjax',$action) ){
		ApmNodesAjax::$action();
	}

}

exit();
