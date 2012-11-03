<?php

require_once(dirname(__FILE__) .'/config.php');

class ApmNodeDataIntern{
	private $apm_id = 0;
	private $type = '';
	private $wp_id = 0;
	
	public function __construct($defaults=array()){
		foreach($defaults as $attribute=>$value){
			if( property_exists(get_class($this),$attribute) ){
				$this->{$attribute} = $value;
			}
		}
	}
	
	public function __get($name){
		if( property_exists(get_class($this),$name) ){
			return $this->{$name};
		}
		return null;
	}
	
	public function load($apm_id){
		global $wpdb;
		
		if( $apm_id == ApmTreeData::root_id ){
			
			$this->apm_id = ApmTreeData::root_id;
			$this->type = 'root';
			$this->wp_id = 0;
			
		}else{
		
			$sql = "SELECT ID,post_type FROM $wpdb->posts WHERE ID='$apm_id' LIMIT 1";
			
			$row = $wpdb->get_row($sql);
			
			if( !empty($row) ){
				$this->apm_id = $row->ID;
				$this->type = $row->post_type;
				$this->wp_id = $row->ID;
			}
		}
	}
	
	public static function load_multiple($apm_ids,$order_matters=false){
		global $wpdb;
		$loaded_data = array();
		$ids_wp = array();
		
		if( !is_array($apm_ids) ){
			$apm_ids = array($apm_ids);
		}	
		
		if( in_array(ApmTreeData::root_id,$apm_ids) ){
			$loaded_data[ApmTreeData::root_id] = new ApmNodeDataIntern(array('apm_id'=>ApmTreeData::root_id,
																			 'type'=>'root',
																			 'wp_id'=>0));
		}

		$apm_ids = "'". implode("','",$apm_ids) ."'";
		
		$sql = "SELECT ID,post_type FROM $wpdb->posts WHERE ID IN ($apm_ids)";
		
		if( $order_matters ){
			$sql .= " ORDER BY FIELD( ID, $apm_ids) ";
		}

		$results = $wpdb->get_results($sql);

		foreach($results as $row){
			$loaded_data[$row->ID] = new ApmNodeDataIntern(array('apm_id'=>$row->ID,
																 'type'=>$row->post_type,
																 'wp_id'=>$row->ID));
			if( !empty($row->ID) ){
				$ids_wp[$row->post_type][$row->ID] = $row->ID; //TODO : not usefull since apm_id = wp_id...
			}
		}
		
		return array('nodes_data'=>$loaded_data,'ids_wp'=>$ids_wp);
	}
	
	public static function load_multiple_from_wp_ids($wp_ids_to_load,$order_matters=false){
		global $wpdb;
		$loaded_data = array();
		$ids_wp = array();
		
		if( is_array($wp_ids_to_load) ){
			$wp_ids_to_load = "'". implode("','",$wp_ids_to_load) ."'";
		}	

		$sql = "SELECT ID,post_type FROM $wpdb->posts WHERE ID IN ($wp_ids_to_load)";
		
		if( $order_matters ){
			$sql .= " ORDER BY FIELD( ID, $wp_ids_to_load) ";
		}
		
		$results = $wpdb->get_results($sql);

		foreach($results as $row){
			$loaded_data[$row->ID] = new ApmNodeDataIntern(array('apm_id'=>$row->ID,
																 'type'=>$row->post_type,
																 'wp_id'=>$row->ID));
			if( !empty($row->ID) ){
				$ids_wp[$row->post_type][$row->ID] = $row->ID; //TODO : not usefull since apm_id = wp_id...
			}
		}
		
		return array('nodes_data'=>$loaded_data,'ids_wp'=>$ids_wp);
	}
	
	/**
	 * Retrieve only the wp_ids corresponding to given $apm_ids
	 * @param array $apm_ids
	 */
	public static function get_wp_ids($apm_ids,$order_matters=false){
		$ids_wp = array();
		
		foreach($apm_ids as $apm_id){
			$ids_wp[$apm_id] = $apm_id; //apm_id = wp_id now!! TODO : clean this!
		}
		
		return $ids_wp; 
	}

}

class ApmNodeDataDisplay{
	
	/**
	 * Instance of ApmNodeDataIntern
	 * @var ApmNodeDataIntern
	 */
	private $intern_data;
	
