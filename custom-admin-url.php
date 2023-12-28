<?php
/**
* Plugin Name: Custom Admin URL Name Change 
* Description: Change the URL link of Admin
* Author: Alex C
* Version: 1.0.1 (tested on WP 6.4)
**/

if ( defined( 'ABSPATH' )
    && ! class_exists( 'Secure_Login' ) ) {

    class Secure_Login {
      
      // Class variables
        private $wp_login_php;

      // Helper function to get the plugin basename
        private function basename() {
            return plugin_basename( __FILE__ );
        }
        
      // Helper function to get the plugin directory path
        private function path() {
            return trailingslashit( dirname( __FILE__ ) );
        }

      // Helper function to check if trailing slashes should be used in URLs
        private function use_trailing_slashes() {
            return ( '/' === substr( get_option( 'permalink_structure' ), -1, 1 ) );
        }

      // Helper function to handle trailing slashes in URLs
        private function user_trailingslashit( $string ) {
            return $this->use_trailing_slashes()
                ? trailingslashit( $string )
                : untrailingslashit( $string );
        }

      // Template loader function to redirect to custom login URL
        private function wp_template_loader() {
            global $pagenow;
            $pagenow = 'index.php';
            if ( ! defined( 'WP_USE_THEMES' ) ) {
                define( 'WP_USE_THEMES', true );
            }
          
            wp();
          
            if ( $_SERVER['REQUEST_URI'] === $this->user_trailingslashit( str_repeat( '-/', 10 ) ) ) {
                $_SERVER['REQUEST_URI'] = $this->user_trailingslashit( '/wp-login-php/' );
            }
            require_once( ABSPATH . WPINC . '/template-loader.php' );
            die;
        }

      // Helper function to get the new login slug
        private function new_login_slug() {
            if ( ( $slug = get_option( 'rwl_page' ) )
                || ( is_multisite()
                    && is_plugin_active_for_network( $this->basename() )
                    && ( $slug = get_site_option( 'rwl_page', 'login' ) ) )
                || ( $slug = 'login' ) ) {
                return $slug;
            }
        }

      // Helper function to generate the new login URL
          public function new_login_url( $scheme = null ) {
            if ( get_option( 'permalink_structure' ) ) {
                return $this->user_trailingslashit( home_url( '/', $scheme ) . $this->new_login_slug() );
            }
            else {
                return home_url( '/', $scheme ) . '?' . $this->new_login_slug();
            }
        }

      // Constructor function to initialize the plugin        
          public function __construct() {
            global $wp_version;
            
            register_activation_hook( $this->basename(), array( $this, 'activate' ) );
            register_uninstall_hook( $this->basename(), array( 'Secure_Login', 'uninstall' ) );
            add_action( 'admin_init', array( $this, 'admin_init' ) );
            add_action( 'admin_notices', array( $this, 'admin_notices' ) );
            add_action( 'network_admin_notices', array( $this, 'admin_notices' ) );

            if ( is_multisite()
                && ! function_exists( 'is_plugin_active_for_network' ) ) {
                require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
            }

            add_filter( 'plugin_action_links_' . $this->basename(), array( $this, 'plugin_action_links' ) );

            if ( is_multisite()
                && is_plugin_active_for_network( $this->basename() ) ) {
                add_filter( 'network_admin_plugin_action_links_' . $this->basename(), array( $this, 'plugin_action_links' ) );
                add_action( 'wpmu_options', array( $this, 'wpmu_options' ) );
                add_action( 'update_wpmu_options', array( $this, 'update_wpmu_options' ) );
            }

            add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ), 1 );
            add_action( 'wp_loaded', array( $this, 'wp_loaded' ) );
            add_filter( 'site_url', array( $this, 'site_url' ), 10, 4 );
            add_filter( 'network_site_url', array( $this, 'network_site_url' ), 10, 3 );
            add_filter( 'wp_redirect', array( $this, 'wp_redirect' ), 10, 2 );
            add_filter( 'site_option_welcome_email', array( $this, 'welcome_email' ) );
            remove_action( 'template_redirect', 'wp_redirect_admin_locations', 1000 );
        }

      // Constructor function to initialize the plugin        
        public function activate() {
            add_option( 'rwl_redirect', '1' );
            delete_option( 'rwl_admin' );
        }

      // Uninstall function to remove options on plugin uninstallation      
        public static function uninstall() {
            global $wpdb;

            if ( is_multisite() ) {
                $blogs = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs}" );
                    if ( $blogs ) {
                        foreach( $blogs as $blog ) {
                            switch_to_blog( $blog );
                            delete_option( 'rwl_page' );
                        }
                        restore_current_blog();
                    }
                delete_site_option( 'rwl_page' );
            }

            else {
                delete_option( 'rwl_page' );
            }
        }

      // Multisite options page for setting networkwide default login URL
        public function wpmu_options() {

            $out = '';

            $out .= '<h3>Secure login location</h3>';
            $out .= '<p>This option allows you to set a networkwide default, which can be overridden by individual sites. Simply go to to the site’s permalink settings to change the url.</p>';
            $out .= '<table class="form-table">';
                $out .= '<tr valign="top">';
                    $out .= '<th scope="row">Networkwide default</th>';
                    $out .= '<td><input id="rwl-page-input" type="text" name="rwl_page" value="' . get_site_option( 'rwl_page', 'login' )  . '"></td>';
                $out .= '</tr>';
            $out .= '</table>';

            echo $out;

        }

      // Multisite options page for setting networkwide default login URL        
          public function update_wpmu_options() {

            if ( ( $rwl_page = sanitize_title_with_dashes( $_POST['rwl_page'] ) )
                && strpos( $rwl_page, 'wp-login' ) === false
                && ! in_array( $rwl_page, $this->forbidden_slugs() ) ) {

                update_site_option( 'rwl_page', $rwl_page );
            }
        }

      // Admin initialization function        
      public function admin_init() {
            global $pagenow;

            add_settings_section(
                'secure-login-section',
                'Admin url change',
                array( $this, 'rwl_section_desc' ),
                'permalink'
            );

            add_settings_field(
                'rwl-page',
                '<label for="rwl-page">Login url</label>',
                array( $this, 'rwl_page_input' ),
                'permalink',
                'secure-login-section'
            );

            if ( isset( $_POST['rwl_page'] )
                && $pagenow === 'options-permalink.php' ) {

                if ( ( $rwl_page = sanitize_title_with_dashes( $_POST['rwl_page'] ) )
                    && strpos( $rwl_page, 'wp-login' ) === false
                    && ! in_array( $rwl_page, $this->forbidden_slugs() ) ) {

                    if ( is_multisite() && $rwl_page === get_site_option( 'rwl_page', 'login' ) ) {
                        delete_option( 'rwl_page' );
                    }
                    else {
                        update_option( 'rwl_page', $rwl_page );
                    }
                }
            }

            if ( get_option( 'rwl_redirect' ) ) {
                delete_option( 'rwl_redirect' );
                if ( is_multisite()
                    && is_super_admin()
                    && is_plugin_active_for_network( $this->basename() ) ) {
                    $redirect = network_admin_url( 'settings.php#rwl-page-input' );
                }

                else {
                    $redirect = admin_url( 'options-permalink.php#rwl-page-input' );
                }
                wp_safe_redirect( $redirect );
                die;
            }
        }

        public function rwl_section_desc() {
            $out = '';
            if ( ! is_multisite()
                || is_super_admin() ) {
                $out .= '';
            }

            if ( is_multisite()
                && is_super_admin()
                && is_plugin_active_for_network( $this->basename() ) ) {
                $out .= '<p>To set a networkwide default, go to <a href="' . network_admin_url( 'settings.php#rwl-page-input' ) . '">Network Settings</a>.</p>';
            }
            echo $out;
        }

        public function rwl_page_input() {
            if ( get_option( 'permalink_structure' ) ) {
                echo '<code>' . trailingslashit( home_url() ) . '</code> <input id="rwl-page-input" type="text" name="rwl_page" value="' . $this->new_login_slug()  . '">' . ( $this->use_trailing_slashes() ? ' <code>/</code>' : '' );
            }
            else {
                echo '<code>' . trailingslashit( home_url() ) . '?</code> <input id="rwl-page-input" type="text" name="rwl_page" value="' . $this->new_login_slug()  . '">';
            }
        }

      // Display notices in the admin area
        public function admin_notices() {
            global $pagenow;
            $out = '';
            if ( ! is_network_admin()
                && $pagenow === 'options-permalink.php'
                && isset( $_GET['settings-updated'] ) ) {
                echo '<div class="updated"><p>Your login page is now here: <strong><a href="' . $this->new_login_url() . '">' . $this->new_login_url() . '</a></strong>. Bookmark this page!</p></div>';
            }
        }

      // Add settings link in the plugins list
        public function plugin_action_links( $links ) {
            if ( is_network_admin()
                && is_plugin_active_for_network( $this->basename() ) ) {
                array_unshift( $links, '<a href="' . network_admin_url( 'settings.php#rwl-page-input' ) . '">Settings</a>' );
            }
            elseif ( ! is_network_admin() ) {
                array_unshift( $links, '<a href="' . admin_url( 'options-permalink.php#rwl-page-input' ) . '">Settings</a>' );
            }
            return $links;
        }

      // Load necessary actions and filters
        public function plugins_loaded() {
            global $pagenow;
            if ( ! is_multisite()
                && ( strpos( $_SERVER['REQUEST_URI'], 'wp-signup' )  !== false
                    || strpos( $_SERVER['REQUEST_URI'], 'wp-activate' ) )  !== false ) {
                wp_die( __( 'This feature is not enabled.' ) );
            }

            $request = parse_url( $_SERVER['REQUEST_URI'] );

            if ( ( strpos( $_SERVER['REQUEST_URI'], 'wp-login.php' ) !== false
                    || untrailingslashit( $request['path'] ) === site_url( 'wp-login', 'relative' ) )
                && ! is_admin() ) {
                $this->wp_login_php = true;
                $_SERVER['REQUEST_URI'] = $this->user_trailingslashit( '/' . str_repeat( '-/', 10 ) );
                $pagenow = 'index.php';
            }

            elseif ( untrailingslashit( $request['path'] ) === home_url( $this->new_login_slug(), 'relative' )
                || ( ! get_option( 'permalink_structure' )
                    && isset( $_GET[$this->new_login_slug()] )
                    && empty( $_GET[$this->new_login_slug()] ) ) ) {
                $pagenow = 'wp-login.php';
            }
        }

      // Load necessary actions after WordPress is fully loaded
        public function wp_loaded() {

            global $pagenow;

            if ( is_admin()
                && ! is_user_logged_in()
                && ! defined( 'DOING_AJAX' ) ) {

                wp_die( __( 'You must log in to access the admin area.' ) );

            }

            $request = parse_url( $_SERVER['REQUEST_URI'] );

            if ( $pagenow === 'wp-login.php'
                && $request['path'] !== $this->user_trailingslashit( $request['path'] )
                && get_option( 'permalink_structure' ) ) {

                wp_safe_redirect( $this->user_trailingslashit( $this->new_login_url() )
                    . ( ! empty( $_SERVER['QUERY_STRING'] ) ? '?' . $_SERVER['QUERY_STRING'] : '' ) );

                die;
            }

            elseif ( $this->wp_login_php ) {

                if ( ( $referer = wp_get_referer() )
                    && strpos( $referer, 'wp-activate.php' ) !== false
                    && ( $referer = parse_url( $referer ) )
                    && ! empty( $referer['query'] ) ) {

                    parse_str( $referer['query'], $referer );

                    if ( ! empty( $referer['key'] )
                        && ( $result = wpmu_activate_signup( $referer['key'] ) )
                        && is_wp_error( $result )
                        && ( $result->get_error_code() === 'already_active'
                            || $result->get_error_code() === 'blog_taken' ) ) {

                        wp_safe_redirect( $this->new_login_url()
                            . ( ! empty( $_SERVER['QUERY_STRING'] ) ? '?' . $_SERVER['QUERY_STRING'] : '' ) );
                        die;
                    }

                }
                $this->wp_template_loader();
            }

            elseif ( $pagenow === 'wp-login.php' ) {
                global $error, $interim_login, $action, $user_login;
                @require_once ABSPATH . 'wp-login.php';
                die;
            }
        }

      // Filter site URL to redirect wp-login.php requests
        public function site_url( $url, $path, $scheme, $blog_id ) {
            return $this->filter_wp_login_php( $url, $scheme );
        }

      // Filter network site URL to redirect wp-login.php requests
        public function network_site_url( $url, $path, $scheme ) {
            return $this->filter_wp_login_php( $url, $scheme );
        }

      // Filter wp_redirect to redirect wp-login.php requests
        public function wp_redirect( $location, $status ) {
            return $this->filter_wp_login_php( $location );
        }

        public function filter_wp_login_php( $url, $scheme = null ) {
            if ( strpos( $url, 'wp-login.php' ) !== false ) {
                if ( is_ssl() ) {
                    $scheme = 'https';
                }

                $args = explode( '?', $url );
                if ( isset( $args[1] ) ) {
                    parse_str( $args[1], $args );
                    $url = add_query_arg( $args, $this->new_login_url( $scheme ) );
                }

                else {
                    $url = $this->new_login_url( $scheme );
                }
            }
            return $url;
        }

      // Filter wp-login.php URLs in welcome emails
        public function welcome_email( $value ) {
            return $value = str_replace( 'wp-login.php', trailingslashit( get_site_option( 'rwl_page', 'login' ) ), $value );
        }

      // Get forbidden slugs used by WordPress
        public function forbidden_slugs() {
            $wp = new WP;
            return array_merge( $wp->public_query_vars, $wp->private_query_vars );
        }
    }
  
// Initialize the Secure_Login class
    new Secure_Login;
}
