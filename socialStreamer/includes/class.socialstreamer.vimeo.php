<?php
/**
 * Social Streamer Vimeo
 * 
 * Extension of social streamer.
 * This class fetches and parses Vimeo RSS feeds.
 *
 * 
 * @author 		: Benjamin Horn
 * @project		: Wordpress
 * @file		: class.socialstreamer.vimeo.php
 * @version		: 1.0.1
 * @created		: 2013-02-10
 * @updated		: 2013-05-07
 *
 * @usage		:
 *
 *				$videos = new socialVimeo( {USERNAME} );
 *				$myVideos = $videos->getPosts();
 *
 */
class SocialVimeo extends SocialStreamer {
	protected $label = 'vimeo';
	private $username = '';
	private $urlPrefix = 'http://vimeo.com/';
	private $urlSuffix = '/videos/rss';
	
	function __construct( $username ) {
		$this->username = $username;

		parent::__construct( $this->urlPrefix . $username . $this->urlSuffix );
	}

	function parsePosts( $dom ) {
		$posts = array();

		foreach( $dom->channel->item as $k => $post ) {
			if( !is_numeric( $k ) ) {
				
				$posts[] = array(
					'title' => $post->title,
					'md5' => (string) md5( $post->description ),
					'url' => (string) $post->link,
					'date' => (integer) strtotime( $post->pubDate ),
					'content' => (string) $post->description,
					'source' => (string) $this->label,
					'id' => (string) $this->username,
				);
			}
		}
		
		return $posts;
	}	
}
?>