	private $display_id;
	private $status;
	private $visibility;
	private $publication_date;
	private $template;
	private $author;
	private $title;
	private $description;
	private $url_front;
	private $url_edit;
	private $is_folded;
	
	private $meta_data = array(); //custom_fields
	
	private $node_position = array('order'=>'','parent'=>'','depth'=>'','nb_children'=>'','children'=>array(),
								   'siblings_no_current'=>'','all_siblings'=>'','siblings_before'=>'','siblings_after'=>'',
								   'previous_sibling'=>'','next_sibling'=>''
								   );

	private $marked = 0;
	
	public function __construct($default_attributes=array()){
		$this->intern_data = new ApmNodeDataIntern();
		$this->set($default_attributes);
	}
	
	public function __get($name){
		
		if( property_exists(get_class($this),$name) ){
			return $this->{$name};
		}else if( array_key_exists($name,$this->node_position) ){
			return $this->node_position[$name];
		}else if( array_key_exists($name,$this->meta_data) ){
			return $this->meta_data[$name];
		}else{
			$intern_property_value = $this->intern_data->{$name};
			if( $intern_property_value !== null ){
				return $intern_property_value;
			}
		}
		/*
		//Before PHP 5.3.0, property_exists doesn't retrieve private properties...
		else if( property_exists('ApmNodeDataIntern',$name) ){
			return $this->intern_data->{$name};
		}*/
		
		return null;
	}
	
	public function set($attributes = array()){
		foreach($attributes as $attribute=>$value){
			if( property_exists(get_class($this),$attribute) ){
				if( $attribute != 'intern_data' && $attribute != 'meta_data' && $attribute != 'node_position'){
					$this->{$attribute} = $value;
				}
			}
		}
	}
	
	public function set_node_position($node_position=array()){
		foreach($node_position as $key => $value){
			if( array_key_exists($key,$this->node_position) ){
				$this->node_position[$key] = $value;
			}
		}
	}
	
	public function set_is_folded($folded){
		$this->is_folded = $folded;
	}
	
	/**
	 * TODO: We would like it private but must be public because is called from ApmNodeDataDisplayCollection::load_multiple()...
	 * @param ApmNodeDataIntern $intern_data
	 */
	public function set_intern_data(ApmNodeDataIntern $intern_data){
		$this->intern_data = $intern_data;
	}
	
	public function set_meta_data($key,$value){
		$this->meta_data[$key] = $value;
	}
	
	public function set_marked($marked){
		$this->marked = $marked;
	}
	
	/**
	 * Loads the data from the wp entity (page, category...) attached to the node. 
	 * Intern data must be set (set_intern_data) before calling this function!
	 * 
	 * TODO: We would like it private but must be public because is called from ApmNodeDataDisplayCollection::load_multiple()...
	 * @param Object $wp_entity Wordpress post/category/... object. If not given, the entity is retrieved inside the function.
	 */
	public function load_data_from_wp_entity($wp_entity=null){
		switch($this->type){
			case 'page':
				if( empty($wp_entity) ){
					$wp_entity = get_page($this->wp_id);
				}
				$this->set_wp_data_from_post($wp_entity);
				break;
				
			/*case 'collection':
				if( empty($wp_entity) ){
					$wp_entity = get_category($this->wp_id);
				}
				$this->set_wp_data_from_category($wp_entity);
				break;*/
		}
	}

	private function set_wp_data_from_post($post){
		$this->title = $post->post_title; //get_the_title($post->ID);
		$this->url_front = get_permalink($post->ID);
		$this->url_edit = get_edit_post_link($post->ID);
		$this->publication_date = $post->post_date;
		
		//Post status:
		/*$this->status = $post->status;
		if( $post->status != 'publish' && $post->status != 'pending' ){
			$$this->status = 'draft';
		}*/
		
		//TODO: Put this status logic elsewhere...
		switch( $post->post_status ){
			case 'publish':
				$this->status = 2; //'Online';
				break;
			case 'pending':
				$this->status = 1; //'Offline (waiting for approval)';
				break;
			case 'trash':
				$this->status = 3; //'Trash';
				break;
			default:
				$this->status = 0; //'Offline';
				break;
		}
		
		//Template: TODO: we make a meta query for each post... there should be a way to
		//make only one query for all the posts '_wp_page_template' meta...
		$template_meta = get_post_meta($post->ID, '_wp_page_template', true);
		$this->template = !empty($template_meta) ? $template_meta : false;
		
	}
	
