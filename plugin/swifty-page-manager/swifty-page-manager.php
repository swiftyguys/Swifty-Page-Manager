<?php
/*
Plugin Name: Swifty Page Manager
Description: Easily create, move and delete pages. Manage page settings.
Author: SwiftyLife
Version: // @echo RELEASE_TAG
Author URI: http://swiftylife.com/plugins/
Plugin URI: http://swiftylife.com/plugins/swifty-page-manager/
*/

class SwiftyPageManager
{
    protected $plugin_file;
    protected $plugin_dir;
    protected $plugin_basename;
    protected $plugin_dir_url;
    protected $plugin_url;
    protected $_plugin_version = '/* @echo RELEASE_TAG */';
    protected $_post_status = 'any';
    protected $_post_type = 'page';
    protected $_tree = null;
    protected $_by_page_id = null;
    protected $is_swifty = false;
    protected $swifty_admin_page = 'swifty_page_manager_admin';

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->plugin_file     = __FILE__ ;
        $this->plugin_dir      = dirname( $this->plugin_file );
        $this->plugin_basename = basename( $this->plugin_dir );
        $this->plugin_dir_url  = plugins_url( rawurlencode( basename( $this->plugin_dir ) ) );
        $this->plugin_url      = $_SERVER['REQUEST_URI'];

        if ( ! class_exists( 'LibSwiftyPluginView' ) ) {
            require_once plugin_dir_path( __FILE__ ) . 'lib/swifty_plugin/lib_swifty_plugin_view.php';
        }

        $this->is_swifty = LibSwiftyPluginView::is_ss_mode();

        // Actions for visitors viewing the site
        if ( $this->is_swifty ) {
            add_filter( 'page_link',         array( $this, 'page_link' ), 10, 2 );
            add_action( 'parse_request',     array( $this, 'parse_request' ) );
            add_filter( 'wp_title',          array( $this, 'seo_wp_title' ), 10, 2 );
            add_filter( 'admin_footer_text', array( $this, 'empty_footer_text' ) );
            add_filter( 'update_footer',     array( $this, 'empty_footer_text' ), 999 );
        }

        // Actions for admins, warning: is_admin is not a security check
        if ( is_admin() ) {
            add_action( 'init', array( $this, 'admin_init' ) );
        }

