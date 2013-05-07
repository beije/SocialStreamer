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
 * @version		: 1.1.0
 * @created		: 2013-02-10
 * @updated		: 2013-05-07
 *
 * @usage		:
 *
 *				$twitter_keys = array(
 *					'token' => '',
 *					'token_secret' => '',
 *					'consumer_key' => '',
 *					'consumer_secret' => '',
 *				);
 *
 *
 *				$tweets = new SocialTwitter( {USERNAME}, $twitter_keys );
 *				$myTweets = $tweets->getPosts();
 *
 */
class SocialTwitter extends SocialStreamer {
	protected $label = 'twitter';
	private $username = '';

	private $token = null;
	private $token_secret = null;
	private $consumer_key = null;
	private $consumer_secret = null;

	private $host = 'api.twitter.com';
	private $method = 'GET';
	private $path = '/1.1/statuses/user_timeline.json';

	private $postLimit = 10;

	private $authUrl;

	function __construct( $username, $oauthKeys = '' ) {
		$this->token = $oauthKeys['token'];
		$this->token_secret = $oauthKeys['token_secret'];
		$this->consumer_key = $oauthKeys['consumer_key'];
		$this->consumer_secret = $oauthKeys['consumer_secret'];

		$this->username = $username;

		$this->authUrl = 'https://' . $this->host . $this->path;

		$this->prepareRequest();

		$this->fetchData();
	}

	function parsePosts( $data ) {
		
		// Some error handling
		if( !is_array( $data ) ) {
			error_log( 'Social Streamer Wordpress Plugin: Error with twitter oauth, attached notice )' . json_encode( $data ) );
			return array();
		}
		if( count($data) == 0 ) {
			return array();
		}
		if( !isset( $data[0]->text ) ) {
			error_log( 'Social Streamer Wordpress Plugin: Error with twitter oauth, attached notice )' .json_encode( $data ) );
			return array();
		}

		if( isset( $data[0] ) )
		$posts = array();
		foreach( $data as $post ) {
			$posts[] = array(
				'title' => $post->text,
				'md5' => (string) md5( $post->text ),
				'url' => (string) "https://twitter.com/" . $this->username. "/statuses/". $post->id_str,
				'date' => (integer) strtotime( $post->created_at ),
				'content' => (string) $post->text,
				'source' => (string) $this->label,
				'id' => (string) $this->username,
			);
		}

		return $posts;
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
		return $this->parsePosts( $this->data );
	}

	/*
	 *	Function createQueryMash()
	 *
	 *	Mashes together the oauth-options with the query to form
	 *  data for SHA1 encryption.
	 *
	 *  @return (String) the mashed results
	 */
	function createQueryMash() {
		// Fetch options for query string
		$options = array_merge($this->oauth, $this->query);

		asort( $options ); // Secondary sort (value)
		ksort( $options ); // Primary sort (key)

		return http_build_query( $options, '', '&' );
	}

	/*
	 *	Function prepareRequest()
	 *
	 *	Prepares the oauth options and the actual query options
	 */
	function prepareRequest() {
		$oauth = array(
			'oauth_consumer_key' => $this->consumer_key,
			'oauth_token' => $this->token,
			'oauth_nonce' => (string) mt_rand(),
			'oauth_timestamp' => time(),
			'oauth_signature_method' => 'HMAC-SHA1',
			'oauth_version' => '1.0'
		);

		$query = array( // query parameters
			'screen_name' => $this->username,
			'count' => $this->postLimit,
		);

		$this->query = array_map( 'rawurlencode', $query );
		$this->oauth = array_map( 'rawurlencode', $oauth ); // must be encoded before sorting
	}

	/*
	 *	Function fetchSignature()
	 *
	 *	Returns a hash signature generate from the request and oauth details
	 *
	 *	@return (String), Signature BASE64 SHA1 hash
	 *
	 */
	private function fetchSignature() {
		
		// Create the data and the encryption key
		$data = $this->method . '&' . rawurlencode( $this->authUrl ) . '&' . rawurlencode( $this->createQueryMash() );
		$key = rawurlencode( $this->consumer_secret ) . '&' . rawurlencode( $this->token_secret );

		// generate the hash
		return base64_encode( hash_hmac( 'sha1', $data, $key, true ) );
	}

	/*
	 *	Function fetchData()
	 *
	 *	Does a curl request to twitter and fetches the data
	 *  The data is then populated to $this->data
	 *
	 */
	function fetchData() {
		// Add the signature for this request
		$this->oauth['oauth_signature'] = rawurlencode( $this->fetchSignature() );

		// Add the authorization line
		$auth = 'OAuth ' . urldecode( http_build_query( $this->oauth, '', ', ' ) );

		// Build the final options array for curl
		$options = array( 
			CURLOPT_HTTPHEADER => array( 'Authorization:' . $auth ),
			CURLOPT_HEADER => false,
			CURLOPT_URL => $this->authUrl . '?' . http_build_query( $this->query ),
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false
		);

		// do our business
		$curl = curl_init();
		curl_setopt_array($curl, $options);
		$response = curl_exec($curl);
		curl_close($curl);

		$this->data = json_decode( $response );
	}


}
?>