jQuery().ready(function(){

	var $ = jQuery;
	var _type = 'tree';
	var _filter = 'online'; // online by default

	$.apm_common = {
		selected_rows: [],

		switch_type:function(type, filter, filter_data, go_to_node){
			
			if( _type == 'tree' ){
				$.apm_browse.delete_moving_overlays();
			}
			
			_type = type;
			_filter = filter;
			$.apm_common.selected_rows = [];

			if( filter != 'search' ){
				$('input#post-search-input').val('');
				$('.result-search-info').hide();
			}

			$.apm_common.close_column_panel();

			$('input[type=checkbox].select-all').removeAttr('checked');

			if( type == 'tree' ){	// Don't use filter for tree
				$('.wp-list-table').removeClass('display-list');
				$('.column-date').hide();
				$('.subsubsub li a').removeClass("current");
				$('a#action_tree').addClass("current");
				$.apm_browse.init(go_to_node);
				$('th').removeClass('desc').removeClass('asc');
			}else{
				$('.wp-list-table').addClass('display-list');
				$('.column-date').show();
				$('th').addClass('desc');
				$.apm_list.init(_filter,filter_data,go_to_node);
			}

		},

		init: function(go_to_node) {
			// Nav
			$('.subsubsub li').bind('click', function() {
				$('.subsubsub li a').removeClass("current");
				$(this).children().addClass("current");
			});

			$('.add-page-end').bind('click', function() {
				$.apm_common.close_column_panel();
				return false;
			});
			
			// Only panel change template and end button
			$('.panel-change-template input.add-page-end').bind('click', function() {
				// uncheck all nodes checked
				$.apm_common.unselect_all_rows();
			})

			$('input[type=checkbox].select-all').unbind().bind('click', function(){
				var select = $(this).attr('checked') == 'checked' ? true : false;
				if( select ){
					// @todo implements a little ajax wheel
					$('input[type=checkbox].select-all').attr('checked','checked');
					if( _type == 'tree' ){
						$.apm_tree.get_tree_nodes(0,function(ajax_answer){
							if( ajax_answer.ok == '1'  ){
								$.each(ajax_answer.tree_nodes,function(index,value){
									if( value != 0 ){ //Don't select root!
										$.apm_common.check_row(value,true);
									}
								});
								// @todo hide a little ajax wheel
							}
						});
					}else{
						$.apm_tree.get_all_current_list_nodes(function(ajax_answer){
							if( ajax_answer.ok == '1'  ){
								$.each(ajax_answer.list_nodes,function(index,value){
									$.apm_common.check_row(value,true);
								});
								// @todo hide a little ajax wheel
							}
						});
					}
				}else{
					$('input[type=checkbox].select-all').removeAttr('checked');
					$.apm_common.unselect_all_rows();
				}
			});

			var switch_type_tree = function() {$.apm_common.switch_type('tree');};
			var switch_type_list_online = function() {$.apm_common.switch_type('list', 'online');};
			var switch_type_list_offline = function() {$.apm_common.switch_type('list', 'offline');};
			var switch_type_list_recent = function() {$.apm_common.switch_type('list', 'recent');}
			var switch_type_list_marked = function() {$.apm_common.switch_type('list', 'marked');}

			$('#action_tree').unbind().bind('click', switch_type_tree);
			$('#action_publish').unbind().bind('click', switch_type_list_online );
			$('#action_unpublish').unbind().bind('click', switch_type_list_offline );
			$('#action_recent').unbind().bind('click', switch_type_list_recent );
			$('#action_all_marked').unbind().bind('click', switch_type_list_marked );
			$('#display-actions-all').unbind().bind('click', $.apm_common.change_check_action );
			$('#search-submit').unbind().bind('click', $.apm_list.search);
			$('#post-search-input').unbind().bind('keypress', function(e){
				if( e.which == 13 ){ //Submit search when hitting enter
					$('#search-submit').click();
					e.preventDefault();
					return false;
				}
			});

			$('#apm-action-all-tagged').unbind().bind('click', $.apm_common.tagged_group_action );
			$('#apm-action-all-publish').unbind().bind('click', $.apm_common.publish_group_action );
			$('#apm-action-all-unpublish').unbind().bind('click', $.apm_common.unpublish_group_action );
			$('#apm-action-all-models').unbind().bind('click', $.apm_common.change_template_group_action);
			$('#apm-action-all-delete').unbind().bind('click', $.apm_common.delete_group_action);

			$('#apm-action-all-untagged').unbind().bind('click', $.apm_list.untag_all_pages);
			$('#apm-action-all-untagged-browse').unbind().bind('click', $.apm_common.untagged_group_action);
			$('#apm-action-all-untagged-grouped').unbind().bind('click', $.apm_list.all_untagged);

			$('#apm-action-all-unselect').unbind().bind('click', function(){$.apm_common.unselect_all_rows(); return false});

			//Set events on window resize :
			$(window).unbind('resize',_on_window_resize).bind('resize',_on_window_resize);
		},

		init_reload: function(go_to_node) {
			$.apm_common.start_big_loader();
			$.apm_tree.reload(go_to_node);
		},

		after_every_action_callback: function(ajax_answer){
			/* Elements still exist in DOM for some Ajax actions
			 * The same event has to be attached several times.
			 * For the moment, we detach then attach the event.
			 */

			$('.tags_events').unbind().bind('click', $.apm_common.change_tags_action );
			$('tr.type-post').unbind('mouseenter', $.apm_browse.change_check_action).bind('mouseenter', $.apm_browse.change_check_action );

			$('.ckeck-sub-pages').unbind().bind('click', $.apm_browse.ckeck_sub_pages );
			$('.unckeck-sub-pages').unbind().bind('click', $.apm_browse.unckeck_sub_pages );
			$('.action-add-page').unbind().bind('click', $.apm_browse.add_pages);
			$('.action-drag').unbind().bind('click', $.apm_browse.drag_pages);
			$('.action-delete-page').unbind().bind('click', $.apm_common.delete_node_action);
			$('.check-column input[type=checkbox].row_select')
				.unbind('change', _on_select_row).bind('change', _on_select_row)
				.unbind('click', $.apm_common.update_panel_template).bind('click', $.apm_common.update_panel_template)
                .unbind('change', $.apm_common.update_panel_template).bind('change', $.apm_common.update_panel_template);

			$('.action_rename').unbind().bind('click', $.apm_common.rename_action);
			$('.action_publish').unbind().bind('click', $.apm_common.publish_action);
			$('.action_unpublish').unbind().bind('click', $.apm_common.unpublish_action);
			$('.action_change_template').unbind().bind('click', $.apm_common.change_template_action_for_one);

			$('.unfold_node').unbind().bind('click', $.apm_browse.unfold_node);
			$('.fold_node').unbind().bind('click', $.apm_browse.fold_node);

			$('#add-first-page').unbind().bind('click', $.apm_browse.add_first_page);

			$.apm_common.update_panel_template();
			$.apm_common.refresh_selected_rows();
		},

		change_tags_action: function() {
			var tags = $(this);
			var current_node = $.apm_common.get_node( $(this) );
			var mark = tags.hasClass('picto_red_tags') ? 0 : 1;

			$.apm_tree.mark_node(current_node, mark, false, function(ajax_answer) {
				if( ajax_answer.ok == '1' ){
					if($.apm_list.marked_mode == true){

						tags.closest('tr').toggle('slow', function() {
							$(this).remove();
						});
					} else {
						$.apm_common.replace_updated_node(ajax_answer);
					}
					$.apm_common.update_counters();
				}
				return false;
			});
			return false;
		},

		rename_action: function() {
			var input = $(this).closest('div').prev();
			var input_content = input.show().find('input[type="text"]');
			var link = input.prev().children();
			var html_content = link.text();
			link.hide();

			// detach all rename events
			input_content.unbind('keyup');
			input_content.next().unbind('click');

			// Rename function
			function rename(input_content) {
				if(input_content.val().length > 0) {
					input_content.parent().hide();

					link.text( input_content.val() );
					link.show();
					
					// Up to date of title page
					$.apm_tree.set_node_property(
						$.apm_common.get_node( input_content ),
						'node_title',
						input_content.val(),
						0,
						function(ajax_answer){
							$.apm_common.replace_updated_node(ajax_answer);
						}
					);
					
				}
				else {
					alert(apm_messages.required_title);
					input_content.blur();
				}

				return false;
			}

			// Factoring rename event
			input_content.val(html_content)
			.bind('keyup', function(event){
				if( 13 === event.which ) {
					rename($(this));
					return false;
				}

				return false;
			});

			// Factoring rename event (see up event)
			input_content.next().bind('click', function() {
				rename(input_content);
				return false;
			});

			$('.cross-delete').bind('click', function() {
				$(this).parent().hide();
				$(this).parent().prev().children().show();
				return false;
			});

			return false;
		},

		publish_action: function() {
			$.apm_common.set_publish_unpublish($(this), 2);
			return false;
		},

		unpublish_action: function() {
			$.apm_common.set_publish_unpublish($(this), 0);
			return false;
		},

		delete_node_action: function() {
			var current_node = $.apm_common.get_node( $(this) );

			var success_callback = function() {
				$.apm_common.init_reload();
				$.apm_common.update_counters();
			}

			var has_children = $('tr#apm-'+ current_node).find('span.picto-subpage').length > 0;

			var warning_message = has_children ? apm_messages.warning_delete_subpages : apm_messages.warning_delete_page;

			if( confirm(warning_message) ) {
				$.apm_tree.delete_multiple_nodes([current_node],success_callback);
			}

			return false;
		},

		change_template_action_for_one: function() {
			var line = $(this).closest('tr');
			$.apm_common.check_row(line.attr('id').replace('apm-',''));

			$.apm_common.change_template_action();

			return false;
		},

		change_template_action: function() {
			var new_template = '';

			$.apm_common.update_panel_template();

			$('.column-panel').show();
			$('.panel-change-template').show().prev().hide();
			$('.apm-grouped-action').hide();

			$.apm_common.update_scroll_positions(true);

			$('.template-list li a').unbind().bind('click', function() {
				$('.template-list li').css('backgroundColor', '#fff');
				$(this).parent().css('backgroundColor', '#eaf2fa');
				new_template = $(this).parent().attr('id');
				return false;
			});

			var different_template = $.apm_common.has_different_template();
			$('.template-list li').each(function(){
				if( $(this).find('a').eq(0).text() == different_template.message ){
					$('.template-list li').css('backgroundColor', '#fff');
					$(this).css('backgroundColor', '#eaf2fa');
					new_template = $(this).attr('id');
				}
			})

			$('#add-template-button').unbind().bind('click', function() {
				if( new_template.length > 0 ) {
					var current_nodes = $.apm_common.get_selected_rows();

					$.apm_common.start_big_loader();
					$.apm_tree.set_nodes_property(
						current_nodes,'node_template',
						new_template.replace('template-file-', ''),
						function(answer_ajax){
							$.apm_common.replace_updated_nodes(answer_ajax);
							$.apm_common.update_panel_template();
							$('.container-list-big-loader').hide();
						}
					);
				}
				return false;
			});

		},

		tagged_group_action: function() {
			var list_node = $.apm_common.get_selected_rows();
			if(!$.apm_common.is_checked(list_node)) return false;

			$.apm_common.start_big_loader();
			$.apm_tree.mark_nodes(list_node, 1, _update_after_tagged_untagged);
			return false;
		},

		untagged_group_action: function() {
			var list_node = $.apm_common.get_selected_rows();
			if(!$.apm_common.is_checked(list_node)) return false;

			$.apm_common.start_big_loader();
			$.apm_tree.mark_nodes(list_node, 0, _update_after_tagged_untagged);
			return false;
		},

		publish_group_action: function() {
			var list_node = $.apm_common.get_selected_rows();
			if(!$.apm_common.is_checked(list_node)) return false;

			$.apm_common.start_big_loader();
			$.apm_tree.set_nodes_status(list_node ,2 , _update_after_publish_unpublish );
			return false;
		},

		unpublish_group_action: function() {
			var list_node = $.apm_common.get_selected_rows();
			if(!$.apm_common.is_checked(list_node)) return false;

			$.apm_common.start_big_loader();
			$.apm_tree.set_nodes_status(list_node,0 , _update_after_publish_unpublish );
			return false;
		},

		change_template_group_action: function() {
			var list_node = $.apm_common.get_selected_rows();
			if(!$.apm_common.is_checked(list_node)) return false;

			$.apm_common.change_template_action();
			return false;
		},

		delete_group_action: function() {
			var list_node = $.apm_common.get_selected_rows();
			if(!$.apm_common.is_checked(list_node)) return false;

			var success_callback = function() {
				$.apm_common.init_reload();
				$.apm_common.update_counters();
				$.apm_common.unselect_all_rows();
			}

			if( list_node.length ) {
				if( confirm(list_node.length > 1 ? apm_messages.warning_delete_pages : apm_messages.warning_delete_page) ) {
					$.apm_tree.delete_multiple_nodes(list_node,success_callback);
				}
			}
			
			$.apm_common.close_column_panel();
			return false;
		},

		is_checked: function(list_node) {
			if( list_node.length === 0 ) {
				alert(apm_messages.bulk_actions_select_at_least_one_page);
				return false;
			}
			return true;
		},

		check_row: function(row_id,checked){
			if( checked == undefined ){
				checked = true;
			}

			if( checked ){
				$('#apm-' + row_id + ' input[type=checkbox]' ).attr('checked', 'checked');
			}else{
				$('#apm-' + row_id + ' input[type=checkbox]' ).removeAttr('checked');
			}

			_update_row_on_select(row_id,checked);

			$.apm_common.select_row(row_id,checked);
		},

		select_row: function(row_id,select){
			if( select == undefined ){
				select = true;
			}

			row_id = parseInt(row_id);

			var index = $.apm_common.selected_rows.indexOf(row_id);

			if( select ){
				if( index == -1 ){
					$.apm_common.selected_rows.push(row_id);
				}
			}else{
				if( index != -1 ){
					$.apm_common.selected_rows.splice(index, 1);
				}
			}

			_update_nb_selected_pages();
		},

		add_selected_row: function(row_id) {
			$.apm_common.select_row(row_id);
		},

		remove_selected_row: function(row_id) {
			$.apm_common.select_row(row_id,false);
		},

		get_selected_rows: function() {
			return $.apm_common.selected_rows;
		},

		unselect_all_rows: function(){
			$.apm_common.selected_rows = [];
			$.apm_common.refresh_selected_rows();

			$('input[type=checkbox].select-all').removeAttr('checked');

			$.apm_common.update_panel_template();
		},

		refresh_selected_rows: function (){
			var tree_body = $('#the-list')[0];
			$('input.row_select',tree_body).attr('checked',false);
			$('tr',tree_body).css('backgroundColor', '#fff');
			$.each($.apm_common.selected_rows,function(index,value){
				$.apm_common.check_row(value,true);
			});
			_update_nb_selected_pages();
		},

		change_check_action: function() {
			var apm_grouped_action_hide = function() {
				$('.apm-grouped-action').hide();
			}
			$('.apm-grouped-action').toggle();
			$('.apm-grouped-action a')
				.unbind('click', apm_grouped_action_hide)
				.bind('click', apm_grouped_action_hide);

			$('body').unbind().bind('click', function(event) {
				if (!$(event.target).closest('.apm-grouped-action').length
				&&  !$(event.target).closest('#display-actions-all').length ) {
					$('.apm-grouped-action').hide();
				};
			});
			return false;
		},

		get_node: function(item) {
			return item.closest('tr').attr('id').replace('apm-', '');
		},

		set_publish_unpublish: function(item, status) {
			var root_node = $.apm_common.get_node(item);
			var node = item.closest('tr');

			$.apm_common.start_big_loader();

			if( _type == 'tree' ){
				$.apm_tree.set_node_status(root_node, status, false, function(ajax_answer) {
					if( ajax_answer.ok == '1' ){
						$.apm_common.replace_updated_node(ajax_answer);
						$.apm_common.update_counters();
					}
					$('.container-list-big-loader').hide();
					return false;
				});
			}else{
				$.apm_tree.set_nodes_status([root_node],status,function(ajax_answer) {
					if( ajax_answer.ok == '1' ){
						if( $.apm_tree.has_filter('node_state') ){
							$.apm_common.init_reload();
						}else{
							$.apm_common.replace_updated_nodes(ajax_answer);
							$('.container-list-big-loader').hide();
						}
						$.apm_common.update_counters();
					}
					return false;
				});
			}

		},

		update_counters: function() {
			if( $.apm_list.marked_mode === true ) {
				var filters = ['marked','marked+online','marked+offline'];
				$.apm_tree.get_list_totals(filters, function(ajax_answer) {
					$('.all_marked .count').html('(' + ajax_answer.total_items['marked'] + ')');
					$('.publish .count').html('(' + ajax_answer.total_items['marked+online'] + ')');
					$('.unpublish .count').html('(' + ajax_answer.total_items['marked+offline'] + ')');
				});
			}
			else {
				$.apm_tree.get_list_totals(['all', 'marked','online','offline'], function(ajax_answer) {
					$('.tree .count').html('(' + ajax_answer.total_items.all + ')');
					$('.publish .count').html('(' + ajax_answer.total_items.online + ')');
					$('.unpublish .count').html('(' + ajax_answer.total_items.offline + ')');
					$('.tagged .count').html('(' + ajax_answer.total_items.marked + ')');
				});
			}
		},

		/* TODO : this method is defined 2 times!! */
		/*update_panel_template: function() {
			if ($('.panel-change-template').is(':visible')) {
				$('.panel-change-template').trigger('click');
			}
		},*/

		update_panel_template: function() {
			var nb_page_selected = $.apm_common.selected_rows.length;
			var elem_page_count_panel = $('.panel-count-page-checked');
			var different_template = $.apm_common.has_different_template();
			if( nb_page_selected === 1 ) {
				var title = $('tr#apm-'+ $.apm_common.selected_rows[0]).find($('.row-title')).html();
				elem_page_count_panel.html(apm_messages.template_panel_selected_single + ' : ' + title);
			}
			else if( nb_page_selected > 1 ) {
				elem_page_count_panel.html(nb_page_selected + ' ' + apm_messages.template_panel_selected_plural);
			}

			// Todo : create class
			elem_page_count_panel.css({
				color: '#000'
			});

			if (nb_page_selected === 0){
				elem_page_count_panel.html(apm_messages.template_panel_no_selected).css('color', 'red'); // Todo : create class
			}

			$('.name-current-template').html(different_template.message);
		},

		has_different_template: function() {
			var template = null;
			var template_name = '';
			var message = '';
			var different_template = false;

			$('.check-column input:checked').each(function() {

				if( !$(this).closest('th').hasClass('column-cb') ) {

					var template_p = $(this).closest('tr').find( $('.column-tags') ).children('p');

					var tmp = template_p.attr('class').replace('template-name-', '');

					if( template !== null && template != tmp ){
                        different_template = true;

						// Bonus: Return false to break the 'each' here (we already know the templates are differents, no need to go further)
						return false;
					}

					template = tmp;
					template_name = template_p.html();
				}
			});

			if( template === null ) template = apm_messages.template_panel_no_template;

			if( different_template === true )
				message = apm_messages.template_panel_different_templates;
			else
				message = template_name;

			return {
				is_different: different_template,
				message: message
			}
		},

		start_big_loader: function() {
			$('.container-list-big-loader').css({'height': $('#the-list').height(),'width': $('#the-list').width()});
			$('.container-list-big-loader').show();
		},

		/**
		 * Use this when updating a node in a LIST, or in the tree for bulk actions.
		 * TODO : maybe make this private ?
		 */
		replace_updated_nodes: function (ajax_answer){
			if( ajax_answer.ok == '1' ){
				$.each(ajax_answer.updated_nodes, function(index, value) {
					$('tr#apm-'+value).replaceWith(ajax_answer.nodes_html[value]);
				});
				$.apm_common.after_every_action_callback(); //TODO : update only ajax_answer.updated_nodes
			}
		},

		/**
		 * Use this when updating a node in the TREE (not in a list).
		 * It handles subtrees replacements in case of cascading actions.
		 * TODO : maybe make this private ?
		 */
		replace_updated_node: function (ajax_answer){
			if( ajax_answer.ok == '1' ){
				$.each(ajax_answer.sub_tree_nodes, function(index, value) {
					if( value != ajax_answer.updated_node && $('tr#apm-'+ value).length ){
						$('tr#apm-'+ value).remove();
					}
				});

				$('tr#apm-'+ ajax_answer.updated_node).replaceWith(ajax_answer.sub_tree);

				$.apm_common.after_every_action_callback(); //TODO : update only ajax_answer.updated_nodes
			}
		},

		update_scroll_positions: function (force_refresh_top){
			var offset = _left_panels_wrapper.offset();
			_default_top = offset.top;
			_default_left = offset.left;

		    //Horizontal scroll
	    	var documentScrollLeft = $(document).scrollLeft();
	    	if (_lastScrollLeft != documentScrollLeft) {

				var new_top = $(window).scrollTop();

	  			_left_panels.css("top",new_top + 'px');
	  			_left_panels.css("left",0 + 'px');
	  			_left_panels.css("position","relative");

	  			_lastScrollLeft = documentScrollLeft;
	    	}

	    	//Vertical scroll
	    	var documentScrollTop = $(document).scrollTop();
	    	if (force_refresh_top || _lastScrollTop != documentScrollTop) {
	        	var new_left = _default_left-_lastScrollLeft;

	  			// On passe en fixe
	        	_left_panels.css("top",_default_top + 'px');
	        	_left_panels.css("left",new_left + 'px');
	        	_left_panels.css("position","fixed");

	  			_lastScrollTop = documentScrollTop;
	    	}
		},

		get_empty_load_result_html: function(){
			var html = '';

			var colspan = $('tr#page-list-headers th:visible').length;

			html += '<tr><td colspan="'+ colspan +'" id="no_page_row">';

			if( _type == 'tree' || $.apm_tree.has_filter('recent') ){
				html += '<span>'+ apm_messages.no_page +'</span>';
				html += '<a href="#" id="add-first-page" class="button">'+ apm_messages.create_first_page +'</a>';
			}else{
				var message = '';

				if( $.apm_tree.has_filter('node_state') ){
					var node_state = $.apm_tree.get_filter_value('node_state');
					if( node_state == 'online' ){
						message = apm_messages.no_online_page;
					}else{
						message = apm_messages.no_offline_page;
					}
				}else if( $.apm_tree.has_filter('marked') ){
					message = apm_messages.no_marked_page;
				}else if( $.apm_tree.has_filter('search') ){
					message = apm_messages.no_page_found;
				}

				html += '<span>'+ message +'</span>';
			}

			html += '</td></tr>';

			return html;
		},

		close_column_panel: function (){
			// Sanitize value
			$('#add-page-title').val('');
			$('#add-number-page option:selected').removeAttr('selected');
			$('.drag-container-selected').css('position', 'absolute');

			var first_elem_pos = $('.add-page-position')[0];
			$(first_elem_pos).attr('checked', 1);
			$('.add-page-title').html('');

			$('.column-panel').hide();
			
			$.apm_browse.delete_add_page_overlay();
		}

	};

	/*
	 * callback functions
	 */
	function _update_after_publish_unpublish(ajax_answer) {
		$.apm_common.replace_updated_nodes(ajax_answer);
		if( _type == 'list' && $.apm_tree.has_filter('node_state') ){
			$.apm_common.init_reload();
		}else{
			$('.container-list-big-loader').hide();
		}
		$.apm_common.update_counters();
	}

	function _update_after_tagged_untagged(ajax_answer) {
		$.apm_common.replace_updated_nodes(ajax_answer);
		if(  _type == 'list' && $.apm_tree.has_filter('marked') ){
			$.apm_common.init_reload();
		}else{
			$('.container-list-big-loader').hide();
		}
		$.apm_common.update_counters();
	}

	function _update_nb_selected_pages(){
		var nb = $.apm_common.selected_rows.length;
		$('.nb-selected-rows').text(nb);
	}

	function _on_select_row(){
		var row_id = $(this).closest('tr').attr('id').replace('apm-','');
		var select = $(this).attr('checked') == 'checked' ? true : false;
		$.apm_common.select_row(row_id,select);
		_update_row_on_select(row_id,select);
	}

	//@todo passer en toggle
	function _update_row_on_select(row_id,select){
		if( select ){
			$('#apm-'+row_id).css('backgroundColor', '#fffbcc');
		}else{
			$('#apm-'+row_id).css('backgroundColor', '#fff');
		}
	}

	function _on_window_resize(){

		//Resize "Add page" overlay :
		$('.drag-container-add-overlaying').each(function(index,element){
			var tr = $(element).closest('tr');
			$(element).css({'width' : tr.css('width') });
		});

		//Resize "Move page" overlay :
		$('.drag-container-selected-overlaying').each(function(index,element){
			var tr = $(element).closest('tr');
			$(element).css({'width' : tr.css('width') });
		});

	}

	var _default_top = 0;
	var _default_left = 0;

	var _lastScrollLeft = 0;
	var _lastScrollTop = 0;

	var _left_panels_wrapper = $("#left-panels-wrapper");
	var _left_panels = $("#left-panels");

	$(window).scroll(function() {
		$.apm_common.update_scroll_positions(false);
	});


});
