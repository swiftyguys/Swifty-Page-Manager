<?php
/*
Plugin Name: Swifty Page Manager
Description: Easily create, move and delete pages. Manage page settings.
Author: SwiftyLife
Version: // @echo RELEASE_TAG
Author URI: http://swiftylife.com/plugins/
Plugin URI: http://swiftylife.com/plugins/swifty-page-manager/
*/
if ( ! defined( 'ABSPATH' ) ) exit;

global $swifty_build_use;
$swifty_build_use = '/*@echo BUILDUSE*/';

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
    protected $is_ssm_active = false;
    protected $is_ssd_active = false;
    protected $is_ss_advanced = false;
    protected $front_page_id = 0;
    protected $swifty_admin_page = 'swifty_page_manager_admin';
    protected $areas = array( 'topbar', 'header', 'navbar', 'sidebar', 'extrasidebar', 'footer', 'bottombar' );

    private $script_refresh_tree = '$SPMTree.jstree( \'refresh\' );';

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->plugin_file = __FILE__;
        $this->plugin_dir = dirname( $this->plugin_file );
        $this->plugin_basename = basename( $this->plugin_dir );
        $this->plugin_dir_url = plugins_url( rawurlencode( basename( $this->plugin_dir ) ) );
        $this->plugin_url = $_SERVER[ 'REQUEST_URI' ];

        require_once plugin_dir_path( __FILE__ ) . 'lib/swifty_plugin/php/autoload.php';
        if( is_null( LibSwiftyPlugin::get_instance() ) ) {
            new LibSwiftyPlugin();
        }

        add_filter( 'swifty_active_plugins', array( $this, 'hook_swifty_active_plugins' ) );
        add_filter( 'swifty_active_plugin_versions', array( $this, 'hook_swifty_active_plugin_versions' ) );

        // postpone further initialization to allow loading other plugins
        add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );

        // remove ninja-forms admin notices
        add_filter( 'nf_admin_notices', array( $this, 'hook_nf_admin_notices' ), 11 );
    }

    /**
     * Called via Swifty filter 'swifty_active_plugins'
     *
     * Add the plugin name to the array
     */
    public function hook_swifty_active_plugins( $plugins )
    {
        $plugins[] = 'swifty-page-manager';
        return $plugins;
    }

    /**
     * Called via Swifty filter 'swifty_active_plugin_versions'
     *
     * Add the plugin name as key to the array with plugin version
     */
    public function hook_swifty_active_plugin_versions( $plugins )
    {
        $plugins['swifty-page-manager'] = array( 'version' => $this->_plugin_version );
        return $plugins;
    }

    /**
     * Called via WP Action 'plugins_loaded'
     *
     * Initialize actions and filter
     */
    function plugins_loaded()
    {
        $this->is_ssm_active = LibSwiftyPluginView::is_required_plugin_active( 'swifty-site' );
        $this->is_ss_advanced = get_user_option( 'swifty_gui_mode' ) === 'advanced';

        // Priority high, so $required_theme_active_swifty_site_designer is set.
        add_action( 'after_setup_theme', array( $this, 'action_after_setup_theme' ), 9999 );

        // Actions for visitors viewing the site
        add_action( 'parse_request',     array( $this, 'parse_request' ) );
        add_filter( 'page_link',         array( $this, 'page_link' ), 10, 2 );
        if ( $this->is_ssm_active ) {
            add_filter( 'wp_title',             array( $this, 'seo_wp_title' ), 10, 2 );
            add_filter( 'document_title_parts', array( $this, 'seo_document_title_parts' ) );
            add_filter( 'admin_footer_text',    array( $this, 'empty_footer_text' ) );
            add_filter( 'update_footer',        array( $this, 'empty_footer_text' ), 999 );
        }

        // Actions for admins, warning: is_admin is not a security check
        if ( is_admin() ) {
            add_action( 'init',       array( $this, 'admin_init' ) );
            add_action( 'admin_init', array( $this, 'hook_admin_init' ) );
        }

        // @if PROBE='include'
        // Swifty Probe Module include (used for testing and gamification)
        add_action( 'admin_enqueue_scripts', array( $this, 'add_module_swifty_probe' ) );
        // @endif
    }

    public function action_after_setup_theme() {
        $this->is_ssd_active = LibSwiftyPluginView::$required_theme_active_swifty_site_designer;
    }

    /**
     * return true when the page manager is showed or pagelist with trashed pages
     *
     * @return bool
     */
    public function is_pagemanager_page() {
        $currentScreen = get_current_screen();
        return ($currentScreen && ( 'pages_page_page-tree' === $currentScreen->base ||
                    ( 'edit' === $currentScreen->base && 'page' === $currentScreen->post_type && 'trash' === get_query_var( 'post_status' ) ) ) );
    }

    /** hide ninja forms notices when showing pagemanager or pagelist with trashed pages
     *  do this by removing all ninja notices from array
     */
    public function hook_nf_admin_notices( $notices ) {

        if( $this->is_pagemanager_page() ) {
            $notices = array();
        }
        return $notices;
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
            add_filter( 'admin_add_swifty_menu', array( &$this, 'hook_admin_add_swifty_menu' ), 1, 4 );
            add_filter( 'admin_add_swifty_admin', array( &$this, 'hook_admin_add_swifty_admin' ), 1, 8 );
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

            if ( $this->is_ssm_active ) {
                add_action( 'wp_ajax_spm_sanitize_url_and_check', array( $this, 'ajax_sanitize_url_and_check' ) );
                add_action( 'save_post',           array( $this, 'restore_page_status' ), 10, 2 );
                add_filter( 'wp_insert_post_data', array( $this, 'set_tmp_page_status' ), 10, 2 );
                add_filter( 'wp_list_pages',       array( $this, 'wp_list_pages' ) );
                add_filter( 'status_header',       array( $this, 'status_header' ) );
            }

            require_once plugin_dir_path( __FILE__ ) . 'lib/swifty_plugin/php/autoload.php';
            if ( ! class_exists( 'LibSwiftyPlugin' ) ) {
                new LibSwiftyPlugin();
            }

            $this->front_page_id = 'page' == get_option('show_on_front') ? (int) get_option( 'page_on_front' ) : 0;
        }
    }

    /**
     * add the spm options and settings and bind them to the correct setting section
     */
    function hook_admin_init()
    {
        // setting group name, name of option
        register_setting( 'spm_plugin_options', 'spm_plugin_options' );

        add_settings_section(
            'spm_plugin_options_main_id',
            '',
            array( $this, 'spm_plugin_options_main_text_callback' ),
            'spm_plugin_options_page'
        );

        add_settings_field(
            'spm_plugin_options_page_tree_max_width',
            __( 'Page tree max. width', 'swifty-page-manager' ),
            array( $this, 'plugin_setting_page_tree_max_width' ),
            'spm_plugin_options_page',
            'spm_plugin_options_main_id'
        );
    }

    function spm_plugin_options_main_text_callback()
    {
    }

    function plugin_setting_page_tree_max_width()
    {
        echo '<input'
            . ' type="text"'
            . ' id="spm_plugin_options_page_tree_max_width"'
            . ' name="spm_plugin_options[page_tree_max_width]"'
            . ' value="' . $this->get_page_tree_max_width() . '"'
            . ' />';
    }

    function get_page_tree_max_width()
    {
        $options = get_option( 'spm_plugin_options' );

        if(    ! $options
            || ! isset( $options[ 'page_tree_max_width' ] )
            || ! $options[ 'page_tree_max_width' ]
        ) {
            $options[ 'page_tree_max_width' ] = 900;

            update_option( 'spm_plugin_options', $options );
        }

        return $options[ 'page_tree_max_width' ];
    }

    public function get_admin_page_title()
    {
        $swifty_SS2_hosting_name = apply_filters( 'swifty_SS2_hosting_name', false );
        if( $swifty_SS2_hosting_name ) {
            $admin_page_title = __( 'SwiftySite Pages', 'swifty-page-manager' );
        } else {
            $admin_page_title = 'Swifty Page Manager';
        }
        return $admin_page_title;
    }

    /**
    /**
     * @param string $title
     * @param string $sep
     * @return string
     */
    public function seo_wp_title( $title, $sep )
    {
        if( is_feed() ) {
            return $title;
        }

        $seoTitle = get_post_meta( get_the_ID(), 'spm_page_title_seo', true );

        if( ! empty( $seoTitle ) ) {
            return "$seoTitle";
        }

        return $title;
    }

    /**
     * Change title for themes supporting the 'title-tag'.
     * Return only seo title when set in SPM.
     *
     * @param $title_parts
     * @return array
     */
    public function seo_document_title_parts( $title_parts ) {
        if( is_feed() ) {
            return $title_parts;
        }

        $seoTitle = get_post_meta( get_the_ID(), 'spm_page_title_seo', true );

        if( ! empty( $seoTitle ) ) {
            return array( 'title' => $seoTitle );
        }

        return $title_parts;
    }

    /**
     * Called via WP Filter 'wp_insert_post_data', if can_edit_pages && is_ssm_active
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
     * Called via WP Action 'save_post', if can_edit_pages && is_ssm_active
     *
     * @param integer $post_id
     * @param WP_Post $post
     */
    public function restore_page_status( $post_id, $post )
    {
        if ( ! current_user_can( 'edit_pages' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page. #314' ) );
        }

        // Check it's not an auto save routine
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! wp_is_post_revision( $post_id ) &&
              $post->post_type   === 'page'   &&
              $post->post_status === '__TMP__'
        ) {
            $post_data = array(
                'ID'           => $post_id,
                'post_status'   => $_POST['post_status'],
            );
            // no need to restore autosave, because this is only used for new pages which
            // do not yet have an autosave
            wp_update_post( $post_data );
        }
    }

    /*
     * Search in DB for spm_url and return post id when found
     *
     */
    protected function get_post_id_from_spm_url( $url )
    {
        /** @var wpdb $wpdb - Wordpress Database */
        global $wpdb;

        $query = $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key='spm_url' AND meta_value='%s'",
            $url );
        return $wpdb->get_var( $query );
    }

    /**
     * Called via WP Action 'parse_request'
     *
     * Action function to make our overridden URLs work by changing the query params.
     * the meta data "spm_url" contains the wanted url (without domain)
     *
     * @param wp $wp - WordPress object
     */
    public function parse_request( &$wp )
    {
        if( ! empty( $wp->request ) ) {
            $post_id = $this->get_post_id_from_spm_url( $wp->request );

            if( $post_id ) {
                $wp->query_vars = array( 'pagename' => get_page_uri( $post_id ) );
                // disable seo-redirect plugin for this url (other solution would be to set $_SERVER["REQUEST_URI"] to the uri)
                remove_action( 'wp', 'WPSR_redirect', 1 );
            }
        }
    }

    /**
     * Called via WP Filter 'page_link', if is_ssm_active
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
        if( $post_id ) {
            $spm_url = get_post_meta( $post_id, 'spm_url', true );

            if( $spm_url ) {
                $link = get_site_url( null, $spm_url );
            } else {
                if( $this->is_ssm_active ) {
                    $post = get_post( $post_id );

                    // Hack: get_page_link() would return ugly permalink for drafts, so we will fake that our post is published.
                    if( in_array( $post->post_status, array( 'draft', 'pending' ) ) ) {
                        $post->post_status = 'publish';
                        $post->post_name = sanitize_title( $post->post_name ? $post->post_name : $post->post_title, $post->ID );
                    }

                    // If calling get_page_link inside page_link action, unhook this function so it doesn't loop infinitely
                    remove_filter( 'page_link', array( $this, 'page_link' ) );

                    $link = get_page_link( $post );

                    // Re-hook this function
                    add_filter( 'page_link', array( $this, 'page_link' ), 10, 2 );
                }
            }
        }
        return $link;
    }

    /**
     * Called via WP Filter 'wp_list_pages', if can_edit_pages && is_ssm_active
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
     * Called via WP Filter 'status_header', if can_edit_pages && is_ssm_active
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

        if ( $this->is_pagemanager_page() ) {
            $currentScreen = get_current_screen();
            if ( 'pages_page_page-tree' === $currentScreen->base ) {
                /** @noinspection PhpIncludeInspection */
                require $this->plugin_dir . '/view/admin_head.php';
            }

            if( $this->is_ssm_active ) {
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
                          $this->get_admin_page_title(),
                          $this->get_admin_page_title(),
                          'edit_pages',
                          'page-tree',
                          array( $this, 'view_page_tree' ) );

        add_filter( 'swifty_admin_page_links_' . $this->swifty_admin_page, array( $this, 'hook_swifty_admin_page_links' ) );

        LibSwiftyPlugin::get_instance()->admin_add_swifty_menu( $this->get_admin_page_title(), __('Pages', 'swifty-page-manager'), $this->swifty_admin_page, array( &$this, 'admin_spm_menu_page' ), true );
    }

    function hook_admin_add_swifty_menu( $page, $name, $key, $func )
    {
        if( ! $page ) {
            $page = add_submenu_page( 'swifty_admin', $name, $name, 'manage_options', $key, $func );
        }
        return $page;
    }

    function hook_admin_add_swifty_admin( $done, $v1, $v2, $v3, $v4, $v5, $v6, $v7 )
    {
        if( ! $done ) {
            add_menu_page( $v1, $v2, $v3, $v4, $v5, $v6, $v7 );
        }
        return true;
    }

    /**
     * Called via admin_menu hook
     *
     * Add links to admin menu
     */
    public function hook_swifty_admin_page_links( $settings_links )
    {
        $settings_links['general'] = array( 'title' => __( 'General', 'swifty-page-manager' ), 'method' => array( &$this, 'spm_tab_options_content' ) );

        return $settings_links;
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
        wp_enqueue_style( 'spm-font-awesome', '//netdna.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css',
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
            'checking_url'            => __( 'Checking url', 'swifty-page-manager' ),
            'no_sub_page_when_draft'  => __( "Unfortunately you can not create a sub page under a page with status 'Draft' because the draft page has not yet been published and thus technically does not exist yet. For now, just create it as a regular page and later you can drag and drop it to become a sub page.", 'swifty-page-manager' ),
            'status_published_draft_content_ucase' => ucfirst( __( 'published - draft content', 'swifty-page-manager' ) )
        );

        wp_localize_script( 'spm', 'spm_l10n', $oLocale );
        wp_localize_script( 'spm', 'spm_data', array(
            'is_ssm_active'  => $this->is_ssm_active,
            'is_ssd_active'  => $this->is_ssd_active,
            'is_ss_advanced' => $this->is_ss_advanced
        ) );

        /** @noinspection PhpIncludeInspection */
        require( $this->plugin_dir . '/view/page_tree.php' );

        do_action( 'swifty_page_manager_view_page_tree' );
    }

    // Our plugin admin menu page
    function admin_spm_menu_page()
    {
        LibSwiftyPlugin::get_instance()->admin_options_menu_page( $this->swifty_admin_page );
    }

    function spm_tab_options_content()
    {
        settings_fields( 'spm_plugin_options' );
        do_settings_sections( 'spm_plugin_options_page' );
        submit_button();

        echo '<p>' . 'Swifty Page Manager ' . $this->_plugin_version . '</p>';
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

            // $id_saved = wp_update_post( $post_to_save ); < now keeping autosave
            $id_saved = LibSwiftyPlugin::get_instance()->wp_update_post_keep_autosave( $post_node->ID, $post_to_save );

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
     * return true when given post_name is unique as slug and spm_url
     */
    public function spm_is_unique_spm_url( $post_id, $post_parent_id, $post_name )
    {
        // look for other pages using this post_name in the spm_url (is it not used for other pages?)
        $spm_url_post_id = $this->get_post_id_from_spm_url( $post_name );
        if( $spm_url_post_id && ( $spm_url_post_id != $post_id ) ) {
            return false;
        }
        // look for other pages using this post_name as slug (is it unique in siblings?)
        global $wpdb;
        $check_sql = "SELECT post_name FROM $wpdb->posts WHERE post_name = %s AND post_type IN ( 'page', 'attachment' ) AND ID != %d AND post_parent = %d LIMIT 1";
        $post_name_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $post_name, $post_id, $post_parent_id ) );
        if( $post_name_check ) {
            return false;
        }
        return true;
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

        // post_name is de menu slug
        $post_id     = ! empty( $_POST['post_ID'] )    ? intval( $_POST['post_ID'] )  : null;
        $post_title  = ! empty( $_POST['post_title'] ) ? trim( $_POST['post_title'] ) : '';
        $post_name   = ! empty( $_POST['post_name'] )  ? trim( $_POST['post_name'] )  : '';
        $post_status = $_POST['post_status'];

        // better be safe with our menu slugs
        $post_name = preg_replace("~[ ]~", "-", $post_name);
        $post_name = preg_replace("~[^a-z0-9//_-]~i", "", $post_name);
        $post_name = rtrim( $post_name, '/' );

        if ( ! $post_title ) {
            $post_title = __( 'New page', 'swifty-page-manager' );
        }

        $spm_is_custom_url  = ! empty( $_POST['spm_is_custom_url'] ) ? intval( $_POST['spm_is_custom_url'] ) : null;
        $spm_page_title_seo = ! empty( $_POST['spm_page_title_seo'] ) ? trim( $_POST['spm_page_title_seo'] ) : '';
        $spm_show_in_menu   = ! empty( $_POST['spm_show_in_menu'] ) ? $_POST['spm_show_in_menu'] : null;

        $post_data = array();

        $post_data['post_title']    = $post_title;
        $post_data['post_status']   = $post_status;
        $post_data['post_type']     = $_POST['post_type'];
