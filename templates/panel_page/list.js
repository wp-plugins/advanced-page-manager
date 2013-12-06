jQuery().ready(function(){
	var $ = jQuery;

	$.apm_list = {
		pagination: {current_page:1,total_pages:0, item_per_page:20},
		marked_mode: false,
		init:function( filter, filter_data, go_to_node){

			$.apm_list.pagination.current_page = 1;
			$.apm_tree.config('list',_update_after_load_list,$.apm_common.after_every_action_callback);

			$.apm_tree.delete_filter('search');
			$.apm_tree.delete_filter('node_state');
			$.apm_tree.delete_filter('recent');

			// When in marked page list, we keep the filter
			if($.apm_list.marked_mode === false) {
				$.apm_tree.delete_filter('marked');
			}

			if( filter == 'search' ){
				$('.subsubsub li a').removeClass("current");
			}

			$.apm_tree.delete_all_orders();
			$('.manage-column').removeClass('sorted').addClass('sortable');

			switch( filter ) {
				case 'search'  :
					if( filter_data != undefined ){
						$.apm_tree.set_filter('search',filter_data);
						//Don't delete 'search' filter here, even if 'filter_data' is empty,
						//or we may have no filter at all, which raises an error...
					}
					break;
				case 'online'  :$.apm_tree.set_filter('node_state','online');break;
				case 'offline' :$.apm_tree.set_filter('node_state','offline');break;
				case 'tagged'  :
					$.apm_tree.set_filter('marked','yes');
					$.apm_tree.set_order('title', 'ASC');
					$.apm_list.marked_mode = true;
				break;
				case 'recent'  :$.apm_tree.set_filter('recent','');break;
			}

			$.apm_tree.set_list_pagination(
				$.apm_list.pagination.current_page,
				$.apm_list.pagination.item_per_page
			);

			$('.column-pages a').unbind().bind('click', $.apm_list.sort_page);
			$('.column-tag a').unbind().bind('click', $.apm_list.sort_mark);
			$('.column-etat a').unbind().bind('click', $.apm_list.sort_state);
			$('.column-models a').unbind().bind('click', $.apm_list.sort_models);
			$('.column-date a').unbind().bind('click', $.apm_list.sort_date);
			
			$('a.custom-sortable').unbind().bind('click', $.apm_list.sort_custom);

			$.apm_common.init_reload(go_to_node);
		},

		search: function() {
			$('.column-date').show();
			var content_search = $('#post-search-input').val();
			if( content_search.length > 0 ) {
				$('.result-search-info strong').html('&laquo;&nbsp;'+ content_search +'&nbsp;&raquo;');
				$('.result-search-info').show();
			}else{
				$('.result-search-info strong').html('');
				$('.result-search-info').hide();
			}
			$.apm_common.switch_type('list','search',$.trim(content_search));
		},

		sort_page: function() {
			$.apm_list.sort('title', '.column-pages');
			return false;
		},

		sort_mark: function() {
			$.apm_list.sort('marked', '.column-tag');
			return false;
		},

		sort_state: function() {
			$.apm_list.sort('node_state', '.column-etat');
			return false;
		},

		sort_models: function() {
			$.apm_list.sort('template', '.column-models');
			return false;
		},

		sort_date: function() {
			$.apm_list.sort('date', '.column-date');
			return false;
		},

		sort_custom: function() {
			var th = $(this).closest('th.manage-column');
			var classes = th.attr('class');
			
			classes = classes.replace('manage-column','');
			classes = classes.replace('desc','');
			classes = classes.replace('asc','');
			classes = classes.replace('sortable','');
			classes = classes.replace('sorted','');
			classes = classes.replace(/^\s+|\s+$/g, ''); //trim
			
			var column = classes.replace('column-','');
			
			$.apm_list.sort(column, '.'+ classes);
			
			return false;
		},

		/*
		 * Allows to sort, 2 parameters :
		 * @param type : column to be sorted
		 * @param itemClass : column class name to update display
		 */
		sort: function(type, itemClass) {
			$.apm_tree.delete_all_orders();
			$('.manage-column').removeClass('sorted').addClass('sortable');
			if($(itemClass).hasClass('desc')) {
				$.apm_tree.set_order(type,'ASC');
				$(itemClass).removeClass('desc').addClass('asc');
			}
			else {
				$.apm_tree.set_order(type,'DESC');
				$(itemClass).removeClass('asc').addClass('desc');
			}
			$(itemClass).removeClass('sortable').addClass('sorted');
			$.apm_common.init_reload();
		},

		// Unmark all elements
		all_untagged: function() {
			var elem_checked = $.apm_common.get_selected_rows();
			$.apm_common.start_big_loader();
			$.apm_tree.mark_nodes(elem_checked, 0, _update_after_untagged);
		},

		untag_all_pages: function() {
			$.apm_tree.unmark_all_nodes(function(ajax_answer){
				if( ajax_answer.ok == 1 ){
					$.apm_common.init_reload();
					$.apm_common.update_counters();
				}
			});
		},

		// Reset filters
		// TODO: use delete_all_filter instead
		reset_filter: function() {
			$.apm_tree.delete_filter('node_state');
			$.apm_tree.delete_filter('marked');
		}
	}

	/**
	 * Callback passed to $.apm_tree.load_list() : displayes the tree and updates all nodes JS events.
	 */
	function _update_after_load_list(ajax_answer){

		if( ajax_answer.ok == 1 ){
			// up to date pagination
			_init_pagination_actions(ajax_answer);
			_update_current_page($.apm_list.pagination.current_page);
			_update_pagination(
				ajax_answer.total_pages,
				ajax_answer.total_items
			);

			$('.manage-column a').hover(function(){
				$(this).css('color', '#D54E21');
			}, function() {
				$(this).css('color', '#21759B');
			});

			if( ajax_answer.list.length > 0 ){
				$('#the-list').html(ajax_answer.list); //This is our list :-)
			}else{
				$('#the-list').html($.apm_common.get_empty_load_result_html());
			}

			$('.container-list-big-loader').hide();
		}else{
			alert("Error : couldn't load list : "+ ajax_answer.error);
		}
	}

	// up to date pagination
	function _update_pagination(total_pages, total_items){
		$('.pagination-total-items').html(total_items);
		$('.pagination-total-pages').html(total_pages);
		$('.container-pagination-browse').hide();
		$('.only-browse').hide();
		$('.container-pagination').show();
	}

	function _update_current_page(current_page) {
		$('.pagination-current-page').val(current_page);
	}

	// Manage pagination actions
	function _init_pagination_actions(ajax_answer) {

		if( ajax_answer.list.length == 0  ){

			$('.pagination-wrapper').hide();

		}else{

			var local_ajax_answer = ajax_answer;

			$('.pagination-wrapper').show();

			$('.pagination-first-page a').unbind().bind('click', function() {
				$.apm_list.pagination.current_page = 1;
				$.apm_tree.set_list_pagination(
					$.apm_list.pagination.current_page,
					$.apm_list.pagination.item_per_page
				);

				_update_current_page($.apm_list.pagination.current_page);
				$.apm_common.init_reload();
				
				return false;
			});

			$('.pagination-last-page a').unbind().bind('click', function() {
				$.apm_list.pagination.current_page = local_ajax_answer.total_pages;
				$.apm_tree.set_list_pagination(
					$.apm_list.pagination.current_page,
					$.apm_list.pagination.item_per_page
				);

				_update_current_page($.apm_list.pagination.current_page);
				$.apm_common.init_reload();
				
				return false;
			});

			$('.pagination-next a').unbind().bind('click', function() {
				if( $.apm_list.pagination.current_page < local_ajax_answer.total_pages )
					$.apm_list.pagination.current_page++;

				$.apm_tree.set_list_pagination(
					$.apm_list.pagination.current_page,
					$.apm_list.pagination.item_per_page
				);

				_update_current_page($.apm_list.pagination.current_page);
				$.apm_common.init_reload();
				
				return false;
			});

			$('.pagination-preview a').unbind().bind('click', function(){
				if( $.apm_list.pagination.current_page > 1 )
					$.apm_list.pagination.current_page--;

				$.apm_tree.set_list_pagination(
					$.apm_list.pagination.current_page,
					$.apm_list.pagination.item_per_page
				);

				_update_current_page($.apm_list.pagination.current_page);
				$.apm_common.init_reload();
				
				return false;
			});

			$('.pagination-current-page').unbind().bind('keyup', function()  {
				if( 13 === event.which ) {
					var current_page = $.apm_list.pagination.current_page;
					var go_page = parseInt( $(this).val() );
					if( go_page < local_ajax_answer.total_pages && go_page >= 1 ) {
						$.apm_list.pagination.current_page = go_page;
					}

					$.apm_tree.set_list_pagination(
						$.apm_list.pagination.current_page,
						$.apm_list.pagination.item_per_page
					);

					_update_current_page($.apm_list.pagination.current_page);
					$.apm_common.init_reload();

				}
			});
		}
	}

	function _update_after_untagged(ajax_answer) {
		$.apm_common.init_reload();
		$('.container-list-big-loader').hide();
		$.apm_common.update_counters();
	}

});