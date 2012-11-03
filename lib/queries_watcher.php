<?php

class ApmQueriesWatcher{
	
	private static $queries = array();
	
	public static function get_queries(){
		return self::$queries;
	}
	
	public static function start(){
		add_filter('query',array(__CLASS__,'query'));
	}
	
	public static function stop(){
		remove_filter('query',array(__CLASS__,'intercept_wp_queries'));
	}
	
	public static function query($query){
		self::$queries[] = $query;
		return $query;
	}
	
}