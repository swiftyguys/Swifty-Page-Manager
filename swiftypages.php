<?php

/*
Plugin Name: Swifty Page Manager
Description: Swifty Page Manager
Author: SwiftyGuys
Version: 0.0.1
Author URI: http://swiftysite.com/
Plugin URI: https://bitbucket.org/swiftyguys/SwiftyPages
*/

class SwiftyPages
{
    protected $plugin_file;
    protected $plugin_dir;
    protected $plugin_basename;
    protected $plugin_dir_url;

    public function __construct()
    {
        $this->plugin_file     = __FILE__ ;
        $this->plugin_dir      = dirname( $this->plugin_file );
        $this->plugin_basename = basename( $this->plugin_dir );
        $this->plugin_dir_url  = plugins_url( basename($this->plugin_dir) );
        define( "CMS_TPV_VERSION", "1.2.21" );
        define( "CMS_TPV_NAME", "CMS Tree Page View" );
        define( "CMS_TPV_URL", $this->plugin_dir_url . '/' );

        add_action( 'init', array( $this, 'cms_tpv_load_textdomain' ) );

// on admin init: add styles and scripts
        add_action( 'admin_init', array( $this, 'cms_tpv_admin_init' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'cms_admin_enqueue_scripts' ) );
        add_action( 'admin_init', array( $this, 'cms_tpv_save_settings' ) );

// Hook onto dashboard and admin menu
        add_action( 'admin_menu', array( $this, "cms_tpv_admin_menu" ) );
        add_action( 'admin_head', array( $this, "cms_tpv_admin_head" ) );
        add_action( 'wp_dashboard_setup', array( $this, "cms_tpv_wp_dashboard_setup" ) );

// Ajax hooks
        add_action( 'wp_ajax_cms_tpv_get_childs', array( $this, 'cms_tpv_get_childs' ) );
        add_action( 'wp_ajax_cms_tpv_move_page', array( $this, 'cms_tpv_move_page' ) );
        add_action( 'wp_ajax_cms_tpv_add_page', array( $this, 'cms_tpv_add_page' ) );
        add_action( 'wp_ajax_cms_tpv_add_pages', array( $this, 'cms_tpv_add_pages' ) );
        add_action( 'wp_ajax_swiftypages_post_settings', array( $this, 'wp_ajax_swiftypages_post_settings' ) );

// activation
        register_activation_hook( $this->plugin_file, array( $this, 'cms_tpv_install' ) );

// catch upgrade
        add_action( 'plugins_loaded', array( $this, 'cms_tpv_plugins_loaded' ), 1 );
    }

    public function __call( $method, $args ) {
        if ( preg_match( '/^cms_tpv_dashboard__(\w+)$/', $method, $matches ) ) {
            $post_type = $matches[1];
            $this->cms_tpv_print_common_tree_stuff( $post_type );
        }
        else {
            throw new Exception( sprintf( 'SwiftyPages: unknown method called: %s', $method ) );
        }
    }

    /**
     * Example how to use action cms_tree_page_view_post_can_edit to modify if a user can edit the page/post
     */
    /*
    add_action("cms_tree_page_view_post_can_edit", function($can_edit, $post_id) {

        if ($post_id === 163) $can_edit = FALSE;

        return $can_edit;

    }, 10, 2);


    add_action("cms_tree_page_view_post_user_can_add_inside", function($can_edit, $post_id) {

        if ($post_id === 233) $can_edit = FALSE;

        return $can_edit;

    }, 10, 2);

    add_action("cms_tree_page_view_post_user_can_add_after", function($can_edit, $post_id) {

        if ($post_id === 142) $can_edit = FALSE;

        return $can_edit;

    }, 10, 2);
    */

    /**
     * Check if a post type is ignored
     */
    function cms_tpv_post_type_is_ignored( $post_type )
    {

        $ignored_post_types = $this->cms_tpv_get_ignored_post_types();

        return in_array( $post_type, $ignored_post_types );

    }

    /**
     * Returns a list of ignored post types
     * These are post types used by plugins etc.
     */
    function cms_tpv_get_ignored_post_types()
    {
        return array(
            // advanced custom fields
            "acf"
        );
    }

