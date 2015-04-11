<?php
/*
* Plugin Name: Captcha Login
* Plugin URI: https://github.com/johnie/captcha-login
* Description: WordPress plugin to add No Captcha reCaptcha to login page
* Version: 1.0.0
* Author: Johnie Hjelm
* Author URI: http://johnie.se
* License: MIT
*/

/*
Copyright 2015 Johnie Hjelm <johniehjelm@me.com> (http://johnie.se)

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.

*/

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'CaptchaLogin' ) ) {

  class CaptchaLogin {

    private static $instance;

    /**
     * Tag identifier used by file includes and selector attributes.
     * @var string
     */

    public $tag;

    /**
     * User friendly name used to identify the plugin.
     * @var string
     */

    public $name;

    /**
     * Description of the plugin.
     * @var string
     */

    public $description;

    /**
     * Current version of the plugin.
     * @var string
     */

    public $version;

    /**
     * Plugin loader instance.
     *
     * @since 1.0.0
     *
     * @return object
     */

    public static function instance() {
      if ( ! isset( self::$instance ) ) {
        self::$instance = new static;
        self::$instance->setup_globals();
        self::$instance->setup_actions();
      }

      return self::$instance;
    }

    /**
     * Initiate the plugin by setting the default values and assigning any
     * required actions and filters.
     *
     * @access private
     */

    private function setup_actions() {

  		add_action( "admin_menu", array( $this, "captcha_login_menu" ) );
  		add_action( "admin_init", array( $this, "display_captcha_options" ) );

			add_action( "login_enqueue_scripts", array( $this, "captcha_login_scripts") );
			add_action( "login_form", array( $this, "display_captcha_login") );

			add_filter( "wp_authenticate_user", array( $this, "verify_captcha_login" ), 10, 2 );

			add_action( "register_form", array( $this, "display_register_captcha" ) );
			add_filter( "registration_errors", array( $this, "verify_registration_captcha" ), 10, 3 );

			add_action( "lostpassword_form", array( $this, "display_login_captcha" ) );
			add_action( "lostpassword_post", array( $this, "verify_lostpassword_captcha" ) );

    }

    /**
 		 * Initiate the globals
 		 *
     * @access private
 		 */

    private function setup_globals() {
      $this->tag = 'captchalogin';
      $this->name = 'Captcha Login';
      $this->description = 'WordPress plugin to add No Captcha reCaptcha to login page';
      $this->version = '1.0.0';
    }


    /**
     * Add menu page
     */

    function captcha_login_menu() {
    	add_submenu_page( 'options-general.php', $this->name, $this->name, "manage_options", $this->tag, array( $this, "captcha_login_page" ) );
    }


    /**
     * Options page
     */

    function captcha_login_page() {
		  ?>
	    <div class="wrap">
	      <h2><?php echo $this->name; ?></h2>
	      <form method="post" action="options.php">
					<?php
						settings_fields( "header_section" );
						do_settings_sections( $this->tag );
						submit_button();
					?>
			  </form>
		  </div>
		  <?php
		}


		/**
		 * Captcha Login options
		 */

		function display_captcha_options() {
	    add_settings_section( "header_section", "Keys", array( $this, "display_captcha_content" ), $this->tag );

	    add_settings_field( "captcha_site_key", __("Site Key"), array( $this, "display_captcha_site_key_element" ), $this->tag, "header_section" );
	    add_settings_field( "captcha_secret_key", __("Secret Key"), array( $this, "display_captcha_secret_key_element" ), $this->tag, "header_section" );

	    register_setting( "header_section", "captcha_site_key" );
	    register_setting( "header_section", "captcha_secret_key" );
		}


		/**
		 * Captcha Login content
		 */

		function display_captcha_content() {
			if ( get_option('captcha_site_key') == '' && get_option('captcha_secret_key') == '' ) {
	    	echo __( '<p>You need to <a href="https://www.google.com/recaptcha/admin" rel="external">register you domain</a> and get keys to make this plugin work.</p>' );
	  	}
	    echo __( "Enter the key details below" );
		}


		/**
		 * Site key element
		 */

		function display_captcha_site_key_element() {
    	?>
      	<input type="text" name="captcha_site_key" id="captcha_site_key" value="<?php echo get_option('captcha_site_key'); ?>" class="regular-text" />
    	<?php
		}


		/**
		 * Secret key element
		 */

		function display_captcha_secret_key_element() {
	    ?>
				<input type="text" name="captcha_secret_key" id="captcha_secret_key" value="<?php echo get_option('captcha_secret_key'); ?>" class="regular-text" />
	    <?php
		}


		/**
		 * Enqueue scripts for login
		 */

		function captcha_login_scripts() {
			if( get_option( 'captcha_site_key' ) && get_option( 'captcha_secret_key' ) ) {
				wp_register_script( "captcha_login", "//www.google.com/recaptcha/api.js" );
        wp_enqueue_script( "captcha_login" );
			}

			?>
				<style>
					#login{
						width: 350px;
					}
					.g-recaptcha{
						margin-bottom: 20px;
					}
				</style>
			<?php
		}


		/**
		 * Display Captcha on login page
		 */

		function display_captcha_login() {
			if( get_option( 'captcha_site_key' ) && get_option( 'captcha_secret_key' ) ) {
				?>
					<div class="g-recaptcha" data-sitekey="<?php echo get_option('captcha_site_key' ); ?>"></div>
				<?php
			}
		}


		/**
		 * Verify Captcha Login
		 */

		function verify_captcha_login( $user, $password ) {
		  if( isset( $_POST['g-recaptcha-response'] ) ) {
		    $recaptcha_secret = get_option( 'captcha_secret_key' );
		    $response = file_get_contents( "https://www.google.com/recaptcha/api/siteverify?secret=" . $recaptcha_secret . "&response=" . $_POST['g-recaptcha-response'] );
		    $response = json_decode( $response, true );
		    if( true == $response["success"] ) {
					return $user;
		    } else {
		      return new WP_Error( "Captcha Invalid", __( "<strong>ERROR</strong>: You are a bot" ) );
		    }
		  } else {
		    if( get_option( 'captcha_site_key' ) && get_option( 'captcha_secret_key' ) ) {
		      return new WP_Error( "Captcha Invalid", __( "<strong>ERROR</strong>: You are a bot. If not then enable JavaScript" ) );
		    } else {
		      return $user;
		    }
		  }
		}


		/**
		 * Display Captcha Login on registration
		 */

		function display_register_captcha() {
		  if( get_option( 'captcha_site_key' ) && get_option( 'captcha_secret_key' ) ) {
		    ?>
		      <div class="g-recaptcha" data-sitekey="<?php echo get_option( 'captcha_site_key' ); ?>"></div>
		    <?php
		  }
		}


		/**
		 * Verify Captcha on registration
		 */

		function verify_registration_captcha( $errors, $sanitized_user_login, $user_email ) {
		  if( isset( $_POST['g-recaptcha-response'] ) ) {
		    $recaptcha_secret = get_option( 'captcha_secret_key' );
		    $response = file_get_contents( "https://www.google.com/recaptcha/api/siteverify?secret=" . $recaptcha_secret . "&response=" . $_POST['g-recaptcha-response'] );
		    $response = json_decode( $response, true );
		    if( true == $response["success"] ) {
		      return $errors;
		    } else {
		      $errors->add( "Captcha Invalid", __( "<strong>ERROR</strong>: You are a bot" ) );
		    }
		  } else {
		    if( get_option( 'captcha_site_key' ) && get_option( 'captcha_secret_key' ) ) {
		      $errors->add( "Captcha Invalid", __( "<strong>ERROR</strong>: You are a bot. If not then enable JavaScript" ) );
		    } else {
		      return $errors;
		    }
		  }

		  return $errors;
		}


		/**
		 * Add Captcha Login on forgot password
		 */

		function verify_lostpassword_captcha() {
		  if( isset( $_POST['g-recaptcha-response'] ) ) {
		    $recaptcha_secret = get_option( 'captcha_secret_key' );
		    $response = file_get_contents( "https://www.google.com/recaptcha/api/siteverify?secret=" . $recaptcha_secret . "&response=" . $_POST['g-recaptcha-response'] );
		    $response = json_decode( $response, true );
		    if( true == $response["success"] ) {
		      return;
		    } else {
		      wp_die( __( "<strong>ERROR</strong>: You are a bot" ) );
		    }
		  } else {
		    if( get_option( 'captcha_site_key' ) && get_option( 'captcha_secret_key' ) ) {
		      wp_die( __( "<strong>ERROR</strong>: You are a bot. If not then enable JavaScript" ) );
		    } else {
		      return;
		    }

		  }

		  return $errors;
		}

  }

}

if ( !function_exists( 'captchalogin' ) ) {
  function captchalogin() {
    return CaptchaLogin::instance();
  }
}

add_action( 'plugins_loaded', 'captchalogin' );
