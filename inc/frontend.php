<?php
/**
 * Tweeted_Frontend Class for Tweeted
 * 
 * This class controls on what happens on the user end when
 * they goto a post.
 * 
 * @since 1.0.0
 * @package tweeted
 * @subpackage frontend
 */

require_once(ABSPATH . WPINC . '/class-IXR.php');
require_once(ABSPATH . WPINC . '/class-json.php');

/**
 * The frontend class for user.
 * @author Shane A. Froebel
 * @since 1.0.0
 */
class Tweeted_Frontend extends Tweeted {
	
	/**
	 * @since 1.0.0
	 * @var boolean
	 */
	var $in_admin;
	
	/**
	 * Main Constructor Class
	 * @since 1.0.0
	 * @return none
	 * @constructor
	 */
	function __construct($in_admin = false) {
		Tweeted::__construct();
		
		// Set this to true if we in the admin area
		$this->in_admin = $in_admin;
		
		/** Create the shortcode **/
		add_shortcode('tweeted', array(&$this, 'get_tweeted'));
		/** Actions & Filters **/
		add_action( 'wp_default_styles', array(&$this, 'default_styles') );
		add_filter( 'the_posts', array(&$this, 'show_css'), 1 );
		
	}
	
	/**
	 * WordPress Overrides
	 */
	
	/**
	 * Add Twitter Styles
	 * @since 1.0.0
	 * @param $styles
	 * @return none
	 */
	function default_styles(&$styles) {
		/** Layout **/
		$styles->add( 'tweeted-layout', $this->plugin_url() . '/css/tweeted-layout.css',	'',	'20100123' );
		
		$themes = apply_filters('tweeted_themes', $this->themes);
		/** Default Themes **/
		foreach ($themes as $theme => $info)
			$styles->add( 'tweeted-' . $theme, $info['file'], array('tweeted-layout'), $info['time']);
	
	}
	
	/**
	 * Main Code
	 */
	
	/**
	 * Run Status
	 * @since 1.0.0
	 * @return boolean
	 */
	function status() {
		if ($this->get_option('run_status') == 'on')
			return true;
		else
			return false;
	}
	
	/**
	 * Get the status information first before we query the Twitter API
	 * @since 1.0.0
	 * @param array $attrib
	 * @param string $content Default null.
	 * @param string $content Default null.
	 * @return string Returns what is going to be outputed on the screen.
	 */
	function get_tweeted($attrib, $content = null, $code = null) {
		
		preg_match_all("/(\d+)/x", $content, $status);		

		if(intval($status[0][0]) > 0) {
			/** process & return data **/
			return $this->show_status($status[0][0]);
		}
		else
			return;	
	}
	
