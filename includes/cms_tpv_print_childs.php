<?php
/**
 * Variables that need to be set:
 * @var SwiftyPages $this
 * @var integer $pageID
 * @var string $view
 * @var array $arrOpenChilds
 * @var string $post_type
 */

$arrPages = $this->cms_tpv_get_pages( "parent=$pageID&view=$view&post_type=$post_type" );

if ( $arrPages )
{

    global $current_screen;
    $screen = convert_to_screen( "edit" );
#return;

// If this is set to null then quick/bul edit stops working on posts (not pages)
// If did set it to null sometime. Can't remember why...
// $screen->post_type = null;

    $post_type_object = get_post_type_object( $post_type );
    ob_start(); // some plugins, for example magic fields, return javascript and things here. we're not compatible with that, so just swallow any output
    $posts_columns = get_column_headers( $screen );
    ob_get_clean();

    unset( $posts_columns[ "cb" ], $posts_columns[ "title" ], $posts_columns[ "author" ], $posts_columns[ "categories" ], $posts_columns[ "tags" ], $posts_columns[ "date" ] );

    global $post;

// Translated post statuses
    $post_statuses = get_post_statuses();


    ?>[<?php
    for ( $i = 0, $pagesCount = sizeof( $arrPages ); $i < $pagesCount; $i++ )
    {

        $onePage       = $arrPages[ $i ];
        $tmpPost       = $post;
        $post          = $onePage;
        $page_id       = $onePage->ID;
        $arrChildPages = null;

        $editLink    = get_edit_post_link( $onePage->ID, 'notDisplay' );
        $content     = esc_html( $onePage->post_content );
        $content     = str_replace( array( "\n", "\r" ), "", $content );
        $hasChildren = false;

        // if viewing trash, don't get children. we watch them "flat" instead
        if ( $view == "trash" )
        {
        }
        else
        {
            $arrChildPages = $this->cms_tpv_get_pages( "parent={$onePage->ID}&view=$view&post_type=$post_type" );
        }

        if ( !empty( $arrChildPages ) )
        {
            $hasChildren = true;
        }
        // if no children, output no state
        $strState = '"state": "closed",';
        if ( !$hasChildren )
        {
            $strState = '';
        }

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

        $post_author = $this->cms_tpv_get_the_modified_author();
        if ( empty( $post_author ) )
        {
            $post_author = __( "Unknown user", 'swiftypages' );
        }

        $title = get_the_title( $onePage->ID ); // so hooks and stuff will do their work
        if ( empty( $title ) )
        {
            $title = __( "<Untitled page>", 'swiftypages' );
        }

        $arr_page_css_styles = array();
        $user_can_edit_page  = apply_filters( "cms_tree_page_view_post_can_edit", current_user_can( $post_type_object->cap->edit_post, $page_id ), $page_id );
        $user_can_add_inside = apply_filters( "cms_tree_page_view_post_user_can_add_inside", current_user_can( $post_type_object->cap->create_posts, $page_id ), $page_id );
        $user_can_add_after  = apply_filters( "cms_tree_page_view_post_user_can_add_after", current_user_can( $post_type_object->cap->create_posts, $page_id ), $page_id );

        if ( $user_can_edit_page )
        {
            $arr_page_css_styles[ ] = "cms_tpv_user_can_edit_page_yes";
        }
        else
        {
            $arr_page_css_styles[ ] = "cms_tpv_user_can_edit_page_no";
        }

        if ( $user_can_add_inside )
        {
            $arr_page_css_styles[ ] = "cms_tpv_user_can_add_page_inside_yes";
        }
        else
        {
            $arr_page_css_styles[ ] = "cms_tpv_user_can_add_page_inside_no";
        }

        if ( $user_can_add_after )
        {
            $arr_page_css_styles[ ] = "cms_tpv_user_can_add_page_after_yes";
        }
        else
        {
            $arr_page_css_styles[ ] = "cms_tpv_user_can_add_page_after_no";
        }

        $page_css = join( " ", $arr_page_css_styles );

        // fetch columns
        $str_columns = "";
        foreach ( $posts_columns as $column_name => $column_display_name )
        {
            $col_name = $column_display_name;
            if ( $column_name == "comments" )
            {
                $col_name = __( "Comments" );
            }
            $str_columns .= "<dt>$col_name</dt>";
            $str_columns .= "<dd>";
            if ( $column_name == "comments" )
            {
                $str_columns .= '<div class="post-com-count-wrapper">';
                $left            = get_pending_comments_num( $onePage->ID );
                $pending_phrase  = sprintf( __( '%s pending' ), number_format( $left ) );
                $pending_phrase2 = "";
                if ( $left )
                {
                    $pending_phrase2 = " + $left " . __( "pending" );
                }

                if ( $left )
                {
                    $str_columns .= '<strong>';
                }
                ob_start();
                comments_number( "<a href='edit-comments.php?p=$page_id' title='$pending_phrase'><span>" . _x( '0', 'comment count' ) . "$pending_phrase2</span></a>", "<a href='edit-comments.php?p=$page_id' title='$pending_phrase' class=''><span class=''>" . _x( '1', 'comment count' ) . "$pending_phrase2</span></a>", "<a href='edit-comments.php?p=$page_id' title='$pending_phrase' class=''><span class=''>" . _x( '%', 'comment count' ) . "$pending_phrase2</span></a>" );
                $str_columns .= ob_get_clean();
                if ( $left )
                {
                    $str_columns .= '</strong>';
                }
                $str_columns .= "</div>";
            }
            else
            {
                ob_start();
                do_action( 'manage_pages_custom_column', $column_name, $onePage->ID );
                $str_columns .= ob_get_clean();
            }
            $str_columns .= "</dd>";
        }

        if ( $str_columns )
        {
            $str_columns = "<dl>$str_columns</dl>";
        }
        $str_columns = json_encode( $str_columns );
        ?>
        {
        "data": {
        "title": <?php echo json_encode( $title ) ?>,
        "attr": {
        "href": "<?php echo $editLink ?>"
        <?php /* , "xid": "cms-tpv-<?php echo $onePage->ID ?>" */ ?>
        }<?php /*,
					"xicon": "<?php echo CMS_TPV_URL . "images/page_white_text.png" ?>"*/
        ?>
        },
        "attr": {
        <?php /* "xhref": "<?php echo $editLink ?>", */ ?>
        "id": "cms-tpv-<?php echo $onePage->ID ?>",
        <?php /* "xtitle": "<?php _e("Click to edit. Drag to move.", 'swiftypages') ?>", */ ?>
        "class": "<?php echo $page_css ?>"
        },
        <?php echo $strState ?>
        "metadata": {
        "id": "cms-tpv-<?php echo $onePage->ID ?>",
        "post_id": "<?php echo $onePage->ID ?>",
        "post_type": "<?php echo $onePage->post_type ?>",
        "post_status": "<?php echo $onePage->post_status ?>",
        "post_status_translated": "<?php echo isset( $post_statuses[ $onePage->post_status ] ) ? $post_statuses[ $onePage->post_status ] : $onePage->post_status ?>",
        "rel": "<?php echo $rel ?>",
        "childCount": <?php echo ( !empty( $arrChildPages ) ) ? sizeof( $arrChildPages ) : 0; ?>,
        "permalink": "<?php echo htmlspecialchars_decode( get_permalink( $onePage->ID ) ) ?>",
        "editlink": "<?php echo htmlspecialchars_decode( $editLink ) ?>",
        "modified_time": "<?php echo $post_modified_time ?>",
        "modified_author": "<?php echo $post_author ?>",
        "columns": <?php echo $str_columns ?>,
        "user_can_edit_page": "<?php echo (int) $user_can_edit_page ?>",
        "user_can_add_page_inside": "<?php echo (int) $user_can_add_inside ?>",
        "user_can_add_page_after": "<?php echo (int) $user_can_add_after ?>",
        "post_title": <?php echo json_encode( $title ) ?>
        }
        <?php
        // if id is in $arrOpenChilds then also output children on this one
        // TODO: if only "a few" (< 100?) pages then load all, but keep closed, so we don't have to do the ajax thingie
        if ( $hasChildren && isset( $arrOpenChilds ) && in_array( $onePage->ID, $arrOpenChilds ) )
        {
            ?>, "children": <?php
            $this->cms_tpv_print_childs( $onePage->ID, $view, $arrOpenChilds, $post_type );
            ?><?php
        }
        ?>

        }
        <?php
        // no comma for last page
        if ( $i < $pagesCount - 1 )
        {
            ?>,<?php
        }

        // return orgiginal post
        $post = $tmpPost;

    }
    ?>]<?php
}