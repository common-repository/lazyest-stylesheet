<?php

/*

Plugin Name: Lazyest Stylesheet

Plugin URI: http://brimosoft.nl/lazyest/stylesheet/

Version: 1.2.0

Description: Lazyest Stylesheet is a CSS editor that lets you customize your site design without modifying your theme or plugins.

Data: 2014, May

Author: Brimosoft

Author URI: http://www.brimosoft.nl/

License: GPL2+

Domain Path: /languages/

*/



/**

 * Lazyest_Stylesheet

 * 

 * @package Lazyest Stylesheet  

 * @author Marcel Brinkkemper, Andre de Waard

 * @copyright 2010-2013 Brimosoft

 * @version 1.1.0

 * @access public

 */

class Lazyest_Stylesheet {



    /**

	 * @staticvar $instance Lazyest_Stylesheet object

	 */ 

    private static $instance;



    /**

	 * @var $style_url url for the stylesheet

	 */

    var $style_url;



    /**

	 * @var $style_file file path for the stylesheet

	 */

    var $style_file;



    /**

	 * @var $options plugin options

	 */

    var $options;



    /**

   * Lazyest_Stylesheet::__construct()

	 * Dummy constructor

	 *  

   */

    function __construct() {}



    /**

	 * Lazyest_Stylesheet::instance()

	 * Return single instance of this class

	 * 

	 * @return Lazyest_Stylesheet

	 */

    public static function instance() {

        if ( ! isset( self::$instance ) ) {

            self::$instance = new Lazyest_Stylesheet;

            self::$instance->init();

        }

        return self::$instance;

    }



    /**

   * Lazyest_Stylesheet::init()

   * All initializations.

   * 

   * @return void

   */

    private function init() {

        $this->load_textdomain();

        $this->load_options();

        $this->actions();

        $this->filters();

        $this->style_file();

    }



    /**

   * Lazyest_Stylesheet::load_textdomain()

   * Load text domain for gettext

   * 

   * @uses load_plugin_textdomain()

   * @return void

   */

    function load_textdomain() {

        load_plugin_textdomain( 'lazyest-stylesheet', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );   	

    }



    /**

   * Lazyest_Stylesheet::load_options()

   * Load options from wpdb or load defaults

   * 

   * @uses get_option()

   * @uses update_option() if defaulkts loaded store options

   * @return void

   */

    function load_options() {

        if ( $options = get_option( 'lazyest-stylesheet' ) ) {

            $this->options = $options;

        } else {

            $this->options = $this->defaults();

            update_option( 'lazyest-stylesheet', $this->options  );

        }

    }



    /**

   * Lazyest_Stylesheet::defaults()

   * Default options.

   * 

   * @return array

   */

    private function defaults() {

        return array(

            'both-stylesheet' => false,

            'no_mobile'  => false,

            'plain_text' => false,

        );

    }



    /**

   * Lazyest_Stylesheet::actions()

   * Add WordPress action hooks.

   * 

   * @uses add_action()

   * @return void

   */

    function actions() {      

        // set 9000 to add stylesheet below other stylesheets

        add_action( 'wp_head',                         array( $this, 'stylesheet'     ), 9000    );

        add_action( 'admin_menu',                      array( $this, 'add_page'       ),  200    );

        add_action( 'admin_bar_menu',                  array( $this, 'admin_bar_menu' ),  100    );    

        add_action( 'admin_notices',                   array( $this, 'admin_notices'  )          );		

        add_action( 'admin_action_lazyest_stylesheet', array( $this, 'save_changes'   )          );

    }



    /**

   * Lazyest_Stylesheet::filters()

   * Add WordPress filters.

   * 

   * @uses add_filter()

   * @return void

   */

    function filters() {     

        add_filter( 'plugin_action_links', array( $this, 'plugin_actions' ), 10, 2 );  	

    }



    /**

   * Lazyest_Stylesheet::style_file()

   * Check if stylesheets exist and if not, create them in upload directory.

   * 

   * @uses wp_upload_dir()

   * @uses trailingslashit()

   * @return void

   */

