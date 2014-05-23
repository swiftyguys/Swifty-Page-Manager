<?php

include 'lib/ss_story.php';

////////////////////////////////////////

class ThisStory extends SSStory {
    protected $pluginName = 'Swifty Page Manager';

    function TakeAction() {
        parent::TakeAction(); // Must be called first!

        $this->wordpress->Login();

//        $this->CheckPluginRunning();

        $this->CheckAddButtonVisibleWhenNoPagesExist();
    }

    ////////////////////////////////////////

    function CheckPluginRunning() {
        $this->EchoMsg( "Check plugin running: " . $this->pluginName );

        $this->wordpress->OpenAdminSubMenu( 'pages', $this->pluginName );

        $txt = $this->getText()->fromHeadingWithText( $this->pluginName );
        $this->assertsString( $txt )->equals( $this->pluginName );
    }

    ////////////////////////////////////////

    function CheckAddButtonVisibleWhenNoPagesExist() {
        $this->EchoMsg( "Check add button visible when no pages exist" );

        $this->wordpress->DeleteAllPages();
        $this->wordpress->OpenAdminSubMenu( 'pages', $this->pluginName );
        $this->st->usingTimer()->wait( 10, "Wait for the add button to be visible." );
        //$element = $this->FindElementByXpathMustExist( 'descendant::div[' . $this->ContainingClass( "spm-no-pages" ) . ']//span[' . $this->ContainingClass( "dashicons-plus" ) . ']' );
        $element = $this->FindElementByXpathMustExist( 'descendant::span[' . $this->ContainingClass( "spm-no-posts-add" ) . ']' );
        $element->click();
    }
}

////////////////////////////////////////

$GLOBALS['ssStory'] = new ThisStory();

////////////////////////////////////////
