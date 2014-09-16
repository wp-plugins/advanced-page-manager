<?php

define('MAX_ADD_PAGES', 10);

// Define actions list
$filters_list = array(
	'tree' => array(
		'label' => __('All', ApmConfig::i18n_domain),
		'current' => true,
		'count' => 0
	),
	'publish' => array(
		'label' => __('Online', ApmConfig::i18n_domain),
		'count' => 0
	),
	'unpublish' => array(
		'label' => __('Offline', ApmConfig::i18n_domain),
		'count' => 0
	),
	'recent'   => array(
		'label' => __('Recent Pages', ApmConfig::i18n_domain),
	)
);

//Hook that can be used by addons to add a navigation filter :
$filters_list = apply_filters('apm_panel_page_filters_nav',$filters_list);

$is_browse = ApmBoContext::get_current_page() == 'browse';

$total_actions = count($filters_list);

// Returns HTML for pagination
function get_template_pagination($position = 'top') {
	$output = '  <div class="container-pagination pagination-'.$position.' tablenav-pages">';
	$output.= '    <span class="pagination-total-items displaying-num">0</span>&nbsp;<span class="displaying-num">élément(s)</span>';
	
	$output.= '    &nbsp;&nbsp;&nbsp;<span class="displaying-num nb-selected-rows">0</span>&nbsp;<span class="displaying-num">sélectionné(s)</span>';
	
	$output.= '    <div class="pagination-wrapper">';
	
	$output.= '	   		&nbsp;<span class="pagination-first-page"><a href="#" class="first-page">&lt;&lt</a></span>';	
	$output.= '    		&nbsp;<span class="pagination-preview"><a href="#" class="prev-page">&lt;</a></span>';
	
	$output.= '			<input class="current-page pagination-current-page" title="Page actuelle" type="text" name="paged" value="1" size="2">';

	$output.= '	   		&nbsp;'.__('on', ApmConfig::i18n_domain).'&nbsp;';
	$output.= '    		<span class="pagination-total-pages"></span>';
	
	$output.= '    		&nbsp;<span class="pagination-next"><a class="next-page" href="#">&gt;</a></span>';
	$output.= '	   		&nbsp;<span class="pagination-last-page"><a class="last-page" href="#">&gt;&gt;</a></span>';
	
	$output.= '    </div>';
	
	$output.= '  </div>';
	
	return $output;
}

/**
 * Retrieves page date the same way it is retrieved in native panel :
 * see /wp-admin/includes/class-wp-posts-list-table.php
 */
function apm_get_page_date($node){
	global $mode;

	$post_date = $node->publication_date;
	$post_date_gmt = $node->publication_date_gmt;
	
	//For legacy, because APM didn't set the gmt date at page creation before :
	if ( $node->status == 2 && '0000-00-00 00:00:00' == $post_date_gmt ) {
		$post_date_gmt = date('Y-m-d H:i:s',strtotime($post_date) - (get_option( 'gmt_offset' ) * 3600));
	}
	
	if ( '0000-00-00 00:00:00' == $post_date ) {
		$t_time = $h_time = __( 'Unpublished' );
		$time_diff = 0;
	} else {
		$t_time = mysql2date(__( 'Y/m/d g:i:s A' ), $post_date, true);
		$m_time = $post_date;
		$time = mysql2date('G', $post_date_gmt, false);

		$time_diff = time() - $time;
		if ( $time_diff > 0 && $time_diff < DAY_IN_SECONDS )
			$h_time = sprintf( __( '%s ago' ), human_time_diff( $time ) );
		else
			$h_time = mysql2date( __( 'Y/m/d' ), $m_time );
	}

	$page_date = '<abbr title="' . $t_time . '">' . apply_filters( 'apm_post_date_column_time', $h_time, $node, 'apm-date', $mode ) . '</abbr>';

	return $page_date;
}

// Check right of current user for actions access
function have_right() {
	$current_user = wp_get_current_user();
	if( is_super_admin() 
		|| in_array( 'administrator', $current_user->roles )
		|| in_array( 'editor', $current_user->roles )
	) return true; 
	
	return false;
}

if( !function_exists('cached_page_template_drowpdown') ){
	/**
	 * Inspired from \wp-admin\includes\theme.php::page_template_drowpdown() :
	 * Changes $templates to static because when the function is called for each displayed pages,
	 * it leads to very bad perfomances...
	 */
	function cached_page_template_drowpdown($default){

		static $templates = array();
		$display_template = __('Default Template',ApmConfig::i18n_domain);
		if( empty($templates) ){
			$templates = get_page_templates();
		}

		ksort( $templates );
		foreach (array_keys( $templates ) as $template ) {
			if ( $default == $templates[$template] )
				$display_template =  $template;
		}
		
		echo $display_template;
	}
}

$status = array(-1=>__('Offline',ApmConfig::i18n_domain),
				0=>__('Offline',ApmConfig::i18n_domain),
				1=>__('Offline',ApmConfig::i18n_domain),
				2=>__('Online',ApmConfig::i18n_domain),
				3=>__('Private'),
				4=>__('Offline',ApmConfig::i18n_domain),
				5=>__('Offline (scheduled)',ApmConfig::i18n_domain),
				);
				