    private function style_file() {

        $uploads = wp_upload_dir();

        $styles = array( 

            'stylesheet', 

            'mobile',

        );

        foreach( $styles as $style ) {

            $this->style_file[$style] = str_replace( '\\', '/', trailingslashit($uploads['basedir']) . "lazyest-$style.css" );

            $this->style_url[$style]  = trailingslashit( $uploads['baseurl'] ) . "lazyest-$style.css";			

            if ( ! file_exists( $this->style_file[$style] ) ) {

                $handle = fopen( $this->style_file[$style], 'w+' );

                if ( false !== $handle ) {

                    fwrite( $handle, 

                           "/* lazyest-$style

Thank you for using Lazyest Stylesheet. 

Enter your style rule changes below.

*/

" );

                    fclose( $handle );

                }    	

            }

        }    

    }





    /**

   * Lazyest_Stylesheet::add_page()

   * Add stylesheet editor page under themes.php ( this is the Appearance menu )

   * Add action hooks for syntax highlighter if requested.

   * 

   * @uses add_submenu_page()

   * @uses add_action()

   * @since 0.1.0

   * @return void

   */

    function add_page() {

        $submenu = add_submenu_page( 'themes.php', 

                                    __( 'Lazyest Stylesheet', 'lazyest-stylesheet' ), __( 'Lazyest Stylesheet', 'lazyest-stylesheet' ), 'edit_themes', 'lazyest-stylesheet-editor',  array( $this, 'editor_page') );

        add_action( "admin_print_styles-{$submenu}", array( &$this, 'manager_css' ) );



        if ( ! isset( $this->options['plain_text'] ) || ! $this->options['plain_text'] ) {

            // Jetpack uses the same syntax highlighter. If Jetpack is activated,enqueue Jetpack javascript to prevent conflicts.

            if ( class_exists( 'Jetpack_Custom_CSS' ) ) { 

                add_action( "admin_print_scripts-$submenu", array( 'Jetpack_Custom_CSS', 'enqueue_scripts' ) );

                add_action( "admin_head-$submenu", array( 'Jetpack_Custom_CSS', 'admin_head' ) );

            } else {

                add_action( "admin_print_scripts-$submenu", array( $this, 'enqueue_scripts' ) );

                add_action( "admin_head-$submenu", array( $this, 'admin_head' ) );	

            }

        }



    }



    /**

 	 * Lazyest_Stylesheet::admin_head()

 	 * Style rules and script for the syntax highlighter.

 	 * 

 	 * @return void

 	 */

    function admin_head() {

?>

<style type="text/css">

    #safecssform {

        position: relative;

    }



    #poststuff {

        padding-top: 0;

    }



    #safecss {

        min-height: 250px;

        width: 100%;

    }



    #safecss-container {

        position: relative;

        width: 99.5%;

        height: 400px;

        border: 1px solid #dfdfdf;

        border-radius: 3px;

    }



    #safecss-container .ace_editor {

        font-family: Consolas, Monaco, Courier, monospace;

    }



    #safecss-ace {

        width: 100%;

        height: 100%;

        display: none; /* Hide on load otherwise it looks weird */

    }



    #safecss-ace.ace_editor {

        display: block;

    }



    #safecss-container .ace-tm .ace_gutter {

        background-color: #ededed;

    }

    #post-body {

        margin-right: -2000px!important;

    }

</style>

<script type="text/javascript">

    /*<![CDATA[*/

    var safecssResize, safecssInit;



    ( function ( $ ) {

        var safe, win;



        safecssResize = function () {

            safe.height( win.height() - safe.offset().top - 250 );

        };



        safecssInit = function() {

            safe = $('#safecss');

            win  = $(window);

            safecssResize();

        };



        window.onresize = safecssResize;

        addLoadEvent( safecssInit );

    } )( jQuery );



    /*]]>*/

</script>

