<?php
include( 'includes/class.socialstreamer.php' );
include( 'includes/class.socialstreamer.twitter.php' );
include( 'includes/class.socialstreamer.flickr.php' );
include( 'includes/class.socialstreamer.vimeo.php' );
include( 'includes/class.socialstreamer.youtube.php' );
include( 'includes/class.socialstreamer.github.php' );
include( 'includes/class.socialstreamer.stackoverflow.php' );

class SocialStreamerPlugin {

	private $supportedNetworks = array( 
		'twitter' => array(), 
		'youtube' => array(), 
		'flickr'  => array(), 
		'vimeo'   => array(),
		'github'   => array(),
		'stackoverflow'   => array(),
	);

	public function __construct() {
		$this->setupWordpress();
	}

	/*
	 * Used for debugging
	 *
	 */
	public function parseHook() {
		add_action( 'init', array( $this, 'iterator' ) );
	}

	/*
	 * Iterates through all the feeds, "main loop"
	 *
	 */
	public function iterator() {
		
		$socialPosts = array();
		foreach( $this->supportedNetworks as $type => $users ) {
			$optionName = 'socialstreamer_' . $type;
			$users = get_option( $optionName );
			
			if( !$users ) continue;
			
			$users = unserialize( $users );
			foreach( $users as $user ) {
				$socialPosts = array_merge($socialPosts, $this->fetchData( $user, $type ) );	
			}
		}

		// Loop through the posts
		foreach( $socialPosts as $socialpost ) {

			if( !$this->postExists( $socialpost['md5'] ) ) {
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
					add_post_meta($id, '_md5', $socialpost['md5'], true );
					add_post_meta($id, 'identifier', $socialpost['id'], true );
				}
			
			}
		}
	}

	/*
	 * Checks if a post exists
	 *
	 * @param (string) $md5 MD5 hash of post
	 *
	 * @return boolean;
	 */
	private function postExists( $md5 ) {
		// Assume that post exists
		$exists = true;

		// Check if current post has already been inserted
		// (The classes have created an MD5 hash on the content 
		// which has been saved as a meta value)
		$matches = new WP_Query( "post_type=socialstreamer_post&meta_key=_md5&meta_value=". $md5 );
		
		if( $matches->found_posts == 0 ) {
			$exists = false;
		}

		// Reset our search
		wp_reset_postdata();

		return $exists;
	}

	/*
	 * Fetches oauth tokens for given network.
	 * (Currently only twitter uses this)
	 *
	 * @param (string) $network The name of the network (twitter)
	 *
	 * @return array;
	 */
	private function getOauthKeys( $network ) {
		// Fetch keys
		$keys = get_option( 'socialstreamer_' . $network . '_keys' );

		if( $keys == false ) {
			return array();
		}

		// Unpack
		if( !is_array( $keys ) ) {
			// data is serialized
			$keys = unserialize( $keys );
		} else {
			// Data is not serialized
			$keys = $keys;
		}

		return $keys;

	}

	/*
	 * Fetches a user based on id and type
	 *
	 * @param (string) $id Userid or what have you
	 * @param (string) $type Social network
	 *
	 * @return array();
	 */
	private function fetchData( $id, $type ) {

		switch( $type ) {
			case 'youtube':
				$d = new socialYoutube( $id );
			break;
			case 'twitter':
				
				$keys = $this->getOauthKeys( 'twitter' );
				if( $keys ) {
					$d = new socialTwitter( $id, $keys );
				} else {
					return array();
				}

			break;
			case 'vimeo':
				$d = new socialVimeo( $id );
			break;
			case 'flickr':
				$d = new socialFlickr( $id );
			break;
			case 'github':
				$d = new socialGithub( $id );
			break;
			case 'stackoverflow':
				$d = new socialStackoverflow( $id );
			break;
			default:
				return false;
			break;
		}

		return $d->getPosts();
	}

	/*
	 *
	 *    Setup wordpress events
	 *
	 */
	private function setupWordpress() {


		// Set actions
		register_deactivation_hook(__FILE__, array( $this, 'deactivatePlugin' ) );
		add_action( 'init', array( $this, 'createPostType' ) );
		add_action( 'socialStreamerCron', array( $this, 'iterator' ) );
		add_action('admin_menu', array( $this, "addAdminPageToMenu" ) );


		// Add new cron schedule
		add_filter( 'cron_schedules', 'add_schedule' );
		function add_schedule( $param ) {
			return array( 'every_five_minutes' => array( 
				'interval' => 300,
				'display'  => __( 'Every 5 minutes' ) 
			) );
		}

		// Add our cron entry
		if ( ! wp_next_scheduled('socialStreamerCron') ) {
			wp_schedule_event( time(), 'every_five_minutes', 'socialStreamerCron' ); // hourly, daily and twicedaily
		}


	}

	/*
	 *    Registers our custom post type
	 *
	 */
	public function createPostType() {
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
	 *    Deactivates plugin, called on deactivation
	 *
	 */
	public function deactivatePlugin() {
		wp_clear_scheduled_hook( 'socialStreamerCron' );
	}

	/*
	 *    Adds plugin settings page to menu
	 *
	 */
	public function addAdminPageToMenu() {
		add_options_page( 'Social Streamer Options', 'Social Streamer', 'administrator', 'socialstreamer', array( $this, 'renderAdminPage' ) );	
	}

	/*
	 *    Renders admin page
	 *
	 */
	public function renderAdminPage() {
			foreach( $this->supportedNetworks as $network => $posts ) {
				$supported[] = $network;
			}
			//$supported = array( 'twitter', 'youtube', 'flickr', 'vimeo', 'github' );

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

				//
				// Twitter oauth tokens
				//
				$twitter_tokens = array(
					'token' => ( isset( $_REQUEST['twitter_token'] ) ? $_REQUEST['twitter_token'] : '' ),
					'token_secret' => ( isset( $_REQUEST['twitter_token_secret'] ) ? $_REQUEST['twitter_token_secret'] : '' ),
					'consumer_key' => ( isset( $_REQUEST['twitter_consumer_key'] ) ? $_REQUEST['twitter_consumer_key'] : '' ),
					'consumer_secret' => ( isset( $_REQUEST['twitter_consumer_secret'] ) ? $_REQUEST['twitter_consumer_secret'] : '' ),
				);

				update_option( 'socialstreamer_twitter_keys', serialize( $twitter_tokens ) );
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

				// Wordpress seems to cache get_option
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
			// Fetch twitter oauth keys
			//

			// Check if socialstreamer_twitter_keys exists,
			// create if not.
			if( !get_option( 'socialstreamer_twitter_keys' ) ) {
				update_option( 'socialstreamer_twitter_keys', serialize( array() ) );
			}

			// Fetch keys
			$twitter_keys = $this->getOauthKeys( 'twitter' );

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
				<h3 style="text-transform:capitalize;">Extra options</h3>
				<strong>Twitter oauth keys</strong>
				<p>
					In order to fetch twitter updates you need to create a twitter application under <a href="https://dev.twitter.com/apps" target="_blank">dev.twitter.com/apps</a>.
					Once you've created your keys, insert them here.
				</p>
				<dl>
					<dt>Token</dt>
					<dd>
						<input type="text" name="twitter_token" value="<?php echo htmlentities( $twitter_keys['token'] ); ?>" />
					</dd>
					<dt>Token secret</dt>
					<dd>
						<input type="text" name="twitter_token_secret" value="<?php echo htmlentities( $twitter_keys['token_secret'] ); ?>" />
					</dd>
					<dt>Consumer key</dt>
					<dd>
						<input type="text" name="twitter_consumer_key" value="<?php echo htmlentities( $twitter_keys['consumer_key'] ); ?>" />
					</dd>
					<dt>Consumer secret</dt>
					<dd>
						<input type="text" name="twitter_consumer_secret" value="<?php echo htmlentities( $twitter_keys['consumer_secret'] ); ?>" />
					</dd>
				</dl>

				<br /><br />
				<input type="submit" name="do" value="<?php _e( 'Save', 'att_trans_domain' ); ?>" />
			</form>
		</div>
		<?php
	}

}

?>