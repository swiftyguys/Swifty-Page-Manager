<?php
/*
Plugin Name: Swifty Page Manager
Description: Swifty Page Manager
Author: SwiftyGuys
Version: 0.0.2
Author URI: http://swiftysite.com/
Plugin URI: https://bitbucket.org/swiftyguys/SwiftyPageManager
*/

class SwiftyPageManager
{
    protected $plugin_file;
    protected $plugin_dir;
    protected $plugin_basename;
    protected $plugin_dir_url;
    protected $plugin_url;
    protected $_plugin_version = '0.0.2';
    protected $_post_status = 'any';
    protected $_post_type = 'page';
    protected $_tree = null;
    protected $_byPageId = null;
    protected $_mainMenuItems = null;
    protected $is_swifty;   // TEMP!!!

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->plugin_file     = __FILE__ ;
        $this->plugin_dir      = dirname( $this->plugin_file );
        $this->plugin_basename = basename( $this->plugin_dir );
        $this->plugin_dir_url  = plugins_url( rawurlencode( basename( $this->plugin_dir ) ) );
        $this->plugin_url      = $_SERVER[ 'REQUEST_URI' ];
        $this->is_swifty       = true;   // TEMP!!!

        if ( !empty( $_GET[ "status" ] ) ) {
            $this->_post_status = $_GET[ "status" ];
        }

        if ( !empty( $_GET[ "post_type" ] ) ) {
            $this->_post_type = $_GET[ "post_type" ];
        }

        add_action( 'init',       array( $this, 'spm_load_textdomain' ) );
        add_action( 'admin_head', array( $this, 'admin_head' ) );
        add_action( 'admin_menu', array( $this, 'admin_menu') );
        add_action( 'wp_ajax_spm_get_childs',    array( $this, 'ajax_get_childs' ) );
        add_action( 'wp_ajax_spm_move_page',     array( $this, 'ajax_move_page' ) );
        add_action( 'wp_ajax_spm_save_page',     array( $this, 'ajax_save_page' ) );
        add_action( 'wp_ajax_spm_delete_page',   array( $this, 'ajax_delete_page' ) );
        add_action( 'wp_ajax_spm_publish_page',  array( $this, 'ajax_publish_page' ) );
        add_action( 'wp_ajax_spm_post_settings', array( $this, 'ajax_post_settings' ) );
        add_action( 'admin_enqueue_scripts',     array( $this, 'add_plugin_css' ) );