	public function load_from_wp_page($wp_page,$no_marked_infos=false,$no_wp_data=false){
		
		$loaded_apm_id = array();
		
		$id_wp = $wp_page->ID;
		
		//We load only one node here :
		$intern_data_loaded = ApmNodeDataIntern::load_multiple_from_wp_ids(array($id_wp));
		
		if( empty($intern_data_loaded['nodes_data']) ){
			return;
		}
		
		$loaded_apm_id = array_pop(array_keys($intern_data_loaded['nodes_data']));
		$intern_data = array_pop($intern_data_loaded['nodes_data']);

		$this->set_intern_data($intern_data);
		
		if( !$no_wp_data ){
			$this->load_data_from_wp_entity($wp_page);
		}
		
		if( !$no_marked_infos && ApmAddons::addon_is_on('flagged_pages') ){
			$marked_infos = new ApmMarkedNodes();
			$this->set_marked($marked_infos->get_node_mark($loaded_apm_id));
		}
		
		return $loaded_apm_id;		
	}
	
	public static function get_multiple_from_wp_pages($wp_pages,$no_marked_infos=false,$no_wp_data=false){
		
		$nodes_data = array();
		
		if( !empty($wp_pages) ){
		
			$ids_wp = array();
			$indexed_wp_pages = array();
			foreach($wp_pages as $page){
				$ids_wp[] = $page->ID;
				$indexed_wp_pages[$page->ID] = $page;
			}
			
			$intern_data_loaded = ApmNodeDataIntern::load_multiple_from_wp_ids($ids_wp);
	
			$loaded_nodes_data = $intern_data_loaded['nodes_data'];
			$ids_wp = $intern_data_loaded['ids_wp']['page'];
	
			if( empty($loaded_nodes_data) ){
				return;
			}
			
			$marked_infos = !$no_marked_infos && ApmAddons::addon_is_on('flagged_pages') ? new ApmMarkedNodes() : null;
			
			foreach($loaded_nodes_data as $apm_id => $intern_data){
				$display_data = new ApmNodeDataDisplay();
				
				$display_data->set_intern_data($intern_data);
				
				if( !$no_wp_data ){
					$display_data->load_data_from_wp_entity($indexed_wp_pages[$intern_data->wp_id]);
				}
				
				if( !$no_marked_infos ){
					$display_data->set_marked($marked_infos->get_node_mark($apm_id));
				}
				
				$nodes_data[$intern_data->wp_id] = $display_data;
			}
			
		}
		
		return $nodes_data;
	}
	
	public function convert_positions_infos_to_wp_ids(){
		$apm_ids = array();

		if( !empty($this->node_position['children']) ){
			$apm_ids = array_merge($apm_ids,$this->node_position['children']);
		}
		
		if( !empty($this->node_position['all_siblings']) ){
			$apm_ids = array_merge($apm_ids,$this->node_position['all_siblings']);
		}
		
		if( $this->node_position['parent'] != ApmTreeData::root_id ){
			$apm_ids[] = $this->node_position['parent'];
		}
		
		$wp_ids = ApmNodeDataIntern::get_wp_ids(array_unique($apm_ids),true);
		
		if( $this->node_position['parent'] !== false ){
			$this->node_position['parent'] = array_key_exists($this->node_position['parent'],$wp_ids) ? $wp_ids[$this->node_position['parent']] : 0;
		}
		
		foreach($this->node_position['children'] as $k => $apm_id){
			$this->node_position['children'][$k] = array_key_exists($apm_id,$wp_ids) ? $wp_ids[$apm_id] : 0;
		}
		
		foreach($this->node_position['all_siblings'] as $k => $apm_id){
			$this->node_position['all_siblings'][$k] = array_key_exists($apm_id,$wp_ids) ? $wp_ids[$apm_id] : 0;
		}
		
		foreach($this->node_position['siblings_no_current'] as $k => $apm_id){
			$this->node_position['siblings_no_current'][$k] = array_key_exists($apm_id,$wp_ids) ? $wp_ids[$apm_id] : 0;
		}
		
		foreach($this->node_position['siblings_before'] as $k => $apm_id){
			$this->node_position['siblings_before'][$k] = array_key_exists($apm_id,$wp_ids) ? $wp_ids[$apm_id] : 0;
		}
		
		foreach($this->node_position['siblings_after'] as $k => $apm_id){
			$this->node_position['siblings_after'][$k] = array_key_exists($apm_id,$wp_ids) ? $wp_ids[$apm_id] : 0;
		}
		
		$this->node_position['previous_sibling'] = $this->node_position['previous_sibling'] !== false && array_key_exists($this->node_position['previous_sibling'],$wp_ids) ? $wp_ids[$this->node_position['previous_sibling']] : 0;
		$this->node_position['next_sibling'] = $this->node_position['next_sibling'] !== false && array_key_exists($this->node_position['next_sibling'],$wp_ids) ? $wp_ids[$this->node_position['next_sibling']] : 0;
		
	}
	
