<?php
/**
 * Variables that need to be set:
 * @var SwiftyPageManager $this
 */
$post_type_object = get_post_type_object( $this->_post_type );
$post_new_file = "post-new.php?post_type=".$this->_post_type;

?>
<div class="wrap">
    <h2><?php _ex( 'Swifty Page Manager', 'headline of page with tree', 'swifty-page-manager' ); ?></h2>
    <?php

    $get_pages_args = array( "post_type" => $this->_post_type );

    $status_data_attributes = array( 'all' => '', 'publish' => '', 'trash' => '' );

    // Calculate post counts
    $post_count         = wp_count_posts( $this->_post_type );
    $post_count_all     = $post_count->publish + $post_count->future + $post_count->draft +
                          $post_count->pending + $post_count->private;
    $post_count_publish = $post_count->publish;
    $post_count_trash   = $post_count->trash;

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
            spmJsonData[ <?php echo json_encode( $this->_post_type ); ?> ] = <?php echo json_encode( $jsonData ); ?>;
        } );
    </script>

    <div class="spm_wrapper">
        <input type="hidden" name="spm_meta_post_type" value="<?php echo esc_attr( $this->_post_type ); ?>" />

        <ul class="spm-subsubsub spm-subsubsub-select-view">
            <li>
                <a class="cms_spm_status_any
                          <?php echo esc_attr( ('any'==$this->getPostStatus()) ? 'current' : '' ); ?>"
                   href="<?php echo esc_attr( add_query_arg( 'status', 'any', $this->getPluginUrl() ) ); ?>"
                    <?php echo esc_html( $status_data_attributes['all'] ); ?>>
                    <?php _e('All', 'swifty-page-manager'); ?>
                    <span class="count">(<?php esc_html_e( $post_count_all ); ?>)</span>
                </a> |
            </li>
            <li>
                <a class="cms_spm_status_publish
                          <?php echo esc_attr( ('publish'==$this->getPostStatus()) ? 'current' : '' ); ?>"
                   href="<?php echo esc_attr( add_query_arg( 'status', 'publish', $this->getPluginUrl() ) ); ?>"
                    <?php echo $status_data_attributes['publish']; ?>>
                    <?php _e('Published', 'swifty-page-manager'); ?>
                    <span class="count">(<?php esc_html_e( $post_count_publish ); ?>)</span>
                </a> |
            </li>

            <?php if ( $post_count_trash ): ?>
            <li>
                <a class="cms_spm_status_trash"
                   href="<?php esc_attr_e( admin_url() . 'edit.php?post_status=trash&post_type=page' ); ?>">
                    <?php _e('Trash', 'swifty-page-manager') ?>
                    <span class="count">(<?php esc_html_e( $post_count_trash ); ?>)</span>
                </a> |
            </li>
            <?php endif; ?>

            <li><a href="#" class="spm_open_all"><?php _e( 'Expand', 'swifty-page-manager' ); ?></a> |</li>
            <li><a href="#" class="spm_close_all"><?php _e( 'Collapse', 'swifty-page-manager' ); ?></a></li>

        </ul>

        <div class="spm_working">
            <?php _e( 'Loading...', 'swifty-page-manager' ); ?>
        </div>

        <div class="spm-message updated below-h2 hidden">
            <p>Message goes here.</p>
        </div>

        <div class="spm-tree-container tree-default">
            <?php _e( 'Loading tree', 'swifty-page-manager' ); ?>
        </div>

        <div style="clear: both;"></div>
    </div>

<!-- SwiftySite template page buttons-->
<span class="spm-page-actions-tmpl __TMPL__ spm-hidden">
    <span class="button button-primary spm-button spm-page-button" data-spm-action="add"
          title="<?php esc_attr_e( 'Add page', 'swifty-page-manager' ); ?>">
        <span class="dashicons spm-icon dashicons-plus"></span>
    </span>
    <span class="button button-primary spm-button spm-page-button" data-spm-action="settings"
          title="<?php esc_attr_e( 'Edit page', 'swifty-page-manager' ); ?>">
        <span class="dashicons spm-icon dashicons-admin-generic"></span>
    </span>
    <span class="button button-primary spm-button spm-page-button" data-spm-action="delete"
          title="<?php esc_attr_e( 'Delete page', 'swifty-page-manager' ); ?>">
        <span class="dashicons spm-icon dashicons-trash"></span>
    </span>
    <span class="button button-primary spm-button spm-page-button" data-spm-action="edit"
          title="<?php esc_attr_e( 'Edit page content', 'swifty-page-manager' ); ?>">
        <span class="dashicons spm-icon dashicons-edit"></span>
    </span>
    <span class="button button-primary spm-button spm-page-button" data-spm-action="view"
          title="<?php esc_attr_e( 'View page', 'swifty-page-manager' ); ?>">
        <span class="dashicons spm-icon dashicons-visibility"></span>
    </span>
    <span class="button button-primary spm-button spm-page-button" data-spm-action="publish"
          title="<?php esc_attr_e( 'Publish page', 'swifty-page-manager' ); ?>">
        <span class="dashicons spm-icon dashicons-upload"></span>
    </span>
