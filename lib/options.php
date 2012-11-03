<?php

class ApmOptions{
	
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
	
	public static function get_lost_pages(){
		global $wpdb;
		
		$lost_pages = array();
		
		$tree = ApmTreeDb::get_last_tree();
		
		$tree = new ApmTree($tree);
		$tree_apm_ids = $tree->get_nodes_flat();
		$tree_wp_ids = ApmNodeDataIntern::get_wp_ids($tree_apm_ids);
		$tree_wp_ids = array_diff($tree_wp_ids,array(ApmTreeData::root_id)); //Remove the zeroes (for root).
		
		$sql = "SELECT * FROM $wpdb->posts AS p WHERE p.post_type = 'page' AND p.ID NOT IN ('". implode("','",$tree_wp_ids) ."')";
		$lost_pages_raw = $wpdb->get_results($sql);
		
		foreach($lost_pages_raw as $page){
			$lost_pages[$page->ID] = $page;
		}
		
		return $lost_pages;
	}
	
	public static function get_base_url(){
		//TODO : add nonce!
		
		$options_url = get_option('siteurl') .'/wp-admin/edit.php?post_type=page&page=apm_options_pages_menu';
		
		return $options_url; 
	}
	
	public static function get_restore_page_url($page_id){
		return add_query_arg(array('apm_options_action'=>'restore_page','wp_id'=>$page_id,'redirect_to_page_in_tree'=>true),self::get_base_url());
	}
	
	public static function handle_actions(){
	
		self::check_tree_loaded();
		
		//TODO : check nonce!
		
		if( !empty($_GET['apm_options_action']) ){
			
			switch( $_GET['apm_options_action'] ){
				
				case 'delete_all_data':
					ApmTreeData::delete_database_data(false);
					wp_redirect(add_query_arg(array('apm_options_msg'=>urlencode(__("Plugin data (except options) have been deleted",ApmConfig::i18n_domain))),self::get_base_url()));
					exit();
					break;
					
				case 'delete_options':
					ApmOptions::delete_database_data();
					wp_redirect(add_query_arg(array('apm_options_msg'=>urlencode(__("Plugin options have been deleted ",ApmConfig::i18n_domain))),self::get_base_url()));
					exit();
					break;
					
				case 'delete_folding_infos':
					ApmTreeState::delete_all();
					wp_redirect(add_query_arg(array('apm_options_msg'=>urlencode(__("Fold / Unfold data have been deleted",ApmConfig::i18n_domain))),self::get_base_url()));
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
					wp_redirect(add_query_arg(array('apm_options_msg'=>urlencode(__('APM tree successfully restored from Wordpress pages',ApmConfig::i18n_domain))),self::get_base_url()));
					exit();
					break;
					
				case 'apm_tree_to_wp_pages_tree':
					$tree = new ApmTreeData();
					$tree->load_last_tree();
					$tree->synchronize_tree_with_wp_entities();
					wp_redirect(add_query_arg(array('apm_options_msg'=>urlencode(__('Wordpress pages tree successfully restore from APM tree',ApmConfig::i18n_domain))),self::get_base_url()));
					exit();
					break;
			}
			
			do_action('apm_options_handle_action',$_GET['apm_options_action'],self::get_base_url());
			
		}elseif( !empty($_POST['apm_options_action']) ){
			
			switch( $_POST['apm_options_action'] ){
				
				case 'save_admin_options':
					$options = self::format_options($_POST);
					self::save_options($options);
					self::$feedback['msg'] = __('Admin options saved successfuly',ApmConfig::i18n_domain);
					break;
					
			}
			
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
