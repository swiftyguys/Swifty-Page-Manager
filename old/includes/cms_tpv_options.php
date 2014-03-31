<?php
/**
 * Variables that need to be set:
 * @var SwiftyPages $this
 */
?>
<div class="wrap">

    <h2><?php echo CMS_TPV_NAME ?> <?php _e( "settings", 'swiftypages' ) ?></h2>

    <form method="post" action="options.php" class="cmtpv_options_form">

        <?php wp_nonce_field( 'update-options' ); ?>

        <h3><?php _e( "Select where to show a tree for pages and custom post types", 'swiftypages' ) ?></h3>

        <table class="form-table">

            <tbody>

            <?php

            $options = $this->cms_tpv_get_options();

            $post_types = get_post_types( array(
                                              "show_ui" => true
                                          ), "objects" );


            $arr_page_options = array();
            foreach ( $post_types as $one_post_type )
            {

                if ( $this->cms_tpv_post_type_is_ignored( $one_post_type->name ) )
                {
                    continue;
                }

                $name = $one_post_type->name;

                if ( $name === "post" )
                {
                    // no support for pages. you could show them.. but since we can't reorder them there is not idea to show them.. or..?
                    // 14 jul 2011: ok, let's enable it for posts too. some people says it useful
                    // http://wordpress.org/support/topic/this-plugin-should-work-also-on-posts
                    // continue;
                }
                else
                {
                    if ( $name === "attachment" )
                    {
                        // No support for media/attachment
                        continue;
                    }
                }

                $arr_page_options[ ] = "post-type-dashboard-$name";
                $arr_page_options[ ] = "post-type-menu-$name";
                $arr_page_options[ ] = "post-type-postsoverview-$name";

                echo "<tr>";

                echo "<th scope='row'>";
                echo "<p>" . $one_post_type->label . "</p>";
                echo "</th>";

                echo "<td>";

                echo "<p>";

                $checked = ( in_array( $name, $options[ "dashboard" ] ) ) ? " checked='checked' " : "";
                echo "<input $checked type='checkbox' name='post-type-dashboard[]' value='$name' id='post-type-dashboard-$name' /> <label for='post-type-dashboard-$name'>" . __( "On dashboard", 'swiftypages' ) . "</label>";

                echo "<br />";
                $checked = ( in_array( $name, $options[ "menu" ] ) ) ? " checked='checked' " : "";
                echo "<input $checked type='checkbox' name='post-type-menu[]' value='$name' id='post-type-menu-$name' /> <label for='post-type-menu-$name'>" . __( "In menu", 'swiftypages' ) . "</label>";

                echo "<br />";
                $checked = ( in_array( $name, $options[ "postsoverview" ] ) ) ? " checked='checked' " : "";
                echo "<input $checked type='checkbox' name='post-type-postsoverview[]' value='$name' id='post-type-postsoverview-$name' /> <label for='post-type-postsoverview-$name'>" . __( "On post overview screen", 'swiftypages' ) . "</label>";

                echo "</p>";

                echo "</td>";

                echo "</tr>";

            }

            ?>
            </tbody>
        </table>

        <input type="hidden" name="action" value="update"/>
        <input type="hidden" name="cms_tpv_action" value="save_settings"/>
        <?php // TODO: why is the line below needed? gives deprecated errors ?>
        <input type="hidden" name="page_options" value="<?php echo join( $arr_page_options, "," ) ?>"/>

        <p class="submit">
            <input type="submit" class="button-primary" value="<?php _e( 'Save Changes', 'swiftypages' ) ?>"/>
        </p>

    </form>

</div>