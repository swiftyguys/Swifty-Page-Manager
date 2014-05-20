<?php

include 'lib/ss_story.php';

////////////////////////////////////////

class ThisStory extends SSStory {

    function TakeAction() {
        parent::TakeAction(); // Must be called first!

        $this->wordpress->Login();

        $this->CheckPluginRunning( 'Swifty Page Manager' );
    }

    ////////////////////////////////////////

    function CheckPluginRunning( $pluginName ) {
        $this->EchoMsg( "Check plugin running: " . $pluginName );

        $this->wordpress->OpenAdminSubMenu( 'pages', $pluginName );

        $txt = $this->getText()->fromHeadingWithText( $pluginName );
        $this->assertsString( $txt )->equals( $pluginName );
    }

}

////////////////////////////////////////

$GLOBALS['ssStory'] = new ThisStory();

////////////////////////////////////////
