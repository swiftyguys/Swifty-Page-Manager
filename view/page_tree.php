<?php
/**
 * Variables that need to be set:
 * @var SwiftyPages $this
 */
$post_type_object = get_post_type_object( $this->_post_type );
$post_new_file = "post-new.php?post_type=".$this->_post_type;

?>
<div class="wrap">
    <?php echo get_screen_icon(); ?>
    <h2><?php echo _x( 'SwiftyPages', "headline of page with tree", 'swiftypages' ); ?></h2>
    <?php

    $get_pages_args = array( "post_type" => $this->_post_type );

    $pages = $this->_get_pages( $get_pages_args );

    // check if wpml is active and if this post type is one of its enabled ones
    $wpml_current_lang = "";
    $wmpl_active_for_post = false;
    if ( defined( "ICL_SITEPRESS_VERSION" ) )
    {

        $wpml_post_types = $sitepress->get_translatable_documents();
        if ( array_key_exists( $post_type, $wpml_post_types ) )
        {
            $wmpl_active_for_post = true;
            $wpml_current_lang    = $sitepress->get_current_language();
        }

    }

    $status_data_attributes = array( "all" => "", "publish" => "", "trash" => "" );

    // Calculate post counts
    if ( $wpml_current_lang )
    {

// Count code for WPML, mostly taken/inspired from  WPML Multilingual CMS, sitepress.class.php
        $langs = array();

        $wpml_post_counts = $this->_get_wpml_post_counts( $this->_post_type );

        $post_count_all     = (int) @$wpml_post_counts[ "private" ][ $wpml_current_lang ] + (int) @$wpml_post_counts[ "future" ][ $wpml_current_lang ] + (int) @$wpml_post_counts[ "publish" ][ $wpml_current_lang ] + (int) @$wpml_post_counts[ "draft" ][ $wpml_current_lang ];
        $post_count_publish = (int) @$wpml_post_counts[ "publish" ][ $wpml_current_lang ];
        $post_count_trash   = (int) @$wpml_post_counts[ "trash" ][ $wpml_current_lang ];

        foreach ( $wpml_post_counts[ "publish" ] as $one_wpml_lang => $one_wpml_lang_count )
        {
            if ( "all" === $one_wpml_lang )
            {
                continue;
            }
            $lang_post_count_all     = (int) @$wpml_post_counts[ "publish" ][ $one_wpml_lang ] + (int) @$wpml_post_counts[ "draft" ][ $one_wpml_lang ];
            $lang_post_count_publish = (int) @$wpml_post_counts[ "publish" ][ $one_wpml_lang ];
            $lang_post_count_trash   = (int) @$wpml_post_counts[ "trash" ][ $one_wpml_lang ];
            $status_data_attributes[ "all" ] .= " data-post-count-{$one_wpml_lang}='{$lang_post_count_all}' ";
            $status_data_attributes[ "publish" ] .= " data-post-count-{$one_wpml_lang}='{$lang_post_count_publish}' ";
            $status_data_attributes[ "trash" ] .= " data-post-count-{$one_wpml_lang}='{$lang_post_count_trash}' ";
        }

    }
    else
    {
        $post_count         = wp_count_posts( $this->_post_type );
        $post_count_all     = $post_count->publish + $post_count->future + $post_count->draft + $post_count->pending + $post_count->private;
        $post_count_publish = $post_count->publish;
        $post_count_trash   = $post_count->trash;
    }


    // output js for the root/top level
    // function swiftypages_print_childs($pageID, $view = "all", $arrOpenChilds = null, $post_type) {
    // @todo: make into function since used at other places
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

    $jsonData = $this->_get_childrenJsonData( 0, $jstree_open, $this->_post_type );

    ?>
    <script type="text/javascript">
        jQuery( function ( $ ) {
            var swiftypages_jsondata = $.data( document, 'swiftypages_jsondata' );
            swiftypages_jsondata["<?php echo $this->_post_type ?>"] = <?php echo json_encode( $jsonData ); ?>;
        } );
    </script>

    <div class="swiftypages_wrapper">
    <input type="hidden" name="swiftypages_meta_post_type" value="<?php echo $this->_post_type ?>"/>
    <input type="hidden" name="swiftypages_meta_wpml_language" value="<?php echo $wpml_current_lang ?>"/>
    <?php

    // check if WPML is activated and show a language-menu
    if ( $wmpl_active_for_post )
    {

        $wpml_langs       = icl_get_languages();
        $wpml_active_lang = null;
        if ( sizeof( $wpml_langs ) >= 1 )
        {
            $lang_out = "";
            $lang_out .= "<ul class='swiftypages-subsubsub swiftypages_switch_langs'>";
            foreach ( $wpml_langs as $one_lang )
            {
                $one_lang_details = $sitepress->get_language_details( $one_lang[ "language_code" ] ); // english_name | display_name
                $selected         = "";
                if ( $one_lang[ "active" ] )
                {
                    $wpml_active_lang = $one_lang;
                    $selected         = "current";
                }

                $lang_count = (int) @$wpml_post_counts[ "publish" ][ $one_lang[ "language_code" ] ] + (int) @$wpml_post_counts[ "draft" ][ $one_lang[ "language_code" ] ];

                $lang_out .= "
						<li>
							<a class='swiftypages_switch_lang $selected swiftypages_switch_language_code_{$one_lang["language_code"]}' href='#'>
								$one_lang_details[display_name]
								<span class='count'>(" . $lang_count . ")</span>
							</a> |</li>";
            }
            $lang_out = preg_replace( '/ \|<\/li>$/', "</li>", $lang_out );
            $lang_out .= "</ul>";
            echo $lang_out;
        }

    }

    if (true) {

    // start the party!

    ?>
    <ul class="swiftypages-subsubsub swiftypages-subsubsub-select-view">
        <li class="swiftypages_view_is_status_view">
            <a class="swiftypages_view_all  <?php echo ( $this->_view == "all" ) ? "current" : "" ?>"
               href="#" <?php echo $status_data_attributes[ "all" ] ?>>
                <?php _e( "All", 'swiftypages' ) ?>
                <span class="count">(<?php echo $post_count_all ?>)</span>
            </a> |
        </li>
        <li class="swiftypages_view_is_status_view">
            <a class="swiftypages_view_public <?php echo ( $this->_view == "public" ) ? "current" : "" ?>"
               href="#" <?php echo $status_data_attributes[ "publish" ] ?>>
                <?php _e( "Public", 'swiftypages' ) ?>
                <span class="count">(<?php echo $post_count_publish ?>)</span>
            </a> |
        </li>

        <li><a href="#" class="swiftypages_open_all"><?php _e( "Expand", 'swiftypages' ) ?></a> |</li>
        <li><a href="#" class="swiftypages_close_all"><?php _e( "Collapse", 'swiftypages' ) ?></a></li>

    </ul>

    <div class="swiftypages_working">
        <?php _e( "Loading...", 'swiftypages' ) ?>
    </div>

    <div class="swiftypages_message updated below-h2 hidden"><p>Message goes here.</p></div>

    <div class="swiftypages_container tree-default">
        <?php _e( "Loading tree", 'swiftypages' ) ?>
    </div>

    <div style="clear: both;"></div>

    <!-- template forpopup with actions -->
    <div class="swiftypages_page_actions">

        <!-- swiftypages_page_actions_page_id -->
        <h4 class="swiftypages_page_actions_headline"></h4>

        <p class="swiftypages_action_edit_and_view">
            <a href="#" title='<?php _e( "Edit page", 'swiftypages' ) ?>'
               class='swiftypages_action_edit'><?php _e( "Edit", 'swiftypages' ) ?></a>
            <a href="#" title='<?php _e( "View page", 'swiftypages' ) ?>'
               class='swiftypages_action_view'><?php _e( "View", 'swiftypages' ) ?></a>
        </p>

        <!-- links to add page -->
        <p class="swiftypages_action_add_and_edit_page">

            <span class='swiftypages_action_add_page'><?php echo $post_type_object->labels->add_new_item ?></span>

            <a class='swiftypages_action_add_page_after' href="#"
               title='<?php _e( "Add new page after", 'swiftypages' ) ?>'><?php _e( "After", 'swiftypages' ) ?></a>

            <a class='swiftypages_action_add_page_inside' href="#"
                     title='<?php _e( "Add new page inside", 'swiftypages' ) ?>' ><?php _e( "Inside", 'swiftypages' ) ?></a>

            <!-- <span class="swiftypages_action_add_page_inside_disallowed"><?php _e( "Can not create page inside of a page with draft status", 'swiftypages' ) ?></span> -->

        </p>

        <dl>
            <dt><?php _e( "Last modified", 'swiftypages' ) ?></dt>
            <dd>
                <span class="swiftypages_page_actions_modified_time"></span> <?php _e( "by", 'swiftypages' ) ?>
                <span class="swiftypages_page_actions_modified_by"></span>
            </dd>
            <dt><?php _e( "Page ID", 'swiftypages' ) ?></dt>
            <dd><span class="swiftypages_page_actions_page_id"></span></dd>
        </dl>

        <span class="swiftypages_page_actions_arrow"></span>
    </div>
</div>

<!-- SwiftySite template page buttons-->
<span class="ss-page-actions-tmpl __TMPL__ ss-hidden">
    <span class="button button-primary ss-button ss-button-add-page" data-ss-action="add" title='<?php _e( "Add page(s)", 'swiftypages' ) ?>'>
            <span class="dashicons ss-icon dashicons-plus"></span>
        </span>
        <span class="button button-primary ss-button ss-button-page-settings" data-ss-action="settings"
              title='<?php _e( "Edit page", 'swiftypages' ) ?>'>
            <span class="dashicons ss-icon dashicons-admin-generic"></span>
        </span>
        <span class="button button-primary ss-button ss-button-delete-page" data-ss-action="delete"
              title='<?php _e( "Delete page", 'swiftypages' ) ?>'>
            <span class="dashicons ss-icon dashicons-no"></span>
        </span>
        <span class="button button-primary ss-button ss-button-edit-page" data-ss-action="edit"
              title='<?php _e( "Edit page content", 'swiftypages' ) ?>'>
            <span class="dashicons ss-icon dashicons-admin-tools"></span>
        </span>
        <span class="button button-primary ss-button ss-button-view-page" data-ss-action="view"
              title='<?php _e( "View page", 'swiftypages' ) ?>'>
            <span class="dashicons ss-icon dashicons-visibility"></span>
        </span>
    </span>

    <!-- SwiftySite template Delete -->
<span class="ss-container ss-page-delete-tmpl __TMPL__ ss-hidden">
    <form method="post" class="ss-form ss-page-delete-form">
                    <table class="ss-table wp-list-table widefat fixed pages">
                        <tbody>
                        <tr class="inline-edit-row inline-edit-row-page inline-edit-page quick-edit-row quick-edit-row-page inline-edit-page inline-editor"
                            style="">
                            <td colspan="5" class="colspanchange">
                                <fieldset class="inline-edit-col-left">
                                    <div class="inline-edit-col">
                                        <div class="inline-edit-group">
                                                <span class="title">
                                                    <?php _e( "Are you sure you want to permanently delete this page with all it's content?", 'swiftypages' ) ?>
                                                </span>
                                        </div>
                                    </div>
                                </fieldset>
                                <fieldset class="inline-edit-col-right">
                                    <div class="inline-edit-group ss-buttons-confirm">
                                        <a accesskey="c" href="#inline-edit"
                                           class="button-secondary cancel alignright ss-button">
                                            <?php _e( "Cancel", 'swiftypages' ) ?>
                                        </a>
                                        <br class="clear">
                                        <a accesskey="s" href="#inline-edit"
                                           class="button-primary delete alignright ss-button">
                                            <?php _e( "Delete", 'swiftypages' ) ?>
                                        </a>
                                    </div>
                                </fieldset>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </form>
            </span>

    <!-- SwiftySite template Add/Edit -->
<span class="ss-container ss-page-add-edit-tmpl __TMPL__ ss-hidden">
    <form method="post" class="ss-form ss-page-add-edit-form">
                <table class="ss-table wp-list-table widefat fixed pages">
                    <tbody>
            <tr class="inline-edit-row inline-edit-row-page inline-edit-page quick-edit-row quick-edit-row-page inline-edit-page inline-editor">
                        <td colspan="5" class="colspanchange">
                            <fieldset class="inline-edit-col-left">
                                <div class="inline-edit-col ss-basic-container">
                                    <label for="ss-menu-button-text" class="ss-label">
                                            <span class="title">
                                                <?php _e( "Menu button text", "cms-tree-page-view" ) ?>
                                            </span>
                                            <span class="input-text-wrap">
                                    <input name="post_title"
                                                       type="text"
                                                       class="ss-input ss-input-text"
                                                       title="Enter menu button text">
                                            </span>
                                    </label>
                                    <label for="ss-page-title-seo" class="ss-label">
                                            <span class="title">
                                                <?php _e( "Page title for Google", "cms-tree-page-view" ) ?>
                                            </span>
                                            <span class="input-text-wrap">
                                                <input name="page-title-seo"
                                                       type="text"
                                                       class="ss-input ss-input-text"
                                                       title="Enter page title for search engines">
                                            </span>
                                    </label>
                                    <label class="ss-label">
                                            <span class="title">
                                                <?php _e( "Page position in tree", "cms-tree-page-view" ) ?>
                                            </span>
                                            <span class="input-text-wrap">
                                                <label class="alignleft ss-radio-label">
                                        <input name="ss_page_position"
                                                           type="radio"
                                                           value="after"
                                                           class="ss-input-radio">
                                                    <span class="radiobutton-title">
                                                        <?php _e( "Next", "cms-tree-page-view" ) ?>
                                                    </span>
                                                </label>
                                                <label class="alignleft ss-radio-label">
                                        <input name="ss_page_position"
                                                           type="radio"
                                                           value="inside"
                                                           class="ss-input-radio">
                                                    <span class="radiobutton-title">
                                                        <?php _e( "Sub", "cms-tree-page-view" ) ?>
                                                    </span>
                                                </label>
                                            </span>
                                    </label>
                                    <label for="ss-custom-page-url" class="ss-label">
                                            <span class="title">
                                                <?php _e( "Customize page url", "cms-tree-page-view" ) ?>
                                            </span>
                                            <span class="input-text-wrap">
                                                <input name="post_name"
                                                       type="text"
                                                       class="ss-input ss-input-text"
                                                       title="Enter a custom page url">
                                            </span>
                                    </label>
                                    <label class="ss-label">
                                            <span class="title">
                                                <?php _e( "Show in menu", "cms-tree-page-view" ) ?>
                                            </span>
                                             <span class="input-text-wrap">
                                                <label class="alignleft ss-radio-label">
                                                    <input name="ss_show_in_menu"
                                                           type="radio"
                                                           value="show"
                                                           class="ss-input-radio">
                                                    <span class="radiobutton-title">
                                                        <?php _e( "Show", "cms-tree-page-view" ) ?>
                                                    </span>
                                                </label>
                                                <label class="alignleft ss-radio-label">
                                                    <input name="ss_show_in_menu"
                                                           type="radio"
                                                           value="hide"
                                                           class="ss-input-radio">
                                                    <span class="radiobutton-title">
                                                        <?php _e( "Hide", "cms-tree-page-view" ) ?>
                                                    </span>
                                                </label>
                                            </span>
                                    </label>
                                    <label class="ss-label">
                                            <span class="title">
                                                <?php _e( "Draft or live", "cms-tree-page-view" ) ?>
                                            </span>
                                            <span class="input-text-wrap">
                                                <label class="alignleft ss-radio-label">
                                        <input name="_status"
                                                           type="radio"
                                                           value="draft"
                                                           class="ss-input-radio">
                                                    <span class="radiobutton-title">
                                                        <?php _e( "Draft", "cms-tree-page-view" ) ?>
                                                    </span>
                                                </label>
                                                <label class="alignleft ss-radio-label">
                                        <input name="_status"
                                                           type="radio"
                                               value="publish"
                                                           class="ss-input-radio">
                                                    <span class="radiobutton-title">
                                                        <?php _e( "Live", "cms-tree-page-view" ) ?>
                                                    </span>
                                                </label>
                                            </span>
                                    </label>
                                    <label class="ss-label">
                                            <span class="title">
                                                <?php _e( "Show or hide header", "cms-tree-page-view" ) ?>
                                            </span>
                                            <span class="input-text-wrap">
                                                <label class="alignleft ss-radio-label">
                                                    <input name="ss_header_visibility"
                                                           type="radio"
                                                           value="show"
                                                           class="ss-input-radio">
                                                    <span class="radiobutton-title">
                                                        <?php _e( "Show", "cms-tree-page-view" ) ?>
                                                    </span>
                                                </label>
                                                <label class="alignleft ss-radio-label">
                                                    <input name="ss_header_visibility"
                                                           type="radio"
                                               value="hide"
                                                           class="ss-input-radio">
                                                    <span class="radiobutton-title">
                                                        <?php _e( "Hide", "cms-tree-page-view" ) ?>
                                                    </span>
                                                </label>
                                            </span>
                                    </label>
                                    <label class="ss-label">
                                            <span class="title">
                                                <?php _e( "Show or hide sidebar", "cms-tree-page-view" ) ?>
                                            </span>
                                            <span class="input-text-wrap">
                                                <label class="alignleft ss-radio-label">
                                                    <input name="ss_sidebar_visibility"
                                                           type="radio"
                                               value="left"
                                                           class="ss-input-radio">
                                                    <span class="radiobutton-title">
                                                        <?php _e( "Left", "cms-tree-page-view" ) ?>
                                                    </span>
                                                </label>
                                                <label class="alignleft ss-radio-label">
                                                    <input name="ss_sidebar_visibility"
                                                           type="radio"
                                               value="right"
                                                           class="ss-input-radio">
                                                    <span class="radiobutton-title">
                                                        <?php _e( "Right", "cms-tree-page-view" ) ?>
                                                    </span>
                                                </label>
                                                <label class="alignleft ss-radio-label">
                                                    <input name="ss_sidebar_visibility"
                                                           type="radio"
                                                           value="hide"
                                                           class="ss-input-radio">
                                                    <span class="radiobutton-title">
                                                        <?php _e( "Hide", "cms-tree-page-view" ) ?>
                                                    </span>
                                                </label>
                                            </span>
                                    </label>
                                </div>
                            </fieldset>
                            <fieldset class="inline-edit-col-right">
                                <div class="inline-edit-group ss-buttons-confirm">
                            <a accesskey="c" class="button-secondary alignright ss-button ss-page-add-edit-cancel">
                                        <?php _e( "Cancel", 'swiftypages' ) ?>
                                    </a>
                                    <br class="clear">
                            <a accesskey="s" class="button-primary alignright ss-button ss-page-add-edit-save">
                                        <?php _e( "Save", 'swiftypages' ) ?>
                                    </a>
                                    <a accesskey="s" class="button-primary alignright ss-button ss-page-add-save">
                                        <?php _e( "Save New", 'swiftypages' ) ?>
                                    </a>
                                </div>
                            </fieldset>
                        </td>
                    </tr>
                    </tbody>
                </table>
                </form>
            </span>
<?php // _get_list_table('WP_Posts_List_Table')->inline_edit(); ?>
            <?php wp_nonce_field( "inlineeditnonce", '_inline_edit' ) ?>
    <?php
    }

    if ( empty( $pages ) )
    {
        echo '<div class="updated fade below-h2"><p>' . __( "No posts found.", 'swiftypages' ) . '</p></div>';
    }

    ?>
</div>