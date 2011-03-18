<?php
/**
 * 'Tweeted' plugin allows you to quick import a Twitter
 * status message into any post/page without having to copy the information.
 * 
 * Formats the output just like Twitter. Use custom CSS
 * to be able to style it for your theme!
 * 
 * Note: PHP5 Support Only!
 * 
 * @author Shane Froebel <shane@bugssite.org>
 * @version 1.0.0
 * @copyright 2009
 * @package tweeted
 */

 /**
 * Define the 'Tweeted' Plugin Version Number
 * @version 1.0.0
 * @return none
 */
DEFINE ( 'TWEETED_VERSION', '1.0.0' );			//	MAKE SURE THIS MATCHES THE VERSION ABOVE AND BELOW!!!!

/*
**************************************************************************
Plugin Name:  Tweeted
Plugin URI:   http://tweeted.org
Version:      1.0.0
Description:  This plugin will let you embed a tweet directly into your post or page, without having to copy the content of the tweet or take a screenshot. Just copy the link to the tweet, such as [tweeted]http://twitter.com/wpvibe/status/8684685314[/tweeted] and the specified tweet will show up.
Author:       <a href="http://bugssite.org/">Shane Froebel</a> (Code), <a href="http://armeda.com">Dre Armeda</a> (Design),  and <a href="http://www.jonathan.vc">Jonathan Dingman</a> (Manager) 
Author URI:	  http://wpvibe.com
**************************************************************************/

class Tweeted {
	
	/**
	 * Tweeted Version
	 * @since 1.0.0
	 * @var string
	 */
	var $version = TWEETED_VERSION;

	/*
	 * Our Default Themes
	 * @since 1.0.0
	 * @var array
	 */
	var $themes;
	
	/**
	 * Options
	 * @since 1.0.0
	 * @var string
	 */
	var $options;

	/**
	 * Compatability for PHP 4.
	 * @since 1.0.0
	 * @return none
	 */
	function Tweeted() {
		$this->__construct();
	}

	/**
	 * Main Contructor Class
	 * @since 1.0.0
	 * @return none
	 * @constructor
	 */
	function __construct() {
		// Ability for plugins,themes to add own themes to system.
		$this->themes = array(
			'light' =>	array('name' => _('Light'), 'file' => $this->plugin_url() . '/css/tweeted-light.css', 'time' => '20100213'),
			'dark'	=>	array('name' => _('Dark'), 'file' => $this->plugin_url() . '/css/tweeted-dark.css', 'time' => '20100213'),
		);
		
		//	Load Options
		$this->options = get_option('tweeted');		
	}
	
	/**
	 * Get the full URL to the plugin
	 * @since 1.0.0
	 * @return string
	 */
	function plugin_url() {
		$plugin_url = plugins_url ( plugin_basename ( dirname ( __FILE__ ) ) );
		return $plugin_url;
	}
	
	/**
	 * Get an option from the array.
	 * @since 1.0.0
	 * @return none
	 * @param object $option
	 */
	function get_option($option) {
		if ( isset($this->options[$option]) )
			return $this->options[$option];
		else
			return false;
	}
	
}

/**
 * Start the Tweeted_Admin or Tweeted_Frontend Class
 */
// Note: Only Operate with PHP5.
require_once( dirname( __FILE__ ) . '/inc/admin.php' 			);
require_once( dirname( __FILE__ ) . '/inc/frontend.php' 		);
if ( is_admin() ) {
	$Tweeted_Admin = new Tweeted_Admin();
} else {
	$Tweeted_Frontend = new Tweeted_Frontend();	
}

?>