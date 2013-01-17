jQuery().ready(function(){
	var $ = jQuery;

	$.apm_browse = {
		list_edit_action: ['insert_before', 'insert_after', 'insert_child'],
		new_nodes: [],
		page_overlayed_for_add: '',
		page_overlayed_for_moving: '',
		page_overlayed_for_droping: '',
		auto_unfold_after_moving: false,
		pos_calc: 62,

		init: function(go_to_node) {
			$.apm_tree.delete_all_filters();
			$.apm_tree.config('tree',_update_after_load_tree,$.apm_common.after_every_action_callback);

			$('#apm-action-all-fold').unbind().bind('click', function(){ $.apm_browse.fold_all_nodes(); return false; });

			$.apm_common.init_reload(go_to_node);
		},

		change_check_action: function() {
			var span = $(this).find('span.view-actions');
			var status = 0;

			span.css({'display' : 'inline-block'});
			span.toggle(function() {
				$(this).next().show();
				status = 1;
			}, function() {
				$(this).next().hide();
			});

			$(this).bind('mouseleave', function() {
				span.css({'display' : 'none'});
				$(this).find('div.apm-sub-action').hide();
			});
			return false;
		},

		ckeck_sub_pages: function() {
			var root_node = $.apm_common.get_node($(this));
			$.apm_tree.get_tree_nodes(root_node, function(ajax_answer) {
				var list_sub_list_node = ajax_answer.tree_nodes;

				for( var i = 0; i < list_sub_list_node.length; i++ ) {
					if( root_node !=  list_sub_list_node[i]) {
						$.apm_common.check_row(list_sub_list_node[i]);
					}
				}
			});
			return false;
		},

		unckeck_sub_pages: function() {
			var root_node = $.apm_common.get_node($(this));
			$.apm_tree.get_tree_nodes(root_node, function(ajax_answer) {
				var list_sub_list_node = ajax_answer.tree_nodes;

				for( var i = 0; i < list_sub_list_node.length; i++ ) {
					if( root_node !=  list_sub_list_node[i]) {
						$.apm_common.check_row(list_sub_list_node[i],false);
					}
				}
			});
			return false;
		},

		add_pages: function() {
			var current_item = $(this).closest('tr');
			var root_node = $.apm_common.get_node(current_item);
			var page_title = current_item.contents().find('.row-title').html();
			var pos = current_item.offset();

			//Cancel any "move page" action that started before :
			_cancel_drag();

			$('.error-add-first-page').hide();
			$('.error-add-page').hide();
			$('.add-page-title').html(page_title);
			$('.column-panel').show();
			$('.panel-add-page').show().next().hide();
			$('#position-radio-button').show().prev().show();
			$('.add-page-position:first').attr('checked', 'checked');

			_set_add_page_overlay(current_item);

			$.apm_common.update_scroll_positions(true);

			$('#add-page-title').trigger('focus');

			$('#add-page-button').unbind().bind('click', function() {
				// Get user input
				var page_title = $('#add-page-title').val();
				var page_position = $('.add-page-position:checked').val();
				var nodes_number = Math.max(parseInt($('#add-number-page option:selected').val()),1);
				var page_template = $('#add-page-model').val();
				var nodes_data = {'node_template' : page_template};
				$('.error-add-first-page').hide();


				var error = false;

				$.apm_common.start_big_loader();
				var success_callback = _update_add_page;

				if(page_title.length == 0 || typeof( page_position ) == 'undefined') {
					$('.error-add-first-page').show();
					$('.container-list-big-loader').hide();
				}
				else {
					$.apm_browse.create_page(
						$.apm_browse.list_edit_action[page_position],
						root_node, 'page', page_title, nodes_number, nodes_data,
						success_callback
					);
				}
			});
			return false;
		},
		
		add_first_page: function() {
			$('.column-panel').show();
			$('.panel-add-page').show();
			$('#position-radio-button').hide().prev().hide();
			$('.error-add-first-page').hide();
			$('.error-add-page').hide();
			$('.add-page-title').closest('.display-count-page-checked').hide();

			$('#add-page-button').unbind().bind('click', function() {
				var page_title = $('#add-page-title').val();
				var nodes_number = Math.max(parseInt($('#add-number-page option:selected').val()),1);
				var page_template = $('#add-page-model').val();
				var nodes_data = {'node_template' : page_template};
				var root_node = 0;

				$.apm_common.start_big_loader();
				var success_callback = _update_add_page;
				if(page_title.length == 0 ) {
					$('.error-add-first-page').show();
					$('.container-list-big-loader').hide();
				}
				else {
					$.apm_browse.create_page(
						'insert_child',
						root_node, 'page', page_title, nodes_number, nodes_data,
						success_callback
					);
				}
				$.apm_common.close_column_panel();
				$('.add-page-title').closest('.display-count-page-checked').show();
			});
		},

		create_page: function(position, root_node, type, page_title, nodes_number, nodes_data, success_callback) {
				if( nodes_number > 1 ){
					$.apm_tree.add_multiple_nodes( position,
						root_node, 'page', page_title, nodes_data, nodes_number, success_callback
					);
				}
				else {
					$.apm_tree.add_new_node(
						position,
						root_node, type, page_title, nodes_data, success_callback
					);
				}
		},


		unfold_node: function(item) {

			var link = $(this);

			// functionning differently when folding is triggered
			if(item && !item.isPropagationStopped) {
			    link = item;
			}
			var current_node = $.apm_common.get_node( link );

			$.apm_common.start_big_loader();
			$.apm_tree.unfold_node(current_node, function(answer_ajax){
			    link.closest('tr').replaceWith(answer_ajax.unfolded_sub_tree);
				
			    $('.container-list-big-loader').hide();
			    $.apm_common.after_every_action_callback();
			    
			    _update_add_page_overlay(answer_ajax.unfolded_sub_tree_nodes);
			    _update_moving_overlays(answer_ajax.unfolded_sub_tree_nodes);
			    _update_drop_page_overlay(current_node);
			});
			return false;
		},

		fold_node: function(item,after_fold_callback) {

			var link = $(this);
			var isPropagationStopped = item.isPropagationStopped;
			if(item && !isPropagationStopped) {
			    link = item;
			}
			
			var current_node = $.apm_common.get_node( link );

			$.apm_common.start_big_loader();
			$.apm_tree.fold_node(current_node, function(answer_ajax){

			    for(value in answer_ajax.folded_sub_tree_nodes) {
				    if(answer_ajax.folded_sub_tree_nodes[value] != current_node) {
					    $('#apm-'+answer_ajax.folded_sub_tree_nodes[value]).remove();
				    }
			    }

			    //We replace the folded node with new data sent by the server :
			    //Warning : From here, the old node is removed from DOM !
			    link.closest('tr').replaceWith(answer_ajax.folded_sub_tree);
			    
			    var new_node_tr = $('#apm-'+ answer_ajax.folded_node);
			    
			    $.apm_common.after_every_action_callback(); 
			    //TODO : We should have a function to update only one node event 
			    //instead of reloading all nodes events!!
			    
			    _update_add_page_overlay([answer_ajax.folded_node]);
			    _update_moving_overlays([answer_ajax.folded_node]);
			    _update_drop_page_overlay(answer_ajax.folded_node);
			    
			    // functionning differently when folding is triggered
			    if( isPropagationStopped ) {
			    	$('.container-list-big-loader').hide();
			    }
			    
			    if( after_fold_callback != undefined ){
			    	after_fold_callback(new_node_tr);
			    }

			});
			return false;
		},

		fold_all_nodes: function(){
			$.apm_tree.fold_all_nodes(function(ajax_answer){
				if( ajax_answer.ok == 1 ){
					$.apm_common.init_reload();
				}
			});
		},
		
		drag_pages: function() {
			var current_item = $(this).closest('tr');
			var moving_node = $.apm_common.get_node(current_item);
			
			$.apm_browse.auto_unfold_after_moving = false;
			
			//Cancel any "add page" action that started before:
			$.apm_common.close_column_panel();

			if(current_item.find('.fold_node').length > 0) {
				$.apm_browse.auto_unfold_after_moving = true;
				var fold_node_callback = function(current_item){
					_start_moving(moving_node);
				}
				$.apm_browse.fold_node(current_item.find('.fold_node'),fold_node_callback);
			}else{
				_start_moving(moving_node);
			}

			//$('.check-column').unbind();

			return false;
		},

		delete_moving_overlays: function() {
			$('tr.type-post').unbind('mouseenter').bind('mouseenter', $.apm_browse.change_check_action );
			$('.drag-container-selected-overlaying').remove();
			
			$('#apm-'+ $.apm_browse.page_overlayed_for_moving).find('.unfold_node, .fold_node').eq(0).css({'z-index':''}); //Back to original z-index

			$.apm_browse.page_overlayed_for_moving = '';
			
			$('.drop-container-overlaying').remove();
			$.apm_browse.page_overlayed_for_droping = '';
			
			$.apm_browse.auto_unfold_after_moving = false;
		},
		
		delete_add_page_overlay: function(){
			$.apm_browse.page_overlayed_for_add = '';
			$('.drag-container-add-overlaying').remove();
		},

		scroll_to: function(element_id, new_nodes){

			$('html,body').animate({
				scrollTop: $('#'+element_id).offset().top - 40
			}, 1000, function() {
				if( new_nodes.length > 0 ) {
					for(var i = 0; i < new_nodes.length; i++ ) {
						$('#apm-'+new_nodes[i]).animate({
							backgroundColor: '#ffc9c9'
						}, 500, function() {
							for(var i = 0; i < new_nodes.length; i++ ) {
								$('#apm-'+new_nodes[i]).animate({
									backgroundColor: '#fff'
								}, 500);
							}
						});
					}
				}
				else {
					$('#'+element_id).animate({
						backgroundColor: '#ffc9c9'
					}, 500, function() {
						$('#'+element_id).animate({
							backgroundColor: '#fff'
						}, 500);
					});
				}
			});
		}
	};

	/*
	 * Callback functions
	 */
	function _update_after_load_tree(ajax_answer){
		if( ajax_answer.ok == 1 ){
			$('.container-pagination').hide(); // hide pagination, isn't usable in tree mode
			$('.container-pagination-browse').show();
			$('.only-browse').show();

			if( ajax_answer.tree.length > 0 ){
				$('#the-list').html(ajax_answer.tree); //This is our tree :-)
			}else{
				$('#the-list').html($.apm_common.get_empty_load_result_html());
			}

			$('.container-list-big-loader').hide();
			$('.manage-column').removeClass('sortable');
			$('.manage-column a').unbind().hover(function(){
				$(this).css('color', '#21759B');
			});

			if( parseInt(ajax_answer.go_to_node) != 0 ){
				$.apm_browse.scroll_to('apm-'+ ajax_answer.go_to_node, $.apm_browse.new_nodes);
			}

			$.apm_browse.new_nodes = [];

			_update_add_page_overlay();
			_update_moving_overlays();
			

		}else{
			alert(apm_messages.load_tree_error + " : " + ajax_answer.error);
		}
	}

	function _update_add_page(ajax_answer){
		if(ajax_answer.new_node) {
			$.apm_common.init_reload(ajax_answer.new_node);
			$.apm_browse.new_nodes[0] = ajax_answer.new_node;
		} else {
			$.apm_common.init_reload(ajax_answer.new_nodes[0]);
			$.apm_browse.new_nodes = ajax_answer.new_nodes;
		}

		$.apm_common.after_every_action_callback();

		$.apm_common.update_counters();

		$('.container-list-big-loader').hide();
		$('#add-page-title').val('');
		$('#add-page-model').val('default');
		$('#add-number-page').val('1');
		$('.add-page-position:first').attr('checked', 'checked');
	}

	function _set_add_page_overlay(item_to_overlay){
		var height_box = $('#' + item_to_overlay.attr('id')).height();
		var overlay_host = item_to_overlay.find('.overlay_host').eq(0);

		$.apm_browse.delete_add_page_overlay();

		$('#drag-container-add-template').clone(true).removeAttr('id').addClass('drag-container-add-overlaying').css({
			'top' : '-10px',
			'height' : height_box,
			'width' : item_to_overlay.css('width')
		}).appendTo(overlay_host).show();

		$.apm_browse.page_overlayed_for_add = item_to_overlay.attr('id').replace('apm-','');
	}

	function _update_add_page_overlay(nodes_to_check){
		if( nodes_to_check != undefined ){
			$.each(nodes_to_check,function(k,item_id){
				if( item_id == $.apm_browse.page_overlayed_for_add ){
					_set_add_page_overlay($('#apm-'+item_id));
				}
			});
		}else{
			$('#the-list tr').each(function(k,item){
				if( $(item).attr('id') != undefined ){
					item_id = $(item).attr('id').replace('apm-','');
					if( item_id == $.apm_browse.page_overlayed_for_add ){
						_set_add_page_overlay($('#apm-'+item_id));
					}
				}
			});
		}
	}
	
	function _update_moving_overlays(nodes_to_check){
		if( $.apm_browse.page_overlayed_for_moving != '' ){
			
			if( nodes_to_check != undefined ){
				
				$.each(nodes_to_check,function(k,item_id){
					
					if( item_id == $.apm_browse.page_overlayed_for_moving ){
						_update_moving_page_overlay();
					}else{
						$('#apm-'+item_id).unbind('mouseenter', _moving_row_mouse_enter).bind('mouseenter', _moving_row_mouse_enter);
					}
					
				});
				
			}else{
				$('tr.type-post').unbind('mouseenter', _moving_row_mouse_enter).bind('mouseenter', _moving_row_mouse_enter);
			}
			
		}	
	}
	
	function _update_moving_page_overlay(){
		var item_to_overlay = $('#apm-' + $.apm_browse.page_overlayed_for_moving);
		
		var height_box = item_to_overlay.height();
		var overlay_host = item_to_overlay.find('.overlay_host').eq(0);
		
		item_to_overlay.find('.unfold_node, .fold_node').eq(0).css({'z-index':'1'}); //So that we can't fold/unfold the moving node
		
		$('.drag-container-selected-overlaying').remove();

		var new_overlay = $('#drag-container-selected-template').clone(true).removeAttr('id').addClass('drag-container-selected-overlaying').css({
			'top' : '-10px',
			'height' : height_box,
			'width' : item_to_overlay.css('width')
		}).appendTo(overlay_host).show();
		
		$('a.cancel-drag',new_overlay).unbind().bind('click', function(){
			_cancel_drag();
			return false;
		});
	}

	function _set_moving_page_overlay(item_to_overlay_apm_id){
		$.apm_browse.page_overlayed_for_moving = item_to_overlay_apm_id;
		_update_moving_page_overlay();
	}

	function _set_drop_page_overlay(item_to_overlay){
		var height_box = item_to_overlay.height();
		var overlay_host = item_to_overlay.find('.overlay_host').eq(0);

		$('.drop-container-overlaying').remove();

		var new_overlay = $('#drop-container-template').clone(true).removeAttr('id').addClass('drop-container-overlaying').css({
			'top' : '-10px',
			'height' : height_box,
			'width' : item_to_overlay.css('width')
		}).appendTo(overlay_host).show();

		//Display fold/unfold controls on the overlay :
		var is_folded = item_to_overlay.find('a.unfold_node').length > 0;
		var is_unfolded = item_to_overlay.find('a.fold_node').length > 0;
		var subpages_controls = $(".apm-subpages-controls",new_overlay);
		var subpages_controls_link = $("a",subpages_controls);
		if (is_folded) { 
			// Page has subpage(s) that are folded - Display controls
			subpages_controls.css("display","inline");
			subpages_controls_link.removeClass("fold_node").addClass("unfold_node");
			subpages_controls_link.unbind().bind('click', $.apm_browse.unfold_node);

		}else if (is_unfolded) { 
			// Page has subpage(s) that are unfolded - display controls
			subpages_controls.css("display","inline");
			subpages_controls_link.removeClass("unfold_node").addClass("fold_node");
			subpages_controls_link.unbind().bind('click', $.apm_browse.fold_node);
		}else{
			// Page has no subpage - hide controls
			subpages_controls.css("display","none");
		}
		
		//Update overlay events :
		var moving_node = $.apm_browse.page_overlayed_for_moving;
		var drop_node = item_to_overlay.attr('id').replace('apm-','');
		
		var callback = function() {
			$.apm_common.init_reload(moving_node);
		};
		
		$('a.cancel-drag',new_overlay).unbind().bind('click', function(){
			_cancel_drag();
			return false;
		});
		
		$('.drop-after',new_overlay).unbind().bind('click', function() {
			$.apm_common.start_big_loader();
			$.apm_browse.delete_moving_overlays();
			$.apm_tree.edit(
				'insert_after', moving_node,
				drop_node, callback
			);
		});

		$('.drop-before',new_overlay).unbind().bind('click', function() {
			$.apm_common.start_big_loader();
			$.apm_browse.delete_moving_overlays();
			$.apm_tree.edit(
				'insert_before', moving_node,
				drop_node, callback
			);
		});

		$('.drop-sub',new_overlay).unbind().bind('click', function() {
			$.apm_common.start_big_loader();
			$.apm_browse.delete_moving_overlays();
			$.apm_tree.edit(
				'insert_child', moving_node,
				drop_node, callback
			);
		});
		
		//Store the current node overlayed for droping :
		$.apm_browse.page_overlayed_for_droping = drop_node;
	}
	
	function _update_drop_page_overlay(target_node){
		
		var moving_node = $.apm_browse.page_overlayed_for_moving;
		
		//We are not moving any page : return.
		if( moving_node == '' ){
			return;
		}
		
		//We won't drop the moving page on itself... return.
		if( target_node == moving_node ){
			return;
		}
		
		//No need to set a drop overlay to a node that already has it :
		if( target_node == $.apm_browse.page_overlayed_for_droping ){
			return;
		}
		
		var target_item = $('#apm-'+ target_node);

		//Set the drop overlay :
		_set_drop_page_overlay(target_item);

		target_item.bind('mouseleave', function(e){
			$('.drop-container-overlaying').hide();
			$.apm_browse.page_overlayed_for_droping = '';
		});
	}

	function _moving_row_mouse_enter(){
		_update_drop_page_overlay($(this).attr('id').replace('apm-',''));
	}

	function _start_moving(moving_node){

	 	$('.container-list-big-loader').hide();

	 	_set_moving_page_overlay(moving_node);
	 	
	 	_update_moving_overlays();

	}

	function _cancel_drag(){
		var node_to_unfold = $.apm_browse.page_overlayed_for_moving;
		var auto_unfold_after_moving = $.apm_browse.auto_unfold_after_moving;
		
		$.apm_browse.delete_moving_overlays(); //Empties $.apm_browse.page_overlayed_for_moving and $.apm_browse.auto_unfold_after_moving
		
		if( auto_unfold_after_moving && node_to_unfold != '' ) {
			node_to_unfold = $('#apm-'+ node_to_unfold);
			if( node_to_unfold.length ){
				$.apm_browse.unfold_node(node_to_unfold.find('.unfold_node'));
			}
		}
	}

});