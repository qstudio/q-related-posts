<?php 

/**
 * Plugin Name:     Q Related Posts
 * Plugin URI:      http://qstudio.us/
 * Description:     Quick loading, simple related content
 * Version:         0.1
 * Author:          Q Studio
 * Author URI:      http://qstudio.us
 * License:         GPL2
 * Class:           Q_Related_Posts
 * Text Domain:     q-related-posts
 */

/**
 * Based on http://www.kakunin-pl.us/
 */

defined( 'ABSPATH' ) OR exit;

if ( ! class_exists( 'Q_Related_Posts' ) ) {
    
    // register widget ##
    add_action( 'widgets_init', 'register_q_related_posts_widget', 0 );
    function register_q_related_posts_widget() {
        register_widget( 'Q_Related_Posts' );
    }
    
    if ( ! defined( 'QRP_DOMAIN' ) ) {
        define( 'QRP_DOMAIN', 'q-related-posts' );
    }

    if ( ! defined( 'QRP_PLUGIN_URL' ) ) {
        define( 'QRP_PLUGIN_URL', plugins_url() . '/' . dirname( plugin_basename( __FILE__ ) ));
    }

    if ( ! defined( 'QRP_PLUGIN_DIR' ) ) {
        define( 'QRP_PLUGIN_DIR', WP_PLUGIN_DIR . '/' . dirname( plugin_basename( __FILE__ ) ));
    }
    
    // Define class - extend WP_Widget ##
    class Q_Related_Posts extends WP_Widget
    {
        
        // Refers to a single instance of this class. ##
        private static $instance = null;
        
        // widget settings ##
        protected static $settings = '';

        const version = '0.1';
        const text_domain = 'q-related-posts'; // for translation ##

        /**
         * Creates or returns an instance of this class.
         *
         * @return  Foo     A single instance of this class.
         */
        public static function get_instance() 
        {

            if ( null == self::$instance ) {
                self::$instance = new self;
            }

            return self::$instance;

        }
        
        public function __construct() 
        {

            // set text domain ##
            add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

            // install routine ##
            #add_action( 'admin_init', array( $this, 'install' ) );

            // widget ##
            parent::__construct(
                'q-related-posts', // Base ID
                __( 'Q Related Posts', self::text_domain ), // Name
                array( 'description' => __( 'Display selected related posts', self::text_domain ), ) // Args
            );

            // load plugin libraries ##
            self::requirements();
            
            // front-end filtering and assets ##
            if ( ! is_admin() ) {
                
                // filter title ##
                add_filter( 'qrp_before_post_title', array( __CLASS__, 'qrp_default_before_post_title' ), 10, 2 );
                
            }
            
        }


        private function requirements() 
        {

            require_once( QRP_PLUGIN_DIR . '/meta_box.php' );
            
        }


        /**
         * Load Text Domain for translations
         * 
         * @since       0.1
         * 
         */
        public function load_plugin_textdomain() 
        {

            // set text-domain ##
            $domain = self::text_domain;

            // The "plugin_locale" filter is also used in load_plugin_textdomain()
            $locale = apply_filters('plugin_locale', get_locale(), $domain);

            // try from global WP location first ##
            load_textdomain( $domain, WP_LANG_DIR.'/plugins/'.$domain.'-'.$locale.'.mo' );

            // try from plugin last ##
            load_plugin_textdomain( $domain, FALSE, plugin_dir_path( __FILE__ ).'languages/' );

        }


        /**
         * WP Enqueue Scripts - on the front-end of the site
         * 
         * @since       0.1
         * @return      void
         */
        /*
        public function wp_enqueue_scripts() {

            // Register the script ##
            wp_register_script( 'q-related-posts-js', self::get_plugin_url( 'javascript/q-related-posts.js' ), array( 'jquery' ), self::version, true );

            // Now we can localize the script with our data.
            $translation_array = array( 
                    'ajax_nonce'    => wp_create_nonce( 'q_mos_nonce' )
                ,   'ajax_url'      => get_home_url( '', 'wp-admin/admin-ajax.php' )
                ,   'saved'         => __( "Student Saved", self::text_domain )
                ,   'error'         => __( "Error", self::text_domain )
            );
            wp_localize_script( 'q-related-posts-js', 'q_mos', $translation_array );

            // enqueue the script ##
            wp_enqueue_script( 'q-related-posts-js' );

            wp_register_style( 'q-related-posts-css', self::get_plugin_url( 'css/q-related-posts.css' ) );
            wp_enqueue_style( 'q-related-posts-css' );

        }
        */

        /**
         * Get Plugin URL
         * 
         * @since       0.1
         * @param       string      $path   Path to plugin directory
         * @return      string      Absoulte URL to plugin directory
         */
        public static function get_plugin_url( $path = '' ) 
        {

            return plugins_url( ltrim( $path, '/' ), __FILE__ );

        }


        /**
         * Front-end display of widget.
         *
         * @see WP_Widget::widget()
         *
         * @param array $args     Widget arguments.
         * @param array $instance Saved values from database.
         */
        public function widget( $args, $instance ) 
        {

            // get widget settings ##
            $title = $instance['title'] ? $instance['title'] : false;
            self::$settings["title"] = apply_filters( 'widget_qrp_title', $title );
            
            $limit = $instance['limit'] ? $instance['limit'] : 5;
            self::$settings["limit"] = apply_filters( 'widget_qrp_limit', $limit );
            
            $thumbnail = $instance['thumbnail'] ? $instance['thumbnail'] : 1;
            self::$settings["thumbnail"] = apply_filters( 'widget_qrp_thumbnail', $thumbnail );

            // check if widget settings ok ##
            if ( isset( self::$settings ) && array_filter( self::$settings ) ) {
                
                #pr( self::$settings );
                
                // build related posts widget ##
                self::render();

            } // setting ok ##

        }


        /**
         * Back-end widget form.
         *
         * @see WP_Widget::form()
         *
         * @param array $instance Previously saved values from database.
         */
        public function form( $instance ) 
        {

            $title = isset( $instance[ 'title' ] ) ? $instance[ 'title' ] : __( 'Related Posts', self::text_domain ) ;
            $limit = isset( $instance[ 'limit' ] ) ? $instance[ 'limit' ] : 5 ;
            $thumbnail = isset( $instance[ 'thumbnail' ] ) ? $instance[ 'thumbnail' ] : 1 ;

    ?>
            <p>
                <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', self::text_domain ); ?></label> 
                <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'limit' ); ?>"><?php _e( 'Number of posts:', self::text_domain ); ?></label> 
                <input class="widefat" id="<?php echo $this->get_field_id( 'limit' ); ?>" name="<?php echo $this->get_field_name( 'limit' ); ?>" type="text" value="<?php echo esc_attr( $limit ); ?>">
            </p>
            <p>
                <input id="<?php echo $this->get_field_id('thumbnail'); ?>" name="<?php echo $this->get_field_name('thumbnail'); ?>" type="checkbox" value="1" <?php checked( '1', $thumbnail ); ?> />
                <label for="<?php echo $this->get_field_id('thumbnail'); ?>"><?php _e( 'Show Thumbnail:', self::text_domain ); ?></label>
            </p>
    <?php 
        }


        /**
         * Sanitize widget form values as they are saved.
         *
         * @see WP_Widget::update()
         *
         * @param array $new_instance Values just sent to be saved.
         * @param array $old_instance Previously saved values from database.
         *
         * @return array Updated safe values to be saved.
         */
        public function update( $new_instance, $old_instance ) 
        {

            $instance = array();
            $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '' ;
            $instance['limit'] = ( ! empty( $new_instance['limit'] ) ) ? intval( $new_instance['limit'] ) : 5 ;
            $instance['thumbnail'] = ( ! empty( $new_instance['thumbnail'] ) ) ? strip_tags( $new_instance['thumbnail'] ) : 1 ;
            return $instance;

        }


        public static function render()
        {
            
            echo self::get_related_posts();
            
        }
        
        
        public static function get_related_posts() 
        {

            $posts = self::get_data();
            if ( ! $posts ) {
                #pr( "kicked 1" );
                return false;
            }

            $html = '<h2 class="q-related-posts-title">' . apply_filters( 'qrp_title' , __( 'Related Posts', QRP_DOMAIN ) ) . '</h2><ul class="q-related-posts">';
            foreach( $posts as $post ) {
                $html .= '<li><a href="' . get_permalink( $post['ID'] ) . '" >';
                $html .= apply_filters( 'qrp_before_post_title', '', $post['ID'] );
                $html .= apply_filters( 'qrp_post_title', '<p class="title">' . get_the_title($post['ID']) . '</p>', $post['ID'] );
                $html .= apply_filters( 'qrp_after_post_title', '', $post['ID'] );
                $html .= '</a></li>';
            }
            $html .= '</ul>';

            return $html;

        }
        
        
        public static function qrp_default_before_post_title( $html, $post_id ) 
        {

            #pr( $post_id );
            $options = get_option( 'qrp_options' );
            if ( ! isset( $options['post_thumbnail'] ) || $options['post_thumbnail'] != 1 ) {
                return $html;
            }

            if ( has_post_thumbnail( $post_id ) ) {

                $attachment_id = get_post_thumbnail_id( $post_id );
                $image_attributes = wp_get_attachment_image_src( $attachment_id, 'thumbnail' );
                $html .= '<p class="thumb">' . get_the_post_thumbnail( $post_id, 'thumbnail' ). '</p>';

            }

            return $html; 

        }
        

        public static function get_data() 
        {

            $related_posts = self::get_data_post_meta( get_the_ID() );

            if ( ! $related_posts ) {
                
                #pr( "kicked 2" );
                return false;
                
            }

            if ( is_array( $related_posts ) ) {

                $related_posts = array_unique( $related_posts, SORT_REGULAR );

            }

            return $related_posts;

        }

        public static function get_data_post_meta( $posts_id = null ) 
        {

            if ( ! isset( $post_id ) ) {

                global $post;

                $post_id = $post->ID;

            }

            if ( empty( $post_id ) ) {

                #pr( "kicked 3" );
                return false;

            }

            $posts = get_post_meta( $post_id, 'q_related_posts', true );

            if ( ! $posts ) {
                
                #pr( "kicked 4" );
                return false;
                
            }

            $posts_ids = array();
            foreach ( $posts as $id ) {
                $posts_ids[]['ID'] = $id;
            }
            #wp_die( pr( self::$settings ) );
            $current_num = count( $posts );
            $display_num = intval( isset( self::$settings["limit"] ) ? self::$settings["limit"] : 10 );

            if ( $current_num > $display_num ) {
                $num = $current_num - $display_num;
                for ( $i = 0; $i < $num; $i++ ) {
                    array_pop( $posts_ids );
                }
                return $posts_ids;
            } else {
                return $posts_ids;
            }	

        }

    }

}
