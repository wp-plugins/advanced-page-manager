<?php

require_once(dirname(__FILE__) .'/config.php');

class ApmOptions{
	
	/**
	 * Feedback message displayed when editing options
	 * @var array
	 */
	public static $feedback = array('msg'=>'','type'=>'info');
	
	/**
	 * Database identifier for the apm_sites_builder options (in wp_options table). 
	 * @var string
	 */
	private static $option_db_id = ApmConfig::option_db_id;
	
	/**
	 * Plugin admin options
	 * @var array
	 */
	private static $admin_options = array();
	
	/**
	 * Nonce used when saving plugin options
	 * @var string
	 */
	const save_options_nonce = 'apm_save_plugin_options';
	
	/**
	 * Retrieves on choosen option from plugin options
	 * @param string $option Option name
	 */
	public static function get_option($option){
		if( empty(self::$admin_options) ){
			self::load_options();
		}
		
		return array_key_exists($option,self::$admin_options) ? self::$admin_options[$option] : '';
	}
	
	/**
	 * Loads options from database
	 */
	private static function load_options(){
		$options = get_option(self::$option_db_id);
		
		if( empty($options) ){
			$options = self::format_options();
		}
		
		self::$admin_options = $options;
	}
	
	/**
	 * Saves given options to database
	 * @param array $options
	 */
	public static function save_options($options){
		$options = self::format_options($options);
		if( get_option(self::$option_db_id) !== false ){
		    update_option(self::$option_db_id, $options);
		}else{
			add_option(self::$option_db_id, $options, '', 'no');
		}
		self::$admin_options = $options;
	}
	
	public static function delete_database_data(){
		delete_option(self::$option_db_id);
	}
	
	/**
	 * Formats the given option : 
	 * @param array $options If not set, will retrieve the default options.
	 */
	private static function format_options($options=array()){

		if( empty($options) ){ //can be null or false
			$options = array();
		}
		
		//Default values :
		$default_options = array('panel_page_template_name'=>'panel_page',
							 	 'queries_watcher_on'=>false,
								 'display_lost_pages'=>false,
							    );
							    
		$default_options = apply_filters('apm_default_plugin_options',$default_options);
							    
		foreach($options as $k=>$v){
			if( !array_key_exists($k,$default_options) ){
				unset($options[$k]);
			}
		}
							    
		foreach($default_options as $k=>$v){
			if( !array_key_exists($k,$options) ){
				$options[$k] = $v;
			}
		}
		
		if( $options['queries_watcher_on'] ){ //Can be 1 or '1'. We need == true :
			$options['queries_watcher_on'] = true;
		}
		
		if( $options['display_lost_pages'] ){ //Can be 1 or '1'. We need == true :
			$options['display_lost_pages'] = true;
		}
		
		return $options;
	}
	
	public static function get_lost_pages($allow_autodrafts=false){
		global $wpdb;
		
		$lost_pages = array();
		
		$tree = ApmTreeDb::get_last_tree();
		if( !empty($tree) ){
			$tree = new ApmTree($tree);
			$tree_apm_ids = $tree->get_nodes_flat();
			$tree_wp_ids = ApmNodeDataIntern::get_wp_ids($tree_apm_ids);
			$tree_wp_ids = array_diff($tree_wp_ids,array(ApmTreeData::root_id)); //Remove the zeroes (for root).
			
			$allowed_post_status = ApmConfig::$allowed_post_status;
			
			if( $allow_autodrafts ){
				$allowed_post_status[] = 'auto-draft';
			}
			
			$allowed_post_status = apply_filters('apm_allowed_post_status',$allowed_post_status,'get_lost_pages');
			
			$allowed_post_status = array_map("addslashes",$allowed_post_status);
			
			$sql_status = " AND post_status IN ('". implode("','",$allowed_post_status) ."') ";
			
			$sql = "SELECT * FROM $wpdb->posts AS p 
							 WHERE p.post_type = 'page' $sql_status AND p.ID NOT IN ('". implode("','",$tree_wp_ids) ."')";
			
			$lost_pages_raw = $wpdb->get_results($sql);
			
			if( !empty($lost_pages_raw) ){
				foreach($lost_pages_raw as $page){
					$page->post_title = _draft_or_post_title($page->ID);
					$lost_pages[$page->ID] = $page;
				}
			}
		}
		
		return $lost_pages;
	}
	
	public static function get_base_url($no_nonce=false){
		
		$options_url = get_option('siteurl') .'/wp-admin/edit.php?post_type=page&page=apm_options_pages_menu';
		
		if( !$no_nonce ){
			$options_url = wp_nonce_url($options_url,self::save_options_nonce);
		}
		
		return $options_url; 
	}
	
	public static function get_restore_page_url($page_id,$redirect_to_page_in_tree=true){
		return add_query_arg(array('apm_options_action'=>'restore_page','wp_id'=>$page_id,'redirect_to_page_in_tree'=>$redirect_to_page_in_tree),self::get_base_url());
	}
	
