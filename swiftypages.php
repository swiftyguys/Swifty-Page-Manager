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
        add_action( 'admin_head', array( $this, "admin_head" ) );
        add_action( 'admin_menu', array($this,'admin_menu') );
        add_action( 'wp_ajax_swiftypages_get_childs',    array( $this, 'ajax_get_childs' ) );
        add_action( 'wp_ajax_swiftypages_move_page',     array( $this, 'ajax_move_page' ) );
        add_action( 'wp_ajax_swiftypages_add_page',      array( $this, 'ajax_add_page' ) );
        add_action( 'wp_ajax_swiftypages_post_settings', array( $this, 'ajax_post_settings' ) );

    }

    function admin_head()
    {
        $currentScreen = get_current_screen();
        if ( 'pages_page_page-tree' == $currentScreen->base ) {
            add_filter( "views_" . $currentScreen->id, array( $this, "filter_views_edit_postsoverview" ) );
            require $this->plugin_dir . '/view/admin_head.php';
        }
    }

    public function admin_menu() {
        add_submenu_page( 'edit.php?post_type=page', __( 'SwiftyPages', 'swiftypages' )
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

    function ajax_get_childs()
    {
        header( "Content-type: application/json" );

        $action    = $_GET[ "action" ];
        $view      = $this->_view; // all | public | trash
        $post_type = 'page';
        $search    = ( isset( $_GET[ "search_string" ] ) ) ? trim( $_GET[ "search_string" ] ) : ""; // exits if we're doing a search

        // Check if user is allowed to get the list. For example subscribers should not be allowed to
        // Use same capability that is required to add the menu
        $post_type_object = get_post_type_object( $post_type );
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
                $sqlsearch = "%{$search}%";
                // feels bad to leave out the "'" in the query, but prepare seems to add it..??
                $sql            = $wpdb->prepare( "SELECT id, post_parent FROM $wpdb->posts WHERE post_type = 'page' AND post_title LIKE %s", $sqlsearch );
                $hits           = $wpdb->get_results( $sql );
                $arrNodesToOpen = array();
                foreach ( $hits as $oneHit )
                {
                    $arrNodesToOpen[ ] = $oneHit->post_parent;
                }

                $arrNodesToOpen  = array_unique( $arrNodesToOpen );
                $arrNodesToOpen2 = array();
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

                $arrNodesToOpen = array_merge( $arrNodesToOpen, $arrNodesToOpen2 );
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
                $this->_print_childs( $id, $jstree_open, $post_type );
                exit;
            }
        }

        exit;
    }

    /**
     * Output tree and html code for post overview page
     */
    function filter_views_edit_postsoverview( $filter_var )
    {

        $current_screen = get_current_screen();

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
        if ( is_post_type_hierarchical( $current_screen->post_type ) )
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

    function ajax_move_page()
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
                $wpdb->query( $wpdb->prepare( "UPDATE $wpdb->posts SET menu_order = menu_order+1 WHERE menu_order >= %d", $post_ref_node->menu_order + 1 ) );

                $post_to_save = array(
                    "ID"          => $post_node->ID,
                    "menu_order"  => $post_ref_node->menu_order,
                    "post_parent" => $post_ref_node->post_parent,
                    "post_type"   => $post_ref_node->post_type
                );
                wp_update_post( $post_to_save );

                echo "did before";

            }
            elseif ( "after" == $type )
            {

                // post_node is placed after ref_post_node

                // update menu_order of all posts with the same parent ref_post_node and with a menu_order of the same as ref_post_node, but do not include ref_post_node
                // +2 since multiple can have same menu order and we want our moved post to have a unique "spot"
                $wpdb->query( $wpdb->prepare( "UPDATE $wpdb->posts SET menu_order = menu_order+2 WHERE post_parent = %d AND menu_order >= %d AND id <> %d ", $post_ref_node->post_parent, $post_ref_node->menu_order, $post_ref_node->ID ) );

                // update menu_order of post_node to the same that ref_post_node_had+1
                #$wpdb->query( $wpdb->prepare( "UPDATE $wpdb->posts SET menu_order = %d, post_parent = %d WHERE ID = %d", $post_ref_node->menu_order+1, $post_ref_node->post_parent, $post_node->ID ) );

                $post_to_save = array(
                    "ID"          => $post_node->ID,
                    "menu_order"  => $post_ref_node->menu_order + 1,
                    "post_parent" => $post_ref_node->post_parent,
                    "post_type"   => $post_ref_node->post_type
                );
                wp_update_post( $post_to_save );

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

    function ajax_add_page()
    {
        global $wpdb;

        /*
        (
        [action] => swiftypages_add_page
        [pageID] => swiftypages-id-1318
        type
        )
        */
        $type       = $_POST[ "type" ];
        $pageID     = $_POST[ "pageID" ];
        $pageID     = str_replace( "swiftypages-id-", "", $pageID );
        $page_title = trim( $_POST[ "page_title" ] );
        $post_type  = $_POST[ "post_type" ];
        $wpml_lang  = $_POST[ "wpml_lang" ];
        if ( !$page_title )
        {
            $page_title = __( "New page", 'swiftypages' );
        }

        $ref_post = get_post( $pageID );

        if ( "after" == $type )
        {

            /*
                add page under/below ref_post
            */

            // update menu_order of all pages below our page
            $wpdb->query( $wpdb->prepare( "UPDATE $wpdb->posts SET menu_order = menu_order+2 WHERE post_parent = %d AND menu_order >= %d AND id <> %d ", $ref_post->post_parent, $ref_post->menu_order, $ref_post->ID ) );

            // create a new page and then goto it
            $post_new                   = array();
            $post_new[ "menu_order" ]   = $ref_post->menu_order + 1;
            $post_new[ "post_parent" ]  = $ref_post->post_parent;
            $post_new[ "post_type" ]    = "page";
            $post_new[ "post_status" ]  = "draft";
            $post_new[ "post_title" ]   = $page_title;
            $post_new[ "post_content" ] = "";
            $post_new[ "post_type" ]    = $post_type;
            $newPostID                  = wp_insert_post( $post_new );

        }
        else
        {
            if ( "inside" == $type )
            {

                /*
                    add page inside ref_post
                */

                // update menu_order, so our new post is the only one with order 0
                $wpdb->query( $wpdb->prepare( "UPDATE $wpdb->posts SET menu_order = menu_order+1 WHERE post_parent = %d", $ref_post->ID ) );

                $post_new                   = array();
                $post_new[ "menu_order" ]   = 0;
                $post_new[ "post_parent" ]  = $ref_post->ID;
                $post_new[ "post_type" ]    = "page";
                $post_new[ "post_status" ]  = "draft";
                $post_new[ "post_title" ]   = $page_title;
                $post_new[ "post_content" ] = "";
                $post_new[ "post_type" ]    = $post_type;
                $newPostID                  = wp_insert_post( $post_new );

            }
        }

        if ( $newPostID )
        {
            // return editlink for the newly created page
            $editLink = get_edit_post_link( $newPostID, '' );
            if ( $wpml_lang )
            {
                $editLink = add_query_arg( "lang", $wpml_lang, $editLink );
            }
            echo $editLink;
        }
        else
        {
            // fail, tell js
            echo "0";
        }


        exit;
    }

    public function ajax_post_settings() {
        $post_id = intval( $_REQUEST['post_ID'] );
        header( 'Content-Type: text/javascript' );
        $post = get_post($post_id);
        ?>
        var li = jQuery( 'li#swiftypages-id-<?php echo $post_id; ?>' );
        li.find( 'input[name="post_title"]' ).val( <?php echo json_encode($post->post_title); ?> );
        li.find( 'input[name="post_name"]' ).val( <?php echo json_encode($post->post_name); ?> );
        <?php
        exit;
    }

    ////////////////////////////////////////////////////////////////////////////////////////

    protected function _addRoute( $route, $callable ) {
        $hookName = get_plugin_page_hookname( $route, '' );
        add_action( $hookName, $callable );
        global $_registered_pages;
        $_registered_pages[$hookName] = true;
    }

    protected function _get_pages( $args = null )
    {
        $defaults = array(
            "post_type" => "page",
            "parent"    => "",
            "view"      => "all" // all | public | trash
        );
        $r        = wp_parse_args( $args, $defaults );

        $get_posts_args = array(
            "numberposts"         => "-1",
            "orderby"             => "menu_order title",
            "order"               => "ASC",
            // "caller_get_posts" => 1, // get sticky posts in natural order (or so I understand it anyway). Deprecated since 3.1
            "ignore_sticky_posts" => 1,
            // "post_type" => "any",
            "post_type"           => $r[ "post_type" ],
            "xsuppress_filters"   => "0"
        );
        if ( $r[ "parent" ] )
        {
            $get_posts_args[ "post_parent" ] = $r[ "parent" ];
        }
        else
        {
            $get_posts_args[ "post_parent" ] = "0";
        }
        if ( $r[ "view" ] == "all" )
        {
            $get_posts_args[ "post_status" ] = "any"; // "any" seems to get all but auto-drafts
        }
        elseif ( $r[ "view" ] == "trash" )
        {

            $get_posts_args[ "post_status" ] = "trash";

            // if getting trash, just get all pages, don't care about parent?
            // because otherwise we have to mix trashed pages and pages with other statuses. messy.
            $get_posts_args[ "post_parent" ] = null;

        }
        else
        {
            $get_posts_args[ "post_status" ] = "publish";
        }

        // does not work with plugin role scoper. don't know why, but this should fix it
        remove_action( "get_pages", array( 'ScoperHardway', 'flt_get_pages' ), 1, 2 );

        // does not work with plugin ALO EasyMail Newsletter
        remove_filter( 'get_pages', 'ALO_exclude_page' );

        #do_action_ref_array('parse_query', array(&$this));
        #print_r($get_posts_args);

        $pages = get_posts( $get_posts_args );

        // filter out pages for wpml, by applying same filter as get_pages does
        // only run if wpml is available or always?
        // Note: get_pages filter uses orderby comma separated and with the key sort_column
        $get_posts_args[ "sort_column" ] = str_replace( " ", ", ", $get_posts_args[ "orderby" ] );
        $pages                           = apply_filters( 'get_pages', $pages, $get_posts_args );

        return $pages;

    }

    protected function _print_childs( $pageID, $arrOpenChilds = null, $post_type ) {
        require $this->plugin_dir . '/view/print_childs.php';
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

}

$SwiftyPages = new SwiftyPages();