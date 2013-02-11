<?php
/**
 * Social Streamer Youtube
 * 
 * Extension of social streamer.
 * This class fetches and parses Youtube RSS feeds.
 *
 * 
 * @author 		: Benjamin Horn
 * @project		: Wordpress
 * @file		: class.socialstreamer.youtube.php
 * @version		: 1.0.0
 * @created		: 2013-02-10
 * @updated		: 2013-02-13
 *
 * @usage		:
 *
 *				$yt = new socialYoutube( {USERNAME} );
 *				$myVideos = $yt->getPosts();
 *
 */
class socialYoutube extends SocialStreamer {
	protected $label = 'youtube';
	private $username = '';
	private $urlPrefix = 'http://gdata.youtube.com/feeds/base/users/';
	private $urlSuffix = '/uploads?alt=rss&v=2&orderby=published&client=ytapi-youtube-profile';
	
	function __construct( $username ) {
		$this->username = $username;

		parent::__construct( $this->urlPrefix . $username . $this->urlSuffix );
	}

	function domToPost( $dom ) {
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