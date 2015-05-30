<?php

use DataSift\Storyplayer\PlayerLib\StoryTeller;
use DataSift\Storyplayer\Prose\E5xx_ActionFailed;

include '../plugin/swifty-page-manager/lib/swifty_plugin/php/probe/ss_story.php';

////////////////////////////////////////

class ThisStory extends SSStory {
//class ThisStory extends SSCeption {
    protected $pluginName = 'Swifty Page Manager';

    function TakeAction() {
        parent::TakeAction();   // Must be called first!

        $this->SetPluginName( $this->pluginName );

        ////////////////////////////////////////

        $this->RegisterTry(
            'I am on SPM main page',
            function() {
                if( ! $this->GetTryFlag( 'on_spm_main' ) ) {
                    $this->DoTry( 'I click on WP admin -> Pages -> Swifty Page Manager', '' );
                    $this->SetTryFlag( 'on_spm_main', true );
                }
            }
        );

        ////////////////////////////////////////

        $this->RegisterTry(
            'I am in Swifty mode',
            function() {
                if( $this->GetTryFlag( 'ss_mode' ) !== 'ss_force' ) {
                    $this->SetCookie( 'ss_mode', 'ss_force' );
                    $this->SetTryFlag( 'ss_mode', 'ss_force' );
                    $this->SetTryFlag( 'on_spm_main', false );
                }
            }
        );

        ////////////////////////////////////////

        $this->RegisterTry(
            'I am in WP mode',
            function() {
                if( $this->GetTryFlag( 'ss_mode' ) !== 'wp' ) {
                    $this->SetCookie( 'ss_mode', 'wp' );
                    $this->SetTryFlag( 'ss_mode', 'wp' );
                    $this->SetTryFlag( 'on_spm_main', false );
                }
            }
        );

        ////////////////////////////////////////

        $this->RunProbeDescription();
    }
}

////////////////////////////////////////

$GLOBALS['ssStory'] = new ThisStory();
$story = $GLOBALS['ssStory']->SetupStory( 'Swifty Page Manager' );
