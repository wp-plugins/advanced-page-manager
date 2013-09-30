<?php 

class ApmConfig{
	
	const option_db_id = 'advanced_page_manager_options_db';
	const addons_db_id = 'advanced_page_manager_addons_db';
	const tree_option_db_id = 'advanced_page_manager_tree_db';
	const tree_state_user_meta = 'advanced_page_manager_tree_state';
	const i18n_domain = 'advanced-page-manager';
	const apm_seo_permalinks_id_url_var = 'apm-';
	
	/**
	 * Only pages with the following post status will be loaded in APM tree.
	 * This can be modified via the "apm_allowed_post_status" hook
	 */
	public static $allowed_post_status = array('draft','publish','pending','trash','private');
}
