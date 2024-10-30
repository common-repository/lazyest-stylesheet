<?php
/**
 * Removes the Lazyest Stylesheet permanent stylesheet
 */

// If uninstall/delete not called from WordPress then exit
if( ! defined ( 'ABSPATH' ) && ! defined ( 'WP_UNINSTALL_PLUGIN' ) )
	exit ();

$uploads = wp_upload_dir(); 
$styles = array( 'stylesheet', 'mobile' );
foreach( $styles as $style ) {
	$style_file =  str_replace( '\\', '/', trailingslashit($uploads['basedir']) . "lazyest-$style.css" );	
	echo "Removing $style_file<br />";
	@unlink( $style_file );
}	 
delete_option( 'lazyest-stylesheet' );

  
?>