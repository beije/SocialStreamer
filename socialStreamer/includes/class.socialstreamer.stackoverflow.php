<?php
/**
 * Social Streamer Stackoverflow
 * 
 * Extension of social streamer.
 * This class fetches and parses Stackoverflow user feeds.
 *
 * 
 * @author 		: Benjamin Horn
 * @project		: Wordpress
 * @file		: class.socialstreamer.stackoverflow.php
 * @version		: 1.0.0
 * @created		: 2013-03-18
 * @updated		: 2013-03-18
 *
 * @usage		:
 *
 *				$SOuser = new socialStackoverflow( {STACKOVERFLOW-ID} );
 *				$myPosts = $SOuser->getPosts();
 *
 */
class SocialStackoverflow extends SocialStreamer {
	protected $label = 'stackoverflow';
	private $username = '';
	private $urlPrefix = 'http://stackoverflow.com/feeds/user/';

	function __construct( $username ) {
		$this->username = $username;

		parent::__construct( $this->urlPrefix . $username );
	}

	function domToPost( $dom ) {
		
		$posts = array();
		foreach( $dom->entry as $k => $post ) {
			if( !is_numeric( $k ) ) {
				$posts[] = array(
					'title' => (string) $post->title,
					'md5' => (string) md5( $post->summary ),
					'url' => (string) $post->id,
					'date' => (integer) strtotime( $post->published ),
					'content' => (string) $post->summary,
					'source' => (string) $this->label,
					'id' => (string) $this->username,
				);
			}
		}

		return $posts;
	}	
}
?>