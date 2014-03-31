<?php
/**
 * Variables that need to be set:
 * @var SwiftyPages $this
 */
$post_type = $this->cms_tpv_get_selected_post_type();
$post_type_object = get_post_type_object( $post_type );

if ( 'post' != $post_type )
{
    $post_new_file = "post-new.php?post_type=$post_type";
}
else
{
    $post_new_file = 'post-new.php';
}

?>
<div class="wrap">
    <?php echo get_screen_icon(); ?>
    <h2><?php

        $page_title = sprintf( _x( '%1$s Tree View', "headline of page with tree", 'swiftypages' ), $post_type_object->labels->name );
        echo $page_title;

        // Add "add new" link the same way as the regular post page has
        if ( current_user_can( $post_type_object->cap->create_posts ) )
        {
            echo ' <a href="' . esc_url( $post_new_file ) . '" class="add-new-h2">' . esc_html( $post_type_object->labels->add_new ) . '</a>';
        }

        ?></h2>

    <?php
    $this->cms_tpv_print_common_tree_stuff( $post_type );
    ?>

</div>