    /**
     * Use the ajax action-thingie to catch our form with new pages
     * Add pages and then redirect to...?
     */
    function cms_tpv_add_pages()
    {

        #sf_d($_POST);exit;
        /*
        Array
        (
            [action] => cms_tpv_add_pages
            [cms_tpv_add_new_pages_names] => Array
                (
                    [0] => xxxxx
                    [1] => yyyy
                    [2] =>
                )

            [cms_tpv_add_type] => inside
            [cms_tpv_add_status] => draft
            [lang] => de
        )
        */

        $post_position = $_POST[ "cms_tpv_add_type" ];
        $post_status   = $_POST[ "cms_tpv_add_status" ];
        $post_names    = (array) $_POST[ "cms_tpv_add_new_pages_names" ];
        $ref_post_id   = (int) $_POST[ "ref_post_id" ];
        $lang          = $_POST[ "lang" ];

        // Check nonce
        if ( !check_admin_referer( "cms-tpv-add-pages" ) )
        {
            wp_die( __( 'Cheatin&#8217; uh?' ) );
        }

        // If lang variable is set, then set some more wpml-related post/get-variables
        if ( $lang )
        {
            // post seems to fix creating new posts in selcted lang
            $_POST[ "icl_post_language" ] = $lang;
            // $_GET["lang"] = $lang;
        }

        // make sure the status is publish and nothing else (yes, perhaps I named it bad elsewhere)
        if ( "published" === $post_status )
        {
            $post_status = "publish";
        }

        // remove possibly empty posts
        $arr_post_names = array();
        foreach ( $post_names as $one_post_name )
        {
            if ( trim( $one_post_name ) )
            {
                $arr_post_names[ ] = $one_post_name;
            }
        }

        $arr_post_names_count = sizeof( $arr_post_names );

        // check that there are pages left
        if ( empty( $arr_post_names ) )
        {
            die( "Error: no pages to add." );
        }

        $ref_post = get_post( $ref_post_id );
        if ( null === $ref_post )
        {
            die( "Error: could not load reference post." );
        }

        // Make room for our new pages
        // Get all pages at a level level and loop until our reference page
        // and then all pages after that one will get it's menu_order
        // increased by the same number as the number of new posts we're gonna add

        $ok_to_continue_by_permission = true;
        $post_type_object             = get_post_type_object( $ref_post->post_type );

        $post_parent = 0;
        if ( "after" === $post_position )
        {
            $post_parent                  = $ref_post->post_parent;
            $ok_to_continue_by_permission = apply_filters( "cms_tree_page_view_post_user_can_add_after", current_user_can( $post_type_object->cap->create_posts, $ref_post_id ), $ref_post_id );
        }
        elseif ( "inside" === $post_position )
        {
            $post_parent                  = $ref_post->ID;
            $ok_to_continue_by_permission = apply_filters( "cms_tree_page_view_post_user_can_add_inside", current_user_can( $post_type_object->cap->create_posts, $ref_post_id ), $ref_post_id );
        }

        if ( !$ok_to_continue_by_permission )
        {
            wp_die( __( 'Cheatin&#8217; uh?' ) );
            return false;
        }

//	$user_can_edit_page = apply_filters("cms_tree_page_view_post_can_edit", current_user_can( $post_type_object->cap->edit_post, $ref_post_id), $ref_post_id);


        /*
        perhaps for wpml:
        suppress_filters=0

        */

        $args = array(
            "post_status"      => "any",
            "post_type"        => $ref_post->post_type,
            "numberposts"      => -1,
            "offset"           => 0,
            "orderby"          => 'menu_order',
            'order'            => 'asc',
            'post_parent'      => $post_parent,
            "suppress_filters" => false
        );
        //if ($lang) $args["lang"] = $lang;
        $posts = get_posts( $args );

        #sf_d($_GET["lang"]);sf_d($args);sf_d($posts);exit;

        // If posts exist at this level, make room for our new pages by increasing the menu order
        if ( sizeof( $posts ) > 0 )
        {

            if ( "after" === $post_position )
            {

                $has_passed_ref_post = false;
                foreach ( $posts as $one_post )
                {

                    if ( $has_passed_ref_post )
                    {

                        $post_update = array(
                            "ID"         => $one_post->ID,
                            "menu_order" => $one_post->menu_order + $arr_post_names_count
                        );
                        $return_id   = wp_update_post( $post_update );
                        if ( 0 === $return_id )
                        {
                            die( "Error: could not update post with id " . $post_update->ID . "<br>Technical details: " . print_r( $post_update ) );
                        }

                    }

                    if ( !$has_passed_ref_post && $ref_post->ID === $one_post->ID )
                    {
                        $has_passed_ref_post = true;
                    }

                }

                $new_menu_order = $ref_post->menu_order;

            }
            elseif ( "inside" === $post_position )
            {

                // in inside, place at beginning
                // so just get first post and use that menu order as base
                $new_menu_order = $posts[ 0 ]->menu_order - $arr_post_names_count;

            }


        }
        else
        {

            // no posts, start at 0
            $new_menu_order = 0;

        }

        $post_parent_id = null;
        if ( "after" === $post_position )
        {
            $post_parent_id = $ref_post->post_parent;
        }
        elseif ( "inside" === $post_position )
        {
            $post_parent_id = $ref_post->ID;
        }

        // Done maybe updating menu orders, add the new pages
        $arr_added_pages_ids = array();
        foreach ( $arr_post_names as $one_new_post_name )
        {

            $new_menu_order++;
            $newpost_args = array(
                "menu_order"  => $new_menu_order,
                "post_parent" => $post_parent_id,
                "post_status" => $post_status,
                "post_title"  => $one_new_post_name,
                "post_type"   => $ref_post->post_type
            );
            $new_post_id  = wp_insert_post( $newpost_args );

            if ( 0 === $new_post_id )
            {
                die( "Error: could not add post" );
            }

            $arr_added_pages_ids[ ] = $new_post_id;


        }

        // Done. Redirect to the first page created.
        $first_post_edit_link = get_edit_post_link( $arr_added_pages_ids[ 0 ], "" );
        //wp_redirect($first_post_edit_link);

        echo '<script type="text/javascript">window.location=\'edit.php?post_type=page&page=cms-tpv-page-page\';</script>';

        exit;

    }