	private function set_wp_data_from_category($category){
		$this->title = $category->name; 
		$this->url_front = get_category_link($category->term_id);
		$this->url_edit = '';
	}
	
	public function get_flattened(){
		$attributes = get_object_vars($this);
		$flat_attributes = array();
		foreach($attributes as $attribute=>$value){
			if( $attribute == 'intern_data' ){
				$flat_attributes['apm_id'] = $value->apm_id;
				$flat_attributes['type'] = $value->type;
				$flat_attributes['wp_id'] = $value->wp_id;
			}else if( $attribute == 'meta_data' ){
				
			}else if( $attribute == 'node_position' ){
				foreach( $value as $k=>$v ){
					$flat_attributes[$k] = $v;
				}
			}else{
				$flat_attributes[$attribute] = $value;
			}
		}	
		return $flat_attributes;	
	}
	
	/**
	 * TODO : see how to make a multiple update in ONE query when updating more than one node.
	 * For now, we do one update query for each node.
	 * We should just update the object property here and save all at once at the end...
	 */
	public function update_property($property,$value){
		if( $this->type == 'page' ){
			switch($property){
				case 'node_title':
					wp_update_post(array('ID'=>$this->wp_id,'post_title'=>$value));
					$this->title = $value;
					break;
				case 'node_template':
					$page_template = $value;
					self::set_page_template($this->wp_id,$page_template);
					$this->template = $page_template;
					break;
				case 'node_marked':
					if( ApmAddons::addon_is_on('flagged_pages') ){
						ApmMarkedNodes::mark_user_nodes($this->apm_id,$value);
						$this->set_marked($value);
					}
					break;
			}
		}
	}
	
	public function update_status($status){
		
		if( array_key_exists($status, ApmConstants::$wp_status_map) ){
			
			$post_status = ApmConstants::$wp_status_map[$status];

			if( $this->type == 'page' ){
						
				switch( $status ){
					case 0: 
					case 1:
						wp_update_post(array('ID'=>$this->wp_id,
					 				 		 'post_status'=>$post_status)
			   				   );
						break;
					case 2:
						wp_publish_post($this->wp_id);
						break;
				}
				
				$this->status = $status;
				
				//URL changes according to post status -> reload it :
				$this->url_front = get_permalink($this->wp_id);
			}
		}
		
	}
	
	/**
	 * Sets a page template
	 * @param int $wp_id
	 * @param string $page_template
	 */
	public static function set_page_template($page_wp_id,$page_template){
		//Inspired from wp-includes/post.php template setting
		if( !function_exists('get_page_templates') ){
			require( ABSPATH .'/wp-admin/includes/theme.php' );
		}
		$page_templates = get_page_templates();
		if( $page_template != 'default' && !in_array($page_template, $page_templates) ){
			//'The page template is invalid.'
		}else{
			update_post_meta($page_wp_id, '_wp_page_template',  $page_template);
		}
	}
	
}

class ApmNodeDataDisplayCollection{
	
	/**
	 * Array of ApmNodeDataDisplay Instances
	 * @var array
	 */
	private $nodes_data = array();
	
	/**
	 * Instance of ApmMarkedNodes
	 * @var object
	 */
	private $marked_infos = null;
	
	public function __construct(){
		$this->marked_infos = ApmAddons::addon_is_on('flagged_pages') ? new ApmMarkedNodes() : null;
	}
	
	public function get_array(){
		return $this->nodes_data;
	}
	
	public function is_empty(){
		return empty($this->nodes_data);
	}
	
	public function get($apm_id){
		return array_key_exists($apm_id,$this->nodes_data) ? $this->nodes_data[$apm_id] : null;
	}
	
	public function has_node($node_intern_id){
		return array_key_exists($node_intern_id,$this->nodes_data);
	}
	
