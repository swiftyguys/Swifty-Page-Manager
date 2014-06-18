<?php

include 'lib/ss_story.php';

////////////////////////////////////////

class ThisStory extends SSStory {
    protected $pluginName = 'Swifty Page Manager';

    function TakeAction() {
        parent::TakeAction(); // Must be called first!

        $this->wordpress->Login();

        $this->Probe( 'SPM.CheckRunning', array(
            "plugin_name" => $this->pluginName
        ) );

        $this->Probe( 'WP.DeleteAllPages', array(
            'plugin_name' => $this->pluginName
        ) );

        $this->Probe( 'WP.EmptyTrash', array(
            'plugin_name' => $this->pluginName
        ) );

        $this->Probe( 'SPM.NoPagesExist', array(
            'plugin_name' => $this->pluginName
        ) );

        $this->Probe( 'WP.CreateXDraftPages', array(
            'plugin_name' => $this->pluginName,
            'x_pages'     => 2
        ) );

        $this->Probe( 'SPM.XPagesExist', array(
            'plugin_name' => $this->pluginName,
            'x_pages'     => 2
        ) );

        $this->Probe( 'SPM.CreatePage', array(
            'plugin_name' => $this->pluginName,
            'page_nr'     => 'last',
            'values'      => json_encode( array(
                'post_title'  => 'text:SPM Page last',
                'add_mode'    => 'radio:after',
                'post_status' => 'radio:draft'
            ) )
        ) );

        $this->Probe( 'SPM.XPagesExist', array(
            'plugin_name' => $this->pluginName,
            'x_pages'     => 3
        ) );

        $this->Probe( 'SPM.CreatePage', array(
            'plugin_name' => $this->pluginName,
            'page_nr'     => 1,
            'values'      => json_encode( array(
                'post_title'  => 'text:SPM Page second',
                'add_mode'    => 'radio:after',   // radio:after | radio:inside
                'post_status' => 'radio:draft'    // radio:draft | radio:publish
            ) )
        ) );

        $this->Probe( 'SPM.XPagesExist', array(
            'plugin_name' => $this->pluginName,
            'x_pages'     => 4
        ) );

        $this->Probe( 'SPM.PageExists', array(
            'plugin_name' => $this->pluginName,
            'post_title'  => 'SPM Page last',
            'x_pages'     => 1,
            'at_pos'      => 4
        ) );

        $this->Probe( 'SPM.CheckPageStatus', array(
            'plugin_name' => $this->pluginName,
            'post_title'  => 'SPM Page last',
            'is_status'   => 'draft'   // draft | publish
        ) );

        $this->Probe( 'SPM.PageExists', array(
            'plugin_name' => $this->pluginName,
            'page_nr'     => 2,   // 'post_title'  => 'SPM Page second',
            'x_pages'     => 1,
            'at_pos'      => 2
        ) );

        $this->Probe( 'SPM.EditPage', array(
            'plugin_name' => $this->pluginName,
            'post_title'  => 'SPM Page second',
            'values'      => json_encode( array(
                'post_title'  => 'text:Tweede SPM Pagina',
                'post_status' => 'radio:publish'
            ) )
        ) );

        $this->Probe( 'SPM.PageExists', array(
            'plugin_name' => $this->pluginName,
            'post_title'  => 'Tweede SPM Pagina',
            'x_pages'     => 1,
            'at_pos'      => 2
        ) );

        $this->Probe( 'SPM.CheckPageStatus', array(
            'plugin_name' => $this->pluginName,
            'post_title'  => 'Tweede SPM Pagina',
            'is_status'   => 'publish'   // draft | publish
        ) );

        $this->Probe( 'SPM.DeletePage', array(
            'plugin_name' => $this->pluginName,
            'page_nr'     => 2
        ) );

        $this->Probe( 'SPM.XPagesExist', array(
            'plugin_name' => $this->pluginName,
            'x_pages'     => 3
        ) );

        $this->Probe( 'SPM.PublishPage', array(
            'plugin_name' => $this->pluginName,
            'page_nr'     => 'last'
        ) );

        $this->Probe( 'SPM.CheckPageStatus', array(
            'plugin_name' => $this->pluginName,
            'page_nr'     => 3,
            'is_status'   => 'publish'   // draft | publish
        ) );

        $this->Probe( 'SPM.CreatePage', array(
            'plugin_name' => $this->pluginName,
            'page_nr'     => 3,
            'values'      => json_encode( array(
                'post_title'  => 'text:SPM Page inside',
                'add_mode'    => 'radio:inside',   // radio:after | radio:inside
                'post_status' => 'radio:draft'    // radio:draft | radio:publish
            ) )
        ) );

//        $this->Probe( 'SPM.EditPageContent', array(
//            'plugin_name' => $this->pluginName,
//            'post_title'  => 'SPM Page inside',
//            'values'      => json_encode( array(
//                'post_title'  => 'text:Sub Menu Page'
//            ) )
//        ) );
//
//        $this->Probe( 'SPM.OpenSubMenu', array(
//            'plugin_name' => $this->pluginName,
//            'page_nr'     => 3
//        ) );
//
//        $this->Probe( 'SPM.PageExists', array(
//            'plugin_name' => $this->pluginName,
//            'post_title'  => 'Sub Menu Page',
//            'x_pages'     => 1
//        ) );
    }

    ////////////////////////////////////////
}

////////////////////////////////////////

$GLOBALS['ssStory'] = new ThisStory();

////////////////////////////////////////