        // @if PROBE='include'
        // Swifty Probe Module include (used for testing and gamification)
        add_action( 'admin_enqueue_scripts', array( $this, 'add_module_swifty_probe' ) );
        // @endif
    }

    public function empty_footer_text() {
        return '';
    }

    /**
     * Called via WP Action 'init' if is_admin
     *
     * Load translations
     */
    function admin_init()
    {
        if ( current_user_can( 'edit_pages' ) ) {
            if ( ! empty( $_GET['status'] ) ) {
                $this->_post_status = $_GET['status'];
            }

            load_plugin_textdomain( 'swifty-page-manager', false, '/swifty-page-manager/languages' );

            add_action( 'admin_head', array( $this, 'admin_head' ) );
            add_action( 'admin_menu', array( $this, 'admin_menu') );
            add_action( 'wp_ajax_spm_get_childs',    array( $this, 'ajax_get_childs' ) );
            add_action( 'wp_ajax_spm_save_page',     array( $this, 'ajax_save_page' ) );
            add_action( 'wp_ajax_spm_post_settings', array( $this, 'ajax_post_settings' ) );
            add_action( 'wp_ajax_spm_move_page',     array( $this, 'ajax_move_page' ) );

            if ( current_user_can( 'delete_pages' ) ) {
                add_action( 'wp_ajax_spm_delete_page', array( $this, 'ajax_delete_page' ) );
            }

            if ( current_user_can( 'publish_pages' ) ) {
                add_action( 'wp_ajax_spm_publish_page', array( $this, 'ajax_publish_page' ) );
            }

            add_action( 'admin_enqueue_scripts', array( $this, 'add_plugin_css' ) );

            if ( $this->is_swifty ) {
                add_action( 'wp_ajax_spm_sanitize_url', array( $this, 'ajax_sanitize_url' ) );
                add_action( 'save_post',           array( $this, 'restore_page_status' ), 10, 2 );
                add_filter( 'wp_insert_post_data', array( $this, 'set_tmp_page_status' ), 10, 2 );
                add_filter( 'wp_list_pages',       array( $this, 'wp_list_pages' ) );
                add_filter( 'status_header',       array( $this, 'status_header' ) );
            }

            if ( ! class_exists( 'LibSwiftyPlugin' ) ) {
                require_once plugin_dir_path( __FILE__ ) . 'lib/swifty_plugin/lib_swifty_plugin.php';
                new LibSwiftyPlugin();
            }
        }
    }

    /**
    /**
     * @param string $title
     * @param string $sep
     * @return string
     */
    public function seo_wp_title( $title, $sep )
    {
        if ( is_feed() ) {
            return $title;
        }

        $seoTitle = get_post_meta( get_the_ID(), 'spm_page_title_seo', true );

        if ( ! empty( $seoTitle ) ) {
            return "$seoTitle $sep ";
        }

        return $title;
    }

    /**
     * Called via WP Filter 'wp_insert_post_data', if can_edit_pages && is_swifty
     *
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
     * Called via WP Action 'save_post', if can_edit_pages && is_swifty
     *
     * @param integer $post_id
     * @param WP_Post $post
     */
    public function restore_page_status( $post_id, $post )
    {
        if ( ! current_user_can( 'edit_pages' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page. #314' ) );
        }

        /** @var wpdb $wpdb - Wordpress Database */
        global $wpdb;

        // Check it's not an auto save routine
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! wp_is_post_revision( $post_id ) &&
              $post->post_type   === 'page'   &&
              $post->post_status === '__TMP__'
        ) {
            $wpdb->query( $wpdb->prepare( "UPDATE $wpdb->posts SET post_status = '%s' WHERE id = %d",
                                          $_POST['post_status'],
                                          $post_id ) );
        }
    }

    /**
     * Called via WP Action 'parse_request' if is_swifty
     *
     * Action function to make our overridden URLs work by changing the query params.
     *
     * @param wp $wp - WordPress object
     */
    public function parse_request( &$wp )
    {
        /** @var wpdb $wpdb - Wordpress Database */
        global $wpdb;

        if ( ! empty( $wp->request ) ) {
            $query = $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key='spm_url' AND meta_value='%s'",
                                     $wp->request );
            $post_id = $wpdb->get_var( $query );

            if ( $post_id ) {
                if  ( 'page' == get_option('show_on_front') && $post_id == get_option('page_for_posts') ) {
                    // Workaround to be able to show the blog posts on a page with custom URL
                    $post = get_post( $post_id );
                    $wp->query_vars = array( 'pagename', $post->post_name );
                } else {
                    $wp->query_vars = array( 'p' => $post_id, 'post_type' => 'page' );
                }
            }
        }
    }

    /**
     * Called via WP Filter 'page_link', if is_swifty
     *
     * Filter function called when the link to a page is needed.
     * We return our custom URL if it has been set.
     *
     * @param string $link
     * @param bool|integer $post_id
     * @return string
     */
    public function page_link( /** @noinspection PhpUnusedParameterInspection */ $link, $post_id=false )
    {
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
     * Called via WP Filter 'wp_list_pages', if can_edit_pages && is_swifty
     *
     * Filter function to add "spm-hidden" class to hidden menu items in <li> tree.
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
     * Called via WP Filter 'status_header', if can_edit_pages && is_swifty
     *
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
            if ( ! empty( $wp->request ) ) {
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
     * Called via WP Action 'admin_head' if can_edit_pages
     *
     * Output header for admin page
     */
    public function admin_head()
    {
        if ( ! current_user_can( 'edit_pages' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page. #314' ) );
        }

        // hide update notices from this page
        remove_action( 'admin_notices', 'update_nag', 3 );

        $currentScreen = get_current_screen();

        if ( 'pages_page_page-tree' === $currentScreen->base ) {
            /** @noinspection PhpIncludeInspection */
            require $this->plugin_dir . '/view/admin_head.php';

            if( $this->is_swifty ) {
                require $this->plugin_dir . '/view/swifty_admin_head.php';
            }
        }
    }

    /**
     * Called via WP Action 'admin_menu' if can_edit_pages
     *
     * Add submenu to admin left menu
     */
    public function admin_menu()
    {
        add_submenu_page( 'edit.php?post_type=' . $this->_post_type,
                          __( 'Swifty Page Manager', 'swifty-page-manager' ),
                          __( 'Swifty Page Manager', 'swifty-page-manager' ),
                          'edit_pages',
                          'page-tree',
                          array( $this, 'view_page_tree' ) );

        LibSwiftyPlugin::get_instance()->admin_add_swifty_menu( __('Swifty Page Manager', 'swifty-page-manager'), $this->swifty_admin_page, array( &$this, 'admin_spm_menu_page' ), true );
    }

    /**
     * Called via WP do_action if can_edit_pages
     *
     * Show page tree
     */
    public function view_page_tree()
    {
        if ( ! current_user_can( 'edit_pages' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page. #314' ) );
        }

        // renamed from cookie to fix problems with mod_security
        wp_enqueue_script( 'jquery-cookie', $this->plugin_dir_url . $this->_find_minified( '/js/jquery.biscuit.js' ), array( 'jquery' ) );
        wp_enqueue_script( 'jquery-ui-tooltip' );
        wp_enqueue_script( 'jquery-jstree', $this->plugin_dir_url . $this->_find_minified( '/js/jquery.jstree.js' ), false,
                           $this->_plugin_version );
        wp_enqueue_script( 'jquery-alerts', $this->plugin_dir_url . $this->_find_minified( '/js/jquery.alerts.js' ), false,
                           $this->_plugin_version );
        wp_enqueue_script( 'spm',   $this->plugin_dir_url . $this->_find_minified( '/js/swifty-page-manager.js' ), false,
                           $this->_plugin_version );

        wp_enqueue_style( 'jquery-alerts',  $this->plugin_dir_url . '/css/jquery.alerts.css', false,
                          $this->_plugin_version );
        wp_enqueue_style( 'spm-font-awesome', '//netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.css',
                          false, $this->_plugin_version );

        $oLocale = array(
            'status_draft_ucase'      => ucfirst( __( 'draft', 'swifty-page-manager' ) ),
            'status_future_ucase'     => ucfirst( __( 'future', 'swifty-page-manager' ) ),
            'status_password_ucase'   => ucfirst( __( 'protected', 'swifty-page-manager' ) ),
            'status_pending_ucase'    => ucfirst( __( 'pending', 'swifty-page-manager' ) ),
            'status_private_ucase'    => ucfirst( __( 'private', 'swifty-page-manager' ) ),
            'status_trash_ucase'      => ucfirst( __( 'trash', 'swifty-page-manager' ) ),
            'password_protected_page' => __( 'Password protected page', 'swifty-page-manager' ),
            'no_pages_found'          => __( 'No pages found.', 'swifty-page-manager' ),
            'hidden_page'             => __( 'Hidden', 'swifty-page-manager' ),
            'no_sub_page_when_draft'  => __( "Sorry, can't create a sub page to a page with status \"draft\".", 'swifty-page-manager' ),
            'status_published_draft_content_ucase' => ucfirst( __( 'published - draft content', 'swifty-page-manager' ) )
        );

        wp_localize_script( 'spm', 'spm_l10n', $oLocale );
        wp_localize_script( 'spm', 'php_data', array(
            'is_swifty_mode' => LibSwiftyPluginView::is_ss_mode()
        ) );

        /** @noinspection PhpIncludeInspection */
        require( $this->plugin_dir . '/view/page_tree.php' );
    }

    // Our plugin admin menu page
    function admin_spm_menu_page()
    {
        $admin_page_title = __( 'Swifty Page Manager', 'swifty-page-manager' );
        $admin_page = $this->swifty_admin_page;
        $tab_general_title = __( 'General', 'swifty-page-manager' );
        $tab_general_method = array( $this, 'spm_tab_options_content' );

        LibSwiftyPlugin::get_instance()->admin_options_menu_page( $admin_page_title, $admin_page, $tab_general_title, $tab_general_method );
    }

    function spm_tab_options_content()
    {
        echo '<p>' . __( 'There are currently no settings for this plugin.', 'swifty-page-manager' ) . '</p>';
//        settings_fields( 'spm_plugin_options' );
//        do_settings_sections( 'spm_plugin_options_page' );
//        submit_button();
    }

    /**
     * Called via WP Ajax Action 'wp_ajax_spm_get_childs' if can_edit_pages
     *
     * Return JSON with tree children, called from Ajax
     */
    public function ajax_get_childs()
    {
        if ( ! current_user_can( 'edit_pages' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page. #359' ) );
        }

        header( 'Content-type: application/json' );

        $action = $_GET['action'];

        // Check if user is allowed to get the list. For example subscribers should not be allowed to
        // Use same capability that is required to add the menu
        $post_type_object = get_post_type_object( $this->_post_type );

        if ( ! current_user_can( $post_type_object->cap->edit_posts ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page. #371' ) );
        }

        if ( $action ) {   // regular get
            $id = ( isset( $_GET['id'] ) ) ? $_GET['id'] : null;
            $id = (int) str_replace( 'spm-id-', '', $id );

            $jstree_open = array();

            if ( isset( $_COOKIE['jstree_open'] ) ) {
                $jstree_open = $_COOKIE['jstree_open'];  // like this: [jstree_open] => spm-id-1282,spm-id-1284,spm-id-3
                $jstree_open = explode( ',', $jstree_open );

                for ( $i = 0; $i < sizeof( $jstree_open ); $i++ ) {
                    $jstree_open[ $i ] = (int) str_replace( '#spm-id-', '', $jstree_open[ $i ] );
                }
            }

            $this->get_tree();
            $json_data = $this->get_json_data( $this->_by_page_id[ $id ], $jstree_open );
            print json_encode( $json_data );
            exit;
        }

        exit;
    }

    /**
     * Called via WP Ajax Action 'wp_ajax_spm_move_page' if can_edit_pages
     *
     * Ajax function to move a page
     */
    public function ajax_move_page()
    {
        if ( ! current_user_can( 'edit_pages' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page. #465' ) );
        }

        /*
         the node that was moved,
         the reference node in the move,
         the new position relative to the reference node (one of "before", "after" or "inside")
        */
        /** @var wpdb $wpdb - Wordpress Database */
        global $wpdb;

        $node_id     = $_POST['node_id']; // the node that was moved
        $ref_node_id = $_POST['ref_node_id'];
        $type        = $_POST['type'];

        $node_id     = str_replace( 'spm-id-', '', $node_id );
        $ref_node_id = str_replace( 'spm-id-', '', $ref_node_id );

        $_POST['skip_sitepress_actions'] = true; // sitepress.class.php->save_post_actions

        if ( $node_id && $ref_node_id ) {
            $post_node     = get_post( $node_id );
            $post_ref_node = get_post( $ref_node_id );

            // first check that post_node (moved post) is not in trash. we do not move them
            if ( $post_node->post_status === 'trash' ) {
                exit;
            }

            $post_to_save = array(
                'ID'          => $post_node->ID,
                'menu_order'  => $this->_createSpaceForMove( $post_ref_node, $type ),
                'post_parent' => ( 'inside' === $type ) ? $post_ref_node->ID : $post_ref_node->post_parent,
                'post_type'   => $this->_post_type
            );

            $id_saved = wp_update_post( $post_to_save );

            if ( 'inside' === $type && $id_saved ) {
                $show_ref_page_in_menu = get_post_meta( $ref_node_id, 'spm_show_in_menu', true );

                if ( ! empty( $show_ref_page_in_menu ) && $show_ref_page_in_menu !== 'show' ) {
                    update_post_meta( $id_saved, 'spm_show_in_menu', 'hide' );
                }
            }

            echo 'did ' . $type;

            // Store the moved page id in the jstree_select cookie
            setcookie( 'jstree_select', '#spm-id-' . $post_node->ID );

        } else {
            // error
        }

        do_action( 'spm_node_move_finish' );

        exit;
    }

    /**
     * Called via WP Ajax Action 'ajax_save_page' if can_edit_pages
     *
     * Ajax funtion to save a page
     */
    public function ajax_save_page()
    {
        if ( ! current_user_can( 'edit_pages' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page. #588' ) );
        }

        /** @var wpdb $wpdb - Wordpress Database */
        global $wpdb;

        $post_id     = ! empty( $_POST['post_ID'] )    ? intval( $_POST['post_ID'] )  : null;
        $post_title  = ! empty( $_POST['post_title'] ) ? trim( $_POST['post_title'] ) : '';
        $post_name   = ! empty( $_POST['post_name'] )  ? trim( $_POST['post_name'] )  : '';
        $post_status = $_POST['post_status'];

        if ( ! $post_title ) {
            $post_title = __( 'New page', 'swifty-page-manager' );
        }

        $spm_is_custom_url      = ! empty( $_POST['spm_is_custom_url'] ) ? intval( $_POST['spm_is_custom_url'] ) : null;
        $spm_page_title_seo     = ! empty( $_POST['spm_page_title_seo'] ) ? trim( $_POST['spm_page_title_seo'] ) : '';
        $spm_show_in_menu       = ! empty( $_POST['spm_show_in_menu'] ) ? $_POST['spm_show_in_menu'] : null;
        $spm_header_visibility  = ! empty( $_POST['spm_header_visibility'] ) ? $_POST['spm_header_visibility']  : null;
        $spm_sidebar_visibility = ! empty( $_POST['spm_sidebar_visibility'] ) ? $_POST['spm_sidebar_visibility'] : null;

        $post_data = array();

        $post_data['post_title']    = $post_title;
        $post_data['post_status']   = $post_status;
        $post_data['post_type']     = $_POST['post_type'];
        $post_data['page_template'] = $_POST['page_template'];

        if ( isset( $post_id ) && ! empty( $post_id ) ) {  // We're in edit mode
            $post_data['ID'] = $post_id;

            $post_id = wp_update_post( $post_data );

            if ( $post_id ) {
                if ( $this->is_swifty ) {
                    $cur_spm_url = get_post_meta( $post_id, 'spm_url', true );

                    if ( ! empty( $cur_spm_url ) ) {
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

                echo '1';
            } else {
                echo '0';   // fail, tell js
            }
        }
        else {   // We're in create mode
            $parent_id = $_POST['parent_id'];
            $parent_id = intval( str_replace( 'spm-id-', '', $parent_id ) );
            $ref_post  = get_post( $parent_id );
            $add_mode  = $_POST['add_mode'];

            $post_data['post_content'] = '';
            $post_data['menu_order']   = $this->_createSpaceForMove( $ref_post, $add_mode );
            $post_data['post_parent']  = ( 'inside' === $add_mode ) ? $ref_post->ID :  $ref_post->post_parent;

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
                setcookie( 'jstree_select', '#spm-id-' . $post_id );

                echo '1';
            } else {
                echo '0';   // fail, tell js
            }
        }

        exit;
    }

    /**
     * Called via WP Ajax action 'wp_ajax_spm_delete_page' if can_edit_pages
     *
     * Ajax function to delete a page
     */
    public function ajax_delete_page()
    {
        if ( ! current_user_can( 'delete_pages' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page. #708' ) );
        }

        $post_id = intval( $_POST['post_ID'] );

        if ( isset( $post_id ) && ! empty( $post_id ) ) {
            $menu_items = wp_get_associated_nav_menu_items( $post_id, 'post_type', 'page' );

            foreach ( $menu_items as $menu_item_id ) {
                wp_delete_post( $menu_item_id, true );
            }

            $post_data = wp_delete_post( $post_id, false );

            if ( is_object( $post_data ) ) {
                echo '1';
            } else {
                echo '0';   // fail, tell js
            }
        } else {
            echo '0';   // fail, tell js
        }

        exit;
    }

    /**
     * Called via WP Ajax action 'wp_ajax_spm_publish_page' if can_edit_pages
     *
     * Ajax function to publish a page
     */
    public function ajax_publish_page()
    {
        if ( ! current_user_can( 'publish_pages' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page. #747' ) );
        }

        $post_id = intval( $_POST['post_ID'] );

        if ( isset( $post_id ) && ! empty( $post_id ) ) {
            $this->_update_post_status( $post_id, 'publish' );

            echo '1';
        } else {
            echo '0';   // fail, tell js
        }

        exit;
    }

    /**
     * Called via WP Ajax 'wp_ajax_spm_post_settings' if can_edit_pages
     *
     * Ajax function to set the settings of a post
     */
    public function ajax_post_settings()
    {
        if ( ! current_user_can( 'edit_pages' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page. #771' ) );
        }

        header( 'Content-Type: text/javascript' );

        $post_id           = intval( $_REQUEST['post_ID'] );
        $post              = get_post( $post_id );
        $post_meta         = get_post_meta( $post_id );
        $post_status       = ( $post->post_status === 'private' ) ? 'publish' : $post->post_status; // _status
        $spm_show_in_menu  = ( $post->post_status === 'private' ) ? 'hide' : 'show';
        $spm_is_custom_url = 0;

        $defaults = array( 'spm_show_in_menu'       => 'show'
                         , 'spm_page_title_seo'     => $post->post_title
                         , 'spm_header_visibility'  => 'hide'
                         , 'spm_sidebar_visibility' => 'hide'
                         );

        foreach ( $defaults as $key => $val ) {
            if ( ! isset( $post_meta[ $key ] ) ) {
                $post_meta[ $key ] = $val;
            }
        }

        if ( $this->is_swifty ) {
            if ( ! empty( $post_meta['spm_url'][0] ) ) {
                $spm_page_url = $post_meta['spm_url'][0];
                $spm_is_custom_url = 1;
            } else {
                $spm_page_url = wp_make_link_relative( get_page_link( $post_id ) );
            }
        } else {
            $spm_page_url = $post->post_name;
        }

        $spm_page_url = trim( $spm_page_url, '/' );

        if ( $post_meta['spm_show_in_menu'] === 'show' ) {
            // post_status can be private, so then the page must not be visible in the menu.
            $post_meta['spm_show_in_menu'] = $spm_show_in_menu;
        }

        if ( empty( $post_meta['spm_show_in_menu'] )  ) {
            $post_meta['spm_show_in_menu'] = 'show';
        }

        ?>
        var li = jQuery( '#spm-id-<?php echo $post_id; ?>' );

        li.find( 'input[name="post_title"]' ).val( <?php echo json_encode( $post->post_title ); ?> );
        li.find( 'input[name="post_status"]' ).val( [ <?php echo json_encode( $post_status ); ?> ] );
        li.find( 'select[name="page_template"]' ).val( [ <?php echo json_encode( $post->page_template ); ?> ] );
        li.find( 'input[name="post_name"]' ).val( <?php echo json_encode( $spm_page_url ); ?> );
        li.find( 'input[name="spm_is_custom_url"]' ).val( <?php echo json_encode( $spm_is_custom_url ); ?> );
        li.find( 'input[name="spm_show_in_menu"]' ).val( [ <?php echo json_encode( $post_meta['spm_show_in_menu'] ); ?> ] );
        li.find( 'input[name="spm_page_title_seo"]' ).val( <?php echo json_encode( $post_meta['spm_page_title_seo'] ); ?> );
        li.find( 'input[name="spm_header_visibility"]' ).val( [ <?php echo json_encode( $post_meta['spm_header_visibility'] ); ?> ] );
        li.find( 'input[name="spm_sidebar_visibility"]' ).val( [ <?php echo json_encode( $post_meta['spm_sidebar_visibility'] ); ?> ] );

        <?php
        exit;
    }

    /**
     * Called via WP Admin Ajax 'wp_ajax_spm_sanitize_url' if can_edit_pages && is_swifty
     *
     * Ajax function to use Wordpress' sanitize_title_with_dashes function to prepare an URL string
     */
    public function ajax_sanitize_url()
    {
        echo sanitize_title_with_dashes( $_POST['url'] );
        exit;
    }

    /**
     * Get page tree as PHP class.
     *
     * @return StdClass
     */
    public function get_tree()
    {
        if ( is_null( $this->_tree ) ) {
            $this->_tree = new StdClass();
            $this->_tree->menuItem = new StdClass();
            $this->_tree->menuItem->ID = 0; // Fake menu item as root.
            $this->_tree->children = array();
            $this->_by_page_id = array();
            $this->_by_page_id[0] = &$this->_tree;
            $this->_add_all_pages();
        }

        return $this->_tree;
    }

    /**
     * Get the JSON data for a branch of the tree, or the whole tree
     *
     * @param $branch
     * @return array
     */
    public function get_json_data( &$branch )
    {
        $result   = array();
        $children = $branch->children;

        // Sort children by menu_order and post_title
        usort( $children, array( $this, '_sort_children' ) );

        foreach ( $children as $child ) {
            if ( isset( $child->page ) ) {
                $new_branch = $this->_get_page_json_data( $child->page );

                /**
                 * if no children, output no state
                 * if viewing trash, don't get children. we watch them "flat" instead
                 */
                if ( $this->get_post_status() !== 'trash' ) {
                    $new_branch['children'] = $this->get_json_data( $child );

                    if ( count( $new_branch['children'] ) ) {
                        $new_branch['state'] = 'closed';
                    }
                }

                $result[] = $new_branch;
            }
        }

        return $result;
    }

    /**
     * Save an old page URL, we create a redirect for old links from other sites and Google.
     * USAGE:  $this->save_old_url( 469, 'old/url/path' );
     *
     * @param $post_id
     * @param $old_url
     */
    function save_old_url( $post_id, $old_url )
    {
        /** @var wpdb $wpdb - Wordpress Database */
        global $wpdb;

        $old_url = preg_replace( '|^' . preg_quote( get_site_url(), '|' ) . '|', '', $old_url ); // Remove root URL
        $old_url = trim( $old_url, " \t\n\r\0\x0B/" ); // Remove leading and trailing slashes or whitespaces

        $exist_query = $wpdb->prepare(
            "SELECT COUNT(*) FROM $wpdb->postmeta WHERE post_id = %d AND meta_key LIKE 'spm_old_url_%%' AND meta_value='%s'",
                $post_id,
                $old_url );

        $exists = intval( $wpdb->get_var( $exist_query ) );

        if ( ! $exists ) {
            $last_key_query = $wpdb->prepare(
                "SELECT REPLACE( meta_key, 'spm_old_url_', '' ) FROM $wpdb->postmeta WHERE post_id = %d AND meta_key LIKE 'spm_old_url_%%' ORDER BY meta_key DESC",
                    $post_id );

            $last_key = $wpdb->get_var( $last_key_query );
            $number   = intval( $last_key ) + 1;

            add_post_meta( $post_id, 'spm_old_url_' . $number, $old_url );
        }
    }

    /**
     * Get current post status filter. The user can choose this.
     * - any
     * - publish
     * - trash
     *
     * @return string
     */
    public function get_post_status()
    {
        return $this->_post_status;
    }

    /**
     * Get the URL of this plugin, for example:
     * http://domain.com/wp-admin/edit.php?post_type=page&page=page-tree
     *
     * @return string
     */
    public function get_plugin_url()
    {
        return $this->plugin_url;
    }

    /**
     * Adds the plugin css to the head tag.
     */
    public function add_plugin_css()
    {
        wp_enqueue_style( 'spm', $this->plugin_dir_url . '/css/styles.css', false, $this->_plugin_version );
    }

    ////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sort children by menu_order and post_title
     */
    protected function _sort_children( $a, $b )
    {
        $result = 0;

        if ( isset( $a->page ) && isset( $b->page ) ) {
            $result = $a->page->menu_order - $b->page->menu_order;

            if ( 0 == $result ) {
                $result = strcmp( $a->page->post_title, $b->page->post_title );
            }
        }

        return $result;
    }

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
     *
     */
    protected function _add_all_pages()
    {
        $args = array();
        $args['post_type'] = 'page';
        $args['post_status'] = $this->get_post_status();
        $args['numberposts'] = -1;
        $args['orderby'] = 'menu_order title';
        $args['order'] = 'ASC';
        $pages = get_posts( $args );
        $added = true;

        while ( ! empty( $pages ) && $added ) {
            $added = false;
            $keys  = array_keys( $pages );

            foreach ( $keys as $key ) {
                $page = $pages[ $key ];

                if ( isset( $this->_by_page_id[ $page->ID ] ) ) {
                    $branch = &$this->_by_page_id[ $page->ID ];
                    $branch->page = $page;

                    unset( $branch );
                    unset( $pages[ $key ] );

                    $added = true;
                } else if ( isset( $this->_by_page_id[ $page->post_parent ] ) ) {
                    $parent_branch = &$this->_by_page_id[ $page->post_parent ];
                    $new_branch = new stdClass();
                    $new_branch->page = $page;
                    $new_branch->children = array();
                    $this->_by_page_id[ $new_branch->page->ID ] = &$new_branch;
                    $parent_branch->children[] = &$new_branch; // Warning, does not sort children correctly

                    unset( $new_branch );
                    unset( $pages[ $key ] );

                    $added = true;
                }
            }
        }

        // Add rest to root
        $parent_branch = &$this->_tree;

        foreach ( $pages as $page ) {
            $new_branch = new stdClass();
            $new_branch->page = $page;
            $new_branch->children = array();
            $this->_by_page_id[ $new_branch->page->ID ] = &$new_branch;
            $parent_branch->children[] = &$new_branch;
            unset( $new_branch );
        }
    }

    /**
     * @param WP_Post $one_page
     * @return array
     */
    protected function _get_page_json_data( $one_page )
    {
        $page_json_data = array();

        $page_id = $one_page->ID;

        $post_statuses    = get_post_statuses();
        $post_type_object = get_post_type_object( $this->_post_type );
        $editLink         = get_edit_post_link( $one_page->ID, 'notDisplay' );

        // type of node
        $rel = $one_page->post_status;

        if ( $one_page->post_password ) {
            $rel = 'password';
        }

        // modified time
        $post_modified_time = strtotime( $one_page->post_modified );
        $post_modified_time = date_i18n( get_option( 'date_format' ), $post_modified_time, false );

        // last edited by
        setup_postdata( $one_page );

        if ( $last_id = get_post_meta( $one_page->ID, '_edit_last', true ) ) {
            $last_user = get_userdata( $last_id );

            if ( $last_user !== false ) {
                $post_author = apply_filters( 'the_modified_author', $last_user->display_name );
            }
        }

        if ( empty( $post_author ) ) {
            $post_author = __( 'Unknown user', 'swifty-page-manager' );
        }

        $title = get_the_title( $one_page->ID ); // so hooks and stuff will do their work

        if ( empty( $title ) ) {
            $title = __( '[untitled page]', 'swifty-page-manager' );
        }

        $arr_page_css_styles = array();

        if ( current_user_can( $post_type_object->cap->edit_post, $page_id ) ) {
            $arr_page_css_styles[] = 'spm-can-edit';
        }

        if ( current_user_can( $post_type_object->cap->create_posts, $page_id ) && 'draft' !== $one_page->post_status ) {
            $arr_page_css_styles[] = 'spm-can-add-inside';
        }

        if ( current_user_can( $post_type_object->cap->create_posts, $one_page->post_parent ) ) {
            $arr_page_css_styles[] = 'spm-can-add-after';
        }

        if ( current_user_can( $post_type_object->cap->publish_posts, $page_id ) ) {
            $arr_page_css_styles[] = 'spm-can-publish';
        }

        if ( current_user_can( $post_type_object->cap->delete_post, $page_id ) ) {
            $arr_page_css_styles[] = 'spm-can-delete';
        }

        if ( $this->is_swifty ) {
            $show_page_in_menu = get_post_meta( $page_id, 'spm_show_in_menu', true );

            if ( empty( $show_page_in_menu ) ) {
                $show_page_in_menu = 'show';
            }

            $arr_page_css_styles[] = 'spm-show-page-in-menu-' . ( $show_page_in_menu === 'show' ? 'yes' : 'no' );
        }

        $page_json_data['data']          = array();
        $page_json_data['data']['title'] = $title;

        $page_json_data['attr']          = array();
        $page_json_data['attr']['id']    = 'spm-id-' . $one_page->ID;
        $page_json_data['attr']['class'] = join( ' ', $arr_page_css_styles );

        $page_json_data['metadata']                = array();
        $page_json_data['metadata']['id']          = 'spm-id-' . $one_page->ID;
        $page_json_data['metadata']['post_id']     = $one_page->ID;
        $page_json_data['metadata']['post_type']   = $one_page->post_type;
        $page_json_data['metadata']['post_status'] = $one_page->post_status;

        if ( isset( $post_statuses[ $one_page->post_status ] )  ) {
            $page_json_data['metadata']['post_status_translated'] = $post_statuses[ $one_page->post_status ];
        } else {
            $page_json_data['metadata']['post_status_translated'] = $one_page->post_status;
        }

        $page_json_data['metadata']['rel']             = $rel;
        $page_json_data['metadata']['permalink']       = htmlspecialchars_decode( get_permalink( $one_page->ID ) );
        $page_json_data['metadata']['swifty_edit_url'] = add_query_arg( 'swcreator_edit', 'true', htmlspecialchars_decode( get_permalink( $one_page->ID ) ) );
        $page_json_data['metadata']['editlink']        = htmlspecialchars_decode( $editLink );
        $page_json_data['metadata']['modified_time']   = $post_modified_time;
        $page_json_data['metadata']['modified_author'] = $post_author;
        $page_json_data['metadata']['post_title']      = $title;
        $page_json_data['metadata']['delete_nonce']    = wp_create_nonce( 'delete-page_' . $one_page->ID, '_trash' );
        $page_json_data['metadata']['published_draft_content'] = LibSwiftyPlugin::get_instance()->get_autosave_version_if_newer( $page_id );

        return $page_json_data;
    }

    /**
     * Usage: $seo_version = $this->get_plugin_version('wordpress-seo/*');
     *
     * @param  string $plugin_match - For example "wordpress-seo/*"
     * @return bool|string         - false if plugin not installed or not active
     */
    protected function get_plugin_version( $plugin_match )
    {
        $result = false;
        $regexp = preg_quote( $plugin_match, '#' );
        $regexp = str_replace( array( '\*', '\?' ), array( '.*', '.' ), $regexp );
        $regexp = '#^' . $regexp . '$#i';

        if ( ! function_exists( 'get_plugin_data' ) ) {
            require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
        }

        $plugins = get_option( 'active_plugins', array() ); // returns only active plugins

        foreach ( $plugins as $plugin ) {
            if ( preg_match( $regexp, $plugin ) ) {
                $data   = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
                $result = ( ! empty( $data['Version'] ) ) ? $data['Version'] : '0.0.1';

                break;
            }
        }

        return $result;
    }

    /**
     * Usage: $have_seo = $this->_is_plugin_minimal( 'wordpress-seo/*', '1.0.0' );
     *
     * @param $plugin_match - For example "wordpress-seo/*"
     * @param $require_version
     * @return bool|mixed
     */
    protected function _is_plugin_minimal( $plugin_match, $require_version )
    {
        $result = false;
        $plugin_version = $this->get_plugin_version( $plugin_match );

        if ( $plugin_version ) {
            $result = version_compare( $plugin_version, $require_version, '>=' );
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

        if ( ! empty( $show ) && 'hide' === $show ) {
            $result .= ' spm-hidden';
        };

        return $result;
    }

    /**
     * Return minified filename, if exists; otherwise original filename
     */
    protected function _find_minified( $file_name )
    {
        $file_name_min = preg_replace( '|\.js$|', '.min.js', $file_name );

        if ( file_exists( $this->plugin_dir . $file_name_min ) ) {
            $file_name = $file_name_min;
        }

        return $file_name;
    }

    /**
     * Get a menu_order space to move/insert a page
     *
     * @param WP_Post $ref_post
     * @param string $direction, can be 'before','after' or 'inside'
     * @return int
     */
    protected function _createSpaceForMove( $ref_post, $direction = 'after' ) {
        /** @var wpdb $wpdb */
        global $wpdb;

        if ( 'inside' === $direction ) {
            $query  = $wpdb->prepare( "SELECT MAX(menu_order) FROM $wpdb->posts WHERE post_parent = %d", $ref_post->ID );
            $result = $wpdb->get_var( $query ) + 1;
        } else { // after or before
            $result = $ref_post->menu_order;

            if ( 'after' === $direction ) {
                $result++;
            }

            $posts = get_posts( array(
                'post_status'      => 'any',
                'post_type'        => $this->_post_type,
                'numberposts'      => -1,
                'offset'           => 0,
                'orderby'          => 'menu_order title',
                'order'            => 'asc',
                'post_parent'      => $ref_post->post_parent,
                'suppress_filters' => false
            ) );
            $has_passed_ref_post = false;

            foreach ( $posts as $one_post ) {
                if ( $has_passed_ref_post or ( 'before' === $direction && $ref_post->ID === $one_post->ID ) ) {
                    $post_update = array(
                        'ID'         => $one_post->ID,
                        'menu_order' => $one_post->menu_order + 2
                    );
                    $return_id = wp_update_post( $post_update );

                    if ( 0 === $return_id ) {
                        die( 'Error: could not update post with id ' . $post_update->ID . '<br>Technical details: ' . print_r( $post_update ) );
                    }
                }

                if ( ! $has_passed_ref_post && $ref_post->ID === $one_post->ID ) {
                    $has_passed_ref_post = true;
                }
            }
        }

        return $result;
    }

    // @if PROBE='include'
    /**
     * Swifty Probe Module include (used for testing and gamification)
     */
    public function add_module_swifty_probe()
    {
        wp_enqueue_script(
            'swifty-probe',
            $this->plugin_dir_url . $this->_find_minified( '/lib/swifty_plugin/js/probe/__probe.js' ),
            false
        );

        wp_enqueue_script(
            'swifty-probe-wp',
            $this->plugin_dir_url . $this->_find_minified( '/lib/swifty_plugin/js/probe/_probe.wp.js' ),
            array( 'swifty-probe' )
        );

        wp_enqueue_script(
            'swifty-probe-utils',
            $this->plugin_dir_url . $this->_find_minified( '/lib/swifty_plugin/js/probe/_probe.utils.js' ),
            array( 'swifty-probe' )
        );

        wp_enqueue_script(
            'swifty-probe-spm',
            $this->plugin_dir_url . $this->_find_minified( '/js/probe/probe.spm.js' ),
            array( 'swifty-probe-wp' )
        );

        wp_enqueue_script(
            'bililite-range',
            $this->plugin_dir_url . $this->_find_minified( '/lib/swifty_plugin/js/lib/probe/bililiteRange.js' ),
            false
        );

        wp_enqueue_script(
            'jquery-simulate',
            $this->plugin_dir_url . $this->_find_minified( '/lib/swifty_plugin/js/lib/probe/jquery.simulate.js' ),
            false
        );

        wp_enqueue_script(
            'jquery-simulate-ext',
            $this->plugin_dir_url . $this->_find_minified( '/lib/swifty_plugin/js/lib/probe/jquery.simulate.ext.js' ),
            array( 'jquery-simulate' )
        );

        wp_enqueue_script(
            'jquery-drag-n-drop',
            $this->plugin_dir_url . $this->_find_minified( '/lib/swifty_plugin/js/lib/probe/jquery.simulate.drag-n-drop.js' ),
            array( 'jquery-simulate-ext' )
        );

        wp_enqueue_script(
            'jquery-key-sequence',
            $this->plugin_dir_url . $this->_find_minified( '/lib/swifty_plugin/js/lib/probe/jquery.simulate.key-sequence.js' ),
            array( 'jquery-simulate-ext' )
        );
    }
    // @endif

} // End of class SwiftyPageManager

// Start the plugin
$SwiftyPageManager = new SwiftyPageManager();
