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
	include( 'includes/class.socialstreamer.php' );
	include( 'includes/class.socialstreamer.twitter.php' );
	include( 'includes/class.socialstreamer.flickr.php' );
	include( 'includes/class.socialstreamer.vimeo.php' );
	include( 'includes/class.socialstreamer.youtube.php' );
	
	$supported = array( 
		'twitter' => array(), 
		'youtube' => array(), 
		'flickr'  => array(), 
		'vimeo'   => array(),
	);
	$socialPosts = array();
	foreach( $supported as $type => $users ) {
		$optionName = 'socialstreamer_' . $type;
		$users = get_option( $optionName );
		
		if( !$users ) continue;
		
		$users = unserialize( $users );
		foreach( $users as $user ) {
			$socialPosts = array_merge($socialPosts, socialStreamFetchData( $user, $type ) );	
		}
	}

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
 * Fetches a user based on id and type
 *
 * @param (string) $id Userid or what have you
 * @param (string) $type Social network
 *
 * @return array();
 */
function socialStreamFetchData( $id, $type ) {

	switch( $type ) {
		case 'youtube':
			$d = new socialYoutube( $id );
		break;
		case 'twitter':
			$d = new socialTwitter( $id );
		break;
		case 'vimeo':
			$d = new socialVimeo( $id );
		break;
		case 'flickr':
			$d = new socialFlickr( $id );
		break;
		default:
			return false;
		break;
	}

	return $d->getPosts();
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
 *
 *	ADMIN
 *
 */

// Add admin page to menu
add_action('admin_menu', 'socialStreamerAddAdminPage');
function socialStreamerAddAdminPage() {
	add_options_page( 'Social Streamer Options', 'Social Streamer', 'administrator', 'socialstreamer', 'socialStreamerAdmin');	
}

/*
 *	Function for rendering out the admin page
 */
function socialStreamerAdmin() {
	$supported = array( 'twitter', 'youtube', 'flickr', 'vimeo' );

	// Post data, save
	if( isset( $_REQUEST['do'] ) ) {

		// Loop through each network aka type
		foreach( $supported as $type ) {
			// build our option name
			$optionName = 'socialstreamer_' . $type;

			// Check that the request is set and that it's an array
			if( isset( $_REQUEST[$type] ) && is_array( $_REQUEST[$type] ) ) {
				
				// Clean data, removing items that are empty
				$data = array();
				foreach( $_REQUEST[$type] as $d ) {
					$d = trim( $d );
					if( !empty( $d ) ) {
						$data[] = $d;
					}
				}

				// Update database
				update_option( $optionName, serialize( $data ) );
			}
		}
	}

	//
	// Fetch data
	//

	$socialNetwork = array();
	// Loop through networks
	foreach( $supported as $type ) {
		// Build option name
		$optionName = 'socialstreamer_' . $type;

		// Check if option exists, if not, create it
		// (This should only run once, the first time you visit
		// this page)
		if( !get_option( $optionName ) ) {
			update_option( $optionName, serialize( array() ) );
		}

		// Get the option
		$option = get_option( $optionName );

		// Wordpress seams to cache get_option
		// and sometimes unserializes the data for you,
		// so this check is added so it doesn't throw errors
		if( !is_array( $option ) ) {
			// data is serialized
			$socialNetwork[$type] = unserialize( $option );
		} else {
			// Data is not serialized
			$socialNetwork[$type] = $option;
		}
	}

	//
	// Write out our form
	//

	?>
<div class="wrap">
	<h2><?php _e( 'Social streamer', 'att_trans_domain' ); ?></h2>
	<form name="att_img_options" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">

	<?php // loop through the networks ?>
	<?php foreach( $socialNetwork as $k => $users ): ?>
		<h3 style="text-transform:capitalize;"><?php echo $k; ?></h3>

		<?php // loop through the users under a network ?>
		<?php foreach( $users as $t => $user ): ?>
			<input type="text" name="<?php echo $k; ?>[]" value="<?php echo htmlentities( $user ); ?>" /><br />
		<?php endforeach; ?>

		<?php // Add an empty input field for new users ?>
		<input type="text" name="<?php echo $k; ?>[]" value="" placeholder="New!" /><br />
	<?php endforeach; ?>
		<br /><br />
		<input type="submit" name="do" value="<?php _e( 'Save', 'att_trans_domain' ); ?>" />
	</form>
</div>
<?php

}


/*
 *
 *	HOOKS
 *
 */


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

// Debug, add when you want to load content
// every page view.
//add_action( 'init', 'socialStreamIterator' );
?>