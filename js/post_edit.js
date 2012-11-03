
jQuery().ready(function(){
	var $ = jQuery;
	
	function update_subpage_link(){
		$('a#apm_page_attributes_subpage_link').attr('href',$('select#apm_page_attributes_subpage_select').val());
	}
	
	update_subpage_link();
	
	$('#apm_page_attributes_subpage_select').change(function(){
		update_subpage_link();
	});
	
	$('a#apm_flag_page').click(function(){
		function success_callback(ajax_answer){
			if( ajax_answer.ok == 1 ){
				$('a#apm_flag_page').hide();
				$('a#apm_unflag_page').show();
			}
		}
		$.apm_tree.mark_node($(this).attr('rel'),1,0,success_callback);
		return false;
	});
	
	$('a#apm_unflag_page').click(function(){
		function success_callback(ajax_answer){
			if( ajax_answer.ok == 1 ){
				$('a#apm_unflag_page').hide();
				$('a#apm_flag_page').show();
			}
		}
		$.apm_tree.mark_node($(this).attr('rel'),0,0,success_callback);
		return false;
	});
	
});