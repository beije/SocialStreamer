<?php
/**
 * Social Streamer Twitter
 * 
 * Extension of social streamer.
 * This class fetches and parses twitter RSS feeds.
 *
 * 
 * @author 		: Benjamin Horn
 * @project		: Wordpress
 * @file		: class.socialstreamer.twitter.php
 * @version		: 1.0.0
 * @created		: 2013-02-10
 * @updated		: 2013-02-13
 *
 * @usage		:
 *
 *				$tweets = new SocialTwitter( {USERNAME} );
 *				$myTweets = $tweets->getPosts();
 *
 */
class SocialTwitter extends SocialStreamer {
	protected $label = 'twitter';
	private $username = '';
	private $urlPrefix = 'http://api.twitter.com/1/statuses/user_timeline.rss?screen_name=';
	function __construct( $username ) {
		$this->username = $username;
		parent::__construct( $this->urlPrefix . $username );
	}

	function domToPost( $dom ) {
		$posts = array();
		foreach( $dom->channel->item as $k => $post ) {
			if( !is_numeric( $k ) ) {
				// Remove username from tweets ( 'USER:' => '' )
				$content = str_replace( $this->username.': ', '', $post->description );
				$posts[] = array(
					'title' => $content,
					'md5' => (string) md5( $content ),
					'url' => (string) $post->link,
					'date' => (integer) strtotime( $post->pubDate ),
					'content' => (string) $content,
					'source' => (string) $this->label
				);
			}
		}

		return $posts;
	}	
}
?>