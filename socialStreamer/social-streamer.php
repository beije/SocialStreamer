<?php
/*
Plugin Name: Social streamer
Plugin URI: http://www.benjaminhorn.se
Description: Fetches social updates from different sources.
Version: 0.0.1
Author: Benjamin Horn
Author URI: http://www.benjaminhorn.se
*/

/*
 *    Add new cron schedule
 *    Once every 5 minutes
 */
add_filter( 'cron_schedules', 'add_schedule' );
function add_schedule( $param ) {
    return array( 'every_five_minutes' => array( 
        'interval' => 300,
        'display'  => __( 'Every 5 minutes' ) 
    ) );
}

/*
 *    socialStreamIterator
 *    Fetches, checks and adds all the posts
 */
function socialStreamIterator() {

	// Load our classes
	include( 'class.socialstreamer.php' );
	include( 'class.socialstreamer.twitter.php' );
	include( 'class.socialstreamer.flickr.php' );
	include( 'class.socialstreamer.vimeo.php' );
	include( 'class.socialstreamer.youtube.php' );
	
	// Initialize our objects and fetch the data
	$yt = new socialYoutube('thq1beije');
	$v = new socialVimeo('beije');
	$tw = new socialTwitter('thq1beije');
	$f = new socialFlickr('70752991@N03');

	// Build an array with all the data
	$socialPosts = $yt->getPosts();
	$socialPosts = array_merge( $socialPosts, $v->getPosts() );
	$socialPosts = array_merge( $socialPosts, $tw->getPosts() );
	$socialPosts = array_merge( $socialPosts, $f->getPosts() );

	// Loop through the posts
	foreach( $socialPosts as $socialpost ) {

		// Check if current post has already been inserted
		// (The classes have created an MD5 hash on the content 
		// which has been saved as a meta value)
		$matches = new WP_Query( "post_type=socialstreamer_post&meta_key=md5&meta_value=". $socialpost['md5'] );
		if( $matches->found_posts == 0 ) {
			
			// Current post is new, insert

			// Prepare the 'core' data
			$post = array(
				'post_content' => $socialpost['content'],
				'post_date' => date( 'Y-m-d H:i:s', $socialpost['date'] ),
				'post_date_gmt' => date( 'Y-m-d H:i:s', $socialpost['date'] ),
				'post_name' => $socialpost['title'],
				'post_status' => 'publish',
				'post_title' => $socialpost['title'],
				'post_type' => 'socialstreamer_post',
			);
			
			// Insert post
			$id = wp_insert_post( $post );

			// Check our id
			if( is_numeric( $id ) ) {

				// Insert metadata about the post
				add_post_meta($id, 'source', $socialpost['source'], true );
				add_post_meta($id, 'url', $socialpost['url'], true );
				add_post_meta($id, 'md5', $socialpost['md5'], true );
			}
		
		}

		// Reset our search
		wp_reset_postdata();
		$matches = null;
	}
}

/*
 *    Creates our custom post type
 *
 */
function create_post_type() {
	register_post_type( 'socialstreamer_post',
		array(
			'labels' => array(
				'name' => __( 'Social posts' ),
				'singular_name' => __( 'Social post' )
			),
			'public' => true,
			'has_archive' => false,
			'rewrite' => array('slug' => 'socialposts'),
			'supports' => array( 'custom-fields', 'editor', 'author' ),
		)
	);
}

/*
 *    Fires when the plugin is deactivated
 *    removes our cron entry
 */
function socialStreamerDeactivate() {
    wp_clear_scheduled_hook( 'socialStreamerCron' );
}

// Add our cron entry
if ( ! wp_next_scheduled('socialStreamerCron') ) {
    wp_schedule_event( time(), 'every_minute', 'socialStreamerCron' ); // hourly, daily and twicedaily
}

// Set actions
register_deactivation_hook(__FILE__, 'socialStreamerDeactivate');
add_action( 'init', 'create_post_type' );
add_action( 'socialStreamerCron', 'socialStreamIterator' );
 
?>