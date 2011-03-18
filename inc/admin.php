<?php
/**
 * Tweeted_Admin Class for Tweeted
 * 
 * This class controls on what happens on the admin page.
 * This includes the options pages for this plugin.
 * 
 * @since 1.0.0
 * @package tweeted
 * @subpackage admin
 */

class Tweeted_Admin extends Tweeted {
	
	/**
	 * Main Constructor Class
	 * @since 1.0.0
	 * @return none
	 * @constructor
	 */
	function __construct() {
		Tweeted::__construct();		

		//	Should we be upgrading the options?
		if ( version_compare( $this->get_option('version'), $this->version, '!=' ) && $this->get_option('version') !== false )
			$this->check_upgrade();
		
		//	Store Plugin Location Information
		$this->plugin_file = dirname( dirname ( __FILE__ ) ) . '/tweeted.php';
		$this->plugin_basename = plugin_basename( $this->plugin_file );
		
		//	Load Translations File
		load_plugin_textdomain( 'tweeted', false, $this->plugin_url() . '/locale' );

		//	Run this when installed or upgraded.
		register_activation_hook( $this->plugin_file , array(&$this, 'init') );

		//	Admin Actions
		add_action( 'admin_menu',               array(&$this, 'register_settings_page') 			);
		add_action( 'admin_init',				array(&$this, 'admin_init' ) 						);
	}

	/*** MAIN FUNCTIONS ***/
	
	/**
	 * Initialize 'Tweets from Twitter' Default Settings
	 * @since 1.0.0
	 * @return none
	 */
	function init() {
		if ( version_compare(PHP_VERSION, '5.0.0', '<') ) {
			deactivate_plugins( $this->plugin_file );
			wp_die( _("Sorry, Tweeted requires PHP 5+ or higher. Ask your host how to enable PHP 5 as the default on your server.") );
		}
		if ( !get_option('tweeted') )
			add_option('tweeted', $this->defaults());
		else
			$this->check_upgrade();
	}

	/**
	 * Regestration of the Setting Page
	 * @since 1.0.0
	 * @return none
	 */
	function register_settings_page() {
		$page = add_options_page( __('Tweeted'), __("Tweeted"), 'manage_options', 'tweeted', array(&$this, 'settings_page') );
		add_action('admin_print_styles-' . $page, array(&$this, 'admin_styles'));
	}

	/**
	 * Whitelist the 'twitter-tweets' options
	 * @since 1.0.0
	 * @return none
	 */
	function admin_init() {
		/** Register Style's **/
		wp_register_style( 'tweeted-layout', $this->plugin_url() . '/css/default-layout.css',	'',	'20100123' );
		
		$themes = apply_filters('tweeted_themes', $this->themes);
		foreach ($themes as $theme => $info)
			wp_register_style( 'tweeted-' . $theme,	$info['file'],		array('tweeted-layout'),	$info['time'] );
		
		register_setting( 'tweeted', 'tweeted', array(&$this , 'update') );
	}
	
	function admin_styles() {
		if ( $this->get_option('default_css') )
			wp_enqueue_style('tweeted-' . $this->get_option('theme'));
	}
	
	/*** OTHER FUNCTIONS ***/
	
	/**
	 * Check if an upgraded is needed
	 * @since 1.0.0
	 * @return none
	 */
	function check_upgrade () {
		if ( version_compare($this->get_option('version'), TWEETED_VERSION, '<') )
			$this->upgrade(TWEETED_VERSION);
	}

	/**
	 * Upgrade options
	 * @since 1.0.0
	 * @return none
	 */
	function upgrade($ver) {
		/*
		if ( $ver == '0.0.0' ) {
			$twitter_tweets = get_option('twitter_tweets');
			
		}
		*/
	}

	/**
	 * Return the default options
	 * @since 1.0.0
	 * @return array
	 */
	function defaults() {
		$defaults = array(
			'version' 		=> $this->version,
			'run_status'	=> 'on',
			'default_css'	=> 0,
			'theme'			=> 'dark',
			'show_props'	=> 1,
			'date_format'	=> 'M j, Y @ h:i A'
		);
		return $defaults;
	}
	
	/**
	 * Gets the default values.
	 * @since 1.0.0
	 * @param $value
	 * @return mixed
	 */
	function get_default($value) {
		$defaults = $this->defaults();
		return $defaults[$value];
	}
	
	/**
	 * Update/validate the options in the options table from the POST
	 * @since 1.0.0
	 * @return none
	 */
	function update($options) {
		if ( isset($options['delete']) && $options['delete'] == 'true' ) {
			delete_option('tweeted');
		} else if ( isset($options['default']) && $options['default'] == 'true' ) {
			return $this->defaults();
		} else {
			
			//	If the user leaves the date format blank, we are going back to the default settings.
			if ( empty($options['date_format']) )
				$options['date_format'] = $this->get_default('date_format');
			
			unset($options['delete'], $options['default']);
			return $options;
		}
	}

	/**
	 * Javascript needed for the Settings Page Only
	 * @since 1.0.0
	 * @return none
	 */
	function settings_page_js() {
	?>
	<?php
	}
	
