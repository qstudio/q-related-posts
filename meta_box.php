<?php

class Q_Related_Posts_Admin_Meta_Box {
    
    public function __construct() 
    {
            
        add_action( 'save_post', array( $this, 'save_post' ) );
        add_action( 'admin_menu', array( $this, 'add_meta_box' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
        add_action( 'wp_ajax_qrp_search_posts', array( $this, 'qrp_search_posts' ) );
        #add_action( 'wp_ajax_qrp_reset_related_posts', array( $this, 'qrp_reset_related_posts' ) );

    }
	
   
    public function save_post( $post_id ) 
    {
        
        // If this is just a revision, don't save ##
	if ( wp_is_post_revision( $post_id ) ) {
            return;
        }
        
        if ( isset( $_POST['q_related_posts'] ) && is_array( $_POST['q_related_posts'] ) ) {
            
            update_post_meta( $post_id, 'q_related_posts', $_POST['q_related_posts'] );
            
        }
        
    }

    public function admin_enqueue_scripts() 
    {
        
        wp_register_style( 'sipr-admin-css', QRP_PLUGIN_URL . '/css/q-related-posts.css', array(), date('YmdHis', filemtime(QRP_PLUGIN_DIR . '/css/q-related-posts.css')) );
        wp_enqueue_style( 'sipr-admin-css' );
        wp_register_script( 'sipr-admin-post-js', QRP_PLUGIN_URL . '/javascript/admin-post.js', array(), date('YmdHis', filemtime(QRP_PLUGIN_DIR . '/javascript/admin-post.js')) );
        wp_enqueue_script( 'sipr-admin-post-js' );
        wp_register_script( 'sipr-color-js', QRP_PLUGIN_URL . '/javascript/jquery.color.js', array(), date('YmdHis', filemtime(QRP_PLUGIN_DIR . '/javascript/jquery.color.js')) );
        wp_enqueue_script( 'sipr-color-js' );
        wp_enqueue_script( 'jquery-ui-sortable' );
        wp_localize_script( 'sipr-admin-post-js', 'objectL10n', array(
            'alert'         => __( 'Maximum number of Related Posts %d', QRP_DOMAIN ),
            'max_posts'     => 5, // @todo - allow this to be a setting ##
            'post_id'       => get_the_ID()
        ) );
                
    }
    
    public function qrp_reset_related_posts() 
    {
        
        global $q_related_posts, $post;
        if ( ! isset( $_POST['post_id'] ) || ! is_numeric( $_POST['post_id'] ) ) {
            pr( "kicked 1.." );
            return;
        }

        $post = get_post( $_POST['post_id'] );
        $results = $q_related_posts->get_data_original();	
        $json = array();
        $cnt  = 0;

        if ( empty( $results ) ) {
            pr( "kicked 2.." );
            return;
        }

        foreach ( $results as $id ) {
            $json[$cnt]['ID'] = $id['ID'];
            $json[$cnt]['post_title'] = get_the_title($id['ID']);
            $image = get_the_post_thumbnail( $id['ID'], array(21, 21) );
            $json[$cnt]['post_thumbnail'] = !empty($image) ? $image : '';
            $json[$cnt]['permalink'] = get_permalink($id['ID']);
            $cnt++;
        }
        echo json_encode($json);
        exit;
        
    }
    
    public function qrp_search_posts() 
    {
        
    	if ( ! isset( $_POST['s'] ) ) {
            return;
        }
		
        $resutls = $this->search($_POST['s']);
        if ( empty( $resutls ) ) {
            return;
        }

        $json = array();
        $cnt  = 0;
        foreach ( $resutls as $ret ) {
            $json[$cnt]['ID'] = $ret->ID;
            $json[$cnt]['post_title'] = get_the_title($ret->ID);
            $image = get_the_post_thumbnail( $ret->ID, array(21, 21) );
            $json[$cnt]['post_thumbnail'] = !empty($image) ? $image : '';
            $json[$cnt]['permalink'] = get_permalink($ret->ID);
            $cnt++;
        }
        echo json_encode($json);
        exit;
        
    }
    
    
    public function search( $s = null ) 
    {
        
        // sanity check ##
        if ( is_null( $s ) ) { return false; }
        
        // bring global class into scope ##
        global $wpdb;

        // prepare SQL ##
        $sql = $wpdb->prepare( 
            "
                SELECT SQL_CALC_FOUND_ROWS ID 
                FROM {$wpdb->posts} 
                WHERE 
                    ( ( ( post_title LIKE '%%%s%%' ) OR ( post_content LIKE '%%%s%%' ) ) ) 
                    AND ( ( ( wp_posts.post_type = 'post' ) OR ( wp_posts.post_type = 'page' ) ) ) 
                    AND post_status = 'publish' 
                ORDER BY post_title LIKE '%%%s%%' DESC, post_date DESC 
                LIMIT 0, 10
            "
            , 
                $s
            ,   $s
            ,   $s 
        );

        // return results ##
        return $wpdb->get_results( $sql );

    }
	
    public function add_meta_box() 
    {
        
        // posts ##
        add_meta_box( 'q-related-posts', __( 'Related Posts', QRP_DOMAIN ), array( $this, 'meta_box' ), 'post', 'advanced', 'low' );

        // pages ##
        add_meta_box( 'q-related-posts', __( 'Related Posts', QRP_DOMAIN ), array( $this, 'meta_box' ), 'page', 'advanced', 'low' );
            
    }
	
    public function meta_box() 
    {
        
        #global $q_related_posts;
				
?>
    <div class="qrp_relationship" >
	<!-- Left List -->
	<div class="relationship_left">
            <table class="widefat">
                <thead>
                    <tr>
                        <th>
                            <input class="relationship_search" placeholder="<?php _e("Search...",QRP_DOMAIN); ?>" type="text" />
                        </th>
                    </tr>
                </thead>
            </table>
            <ul class="relationship_list">
                <li class="load-more">
                </li>
            </ul>
	</div>
	<!-- /Left List -->
	
	<!-- Right List -->
	<div class="relationship_right">
            <!--<h3><?php #_e( "Related Posts:", QRP_DOMAIN ); ?></h3>
            <input type="button" id="sirp-reset" class="button-secondary" value="<?php #_e( 'Reset', QRP_DOMAIN ); ?>" />-->
            <ul class="bl relationship_list">
<?php
			
            $related_posts = Q_Related_Posts::get_data();
			
            if ( !empty($related_posts) ) {
                foreach( $related_posts as $p ) {
                    $image = get_the_post_thumbnail( $p['ID'], array(21, 21) );

                    $title = ''; 
                    if ( !empty($image) )
                        $title .= '<div class="result-thumbnail">' . $image . '</div>';

                    $title .= get_the_title($p['ID']);

                    echo '<li>
                        <a href="' . get_permalink($p['ID']) . '" class="" data-post_id="' . $p['ID'] . '"><span class="title">' . $title . '</span><span class="sirp-button"></span></a>
                    </li>';					
                }	
            }		
		
?>
		</ul>
	</div>
	<!-- / Right List -->
	
</div>
<?php

    }
	
}

new Q_Related_Posts_Admin_Meta_Box();