	/**
	 * We don't load wp posts data here because we do it in "load_multiple", 
	 * so that we make only one "get_posts" and not several "get_post". 
	 * 
	 * @param integer $intern_id
	 * @param string $type
	 * @param integer $wp_id
	 */
	public function add($apm_id,$type,$wp_id,$no_wp_data=false){
		$new_node_data =  new ApmNodeDataDisplay();
		
		$new_node_data->set_intern_data(new ApmNodeDataIntern(array('apm_id'=>$apm_id,
						  										     'type'=>$type,
																     'wp_id'=>$wp_id)));
		
		if( !$no_wp_data ){
			$new_node_data->load_data_from_wp_entity();
		}

		$this->nodes_data[$apm_id] = $new_node_data;
	}
	
	public function delete($nodes_to_delete){
		if( is_numeric($nodes_to_delete) ){
			$nodes_to_delete = array($nodes_to_delete);
		}
		
		foreach($nodes_to_delete as $k=>$node){
			if( !array_key_exists($node,$this->nodes_data) ){
				unset($nodes_to_delete[$k]);
			}else{
				unset($this->nodes_data[$node]);
			}
		}
		
		if( ApmAddons::addon_is_on('flagged_pages') ){
			ApmMarkedNodes::delete_multiple($nodes_to_delete);
		}
	}
	
	public function get_node_apm_id_by_wp_id($wp_id){
		$found_apm_id = false; //0 is the root!
		foreach($this->nodes_data as $apm_id=>$node_data){
			if( $node_data->wp_id == $wp_id ){
				$found_apm_id = $apm_id;
				break;
			}
		}
		return $found_apm_id;
	}

	/**
	 * Loads Intern data, Worpdress Posts data and Maked nodes data for each node given in $apm_ids.
	 */
	public function load_multiple($apm_ids,$no_wp_data=false,$no_marked_infos=false,$order_matters=false){
		
		$this->nodes_data = array();
		
		$intern_data_loaded = ApmNodeDataIntern::load_multiple($apm_ids,$order_matters);

		$nodes_data = $intern_data_loaded['nodes_data'];
		$ids_wp = $intern_data_loaded['ids_wp']['page'];

		if( empty($nodes_data) ){
			return;
		}
		
		foreach($nodes_data as $apm_id => $intern_data){
			$display_data = new ApmNodeDataDisplay();
			
			$display_data->set_intern_data($intern_data);
						
			$this->nodes_data[$apm_id] = $display_data;
		}
		
		if( !$no_wp_data && !empty($ids_wp) ){
			$this->load_wp_data($ids_wp);
		}
		
		if( !$no_marked_infos ){
			$this->load_marked_infos();
		}
	}
	
	/**
	 * self::load_multiple() must have been called before that because it loads
	 * wp data from wp_ids found in $this->nodes_data.
	 */
	public function load_wp_data($ids_wp = array(),$posts_already_loaded=array()){
		
		if( !empty($this->nodes_data) ){
			
			if( empty($ids_wp) ){
				$ids_wp = $this->get_wp_ids();
			}
			
			$posts = empty($posts_already_loaded) ? array() : $posts_already_loaded;
			$pages_found = array();
			
			if( empty($posts_already_loaded) ){
			
				//TODO: coma separated or 'any' doesn't seem to work here for post_status (also it should according 
				//to http://codex.wordpress.org/Template_Tags/get_posts...)
				$posts = get_pages(array('include'=>array_values($ids_wp),'post_type'=>'page','post_status'=>'publish'));
				$posts_draft = get_pages(array('include'=>array_values($ids_wp),'post_type'=>'page','post_status'=>'draft'));
				$posts_pending = get_pages(array('include'=>array_values($ids_wp),'post_type'=>'page','post_status'=>'pending'));
				$posts_trash = get_pages(array('include'=>array_values($ids_wp),'post_type'=>'page','post_status'=>'trash'));
				$posts = array_merge($posts,$posts_draft,$posts_pending,$posts_trash);
				
				// Tried to replace by only one query instead of 4 get_pages() : 
				// BUT IT TAKES MORE TIME TO COMPUTE (average 800ms more than separated get_pages() for 500 pages...)
				//global $wpdb;
				//$posts = $wpdb->get_results($wpdb->prepare("SELECT * from $wpdb->posts WHERE post_type = 'page' AND ID IN ('". implode("','",$ids_wp) ."')"));
			
			}
			
			foreach($posts as $k=>$post){
				$pages_found[$post->ID] = $post;
			}
				
			foreach($this->nodes_data as $apm_id => $node){
				switch( $node->type ){
					case 'root':
						$this->nodes_data[$apm_id]->set(array('title'=>'Root'));
						break;
					case 'page':
						$wp_id = $node->wp_id;
						if( !empty($posts) && !empty($wp_id) && array_key_exists($wp_id,$pages_found) ){
							$this->nodes_data[$apm_id]->load_data_from_wp_entity($pages_found[$wp_id]);
						}
						break;
				}
			}
				
		}
	}
	
