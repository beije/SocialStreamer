<?php
/**
 * Social Streamer Github
 * 
 * Extension of social streamer.
 * This class fetches and parses github atom feeds.
 *
 * 
 * @author 		: Benjamin Horn
 * @project		: Wordpress
 * @file		: class.socialstreamer.github.php
 * @version		: 1.0.1
 * @created		: 2013-03-10
 * @updated		: 2013-05-07
 *
 * @usage		:
 *
 *				$github = new socialGithub( {GITHUB-USERNAME} );
 *				$githubEvents = $github->getPosts();
 */

class SocialGithub extends SocialStreamer {
	protected $label = 'github';
	private $username = '';
	private $urlPrefix = 'http://github.com/';
	private $urlSuffix = '.atom';

	function __construct( $username ) {
		$this->username = $username;

		parent::__construct( $this->urlPrefix . $username . $this->urlSuffix );
	}

	function parsePosts( $dom ) {
		$posts = array();
		foreach( $dom->entry as $k => $post ) {
			if( !is_numeric( $k ) ) {

				$linkdata = $post->link->attributes();
				$posts[] = array(
					'title' => (string) $post->title,
					'md5' => (string) md5( $post->content ),
					'url' => (string) $linkdata->href,
					'date' => (integer) strtotime( $post->published ),
					'content' => (string) $post->content,
					'source' => (string) $this->label,
					'id' => (string) $this->username,
				);
			}
		}

		return $posts;
	}	
}
?>