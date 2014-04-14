<?php
/*
Plugin Name: Swifty Page Manager
Description: Swifty Page Manager
Author: SwiftyGuys
Version: 0.0.2
Author URI: http://swiftysite.com/
Plugin URI: https://bitbucket.org/swiftyguys/SwiftyPages
*/

class SwiftyPages
{
    protected $plugin_file;
    protected $plugin_dir;
    protected $plugin_basename;
    protected $plugin_dir_url;
    protected $_plugin_version = '0.0.2';
    protected $_view = 'all';
    protected $_post_type = 'page';
    protected $_tree = null;
    protected $_byPageId = null;

    /**
     * Constructor
     */
    public function __construct() {
        $this->plugin_file     = __FILE__ ;
        $this->plugin_dir      = dirname( $this->plugin_file );
        $this->plugin_basename = basename( $this->plugin_dir );
        $this->plugin_dir_url  = plugins_url( basename($this->plugin_dir) );
        if ( !empty($_GET[ "view" ]) ) {
            $this->_view = $_GET[ "view" ];
        }
        if ( !empty($_GET[ "post_type" ]) ) {
            $this->_post_type = $_GET[ "post_type" ];
        }
        add_action( 'admin_head', array( $this, "admin_head" ) );
        add_action( 'admin_menu', array($this,'admin_menu') );
        add_action( 'wp_ajax_swiftypages_get_childs',    array( $this, 'ajax_get_childs' ) );
        add_action( 'wp_ajax_swiftypages_move_page',     array( $this, 'ajax_move_page' ) );
        add_action( 'wp_ajax_swiftypages_save_page',     array( $this, 'ajax_save_page' ) );
        add_action( 'wp_ajax_swiftypages_delete_page',   array( $this, 'ajax_delete_page' ) );
        add_action( 'wp_ajax_swiftypages_post_settings', array( $this, 'ajax_post_settings' ) );

    }

    public function admin_head()
    {
        $currentScreen = get_current_screen();
        if ( 'pages_page_page-tree' == $currentScreen->base ) {
            add_filter( "views_" . $currentScreen->id, array( $this, "filter_views_edit_postsoverview" ) );
            require $this->plugin_dir . '/view/admin_head.php';
        }
    }

    public function admin_menu() {
        add_submenu_page( 'edit.php?post_type='.$this->_post_type
                        , __( 'SwiftyPages', 'swiftypages' )
                        , __( 'SwiftyPages', 'swiftypages' )
                        , 'manage_options'
                        , 'page-tree'
                        , array( $this, 'view_page_tree' )
                        );
    }