    /**
     * Output and add hooks in head
     */
    function cms_tpv_admin_head()
    {
        require $this->plugin_dir . '/includes/cms_tpv_admin_head.php';
    }

    /**
     * Detect if we are on a page that use CMS Tree Page View
     */
    function cms_tpv_is_one_of_our_pages()
    {

        $options   = $this->cms_tpv_get_options();
        $post_type = $this->cms_tpv_get_selected_post_type();

        if ( !function_exists( "get_current_screen" ) )
        {
            return false;
        }

        $current_screen = get_current_screen();
        $is_plugin_page = false;

        // Check if current page is one of the ones defined in $options["menu"]
        foreach ( $options[ "menu" ] as $one_post_type )
        {
            if ( strpos( $current_screen->id, "_page_cms-tpv-page-{$one_post_type}" ) !== false )
            {
                $is_plugin_page = true;
                break;
            }
        }

        // Check if current page is one of the ones defined in $options["postsoverview"]
        if ( $current_screen->base === "edit" && in_array( $current_screen->post_type, $options[ "postsoverview" ] ) )
        {
            $is_plugin_page = true;
        }

        if ( $current_screen->id === "settings_page_cms-tpv-options" )
        {
            // Is settings page for plugin
            $is_plugin_page = true;
        }
        elseif ( $current_screen->id === "dashboard" && !empty( $options[ "dashboard" ] ) )
        {
            // At least one post type is enabled to be visible on dashboard
            $is_plugin_page = true;
        }

        return $is_plugin_page;

    }