	/**
	 * Output the Status!
	 * @since 1.0.0
	 * @param int $status_id
	 * @return none
	 */
	function show_status($status_id = '') {
		global $post;
		
		// Check to see if we are on!
		if ( !$this->status() )
			return '';
		
		// Are we in admin?
		if ( !$this->in_admin ) {
			
			/** Check to see if it's already stored in the post/page data. **/
			$twittertweet_tweet_meta_values = get_post_meta($post->ID, '_tweeted_' . $status_id, true);
			// If we have data already from this post, we should be showing it. No need to re-query Twitter.
			if ( is_array($twittertweet_tweet_meta_values) ) {
				return $this->show_tweet($twittertweet_tweet_meta_values, $status_id);
			}
		
			/**
			 * Query Twitter API
			 **/
			// @todo Change this to the new API address
			$twitter_ixr = new IXR_Tweeted('http://twitter.com/statuses/show/'.$status_id.'.json');
			$twitter_ixr->debug = false;	//	only set this to true when debuging Twitter Connection!
			if ( !$twitter_ixr->query('GET'))
				return "<a href='http://twitter.com/statuses/show/'.$status_id .'>Tweet</a>";
//			    die('Something Went Wrong: '.$twitter_ixr->getErrorCode().' : '.$twitter_ixr->getErrorMessage());
			/** Store the data. **/	
			$twitter_ixr_data = $twitter_ixr->message;
			/** Close the IXR connection **/
			unset($twitter_ixr);
		
			/**
			 * Start JSON Service 
			 **/
			$twitter_json = new Services_JSON();
			/** Decode JSON Data **/
			$twitter_json_data = $twitter_json->decode($twitter_ixr_data->message);
			/** Close the JSON Connection **/
			unset($twitter_json);
			
			/** Store Data **/
			$tweet_array = array(
				/** Tweet Info **/
				'id'			=> $twitter_json_data->user->id,
				'realname'		=> $twitter_json_data->user->name,
				'user'			=> $twitter_json_data->user->screen_name,
				'gravatar'		=> $twitter_json_data->user->profile_image_url,
				'text'			=> $twitter_json_data->text,
				/** Extra Data **/
				'source'		=> $twitter_json_data->source,
				'reply_to'		=> $twitter_json_data->in_reply_to_screen_name,
				'reply_to_id'	=> $twitter_json_data->in_reply_to_status_id,
				'create_at'		=> $twitter_json_data->created_at,			/** time that the user created this tweet based of UTC **/
				'utc_offset'	=> $twitter_json_data->user->utc_offset,	/** user's UTC offset **/
			);
			
			add_post_meta($post->ID, '_tweeted_' . $status_id, $tweet_array, true);
		
			unset($twitter_json_data);
			return $this->show_tweet($tweet_array, $status_id);
			
		}
		
		// Fake Data for Admin Page Preview Only
		$tweet_array = array(
			/** Tweet Info **/
			'id'			=>	'',
			'realname'		=>	'WPVibe',
			'user'			=>	'WPVibe',
			'gravatar'		=>	$this->plugin_url() . '/images/wpvibe-avatar.png',
			'text'			=>	'Thanks for using our plugin! #wordpress via @WPVibe',
			/** Extra Data **/
			'source'		=>	'<a href="http://wordpress.org">WordPress Admin Preview</a>',
			'reply_to'		=>	'WPVibe',
			'reply_to_id'	=>	'#',
			'create_at'		=>	'',
			'utc_offset'	=>	'',
		);
		
		return $this->show_tweet($tweet_array);
		
	}

	/**
	 * Show the tweet.
	 * @since 1.0.0
	 * @param $content
	 * @param $status_id [Optionial] The statis ID number.
	 * @return none
	 */
	function show_tweet($content, $status_id = '') {

		/** Send $content through filter so plugins, themes can mainipulate the data. **/
		extract( apply_filters('twitter_tweet_content', $content), EXTR_SKIP );
		
		//	Set Time
		$time = date($this->get_option('date_format'), strtotime($create_at));
		
		//	Source 'no follow'  ... props alxndr
		$pattern = '/(<a href="[^"]+")(>[^<>]+<\/a>)/';
		$replacement = '$1 rel="nofollow"$2';
		$source = preg_replace($pattern, $replacement, $source, 1);
		
		$tweet_output = '';
		
		/** Our Output **/
		$tweet_output .= '<div class="tweeted">';
		$tweet_output .= '	<div class="tweeted-arrow"></div>';
		$tweet_output .= '		<div class="tweeted-main">';
		$tweet_output .= '			<div class="tweeted-info">';
		$tweet_output .= '				<a href="http://twitter.com/'.$user.'" title="'.$realname.'"><img src="'.$gravatar.'" alt="Twitter Avatar" /></a>';
		$tweet_output .= '				<h2><a href="http://twitter.com/'.$user.'" title="'.$realname.'">'.$user.'</a></h2>';
		$tweet_output .= '				<h3>'.$realname.'</h3>';
		$tweet_output .= '			</div>';		
		$tweet_output .= '			<div class="tweeted-tweet">';
		$tweet_output .= '				<p><a href="http://twitter.com/'.$user.'" title="'.$realname.'">@'.$user.'</a>: '.$text.'</p>';
		$tweet_output .= '				<span><a href="' . (( !$this->in_admin ) ? 'http://twitter.com/'.$user.'/status/'.$status_id : '#') . '" title="'.$time.'" rel="nofollow">'.$time.'</a> from '.$source;
		if ( !empty($reply_to) )
			$tweet_output .= sprintf( __(' in reply to <a href="%s" title="%s">%s</a>'),  ( !$this->in_admin ) ? 'http://twitter.com/'.$reply_to.'/status/'.$reply_to_id : '#', $reply_to, $reply_to);
		$tweet_output .= '				</span>';
		if ( $this->get_option('show_props') )
			$tweet_output .= sprintf( '<span class="props">%s <a href="http://tweeted.org" title="%s">%s</a></span>', __("Powered by"), __("Powered by Tweeted"), __("Tweeted"));
		$tweet_output .= '			</div>';
		$tweet_output .= '		</div>';
		$tweet_output .= '</div>';
		
		return $tweet_output;		
		// return apply_filters('twitter_tweet_override_output', $tweet_output, $content);
	}
	
