<?php
/**
 * Variables that need to be set:
 * @var SwiftyPages $this
 */
if ( !$this->cms_tpv_is_one_of_our_pages() )
{
    return;
}

$this->cms_tpv_setup_postsoverview();

global $cms_tpv_view;

if ( isset( $_GET[ "cms_tpv_view" ] ) )
{
    $cms_tpv_view = htmlspecialchars( $_GET[ "cms_tpv_view" ] );
}
else
{
    $cms_tpv_view = "all";
}

?>
<script type="text/javascript">
    /* <![CDATA[ */
    var CMS_TPV_URL = "<?php echo CMS_TPV_URL ?>";
    var CMS_TPV_AJAXURL = "?action=cms_tpv_get_childs&view=";
    var CMS_TPV_VIEW = "<?php echo $cms_tpv_view ?>";
    var cms_tpv_jsondata = {};
    /* ]]> */
</script>

<!--[if IE 6]>
<style>
    .cms_tree_view_search_form {
        display: none !important;
    }

    .cms_tpv_dashboard_widget .subsubsub li {
    }
</style>
<![endif]-->