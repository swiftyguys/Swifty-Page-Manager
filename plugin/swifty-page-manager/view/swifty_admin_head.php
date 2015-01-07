<?php
/**
 * Hide the wp-admin menus
 */
?>
<script type="text/javascript">
    jQuery( function ( $ ) {
        $('#wpadminbar').hide();
        $('#adminmenuback').hide();
        $('#adminmenuwrap').hide();
        $('#wpcontent').css('margin-left', '0px');
        //$('.wp-toolbar').css('padding-top', '0px');
        $('.updated').hide();
    } );
</script>
<style>
html.wp-toolbar {
    padding-top: 0px !important;
}
</style>