    public function view_page_tree() {
        if ( !current_user_can( 'edit_pages' ) )  {
            wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        }
        // renamed from cookie to fix problems with mod_security
        wp_enqueue_script( "jquery-cookie", $this->plugin_dir_url . "/js/jquery.biscuit.js", array( "jquery" ) );
        wp_enqueue_script( "jquery-ui-sortable" );
        wp_enqueue_script( "jquery-jstree", $this->plugin_dir_url . "/js/jquery.jstree.js",   false, $this->_plugin_version );
        wp_enqueue_script( "jquery-alerts", $this->plugin_dir_url . "/js/jquery.alerts.js",   false, $this->_plugin_version );
        wp_enqueue_script( 'swiftypages',   $this->plugin_dir_url . "/js/swiftypages.js",     false, $this->_plugin_version );

        wp_enqueue_style( "swiftypages",    $this->plugin_dir_url . "/css/styles.css",        false, $this->_plugin_version );
        wp_enqueue_style( "jquery-alerts",  $this->plugin_dir_url . "/css/jquery.alerts.css", false, $this->_plugin_version );
        $oLocale = array(
            "Enter_title_of_new_page"                     => __( "Enter title of new page", 'swiftypages' ),
            "child_pages"                                 => __( "child pages", 'swiftypages' ),
            "Edit_page"                                   => __( "Edit page", 'swiftypages' ),
            "View_page"                                   => __( "View page", 'swiftypages' ),
            "Edit"                                        => __( "Edit", 'swiftypages' ),
            "View"                                        => __( "View", 'swiftypages' ),
            "Add_page"                                    => __( "Add page", 'swiftypages' ),
            "Add_new_page_after"                          => __( "Add new page after", 'swiftypages' ),
            "after"                                       => __( "after", 'swiftypages' ),
            "inside"                                      => __( "inside", 'swiftypages' ),
            "Can_not_add_sub_page_when_status_is_draft"   => __( "Sorry, can't create a sub page to a page with status \"draft\".", 'swiftypages' ),
            "Can_not_add_sub_page_when_status_is_trash"   => __( "Sorry, can't create a sub page to a page with status \"trash\".", 'swiftypages' ),
            "Can_not_add_page_after_when_status_is_trash" => __( "Sorry, can't create a page after a page with status \"trash\".", 'swiftypages' ),
            "Add_new_page_inside"                         => __( "Add new page inside", 'swiftypages' ),
            "Status_draft"                                => __( "draft", 'swiftypages' ),
            "Status_future"                               => __( "future", 'swiftypages' ),
            "Status_password"                             => __( "protected", 'swiftypages' ), // is "protected" word better than "password" ?
            "Status_pending"                              => __( "pending", 'swiftypages' ),
            "Status_private"                              => __( "private", 'swiftypages' ),
            "Status_trash"                                => __( "trash", 'swiftypages' ),
            "Status_draft_ucase"                          => ucfirst( __( "draft", 'swiftypages' ) ),
            "Status_future_ucase"                         => ucfirst( __( "future", 'swiftypages' ) ),
            "Status_password_ucase"                       => ucfirst( __( "protected", 'swiftypages' ) ), // is "protected" word better than "password" ?
            "Status_pending_ucase"                        => ucfirst( __( "pending", 'swiftypages' ) ),
            "Status_private_ucase"                        => ucfirst( __( "private", 'swiftypages' ) ),
            "Status_trash_ucase"                          => ucfirst( __( "trash", 'swiftypages' ) ),
            "Password_protected_page"                     => __( "Password protected page", 'swiftypages' ),
            "Adding_page"                                 => __( "Adding page...", 'swiftypages' ),
            "Adding"                                      => __( "Adding ...", 'swiftypages' ),
            "No posts found"                              => __( "No posts found.", 'swiftypages' ),

            "Menu_button_text"                            => __( "Menu button text", 'swiftypages' ),
            "Page_type"                                   => __( "Page type", 'swiftypages' ),
            "Page_title_for_search_engines"               => __( "Page title for search engines", 'swiftypages' ),
            "Customize_page_url"                          => __( "Customize page url", 'swiftypages' ),
            "Show_in_menu"                                => __( "Show in menu", 'swiftypages' ),
            "Show"                                        => __( "Show", 'swiftypages' ),
            "Hide"                                        => __( "Hide", 'swiftypages' ),
            "Position_of_page"                            => __( "Position of page", 'swiftypages' ),
            "Next"                                        => __( "Next", 'swiftypages' ),
            "Sub"                                         => __( "Sub", 'swiftypages' ),
            "Draft_or_live"                               => __( "Draft or live", 'swiftypages' ),
            "Draft"                                       => __( "Draft", 'swiftypages' ),
            "Live"                                        => __( "Live", 'swiftypages' ),
            "Edit_page_content"                           => __( "Edit page content", 'swiftypages' ),
            "Delete page"                                 => __( "Delete page", 'swiftypages' ),
            "Delete"                                      => __( "Delete", 'swiftypages' ),
            "Delete_are_you_sure?"                        => __( " Are you sure you want to permanently delete this page with all it's content?", 'swiftypages' )
        );
        wp_localize_script( "swiftypages", 'swiftypages_l10n', $oLocale );

        require( $this->plugin_dir . '/view/page_tree.php' );
    }

