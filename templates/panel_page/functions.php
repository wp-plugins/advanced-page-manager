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
				);
				