<?php

require_once(dirname(__FILE__) .'/functions.php');

/**
 * Retrieves parent page of given page
 * !! Will retrieve it even if not published !!
 * Specs : apm_get_page_path() avec gestion de la remontée ?
 */
function apm_get_parent_page($page_id=0){
	$parent_page = null;
	
	$page_position = Apm::get_page_tree_positions($page_id);
	if( !empty($page_position->parent) ){
		$parent_page = Apm::get_page($page_position->parent);
	}
	
	return $parent_page;	
}

/**
 * Retrieves siblings of given page
 * Specs : Obtenir la liste des pages de même niveau en signalant la page courante
 * TODO : voir comment signaler la page courante!
 */
function apm_get_siblings($page_id=0){
	$siblings = array();
	
	$page_position = Apm::get_page_tree_positions($page_id);
	if( !empty($page_position->all_siblings) ){
		$siblings = Apm::get_pages(array('include'=>$page_position->all_siblings,'sort_column'=>'menu_order'));
	}
	
	return $siblings;
}

/**
 * Retrieves the next published sibling of the current page.
 * Specs : Obtenir la page suivante dans la liste des pages de même niveau
 */
function apm_get_next_sibling($page_id=0){
	$next_sibling = null;
	
	$page_position = Apm::get_page_tree_positions($page_id);
	if( !empty($page_position->siblings_after) ){
		$next_sibling_id = Apm::find_published_page_id_in_list($page_position->siblings_after);
		if( !empty($next_sibling_id) ){
			$next_sibling = Apm::get_page($next_sibling_id);
		}
	}
	
	return $next_sibling;
}

/**
 * Retrieves the previous published sibling of the current page.
 * Specs : Obtenir la page précédente dans la liste des pages de même niveau
 */
function apm_get_previous_sibling($page_id=0){  
	$previous_sibling = null;
	
	$page_position = Apm::get_page_tree_positions($page_id);
	if( !empty($page_position->siblings_before) ){
		$previous_sibling_id = Apm::find_published_page_id_in_list($page_position->siblings_before,false);
		if( !empty($previous_sibling_id) ){
			$previous_sibling = Apm::get_page($previous_sibling_id);
		}
	}
	
	return $previous_sibling;
}

/**
 * Retrieves the first published sibling of the current page.
 */
function apm_get_first_sibling($page_id=0){
	$first_sibling = null;
	
	$page_position = Apm::get_page_tree_positions($page_id);
	if( !empty($page_position->all_siblings) ){
		$first_sibling_id = Apm::find_published_page_id_in_list($page_position->all_siblings);
		if( !empty($first_sibling_id) ){
			$first_sibling = Apm::get_page($first_sibling_id);
		}
	}
	
	return $first_sibling;
}

/**
 * Retrieves the last published sibling of the current page.
 */
function apm_get_last_sibling($page_id=0){
	$last_sibling = null;
	
	$page_position = Apm::get_page_tree_positions($page_id);
	if( !empty($page_position->all_siblings) ){
		$last_sibling_id = Apm::find_published_page_id_in_list($page_position->all_siblings,false);
		if( !empty($last_sibling_id) ){
			$last_sibling = Apm::get_page($last_sibling_id);
		}
	}
	
	return $last_sibling;
}

/**
 * Retrieves all published subpages of the current page.
 * Specs : Obtenir une liste de sous-pages sur un niveau en fournissant l’ID de la page parente
 */
function apm_get_subpages($page_id=0){
	$subpages = array();
	
	$page_position = Apm::get_page_tree_positions($page_id);
	if( !empty($page_position->children) ){
		$subpages = Apm::get_pages(array('include'=>$page_position->children,'sort_column'=>'menu_order'));
	}
	
	return $subpages;
}

/**
 * Retrieves the first published subpage of the current page.
 */
function apm_get_first_subpage($page_id=0){
	$first_subpage = null;
	
	$page_position = Apm::get_page_tree_positions($page_id);
	if( !empty($page_position->children) ){
		$first_subpage_id = Apm::find_published_page_id_in_list($page_position->children);
		if( !empty($first_subpage_id) ){
			$first_subpage = Apm::get_page($first_subpage_id);
		}
	}
	
	return $first_subpage;
}

/**
 * Retrieves the last published subpage of the current page.
 */
function apm_get_last_subpage($page_id=0){
	$last_subpage = null;
	
	$page_position = Apm::get_page_tree_positions($page_id);
	if( !empty($page_position->children) ){
		$last_subpage_id = Apm::find_published_page_id_in_list($page_position->children,false);
		if( !empty($last_subpage_id) ){
			$last_subpage = Apm::get_page($last_subpage_id);
		}
	}
	
	return $last_subpage;
}

/**
 * Retrieves the link to the page in admin APM "All pages" panel, only if
 * a user is connected and has the right to edit pages.
 * Specs : ouvre la page dans le panneau Toutes les pages de l’admin
 */
function apm_where_is_page($page_id=0){ 
	return Apm::get_page_show_in_tree_link($page_id);
}

/**
 * Retrieves the order of a given page among its siblings.
 * Siblings order starts at 0.
 * If the page has no sibling, its order is 0 too.
 */
function apm_get_page_order($page_id=0){
	$page_position = Apm::get_page_tree_positions($page_id);
	return $page_position->order;
}

/**
 * Returns page positions infos : 'order','parent','depth','nb_children','children',
 * 'all_siblings','siblings_no_current','siblings_before','siblings_after','previous_sibling','next_sibling'
 */
function apm_get_page_position_infos($page_id=0){
	return Apm::get_page_tree_positions($page_id);
}

/**
 * TODO : Définir un format de retour : pour l'instant : retourne la liste des noeuds parents publiés
 * Specs : "Obtenir le chemin d’une page (la suite des pages et sous-pages)"
 */
function apm_get_page_path($page_id=0){
	$path = array();
	
	$page = get_page($page_id);
 	if( !empty($page->ancestors) ){
 		$path = $page->ancestors;
	}
	
	return $path;
}

/**
 * TODO : Que doit faire ce template_tag exactement?
 * Specs : "On prévoit de pouvoir faire des listes avec d’autres paramètres"
 */
function apm_get_page_by_id($page_id=0){
	return Apm::get_page($page_id);
}