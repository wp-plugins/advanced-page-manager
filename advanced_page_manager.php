<?php
/*
 * Plugin Name: Advanced Page Manager
 * Description: A plugin that redefines the way you create, move, edit and publish your pages.  
 * Version: 1.2
 * Author: Uncategorized Creations
 * Plugin URI: http://www.uncategorized-creations.com/
 * Author URI: http://www.uncategorized-creations.com/
 * License: GNU General Public License v2 or later
 */

/*  
Copyright 2012 Uncategorized Creations  (email : uncategorized.creations@gmail.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if( !class_exists('advanced_page_manager') ){

require_once(dirname(__FILE__).'/lib/config.php');
require_once(dirname(__FILE__).'/lib/options.php');
require_once(dirname(__FILE__).'/lib/constants.php');
require_once(dirname(__FILE__).'/lib/addons.php');
require_once(dirname(__FILE__).'/lib/bo_context.php');
require_once(dirname(__FILE__).'/lib/tree_data.php');
require_once(dirname(__FILE__).'/lib/pointers.php');
require_once(dirname(__FILE__).'/lib/functions.php');
require_once(dirname(__FILE__).'/lib/template_tags.php');

class advanced_page_manager{
	
	/**
	 * Defines hooks used by the plugin
	 */
	public static function hooks(){
		
		register_activation_hook(__FILE__, array(__CLASS__,'activate'));
		register_deactivation_hook( __FILE__, array(__CLASS__, 'deactivate' ) );
		register_uninstall_hook( __FILE__, array(__CLASS__, 'uninstall' ) );
		
		add_action('wp_ajax_apm_tree_actions', array(__CLASS__, 'ajax_tree_actions'));
		
		add_action('admin_menu', array(__CLASS__, 'admin_menu'));
		
		//For the settings panel : admin_menu with priority 99 so that it's allways the last one.
		//(Addons may want to add their own admin panel, which have to come before this settings panel)
		add_action('admin_menu', array(__CLASS__, 'admin_menu_settings'),99); 
		
		add_action('admin_enqueue_scripts', array(__CLASS__,'admin_enqueue_scripts'));
		add_action('admin_bar_menu', array(__CLASS__, 'admin_bar_menu'));
		
		//Add meta box hooks (see codex reference: http://codex.wordpress.org/Function_Reference/add_meta_box)
		//WP 3.0+
		//add_action('add_meta_boxes', array(__CLASS__,'add_meta_boxes'));
		//backwards compatible:
		add_action('admin_init', array(__CLASS__,'add_meta_boxes'), 1);
		
		add_action('admin_init', array(__CLASS__,'admin_redirect'), 2);
		
		add_action('plugins_loaded', array(__CLASS__,'plugins_loaded'));
		
		add_action('template_redirect', array(__CLASS__,'seo_permalinks_addon_parachute'), 1);
		
		//Deactivated for now because handling page insertion by other plugins has to be thoroughly tested :
		//add_action('wp_insert_post',  array(__CLASS__,'wp_insert_post'),10,2);
	}
	
	/**
	 * Loads the plugin translations 
	 */
	public static function plugins_loaded(){
		load_plugin_textdomain(ApmConfig::i18n_domain, false, dirname( plugin_basename( __FILE__ ) ) . '/lang/');
	}
	
	/**
	 * What to do on plugin activation
	 */
	public static function activate(){	
		//ApmAddons::plugins_loaded() hook is not fired at plugin activation,
		//so we include addons file that may be needed in the following 
		//"ApmTreeData::update_tree_data_on_install()" :
		ApmAddons::include_activated_addons_files();   
		
		//Some pages may have been deleted/created/modified since last plugin install :
		ApmTreeData::update_tree_data_on_install();
	}
	
	/**
	 * What to do on plugin deactivation
	 */
	public static function deactivate(){		
    	//Nothing for now.	
	}
	
	/**
	 * Delete plugin database footprint on unsinstall
	 */
	public static function uninstall(){
    	ApmTreeData::delete_database_data();
    	ApmAddons::delete_database_data();
	}
		
	/**
	 * Adds our entries to back office "Pages" admin submenu (and removes the Wordpress default ones)
	 */
	public static function admin_menu(){
		
		$browse_pages_menu = add_submenu_page( 'edit.php?post_type=page', __('All Pages',ApmConfig::i18n_domain), __('All Pages',ApmConfig::i18n_domain), 'edit_published_posts','apm_browse_pages_menu', array(__CLASS__,'bo_panel_template'));
		add_action('admin_print_styles-'. $browse_pages_menu,  array(__CLASS__,'admin_print_styles_admin_page'));
	
		remove_submenu_page('edit.php?post_type=page','post-new.php?post_type=page');
		remove_submenu_page('edit.php?post_type=page','edit.php?post_type=page');
		
	}
	
	/**
	 * Adds the settings entry as last item of the "Pages" admin submenu 
	 * AND put our "All pages" submenu back to first position (in case we added
	 * pages custom taxonomies for example)
	 */
	public static function admin_menu_settings(){
		
		//Put our "All pages" submenu back to first position (in case we added
	 	//pages custom taxonomies for example):
	 	//TODO : This is a hack on the global $submenu. See if there is a best way to 
	 	//reorder admin submenus. 
		global $submenu;
		if( array_key_exists('edit.php?post_type=page',$submenu) ){
			$pages_submenu = $submenu['edit.php?post_type=page']; //"Pages" menu
			foreach($pages_submenu as $priority => $data){
				if( in_array('apm_browse_pages_menu',$data) ){ //Our "All pages" submenu
					unset($submenu['edit.php?post_type=page'][$priority]);
					$submenu['edit.php?post_type=page'][1] = $data;
					ksort($submenu['edit.php?post_type=page']);
					break;
				}
			}
		}
		
		//Settings submenu : admins only :
		if( current_user_can('manage_options') ){
			add_submenu_page('edit.php?post_type=page', __('Settings',ApmConfig::i18n_domain), __('Settings',ApmConfig::i18n_domain), 'activate_plugins', 'apm_options_pages_menu', array(__CLASS__,'bo_options_panel_template'));
		}
	}
	
	/**
	 * Enqueues scripts, styles and javascript localization used by the plugin
	 */
	public static function admin_enqueue_scripts(){

		if( isset($_GET['page']) ){ 
			
			$load_api_and_template_resources = apply_filters('apm_load_api_and_template_resources',false);
		    
			if( $_GET['page'] == 'apm_browse_pages_menu' || $load_api_and_template_resources ){
				wp_enqueue_script('apm_tree_api_js',plugins_url('js/tree.js', __FILE__),array(),ApmConstants::resources_version);
				wp_localize_script('apm_tree_api_js','apm_api_js_data',self::get_js_vars());
				
				$template_path = "templates/". ApmOptions::get_option('panel_page_template_name');
				
				$js_template_path = $template_path ."/common.js";
				$panel_js_file = dirname(__FILE__) .'/'. $js_template_path;
				if( file_exists($panel_js_file) ){
					wp_enqueue_script('apm_bo_common_scripts', plugins_url($js_template_path, __FILE__),array('jquery-color'),ApmConstants::resources_version);
					wp_localize_script('apm_bo_common_scripts','apm_messages',self::get_js_messages());
				}else{
					self::show_error(__('Resource not found',ApmConfig::i18n_domain) . ' : ['. $panel_js_file .']');
				}
				
		    	$js_template_path = $template_path ."/browse.js";
				$panel_js_file = dirname(__FILE__) .'/'. $js_template_path;
				if( file_exists($panel_js_file) ){
					wp_enqueue_script('apm_bo_browse_scripts', plugins_url($js_template_path, __FILE__),array(),ApmConstants::resources_version);
				}else{
					self::show_error(__('Resource not found',ApmConfig::i18n_domain) .' : ['. $panel_js_file .']');
				}
					
				$js_template_path = $template_path ."/list.js";
				$panel_js_file = dirname(__FILE__) .'/'. $js_template_path;
				if( file_exists($panel_js_file) ){
					wp_enqueue_script('apm_bo_list_scripts', plugins_url($js_template_path, __FILE__),array(),ApmConstants::resources_version);
				}else{
					self::show_error(__('Resource not found',ApmConfig::i18n_domain) .' : ['. $panel_js_file .']');
				}
				
		    }	
		}elseif( isset($_GET['post']) && isset($_GET['action']) && $_GET['action'] == 'edit' ){
			global $post;
			if( $post->post_type == 'page' ){
				wp_enqueue_script('apm_tree_api_js',plugins_url('js/tree.js', __FILE__),array(),ApmConstants::resources_version);
				wp_localize_script('apm_tree_api_js','apm_api_js_data',self::get_js_vars());
				
				wp_enqueue_script('apm_post_edit_js',plugins_url('js/post_edit.js', __FILE__),array(),ApmConstants::resources_version);

				wp_register_style('apm_post_edit_css', plugins_url('css/post_edit.css', __FILE__), array(),ApmConstants::resources_version);
	        	wp_enqueue_style('apm_post_edit_css', false, array(),ApmConstants::resources_version);
			}
		}
	}
	
	/**
	 * Includes styles required for the new "All pages" panel
	 */
	public static function admin_print_styles_admin_page(){
		$css_template_path = "/templates/". ApmOptions::get_option('panel_page_template_name') ."/common.css";
		$panel_css_file = dirname(__FILE__) .'/'. $css_template_path;
		if( file_exists($panel_css_file) ){
			wp_register_style('apm_sb_panel_page_css', plugins_url($css_template_path, __FILE__));
			wp_enqueue_style('apm_sb_panel_page_css',false,array(),ApmConstants::resources_version);
		}else{
			self::show_error(__('Resource not found',ApmConfig::i18n_domain) .' : ['. $panel_css_file .']');
		}
	}
	
	/**
	 * Handles AJAX requests by redirecting them to the plugin AJAX processor
	 */
	public static function ajax_tree_actions() {
		$ajax_path = plugin_dir_path(__FILE__) .'ajax/';
		include($ajax_path . 'ajax-processor.php');
		exit;
	}
	
	/**
	 * Plugin specific redirections
	 */
	public static function admin_redirect(){
		global $pagenow, $typenow, $plugin_page;
		
		if( $pagenow == 'post-new.php' && $typenow == 'page' ){
			//Redirect "New page" to our "All pages" panel :
			wp_redirect(ApmBoContext::get_browse_url());
			exit();
		}elseif( $plugin_page == 'apm_browse_pages_menu' && $pagenow == 'admin.php' ){
			//Redirect main "Pages" link to "All pages" panel :
			wp_redirect(ApmBoContext::get_browse_url());
			exit();
		}elseif( $pagenow == 'edit.php' && $typenow == 'page' 
				 && empty($plugin_page) ){
			//Redirect standard WP pages list to our customized "All pages" panel :
			//However, if needed, we can display the native pages list using the 
			//"apm_force_native_display" $_GET parameter :
			if( !isset($_GET['apm_force_native_display']) ){
				wp_redirect(ApmBoContext::get_browse_url());
				exit();
			}
		}
		
		//Bugfix "Headers already sent" on action in Pages > Settings > Plugin data management for some configs.
		//TODO : see if we can identify more precisely what is causing this to find a more targeted fix. 
		//(see ApmOptions::handle_actions())
		if( !empty( $_GET['apm_options_action'] ) ){
			ob_start();
		}
		
	}
	
	/**
	 * Defines the vars that are passed from PHP to javascript thru localize_script().
	 */
	private static function get_js_vars(){
		
		$js_vars = array(
			'site_url' => get_bloginfo('wpurl'),
			'ajax_url' => admin_url( '/admin-ajax.php' ),
			'wp_nonce' => wp_create_nonce( 'apm_ajax_request' ),
			'login_url' => wp_login_url(),
			'login_url_redirect_to_browse_page' => wp_login_url(ApmBoContext::get_browse_url()),
			'no_user_logged_in_error_msg' => __("Your session has expired.\nYou will be redirected to login page.",ApmConfig::i18n_domain),
		);
		
		$activated_addons = ApmAddons::get_activated_addons();
		$js_vars['activated_addons'] = array_keys($activated_addons);
		
		$js_vars = apply_filters('apm_js_vars',$js_vars); 
		
		return $js_vars;
	}
	
	/**
	 * Defines the translations for messages that are passed from PHP to javascript thru localize_script().
	 */
	private static function get_js_messages(){
		$template_lang_file = dirname(__FILE__) ."/templates/". ApmOptions::get_option('panel_page_template_name') .'/js_messages.php';
		if( file_exists($template_lang_file) ){
			require_once($template_lang_file); //must define a $apm_messages array
			if( isset($apm_messages) ){
				return $apm_messages;
			}else{
				self::show_error(__('An $apm_messages array should be defined in',ApmConfig::i18n_domain) .' : ['. $template_lang_file .']');
			}
		}else{
			self::show_error(__('Resource not found',ApmConfig::i18n_domain) . ' : ['. $template_lang_file .']');
		}
	}
	
	/**
	 * Loads the "All pages" template 
	 */
	public static function bo_panel_template(){
		
		ApmBoContext::set_current_page_infos($_GET['page']);
		
		$template_file = dirname(__FILE__) ."/templates/". ApmOptions::get_option('panel_page_template_name') ."/browse.php";
		if( file_exists($template_file) ){
			require_once(dirname(__FILE__).'/lib/custom_columns.php');
			require_once($template_file);
		}else{
			self::show_error(__('Template not found',ApmConfig::i18n_domain) . ' : ['. $template_file .']');
		}
	}
	
	/**
	 * Loads the template for the plugin options panel
	 */
	public static function bo_options_panel_template(){
		ApmOptions::handle_actions();
		ApmAddons::handle_actions();
		require_once(dirname(__FILE__) ."/templates/core/bo_options_panel.php");
	}
	
	/**
	 * Echoes internal errors.  
	 * @param string $msg
	 */
	private static function show_error($msg){
		echo '<p>Advanced Page Manager plugin error : '. $msg .'</p>';
	}

	/**
	 * Adds the "page attributes" meta box to the page edition
	 */
	public static function add_meta_boxes(){
		remove_meta_box('pageparentdiv','page','side');
		add_meta_box('apm_pageparentdiv', __('Page Attributes'), array(__CLASS__,'page_attributes_meta_box'), 'page', 'side', 'core');
	}
	
	/**
	 * Loads the template for the "page attributes" meta box 
	 * @param unknown_type $post
	 */
	public static function page_attributes_meta_box($post) {
		require_once(dirname(__FILE__) .'/templates/core/page_attributes_meta_box.php');
	}
	
	/**
	 * If the "seo_permalinks" addon has been installed, the "seoed" urls will
	 * lead to 404 once the addon is deactivated : we correct that here, redirecting 
	 * to the standard page url.
	 */
	public static function seo_permalinks_addon_parachute(){
		
		$requested_url  = is_ssl() ? 'https://' : 'http://';
		$requested_url .= $_SERVER['HTTP_HOST'];
		$requested_url .= $_SERVER['REQUEST_URI'];
		
		if( is_404() && preg_match('|.*'. ApmConfig::apm_seo_permalinks_id_url_var .'(\d+)/?$|',$requested_url,$matches) ){
			$redirect_url = get_permalink($matches[1]);
			wp_redirect($redirect_url, 301);
			exit($redirect_url);
		}
		
	}
	
	/**
	 * To handle pages inserted from outside APM (by another plugin for example)
	 * @param int $post_id
	 * @param object $post
	 */
	public static function wp_insert_post($post_id,$post){
		if( $post->post_type == 'page' ){
			//Deactivated for now because handling page insertion by other plugins has to be thoroughly tested :
			//ApmTreeData::insert_page_from_outside($post);
		}
	}
	
	/**
	 * To add some APM infos to the admin bar
	 */
	public static function admin_bar_menu(){
		/*
		//TODO : this appears as first element in admin toolbar... would be better as last element... 
		
		global $wp_admin_bar;
		global $wp_query;
		
		if( $wp_query->is_page() ){
			global $post;
			
			$args = array(
				'id' => 'apm_go_to_page_menu_link',
				'title' => __("Where is this page ?", ApmConfig::i18n_domain),
				'href' => ApmBoContext::get_reach_node_url($post->ID)
			);
		}
		
		$wp_admin_bar->add_node($args);
		*/ 
	}
	
}
}

advanced_page_manager::hooks();

?>