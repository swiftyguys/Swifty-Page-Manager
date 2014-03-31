<?php
/**
 * Variables that need to be set:
 * @var SwiftyPages $this
 */
?>
<style>
    /* TODO: Do we still need this? Does it need to be here? */
    .tablenav.top {
        float: right;
    }

    .view-switch {
        visibility: hidden;
    }
</style>

<script type="text/javascript">
    jQuery( function ( $ ) {
        $.data( document, 'swiftypages_jsondata', {} );
        $.data( document, 'swiftypages_view', '<?php echo $this->_view; ?>' );
    } );
</script>