<?php

class ApmAddons{
	
	private static $option_db_id = ApmConfig::addons_db_id;
	public static $posted_options_action = 'save_addons';
	
	private static $activated_addons = null;
	
	
	public static function hooks(){
		add_action('plugins_loaded',array(__CLASS__,'plugins_loaded'));
	}
	
	public static function plugins_loaded(){
		//Include addons if we are not activating or deactivating one of them.
		//(see self::handle_actions() for addon activation/deactivation)
		if( !isset($_POST['apm_options_action']) || $_POST['apm_options_action'] != self::$posted_options_action ){
			self::include_activated_addons_files();
		}
	}
	
	public static function get_activated_addons(){
		self::check_activated_addons_loaded();
		return self::$activated_addons;
	}
	
	public static function addon_is_on($addon){
		self::check_activated_addons_loaded();
		return array_key_exists($addon,self::$activated_addons);
	}
	
	public static function get_addons($only_activated=false){
		$addons = array();
		
		$raw_addons = self::get_addons_from_db();
		
		foreach($raw_addons as $addon_file => $activated){
			if( !$only_activated || $activated ){
				$addons[$addon_file] = array( 'option_name'=>$addon_file,
											  'file'=>$addon_file .'.php',
											  'name'=>__(ucfirst(str_replace('_',' ',$addon_file)),ApmConfig::i18n_domain), //TODO : get it from the addon file
											  'description'=>'', //TODO
											  'activated'=>$activated
											  );
			}
		}
		
		return $addons;
	}
	
	/**
	 * Use this to set a hook that will be executed at addon activation.
	 * Inspired from WP register_activation_hook() function. 
	 * @param string $file The filename of the plugin including the path.
 	 * @param callback $function the function hooked to the 'activate_addon' action.
	 */
	public static function register_activation_hook($file, $function) {
		$file = basename(plugin_basename($file));
		add_action('apm_activate_addon_' . $file, $function);
	}

	/**
	 * Use this to set a hook that will be executed at addon deactivation.
	 * Inspired from WP register_deactivation_hook() function. 
	 * @param string $file The filename of the plugin including the path.
 	 * @param callback $function the function hooked to the 'activate_addon' action.
	 */
	public static function register_deactivation_hook($file, $function) {
		$file = basename(plugin_basename($file));
		add_action('apm_deactivate_addon_' . $file, $function);
	}
	
	private static function check_activated_addons_loaded(){
		if( self::$activated_addons === null ){
			self::$activated_addons = self::get_addons(true);
		}
	}
	
	private static function save_addons($addons){
		$addons = self::check_addons($addons);

		if( get_option(self::$option_db_id) !== false ){
		    update_option(self::$option_db_id, $addons);
		}else{
			add_option(self::$option_db_id, $addons, '', 'no');
		}
	}
	
	private static function get_addons_from_db(){
		$addons = get_option(self::$option_db_id);
		
		$addons = self::check_addons($addons);

		return $addons;
	}
	
	private static function check_addons($addons=array()){

		$default_addons = array();

		if( empty($addons) || !is_array($addons) ){ //can be null or false
			$addons = $default_addons;
		}
		
		foreach( $addons as $addon_file=>$activated ){
			if( $activated === '1' || $activated === true ){
				$addons[$addon_file] = true;
			}elseif( $activated === '0' || $activated === false ){
				$addons[$addon_file] = false;
			}else{
				unset($addons[$addon_file]);
			}
		}
		
		$addons_files = self::get_addons_files();
		foreach( $addons as $addon_file=>$activated ){
			if( !in_array($addon_file .'.php',$addons_files) ){
				unset($addons[$addon_file]);
			}
		}

		foreach($addons_files as $addon_file){
			if( !array_key_exists(str_replace('.php','',$addon_file),$addons) ){
				$addons[str_replace('.php','',$addon_file)] = false;
			}
		}
		
		return $addons;
	}
	
	public static function delete_database_data(){
		delete_option(self::$option_db_id);
	}
	
	public static function handle_actions(){
	
		//Note : security nonce on APM options actions is checked in ApmOptions::handle_actions()
		
		if( !empty($_POST['apm_options_action']) ){
			
			//Addons activations/deactivations :
			if( $_POST['apm_options_action'] == self::$posted_options_action ){
				
				$current_addons = self::get_addons_from_db();
		
				$addons = self::check_addons($_POST);
				self::save_addons($addons);
				
				foreach( $addons as $addon_file=>$activated ){
					require_once(self::get_addons_directory() .'/'. $addon_file .'.php');
					if( $activated ){
						if( isset($current_addons[$addon_file]) ){ //Addon existed
							if( $current_addons[$addon_file] === false ){ 
								//Only launch activation hook if it was deactivated
								do_action('apm_activate_addon_'. $addon_file .'.php');
							}
						}else{
							//Addon didn't exist : launch activation hook:
							do_action('apm_activate_addon_'. $addon_file .'.php');
						}
					}else{
						if( isset($current_addons[$addon_file]) ){ //Addon existed
							if( $current_addons[$addon_file] === true ){ 
								//Only launch deactivation hook if it was activated
								do_action('apm_deactivate_addon_'. $addon_file .'.php');
							}
						}else{
							//Addon didn't exist and we deactivate it (a bit strange I must say...)
							//Still, it can't hurt to launch the deactivation hook:
							do_action('apm_deactivate_addon_'. $addon_file .'.php');
						}
					}
				}

				//Reload page in case the addon changes the BO menu :
				wp_redirect(add_query_arg(array('apm_options_msg'=>urlencode(__('Addons activation parameters saved successfuly',ApmConfig::i18n_domain))),ApmOptions::get_base_url(true)));
				exit();
			}
			
		}
		
	}
	
	private static function get_addons_files(){
		
		$addons_files = array();
		
		$addons_directory = self::get_addons_directory(); 
		
		if( $handle = opendir($addons_directory) ){

			while( false !== ($entry = readdir($handle)) ){
				if( $entry != '.' && $entry != '..' && strpos($entry,'.php') ){
					$addons_files[] = $entry;
				}
			}

			closedir($handle);
		}
		
		return $addons_files;
	}	
	
	public static function include_activated_addons_files(){
		self::check_activated_addons_loaded();
		foreach( self::$activated_addons as $addon ){
			require_once(self::get_addons_directory() .'/'. $addon['file']);
		}
	}
	
	private static function get_addons_directory(){
		return dirname(__FILE__) .'/addons';
	}
	
}

ApmAddons::hooks();