	public static function handle_actions(){
	
		//Check nonce:
		if( !empty($_REQUEST['apm_options_action']) && !wp_verify_nonce($_REQUEST['_wpnonce'],self::save_options_nonce) ){
			wp_die(__("Could not save plugin settings : security check failed.",ApmConfig::i18n_domain));
			exit();
		}

		$redirect_url = self::get_base_url(true);
		
		if( !empty($_GET['apm_options_action']) ){

			//Bugfix "Headers already sent" on action in Pages > Settings > Plugin data management for some configs.
			//TODO : see if we can identify more precisely what is causing this to find a more targeted fix.
			$buffer = ob_get_clean();
			
			switch( $_GET['apm_options_action'] ){
				
				case 'delete_all_data':
					ApmTreeData::delete_database_data(false);
					wp_redirect( add_query_arg( array( 'apm_options_msg'=>2 ), $redirect_url ) );
					exit();
					break;
					
				case 'delete_options':
					ApmOptions::delete_database_data();
					wp_redirect( add_query_arg( array( 'apm_options_msg'=>3 ), $redirect_url ) );
					exit();
					break;
					
				case 'delete_folding_infos':
					ApmTreeState::delete_all();
					wp_redirect( add_query_arg( array( 'apm_options_msg'=>4 ), $redirect_url ) );
					exit();
					break;
					
				case 'restore_page':
					if( !empty($_GET['wp_id']) ){
						
						//Check if the page is still lost : 
						$lost_pages = self::get_lost_pages();
						if( array_key_exists($_GET['wp_id'],$lost_pages) ){
							$tree = new ApmTreeData();
							$tree->load_last_tree();
							
							$apm_id = $tree->add_new_node('insert_child',ApmTreeData::root_id,'page',$_GET['wp_id']);
							
							self::$feedback['msg'] = sprintf(__('The page "%s" has been successful restored to the end of the tree',ApmConfig::i18n_domain),$lost_pages[$_GET['wp_id']]->post_title);
							
							if( !empty($_GET['redirect_to_page_in_tree']) ){
								wp_redirect(ApmBoContext::get_reach_node_url($apm_id));
								exit();
							}
							
						}else{
							self::$feedback['type'] = 'error';
							self::$feedback['msg'] = sprintf(__('The page to restore (wp_id = %s) is not in lost pages.',ApmConfig::i18n_domain),$_GET['wp_id']);
						}
					}
					break;
					
				case 'reset_tree_from_wp_pages':
					//Restore apm tree from WP pages tree
					$tree = new ApmTreeData();
					$tree->reset_tree_and_data();
					wp_redirect( add_query_arg( array( 'apm_options_msg'=>5 ), $redirect_url ) );
					exit();
					break;
					
				case 'apm_tree_to_wp_pages_tree':
					$tree = new ApmTreeData();
					$tree->load_last_tree();
					$tree->synchronize_tree_with_wp_entities();
					wp_redirect( add_query_arg( array( 'apm_options_msg'=>6 ), $redirect_url ) );
					exit();
					break;
			}
			
			//Bugfix "Headers already sent" on action in Pages > Settings > Plugin data management for some configs.
			//TODO : see if we can identify more precisely what is causing this to find a more targeted fix.
			echo $buffer;
			
			do_action('apm_options_handle_get_action',$_GET['apm_options_action'],$redirect_url);
			
		}elseif( !empty($_POST['apm_options_action']) ){
			
			switch( $_POST['apm_options_action'] ){
				
				case 'save_admin_options':
					self::save_options($_POST);
					self::$feedback['msg'] = __('Admin options saved successfuly',ApmConfig::i18n_domain);
					break;
					
			}
			
			do_action('apm_options_handle_post_action',$_POST['apm_options_action'],$redirect_url);
			
		}
		
	}
	
	private static function check_tree_loaded(){
		$tree_db = ApmTreeDb::get_last_tree();
		if( empty($tree_db) ){
			$tree = new ApmTreeData();
			$tree->load_last_tree(); //Will load the tree from WP entities
		}
	}
	
	/**
	* Returns a feedback message
	*/
	public static function get_msg( $msg ){
		$msg = intval( $msg );
		
		// Possible feedback messages displayed when editing options
		$feedback_msg = array(
			1 => __( 'Addons activation parameters saved successfuly', ApmConfig::i18n_domain ),
			2 => __( 'Plugin data (except options) have been deleted', ApmConfig::i18n_domain ),
			3 => __( 'Plugin options have been deleted ', ApmConfig::i18n_domain ),
			4 => __( 'Fold / Unfold data have been deleted', ApmConfig::i18n_domain ),
			5 => __( 'APM tree successfully restored from Wordpress pages', ApmConfig::i18n_domain ),
			6 => __( 'Wordpress pages tree successfully restore from APM tree', ApmConfig::i18n_domain ),
			7 => __( 'Flags data has been successfully deleted', ApmConfig::i18n_domain ),
			8 => __( 'Page taxonomies settings saved successfuly', ApmConfig::i18n_domain ),
		);
		
		return !empty( $feedback_msg[$msg] ) ? $feedback_msg[$msg] : self::$feedback['msg'];
	}
	
	/**
	* Returns a feedback message type
	*/
	public static function get_msg_type( $type ){
		$type = intval( $type );
		
		// Possible feedback message types displayed when editing options
		$feedback_type = array(
			1 => 'info',
			2 => 'error',
		);
		
		return !empty( self::$feedback_type[$type] ) ? self::$feedback_type[$type] : self::$feedback['type'];
	}
	
	/**
	 * Debug functions :
	 */
	
	/**
	 * Debug function : to display tree nodes
	 */
	public static function get_raw_tree(){
		return ApmTreeDb::get_last_tree();
	}
	
	/**
	 * Debug function : to display tree nodes fold/unfold states
	 */
	public static function get_tree_state(){
		$tree_state = new ApmTreeState();
		$tree_state->load();
		return $tree_state->get_tree_state();
	}
	
	/**
	 * Debug function : to display tree nodes marks infos
	 */
	public static function get_marked(){
		$marked_pages = array();
		if( ApmAddons::addon_is_on('flagged_pages') ){
			$marked = new ApmMarkedNodes();
			$marked_pages = $marked->get_marked_nodes();
		}
		return $marked_pages;
	}
}