	/**
	 * Check Functions
	 */
	
	/**
	 * Only output CSS code if needed.
	 * @since 1.0.0
	 * @param $posts
	 * @return array
	 */
	function show_css($posts) {	
		if (empty($posts)) 
			return $posts;
	 
		$shortcode_found = false;
		foreach ($posts as $post) {
			if ( stripos($post->post_content, 'tweeted') ) {
				$shortcode_found = true;
				break;
			}
		}
	 	
		if ( ( $shortcode_found ) && ( !$this->get_option('default_css') ) )
			wp_enqueue_style('tweeted-' . $this->get_option('theme'));	//	Output the current theme.
	 
		return $posts;
	}
	
}

/**
 * Extends IXR_Client to use it for Twitter API Calls.
 * @author Shane A. Froebel
 * @since 1.0.0
 */
class IXR_Tweeted extends IXR_Client {
	
	/**
	 * This is the hard-coded User Agent String
	 * @var string
	 */
	var $useragent = 'Tweeted WordPress Plugin';
	
    function query() {
        $args = func_get_args();
        $method = array_shift($args);
        $request = new IXR_Request($method, $args);
        $length = $request->getLength();
        $xml = $request->getXml();
        
        $r = "\r\n";
        $request = "GET {$this->path} HTTP/1.0$r";

		$this->headers['Host']				= $this->server;
		$this->headers['Content-Type']		= 'text/xml';
		$this->headers['User-Agent']		= $this->useragent;
		$this->headers['Content-Length']	= $length;
		
		if ($this->debug) {
			echo '<pre class="ixr_request">'.htmlspecialchars($request)."\n</pre>\n\n";
		}
		
		foreach( $this->headers as $header => $value ) {
			$request .= "{$header}: {$value}{$r}";
		}
		$request .= $r;
        $request .= $xml;
        if ($this->timeout) {
            $fp = @fsockopen($this->server, $this->port, $errno, $errstr, $this->timeout);
        } else {
            $fp = @fsockopen($this->server, $this->port, $errno, $errstr);
        }
        if (!$fp) {
            $this->error = new IXR_Error('tweeted-transport', "Transport Error - Could not open socket: $errno $errstr");
            return false;
        }
        fputs($fp, $request);
        $contents = '';
        $debug_contents = '';
        $gotFirstLine = false;
        $gettingHeaders = true;
        while (!feof($fp)) {
            $line = fgets($fp, 4096);
            if (!$gotFirstLine) {
                if (strstr($line, '200') === false) {
                    $this->error = new IXR_Error('tweeted-transport-http', 'Transport Error - HTTP status code was not 200. - ' . $line);
                    return false;
                }
                $gotFirstLine = true;
            }
            if (trim($line) == '') {
                $gettingHeaders = false;
            }
            if (!$gettingHeaders) {
                $contents .= trim($line);
            }
            if ($this->debug) {
            	$debug_contents .= $line;
            }
        }
        if ($this->debug) {
        	echo '<pre class="ixr_response">'.htmlspecialchars($debug_contents)."\n</pre>\n\n";
        }
        if (empty($contents)) {
        	$this->error = new IXR_Error('tweeted-nocontent', 'No content.');
        	return false;
        }
        $this->message = new IXR_Message($contents);
        if ($this->debug) {
        	echo print_r($this->message);
        }
        return true;
    }
}