    /**
     * Add styles and scripts to pages that use the plugin
     */
    function cms_admin_enqueue_scripts()
    {

        if ( $this->cms_tpv_is_one_of_our_pages() )
        {

            // renamed from cookie to fix problems with mod_security
            wp_enqueue_script( "jquery-cookie", CMS_TPV_URL . "scripts/jquery.biscuit.js", array( "jquery" ) );
            wp_enqueue_script( "jquery-ui-sortable" );
            wp_enqueue_script( "jquery-jstree", CMS_TPV_URL . "scripts/jquery.jstree.js", false, CMS_TPV_VERSION );
            wp_enqueue_script( "jquery-alerts", CMS_TPV_URL . "scripts/jquery.alerts.js", false, CMS_TPV_VERSION );
            // wp_enqueue_script( "hoverIntent");
            wp_enqueue_script( "cms_tree_page_view", CMS_TPV_URL . "scripts/cms_tree_page_view.js", false, CMS_TPV_VERSION );

            wp_enqueue_style( "cms_tpv_styles", CMS_TPV_URL . "styles/styles.css", false, CMS_TPV_VERSION );
            wp_enqueue_style( "jquery-alerts", CMS_TPV_URL . "styles/jquery.alerts.css", false, CMS_TPV_VERSION );

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
            wp_localize_script( "cms_tree_page_view", 'cmstpv_l10n', $oLocale );

        }

    }

    function cms_tpv_load_textdomain()
    {
        // echo "load textdomain";
        if ( is_admin() )
        {
            load_plugin_textdomain( 'swiftypages', WP_CONTENT_DIR . "/plugins/languages", "/".$this->plugin_basename."/languages" );
        }
    }

    function cms_tpv_admin_init()
    {

        // DEBUG
        //wp_enqueue_script( "jquery-hotkeys" );

        // add row to plugin page
        add_filter( 'plugin_row_meta', array( $this, 'cms_tpv_set_plugin_row_meta' ), 10, 2 );

        // @todo: register settings
        #add_settings_section("cms_tree_page_view_settings", "cms_tree_page_view", "", "");
        #register_setting( 'cms_tree_page_view_settings', "post-type-dashboard-post" );


    }

    /**
     * Check if this is a post overview page and that plugin is enabled for this overview page
     */
    function cms_tpv_setup_postsoverview()
    {

        $options        = $this->cms_tpv_get_options();
        $current_screen = get_current_screen();

        if ( "edit" === $current_screen->base && in_array( $current_screen->post_type, $options[ "postsoverview" ] ) )
        {

            // Ok, this is a post overview page that we are enabled for
            add_filter( "views_" . $current_screen->id, array( $this, "cmstpv_filter_views_edit_postsoverview" ) );

            $this->cmstpv_postoverview_head();

        }

    }

    /**
     * Add style etc to wp head to minimize flashing content
     */
    function cmstpv_postoverview_head()
    {
        require $this->plugin_dir . '/includes/cmstpv_postoverview_head.php';
    }

    /**
     * Output tree and html code for post overview page
     */
    function cmstpv_filter_views_edit_postsoverview( $filter_var )
    {

        $current_screen = get_current_screen();

        ob_start();
        $this->cms_tpv_print_common_tree_stuff();
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
        $title  = __( "Tree View", 'swiftypages' );
        $tree_a = "<a href='" . esc_url( add_query_arg( 'mode', $mode, $_SERVER[ 'REQUEST_URI' ] ) ) . "' $class> <img id='view-switch-$mode' src='" . esc_url( includes_url( 'images/blank.gif' ) ) . "' width='20' height='20' title='$title' alt='$title' /></a>\n";

        // Copy of wordpress own, if it does not exist
        $wp_list_a = "";
        if ( is_post_type_hierarchical( $current_screen->post_type ) )
        {

            $mode      = "list";
            $class     = isset( $_GET[ "mode" ] ) && $_GET[ "mode" ] != $mode ? " class='cmstpv_add_list_view' " : " class='cmstpv_add_list_view current' ";
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
			<div class="cmstpv-postsoverview-wrap">
				%1$s
			</div>
		', $tree_common_stuff );

        }

        echo $out;

        return $filter_var;

    }


    /**
     * Add settings link to plugin page
     * Hopefully this helps some people to find the settings page quicker
     */
    function cms_tpv_set_plugin_row_meta( $links, $file )
    {

        if ( $file === basename(dirname($this->plugin_file)).'/'.basename($this->plugin_file) )
        {
            return array_merge(
                $links,
                array( sprintf( '<a href="options-general.php?page=%s">%s</a>', "cms-tpv-options", __( 'Settings' ) ) )
            );
        }
        return $links;

    }