<?php

    }



    /**

	 * Lazyest_Stylesheet::enqueue_scripts()

	 * Enqueue syntax highlighter scripts.

	 * 

	 * @uses wp_enqueue_script()

	 * @uses plugins_url()

	 * @return void

	 */

    function enqueue_scripts() {

        wp_enqueue_script( 'postbox' );



        $url = plugins_url( 'js/', __FILE__ );

        wp_enqueue_script( 'jquery.spin' );

        wp_enqueue_script( 'safecss-ace', $url . 'ace/ace.js',          array(),                              '1.0.0', true );

        wp_enqueue_script( 'safecss-ace-css', $url . 'ace/mode-css.js', array( 'safecss-ace' ),               '1.0.0', true );

        wp_enqueue_script( 'safecss-ace-use', $url . 'safecss-ace.js',  array( 'jquery', 'safecss-ace-css' ), '1.0.0', true );		

    }



    /**

   * Lazyest_Stylesheet::admin_bar_menu()

   * SWhow Lazyest Stylesheet in Admin Bar Menu.

   * 

   * @uses current_user_can() to checkj if user can edit themes.

   * @uses admin_url()

   * @return void

   */

    function admin_bar_menu() {

        global $wp_admin_bar;

        if ( current_user_can( 'edit_themes') )

            $wp_admin_bar->add_menu( array( 'parent' => 'appearance', 'id' => 'lazyest_stylesheet', 'title' => __( 'Lazyest Styleheet', 'lazyest-stylesheet' ), 'href' => admin_url( 'themes.php?page=lazyest-stylesheet-editor' ) ) );						

    }



    /**

   * Lazyest_Stylesheet::admin_notices()

   * Notices after updating the file.

   * 

   * @uses get_current_screen() to display notices for lazyest-stylesheet screen only

   * @return void

   */

    function admin_notices() {

        if ( 'appearance_page_lazyest-stylesheet-editor' == get_current_screen()->id && isset( $_GET['updated'] ) ) {

            if ( 'true' == $_GET['updated'] ) {

                echo '<div id="message" class="updated"><p><strong>' . __( 'Succesfully saved your stylesheet', 'lazyest-stylesheet' ) . '</strong></p></div>';

            } else {				

                echo '<div id="message" class="error"><p>' . __( 'Could not save your stylesheet', 'lazyest-stylesheet' ) . '</p></div>';

            }

        }  	

    }



    /**

   * Lazyest_Stylesheet::plugin_actions()

   * 

   * @param array $links

   * @param string $file

   * @return void

   */

    function plugin_actions( $links, $file ) {

        if ( $file == plugin_basename( __FILE__ ) ) {

            $links[] = '<a href="' . add_query_arg( array('page' => 'lazyest-stylesheet-editor' ), admin_url( 'themes.php' ) ) . '">' . esc_html__( 'Stylesheet', 'lazyest-stylesheet' ) . '</a>';

        }

        return $links;

    }



    /**

   * Lazyest_Stylesheet::manager_css()

   * enqueue WordPress theme-editor stylesheet

	 *  

   * @return void

   * @uses wp_enqueue_style()

   */

    function manager_css() {

        wp_enqueue_style( 'theme-editor' );

    }



    /**

   * Lazyest_Stylesheet::save_changes()

   * Save options and changes to the stylesheet.

   * 

   * @uses wp_verify_nonce() to validate request

	 * @uses wp_die() when validate fails

	 * @uses get_option() to load old options

	 * @uses update_option() to store new options

	 * @uses add_query_arg() to build lazyest-stylesheet url

	 * @uses wp_get_referer() to get referring lazyest-stylesheet url

   * @return void

   */

    function save_changes() {          

        $nonce=$_REQUEST['_wpnonce'];

        if ( !wp_verify_nonce( $nonce, 'ls_edit_stylesheet' ) ) 

            wp_die( __( 'Security check', 'lazyest-stylesheet' ) );



        $options = get_option( 'lazyest-stylesheet' );   

        $options['both-stylesheet']  = isset( $_POST['lazyest-stylesheet']['both-stylesheet'] )  ? 1 : 0;

        $options['no_mobile']  = isset( $_POST['lazyest-stylesheet']['no_mobile'] )  ? 1 : 0;

        $options['plain_text'] = isset( $_POST['lazyest-stylesheet']['plain_text'] ) ? 1 : 0;

        update_option( 'lazyest-stylesheet', $options );



        $newcontent = stripslashes( $_POST['safecss'] );



        $style =  ( isset( $_POST['style'] ) && $_POST['style'] == 'mobile' ) ? 'mobile' : 'stylesheet';

        $style_file = $this->style_file[$style];



        if ( is_writeable( $style_file ) ) {

            $handle = @fopen( $style_file, 'w+' );

            if ( $handle ) {

                fwrite( $handle, $newcontent );

                fclose( $handle );

                $updated = 'true';

            }

        } else {

            $updated = 'false';	

        }

        $url = add_query_arg( array( 'updated' => $updated ), wp_get_referer() );

        wp_redirect( $url );

        exit();

    }



    /**

   * Lazyest_Stylesheet::editor_page()

   * Display the stylesheet editor page

   * 

   * @uses current_user_can()

   * @uses wp_die()

   * @uses wp_nonce_field()

   * @uses admin_url()

   * @since 0.1.0

   * @return void

   */

    function editor_page() {

        global $lg_gallery;

        if ( !current_user_can( 'edit_themes' ) ) {      

            wp_die( __( 'You do not have permissions to edit themes.', 'lazyest-stylesheet' ) );

        }      

        $style =  ( isset( $_REQUEST['style'] ) && $_REQUEST['style'] == 'mobile' ) ? 'mobile' : 'stylesheet';

        $style_file = $this->style_file[$style];

        $message = '';

        $handle = @fopen( $style_file, 'r' );

        $content = false;

        if ( $handle ) {    	

            $content = filesize( $style_file ) ? @fread( $handle, filesize( $style_file ) ) : ' ';

            @fclose( $handle ); 		 

            if ( $content !== false ) {

                $content = htmlspecialchars( $content );

                $success = true;

            }

        } else {

            $success = false;

        }

        $title = ( is_writeable( $style_file ) ) ? __( 'Edit Lazyest Stylesheet', 'lazyest-stylesheet' ) :__( 'Browse Lazyest Stylesheet', 'lazyest-stylesheet' ); 				

?>

<div class="wrap">

    <div id="icon-themes" class="icon32"></div>      	

    <h2><?php echo $title; ?></h2>

    <?php if ( ! $success ) : ?>

    <div id="no-stylesheet" class="error">

        <p><?php _e( 'Lazyest Stylesheet cannot open your stylesheet', 'lazyest-stylesheet' ); ?></p>

    </div>

    <?php endif; ?> 		

    <?php if ( $success ) : ?>



    <form name="safecssform" id="safecssform" method="post" action="admin.php">	

        <input type="hidden" name="action" value="lazyest_stylesheet" />

        <?php if ( isset( $_REQUEST['style'] ) && $_REQUEST['style'] == 'mobile' ) : ?>

        <input type="hidden" name="style" value="mobile" />

        <?php endif; ?> 

        <?php wp_nonce_field( 'ls_edit_stylesheet' );  ?>

        <h3><?php echo __('Permanent Stylesheet', 'lazyest-stylesheet' ); ?> <span><?php echo "lazyest-$style.css" ?></span></h3>

        <div id="poststuff" class="metabox-holder has-right-sidebar">

            <div id="postbox-container-1" class="inner-sidebar">

                <div id="side-sortables" class="meta-box-sortables ui-sortable">

                    <div id="submitdiv" class="postbox">

                        <div class="handlediv" title="Click to toggle"><br /></div>

                        <h3 class="hndle"><span><?php _e( 'Publish', 'lazyest-stylesheet') ?></span></h3>

                        <div class="inside">

                            <div id="minor-publishing">

                                <div id="misc-publishing-actions">

                                    <div class="misc-pub-section">

                                        <p><strong><?php _e( 'Stylesheets:', 'lazyest-stylehseet' ) ?></strong></p>												

                                        <ul>

                                            <li>

                                                <a href="<?php echo admin_url('themes.php?page=lazyest-stylesheet-editor') ?>"><?php esc_html_e( 'Standard', 'lazyest-stylesheet' ); ?></a><br />

                                                <span class="nonessential">(lazyest-stylesheet.css)</span>

                                            </li>

                                            <?php if ( $this->options['both-stylesheet'] == '1' )  :?>

                                            <li>

                                                <a href="<?php echo admin_url('themes.php?page=lazyest-stylesheet-editor&amp;style=mobile') ?>"><?php esc_html_e( 'Mobile', 'lazyest-stylesheet' ); ?></a><br />

                                                <span class="nonessential">(lazyest-mobile.css)</span>

                                            </li>

                                            <?php endif; ?>

                                        </ul>

                                    </div>

                                    <div class="misc-pub-section">

                                        <p><strong><?php _e( 'Options:', 'lazyest-stylehseet' ) ?></strong></p>

                                        <p>

                                            <label>

                                                <input type="checkbox" name="lazyest-stylesheet[both-stylesheet]" value="1" <?php checked( '1', isset( $this->options['both-stylesheet'] ) ? $this->options['both-stylesheet'] : 0 ); ?> /> <?php esc_html_e( 'Use different stylesheet for mobile.', 'lazyest-stylesheet' ); ?>

                                            </label>

                                        </p>

                                        <p>

                                            <label>

                                                <input type="checkbox" name="lazyest-stylesheet[no_mobile]" value="1" <?php checked( '1', isset( $this->options['no_mobile'] ) ? $this->options['no_mobile'] : 0 ); ?> /> <?php esc_html_e( 'Disable on mobile devices', 'lazyest-stylesheet' ); ?>

                                            </label>

                                        </p>

                                        <p>

                                            <label>

                                                <input type="checkbox" name="lazyest-stylesheet[plain_text]" value="1" <?php checked( '1', isset( $this->options['plain_text'] ) ? $this->options['plain_text'] : 0 ); ?> /> <?php esc_html_e( 'Disable syntax highlighting', 'lazyest-stylesheet' ); ?>

                                            </label>

                                        </p>

                                    </div>

                                </div>

                            </div>

                            <div id="major-publishing-actions">

                                <input type="button" class="button" id="preview" name="preview" value="Preview" style="display:none" />

                                <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=MSWK36DKJ7GW4" title="<?php _e( 'Support the development of Lazyest Stylesheet', 'lazyest-stylesheet' ); ?>">

                                    <img src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif" alt="PayPal - The safer, easier way to pay online!" width="92" height="26" />

                                </a>

                                <div id="publishing-action">

                                    <?php if (is_writeable( $style_file ) ) : ?>

                                    <input class="button-primary" type="submit" name="submit" value="<?php _e( 'Save Changes', 'lazyest-stylesheet' ); ?>" />

                                    <?php else : ?>

                                    <em><?php _e( 'If this file were writable you could edit it.', 'lazyest-stylesheet' ); ?></em>

                                    <?php endif; ?>

                                    <br class="clear"/>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

            <div id="post-body">

                <div id="post-body-content">

                    <div class="postarea">

                        <?php if ( $this->options['plain_text'] ) : ?>

                        <style type="text/css">

                            #post-body {

                                margin-right: -2000px!important;

                            }

                        </style>

                        <div id="template">    

                            <textarea cols="80" rows="25" name="safecss" id="newcontent" tabindex="1" style="width:100%;"><?php echo $content ?></textarea>

                        </div>

                        <?php else : ?>	

                        <div>  

                            <div id="safecss-container">

                                <div id="safecss-ace"></div>

                            </div>  

                            <textarea id="safecss" name="safecss" class="hide-if-js"><?php echo $content ?></textarea>

                        </div>

                        <?php endif; ?>

                    </div>

                </div>

            </div>

        </div>

    </form>

    <?php endif; ?>

</div>

<?php     

    } 



    /**

   * Lazyest_Stylesheet::stylesheet()

   * Add link to the permanent stylesheet

   * 

   * @since 0.1.0

   * @uses wp_is_mobile to check if site is opened on a mobile browser

   * @return void

   */

    function stylesheet() {

        /* check if user selected to show stylesheet on all media props andredewaard */
        $mobile_browser = wp_is_mobile() &&  $this->options['both-stylesheet'];

        $is_mobile = apply_filters( 'lazyest-stylesheet-mobile-browser', $mobile_browser );

        if ( isset( $this->options['no_mobile'] ) && ( $this->options['no_mobile'] == '1' ) && ( false !== $is_mobile ) )

            return;

        $style = $is_mobile ? 'mobile' : 'stylesheet';	

        $style_file = $this->style_url[$style];        

        echo  "\n<link rel=\"stylesheet\" type=\"text/css\" href=\"$style_file\" />\n";  

    }



} // Lazyest_Stylesheet



function lazyest_stylesheet() {

    return Lazyest_Stylesheet::instance();

}



lazyest_stylesheet();