//        $post_data['page_template'] = $_POST['page_template'];

        if ( isset( $post_id ) && ! empty( $post_id ) ) {  // We're in edit mode
            $post_data['ID'] = $post_id;

            // $post_id = wp_update_post( $post_data ); < now keeping autosave
            $post_id = LibSwiftyPlugin::get_instance()->wp_update_post_keep_autosave( $post_id, $post_data );

            if( $post_id ) {
                if( $this->is_ssm_active ) {
                    $post = get_post( $post_id );
                    $spm_show_as_first = isset( $_POST['spm_show_as_first'] ) ? $_POST['spm_show_as_first'] : null;
                    $spm_alt_menu_text = isset( $_POST['spm_alt_menu_text'] ) ? trim( $_POST['spm_alt_menu_text'] ) : null;

                    if( $this->spm_is_unique_spm_url( $post_id, $post->post_parent, $post_name ) ) {
                        $cur_spm_url = get_post_meta( $post_id, 'spm_url', true );

                        if( ! empty( $cur_spm_url ) ) {
                            if( $cur_spm_url !== $post_name ) {
                                $this->save_old_url( $post_id, $cur_spm_url );
                            }
                        } else {
                            if( $spm_is_custom_url ) {
                                $this->save_old_url( $post_id, wp_make_link_relative( get_page_link( $post_id ) ) );
                            }
                        }

                        update_post_meta( $post_id, 'spm_url', $spm_is_custom_url ? $post_name : '' );
                    }

                    update_post_meta( $post_id, 'spm_show_in_menu', $spm_show_in_menu );
                    update_post_meta( $post_id, 'spm_page_title_seo', $spm_page_title_seo );

                    foreach( $this->areas as $area ) {
                        $area_visibility = 'spm_' . $area . '_visibility';

                        update_post_meta(
                            $post_id,
                            $area_visibility,
                            ! empty( $_POST[ $area_visibility ] ) ? $_POST[ $area_visibility ] : null
                        );
                    }

                    if ( ! is_null( $spm_show_as_first ) ) {
                        update_post_meta( $post_id, 'spm_show_as_first', $spm_show_as_first );
                    }

                    if ( ! is_null( $spm_alt_menu_text ) ) {
                        if ( $spm_show_as_first === 'show' ) {
                            update_post_meta( $post_id, 'spm_alt_menu_text', $spm_alt_menu_text );
                        }
                    }
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
            $parent_post_meta = get_post_meta( $parent_id );
            $page_type = $_POST['page_type'];

            $post_data['post_content'] = '';
            $post_data['menu_order']   = $this->_createSpaceForMove( $ref_post, $add_mode );
            $post_data['post_parent']  = ( 'inside' === $add_mode ) ? $ref_post->ID :  $ref_post->post_parent;

            $post_id = wp_insert_post( $post_data );
            $post_id = intval( $post_id );

            if ( $post_id ) {

                $post = get_post( $post_id );
                if ( $this->is_ssm_active ) {
                    // make sure the menu url is unique
                    if( $post_name === '' ) {
                        $post_name = $post->post_name;
                    }

                    $nr = 2;
                    $original_post_name = $post_name;
                    while ( ! $this->spm_is_unique_spm_url( $post_id, $post->post_parent, $post_name ) ) {
                        $post_name = $original_post_name . '-' . $nr++;
                        $spm_is_custom_url = true;
                    }

                    add_post_meta( $post_id, 'spm_url', $spm_is_custom_url ? $post_name : '', 1 );
                    add_post_meta( $post_id, 'spm_show_in_menu', $spm_show_in_menu, 1 );
                    add_post_meta( $post_id, 'spm_page_title_seo', $spm_page_title_seo, 1 );

                    foreach( $this->areas as $area ) {
                        $v_key = 'spm_' . $area . '_visibility';

                        // Page will be a copy of the parent, with or without the parent's content.
                        if( $page_type && $page_type !== 'default' ) {
                            $t_key = 'spm_' . $area . '_template';

                            foreach ( array( $v_key, $t_key ) as $key ) {
                                if( isset( $parent_post_meta[ $key ] ) ) {
                                    $val = $parent_post_meta[ $key ];
                                    $val = ( is_array( $val ) && count( $val ) === 1 ) ? $val[ 0 ] : $val;

                                    add_post_meta( $post_id, $key, $val, 1 );
                                }
                            }
                        } else {
                            add_post_meta(
                                $post_id,
                                $v_key,
                                ! empty( $_POST[ $v_key ] ) ? $_POST[ $v_key ] : null,
                                1
                            );
                        }
                    }

                    if( $page_type && $page_type === 'copy' ) {
                        $parent_content = $ref_post->post_content;
                        $autosave_content = LibSwiftyPluginView::get_instance()->get_autosave_version_if_newer( $parent_id );

                        if( $autosave_content ) {
                            $parent_content = $autosave_content;
                        }

                        wp_update_post( array(
                            'ID'           => $post_id,
                            'post_content' => $parent_content,
                        ) );
                    }
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

        header( 'Content-Type: text/javascript' );

        $post_id = intval( $_POST['post_ID'] );

        if ( isset( $post_id ) && ! empty( $post_id ) ) {
            $menu_items = wp_get_associated_nav_menu_items( $post_id, 'post_type', 'page' );

            foreach ( $menu_items as $menu_item_id ) {
                wp_delete_post( $menu_item_id, true );
            }

            wp_delete_post( $post_id, false );
        }
        // always refresh the tree
        echo $this->script_refresh_tree;

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

        header( 'Content-Type: text/javascript' );

        $post_id = intval( $_POST['post_ID'] );

        if ( isset( $post_id ) && ! empty( $post_id ) ) {
            $this->_update_post_status( $post_id, 'publish' );

            // return refresh, unless overwritten in filter (scc wants to run some js code)
            echo apply_filters( 'swifty_page_manager_publish_ajax_succes', $this->script_refresh_tree, $post_id );
        } else {
            // fail, only refresh tree
            echo $this->script_refresh_tree;
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

        foreach( $post_meta as $key => $val ) {
            if( is_array( $val ) && count( $val ) === 1 ) {
                $post_meta[ $key ] = $val[ 0 ];
            }
        }

        $defaults = array(
            'spm_show_in_menu' => 'show',
            'spm_page_title_seo' => $post->post_title,
        );

        foreach( $this->areas as $area ) {
            $defaults[ 'spm_' . $area . '_visibility' ] = 'default';
        }

        if( $this->is_ssm_active ) {
            $defaults = array_merge( $defaults, array(
                'spm_show_as_first' => 'show',
                'spm_alt_menu_text' => ''
            ) );
        }

        foreach ( $defaults as $key => $val ) {
            if ( ! isset( $post_meta[ $key ] ) ) {
                $post_meta[ $key ] = $val;
            }
        }

        if ( $this->is_ssm_active ) {
            if ( ! empty( $post_meta['spm_url'] ) ) {
                $spm_page_url = $post_meta['spm_url'];
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

        $spm_alt_menu_text = '';

        if( $this->is_ssm_active ) {
            if( $post_meta[ 'spm_show_as_first' ] === 'hide' ) {
                $spm_alt_menu_text = $post_meta[ 'spm_alt_menu_text' ];
                $post_meta[ 'spm_alt_menu_text' ] = '';
            }
        }

        ?>
        var li = jQuery( '#spm-id-<?php echo $post_id; ?>' );

        li.find( '#spm_post_name_error' ).html( '' );
        li.find( '#spm_post_name_message' ).html( '' );
        li.find( 'input[name="post_title"]' ).val( <?php echo json_encode( $post->post_title ); ?> );
        li.find( 'input[name="post_status"]' ).val( [ <?php echo json_encode( $post_status ); ?> ] );
        li.find( 'input[name="post_name"]' ).val( <?php echo json_encode( $spm_page_url ); ?> );
        li.find( 'input[name="spm_is_custom_url"]' ).val( <?php echo json_encode( $spm_is_custom_url ); ?> );
        li.find( 'input[name="spm_show_in_menu"]' ).val( [ <?php echo json_encode( $post_meta['spm_show_in_menu'] ); ?> ] );
        li.find( 'input[name="spm_page_title_seo"]' ).val( <?php echo json_encode( $post_meta['spm_page_title_seo'] ); ?> );
        <?php if ( $this->is_ssm_active ):
            foreach( $this->areas as $area ) { ?>
        li.find( 'input[name="spm_<?php echo $area; ?>_visibility"]' ).val( [ <?php echo json_encode( $post_meta['spm_' . $area . '_visibility'] ); ?> ] );
            <?php } ?>
        li.find( 'input[name="spm_show_as_first"]' ).val( [ <?php echo json_encode( $post_meta[ 'spm_show_as_first' ] ); ?> ] );
        li.find( 'input[name="spm_alt_menu_text"]' ).val( <?php echo json_encode( $post_meta[ 'spm_alt_menu_text' ] ); ?> );

        if ( li.find( 'input[name="spm_show_as_first"]:checked' ).val() === 'hide' ) {
            li.find( 'input[name="spm_alt_menu_text"]' )
                .attr( 'data-alt_menu_text', <?php echo json_encode( $spm_alt_menu_text ); ?> )
                .prop( 'disabled', true )
                .val( '' );
        }

        <?php endif;
        exit;
    }

    /**
     * Called via WP Admin Ajax 'wp_ajax_spm_sanitize_url' if can_edit_pages && is_ssm_active
     *
     * Ajax function to use Wordpress' sanitize_title_with_dashes function to prepare an URL string
     * and check the URL string for uniqueness
     * _POST[ 'post_parent' ] - the parent ID of the post, use -1 with valid post_id to get the parent ID from the post
     * _POST[ 'post_id' ]     - the post id, use 0 and valid post_parent for a new post
     * _POST[ 'url' ]         - url that will be sanitized / checked
     * _POST[ 'path' ]        - is the path of the post_parent, when creating a new post
     * _POST[ 'do_sanitize' ] - 0 or 1 use the WP sanitize functions, used when a post title is used for the url
     *
     * returned json
     * message - result of the call, no errors
     * error   - a error was detected in the given url: wrong characters / existing url
     * url     - if do_sanitize = 1, return the sanitized url, otherwise return the given url
     *
     */
    public function ajax_sanitize_url_and_check()
    {
        $result = array();
        $result[ 'message' ] = __( 'Url is unique.', 'swifty-page-manager' ); // no message everything is ok
        $result[ 'error' ] = ''; // no message everything is ok

        if( isset( $_POST[ 'url' ] ) && isset( $_POST[ 'post_parent' ] ) && isset( $_POST[ 'post_id' ] ) && isset( $_POST[ 'path' ] ) && isset( $_POST[ 'do_sanitize' ] ) ) {
            $url = $_POST[ 'url' ];
            $path = $_POST[ 'path' ];
            $post_id = intval( $_POST[ 'post_id' ] );
            $post_parent = intval( $_POST[ 'post_parent' ] );

            $url = rtrim( $url, '/' );
            if( $_POST[ 'do_sanitize' ] ) {
                $url = sanitize_title_with_dashes( $url );
            }

            // this is the sanitize action that will be used when saving settings
            $post_name = preg_replace("~[ ]~", "-", $url);
            $post_name = preg_replace("~[^a-z0-9//_-]~i", "", $post_name);

            if( $post_name !== $url ) {
                $result[ 'message' ] = '';
                $result[ 'error' ] = __( 'Url contains forbidden characters.', 'swifty-page-manager' );
            } else if( $post_id || ($post_parent >= 0)) {
                // make sure no path delimeters are surrounding the post_name even when path is empty
                $post_name = trim( $path, '/' ) . '/' . trim( $post_name, '/' );
                $post_name = trim( $post_name, '/' );

                if( ( $post_parent === -1 ) && $post_id ) {
                    $post = get_post( $post_id );
                    $post_parent = $post->post_parent;
                }

                if( ! $this->spm_is_unique_spm_url( $post_id, $post_parent, $post_name ) ) {
                    $result[ 'message' ] = '';
                    $result[ 'error' ] = __( 'Url is not unique.', 'swifty-page-manager' );
                }
            }
            $result[ 'url' ] = $url;
        }

        echo json_encode( $result );
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
        if( $this->is_ssm_active && ( $post_status === 'publish' ) ) {
            // use autosave content when publishing, remove autosave (no newer autosave record)
            $autosave_content = LibSwiftyPluginView::get_instance()->get_autosave_version_if_newer( $post_id );
            $post_data = array(
                'ID' => $post_id,
                'post_status' => $post_status
            );

            if( $autosave_content ) {
                $post_data[ 'post_content' ] = $autosave_content;
            }

            wp_update_post( $post_data );
        } else {
            // remember current autosave
            LibSwiftyPlugin::get_instance()->wp_update_post_keep_autosave( $post_id,
                array(
                    'ID' => $post_id,
                    'post_status' => $post_status
                ) );
        }
        do_action( 'swifty_page_manager_publish', $post_id );
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
        $args['suppress_filters'] = 0; // WPML plugin support
        $pages = get_posts( $args );
        $added = true;

        while ( ! empty( $pages ) && $added ) {
            $added = false;
            $keys  = array_keys( $pages );

            foreach ( $keys as $key ) {
                $page = $pages[ $key ];

                // we now check for this page, when we have the need for blacklisting more pages we can
                // create a filter for it
                if( $this->is_ssm_active && ( 'ninja_forms_preview_page' === $page->post_title ) ) {
                    unset( $pages[ $key ] );
                } else {
                    if( isset( $this->_by_page_id[ $page->ID ] ) ) {
                        $branch = &$this->_by_page_id[ $page->ID ];
                        $branch->page = $page;

                        unset( $branch );
                        unset( $pages[ $key ] );

                        $added = true;
                    } else if( isset( $this->_by_page_id[ $page->post_parent ] ) ) {
                        $parent_branch = &$this->_by_page_id[ $page->post_parent ];
                        $new_branch = new stdClass();
                        $new_branch->page = $page;
                        $new_branch->children = array();
                        $this->_by_page_id[ $new_branch->page->ID ] = &$new_branch;
                        $parent_branch->children[ ] = &$new_branch; // Warning, does not sort children correctly

                        unset( $new_branch );
                        unset( $pages[ $key ] );

                        $added = true;
                    }
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
            if( !$this->is_ssm_active || $one_page->post_parent ) {
                $arr_page_css_styles[] = 'spm-can-delete';
            } else {
                // we can not delete the front page
                if( $this->front_page_id !== $page_id ) {
                    $page_count = count( get_pages( 'parent=0' ) );
                    // we are not allowed to remove the last published page
                    if( ( $page_count > 1 ) || ( $one_page->post_status !== 'publish' ) ) {
                        $arr_page_css_styles[] = 'spm-can-delete';
                    }
                }
            }
        }

        if ( $this->is_ssm_active ) {
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
        $page_json_data['metadata']['swifty_edit_url'] = add_query_arg( 'swcreator_edit', 'main', htmlspecialchars_decode( get_permalink( $one_page->ID ) ) );
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
                    //$return_id = wp_update_post( $post_update, true ); < now keeping autosave
                    $return_id = LibSwiftyPlugin::get_instance()->wp_update_post_keep_autosave( $one_post->ID, $post_update, true );

                    if (is_wp_error($return_id)) {
                        die( 'Error: could not update post with id ' . $post_update['ID'] . '<br>Technical details: ' . print_r( $return_id, true ) );
                    }
                }

                if ( ! $has_passed_ref_post && $ref_post->ID === $one_post->ID ) {
                    $has_passed_ref_post = true;
                }
            }
        }

        return $result;
    }

    /**
     * echo simple language switcher for WPML, 'sitepress' is used as plugin translation domain
     */
    function admin_language_switcher() {
        // do nothing if WPML is not active
        if( ! class_exists( 'SitePress' ) ) {
            return;
        }
        // If check_settings_integrity exists then it should not fail, otherwise ignore.
        if( method_exists( 'SitePress', 'check_settings_integrity' ) && ! SitePress::check_settings_integrity() ) {
            return;
        }

        global $sitepress;

        // We need those methods, so make sure they are available.
        if( !method_exists( $sitepress, 'get_current_language' ) ||
            !method_exists( $sitepress, 'get_default_language' ) ||
            !method_exists( $sitepress, 'get_active_languages' )) {
            return;
        }

        $languages_links   = array();

        $current_language = $sitepress->get_current_language();
        $current_language = $current_language ? $current_language : $sitepress->get_default_language();

        if ( isset( $_SERVER[ 'QUERY_STRING' ] ) ) {
            parse_str( $_SERVER[ 'QUERY_STRING' ], $query_vars );
            unset( $query_vars[ 'lang' ], $query_vars[ 'admin_bar' ] );
        } else {
            $query_vars = array();
        }
        $query_string = http_build_query( $query_vars );
        if ( empty( $query_string ) ) {
            $query_string = '?';
        } else {
            $query_string = '?' . $query_string . '&';
        }

        foreach ( $sitepress->get_active_languages() as $lang ) {

            $query = $query_string . 'lang=' . $lang[ 'code' ]; // the default language need to specified explicitly yoo in order to set the lang cookie

            $link_url = admin_url( basename( $_SERVER[ 'SCRIPT_NAME' ] ) . $query );

            $languages_links[ $lang[ 'code' ] ] = array(
                'url'     => $link_url,
                'current' => $lang[ 'code' ] == $current_language,
                'anchor'  => $lang[ 'display_name' ]
            );
        }

        $query = $query_string . 'lang=all';
        $link_url = admin_url( basename( $_SERVER[ 'SCRIPT_NAME' ] ) . $query );

        $languages_links[ 'all' ] = array(
            'url'  => $link_url, 'current' => 'all' == $current_language, 'anchor' => __( 'All languages', 'sitepress' )
        );

        // We start with the current language in our select.
		$lang   = $languages_links[ $current_language ];

        if ( $languages_links ) {
?>
<select onchange="window.location=this.value" style="margin: 0 0 0 20px;">
<option value="<?php echo esc_url( $lang[ 'url' ] ); ?>"><?php echo $lang[ 'anchor' ]; ?></option>
<?php
            foreach ( $languages_links as $code => $lang ) {
                if ( $code == $current_language )
                    continue;
                ?>
                <option value="<?php echo esc_url( $lang[ 'url' ] ); ?>"><?php echo $lang[ 'anchor' ]; ?></option>
                <?php
            }
?>
</select>
<?php
        }
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
