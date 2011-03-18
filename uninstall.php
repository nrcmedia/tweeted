<?php
/**
 * Uninstalls the Tweeted options when an uninstall has been requested 
 * from the WordPress admin. Only can be done from Admin Area!
 *
 * @package tweeted
 * @subpackage uninstall
 * @since 1.0.0
 */

// If uninstall/delete not called from WordPress then exit
if( ! defined ( 'ABSPATH' ) && ! defined ( 'WP_UNINSTALL_PLUGIN' ) )
	exit ();

// Delete shadowbox option from options table
delete_option ( 'tweeted' );

?>