	/**
	 * The setting page itself.
	 * @since 1.0.0
	 * @return none
	 */
	function settings_page() {
	
	/** Output any Javascript Required **/
	$this->settings_page_js();
	
	?>	
	<div class="wrap">
	<?php screen_icon(); ?>
		<h2><?php _e( 'Tweeted Settings' ); ?></h2>
			
		<form method="post" action="options.php">
		<?php settings_fields('tweeted'); ?>
		
		<input type="hidden" name="tweeted[version]" value="<?php echo $this->version; ?>" />
		<input type="hidden" name="tweeted[run_status]" value="on" />
		
		<h3><?php _e( 'Theme Settings' ); ?></h3>
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><?php _e( 'Default Theme' ); ?></th>
				<td>
					<select name="tweeted[theme]" id="tweeted_theme" class="postform">
					<?php
						$themes = apply_filters('tweeted_themes', $this->themes);
						foreach ( $themes as $theme => $info ) {
							echo '<option value="' . esc_attr($theme) . '"';
							selected( $this->get_option('theme'), $theme );
							echo '>' . htmlspecialchars($info['name']) . "</option>\n";
						}
					?>
					</select>
				<br/><?php _e("Select which theme you would like to use if you are not going override the CSS in your own file."); ?>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e( 'Custom CSS' ) ?></th>
				<td>
					<input type="checkbox" value="1"<?php checked('1', $this->get_option('default_css')); ?> id="tweeted_default_css" name="tweeted[default_css]"/> <label for="tweeted_default_css"><?php _e("Check this box if you want to use your own CSS."); ?></label>
				</td>
			</tr>
		</table>
		<br/>
		
		<h3><?php _e( 'Other Settings' ); ?></h3>
		<table class="form-table">
			<!--
			<tr valign="top">
				<th scope="row"><?php _e('Status'); ?></th>
				<td>
				<?php
					$run_statuss = array('on' => __("On"), 'off' => __('Off'));
					foreach ( $run_statuss as $key => $value) {
						$selected = ($this->get_option('run_status') == $key) ? 'checked="checked"' : '';
						echo "\n\t<label><input id='$key' type='radio' name='tweeted[run_status]' value='$key' $selected/> $value</label><br />";
					}
				?>
				<br/><?php _e("Turning the system off would remove the 'shortcode' and the URL inside of it, but keep it in the post in-case it is reactivated."); ?>
				</td>
			</tr>
			-->
			<tr valign="top">
				<th scope="row"><?php _e( 'Support Tweeted' ) ?></th>
				<td>
					<input type="checkbox" value="1"<?php checked('1', $this->get_option('show_props')); ?> id="tweeted_show_props" name="tweeted[show_props]"/> <label for="tweeted_show_props"><?php _e("We love link love, so if you want to support the folks that brought you Tweeted, please leave this checked. Thanks! .. and enjoy the plugin :)"); ?></label>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e( 'Date Format' ) ?></th>
				<td>
					<input type="text" value="<?php echo $this->get_option('date_format'); ?>" id="tweeted_date_format" name="tweeted[date_format]"/ ><br /> <label for="tweeted_date_format"><?php _e('Set this to the date and time format you want to use on the tweet output. <a href="http://codex.wordpress.org/Formatting_Date_and_Time" title="Documentation on date formatting">Documentation on date formatting.</a>'); ?></label>
				</td>
			</tr>
		</table>
		<br/>
		
		<h3><?php _e( 'Example' ); ?></h3>
		<table class="form-table">
			<tr valign="top">
				<td>
					[tweeted]http://twitter.com/wpvibe/status/12556071704[/tweeted]
				</td>
			</tr>
		</table>
		<br/>
			
		<p class="submit">
			<input type="submit" name="tweeted-submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
		</p>
		
		</form>
		
		<!--
		<h3><?php _e( 'Preview' ); ?></h3>
		<p><?php _e("Certain elements will not be correct because of this being in the 'admin area'. Be sure to view a post or page for an exact output."); ?></p>
		
		<?php 
		if ( !$this->get_option('default_css') ) {		
		?>
		<p><?php _e('Click &quot;Save Changes&quot; to update this preview.'); ?>
		<div style="background: rgb(223, 223, 223); padding: 40px;">
		<?php
		/** DO A PREVIEW OF WPVibe's LATEST POST **/
		$twitter_frontend = new Tweeted_Frontend(true);
		//echo $twitter_frontend->get_tweeted('', $this->get_last_twitter_post() );
		unset($twitter_frontend);
		?>
		</div>
		<?php		
		} else {
		?>
		<p><?php _e("Preview is unavailable at the moment.")?></p>
		<?php
		}
		?>
		-->
		
	</div>
	<?php
	}
	
	/**
	 * Get the last Twitter post
	 * @since 1.0.0
	 * @return string
	 */
	function get_last_twitter_post() {
	
		/** USER NAME **/	
		$username = 'WPVibe';
		
		/** Process RSS Feed **/
		$rss = fetch_feed('http://search.twitter.com/search.atom?q=from:'.$username.'&rpp=1');
		foreach ( $rss->get_items() as $item ) {
			$link = $item->get_link();
		}
		$rss->__destruct();
		unset($rss, $item);
		
		/** Echo the End Results **/
		return $link;
	}
	
}

?>