    public function ajax_get_childs()
    {
        header( "Content-type: application/json" );

        $action    = $_GET[ "action" ];
        $view      = $this->_view; // all | public | trash
        $search    = ( isset( $_GET[ "search_string" ] ) ) ? trim( $_GET[ "search_string" ] ) : ""; // exits if we're doing a search

        // Check if user is allowed to get the list. For example subscribers should not be allowed to
        // Use same capability that is required to add the menu
        $post_type_object = get_post_type_object( $this->_post_type );
        if ( !current_user_can( $post_type_object->cap->edit_posts ) )
        {
            die( __( 'Cheatin&#8217; uh?' ) );
        }

        if ( $action )
        {

            if ( $search )
            {

                // find all pages that contains $search
                // collect all post_parent
                // for each parent id traverse up until post_parent is 0, saving all ids on the way

                // what to search: since all we see in the GUI is the title, just search that
                global $wpdb;


                $arrNodesToOpen = array();
                // find all parents to the arrnodestopen
                foreach ( $arrNodesToOpen as $oneNode )
                {
                    if ( $oneNode > 0 )
                    {
                        // not at top so check it out
                        $parentNodeID = $oneNode;
                        while ( $parentNodeID != 0 )
                        {
                            $hits               = $wpdb->get_results( $sql );
                            $sql                = "SELECT id, post_parent FROM $wpdb->posts WHERE id = $parentNodeID";
                            $row                = $wpdb->get_row( $sql );
                            $parentNodeID       = $row->post_parent;
                            $arrNodesToOpen2[ ] = $parentNodeID;
                        }
                    }
                }

                $sReturn        = "";

                foreach ( $arrNodesToOpen as $oneNodeID )
                {
                    $sReturn .= "\"#swiftypages-id-{$oneNodeID}\",";
                }
                $sReturn = preg_replace( '/,$/', "", $sReturn );
                if ( $sReturn )
                {
                    $sReturn = "[" . $sReturn . "]";
                }

                if ( $sReturn )
                {
                    echo $sReturn;
                }
                else
                {
                    // if no hits
                    echo "[]";
                }

                exit;

            }
            else
            {

                // regular get

                $id = ( isset( $_GET[ "id" ] ) ) ? $_GET[ "id" ] : null;
                $id = (int) str_replace( "swiftypages-id-", "", $id );

                $jstree_open = array();
                if ( isset( $_COOKIE[ "jstree_open" ] ) )
                {
                    $jstree_open = $_COOKIE[ "jstree_open" ]; // like this: [jstree_open] => swiftypages-id-1282,swiftypages-id-1284,swiftypages-id-3
                    $jstree_open = explode( ",", $jstree_open );
                    for ( $i = 0; $i < sizeof( $jstree_open ); $i++ )
                    {
                        $jstree_open[ $i ] = (int) str_replace( "#swiftypages-id-", "", $jstree_open[ $i ] );
                    }
                }
                $this->getTree();
                $jsonData = $this->getJsonData( $this->_byPageId[$id], $jstree_open );
                print json_encode( $jsonData );
                exit;
            }
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
        $title  = __( "SwiftyPages", 'swiftypages' );
        $tree_a = "<a href='" . esc_url( add_query_arg( 'mode', $mode, $_SERVER[ 'REQUEST_URI' ] ) ) . "' $class> <img id='view-switch-$mode' src='" . esc_url( includes_url( 'images/blank.gif' ) ) . "' width='20' height='20' title='$title' alt='$title' /></a>\n";

        // Copy of wordpress own, if it does not exist
        $wp_list_a = "";
        if ( is_post_type_hierarchical( $this->_post_type ) )
        {

            $mode      = "list";
            $class     = isset( $_GET[ "mode" ] ) && $_GET[ "mode" ] != $mode ? " class='swiftypages_add_list_view' " : " class='swiftypages_add_list_view current' ";
            $title     = __( "List View" ); /* translation not missing - exists in wp */
            $wp_list_a = "<a href='" . esc_url( add_query_arg( 'mode', $mode, $_SERVER[ 'REQUEST_URI' ] ) ) . "' $class><img id='view-switch-$mode' src='" . esc_url( includes_url( 'images/blank.gif' ) ) . "' width='20' height='20' title='$title' alt='$title' /></a>\n";

        }

        $out = "";
        $out .= $tree_a;
        $out .= $wp_list_a;

        // Output tree related stuff if that view/mode is selected
        if ( isset( $_GET[ "mode" ] ) && $_GET[ "mode" ] === "tree" )
        {

            $out .= sprintf( '
			<div class="swiftypages-postsoverview-wrap">
				%1$s
			</div>
		', $tree_common_stuff );

        }

        echo $out;

        return $filter_var;

    }

    public function ajax_move_page()
    {
        /*
         the node that was moved,
         the reference node in the move,
         the new position relative to the reference node (one of "before", "after" or "inside"),
             inside = man placerar den under en sida som inte har några barn?
        */

        global $wpdb;

        $node_id     = $_POST[ "node_id" ]; // the node that was moved
        $ref_node_id = $_POST[ "ref_node_id" ];
        $type        = $_POST[ "type" ];

        $node_id     = str_replace( "swiftypages-id-", "", $node_id );
        $ref_node_id = str_replace( "swiftypages-id-", "", $ref_node_id );

        $_POST[ "skip_sitepress_actions" ] = true; // sitepress.class.php->save_post_actions

        if ( $node_id && $ref_node_id )
        {
            #echo "\nnode_id: $node_id";
            #echo "\ntype: $type";

            $post_node     = get_post( $node_id );
            $post_ref_node = get_post( $ref_node_id );

            // first check that post_node (moved post) is not in trash. we do not move them
            if ( $post_node->post_status == "trash" )
            {
                exit;
            }

            if ( "inside" == $type )
            {
                // post_node is moved inside ref_post_node
                // add ref_post_node as parent to post_node and set post_nodes menu_order to 0
                // @todo: shouldn't menu order of existing items be changed?
                $post_to_save = array(
                    "ID"          => $post_node->ID,
                    "menu_order"  => 0,
                    "post_parent" => $post_ref_node->ID,
                    "post_type"   => $post_ref_node->post_type
                );
                wp_update_post( $post_to_save );

                $treeNode = $this->_getByPageId( $post_node->ID );
                $postRefNode = $this->_getByPageId( $post_ref_node->ID );
                if ( $treeNode
                     && isset( $treeNode->menuItem )
                     && $postRefNode
                     && isset( $postRefNode->menuItem ) ) {
                    $menu_item_data = $this->_getMenuItemDataForSave( $treeNode->menuItem );
                    $menu_item_data['menu-item-position']  = 0;
                    $menu_item_data['menu-item-parent-id'] = $postRefNode->menuItem->ID;
                    wp_update_nav_menu_item( $this->_getMainMenuId(), $treeNode->menuItem->ID, $menu_item_data );
                }

                echo "did inside";

            }
            elseif ( "before" == $type )
            {

                // post_node is placed before ref_post_node
                // update menu_order of all pages with a menu order more than or equal ref_node_post and with the same parent as ref_node_post
                // we do this so there will be room for our page if it's the first page
                // so: no move of individial posts yet
                $wpdb->query( $wpdb->prepare( "UPDATE $wpdb->posts SET menu_order = menu_order+1 WHERE post_parent = %d", $post_ref_node->post_parent ) );

                // update menu order with +1 for all pages below ref_node, this should fix the problem with "unmovable" pages because of
                // multiple pages with the same menu order (...which is not the fault of this plugin!)
                $wpdb->query( $wpdb->prepare( "UPDATE $wpdb->posts SET menu_order = menu_order+1 WHERE menu_order >= %d AND post_type = %s", $post_ref_node->menu_order + 1, 'page' ) );

                $post_to_save = array(
                    "ID"          => $post_node->ID,
                    "menu_order"  => $post_ref_node->menu_order,
                    "post_parent" => $post_ref_node->post_parent,
                    "post_type"   => $post_ref_node->post_type
                );
                wp_update_post( $post_to_save );

                $treeNode = $this->_getByPageId( $post_node->ID );
                $postRefNode = $this->_getByPageId( $post_ref_node->ID );
                if ( $treeNode
                     && isset( $treeNode->menuItem )
                     && $postRefNode
                     && isset( $postRefNode->menuItem ) ) {
                    $wpdb->query( $wpdb->prepare( "UPDATE $wpdb->posts SET menu_order = menu_order+1 WHERE post_parent = %d",
                                                  $postRefNode->menuItem->menu_item_parent ) );
                    $wpdb->query( $wpdb->prepare( "UPDATE $wpdb->posts SET menu_order = menu_order+1 WHERE menu_order >= %d AND post_type = %s",
                                                  $postRefNode->menuItem->menu_order + 1, 'nav_menu_item' ) );
                    $menu_item_data = $this->_getMenuItemDataForSave( $treeNode->menuItem );
                    $menu_item_data['menu-item-position']  = $postRefNode->menuItem->menu_order;
                    $menu_item_data['menu-item-parent-id'] = $postRefNode->menuItem->menu_item_parent;
                    wp_update_nav_menu_item( $this->_getMainMenuId(), $treeNode->menuItem->ID, $menu_item_data );
                }

                echo "did before";

            }
            elseif ( "after" == $type )
            {

                // post_node is placed after ref_post_node

                // update menu_order of all posts with the same parent ref_post_node and with a menu_order of the same as ref_post_node, but do not include ref_post_node
                // +2 since multiple can have same menu order and we want our moved post to have a unique "spot"
                $wpdb->query( $wpdb->prepare( "UPDATE $wpdb->posts SET menu_order = menu_order+2 WHERE post_parent = %d AND menu_order >= %d AND id <> %d ",
                                              $post_ref_node->post_parent,
                                              $post_ref_node->menu_order,
                                              $post_ref_node->ID ) );
                // update menu_order of post_node to the same that ref_post_node_had+1
                #$wpdb->query( $wpdb->prepare( "UPDATE $wpdb->posts SET menu_order = %d, post_parent = %d WHERE ID = %d", $post_ref_node->menu_order+1, $post_ref_node->post_parent, $post_node->ID ) );

                $post_to_save = array(
                    "ID"          => $post_node->ID,
                    "menu_order"  => $post_ref_node->menu_order + 1,
                    "post_parent" => $post_ref_node->post_parent,
                    "post_type"   => $post_ref_node->post_type
                );
                wp_update_post( $post_to_save );

                $treeNode = $this->_getByPageId( $post_node->ID );
                $postRefNode = $this->_getByPageId( $post_ref_node->ID );
                if ( $treeNode
                     && isset( $treeNode->menuItem )
                     && $postRefNode
                     && isset( $postRefNode->menuItem ) ) {
                    $wpdb->query( $wpdb->prepare( "UPDATE $wpdb->posts SET menu_order = menu_order+2 WHERE post_parent = %d AND menu_order >= %d AND id <> %d ",
                                                  $postRefNode->menuItem->menu_item_parent,
                                                  $postRefNode->menuItem->menu_order ,
                                                  $postRefNode->menuItem->ID ) );
                    $menu_item_data = $this->_getMenuItemDataForSave( $treeNode->menuItem );
                    $menu_item_data['menu-item-position']  = $postRefNode->menuItem->menu_order + 1;
                    $menu_item_data['menu-item-parent-id'] = $postRefNode->menuItem->menu_item_parent;
                    wp_update_nav_menu_item( $this->_getMainMenuId(), $treeNode->menuItem->ID, $menu_item_data );
                }

                echo "did after";
            }

            #echo "ok"; // I'm done here!

        }
        else
        {
            // error
        }

        // ok, we have updated the order of the pages
        // but we must tell wordpress that we have done something
        // other plugins (cache plugins) will not know to clear the cache otherwise
        // edit_post seems like the most appropriate action to fire
        // fire for the page that was moved? can not fire for all.. would be crazy, right?
        #wp_update_post(array("ID" => $node_id));
        #wp_update_post(array("ID" => $post_ref_node));
        #clean_page_cache($node_id); clean_page_cache($post_ref_node); // hmpf.. db cache reloaded don't care

        do_action( "cms_tree_page_view_node_move_finish" );

        exit;
    }

    public function ajax_add_page()
    {
        global $wpdb;

        $post_id    = intval( $_POST[ "post_ID" ] );
        $wpml_lang  = isset( $_POST[ "wpml_lang" ] ) ? $_POST[ "wpml_lang" ] : false;
        $post_title = trim( $_POST[ "post_title" ] );
        $post_name  = trim( $_POST[ "post_name" ] );   // url

        if ( !$post_title )
        {
            $post_title = __( "New page", 'swiftypages' );
        }

        $ss_page_title_seo     = trim( $_POST[ "ss_page_title_seo" ] );
        $ss_show_in_menu       = $_POST[ "ss_show_in_menu" ];
        $ss_header_visibility  = $_POST[ "ss_header_visibility" ];
        $ss_sidebar_visibility = $_POST[ "ss_sidebar_visibility" ];

        $post_data = array();

        $post_data[ "post_title" ]  = $post_title;
        $post_data[ "post_name" ]   = $post_name;
        $post_data[ "post_type" ]   = $_POST[ "post_type" ];
        $post_data[ "post_status" ] = $_POST[ "post_status" ];

        if ( isset( $post_id ) && !empty( $post_id ) )  // We're in edit mode
        {
            $post_data[ "ID" ] = $post_id;
            $id_saved = wp_update_post( $post_data );

            if ( $id_saved )
            {
                update_post_meta( $id_saved, 'ss_show_in_menu', $ss_show_in_menu );
                update_post_meta( $id_saved, 'ss_page_title_seo', $ss_page_title_seo );
                update_post_meta( $id_saved, 'ss_header_visibility', $ss_header_visibility );
                update_post_meta( $id_saved, 'ss_sidebar_visibility', $ss_sidebar_visibility );

                echo "1";
            }
            else
            {
                // fail, tell js
                echo "0";
            }
        }
        else   // We're in create mode
        {
            $post_data[ "post_content" ] = "";

            $parent_id = $_POST[ "parent_id" ];
            $parent_id = intval( str_replace( "swiftypages-id-", "", $parent_id ) );
            $ref_post  = get_post( $parent_id );

            if ( "after" == $_POST[ "add_mode" ] )
            {
                // update menu_order of all pages below our page
                $wpdb->query( $wpdb->prepare( "UPDATE $wpdb->posts SET menu_order = menu_order+2 WHERE post_parent = %d AND menu_order >= %d AND id <> %d "
                                            , $ref_post->post_parent
                                            , $ref_post->menu_order
                                            , $ref_post->ID
                                            )
                            );

                // create a new page and then goto it
                $post_data[ "menu_order" ]  = $ref_post->menu_order + 1;
                $post_data[ "post_parent" ] = $ref_post->post_parent;
            }
            elseif ( "inside" == $_POST[ "add_mode" ] )
            {
                // update menu_order, so our new post is the only one with order 0
                $wpdb->query( $wpdb->prepare( "UPDATE $wpdb->posts SET menu_order = menu_order+1 WHERE post_parent = %d", $ref_post->ID ) );

                $post_data[ "menu_order" ]  = 0;
                $post_data[ "post_parent" ] = $ref_post->ID;
            }

            $id_saved = wp_insert_post( $post_data );

            if ( $id_saved )
            {
                add_post_meta( $id_saved, 'ss_show_in_menu', $ss_show_in_menu, 1 );
                add_post_meta( $id_saved, 'ss_page_title_seo', $ss_page_title_seo, 1 );
                add_post_meta( $id_saved, 'ss_header_visibility', $ss_header_visibility, 1 );
                add_post_meta( $id_saved, 'ss_sidebar_visibility', $ss_sidebar_visibility, 1 );

                echo "1";
            }
            else
            {
                // fail, tell js
                echo "0";
            }
        }
        exit;
    }

    public function ajax_delete_page()
    {
        $post_id = intval( $_POST[ "post_ID" ] );

        if ( isset( $post_id ) && !empty( $post_id ) )
        {
            $post_data = wp_delete_post( $post_id, true );

            if ( is_object( $post_data ) )
            {
                delete_post_meta( $post_id, 'ss_show_in_menu' );
                delete_post_meta( $post_id, 'ss_page_title_seo' );
                delete_post_meta( $post_id, 'ss_header_visibility' );
                delete_post_meta( $post_id, 'ss_sidebar_visibility' );

                echo "1";
            } else
            {
                // fail, tell js
                echo "0";
            }
        }

        exit;
    }

    public function ajax_post_settings() {
        $post_id = intval( $_REQUEST['post_ID'] );
        header( 'Content-Type: text/javascript' );

        $post_id   = intval( $_REQUEST[ 'post_ID' ] );
        $post      = get_post( $post_id );
        $post_meta = get_post_meta( $post_id );
        $post_status     = ( $post->post_status == 'private' ) ? 'publish' : $post->post_status; // _status
        $ss_show_in_menu = ( $post->post_status == 'private' ) ? 'hide' : 'show';

        $defaults = array( 'ss_show_in_menu'       => 'show'
                         , 'ss_page_title_seo'     => $post->post_title
                         , 'ss_header_visibility'  => 'hide'
                         , 'ss_sidebar_visibility' => 'hide'
                         );

        foreach ( $defaults as $key => $val ) {
            if ( !isset( $post_meta[ $key ] ) ) {
                $post_meta[ $key ] = $val;
            }
        }

        if ( $post_meta[ 'ss_show_in_menu' ] == 'show' ) {
            // post_status can be private, so then the page must not be visible in the menu.
            $post_meta[ 'ss_show_in_menu' ] = $ss_show_in_menu;
        }

        ?>
        var li = jQuery( 'li#cms-tpv-<?php echo $post_id; ?>' );

li.find( '> a' ).contents().filter( function() {
    if ( this.nodeType === 3 ) {
        this.nodeValue: <?php echo json_encode($post->post_title); ?>
    }

        li.find( 'input[name="post_title"]'  ).val(   <?php echo json_encode( $post->post_title ); ?>   );
        li.find( 'input[name="post_name"]'   ).val(   <?php echo json_encode( $post->post_name  ); ?>   );
        li.find( 'input[name="post_status"]' ).val( [ <?php echo json_encode( $post_status      ); ?> ] );
        li.find( 'input[name="ss_show_in_menu"]'       ).val( [ <?php echo json_encode( $post_meta[ 'ss_show_in_menu'       ] ); ?> ] );
        li.find( 'input[name="ss_page_title_seo"]'     ).val( [ <?php echo json_encode( $post_meta[ 'ss_page_title_seo'     ] ); ?> ] );
        li.find( 'input[name="ss_header_visibility"]'  ).val( [ <?php echo json_encode( $post_meta[ 'ss_header_visibility'  ] ); ?> ] );
        li.find( 'input[name="ss_sidebar_visibility"]' ).val( [ <?php echo json_encode( $post_meta[ 'ss_sidebar_visibility' ] ); ?> ] );

        li.find( 'input[name="post_title"]' ).val( <?php echo json_encode($post->post_title); ?> );
        li.find( 'input[name="post_name"]' ).val( <?php echo json_encode($post->post_name); ?> );
        <?php
        exit;
    }

    public function getTree() {
        if ( is_null( $this->_tree ) ) {
            $this->_tree = new StdClass();
            $this->_tree->menuItem = new StdClass();
            $this->_tree->menuItem->ID = 0; // Fake menu item as root.
            $this->_tree->children = array();
            $this->_byPageId = array();
            $this->_byPageId [ 0 ] = &$this->_tree;
            $mainMenuId = $this->_getMainMenuId();
            if ( $mainMenuId ) {
                $this->_addMenuPages( $mainMenuId, $this->_tree );
            }
            $this->_addAllPages();
        }
        return $this->_tree;
    }

    public function getJsonData( &$branch ) {
        $result = array();
        $childKeys = array_keys( $branch->children );
        foreach ( $childKeys as $childKey ) {
            $child = &$branch->children[$childKey];
            if ( isset( $child->page ) ) {
                $newBranch = $this->_get_pageJsonData( $child->page );
                /**
                 * if no children, output no state
                 * if viewing trash, don't get children. we watch them "flat" instead
                 */
                if ( $this->_view != "trash" ) {
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

    ////////////////////////////////////////////////////////////////////////////////////////

    protected function _addRoute( $route, $callable ) {
        $hookName = get_plugin_page_hookname( $route, '' );
        add_action( $hookName, $callable );
        global $_registered_pages;
        $_registered_pages[$hookName] = true;
    }

    protected function _addMenuPages( $menuId, &$parentBranch ) {
        $menuItems = wp_get_nav_menu_items( $menuId );
        /** @var WP_Post $menuItem */
        foreach ( $menuItems as $menuItem ) {
            if ( $menuItem->menu_item_parent == $parentBranch->menuItem->ID ) {
                $newBranch = new stdClass();
                $newBranch->menuItem = $menuItem;
                $newBranch->children = array();
                $this->_byPageId[ $menuItem->object_id ] = &$newBranch;
                $this->_addMenuPages( $menuId, $newBranch );
                $parentBranch->children[] = &$newBranch;
                unset( $newBranch );
            }
        }
    }

    protected function _addAllPages() {
        $args = array();
        $args['post_type'] = 'page';
        $args['post_status'] = 'any';
        $args['view'] = 'all';
        $args['numberposts'] = -1;
        $args['orderby'] = 'menu_order';
        $args['order'] = 'ASC';
        $pages = get_posts( $args );
        $added = true;
        while ( !empty($pages) && $added ) {
            $added = false;
            $keys = array_keys( $pages );
            foreach ( $keys as $key ) {
                $page = $pages[$key];
                if ( isset( $this->_byPageId[$page->ID] ) ) {
                    $branch = &$this->_byPageId[$page->ID];
                    $branch->page = $page;
                    unset( $branch );
                    unset( $pages[$key] );
                    $added = true;
                }
                else if ( isset( $this->_byPageId[ $page->post_parent ] ) ) {
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
     * @param integer $pageId
     */
    protected function &_getByPageId( $pageId ) {
        $result = null;
        if ( null == $this->_tree ) {
            $this->getTree();
        }
        if ( isset( $this->_byPageId[ $pageId ] ) ) {
            $result = &$this->_byPageId[ $pageId ];
        }
        return $result;
    }

    protected function _getMainMenuId() {
        $result = false;
        $menus = wp_get_nav_menus();
        if ( !empty( $menus[0] ) ) {
            $result = $menus[0]->term_id;
        }
        return $result;
    }

    protected function _get_pageJsonData( $onePage ) {
        $pageJsonData = array();

        $post          = $onePage;
        $page_id       = $onePage->ID;
        $arrChildPages = null;

        $post_statuses = get_post_statuses();
        $post_type_object = get_post_type_object( $this->_post_type );

        $editLink    = get_edit_post_link( $onePage->ID, 'notDisplay' );

        // type of node
        $rel = $onePage->post_status;
        if ( $onePage->post_password )
        {
            $rel = "password";
        }

        // modified time
        $post_modified_time = strtotime( $onePage->post_modified );
        $post_modified_time = date_i18n( get_option( 'date_format' ), $post_modified_time, false );

        // last edited by
        setup_postdata( $post );

        if ( $last_id = get_post_meta( $post->ID, '_edit_last', true ) )
        {
            $last_user = get_userdata( $last_id );
            if ( $last_user !== false )
            {
                $post_author = apply_filters( 'the_modified_author', $last_user->display_name );
            }
        }
        if ( empty( $post_author ) )
        {
            $post_author = __( "Unknown user", 'swiftypages' );
        }

        $title = get_the_title( $onePage->ID ); // so hooks and stuff will do their work
        if ( empty( $title ) )
        {
            $title = __( "<Untitled page>", 'swiftypages' );
        }

        $user_can_edit_page  = apply_filters( "cms_tree_page_view_post_can_edit", current_user_can( $post_type_object->cap->edit_post, $page_id ), $page_id );
        $user_can_add_inside = apply_filters( "cms_tree_page_view_post_user_can_add_inside", current_user_can( $post_type_object->cap->create_posts, $page_id ), $page_id );
        $user_can_add_after  = apply_filters( "cms_tree_page_view_post_user_can_add_after", current_user_can( $post_type_object->cap->create_posts, $page_id ), $page_id );
        $arr_page_css_styles = array();
        $arr_page_css_styles[] = "swiftypages_user_can_edit_page_" . ( $user_can_edit_page ? 'yes' : 'no' );
        $arr_page_css_styles[] = "swiftypages_user_can_add_page_inside_" . ( $user_can_add_inside ? 'yes' : 'no' );
        $arr_page_css_styles[] = "swiftypages_user_can_add_page_after_" . ( $user_can_add_after ? 'yes' : 'no' );

        $pageJsonData['data'] = array();
        $pageJsonData['data']['title'] = $title;
        $pageJsonData['data']['attr'] = array();
        $pageJsonData['data']['attr']['href'] = $editLink;

        $pageJsonData['attr'] = array();
        $pageJsonData['attr']['id'] = "swiftypages-id-" . $onePage->ID;
        $pageJsonData['attr']['class'] = join( ' ', $arr_page_css_styles );

        $pageJsonData['metadata'] = array();
        $pageJsonData['metadata']["id"] = "swiftypages-id-".$onePage->ID;
        $pageJsonData['metadata']["post_id"] = $onePage->ID;
        $pageJsonData['metadata']["post_type"] = $onePage->post_type;
        $pageJsonData['metadata']["post_status"] = $onePage->post_status;
        $pageJsonData['metadata']["post_status_translated"] = isset( $post_statuses[ $onePage->post_status ] ) ? $post_statuses[ $onePage->post_status ] : $onePage->post_status;
        $pageJsonData['metadata']["rel"] = $rel;
        $pageJsonData['metadata']["childCount"] = ( !empty( $arrChildPages ) ) ? sizeof( $arrChildPages ) : 0;
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
     * Get number of posts from WPML
     */
    protected function _get_wpml_post_counts( $post_type )
    {
        global $wpdb;

        $arr_statuses = array( "publish", "draft", "trash", "future", "private" );
        $arr_counts   = array();

        foreach ( $arr_statuses as $post_status )
        {
            $extra_cond = "";
            if ( $post_status )
            {
                $extra_cond .= " AND post_status = '" . $post_status . "'";
            }
            if ( $post_status != 'trash' )
            {
                $extra_cond .= " AND post_status <> 'trash'";
            }
            $extra_cond .= " AND post_status <> 'auto-draft'";
            $sql = "
			SELECT language_code, COUNT(p.ID) AS c FROM {$wpdb->prefix}icl_translations t
			JOIN {$wpdb->posts} p ON t.element_id=p.ID
			JOIN {$wpdb->prefix}icl_languages l ON t.language_code=l.code AND l.active = 1
			WHERE p.post_type='{$post_type}' AND t.element_type='post_{$post_type}' {$extra_cond}
			GROUP BY language_code
		";

            $res = $wpdb->get_results( $sql );

            $langs          = array();
            $langs[ 'all' ] = 0;
            foreach ( $res as $r )
            {
                $langs[ $r->language_code ] = $r->c;
                $langs[ 'all' ] += $r->c;
            }
            $arr_counts[ $post_status ] = $langs;
        }
        return $arr_counts;
    }

    protected function _getMenuItemDataForSave( $menuItem ) {
        $menu_item_data = array();
        $menu_item_data['menu-item-attr-title']  = $menuItem->attr_title;
        $menu_item_data['menu-item-classes']     = implode( ' ', $menuItem->classes );
        $menu_item_data['menu-item-db-id']       = $menuItem->db_id;
        $menu_item_data['menu-item-description'] = $menuItem->description;
        $menu_item_data['menu-item-object']      = $menuItem->object;
        $menu_item_data['menu-item-object-id']   = $menuItem->object_id;
        $menu_item_data['menu-item-parent-id']   = $menuItem->menu_item_parent;
        $menu_item_data['menu-item-position']    = $menuItem->menu_order;
        $menu_item_data['menu-item-target']      = $menuItem->target;
        $menu_item_data['menu-item-title']       = $menuItem->title;
        $menu_item_data['menu-item-type']        = $menuItem->post_type;
        $menu_item_data['menu-item-url']         = $menuItem->url;
        $menu_item_data['menu-item-xfn']         = $menuItem->xfn;
        return $menu_item_data;
    }
}

$SwiftyPages = new SwiftyPages();