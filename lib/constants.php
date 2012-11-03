<?php

class ApmConstants{
	
	const apm_version = 'beta 1';
	
	const resources_version = 'beta1.1';
										   
	public static $wp_status_map = array(0=>'draft',
										 1=>'pending',
										 2=>'publish',
										 3=>'trash');
										 
	const browse_pages_url = '/wp-admin/edit.php?post_type=page&page=apm_browse_pages_menu';
										 
}