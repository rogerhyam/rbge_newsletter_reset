<?php
/*
Plugin Name: RBGE Newsletter Reset
Description: A web service to reset posts in the Newsletter category
Version: 0.1
Author: Roger Hyam
License: GPL2
*/
// error_reporting(E_ALL); ini_set('display_errors', 1);

add_action( 'rest_api_init', function () {
	register_rest_route( 'rbge_newsletter_reset', '/since/*', array(
		'methods' => 'GET',
		'callback' => 'newsletter_reset',
	) );
} );

function newsletter_reset( $request ) {
    
    $out = array();
    
    $newsletter_cat = get_category_by_slug('newsletter');
    
    $out['newsletter-cat-id'] = $newsletter_cat->term_id; 
    
    // get all the posts in the newsletter category
    $newsletter_posts = get_posts( array('category' => $newsletter_cat->term_id, 'numberposts' => 1000) );
    
    // remove the newsletter category from them
    foreach($newsletter_posts as $post){
        
        $new_cats = array();
        $old_cats = wp_get_post_categories($post->ID);

        foreach($old_cats as $cat_id){
            if($cat_id == $newsletter_cat->term_id) continue;
            $new_cats[] = $cat_id;
        }
        
        wp_set_post_categories($post->ID, $new_cats, false);
        wp_update_post($post);
    
    }
        
    // work out the since date - it is a month ago if it isn't passed in
    if(@$_GET['date']){
        $since = new DateTime($_GET['date']);
    }else{
        $since = new DateTime();
    }
    
    $out['posts_since'] = $since->format('Y-m-d');
    
    $args = array(
        'post_type' => 'post', 
        'post_status'   => 'publish',
        'date_query'    => array(
            'column'  => 'post_date',
            'after'   => $since->format('Y-m-d')
        ),
        'posts_per_page' => 200
    );
    $query = new WP_Query( $args );
    
    // add them all to the newsletter category
    foreach($query->posts as $post){
  
        wp_set_post_categories($post->ID, array($newsletter_cat->term_id), true);
        
        $out['newly_' .  $post->ID] = array(
            'title' => $post->post_title,
            'date' => $post->post_date,
            'cats' => wp_get_post_categories($post->ID));
    }
    
    
	return $out;
}


?>
