<?php

include 'lib/ss_story.php';

////////////////////////////////////////

class ThisStory extends SSStory {
    protected $pluginName = 'Swifty Page Manager';

    function TakeAction() {
        parent::TakeAction(); // Must be called first!

        $this->wordpress->Login();
        $this->wordpress->DeleteAllPages();
        $this->wordpress->EmptyTrash();
//        $this->CheckIfXPagesExist( 0 );
//        $this->CheckAddButtonVisibleWhenNoPagesExist();
        $this->wordpress->CreateXDraftPages( 2 );
//        $this->CheckIfXPagesExist( 4 );
//        $this->CheckPluginRunning();
        $this->CreateNewPageAftersLastPage();
    }

    ////////////////////////////////////////

    function CheckPluginRunning() {
        $this->EchoMsg( "Check plugin running: " . $this->pluginName );

        $this->wordpress->OpenAdminSubMenu( 'pages', $this->pluginName );

        $txt = $this->getText()->fromHeadingWithText( $this->pluginName );
        $this->assertsString( $txt )->equals( $this->pluginName );
    }

    ////////////////////////////////////////

    function CheckAddButtonVisibleWhenNoPagesExist()
    {
        $this->EchoMsg( 'Check add button visible when no pages exist' );

        $this->wordpress->OpenAdminSubMenu( 'pages', $this->pluginName );
        $this->st->usingTimer()->wait( 2, 'Wait for the add button to be visible' );
        $element = $this->FindElementByXpathMustExist( 'descendant::div[' . $this->ContainingClass( "spm-no-pages" ) . ']//span[' . $this->ContainingClass( "dashicons-plus" ) . ']' );
    }

    ////////////////////////////////////////

    function CheckIfXPagesExist( $total = 1 )
    {
        $this->EchoMsg( 'Check if x pages exist' );

        $this->wordpress->OpenAdminSubMenu( 'pages', $this->pluginName );
        $this->st->usingTimer()->wait( 3, 'Wait for the page tree to be visible' );
        $elements = $this->FindElementsByXpath( 'descendant::div[' . $this->ContainingClass( "spm-tree-container" ) . ']/ul/li' );
        $this->st->assertsInteger( count( $elements ) )->equals( $total );
    }

    ////////////////////////////////////////

    function CreateNewPageAfterLastPage()
    {
        $this->EchoMsg( 'Create new page with the plugin' . $this->wordpress->strings[ 's_spm_pages_save' ] );

        $this->wordpress->OpenAdminSubMenu( 'pages', $this->pluginName );
        $this->st->usingTimer()->wait( 3, 'Wait for the page tree to be visible' );

        $allLiBefore = $this->st->fromBrowser()->getElementsByClass( 'jstree-leaf' );
        $this->st->assertsInteger( count( $allLiBefore ) )->equals( 2 );

        $lastLi = $this->st->fromBrowser()->getElementsByClass( 'jstree-last' );
        $this->st->assertsInteger( count( $lastLi ) )->equals( 1 );
        $lastLiId = $lastLi[0]->attribute( 'id' );   // Example: spm-id-1238
        $lastLiXPath = 'descendant::li[@id="' . $lastLiId . '"]';

        // Selects the last page tree element.
        $pageTreeElement = $this->FindElementsByXpath( $lastLiXPath . '/a[' . $this->ContainingClass( "spm-page-tree-element" ) . ']' );
        $this->st->assertsInteger( count( $pageTreeElement ) )->equals( 1 );
        $pageTreeElement[0]->click();

        // Find the page create button, check if there's only one, click it.
        $pageCreateButton = $this->FindElementsByXpath( $lastLiXPath . '//span[@data-spm-action="add"]' );
        $this->st->assertsInteger( count( $pageCreateButton ) )->equals( 1 );
        $pageCreateButton[0]->click();

        $this->st->usingBrowser()->type( 'SPM Pagina 1' )->intoFieldWithName( "post_title" );

        // Find the page save button, check if there's only one, click it.
        $pageSaveButton = $this->FindElementsByXpath( $lastLiXPath . '//input[@data-spm-action="save"]' );
        $this->st->assertsInteger( count( $pageSaveButton ) )->equals( 1 );
        $pageSaveButton[0]->click();

        $this->st->usingTimer()->wait( 3, 'Wait for the new page to be saved' );

        $allLiAfter = $this->st->fromBrowser()->getElementsByClass( 'jstree-leaf' );
        $this->st->assertsInteger( count( $allLiAfter ) )->equals( 3 );
    }

    ////////////////////////////////////////

    function CreateNewPageAsSubOfTheLastPage()
    {
    }

    ////////////////////////////////////////
}

////////////////////////////////////////

$GLOBALS['ssStory'] = new ThisStory();

////////////////////////////////////////
