<?php

class ApmConstants{
	
	const resources_version = '0.6.5';
										   
	public static $wp_status_map = array(-1=>'auto-draft',
										 0=>'draft',
										 1=>'pending',
										 2=>'publish',
										 3=>'trash');
										 
	const browse_pages_url = '/wp-admin/edit.php?post_type=page&page=apm_browse_pages_menu';
										 
}