</span>

<!-- SwiftySite template Delete -->
<span class="spm-container spm-page-delete-tmpl __TMPL__ spm-hidden">
    <form method="post" class="spm-form spm-page-delete-form">
        <input type="hidden" name="is_swifty" value="<?php echo esc_attr( ( $this->is_swifty ) ? '1' : '0' ); ?>" >
        <input type="hidden" name="wp_site_url" value="<?php echo get_site_url(); ?>" >
        <table class="spm-table wp-list-table widefat fixed pages">
            <tbody>
                <tr class="inline-edit-row inline-edit-row-page inline-edit-page quick-edit-row quick-edit-row-page
                          inline-edit-page inline-editor">
                    <td colspan="5" class="colspanchange">
                        <fieldset class="inline-edit-col-left">
                            <div class="inline-edit-col">
                                <div class="inline-edit-group">
                                    <span class="title">
                                        <?php _e( "Are you sure you want to delete this page with all it's content?",
                                                  'swifty-page-manager' ); ?>
                                    </span>
                                </div>
                            </div>
                        </fieldset>
                        <fieldset class="inline-edit-col-right">
                            <div class="inline-edit-group spm-buttons-confirm">
                                <input type="button" class="button-secondary alignright spm-button spm-do-button"
                                       data-spm-action="cancel" value="<?php esc_attr_e( 'Cancel', 'swifty-page-manager' ); ?>" />
                                <br class="clear">
                                <input type="button" class="button-primary alignright spm-button spm-do-button"
                                       data-spm-action="delete" value="<?php esc_attr_e( 'Delete', 'swifty-page-manager' ); ?>" />
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
        <input type="hidden" name="is_swifty" value="<?php echo esc_attr( ( $this->is_swifty ) ? '1' : '0' ); ?>" >
        <input type="hidden" name="wp_site_url" value="<?php echo get_site_url(); ?>" >
        <table class="spm-table wp-list-table widefat fixed pages">
            <tbody>
                <tr class="inline-edit-row inline-edit-row-page inline-edit-page quick-edit-row quick-edit-row-page
                           inline-edit-page inline-editor">
                    <td colspan="5" class="colspanchange">
                        <fieldset class="inline-edit-col-left">
                            <div class="inline-edit-col">
                                <div class="inline-edit-group">
                                    <span class="title">
                                        <?php esc_html_e( 'Are you sure you want to publish this page so it becomes visible to your visitors?',
                                                  'swifty-page-manager' ) ?>
                                    </span>
                                </div>
                            </div>
                        </fieldset>
                        <fieldset class="inline-edit-col-right">
                            <div class="inline-edit-group spm-buttons-confirm">
                                <input type="button" class="button-secondary alignright spm-button spm-do-button"
                                       data-spm-action="cancel"
                                       value="<?php esc_attr_e( 'Cancel', 'swifty-page-manager' ) ?>" />
                                <br class="clear">
                                <input type="button" class="button-primary alignright spm-button spm-do-button"
                                       data-spm-action="publish"
                                       value="<?php esc_attr_e( 'Publish', 'swifty-page-manager' ) ?>" />
                            </div>
                        </fieldset>
                    </td>
                </tr>
            </tbody>
        </table>
    </form>
</span>

<span class="spm-tooltip spm-title-tooltip spm-hidden">
The title of the page is the text that is shown in the tabs of the browser.<br>
It is also the short text that is shown in blue in the search engines such as Google.<br>
Therefore it is important that it contains the most important keywords of what this page is about.<br>
Put these at the beginning of the text.<br>
That is one of many things you can easily do to get a higher search engine ranking.<br>
The text should ideally be no longer than 70 characters.<br><br>
Example:<br><br>
Swifty Page Manager - easily create a page tree in Wordpress<br>

</span>

<span class="spm-tooltip spm-url-tooltip spm-hidden">
This is the link to your page. It is important that the name of the page is the main keyword of that page<br>
or the name of the product or service that you offer on that page. Preferably right behind the domain name.<br><br>
Example:<br><br>

<?php echo home_url(); ?>/<b>keyword</b><br>
<?php echo home_url(); ?>/<b>product-or-service</b><br>
</span>

<span class="spm-tooltip spm-status-tooltip spm-hidden">
Status <b>Live</b> means that everyone in the world can see that page and that it is shown in your site menu.<br>
Status <b>Draft</b> means that only you can see that page when you are logged in and it is not shown in the site menu.<br>
</span>

