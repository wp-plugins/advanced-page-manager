<?php
/**
 * Advanced Page Manager addon that adds a "Last modified" column to pages tree and lists.
 * @author Uncategorized Creations
 */
class ApmLastModifiedColumn{
		
	public static function hooks(){
		add_filter('apm_manage_pages_columns', array(__CLASS__, 'apm_manage_pages_columns'));
		add_filter('apm_manage_pages_custom_column', array(__CLASS__, 'apm_manage_pages_custom_column'),10,3);
		add_filter('apm_custom_sql_orderby', array(__CLASS__, 'apm_custom_sql_orderby'),10,3);
	}
	
	public static function apm_manage_pages_columns($columns){
			
		$label = apply_filters('apm_addon_last_modified_column_label',__('Last Modified', ApmConfig::i18n_domain));	
	
		//Add "Last mmodified" column before native APM "template" column :
		$additionnal_column = array('apm-last-modified' => '<a href="#" class="custom-sortable">
														    <span>'. __($label) .'</span>
														    <span class="sorting-indicator"></span>
													    </a>');

		$date_index = array_search('template', array_keys($columns));
		
		$columns = array_slice($columns, 0, $date_index, true) + $additionnal_column + array_slice($columns, $date_index, count($columns) - $date_index, true) ;
		
		return $columns;
	}
	
	public static function apm_manage_pages_custom_column($column_name,$post_id,$node){
	
		if( $column_name == 'apm-last-modified' ){
			if( $node->status > -2 ){ //Display for all "displayable" pages status
				//Pages default WP data are already in WP cache at this point,
				//so we can do get_post without affecting performances :
				$page = get_post($post_id);
				
				$page_modified_raw = self::get_page_modified($page);
				
				$page_modified_html = '<p class="apm-last-modified-date">'. $page_modified_raw .'</p>';
				
				$page_modified = apply_filters('apm_addon_last_modified_date',$page_modified_html,$page_modified_raw,$page);
				
				echo $page_modified;
			}
				
		}
		
	}
	
	public static function apm_custom_sql_orderby($order_by_sql,$order_by,$order){
		
		if( $order_by == 'apm-last-modified' ){
			$order_by_sql = "p.post_modified $order";
		}
		
		return $order_by_sql;
	}
	
	/**
	 * Retrieves page date the same way it is retrieved in native panel :
	 * see /wp-admin/includes/class-wp-posts-list-table.php 
	 */
	private static function get_page_modified($post){
		global $mode;
		
		if ( '0000-00-00 00:00:00' == $post->post_modified ) {
			$t_time = $h_time = __( 'Unpublished' );
			$time_diff = 0;
		} else {
			$t_time = get_post_modified_time( __( 'Y/m/d g:i:s A' ) , false, $post, true);
			$m_time = $post->post_modified;
			$time = get_post_modified_time( 'G', true, $post );
		
			$time_diff = time() - $time;
			
			if ( $time_diff >= 0 && $time_diff < DAY_IN_SECONDS )
				$h_time = sprintf( __( '%s ago' ), human_time_diff( $time ) );
			else
				$h_time = mysql2date( __( 'Y/m/d' ), $m_time );
		}
		
		$page_date = '<abbr title="' . $t_time . '">' . apply_filters( 'post_date_column_time', $h_time, $post, 'apm-last-modified', $mode ) . '</abbr>';
		
		return $page_date;
	}
}

ApmLastModifiedColumn::hooks();