    /**
     * Save settings, called when saving settings in general > cms tree page view
     */
    function cms_tpv_save_settings()
    {

        if ( isset( $_POST[ "cms_tpv_action" ] ) && $_POST[ "cms_tpv_action" ] == "save_settings" && check_admin_referer( 'update-options' ) )
        {

            $options                    = array();
            $options[ "dashboard" ]     = isset( $_POST[ "post-type-dashboard" ] ) ? (array) $_POST[ "post-type-dashboard" ] : array();
            $options[ "menu" ]          = isset( $_POST[ "post-type-menu" ] ) ? (array) $_POST[ "post-type-menu" ] : array();
            $options[ "postsoverview" ] = isset( $_POST[ "post-type-postsoverview" ] ) ? (array) $_POST[ "post-type-postsoverview" ] : array();

            update_option( 'cms_tpv_options', $options );

        }

    }

    /**
     * Add widget to dashboard
     */
    function cms_tpv_wp_dashboard_setup()
    {

        // echo "setup dashboard";

        // add dashboard to capability edit_pages only
        if ( current_user_can( "edit_pages" ) )
        {
            $options = $this->cms_tpv_get_options();
            foreach ( $options[ "dashboard" ] as $one_dashboard_post_type )
            {
                $post_type_object = get_post_type_object( $one_dashboard_post_type );
                if ( !empty( $post_type_object ) )
                {
                    $widget_name = sprintf( _x( '%1$s Tree', "name of dashboard", 'swiftypages' ), $post_type_object->labels->name );
                    wp_add_dashboard_widget( "cms_tpv_dashboard_widget_{$one_dashboard_post_type}"
                                           , $widget_name
                                           , array( $this, 'cms_tpv_dashboard__'.$one_dashboard_post_type ) ); // automagic funtion
                }
            }
        }

    }

// Add items to the wp admin menu
    function cms_tpv_admin_menu()
    {

        // add
        $options = $this->cms_tpv_get_options();

        foreach ( $options[ "menu" ] as $one_menu_post_type )
        {

            if ( $this->cms_tpv_post_type_is_ignored( $one_menu_post_type ) )
            {
                continue;
            }

            // post is a special one.
            if ( $one_menu_post_type == "post" )
            {
                $slug = "edit.php";
            }
            else
            {
                $slug = "edit.php?post_type=$one_menu_post_type";
            }

            $post_type_object = get_post_type_object( $one_menu_post_type );

            // Only try to add menu if we got a valid post type object
            // I think you can get a notice message here if you for example have enabled
            // the menu for a custom post type that you later on remove?
            if ( !empty( $post_type_object ) )
            {

                $menu_name  = _x( "Tree View", "name in menu", 'swiftypages' );
                $page_title = sprintf( _x( '%1$s Tree View', "title on page with tree", 'swiftypages' ), $post_type_object->labels->name );
                add_submenu_page( $slug, $page_title, $menu_name, $post_type_object->cap->edit_posts, "cms-tpv-page-$one_menu_post_type", array( $this, "cms_tpv_pages_page" ) );

            }
        }

        add_submenu_page( 'options-general.php', CMS_TPV_NAME, CMS_TPV_NAME, "administrator", "cms-tpv-options", array($this,"cms_tpv_options") );

    }

    /**
     * Output options page
     */
    function cms_tpv_options()
    {
        require $this->plugin_dir . '/includes/cms_tpv_options.php';
    }

    /**
     * Load settings
     * @return array with options
     */
    function cms_tpv_get_options()
    {

        $arr_options = (array) get_option( 'cms_tpv_options' );

        if ( array_key_exists( 'dashboard', $arr_options ) )
        {
            $arr_options[ 'dashboard' ] = (array) @$arr_options[ 'dashboard' ];
        }
        else
        {
            $arr_options[ 'dashboard' ] = array();
        }

        if ( array_key_exists( 'menu', $arr_options ) )
        {
            $arr_options[ 'menu' ] = (array) @$arr_options[ 'menu' ];
        }
        else
        {
            $arr_options[ 'menu' ] = array();
        }

        if ( array_key_exists( 'postsoverview', $arr_options ) )
        {
            $arr_options[ 'postsoverview' ] = (array) @$arr_options[ 'postsoverview' ];
        }
        else
        {
            $arr_options[ 'postsoverview' ] = array();
        }

        return $arr_options;

    }

