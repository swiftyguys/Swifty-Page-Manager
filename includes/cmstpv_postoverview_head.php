<?php if ( isset( $_GET[ "mode" ] ) && $_GET[ "mode" ] === "tree" ): ?>
    <style>
        /* hide and position WP things */
        /* TODO: move this to wp head so we don't have time to see wps own stuff */
        .subsubsub, .tablenav.bottom, .tablenav .actions, .wp-list-table, .search-box, .tablenav .tablenav-pages {
            display: none !important;
        }

        .tablenav.top {
            float: right;
        }

        .view-switch {
            visibility: hidden;
        }
    </style>
<?php
else:
    // post overview is enabled, but not active
    // make room for our icon directly so page does not look jerky while adding it
    ?>
    <style>
        .view-switch {
            padding-right: 23px;
        }
    </style>
<?php endif; ?>