	public function load_one_from_wp_page($wp_page,$no_marked_infos=false,$no_wp_data=false,$append=false){
		$loaded_apm_id = $this->load_multiple_from_wp_pages(array($wp_page),$no_marked_infos,false,$no_wp_data,$append);
		return array_pop($loaded_apm_id);
	}
	
	public function load_multiple_from_wp_pages($wp_pages,$no_marked_infos=false,$order_matters=true,$no_wp_data=false,$append=false){
		
		$loaded_apm_ids = array();
		
		if( !$append ){
			$this->nodes_data = array();
		}
		
		$ids_wp = array();
		foreach($wp_pages as $page){
			$ids_wp[] = $page->ID;
		}
		
		$intern_data_loaded = ApmNodeDataIntern::load_multiple_from_wp_ids($ids_wp,$order_matters);

		$nodes_data = $intern_data_loaded['nodes_data'];
		$ids_wp = $intern_data_loaded['ids_wp']['page'];

		if( empty($nodes_data) ){
			return;
		}
		
		foreach($nodes_data as $apm_id => $intern_data){
			$display_data = new ApmNodeDataDisplay();
			
			$display_data->set_intern_data($intern_data);
			
			$this->nodes_data[$apm_id] = $display_data;
			
			$loaded_apm_ids[] = $apm_id;
		}
		
		if( !$no_wp_data ){
			$this->load_wp_data($ids_wp,$wp_pages);
		}
		
		if( !$no_marked_infos ){
			$this->load_marked_infos();
		}
		
		return $loaded_apm_ids;		
	}
	
	public function load_marked_infos(){
		if( $this->marked_infos !== null ){
			$marked_nodes = $this->marked_infos->get_marked_nodes($this->get_apm_ids());
			foreach($marked_nodes as $apm_id => $mark){
				$this->nodes_data[$apm_id]->set_marked($mark);
			}
		}
	}
	
	public function load_one($apm_id){
		$this->load_multiple(array($apm_id));
	}
	
	public static function get_wp_ids_from_apm_ids($apm_ids){
		return ApmNodeDataIntern::get_wp_ids($apm_ids);
	}
	
	public function get_wp_ids(){
		$wp_ids = array();
		if( !empty($this->nodes_data) ){
			foreach($this->nodes_data as $apm_id => $data){
				$wp_ids[$apm_id] = $data->wp_id;
			}
		}
		return $wp_ids;
	}
	
	public function get_apm_ids(){
		return array_keys($this->nodes_data);
	}
	
	public function update_nodes_property($property,$value,$nodes_to_update=array()){
		if( empty($nodes_to_update) ){
			foreach( $this->nodes_data as $apm_id => $node ){
				$this->nodes_data[$apm_id]->update_property($property,$value);
			}
		}else{
			foreach($nodes_to_update as $node_apm_id){
				$this->nodes_data[$node_apm_id]->update_property($property,$value);
			}
		}
	}
	
	public function update_nodes_status($status,$nodes_to_update=array()){
		if( empty($nodes_to_update) ){
			foreach( $this->nodes_data as $apm_id => $node ){
				$this->nodes_data[$apm_id]->update_status($status);
			}
		}else{
			foreach($nodes_to_update as $node_apm_id){
				$this->nodes_data[$node_apm_id]->update_status($status);
			}
		}
	}
	
	public function set_nodes_positions_from_last_tree(){
		$tree_raw = ApmTreeDb::get_last_tree();
		if( !empty($tree_raw) ){
			$tree = new ApmTree($tree_raw);
			$tree_infos = $tree->get_nodes_tree_infos(array_keys($this->nodes_data));
			foreach( array_keys($this->nodes_data) as $apm_id ){
				$this->nodes_data[$apm_id]->set_node_position($tree_infos[$apm_id]);
			}
		}
	}
}

?>