    function cms_tpv_get_selected_post_type()
    {
        // fix for Ozh' Admin Drop Down Menu that does something with the urls
        // movies funkar:
        // http://localhost/wp-admin/edit.php?post_type=movies&page=cms-tpv-page-xmovies
        // movies funkar inte:
        // http://localhost/wp-admin/admin.php?page=cms-tpv-page-movies
        $post_type = null;
        if ( isset( $_GET[ "post_type" ] ) )
        {
            $post_type = $_GET[ "post_type" ];
        }
        if ( !$post_type )
        {
            // no post type, happens with ozh admin drop down, so get it via page instead
            $page      = isset( $_GET[ "page" ] ) ? $_GET[ "page" ] : "";
            $post_type = str_replace( "cms-tpv-page-", "", $page );
        }

        if ( !$post_type )
        {
            $post_type = "post";
        }
        return $post_type;
    }

    /**
     * Determine if a post type is considered hierarchical
     */
    function cms_tpv_is_post_type_hierarchical( $post_type_object )
    {
        $is_hierarchical = $post_type_object->hierarchical;
        // special case for posts, fake-support hierachical
        if ( "post" == $post_type_object->name )
        {
            $is_hierarchical = true;
        }
        return $is_hierarchical;
    }

    /**
     * Get number of posts from WPML
     */
    function cms_tpv_get_wpml_post_counts( $post_type )
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


    /**
     * Print tree stuff that is common for both dashboard and page
     */
    function cms_tpv_print_common_tree_stuff( $post_type = "" )
    {
        require $this->plugin_dir . '/includes/cms_tpv_print_common_tree_stuff.php';
    }


    /**
     * Pages page
     * A page with the tree. Good stuff.
     */
    function cms_tpv_pages_page()
    {
        require $this->plugin_dir . '/includes/cms_tpv_pages_page.php';
    }

