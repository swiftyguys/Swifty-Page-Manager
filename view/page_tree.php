<?php
/**
 * Variables that need to be set:
 * @var SwiftyPageManager $this
 */
$post_type_object = get_post_type_object( $this->_post_type );
$post_new_file = "post-new.php?post_type=".$this->_post_type;

?>
<div class="wrap">
    <?php echo get_screen_icon(); ?>
    <h2><?php echo _x( 'Swifty Page Manager', 'headline of page with tree', 'swifty-page-manager' ); ?></h2>
    <?php

    $get_pages_args = array( "post_type" => $this->_post_type );

    // Check if wpml is active and if this post type is one of its enabled ones
    $wpml_current_lang    = "";
    $wmpl_active_for_post = false;

    if ( defined( "ICL_SITEPRESS_VERSION" ) ) {
        $wpml_post_types = $sitepress->get_translatable_documents();

        if ( array_key_exists( $post_type, $wpml_post_types ) ) {
            $wmpl_active_for_post = true;
            $wpml_current_lang    = $sitepress->get_current_language();
        }
    }

    $status_data_attributes = array( "all" => "", "publish" => "", "trash" => "" );

    // Calculate post counts
    if ( $wpml_current_lang ) {
        // Count code for WPML, mostly taken/inspired from  WPML Multilingual CMS, sitepress.class.php
        $langs = array();

        $wpml_post_counts = $this->_get_wpml_post_counts( $this->_post_type );

        $post_count_all     = (int) @$wpml_post_counts[ "private" ][ $wpml_current_lang ] + (int) @$wpml_post_counts[ "future" ][ $wpml_current_lang ] + (int) @$wpml_post_counts[ "publish" ][ $wpml_current_lang ] + (int) @$wpml_post_counts[ "draft" ][ $wpml_current_lang ];
        $post_count_publish = (int) @$wpml_post_counts[ "publish" ][ $wpml_current_lang ];
        $post_count_trash   = (int) @$wpml_post_counts[ "trash"   ][ $wpml_current_lang ];

        foreach ( $wpml_post_counts[ "publish" ] as $one_wpml_lang => $one_wpml_lang_count ) {
            if ( "all" === $one_wpml_lang ) {
                continue;
            }

            $lang_post_count_all     = (int) @$wpml_post_counts[ "publish" ][ $one_wpml_lang ] + (int) @$wpml_post_counts[ "draft" ][ $one_wpml_lang ];
            $lang_post_count_publish = (int) @$wpml_post_counts[ "publish" ][ $one_wpml_lang ];
            $lang_post_count_trash   = (int) @$wpml_post_counts[ "trash"   ][ $one_wpml_lang ];
            $status_data_attributes[ "all"     ] .= " data-post-count-{$one_wpml_lang}='{$lang_post_count_all}' ";
            $status_data_attributes[ "publish" ] .= " data-post-count-{$one_wpml_lang}='{$lang_post_count_publish}' ";
            $status_data_attributes[ "trash"   ] .= " data-post-count-{$one_wpml_lang}='{$lang_post_count_trash}' ";
        }
    } else {
        $post_count         = wp_count_posts( $this->_post_type );
        $post_count_all     = $post_count->publish + $post_count->future + $post_count->draft + $post_count->pending + $post_count->private;
        $post_count_publish = $post_count->publish;
        $post_count_trash   = $post_count->trash;
    }

    // output js for the root/top level
    // function spm_print_childs($pageID, $view = "all", $arrOpenChilds = null, $post_type) {
    // @todo: make into function since used at other places
    $jstree_open = array();

    if ( isset( $_COOKIE[ "jstree_open" ] ) ) {
        $jstree_open = $_COOKIE[ "jstree_open" ]; // like this: [jstree_open] => spm-id-1282,spm-id-1284,spm-id-3
        $jstree_open = explode( ",", $jstree_open );

        for ( $i = 0; $i < sizeof( $jstree_open ); $i++ ) {
            $jstree_open[ $i ] = (int) str_replace( "#spm-id-", "", $jstree_open[ $i ] );
        }
    }

    $jsonData = $this->getJsonData( $this->getTree() );

    ?>
    <script type="text/javascript">
        jQuery( function ( $ ) {
            var spmJsonData = $.data( document, 'spm_json_data' );
            spmJsonData[ "<?php echo $this->_post_type ?>" ] = <?php echo json_encode( $jsonData ); ?>;
        } );
    </script>

    <div class="spm_wrapper">
        <input type="hidden" name="spm_meta_post_type" value="<?php echo $this->_post_type ?>" />
        <input type="hidden" name="spm_meta_wpml_language" value="<?php echo $wpml_current_lang ?>" />
    <?php

    // Check if WPML is activated and show a language-menu
    if ( $wmpl_active_for_post ) {
        $wpml_langs       = icl_get_languages();
        $wpml_active_lang = null;

        if ( sizeof( $wpml_langs ) >= 1 ) {
            $lang_out  = "";
            $lang_out .= "<ul class='spm-subsubsub spm-switch-langs'>";

            foreach ( $wpml_langs as $one_lang ) {
                $one_lang_details = $sitepress->get_language_details( $one_lang[ "language_code" ] ); // english_name | display_name
                $selected         = "";

                if ( $one_lang[ "active" ] ) {
                    $wpml_active_lang = $one_lang;
                    $selected         = "current";
                }

                $lang_count = (int) @$wpml_post_counts[ "publish" ][ $one_lang[ "language_code" ] ] + (int) @$wpml_post_counts[ "draft" ][ $one_lang[ "language_code" ] ];
                $lang_out  .= "
                    <li>
                        <a class='spm_switch_lang $selected spm_switch_language_code_{$one_lang["language_code"]}' href='#'>
                            $one_lang_details[display_name]
                            <span class='count'>(" . $lang_count . ")</span>
                        </a> |</li>";
            }

            $lang_out  = preg_replace( '/ \|<\/li>$/', "</li>", $lang_out );
            $lang_out .= "</ul>";

            echo $lang_out;
        }
    }

    if ( true ) {
    ?>
    <ul class="spm-subsubsub spm-subsubsub-select-view">
        <li>
            <a class="cms_spm_status_any  <?php echo ($this->getPostStatus()=="any") ? "current" : "" ?>" href="<?php echo add_query_arg( 'status', 'any', $this->getPluginUrl() ); ?>" <?php echo $status_data_attributes["all"] ?>>
                <?php _e("All", 'swifty-page-manager') ?>
                <span class="count">(<?php echo $post_count_all ?>)</span>
            </a> |
        </li>
        <li>
            <a class="cms_spm_status_publish <?php echo ($this->getPostStatus()=="publish") ? "current" : "" ?>" href="<?php echo add_query_arg( 'status', 'publish', $this->getPluginUrl() ); ?>" <?php echo $status_data_attributes["publish"] ?>>
                <?php _e("Published", 'swifty-page-manager') ?>
                <span class="count">(<?php echo $post_count_publish ?>)</span>
            </a> |
        </li>

        <?php if ( $post_count_trash ): ?>
        <li>
            <a class="cms_spm_status_trash" href="<?php echo admin_url() . 'edit.php?post_status=trash&post_type=page'; ?>">
                <?php _e("Trash", 'swifty-page-manager') ?>
                <span class="count">(<?php echo $post_count_trash ?>)</span>
            </a> |
        </li>
        <?php endif; ?>

        <li><a href="#" class="spm_open_all"><?php _e( 'Expand', 'swifty-page-manager' ) ?></a> |</li>
        <li><a href="#" class="spm_close_all"><?php _e( 'Collapse', 'swifty-page-manager' ) ?></a></li>


    </ul>

    <div class="spm_working">
        <?php _e( 'Loading...', 'swifty-page-manager' ) ?>
    </div>

    <div class="spm-message updated below-h2 hidden">
        <p>Message goes here.</p>
    </div>

    <div class="spm-tree-container tree-default">
        <?php _e( 'Loading tree', 'swifty-page-manager' ) ?>
    </div>

    <div style="clear: both;"></div>
</div>

<!-- SwiftySite template page buttons-->
<span class="spm-page-actions-tmpl __TMPL__ spm-hidden">
    <span class="button button-primary spm-button spm-page-button" data-spm-action="add" title="<?php _e( 'Add page', 'swifty-page-manager' ) ?>">
        <span class="dashicons spm-icon dashicons-plus"></span>
    </span>
    <span class="button button-primary spm-button spm-page-button" data-spm-action="settings" title="<?php _e( 'Edit page', 'swifty-page-manager' ) ?>">
        <span class="dashicons spm-icon dashicons-admin-generic"></span>
    </span>
    <span class="button button-primary spm-button spm-page-button" data-spm-action="delete" title="<?php _e( 'Delete page', 'swifty-page-manager' ) ?>">
        <span class="dashicons spm-icon dashicons-trash"></span>
    </span>
    <span class="button button-primary spm-button spm-page-button" data-spm-action="edit" title="<?php _e( 'Edit page content', 'swifty-page-manager' ) ?>">
        <span class="dashicons spm-icon dashicons-edit"></span>
    </span>
    <span class="button button-primary spm-button spm-page-button" data-spm-action="view" title="<?php _e( 'View page', 'swifty-page-manager' ) ?>">
        <span class="dashicons spm-icon dashicons-visibility"></span>
    </span>
    <span class="button button-primary spm-button spm-page-button" data-spm-action="publish" title="<?php _e( 'Publish page', 'swifty-page-manager' ) ?>">
        <span class="dashicons spm-icon dashicons-upload"></span>
    </span>
</span>

<!-- SwiftySite template Delete -->
<span class="spm-container spm-page-delete-tmpl __TMPL__ spm-hidden">
    <form method="post" class="spm-form spm-page-delete-form">
        <input type="hidden" name="is_swifty" value="<?php echo ( $this->is_swifty ) ? '1' : '0' ?>" >
        <table class="spm-table wp-list-table widefat fixed pages">
            <tbody>
                <tr class="inline-edit-row inline-edit-row-page inline-edit-page quick-edit-row quick-edit-row-page inline-edit-page inline-editor">
                    <td colspan="5" class="colspanchange">
                        <fieldset class="inline-edit-col-left">
                            <div class="inline-edit-col">
                                <div class="inline-edit-group">
                                    <span class="title">
                                        <?php _e( "Are you sure you want to delete this page with all it's content?", 'swifty-page-manager' ) ?>
                                    </span>
                                </div>
                            </div>
                        </fieldset>
                        <fieldset class="inline-edit-col-right">
                            <div class="inline-edit-group spm-buttons-confirm">
                                <input type="button" class="button-secondary alignright spm-button spm-do-button" data-spm-action="cancel" value="<?php _e( 'Cancel', 'swifty-page-manager' ) ?>" />
                                <br class="clear">
                                <input type="button" class="button-primary alignright spm-button spm-do-button" data-spm-action="delete" value="<?php _e( 'Delete', 'swifty-page-manager' ) ?>" />
                            </div>
                        </fieldset>
                    </td>
                </tr>
            </tbody>
        </table>
    </form>
</span>

<!-- SwiftySite template Publish -->
<span class="spm-container spm-page-publish-tmpl __TMPL__ spm-hidden">
    <form method="post" class="spm-form spm-page-publish-form">
        <input type="hidden" name="is_swifty" value="<?php echo ( $this->is_swifty ) ? '1' : '0' ?>" >
        <table class="spm-table wp-list-table widefat fixed pages">
            <tbody>
                <tr class="inline-edit-row inline-edit-row-page inline-edit-page quick-edit-row quick-edit-row-page inline-edit-page inline-editor">
                    <td colspan="5" class="colspanchange">
                        <fieldset class="inline-edit-col-left">
                            <div class="inline-edit-col">
                                <div class="inline-edit-group">
                                    <span class="title">
                                        <?php _e( "Are you sure you want to publish this page so it becomes visible to your visitors?", 'swifty-page-manager' ) ?>
                                    </span>
                                </div>
                            </div>
                        </fieldset>
                        <fieldset class="inline-edit-col-right">
                            <div class="inline-edit-group spm-buttons-confirm">
                                <input type="button" class="button-secondary alignright spm-button spm-do-button" data-spm-action="cancel" value="<?php _e( 'Cancel', 'swifty-page-manager' ) ?>" />
                                <br class="clear">
                                <input type="button" class="button-primary alignright spm-button spm-do-button" data-spm-action="publish" value="<?php _e( 'Publish', 'swifty-page-manager' ) ?>" />
                            </div>
                        </fieldset>
                    </td>
                </tr>
            </tbody>
        </table>
    </form>
</span>

<!-- SwiftySite template Add/Edit -->
<span class="spm-container spm-page-add-edit-tmpl __TMPL__ spm-hidden">
    <form method="post" class="spm-form spm-page-add-edit-form">
        <input type="hidden" name="is_swifty" value="<?php echo ( $this->is_swifty ) ? '1' : '0' ?>" >
        <table class="spm-table wp-list-table widefat fixed pages">
            <tbody>
                <tr class="inline-edit-row inline-edit-row-page inline-edit-page quick-edit-row quick-edit-row-page inline-edit-page inline-editor">
                    <td colspan="5" class="colspanchange">
                        <fieldset class="inline-edit-col-left">
                            <div class="inline-edit-col">
                                <label class="spm-basic-feature">
                                    <span class="title spm-label-title">
                                    <?php
                                        if ( $this->is_swifty ) {
                                    ?>
                                        <?php _e( 'Text in menu', 'swifty-page-manager' ) ?>
                                    <?php
                                        } else {
                                    ?>
                                        <?php _e( 'Title', 'swifty-page-manager' ) ?>
                                    <?php
                                        }
                                    ?>
                                    </span>
                                    <span class="input-text-wrap">
                                        <input name="post_title" type="text" class="spm-input spm-input-small spm-input-text" />
                                    </span>
                                </label>
                                <?php
                                    if ( $this->is_swifty ) {
                                ?>
                                <label class="spm-basic-feature">
                                    <span class="title spm-label-title">
                                        <?php _e( 'Title', 'swifty-page-manager' ) ?>
                                    </span>
                                    <span class="input-text-wrap">
                                        <input name="spm_page_title_seo" type="text" class="spm-input spm-input-text" />
                                    </span>
                                </label>
                                <?php
                                    }
                                ?>
                                <div class="inline-edit-group spm-basic-feature">
                                    <label class="alignleft">
                                        <span class="title spm-label-title">
                                            <?php _e( 'Position', 'swifty-page-manager' ) ?>
                                        </span>
                                    </label>
                                    <label class="alignleft">
                                        <input name="add_mode" type="radio" value="after" class="spm-input-radio" />
                                        <span class="checkbox-title">
                                            <?php _e( 'After', 'swifty-page-manager' ) ?>
                                        </span>
                                    </label>
                                    <label class="alignleft">
                                        <input name="add_mode" type="radio" value="inside" class="spm-input-radio" />
                                        <span class="checkbox-title">
                                            <?php _e( 'As sub page of', 'swifty-page-manager' ) ?>
                                        </span>
                                    </label>
                                </div>
                                <?php
                                    if ( $this->is_swifty ) {
                                ?>
                                <div class="inline-edit-group spm-more">
                                    <input type="button" class="button-secondary alignright spm-button spm-do-button" data-spm-action="more" value="<?php _e( 'More', 'swifty-page-manager' ) ?>" />
                                </div>
                                <label class="spm-advanced-feature">
                                    <span class="title spm-label-title">
                                        <?php _e( 'Url', 'swifty-page-manager' ) ?>
                                    </span>
                                    <span class="input-text-wrap">
                                        <input name="post_name" type="text" class="spm-input spm-input-text" />
                                        <input name="spm_is_custom_url" type="hidden" value="0" />
                                    </span>
                                </label>
                                <div class="inline-edit-group spm-advanced-feature">
                                    <label class="alignleft">
                                        <span class="title spm-label-title">
                                            <?php _e( 'In menu', 'swifty-page-manager' ) ?>
                                        </span>
                                    </label>
                                    <label class="alignleft">
                                        <input name="spm_show_in_menu" type="radio" value="show" class="spm-input-radio" />
                                        <span class="checkbox-title">
                                            <?php _e( 'Show', 'swifty-page-manager' ) ?>
                                        </span>
                                    </label>
                                    <label class="alignleft">
                                        <input name="spm_show_in_menu" type="radio" value="hide" class="spm-input-radio" />
                                        <span class="checkbox-title">
                                            <?php _e( 'Hide', 'swifty-page-manager' ) ?>
                                        </span>
                                    </label>
                                </div>
                                <?php
                                    }
                                ?>
                                <div class="inline-edit-group spm-advanced-feature">
                                    <label class="alignleft">
                                        <span class="title spm-label-title">
                                            <?php _e( 'Status', 'swifty-page-manager' ) ?>
                                        </span>
                                    </label>
                                    <label class="alignleft">
                                        <input name="post_status" type="radio" value="draft" class="spm-input-radio" />
                                        <span class="checkbox-title">
                                            <?php _e( 'Draft', 'swifty-page-manager' ) ?>
                                        </span>
                                    </label>
                                    <label class="alignleft">
                                        <input name="post_status" type="radio" value="publish" class="spm-input-radio" />
                                        <span class="checkbox-title">
                                            <?php _e( 'Live', 'swifty-page-manager' ) ?>
                                        </span>
                                    </label>
                                </div>
                                <label class="spm-advanced-feature">
                                    <span class="title spm-label-title">
                                        <?php _e( 'Template', 'swifty-page-manager' ) ?>
                                    </span>
                                    <select name="page_template" />
                                        <option value="default"><?php _e( 'Default template', 'swifty-page-manager' ) ?></option>
                                         <?php
                                            $templates = wp_get_theme()->get_page_templates();

                                            foreach ( $templates as $template_name => $template_filename ) {
                                                echo '<option value="' . $template_name .'">' . $template_filename . '</option>';
                                            }
                                         ?>
                                    </select>
                                </label>
                                <?php
                                    if ( $this->is_swifty ) {
                                ?>
                                <div class="inline-edit-group spm-advanced-feature">
                                    <label class="alignleft">
                                        <span class="title spm-label-title">
                                            <?php _e( 'Header', 'swifty-page-manager' ) ?>
                                        </span>
                                    </label>
                                    <label class="alignleft">
                                        <input name="spm_header_visibility" type="radio" value="show" class="spm-input-radio" />
                                        <span class="checkbox-title">
                                            <?php _e( 'Show', 'swifty-page-manager' ) ?>
                                        </span>
                                    </label>
                                    <label class="alignleft">
                                        <input name="spm_header_visibility" type="radio" value="hide" class="spm-input-radio" />
                                        <span class="checkbox-title">
                                            <?php _e( 'Hide', 'swifty-page-manager' ) ?>
                                        </span>
                                    </label>
                                </div>
                                <div class="inline-edit-group spm-advanced-feature">
                                    <label class="alignleft">
                                        <span class="title spm-label-title">
                                            <?php _e( 'Sidebar', 'swifty-page-manager' ) ?>
                                        </span>
                                    </label>
                                    <label class="alignleft">
                                        <input name="spm_sidebar_visibility" type="radio" value="left" class="spm-input-radio" />
                                        <span class="checkbox-title">
                                            <?php _e( 'Left', 'swifty-page-manager' ) ?>
                                        </span>
                                    </label>
                                    <label class="alignleft">
                                        <input name="spm_sidebar_visibility" type="radio" value="right" class="spm-input-radio" />
                                        <span class="checkbox-title">
                                            <?php _e( 'Right', 'swifty-page-manager' ) ?>
                                        </span>
                                    </label>
                                    <label class="alignleft">
                                        <input name="spm_sidebar_visibility" type="radio" value="hide" class="spm-input-radio" />
                                        <span class="checkbox-title">
                                            <?php _e( 'Hide', 'swifty-page-manager' ) ?>
                                        </span>
                                    </label>
                                    </span>
                                </div>
                                <div class="inline-edit-group spm-less">
                                    <input type="button" class="button-secondary alignright spm-button spm-do-button" data-spm-action="less" value="<?php _e( 'Less', 'swifty-page-manager' ) ?>" />
                                </div>
                                <?php
                                    }
                                ?>
                            </div>
                        </fieldset>
                        <fieldset class="inline-edit-col-right">
                            <div class="inline-edit-col">
                                <div class="inline-edit-group spm-buttons-confirm">
                                    <input type="button" class="button-secondary alignright spm-button spm-do-button alignright" data-spm-action="cancel" value="<?php _e( 'Cancel', 'swifty-page-manager' ) ?>" />
                                    <br class="clear">
                                    <input type="button" class="button-primary alignright spm-button spm-do-button alignright" data-spm-action="save" value="<?php _e( 'Save', 'swifty-page-manager' ) ?>" />
                                </div>
                            </div>
                        </fieldset>
                    </td>
                </tr>
            </tbody>
        </table>
    </form>
</span>
<?php wp_nonce_field( 'inlineeditnonce', '_inline_edit' ) ?>
<?php
    }

    if ( empty( $jsonData ) ) {
        echo '<div class="updated fade below-h2"><p>' . __( 'No pages found.', 'swifty-page-manager' ) . '</p></div>';
?>
    <span class="button button-primary spm-button spm-do-button spm-no-posts-add" data-spm-action="add" title="<?php _e( 'Add page', 'swifty-page-manager' ) ?>">
        <span class="dashicons spm-icon dashicons-plus"></span>
    </span>
<?php
    }
?>
</div>