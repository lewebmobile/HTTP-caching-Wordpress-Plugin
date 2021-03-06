<?php
/*
Plugin Name: HTTP Caching
Plugin URI: https://github.com/lewebmobile/HTTP-caching-Wordpress-Plugin
Description: A plug-in that sends the proper HTTP caching information for wordpress pages and blog posts that have been marked as static.
Author: Dominique Hazael-Massieux
Version: 1.0
Author URI: http://lewebmobile.fr/
*/   

/*
 Based on wp plugin template at http://pressography.com/plugin_template.txt from Jason DeVelvis http://www.Pressography.com
*/
   
/*  Copyright 2010

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
* Guess the wp-content and plugin urls/paths
*/
// Pre-2.6 compatibility
if ( ! defined( 'WP_CONTENT_URL' ) )
      define( 'WP_CONTENT_URL', get_option( 'siteurl' ) . '/wp-content' );
if ( ! defined( 'WP_CONTENT_DIR' ) )
      define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
if ( ! defined( 'WP_PLUGIN_URL' ) )
      define( 'WP_PLUGIN_URL', WP_CONTENT_URL. '/plugins' );
if ( ! defined( 'WP_PLUGIN_DIR' ) )
      define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );


if (!class_exists('lwm_http_caching')) {
    class lwm_http_caching {
        //This is where the class variables go, don't forget to use @var to tell what they're for
        /**
        * @var string The options string name for this plugin
        */
        var $optionsName = 'lwm_http_caching_options';
        
        /**
        * @var string $localizationDomain Domain used for localization
        */
        var $localizationDomain = "lwm_http_caching";
        
        /**
        * @var string $pluginurl The path to this plugin
        */ 
        var $thispluginurl = '';
        /**
        * @var string $pluginurlpath The path to this plugin
        */
        var $thispluginpath = '';
            
        /**
        * @var array $options Stores the options for this plugin
        */
        var $options = array();
        
        //Class Functions
        /**
        * PHP 4 Compatible Constructor
        */
        function lwm_http_caching(){$this->__construct();}
        
        /**
        * PHP 5 Constructor
        */        
        function __construct(){
            //Language Setup
            $locale = get_locale();
            $mo = dirname(__FILE__) . "/languages/" . $this->localizationDomain . "-".$locale.".mo";
            load_textdomain($this->localizationDomain, $mo);

            //"Constants" setup
            $this->thispluginurl = PLUGIN_URL . '/' . dirname(plugin_basename(__FILE__)).'/';
            $this->thispluginpath = PLUGIN_PATH . '/' . dirname(plugin_basename(__FILE__)).'/';
            
            //Initialize the options
            //This is REQUIRED to initialize the options when the plugin is loaded!
            $this->getOptions();
            
            //Actions        
            add_action("admin_menu", array(&$this,"admin_menu_link"));
	    add_action("wp", array(&$this, "set_http_headers"));

            
        }
        
	        
	/**
        * Send proper last-modified header on keyword-marked pages
	*/        
	function set_http_headers() {
   	    global $post;
	    global $wp_query;
	    $post_status = get_post_meta($post->ID, $this->options["keyword"], true);
 	    if (($this->options["default"]=="apply" && !$post_status=="false") 
	         || $post_status == "true") {
		       // Inspired from http://svn.automattic.com/wordpress/tags/2.1/wp-includes/classes.php
		       // where it's only used for feeds		       
		       set_query_var("post_type", array("post,page"));
		       // this only works when patches in 
		       // http://core.trac.wordpress.org/ticket/14922
		       // have been applied
			$wp_last_modified_ts = max(get_lastcommentmodified('GMT'), get_lastpostmodified('GMT'));
			if ($wp_last_modified_ts) {
  			        $wp_last_modified = mysql2date('D, d M Y H:i:s', $wp_last_modified_ts, 0).' GMT';
			        @header("Last-Modified: $wp_last_modified");
                        }

			// Support for Conditional GET

			$client_last_modified = trim( $_SERVER['HTTP_IF_MODIFIED_SINCE']);
			// If string is empty, return 0. If not, attempt to parse into a timestamp
			$client_modified_timestamp = $client_last_modified ? strtotime($client_last_modified) : 0;

			if ( $wp_last_modified_ts && $client_modified_timestamp >= $wp_last_modified_ts ) {
				status_header( 304 );
				exit;
			}

            }
	}


        /**
        * Retrieves the plugin options from the database.
        * @return array
        */
        function getOptions() {
            //Don't forget to set up the default options
            if (!$theOptions = get_option($this->optionsName)) {
                $theOptions = array('default'=>'dontapply',
				    'keyword'=>'static');
                update_option($this->optionsName, $theOptions);
            }
            $this->options = $theOptions;
            
            //!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
            //There is no return here, because you should use the $this->options variable!!!
            //!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
        }
        /**
        * Saves the admin options to the database.
        */
        function saveAdminOptions(){
            return update_option($this->optionsName, $this->options);
        }
        
        /**
        * @desc Adds the options subpanel
        */
        function admin_menu_link() {
            //If you change this from add_options_page, MAKE SURE you change the filter_plugin_actions function (below) to
            //reflect the page filename (ie - options-general.php) of the page your plugin is under!
            add_options_page('HTTP Caching', 'HTTP Caching', 10, basename(__FILE__), array(&$this,'admin_options_page'));
            add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array(&$this, 'filter_plugin_actions'), 10, 2 );
        }
        
        /**
        * @desc Adds the Settings link to the plugin activate/deactivate page
        */
        function filter_plugin_actions($links, $file) {
           //If your plugin is under a different top-level menu than Settiongs (IE - you changed the function above to something other than add_options_page)
           //Then you're going to want to change options-general.php below to the name of your top-level page
           $settings_link = '<a href="options-general.php?page=' . basename(__FILE__) . '">' . __('Settings') . '</a>';
           array_unshift( $links, $settings_link ); // before other links

           return $links;
        }
        
        /**
        * Adds settings/options page
        */
        function admin_options_page() { 
            if($_POST['lwm_http_caching_save']){
                if (! wp_verify_nonce($_POST['_wpnonce'], 'lwm_http_caching-update-options') ) die('Whoops! There was a problem with the data you posted. Please go back and try again.'); 
                $this->options['default'] = $_POST['lwm_http_caching_default'];                   
                $this->options['keyword'] = $_POST['lwm_http_caching_keyword'];
                                        
                $this->saveAdminOptions();
                
                echo '<div class="updated"><p>';
		_e("Success! Your changes were sucessfully saved!", $this->localizationDomain);
		echo '</p></div>';
            }
?>                                   
                <div class="wrap">
                <h2><?php _e("HTTP Caching", $this->localizationDomain);?></h2>
                <form method="post" id="lwm_http_caching_options">
                <?php wp_nonce_field('lwm_http_caching-update-options'); ?>
		<p><?php _e("This plugin makes Wordpress send information to browsers, so that they don’t reload needlessly a page if it hasn’t changed since the last time they’ve visited it — this is particularly usefull for mobile browsers on which network connectivitity can be problematic.", $this->localizationDomain);?></p>
		<p><?php _e("This shouldn’t be used on posts and pages that load content not managed by Wordpress; the configuration options below allow to define which pages should be excluded from the realm of this plugin.", $this->localizationDomain);?></p>

		<fieldset>
	       <p><label for='lwm_http_caching_keyword'><?php _e("Custom field used to mark pages/posts where to apply the plugin or not", $this->localizationDomain);?>: <input type="text" name="lwm_http_caching_keyword" value="<?php echo $this->options['lwm_http_caching_keyword'];?>" id="lwm_http_caching_keyword"/></label></p>

               <label for='lwm_http_caching_default_dont'><input name="lwm_http_caching_default" type="radio" id="lwm_http_caching_default_dont" value="dontapply" <?php if ($this->options['lwm_http_caching_default']=='dontapply') { echo " checked='checked'";} ?>"/> <?php _e('Don’t apply the plugin to any posts and pages, except those on which the custom field is set to "true"', $this->localizationDomain);?></label><br />
	       <label for='lwm_http_caching_default_do'><input name="lwm_http_caching_default" type="radio" id="lwm_http_caching_default_do" value="apply" <?php if ($this->options['lwm_http_caching_default']=='apply') { echo " checked='checked'";}?>"/> <?php _e('Apply to all posts and pages, except those on which the custom field is set to "false"', $this->localizationDomain);?></label>
	       </fieldset>

	       <p><input type="submit" name="lwm_http_caching_save" value="<?php _e('Save', $this->localizationDomain);?>" /></p>
                </form>
                <?php
        }
        
       
        
  } //End Class
} //End if class exists statement

//instantiate the class
if (class_exists('lwm_http_caching')) {
    $lwm_http_caching_var = new lwm_http_caching();
}
?>