<!-- SwiftySite template Add/Edit -->
<span class="spm-container spm-page-add-edit-tmpl __TMPL__ spm-hidden">
    <form method="post" class="spm-form spm-page-add-edit-form">
        <input type="hidden" name="is_swifty" value="<?php echo esc_attr( ( $this->is_swifty ) ? '1' : '0' ); ?>" >
        <input type="hidden" name="wp_site_url" value="<?php echo get_site_url(); ?>" >
        <table class="spm-table wp-list-table widefat fixed pages">
            <tbody>
                <tr class="inline-edit-row inline-edit-row-page inline-edit-page quick-edit-row quick-edit-row-page
                           inline-edit-page inline-editor">
                    <td colspan="5" class="colspanchange">
                        <fieldset class="inline-edit-col-left">
                            <div class="inline-edit-col">
                                <label class="spm-basic-feature">
                                    <span class="title spm-label-title">
                                    <?php
                                        if ( $this->is_swifty ) {
                                    ?>
                                        <?php esc_html_e( 'Text in menu', 'swifty-page-manager' ) ?>
                                    <?php
                                        } else {
                                    ?>
                                        <?php esc_html_e( 'Title', 'swifty-page-manager' ) ?>
                                    <?php
                                        }
                                    ?>
                                    </span>
                                    <span class="input-text-wrap">
                                        <input name="post_title" type="text"
                                               class="spm-input spm-input-small spm-input-text" />
                                    </span>
                                </label>
                                <?php
                                    if ( $this->is_swifty ) {
                                ?>
                                <label class="spm-basic-feature">
                                    <span class="title spm-label-title">
                                        <?php _e( 'Title', 'swifty-page-manager' ) ?>  <span class="button-secondary spm-tooltip-button" rel="spm-title-tooltip"><i class="fa fa-question"></i></span>
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
                                            <?php esc_html_e( 'Position', 'swifty-page-manager' ) ?>
                                        </span>
                                    </label>
                                    <label class="alignleft">
                                        <input name="add_mode" type="radio" value="after" class="spm-input-radio" />
                                        <span class="checkbox-title">
                                            <?php esc_html_e( 'After', 'swifty-page-manager' ) ?>
                                        </span>
                                    </label>
                                    <label class="alignleft">
                                        <input name="add_mode" type="radio" value="inside" class="spm-input-radio" />
                                        <span class="checkbox-title">
                                            <?php esc_html_e( 'As sub page of', 'swifty-page-manager' ) ?>
                                        </span>
                                    </label>
                                </div>
                                <?php if ( $this->is_swifty ):  ?>
                                <div class="inline-edit-group spm-more">
                                    <input type="button" class="button-secondary alignright spm-button spm-do-button"
                                           data-spm-action="more"
                                           value="<?php esc_attr_e( 'More', 'swifty-page-manager' ) ?>" />
                                </div>
                                <label class="spm-advanced-feature">
                                    <span class="title spm-label-title">
                                        <?php _e( 'Url', 'swifty-page-manager' ) ?> <span class="button-secondary spm-tooltip-button" rel="spm-url-tooltip"><i class="fa fa-question"></i></span>
                                    </span>
                                    <span class="input-text-wrap">
                                        <input name="post_name" type="text" class="spm-input spm-input-text" />
                                        <input name="spm_is_custom_url" type="hidden" value="0" />
                                    </span>
                                </label>
                                <div class="inline-edit-group spm-advanced-feature">
                                    <label class="alignleft">
                                        <span class="title spm-label-title">
                                            <?php esc_html_e( 'In menu', 'swifty-page-manager' ) ?>
                                        </span>
                                    </label>
                                    <label class="alignleft">
                                        <input name="spm_show_in_menu" type="radio" value="show"
                                               class="spm-input-radio" />
                                        <span class="checkbox-title">
                                            <?php esc_html_e( 'Show', 'swifty-page-manager' ) ?>
                                        </span>
                                    </label>
                                    <label class="alignleft">
                                        <input name="spm_show_in_menu" type="radio" value="hide"
                                               class="spm-input-radio" />
                                        <span class="checkbox-title">
                                            <?php esc_html_e( 'Hide', 'swifty-page-manager' ) ?>
                                        </span>
                                    </label>
                                </div>
                                <?php endif; // if ( $this->is_swifty ) ?>
                                <div class="inline-edit-group spm-advanced-feature">
                                    <label class="alignleft">
                                        <span class="title spm-label-title">
                                            <?php _e( 'Status', 'swifty-page-manager' ) ?> <?php if ( $this->is_swifty ) { ?><span class="button-secondary spm-tooltip-button" rel="spm-status-tooltip"><i class="fa fa-question"></i></span><?php } ?>
                                        </span>
                                    </label>
                                    <label class="alignleft">
                                        <input name="post_status" type="radio" value="draft"
                                               class="spm-input-radio" />
                                        <span class="checkbox-title">
                                            <?php esc_html_e( 'Draft', 'swifty-page-manager' ) ?>
                                        </span>
                                    </label>
                                    <label class="alignleft">
                                        <input name="post_status" type="radio" value="publish"
                                               class="spm-input-radio" />
                                        <span class="checkbox-title">
                                            <?php esc_html_e( 'Live', 'swifty-page-manager' ) ?>
                                        </span>
                                    </label>
                                </div>
                                <label class="spm-advanced-feature">
                                    <span class="title spm-label-title">
                                        <?php esc_html_e( 'Template', 'swifty-page-manager' ) ?>
                                    </span>
                                    <select name="page_template" />
                                        <option value="default">
                                            <?php esc_html_e( 'Default template', 'swifty-page-manager' ) ?></option>
                                            <?php
                                                $templates = wp_get_theme()->get_page_templates();

                                                foreach ( $templates as $template_name => $template_filename ) {
                                                    echo '<option value="' . $template_name .'">' . $template_filename . '</option>';
                                                }
                                            ?>
                                    </select>
                                </label>
                                <?php if ( $this->is_swifty ): ?>
                                <div class="inline-edit-group spm-advanced-feature">
                                    <label class="alignleft">
                                        <span class="title spm-label-title">
                                            <?php esc_html_e( 'Header', 'swifty-page-manager' ) ?>
                                        </span>
                                    </label>
                                    <label class="alignleft">
                                        <input name="spm_header_visibility" type="radio" value="show"
                                               class="spm-input-radio" />
                                        <span class="checkbox-title">
                                            <?php esc_html_e( 'Show', 'swifty-page-manager' ) ?>
                                        </span>
                                    </label>
                                    <label class="alignleft">
                                        <input name="spm_header_visibility" type="radio" value="hide"
                                               class="spm-input-radio" />
                                        <span class="checkbox-title">
                                            <?php esc_html_e( 'Hide', 'swifty-page-manager' ) ?>
                                        </span>
                                    </label>
                                </div>
                                <div class="inline-edit-group spm-advanced-feature">
                                    <label class="alignleft">
                                        <span class="title spm-label-title">
                                            <?php esc_html_e( 'Sidebar', 'swifty-page-manager' ) ?>
                                        </span>
                                    </label>
                                    <label class="alignleft">
                                        <input name="spm_sidebar_visibility" type="radio" value="left"
                                               class="spm-input-radio" />
                                        <span class="checkbox-title">
                                            <?php esc_html_e( 'Left', 'swifty-page-manager' ) ?>
                                        </span>
                                    </label>
                                    <label class="alignleft">
                                        <input name="spm_sidebar_visibility" type="radio" value="right"
                                               class="spm-input-radio" />
                                        <span class="checkbox-title">
                                            <?php esc_html_e( 'Right', 'swifty-page-manager' ) ?>
                                        </span>
                                    </label>
                                    <label class="alignleft">
                                        <input name="spm_sidebar_visibility" type="radio" value="hide"
                                               class="spm-input-radio" />
                                        <span class="checkbox-title">
                                            <?php esc_html_e( 'Hide', 'swifty-page-manager' ) ?>
                                        </span>
                                    </label>
                                    </span>
                                </div>
                                <div class="inline-edit-group spm-less">
                                    <input type="button" class="button-secondary alignright spm-button spm-do-button"
                                           data-spm-action="less" value="<?php esc_attr_e( 'Less', 'swifty-page-manager' ) ?>" />
                                </div>
                                <?php endif; // if ( $this->is_swifty ) ?>
                            </div>
                        </fieldset>
                        <fieldset class="inline-edit-col-right">
                            <div class="inline-edit-col">
                                <div class="inline-edit-group spm-buttons-confirm">
                                    <input type="button"
                                           class="button-secondary alignright spm-button spm-do-button alignright"
                                           data-spm-action="cancel" value="<?php esc_attr_e( 'Cancel', 'swifty-page-manager' ) ?>" />
                                    <br class="clear">
                                    <input type="button"
                                           class="button-primary alignright spm-button spm-do-button alignright"
                                           data-spm-action="save" value="<?php esc_attr_e( 'Save', 'swifty-page-manager' ) ?>" />
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

    if ( empty( $jsonData ) ) {
        echo '<div class="updated fade below-h2"><p>' . __( 'No pages found.', 'swifty-page-manager' ) . '</p></div>';
?>
    <span class="button button-primary spm-button spm-do-button spm-no-posts-add" data-spm-action="add"
          title="<?php esc_attr_e( 'Add page', 'swifty-page-manager' ) ?>">
        <span class="dashicons spm-icon dashicons-plus"></span>
    </span>
<?php
    }
?>
</div>