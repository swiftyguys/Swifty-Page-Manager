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
                'add_mode'    => 'radio:after',
                'post_status' => 'radio:draft'
            ) )
        ) );

        $this->Probe( 'SPM.XPagesExist', array(
            'plugin_name' => $this->pluginName,
            'x_pages' => 4
        ) );

        $this->Probe( 'SPM.PageExists', array(
            'plugin_name' => $this->pluginName,
            'post_title'  => 'SPM Page last',
            'x_pages'     => 1,
            'at_pos'      => 4
        ) );

        $this->Probe( 'SPM.PageExists', array(
            'plugin_name' => $this->pluginName,
            'post_title'  => 'SPM Page second',
            'x_pages'     => 1,
            'at_pos'      => 2
        ) );

        $this->Probe( 'SPM.EditPage', array(
            'plugin_name' => $this->pluginName,
            'post_title'  => 'SPM Page second',
            'values'      => json_encode( array(
                'post_title'  => 'text:Tweede SPM Pagina',
                'post_status' => 'radio:live'
            ) )
        ) );

        $this->Probe( 'SPM.PageExists', array(
            'plugin_name' => $this->pluginName,
            'post_title'  => 'Tweede SPM Pagina',
            'x_pages'     => 1,
            'at_pos'      => 2
        ) );
    }

    ////////////////////////////////////////
}

////////////////////////////////////////

$GLOBALS['ssStory'] = new ThisStory();

////////////////////////////////////////
