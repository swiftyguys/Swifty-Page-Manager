<?php
/**
 * Variables that need to be set:
 * @var SwiftyPageManager $this
 */

if ( ! defined( 'ABSPATH' ) ) exit;

?>
<script type="text/javascript">
    jQuery( function ( $ ) {
        $.data( document, 'spm_status', <?php echo json_encode( $this->get_post_status() ); ?> );
    } );
</script>