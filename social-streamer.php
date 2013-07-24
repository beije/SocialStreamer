<?php
/*
Plugin Name: Social streamer
Plugin URI: http://www.benjaminhorn.se
Description: Fetches social updates from different sources.
Version: 0.0.3
Author: Benjamin Horn
Author URI: http://www.benjaminhorn.se
*/

include( 'class.socialstreamer.plugin.php' );
$socialStreamerPlugin = new socialStreamerPlugin();

// Uncomment to parse every page load (useful for debugging)
// $socialStreamerPlugin->parseHook();
?>