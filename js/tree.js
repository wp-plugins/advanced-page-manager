
jQuery().ready(function(){
	var $ = jQuery;
	
	var _type = 'tree';
	var _tree_template = 'tree_display';
	var _after_load_callback = '';
	var _after_every_action_callback = '';
	var _actions_times = {};
	
	//'search', 'recent', 'marked', 'node_state'
	var _filters = {};
	
	//'title','marked','node_state','date','template'. Values : ASC or DESC.
	var _orders = {};
	
	var _pagination_current_page = 1;
	var _pagination_nb_per_page = 5;
	
	$.apm_tree = {
		config:function(type,after_load_callback,after_every_action_callback,tree_template){
			
			if( type != undefined ){
				_type = type;
			}
			
			if( after_every_action_callback != undefined ){
				_after_every_action_callback = after_every_action_callback;
			}
			
			if( tree_template != undefined ){
				_tree_template = tree_template;
			}else{
				_tree_template = _type == 'tree' ? 'tree_display' : 'list_display';
			}
			
			_after_load_callback = after_load_callback;
		},
		load:function(filters,orders,go_to_node){
			if( filters != undefined ){
				_filters = filters;
			}
			
			if( orders != undefined ){
				_orders = orders;
			}
			
			var params = {};
			if( go_to_node != undefined ){
				params.go_to_node = go_to_node;
			}
			
			if( _type == 'tree' ){
				_ajax_action('tree_load',params,_after_load_callback);
			}else if( _type == 'list' ){
				_ajax_action('list_load',params,_after_load_callback);
			}
		},
		reload:function(go_to_node){
			$.apm_tree.load(_filters,_orders,go_to_node);
		},
		set_filter:function(filter,value){
			_filters[filter] = value;
		},
		get_filter_value:function(filter){
			var filter_value = '';
			if( $.apm_tree.has_filter(filter) ){
				filter_value = _filters[filter];
			}
			return filter_value;
		},
		has_filter:function(filter){
			return filter in _filters;
		},
		delete_filter:function(filter){
			delete _filters[filter];
		},
		delete_all_filters:function(){
			_filters = {};
		},
		set_order:function(order,value){
			_orders[order] = value;
		},
		has_order:function(order){
			return order in _orders;
		},
		delete_order:function(order){
			delete _orders[order];
		},
		delete_all_orders:function(){
			_orders = {};
		},
		set_list_pagination:function(current_page,nb_per_page){
			_pagination_current_page = current_page;
			if( nb_per_page != undefined ){
				_pagination_nb_per_page = nb_per_page;
			}
		},
		load_sub_tree:function(root_node,success_callback){
			_ajax_action('tree_load_sub_tree',{'root_node':root_node},success_callback);
		},
		get_tree_nodes:function(root_node,success_callback){
			_ajax_action('tree_get_nodes',{'root_node':root_node},success_callback);
		},
		edit:function(edit_action,node_choice,index_node,success_callback){
			_ajax_action('tree_edit',{'edit_action':edit_action,'node_choice':node_choice,'index_node':index_node},success_callback);
		},
		move_multiple_nodes:function(edit_action,nodes_to_move,index_node,success_callback){
			_ajax_action('tree_move_multiple_nodes',{'edit_action':edit_action,'nodes_to_move':nodes_to_move,'index_node':index_node},success_callback);
		},
		add_new_node:function(edit_action,index_node,node_type,node_id,node_data,success_callback){
			_ajax_action('tree_add_new_node',{'edit_action':edit_action,'index_node':index_node,'node_type':node_type,'node_id':node_id,'node_data':node_data},success_callback);
		},
		add_multiple_nodes:function(edit_action,index_node,node_type,node_id,node_data,nodes_number,success_callback){
			_ajax_action('tree_add_multiple_nodes',{'edit_action':edit_action,'index_node':index_node,'node_type':node_type,'node_id':node_id,'node_data':node_data,'nodes_number':nodes_number},success_callback);
		},
		delete_multiple_nodes:function(nodes_to_delete,success_callback){
			_ajax_action('tree_delete_nodes',{'nodes_to_delete':nodes_to_delete},success_callback);	
		},
		reset:function(success_callback){
			_ajax_action('tree_reset',success_callback);
		},
		get_allowed:function(moving_node,edit_action,success_callback){
			_ajax_action('tree_get_allowed',{'moving_node':moving_node,'edit_action':edit_action},success_callback);
		},
		set_node_status:function(root_node,status,cascading,success_callback){
			_ajax_action('nodes_set_node_status',{'root_node':root_node,'status':status,'cascading':cascading},success_callback);
		},
		set_node_property:function(root_node,property,value,cascading,success_callback){
			_ajax_action('nodes_set_node_property',{'root_node':root_node,'property':property,'value':value,'cascading':cascading},success_callback);
		},
		set_nodes_status:function(nodes_to_update,status,success_callback){
			_ajax_action('nodes_set_nodes_status',{'nodes_to_update':nodes_to_update,'status':status},success_callback);
		},
		set_nodes_property:function(nodes_to_update,property,value,success_callback){
			_ajax_action('nodes_set_nodes_property',{'nodes_to_update':nodes_to_update,'property':property,'value':value},success_callback);
		},
		mark_node:function(root_node,mark,cascading,success_callback){
			$.apm_tree.set_node_property(root_node, 'node_marked', mark, cascading, success_callback);
		},
		mark_nodes:function(nodes_to_update,mark,success_callback){
			$.apm_tree.set_nodes_property(nodes_to_update, 'node_marked', mark, success_callback);
		},
		unmark_all_nodes:function(success_callback){
			_ajax_action('nodes_unmark_all',{},success_callback);
		},
		untrash_node:function(root_node,cascading,success_callback){
			_ajax_action('nodes_untrash',{'root_node':root_node,'cascading':cascading},success_callback);
		},
		fold_node:function(node,success_callback){
			_ajax_action('tree_fold_node',{'node_to_fold':node},success_callback);
		},
		unfold_node:function(node,success_callback){
			_ajax_action('tree_unfold_node',{'node_to_unfold':node},success_callback);
		},
		fold_all_nodes:function(success_callback){
			_ajax_action('tree_fold_all_nodes',{},success_callback);
		},
		unfold_all_nodes:function(success_callback){
			_ajax_action('tree_unfold_all_nodes',{},success_callback);
		},
		find_node:function(node,success_callback){
			_ajax_action('tree_find_node',{'node_to_find':node},success_callback);
		},
		/**
		 * Retrieves totals for 'online', 'offline' and 'marked' items. 
		 * To make the union of 2 types use for example 'marked+online'. 
		 * "types" must be an array : for example : ['marked','online','offline'] or ['marked+online','marked+offline'] 
		 */
		get_list_totals:function(types,success_callback){
			_ajax_action('list_get_total',{'types':types},success_callback);
		},
		/**
		 * Retrieves ids of all the nodes (not paginated) in the current list
		 */
		get_all_current_list_nodes:function(success_callback){
			_ajax_action('list_get_all_nodes',{},success_callback);
		}
	};
	
	function _ajax_action(action,params,success_callback){
		
		var filters_closure = _filters;
		var orders_closure = _orders;
		
		var ajax_success_callback = function(ajax_answer) {
			
			if( ajax_answer.ok == 0 && ajax_answer.error == 'no_user_logged_in' ){
				alert(apm_api_js_data.no_user_logged_in_error_msg);
				window.location.href = apm_api_js_data.login_url_redirect_to_browse_page;
				return;
			}
			
			ajax_answer.action = action;
			ajax_answer.filters = filters_closure;
			ajax_answer.orders = orders_closure;
			
			_actions_times[action].stop = new Date().getTime();
			_actions_times[action].duration = _actions_times[action].stop - _actions_times[action].start;
			
			ajax_answer.duration = _actions_times[action].duration;
			
			_ajax_success(action,ajax_answer);
			if( success_callback ){
				success_callback(ajax_answer);
			}
			
			if( _after_every_action_callback != '' ){
				_after_every_action_callback(ajax_answer);
			}
		};
		
		var s = {};
		s.type = "POST";
		s.url = apm_api_js_data.ajax_url;
		
		s.data = $.extend(s.data, { action: 'apm_tree_actions', apm_action: action, _ajax_nonce: apm_api_js_data.wp_nonce });
		if(params){
			s.data = $.extend(s.data, params);
		}		
		s.data.type = _type;
		s.data.tree_template = _tree_template;
		s.data.filters = _filters;
		s.data.orders = _orders;
		
		if( s.data.type == 'list' ){
			s.data.pagination = {'nb_per_page':_pagination_nb_per_page, 'current_page': _pagination_current_page};
		}

		s.global = false;
		s.timeout = 100000;
		s.success = ajax_success_callback;
		s.error = function(jqXHR,textStatus,errorThrown) {
			_ajax_error(action,jqXHR,textStatus,errorThrown);
		}
		
		_actions_times[action] = {start: new Date().getTime(), stop: 0, duration: 0};
		
		$.ajax(s);
		
	}
	
	function _ajax_success(action,ajax_answer){
		if( ajax_answer.echoed_before_json != undefined ){
			var before_json = ajax_answer.echoed_before_json;
			if( typeof console == "object" ){
				console.log('APM AJAX Warning : some content is echoed before JSON answer : '+
						(before_json.length > 500 ? before_json.substr(0,500)+ '...' : before_json) );
			}
		}
	}
	
	function _ajax_error(action,jqXHR,textStatus,errorThrown){
		
		if( jqXHR.readyState == 0 ){
			//This fires when we click too fast to change page while an action has been called.
			//This is of no consequence. Just return.
			return;
		}
		
		if( typeof console == "object" ){
			console.log('APM AJAX Error on '+ action +' : '+ textStatus);
			var json_answer = jqXHR.responseText;
			console.log('APM AJAX JSON answer : '+ (json_answer.length > 500 ? json_answer.substr(0,500)+ '...' : json_answer) );
			console.log('Error thrown : ', errorThrown, jqXHR);
		}
		
	}
	
});