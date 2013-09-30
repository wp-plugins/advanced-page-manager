<?php
/**
 * Handle APM custom columns, defining 2 hooks that can be used just like  
 * WP custom columns hooks : they are called the same, just with an "apm_" prefix :
 * - apm_manage_pages_columns : filter to add a custom column 
 * - apm_manage_pages_custom_column : action to fill in the custom column cells
 */
class ApmCustomColumns{
	
	private static $custom_columns = null;
	private static $nb_custom_columns = null;
	
	public static function echo_custom_column_th($index){
		
		self::load_custom_columns();
		
		if( array_key_exists($index,self::$custom_columns) ){
			if( !empty(self::$custom_columns[$index]) ){
				foreach(self::$custom_columns[$index] as $column_name => $value){
					?>
					<th scope="col" id="custom-col-<?php echo $column_name ?>" class="manage-column column-<?php echo $column_name ?> sortable desc">
						<?php echo $value ?>
					</th>
					<?php 
				}
			}
		}
		
	}
	
	public static function echo_custom_column_td($index,$post_id,$node){
		
		self::load_custom_columns();
		
		if( array_key_exists($index,self::$custom_columns) ){
			if( !empty(self::$custom_columns[$index]) ){
				foreach(self::$custom_columns[$index] as $column_name => $value){
					?>
					<td class="column-<?php echo $column_name ?>-td">
						<?php do_action('apm_manage_pages_custom_column',$column_name,$post_id,$node) ?>
						
						<?php //TODO : activate native custom columns hook when a specific APM option is set : ?>		
						<?php //do_action('manage_pages_custom_column',$column_name,$post_id) ?>
						<?php //do_action('manage_page_posts_custom_column',$column_name,$post_id) ?>
					</td>
					<?php 
				}
			}
		} 
	}
	
	public static function get_nb_custom_columns(){
		
		if( self::$nb_custom_columns === null ){
			
			self::load_custom_columns();
			
			$nb_custom_columns = 0;
			if( !empty(self::$custom_columns) ){
				foreach(self::$custom_columns as $index => $columns){
					$nb_custom_columns += count($columns);				
				}
			}
			
			self::$nb_custom_columns = $nb_custom_columns;
		}
		
		return self::$nb_custom_columns;
	}
	
	private static function load_custom_columns(){
		
		if( self::$custom_columns === null ){
			
			$default_apm_columns = array('status' => __('Status', ApmConfig::i18n_domain),
										 'date' => __('Date', ApmConfig::i18n_domain),
										 'template' => __('Template', ApmConfig::i18n_domain),
										 'add_page' => '',
									 	);
									 
			$filtered_columns = apply_filters('apm_manage_pages_columns',$default_apm_columns);
			
			//TODO : activate native custom columns hook when a specific APM option is set
			//$filtered_columns = apply_filters('manage_pages_columns',$filtered_columns);
			//$filtered_columns = apply_filters('manage_page_posts_columns',$filtered_columns);
			
			$default_keys = array_values(array_keys($default_apm_columns));
			$filtered_keys = array_values(array_keys($filtered_columns));
			
			//The 4 default columns must still be here AND in the same order :
			$only_custom_keys = array_values(array_diff($filtered_keys,$default_keys)); 
			if( array_values(array_diff($filtered_keys,$only_custom_keys)) !== $default_keys ){
				return;
			}
			
			//Memorize the total number of custom columns :
			self::$nb_custom_columns = count($only_custom_keys);
			
			//Init self::$custom_columns :
			self::$custom_columns = array(  'before_status' => array(),
											'before_date' => array(),
											'before_template' => array(),
											'before_add_page' => array(),
											'after_add_page' => array()
											);
			
			$status_index = array_search('status',$filtered_keys);
			$date_index = array_search('date',$filtered_keys);
			$template_index = array_search('template',$filtered_keys);
			$add_page_index = array_search('add_page',$filtered_keys);
			
			$custom_columns_keys = array();
			$custom_columns_keys['before_status'] = array_slice($filtered_keys,0,$status_index);
			$custom_columns_keys['before_date'] = array_slice($filtered_keys,$status_index+1,$date_index-$status_index-1);
			$custom_columns_keys['before_template'] = array_slice($filtered_keys,$date_index+1,$template_index-$date_index-1);
			$custom_columns_keys['before_add_page'] = array_slice($filtered_keys,$template_index+1,$add_page_index-$template_index-1);
			$custom_columns_keys['after_add_page'] = array_slice($filtered_keys,$add_page_index+1,count($filtered_keys)-$add_page_index-1);
			
			foreach($custom_columns_keys as $index => $columns_keys){
				if( !empty($columns_keys) ){
					foreach($columns_keys as $column_key){
						self::$custom_columns[$index][$column_key] = $filtered_columns[$column_key];
					}
				}
			}
			
		}
	}
	
}