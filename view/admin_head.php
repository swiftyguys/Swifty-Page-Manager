<?php
/**
 * Variables that need to be set:
 * @var Swifty Page Manager $this
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
        $.data( document, 'spm_json_data', {} );
        $.data( document, 'spm_status', '<?php echo $this->getPostStatus(); ?>' );
    } );
</script>