        if ( $this->is_swifty ) {
            add_action( 'wp_ajax_spm_sanitize_url', array( $this, 'ajax_sanitize_url' ) );
            add_action( 'parse_request',       array( $this, 'parse_request' ) );
            add_action( 'save_post',           array( $this, 'restore_page_status' ), 10, 2 );
            add_filter( 'wp_insert_post_data', array( $this, 'set_tmp_page_status' ), 10, 2 );
            add_filter( 'page_link',           array( $this, 'page_link' ), 10, 2 );
            add_filter( 'wp_list_pages',       array( $this, 'wp_list_pages' ) );
            add_filter( 'status_header',       array( $this, 'status_header' ) );
            add_filter( 'wp_title',            array( $this, 'seo_wp_title' ), 10, 2 );
        }
    }

    /**
     * Adds the plugin css to the head tag.
     */
    function add_plugin_css()
    {
        wp_enqueue_style( "spm", $this->plugin_dir_url . "/css/styles.css", false, $this->_plugin_version );
    }

    /**
     * @param string $title
     * @param string $sep
     * @return string
     */
    function seo_wp_title( $title, $sep )
    {
        if ( is_feed() ) {
            return $title;
        }

        $seoTitle = get_post_meta( get_the_ID(), 'spm_page_title_seo', true );

        if ( !empty( $seoTitle ) ) {
            return "$seoTitle $sep ";
        }

        return $title;
    }

    /**
     * @param array $data
     * @param array $postarr
     * @return array
     */
    public function set_tmp_page_status( $data, $postarr )
    {
        // Only do this when creating a page.
        if ( $data['post_type']   === 'page'  &&
             $data['post_status'] === 'draft' &&
             empty( $postarr['ID'] )
        ) {
            $data['post_status'] = '__TMP__';
        }

        return $data;
    }

    /**
     * @param integer $post_id
     * @param WP_Post $post
     */
    public function restore_page_status( $post_id, $post ) {
        /** @var wpdb $wpdb - Wordpress Database */
        global $wpdb;

        // Check it's not an auto save routine
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( !wp_is_post_revision( $post_id ) &&
              $post->post_type   === 'page'   &&
              $post->post_status === '__TMP__'
        ) {
            $wpdb->query( $wpdb->prepare( "UPDATE $wpdb->posts SET post_status = '%s' WHERE id = %d",
                                          $_POST[ "post_status" ],
                                          $post_id ) );
        }
    }

    /**
     * Action function to make our overridden URLs work by changing the query params.
     *
     * @param wp $wp - WordPress object
     */
    public function parse_request( &$wp ) {
        /** @var wpdb $wpdb - Wordpress Database */
        global $wpdb;

        if ( !empty($wp->request) ) {
            $query = $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key='spm_url' AND meta_value='%s'",
                                     $wp->request );

            $post_id = $wpdb->get_var( $query );

            if ( $post_id ) {
                $wp->query_vars = array( 'p' => $post_id, 'post_type' => 'page' );
            }
        }
    }

    /**
     * Filter function called when the link to a page is needed.
     * We return our custom URL if it has been set.
     *
     * @param string $link
     * @param bool|integer $post_id
     * @return string
     */
    public function page_link( /** @noinspection PhpUnusedParameterInspection */ $link, $post_id=false ) {
        $spm_url = get_post_meta( $post_id, 'spm_url', true );

        if ( $spm_url ) {
            $link = get_site_url( null, $spm_url );
        } else {
            $post = get_post( $post_id );

            // Hack: get_page_link() would return ugly permalink for drafts, so we will fake that our post is published.
            if ( in_array( $post->post_status, array( 'draft', 'pending' ) ) ) {
                $post->post_status = 'publish';
                $post->post_name = sanitize_title( $post->post_name ? $post->post_name : $post->post_title, $post->ID );
            }

            // If calling get_page_link inside page_link action, unhook this function so it doesn't loop infinitely
            remove_filter( 'page_link', array( $this, 'page_link' ) );

            $link = get_page_link( $post );

            // Re-hook this function
            add_filter( 'page_link', array( $this, 'page_link' ), 10, 2 );
        }

        return $link;
    }

    /**
     * Filter function to add "spm_hidden" class to hidden menu items in <li> tree.
     *
     * @param $output
     * @return string
     */
    public function wp_list_pages( $output )
    {
        $output = preg_replace_callback(
            '/\bpage-item-(\d+)\b/',
            array( $this, '_wp_list_pages_replace_callback' ),
            $output
        );

        return $output;
    }

    /**
     * Status header filter function.
     * When a 404 error occurs check if we can find the URL in a post's spm_old_url_XXX field.
     * If found, 301 redirect to the post.
     *
     * @param $code
     * @return mixed
     */
    public function status_header( $code )
    {
        /** @var wpdb $wpdb - Wordpress Database */
        global $wpdb;
        global $wp;

        if ( preg_match( '|\b404\b|', $code ) ) {
            if ( !empty($wp->request) ) {
                $query = $wpdb->prepare(
                         "SELECT post_id FROM $wpdb->postmeta WHERE meta_key LIKE 'spm_old_url_%%' AND meta_value='%s'",
                              $wp->request );

                $post_id = $wpdb->get_var( $query );

                if ( $post_id ) {
                    $link = get_page_link( $post_id );

                    if ( $link ) {
                        header( 'Location: ' . $link, true, 301 );
                        exit();
                    }
                }
            }
        }

        return $code;
    }

    /**
     * Output header for admin page
     */
    public function admin_head()
    {
        $currentScreen = get_current_screen();

        if ( 'pages_page_page-tree' === $currentScreen->base ) {
            add_filter( "views_" . $currentScreen->id, array( $this, 'filter_views_edit_postsoverview' ) );
            /** @noinspection PhpIncludeInspection */
            require $this->plugin_dir . '/view/admin_head.php';
        }
    }

    /**
     * Add submenu to admin left menu
     */
    public function admin_menu()
    {
        add_submenu_page( 'edit.php?post_type='.$this->_post_type
                        , __( 'Swifty Page Manager', 'swifty-page-manager' )
                        , __( 'Swifty Page Manager', 'swifty-page-manager' )
                        , 'manage_options'
                        , 'page-tree'
                        , array( $this, 'view_page_tree' )
                        );
    }

    /**
     * Load translations
     */
    function spm_load_textdomain()
    {
        if ( is_admin() ) {
            load_plugin_textdomain( 'swifty-page-manager', false, '/swifty-page-manager/languages' );
        }
    }

    /**
     * Show page tree
     */
    public function view_page_tree()
    {
        if ( !current_user_can( 'edit_pages' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        }

        // renamed from cookie to fix problems with mod_security
        wp_enqueue_script( "jquery-cookie", $this->plugin_dir_url . "/js/jquery.biscuit.js", array( "jquery" ) );
        wp_enqueue_script( "jquery-ui-tooltip" );
        wp_enqueue_script( "jquery-jstree", $this->plugin_dir_url . "/js/jquery.jstree.js", false,
                           $this->_plugin_version );
        wp_enqueue_script( "jquery-alerts", $this->plugin_dir_url . "/js/jquery.alerts.js", false,
                           $this->_plugin_version );
        wp_enqueue_script( 'spm',   $this->plugin_dir_url . "/js/swifty-page-manager.js", false,
                           $this->_plugin_version );

        wp_enqueue_style( "jquery-alerts",  $this->plugin_dir_url . "/css/jquery.alerts.css", false,
                          $this->_plugin_version );
        wp_enqueue_style( 'spm-font-awesome', '//netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.css',
                          false, $this->_plugin_version );

        $oLocale = array(
            "status_draft_ucase"      => ucfirst( __( "draft", 'swifty-page-manager' ) ),
            "status_future_ucase"     => ucfirst( __( "future", 'swifty-page-manager' ) ),
            "status_password_ucase"   => ucfirst( __( "protected", 'swifty-page-manager' ) ),
            "status_pending_ucase"    => ucfirst( __( "pending", 'swifty-page-manager' ) ),
            "status_private_ucase"    => ucfirst( __( "private", 'swifty-page-manager' ) ),
            "status_trash_ucase"      => ucfirst( __( "trash", 'swifty-page-manager' ) ),
            "password_protected_page" => __( "Password protected page", 'swifty-page-manager' ),
            "no_pages_found"          => __( "No pages found.", 'swifty-page-manager' ),
            "hidden_page"             => __( "Hidden", 'swifty-page-manager' ),
            "no_sub_page_when_draft"  => __( "Sorry, can't create a sub page to a page with status \"draft\".", 'swifty-page-manager' ),
        );

        wp_localize_script( "spm", 'spm_l10n', $oLocale );

        /** @noinspection PhpIncludeInspection */
        require( $this->plugin_dir . '/view/page_tree.php' );
    }

    /**
     * Return JSON with tree children, called from Ajax
     */
    public function ajax_get_childs()
    {
        header( "Content-type: application/json" );

        $action = $_GET[ "action" ];

        // Check if user is allowed to get the list. For example subscribers should not be allowed to
        // Use same capability that is required to add the menu
        $post_type_object = get_post_type_object( $this->_post_type );

        if ( !current_user_can( $post_type_object->cap->edit_posts ) ) {
            die( __( 'Cheatin&#8217; uh?' ) );
        }

        if ( $action ) {   // regular get
            $id = ( isset( $_GET[ "id" ] ) ) ? $_GET[ "id" ] : null;
            $id = (int) str_replace( "spm-id-", "", $id );

            $jstree_open = array();

            if ( isset( $_COOKIE[ "jstree_open" ] ) ) {
                $jstree_open = $_COOKIE[ "jstree_open" ]; // like this: [jstree_open] => spm-id-1282,spm-id-1284,spm-id-3
                $jstree_open = explode( ",", $jstree_open );

                for ( $i = 0; $i < sizeof( $jstree_open ); $i++ ) {
                    $jstree_open[ $i ] = (int) str_replace( "#spm-id-", "", $jstree_open[ $i ] );
                }
            }

            $this->getTree();
            $jsonData = $this->getJsonData( $this->_byPageId[$id], $jstree_open );
            print json_encode( $jsonData );
            exit;
        }

        exit;
    }

    /**
     * Output tree and html code for post overview page
     */
    public function filter_views_edit_postsoverview( $filter_var )
    {
        ob_start();
        $this->view_page_tree();
        $tree_common_stuff = ob_get_clean();

        /*
        on non hierarcical post types this one exists:
        tablenav-pages one-page
        then after:
        <div class="view-switch">

        if view-switch exists: add item to it
        if view-switch not exists: add it + item to it
        */

        $mode   = "tree";
        $class  = isset( $_GET[ "mode" ] ) && $_GET[ "mode" ] == $mode ? " class='current' " : "";
        $title  = __( "Swifty Page Manager", 'swifty-page-manager' );
        $tree_a = "<a href='" . esc_url( add_query_arg( 'mode', $mode, $this->getPluginUrl() ) ) .
                  "' $class> <img id='view-switch-$mode' src='" . esc_url( includes_url( 'images/blank.gif' ) ) .
                  "' width='20' height='20' title='$title' alt='$title' /></a>\n";

        // Copy of wordpress own, if it does not exist
        $wp_list_a = "";

        if ( is_post_type_hierarchical( $this->_post_type ) ) {
            $mode      = "list";
            if ( isset( $_GET[ "mode" ] ) && $_GET[ "mode" ] != $mode  ) {
                $class = " class='spm_add_list_view' ";
            } else {
                $class = " class='spm_add_list_view current' ";
            }
            $title     = __( "List View" ); /* translation not missing - exists in wp */
            $wp_list_a = "<a href='" . esc_url( add_query_arg( 'mode', $mode, $this->getPluginUrl() ) ) .
                         "' $class><img id='view-switch-$mode' src='" . esc_url( includes_url( 'images/blank.gif' ) ) .
                         "' width='20' height='20' title='$title' alt='$title' /></a>\n";
        }

        $out  = "";
        $out .= $tree_a;
        $out .= $wp_list_a;

        // Output tree related stuff if that view/mode is selected
        if ( isset( $_GET[ "mode" ] ) && $_GET[ "mode" ] === "tree" ){
            $out .= sprintf( '<div class="spm-postsoverview-wrap">%1$s</div>', $tree_common_stuff );
        }

        echo $out;

        return $filter_var;
    }

    /**
     * Ajax function to move a page
     */
    public function ajax_move_page()
    {
        /*
         the node that was moved,
         the reference node in the move,
         the new position relative to the reference node (one of "before", "after" or "inside")
        */
        /** @var wpdb $wpdb - Wordpress Database */
        global $wpdb;

        $node_id     = $_POST[ "node_id" ]; // the node that was moved
        $ref_node_id = $_POST[ "ref_node_id" ];
        $type        = $_POST[ "type" ];

        $node_id     = str_replace( "spm-id-", "", $node_id );
        $ref_node_id = str_replace( "spm-id-", "", $ref_node_id );

        $_POST[ "skip_sitepress_actions" ] = true; // sitepress.class.php->save_post_actions

        if ( $node_id && $ref_node_id ) {
            #echo "\nnode_id: $node_id";
            #echo "\ntype: $type";

            $post_node     = get_post( $node_id );
            $post_ref_node = get_post( $ref_node_id );

            $show_ref_page_in_menu = get_post_meta( $ref_node_id, 'spm_show_in_menu', true );

            // first check that post_node (moved post) is not in trash. we do not move them
            if ( $post_node->post_status === "trash" ) {
                exit;
            }

            if ( "inside" === $type ) {
                // post_node is moved inside ref_post_node
                // add ref_post_node as parent to post_node and set post_nodes menu_order to 0
                // @todo: shouldn't menu order of existing items be changed?
                $post_to_save = array(
                    "ID"          => $post_node->ID,
                    "menu_order"  => 0,
                    "post_parent" => $post_ref_node->ID,
                    "post_type"   => $post_ref_node->post_type
                );

                $id_saved = wp_update_post( $post_to_save );

                if ( $id_saved && !empty( $show_ref_page_in_menu ) && $show_ref_page_in_menu !== 'show' ) {
                    update_post_meta( $id_saved, 'spm_show_in_menu', 'hide' );
                }

                echo "did inside";
            } elseif ( "before" === $type ) {
                // post_node is placed before ref_post_node
                // update menu_order of all pages with a menu order more than or equal ref_node_post and with the same
                // parent as ref_node_post we do this so there will be room for our page if it's the first page
                // so: no move of individial posts yet
                $wpdb->query(
                     $wpdb->prepare( "UPDATE $wpdb->posts SET menu_order = menu_order+1 WHERE post_parent = %d",
                                     $post_ref_node->post_parent ) );

                // update menu order with +1 for all pages below ref_node, this should fix the problem with "unmovable"
                // pages because of multiple pages with the same menu order (...which is not the fault of this plugin!)
                $wpdb->query(
                     $wpdb->prepare(
                        "UPDATE $wpdb->posts SET menu_order = menu_order+1 WHERE menu_order >= %d AND post_type = %s",
                            $post_ref_node->menu_order + 1,
                            'page' ) );

                $post_to_save = array(
                    "ID"          => $post_node->ID,
                    "menu_order"  => $post_ref_node->menu_order,
                    "post_parent" => $post_ref_node->post_parent,
                    "post_type"   => $post_ref_node->post_type
                );

                wp_update_post( $post_to_save );

                echo "did before";
            } elseif ( "after" === $type ) {
                // post_node is placed after ref_post_node
                // update menu_order of all posts with the same parent ref_post_node and with a menu_order of the same
                // as ref_post_node, but do not include ref_post_node +2 since multiple can have same menu order and we
                // want our moved post to have a unique "spot"
                $wpdb->query(
                    $wpdb->prepare(
                        "UPDATE $wpdb->posts SET menu_order = menu_order+2 WHERE post_parent = %d AND menu_order >= %d AND id <> %d ",
                            $post_ref_node->post_parent,
                            $post_ref_node->menu_order,
                            $post_ref_node->ID ) );

                $post_to_save = array(
                    "ID"          => $post_node->ID,
                    "menu_order"  => $post_ref_node->menu_order + 1,
                    "post_parent" => $post_ref_node->post_parent,
                    "post_type"   => $post_ref_node->post_type
                );

                wp_update_post( $post_to_save );

                echo "did after";
            }

            // Store the moved page id in the jstree_select cookie
            setcookie( "jstree_select", "spm-id-" . $post_node->ID );

        } else {
            // error
        }

        do_action( "spm_node_move_finish" );

        exit;
    }

    /**
     * Ajax funtion to save a page
     */
    public function ajax_save_page()
    {
        /** @var wpdb $wpdb - Wordpress Database */
        global $wpdb;

        $post_id     = !empty( $_POST[ "post_ID" ] ) ? intval( $_POST[ "post_ID" ] ) : null;
        $post_title  = !empty( $_POST[ "post_title" ] ) ? trim( $_POST[ "post_title" ] ) : '';
        $post_name   = !empty( $_POST[ "post_name" ] ) ? trim( $_POST[ "post_name" ] ) : '';
        $post_status = $_POST[ "post_status" ];

        if ( !$post_title ) {
            $post_title = __( "New page", 'swifty-page-manager' );
        }

        $spm_is_custom_url      = !empty( $_POST[ "spm_is_custom_url" ] ) ? intval( $_POST[ "spm_is_custom_url" ] ) : null;
        $spm_page_title_seo     = !empty( $_POST[ "spm_page_title_seo" ] ) ? trim( $_POST[ "spm_page_title_seo" ] ) : '';
        $spm_show_in_menu       = !empty( $_POST[ "spm_show_in_menu" ] ) ? $_POST[ "spm_show_in_menu" ] : null;
        $spm_header_visibility  = !empty( $_POST[ "spm_header_visibility" ] ) ? $_POST[ "spm_header_visibility" ]  : null;
        $spm_sidebar_visibility = !empty( $_POST[ "spm_sidebar_visibility" ] ) ? $_POST[ "spm_sidebar_visibility" ] : null;

        $post_data = array();

        $post_data[ "post_title" ]    = $post_title;
        $post_data[ "post_status" ]   = $post_status;
        $post_data[ "post_type" ]     = $_POST[ "post_type" ];
        $post_data[ "page_template" ] = $_POST[ "page_template" ];

        if ( isset( $post_id ) && !empty( $post_id ) ) {  // We're in edit mode
            $post_data[ "ID" ] = $post_id;

            $post_id = wp_update_post( $post_data );

            if ( $post_id ) {
                if ( $this->is_swifty ) {
                    $cur_spm_url = get_post_meta( $post_id, 'spm_url', true );

                    if ( !empty( $cur_spm_url ) ) {
                        if ( $cur_spm_url !== $post_name ) {
                            $this->save_old_url( $post_id, $cur_spm_url );
                        }
                    } else {
                        if ( $spm_is_custom_url ) {
                            $this->save_old_url( $post_id, wp_make_link_relative( get_page_link( $post_id ) ) );
                        }
                    }

                    update_post_meta( $post_id, 'spm_url', $spm_is_custom_url ? $post_name : '' );
                    update_post_meta( $post_id, 'spm_show_in_menu', $spm_show_in_menu );
                    update_post_meta( $post_id, 'spm_page_title_seo', $spm_page_title_seo );
                    update_post_meta( $post_id, 'spm_header_visibility', $spm_header_visibility );
                    update_post_meta( $post_id, 'spm_sidebar_visibility', $spm_sidebar_visibility );
                }

                echo "1";
            } else {
                echo "0";   // fail, tell js
            }
        }
        else {   // We're in create mode
            $post_data[ "post_content" ] = "";

            $parent_id = $_POST[ "parent_id" ];
            $parent_id = intval( str_replace( "spm-id-", "", $parent_id ) );
            $ref_post  = get_post( $parent_id );

            if ( "after" === $_POST[ "add_mode" ] ) {
                // update menu_order of all pages below our page
                $wpdb->query(
                    $wpdb->prepare(
                        "UPDATE $wpdb->posts SET menu_order = menu_order+2 WHERE post_parent = %d AND menu_order >= %d AND id <> %d ",
                            $ref_post->post_parent,
                            $ref_post->menu_order,
                            $ref_post->ID ) );

                // create a new page and then goto it
                $post_data[ "menu_order" ]  = $ref_post->menu_order + 1;
                $post_data[ "post_parent" ] = $ref_post->post_parent;
            } elseif ( "inside" === $_POST[ "add_mode" ] ) {
                // update menu_order, so our new post is the only one with order 0
                $wpdb->query(
                    $wpdb->prepare( "UPDATE $wpdb->posts SET menu_order = menu_order+1 WHERE post_parent = %d",
                        $ref_post->ID ) );

                $post_data[ "menu_order" ]  = 0;
                $post_data[ "post_parent" ] = $ref_post->ID;
            }

            $post_id = wp_insert_post( $post_data );
            $post_id = intval( $post_id );

            if ( $post_id ) {
                if ( $this->is_swifty ) {
                    add_post_meta( $post_id, 'spm_url', $spm_is_custom_url ? $post_name : '', 1 );
                    add_post_meta( $post_id, 'spm_show_in_menu', $spm_show_in_menu, 1 );
                    add_post_meta( $post_id, 'spm_page_title_seo', $spm_page_title_seo, 1 );
                    add_post_meta( $post_id, 'spm_header_visibility', $spm_header_visibility, 1 );
                    add_post_meta( $post_id, 'spm_sidebar_visibility', $spm_sidebar_visibility, 1 );
                }

                // Store the new page id in the jstree_select cookie
                setcookie( "jstree_select", "spm-id-" . $post_id );

                echo "1";
            } else {
                echo "0";   // fail, tell js
            }
        }

        exit;
    }

    /**
     * Ajax function to delete a page
     */
    public function ajax_delete_page()
    {
        $post_id = intval( $_POST[ "post_ID" ] );

        if ( isset( $post_id ) && !empty( $post_id ) ) {
            $menuItems = wp_get_associated_nav_menu_items( $post_id, 'post_type', 'page' );

            foreach ( $menuItems as $menuItemId ) {
                wp_delete_post( $menuItemId, true );
            }

            $post_data = wp_delete_post( $post_id, false );

            if ( is_object( $post_data ) ) {
//                delete_post_meta( $post_id, 'spm_url' );
//                delete_post_meta( $post_id, 'spm_show_in_menu' );
//                delete_post_meta( $post_id, 'spm_page_title_seo' );
//                delete_post_meta( $post_id, 'spm_header_visibility' );
//                delete_post_meta( $post_id, 'spm_sidebar_visibility' );

                echo "1";
            } else {
                echo "0";   // fail, tell js
            }
        } else {
            echo "0";   // fail, tell js
        }

        exit;
    }

    /**
     * Ajax function to publish a page
     */
    public function ajax_publish_page()
    {
        $post_id = intval( $_POST[ "post_ID" ] );

        if ( isset( $post_id ) && !empty( $post_id ) ) {
            $this->_update_post_status( $post_id, 'publish' );

            echo "1";
        } else {
            echo "0";   // fail, tell js
        }

        exit;
    }

    /**
     * Ajax function to set the settings of a post
     */
    public function ajax_post_settings()
    {
        header( 'Content-Type: text/javascript' );

        $post_id          = intval( $_REQUEST[ 'post_ID' ] );
        $post             = get_post( $post_id );
        $post_meta        = get_post_meta( $post_id );
        $post_status      = ( $post->post_status === 'private' ) ? 'publish' : $post->post_status; // _status
        $spm_show_in_menu = ( $post->post_status === 'private' ) ? 'hide' : 'show';
        $spm_is_custom_url = 0;

        $defaults = array( 'spm_show_in_menu'       => 'show'
                         , 'spm_page_title_seo'     => $post->post_title
                         , 'spm_header_visibility'  => 'hide'
                         , 'spm_sidebar_visibility' => 'hide'
                         );

        foreach ( $defaults as $key => $val ) {
            if ( !isset( $post_meta[ $key ] ) ) {
                $post_meta[ $key ] = $val;
            }
        }

        if ( $this->is_swifty ) {
            if ( !empty( $post_meta[ 'spm_url' ][0] ) ) {
                $spm_page_url = $post_meta[ 'spm_url' ][0];
                $spm_is_custom_url = 1;
            } else {
                $spm_page_url = wp_make_link_relative( get_page_link( $post_id ) );
            }
        } else {
            $spm_page_url = $post->post_name;
        }

        $spm_page_url = trim( $spm_page_url, '/' );

        if ( $post_meta[ 'spm_show_in_menu' ] === 'show' ) {
            // post_status can be private, so then the page must not be visible in the menu.
            $post_meta[ 'spm_show_in_menu' ] = $spm_show_in_menu;
        }

        if ( empty( $post_meta[ 'spm_show_in_menu' ] )  ) {
            $post_meta[ 'spm_show_in_menu' ] = 'show';
        }

        ?>
        var li = jQuery( 'li#cms-tpv-<?php echo $post_id; ?>' );

        var li = jQuery( '#spm-id-<?php echo $post_id; ?>' );

        li.find( 'input[name="post_title"]' ).val( <?php echo json_encode( $post->post_title ); ?> );
        li.find( 'input[name="post_status"]' ).val( [ <?php echo json_encode( $post_status ); ?> ] );
        li.find( 'select[name="page_template"]' ).val( [ <?php echo json_encode( $post->page_template ); ?> ] );
        li.find( 'input[name="post_name"]' ).val( <?php echo json_encode( $spm_page_url ); ?> );
        li.find( 'input[name="spm_is_custom_url"]' ).val( <?php echo json_encode( $spm_is_custom_url ); ?> );
        li.find( 'input[name="spm_show_in_menu"]' ).val( [ <?php echo json_encode( $post_meta[ 'spm_show_in_menu' ] ); ?> ] );
        li.find( 'input[name="spm_page_title_seo"]' ).val( <?php echo json_encode( $post_meta[ 'spm_page_title_seo' ] ); ?> );
        li.find( 'input[name="spm_header_visibility"]' ).val( [ <?php echo json_encode( $post_meta[ 'spm_header_visibility' ] ); ?> ] );
        li.find( 'input[name="spm_sidebar_visibility"]' ).val( [ <?php echo json_encode( $post_meta[ 'spm_sidebar_visibility' ] ); ?> ] );

        li.find( 'input[name="post_title"]' ).val( <?php echo json_encode($post->post_title); ?> );
        li.find( 'input[name="post_name"]' ).val( <?php echo json_encode($post->post_name); ?> );
        <?php
        exit;
    }

    /**
     * Ajax function to use Wordpress' sanitize_title_with_dashes function to prepare an URL string
     */
    public function ajax_sanitize_url()
    {
        echo sanitize_title_with_dashes( $_POST[ "url" ] );
        exit;
    }

    /**
     * @return StdClass
     */
    public function getTree()
    {
        if ( is_null( $this->_tree ) ) {
            $this->_tree = new StdClass();
            $this->_tree->menuItem = new StdClass();
            $this->_tree->menuItem->ID = 0; // Fake menu item as root.
            $this->_tree->children = array();
            $this->_byPageId = array();
            $this->_byPageId [ 0 ] = &$this->_tree;
            $this->_addAllPages();
        }

        return $this->_tree;
    }

    /**
     * @param $branch
     * @return array
     */
    public function getJsonData( &$branch )
    {
        $result    = array();
        $childKeys = array_keys( $branch->children );

        foreach ( $childKeys as $childKey ) {
            $child = &$branch->children[$childKey];

            if ( isset( $child->page ) ) {
                $newBranch = $this->_get_pageJsonData( $child->page );

                /**
                 * if no children, output no state
                 * if viewing trash, don't get children. we watch them "flat" instead
                 */
                if ( $this->getPostStatus() != "trash" ) {
                    $newBranch[ 'children' ] = $this->getJsonData( $child );

                    if ( count($newBranch[ 'children' ]) ) {
                        $newBranch[ 'state' ] = 'closed';
                    }
                }

                $result[] = $newBranch;
            }
        }

        return $result;
    }

    /**
     * USAGE:  $this->save_old_url( 469, 'old/url/path' );
     *
     * @param $post_id
     * @param $old_url
     */
    function save_old_url( $post_id, $old_url ) {
        /** @var wpdb $wpdb - Wordpress Database */
        global $wpdb;

        $old_url = preg_replace( '|^'.preg_quote(get_site_url(),'|').'|', '', $old_url ); // Remove root URL
        $old_url = trim( $old_url, " \t\n\r\0\x0B/" ); // Remove leading and trailing slashes or whitespaces

        $existQuery = $wpdb->prepare(
            "SELECT COUNT(*) FROM $wpdb->postmeta WHERE post_id = %d AND meta_key LIKE 'spm_old_url_%%' AND meta_value='%s'",
                $post_id,
                $old_url );

        $exists = intval( $wpdb->get_var( $existQuery ) );

        if ( !$exists ) {
            $lastkeyQuery = $wpdb->prepare(
                "SELECT REPLACE( meta_key, 'spm_old_url_', '' ) FROM $wpdb->postmeta WHERE post_id = %d AND meta_key LIKE 'spm_old_url_%%' ORDER BY meta_key DESC",
                    $post_id );

            $lastKey = $wpdb->get_var( $lastkeyQuery );
            $number = intval( $lastKey ) + 1;

            add_post_meta( $post_id, 'spm_old_url_'.$number, $old_url );
        }
    }

    /**
     * Get filter on post status. The user can choose this.
     * - any
     * - publish
     * - trash
     *
     * @return string
     */
    public function getPostStatus() {
        return $this->_post_status;
    }

    /**
     * Get the URL of this plugin, for example:
     * http://domain.com/wp-admin/edit.php?post_type=page&page=page-tree
     *
     * @return string
     */
    public function getPluginUrl() {
        return $this->plugin_url;
    }

    ////////////////////////////////////////////////////////////////////////////////////////

    /**
     * @param integer $post_id
     * @param string $post_status
     */
    protected function _update_post_status( $post_id, $post_status )
    {
        wp_update_post( array(
            'ID'          => $post_id,
            'post_status' => $post_status
        ) );
    }

    /**
     * @param string $route
     * @param callable $callable
     */
    protected function _addRoute( $route, $callable )
    {
        $hookName = get_plugin_page_hookname( $route, '' );
        add_action( $hookName, $callable );
        global $_registered_pages;
        $_registered_pages[$hookName] = true;
    }

    /**
     *
     */
    protected function _addAllPages()
    {
        $args = array();
        $args['post_type'] = 'page';
        $args['post_status'] = $this->getPostStatus();
        $args['numberposts'] = -1;
        $args['orderby'] = 'menu_order';
        $args['order'] = 'ASC';
        $pages = get_posts( $args );
        $added = true;

        while ( !empty($pages) && $added ) {
            $added = false;
            $keys  = array_keys( $pages );

            foreach ( $keys as $key ) {
                $page = $pages[$key];

                if ( isset( $this->_byPageId[$page->ID] ) ) {
                    $branch = &$this->_byPageId[$page->ID];
                    $branch->page = $page;
                    unset( $branch );
                    unset( $pages[$key] );
                    $added = true;
                } else if ( isset( $this->_byPageId[ $page->post_parent ] ) ) {
                    $parentBranch = &$this->_byPageId[$page->post_parent];
                    $newBranch = new stdClass();
                    $newBranch->page = $page;
                    $newBranch->children = array();
                    $this->_byPageId[ $newBranch->page->ID ] = &$newBranch;
                    $parentBranch->children[] = &$newBranch;
                    unset( $newBranch );
                    unset( $pages[$key] );
                    $added = true;
                }
            }
        }

        // Add rest to root
        $parentBranch = &$this->_tree;

        foreach ( $pages as $page ) {
            $newBranch = new stdClass();
            $newBranch->page = $page;
            $newBranch->children = array();
            $this->_byPageId[ $newBranch->page->ID ] = &$newBranch;
            $parentBranch->children[] = &$newBranch;
            unset( $newBranch );
        }
    }

    /**
     * @param WP_Post $onePage
     * @return array
     */
    protected function _get_pageJsonData( $onePage )
    {
        $pageJsonData = array();

        $post    = $onePage;
        $page_id = $onePage->ID;

        $post_statuses    = get_post_statuses();
        $post_type_object = get_post_type_object( $this->_post_type );
        $editLink         = get_edit_post_link( $onePage->ID, 'notDisplay' );

        // type of node
        $rel = $onePage->post_status;

        if ( $onePage->post_password ) {
            $rel = "password";
        }

        // modified time
        $post_modified_time = strtotime( $onePage->post_modified );
        $post_modified_time = date_i18n( get_option( 'date_format' ), $post_modified_time, false );

        // last edited by
        setup_postdata( $post );

        if ( $last_id = get_post_meta( $post->ID, '_edit_last', true ) ) {
            $last_user = get_userdata( $last_id );

            if ( $last_user !== false ) {
                $post_author = apply_filters( 'the_modified_author', $last_user->display_name );
            }
        }

        if ( empty( $post_author ) ) {
            $post_author = __( "Unknown user", 'swifty-page-manager' );
        }

        $title = get_the_title( $onePage->ID ); // so hooks and stuff will do their work

        if ( empty( $title ) ) {
            $title = __( "<Untitled page>", 'swifty-page-manager' );
        }

        $user_can_edit_page  = current_user_can( $post_type_object->cap->edit_post, $page_id );
        $user_can_add_inside = current_user_can( $post_type_object->cap->create_posts, $page_id );
        $user_can_add_after  = current_user_can( $post_type_object->cap->create_posts, $page_id );

        $arr_page_css_styles   = array();
        $arr_page_css_styles[] = "spm_user_can_edit_page_" . ( $user_can_edit_page ? 'yes' : 'no' );
        $arr_page_css_styles[] = "spm_user_can_add_page_inside_" . ( $user_can_add_inside ? 'yes' : 'no' );
        $arr_page_css_styles[] = "spm_user_can_add_page_after_" . ( $user_can_add_after ? 'yes' : 'no' );

        if ( $this->is_swifty ) {
            $show_page_in_menu = get_post_meta( $page_id, 'spm_show_in_menu', true );

            if ( empty( $show_page_in_menu ) ) {
                $show_page_in_menu = 'show';
            }

            $arr_page_css_styles[] = "spm-show-page-in-menu-" . ( $show_page_in_menu === 'show' ? 'yes' : 'no' );
        }

        $pageJsonData['data'] = array();
        $pageJsonData['data']['title'] = $title;

        $pageJsonData['attr'] = array();
        $pageJsonData['attr']['id'] = "spm-id-" . $onePage->ID;
        $pageJsonData['attr']['class'] = join( ' ', $arr_page_css_styles );

        $pageJsonData['metadata'] = array();
        $pageJsonData['metadata']["id"] = "spm-id-".$onePage->ID;
        $pageJsonData['metadata']["post_id"] = $onePage->ID;
        $pageJsonData['metadata']["post_type"] = $onePage->post_type;
        $pageJsonData['metadata']["post_status"] = $onePage->post_status;
        if ( isset( $post_statuses[ $onePage->post_status ] )  ) {
            $pageJsonData['metadata']["post_status_translated"] = $post_statuses[ $onePage->post_status ];
        } else {
            $pageJsonData['metadata']["post_status_translated"] = $onePage->post_status;
        }
        $pageJsonData['metadata']["rel"] = $rel;
        $pageJsonData['metadata']["permalink"] = htmlspecialchars_decode( get_permalink( $onePage->ID ) );
        $pageJsonData['metadata']["editlink"] = htmlspecialchars_decode( $editLink );
        $pageJsonData['metadata']["modified_time"] = $post_modified_time;
        $pageJsonData['metadata']["modified_author"] = $post_author;
        $pageJsonData['metadata']["user_can_edit_page"] = (int) $user_can_edit_page;
        $pageJsonData['metadata']["user_can_add_page_inside"] = (int) $user_can_add_inside;
        $pageJsonData['metadata']["user_can_add_page_after"] = (int) $user_can_add_after;
        $pageJsonData['metadata']["post_title"] = $title;
        $pageJsonData['metadata']["delete_nonce"] = wp_create_nonce( "delete-page_".$onePage->ID, '_trash' );

        return $pageJsonData;
    }

    /**
     * Usage: $seoVersion = $this->_getPluginVersion('wordpress-seo/*');
     *
     * @param  string $pluginMatch - For example "wordpress-seo/*"
     * @return bool|string         - false if plugin not installed or not active
     */
    protected function _getPluginVersion( $pluginMatch )
    {
        $result = false;
        $regexp = preg_quote( $pluginMatch, '#' );
        $regexp = str_replace( array('\*','\?'), array('.*','.'), $regexp);
        $regexp = '#^' . $regexp . '$#i';

        if ( ! function_exists( 'get_plugin_data' ) ) {
            require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
        }

        $plugins = get_option( 'active_plugins', array() ); // returns only active plugins

        foreach ( $plugins as $plugin ) {
            if ( preg_match($regexp, $plugin) ) {
                $data = get_plugin_data( WP_PLUGIN_DIR . '/' .$plugin );
                $result = ( !empty($data['Version']) ) ? $data['Version'] : '0.0.1';
                break;
            }
        }

        return $result;
    }

    /**
     * Usage: $haveSeo = $this->_isPluginMinimal('wordpress-seo/*', '1.0.0');
     *
     * @param $pluginMatch - For example "wordpress-seo/*"
     * @param $requireVersion
     * @return bool|mixed
     */
    protected function _isPluginMinimal( $pluginMatch, $requireVersion )
    {
        $result = false;
        $pluginVersion = $this->_getPluginVersion( $pluginMatch );

        if ( $pluginVersion ) {
            $result = version_compare( $pluginVersion, $requireVersion, '>=' );
        }

        return $result;
    }

    /**
     * Callback function for the wp_list_pages() filter function.
     *
     * @param $match  - matches from preg_replace_callback
     * @return string - replacement, $match[0] is replaced by this
     */
    protected function _wp_list_pages_replace_callback( $match )
    {
        $result  = $match[0];
        $post_id = $match[1];
        $show    = get_post_meta( $post_id, 'spm_show_in_menu', true );

        if ( !empty( $show ) && 'hide' === $show ) {
            $result .= ' spm_hidden';
        };

        return $result;
    }

} // End of class SwiftyPageManager

// Start the plugin
$SwiftyPageManager = new SwiftyPageManager();
