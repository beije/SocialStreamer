<?php
/**
 * Social streamer 
 * 
 * Social streamer is a class which handles different feeds form different social media networks.
 * The updates are then saved to wordpress (This class can be used for any project though).
 * 
 * This is a parent class and can't be used as a standalone class, it needs the subclasses to function.
 * 
 * @author 		: Benjamin Horn
 * @project		: Wordpress
 * @file		: class.socialstreamer.php
 * @version		: 1.0.0
 * @created		: 2013-02-10
 * @updated		: 2013-02-13
 *
 * @usage		:
 *
 *	The parent class shouldn't be used.
 *
 */
abstract class SocialStreamer {

	protected $url = false;
	protected $data = array();
	protected $posts = array();
	protected $label = 'Not declared';
	public $parsedPosts = array();

	public function __construct( $url ) {
		$this->url = $url;
		$this->fetchData();
	}

	/*
	 *	Function fetchData()
	 *
	 *	Fetches $this->url and parses the data with simplexml_load_string.
	 *
	 */
	private function fetchData() {
		if( !$this->url ) return false;

		if( $d = file_get_contents( $this->url ) ) {
			$this->data = simplexml_load_string ( $d ); 
		}
	}


	/*
	 *	Function getPosts()
	 *
	 *	Returns the posts from the feed as an array
	 *
	 *	@return (array), An array with the posts
	 *
	 */
	public function getPosts() {
		return $this->domToPost( $this->data );
	}


	/*
	 *	Function getPosts()
	 *
	 *	Returns the posts from the feed as an array
	 *
	 *	@param (string) $url, the url to the feed
	 */
	public function setUrl( $url ) {
		$this->url = $url;
		$this->fetchData();
	}

	/*
	 *	Function domToPost()
	 *
	 *	Returns an empty array
	 *
	 */		
	abstract protected function domToPost( $dom );
}
?>