    /**
     * Get the pages
     */
    function cms_tpv_get_pages( $args = null )
    {

        global $wpdb;

        $defaults = array(
            "post_type" => "post",
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

    /**
     * Output JSON for the children of a node
     * $arrOpenChilds = array with id of pages to open children on
     */
    function cms_tpv_print_childs( $pageID, $view = "all", $arrOpenChilds = null, $post_type )
    {
        require $this->plugin_dir . '/includes/cms_tpv_print_childs.php';
    }

// Act on AJAX-call
// Get pages
    function cms_tpv_get_childs()
    {

        header( "Content-type: application/json" );

        $action    = $_GET[ "action" ];
        $view      = $_GET[ "view" ]; // all | public | trash
        $post_type = ( isset( $_GET[ "post_type" ] ) ) ? $_GET[ "post_type" ] : null;
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
                #foreach ($arrNodesToOpen as $oneNodeID) {
                #	$sReturn .= "cms-tpv-{$oneNodeID},";
                #}
                #$sReturn = preg_replace("/,$/", "", $sReturn);

                foreach ( $arrNodesToOpen as $oneNodeID )
                {
                    $sReturn .= "\"#cms-tpv-{$oneNodeID}\",";
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
                $id = (int) str_replace( "cms-tpv-", "", $id );

                $jstree_open = array();
                if ( isset( $_COOKIE[ "jstree_open" ] ) )
                {
                    $jstree_open = $_COOKIE[ "jstree_open" ]; // like this: [jstree_open] => cms-tpv-1282,cms-tpv-1284,cms-tpv-3
                    #var_dump($jstree_open); string(22) "#cms-tpv-14,#cms-tpv-2"
                    $jstree_open = explode( ",", $jstree_open );
                    for ( $i = 0; $i < sizeof( $jstree_open ); $i++ )
                    {
                        $jstree_open[ $i ] = (int) str_replace( "#cms-tpv-", "", $jstree_open[ $i ] );
                    }
                }
                $this->cms_tpv_print_childs( $id, $view, $jstree_open, $post_type );
                exit;
            }
        }

        exit;
    }

    /**
     * @TODO: check if this is used any longer? If not then delete it!
     */
    function cms_tpv_add_page()
    {
        global $wpdb;

        /*
        (
        [action] => cms_tpv_add_page
        [pageID] => cms-tpv-1318
        type
        )
        */
        $type       = $_POST[ "type" ];
        $pageID     = $_POST[ "pageID" ];
        $pageID     = str_replace( "cms-tpv-", "", $pageID );
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


// AJAX: perform move of article
    function cms_tpv_move_page()
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

        $node_id     = str_replace( "cms-tpv-", "", $node_id );
        $ref_node_id = str_replace( "cms-tpv-", "", $ref_node_id );

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

    /**
     * Install function
     * Called from hook register_activation_hook()
     */
    function cms_tpv_install()
    {
        // first install or pre custom posts version:
        // make sure pages are enabled by default
        $this->cms_tpv_setup_defaults();

        // set to current version
        update_option( 'cms_tpv_version', CMS_TPV_VERSION );
    }

    // cms_tpv_install();

    /**
     * setup some defaults
     */
    function cms_tpv_setup_defaults()
    {

        // check and update version
        $version = get_option( 'cms_tpv_version', 0 );
        #$version = 0; // uncomment to test default settings

        if ( $version <= 0 )
        {
            #error_log("tree: setup defaults, beacuse db version less than 0");
            $options = array();

            // Add pages to both dashboard and menu
            $options[ "dashboard" ] = array( "page" );

            // since 0.10.1 enable menu for all hierarchical custom post types
            // since 1.2 also enable on post overview page
            $post_types = get_post_types( array(
                                              "show_ui"      => true,
                                              "hierarchical" => true
                                          ), "objects" );

            foreach ( $post_types as $one_post_type )
            {
                $options[ "menu" ][ ]          = $one_post_type->name;
                $options[ "postsoverview" ][ ] = $one_post_type->name;
            }

            $options[ "menu" ]          = array_unique( $options[ "menu" ] );
            $options[ "postsoverview" ] = array_unique( $options[ "postsoverview" ] );

            update_option( 'cms_tpv_options', $options );

        }

    }

    /**
     * when plugins are loaded, check if current plugin version is same as stored
     * if not = it's an upgrade. right?
     */
    function cms_tpv_plugins_loaded( $a )
    {
        $installed_version = get_option( 'cms_tpv_version', 0 );
        #echo "installed_version: $installed_version";
        #echo "<br>" . CMS_TPV_VERSION;
        if ( $installed_version != CMS_TPV_VERSION )
        {
            // new version!
            update_option( 'cms_tpv_version', CMS_TPV_VERSION );
        }

    }

    /**
     * modified version of get_the_modified_author() that checks that user was retrieved before applying filters
     * according to http://wordpress.org/support/topic/better-wp-security-conflict-1?replies=7 some users
     * had problems when a user had been deleted
     */
    function cms_tpv_get_the_modified_author()
    {
        if ( $last_id = get_post_meta( get_post()->ID, '_edit_last', true ) )
        {
            $last_user = get_userdata( $last_id );
            if ( $last_user !== false )
            {
                return apply_filters( 'the_modified_author', $last_user->display_name );
            }
        }
    }

    public function wp_ajax_swiftypages_post_settings() {
        $post_id = intval( $_REQUEST['post_ID'] );
        header( 'Content-Type: text/javascript' );
        $post = get_post($post_id);
?>
var li = jQuery( 'li#cms-tpv-<?php echo $post_id; ?>' );
li.find( 'input[name="cms_tpv_add_new_pages_names[]"]' ).val( <?php echo json_encode($post->post_title); ?> );
li.find( 'input[name="post_name"]' ).val( <?php echo json_encode($post->post_name); ?> );
<?php
        exit;
    }

}

$SwiftyPages = new SwiftyPages();