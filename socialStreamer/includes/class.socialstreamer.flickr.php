<?php
/**
 * Social Streamer Flickr
 * 
 * Extension of social streamer.
 * This class fetches and parses flickr RSS feeds.
 *
 * 
 * @author 		: Benjamin Horn
 * @project		: Wordpress
 * @file		: class.socialstreamer.flickr.php
 * @version		: 1.0.1
 * @created		: 2013-02-10
 * @updated		: 2013-05-07
 *
 * @usage		:
 *
 *				$pictures = new socialFlickr( {FLICKR-ID} );
 *				$myPictures = $pictures->getPosts();
 *
 */
class SocialFlickr extends SocialStreamer {
	protected $label = 'flickr';
	private $username = '';
	private $urlPrefix = 'http://api.flickr.com/services/feeds/photos_public.gne?lang=en-us&format=rss_200&id=';

	function __construct( $username ) {
		$this->username = $username;

		parent::__construct( $this->urlPrefix . $username );
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