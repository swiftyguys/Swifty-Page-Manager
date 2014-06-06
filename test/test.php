<?php

include 'lib/ss_story.php';

////////////////////////////////////////

class ThisStory extends SSStory {
    protected $pluginName = 'Swifty Page Manager';

    function TakeAction() {
        parent::TakeAction(); // Must be called first!

        $this->wordpress->Login();
        $this->Probe( 'SPM.CheckRunning', Array( "plugin_name" => $this->pluginName ) );
        $this->Probe( 'WP.DeleteAllPages', Array( 'plugin_name' => $this->pluginName ) );
        $this->Probe( 'WP.EmptyTrash', Array( 'plugin_name' => $this->pluginName ) );
        $this->Probe( 'SPM.NoPagesExist', Array( 'plugin_name' => $this->pluginName ) );
        $this->Probe( 'WP.CreateXDraftPages', Array( 'plugin_name' => $this->pluginName, 'x_pages' => 2 ) );
        $this->Probe( 'SPM.XPagesExist', Array( 'plugin_name' => $this->pluginName, 'x_pages' => 2 ) );
        $this->Probe( 'SPM.CreatePageAfterLastPage', Array( 'plugin_name' => $this->pluginName ) );
        $this->Probe( 'SPM.XPagesExist', Array( 'plugin_name' => $this->pluginName, 'x_pages' => 3 ) );
    }

    ////////////////////////////////////////
}

////////////////////////////////////////

$GLOBALS['ssStory'] = new ThisStory();

////////////////////////////////////////
