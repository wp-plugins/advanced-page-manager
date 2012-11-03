<?php
/**
 * ApmApiAjax class
 * @package APM  
 */

require_once(dirname(__FILE__) ."/actions_ajax.php");

/**
 * Handles internal API actions that require an AJAX answer.
 */
class ApmApiAjax extends ApmActionsAjax{
	
	/**
	 * Checks if a user is logged in and returns an AJAX error message if not
	 */
	public static function check_admin_user_connected(){
		if( !is_user_logged_in() ){
			self::add_error('no_user_logged_in');
			self::send_json();
			exit();
		}
	}
	
}
