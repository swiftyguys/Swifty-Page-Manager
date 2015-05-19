<?php
/**
 * Hide the wp-admin menus
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$currentScreen = get_current_screen();

?>
<script type="text/javascript">
    jQuery( function( $ ) {
<?php
    if ( 'edit' === $currentScreen->base &&
         'page' === $currentScreen->post_type &&
         'trash' === get_query_var( 'post_status' )
    ) {
?>
        $( '#wpbody' ).prepend(
            '<div class="spm_back_button spm_button" style="float:left; margin-top:20px;">' +
                '<i class="fa fa-caret-left"></i>' +
            '</div>'
        );

        $( '.spm_back_button' ).on( 'click', function() {
            // in worst case fallback to main page
            var backLocation = window.location.protocol + '//' +
                               window.location.hostname + ':' +
                               window.location.port + '/';

            if ( typeof Storage !== 'undefined' ) {
                if ( sessionStorage.spm_location ) {
                    backLocation = sessionStorage.spm_location;
                }
            }

            window.location = backLocation;
        });

        $( '#wpbody-content' ).css( {
            'display': 'table',
            'float': 'none',
            'width': 'auto'
        });

        $( 'a.add-new-h2' ).hide();
        $( 'ul.subsubsub' ).hide();
        $( '#screen-meta' ).hide();
        $( '#screen-meta-links' ).hide();
<?php
}
?>
        $( '#wpadminbar' ).hide();
        $( '#adminmenuback' ).hide();
        $( '#adminmenuwrap' ).hide();
        $( '#wpcontent' ).css( 'margin-left', '0px' );
        //$('.wp-toolbar').css('padding-top', '0px');
        $( '.updated' ).hide();
        $( '.error' ).hide();
    } );
</script>

<?php
    if ( 'edit' === $currentScreen->base &&
         'page' === $currentScreen->post_type &&
         'trash' === get_query_var( 'post_status' )
    ) {
?>
<link href="//netdna.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css" rel="stylesheet">
<style>
.fa {
    display: inline-block;
    font-family: FontAwesome;
    font-style: normal;
    font-weight: normal;
    line-height: 1;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}
</style>
<?php
}
?>
<style>
html.wp-toolbar {
    padding-top: 0px !important;
}
</style>