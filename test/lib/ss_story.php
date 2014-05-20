<?php

use DataSift\Storyplayer\PlayerLib\StoryTeller;

include 'wordpress.php';

class SSStory {

    public $data;

    ////////////////////////////////////////

    function TakeAction() { // Must be called first in a derived method
        $this->PrepareForTest();
    }

    ////////////////////////////////////////

    function TestSetup() {
        $st = $this->st;

        // load the test settings; any settings in private will overrule the same settings in public
        $settingsPublic = json_decode( file_get_contents( dirname(__FILE__) . '/../settings_public.json' ), true );
        $settingsPublic[ $st->getParams()[ 'settings' ] ] = ( isset( $settingsPublic[ $st->getParams()[ 'settings' ] ] ) && is_array( $settingsPublic[ $st->getParams()[ 'settings' ] ] )) ? $settingsPublic[ $st->getParams()[ 'settings' ] ] : array(); // initialize if necessary
        $settingsPrivate = json_decode( file_get_contents( dirname(__FILE__) . '/../settings_private.json' ), true );
        $this->data = new stdClass(); // Empty object
        $this->data->testSettings = (object) array_merge(
            $settingsPublic[ 'default' ],
            $settingsPublic[ $st->getParams()[ 'settings' ] ],
            $settingsPrivate[ 'default' ],
            $settingsPrivate[ $st->getParams()[ 'settings' ] ]
        );
        // Sort the settings by order field
        uasort( $this->data->testSettings->setup, function( $a, $b ) {
            if( $a[ 'order' ] < $b[ 'order' ] ) {
                return -1;
            }
            return 1;
        } );
        // Add includes
        for( $x = 1; $x < 10; $x++ ) {
            foreach( $this->data->testSettings->setup as $key => $setupItem ) {
                if( isset( $setupItem[ '__include' . $x ] ) ) {
                    $this->data->testSettings->setup[ $key ] = array_merge( $setupItem, $this->data->testSettings->tmpl[ $setupItem[ '__include' . $x ] ] );
//    echo "\n\n\n\n\n";
//                    var_dump($setupItem);
                }
            }
        }
//        echo "\n\n\n\n\n";
//                        var_dump($this->data->testSettings);

        $this->wordpress = new Wordpress( $this, $st, $this->data->testSettings->wp_user, $this->data->testSettings->wp_pass );

        // what are we calling this host?
        $this->data->instanceName = 'storyplayer1';

        // What settings name was given? (Use # vendor/bin/storyplayer -D settings=...)
        switch( $st->getParams()[ 'settings' ] ) {
            case 'ec2':
                $this->CreateEc2();
                break;
            case 'local':
                break;
            default:
        }

        $this->data->do_phase_after_install = array();
        $this->data->do_phase_after_login = array();
        // Install items defined in settings
        foreach( $this->data->testSettings->setup as $setupItem ) {
            $setupItem = (object) $setupItem ;
            if( $setupItem->action == 'install' ) {
                if( $setupItem->type == 'webapp' ) {
                    $this->InstallWebApp( $setupItem );
                    if( $setupItem->after_install == 'setup' ) {
                        array_push( $this->data->do_phase_after_install, $setupItem );
                    }
                }
                if( $setupItem->type == 'wp_plugin' ) {
                    $this->wordpress->InstallPlugin( $setupItem->relpath, $setupItem->to_abspath );
                }
            }
        }

    }

    ////////////////////////////////////////

    function TestTeardown() {
        $st = $this->st;

        switch( $st->getParams()[ 'settings' ] ) {
            case 'ec2':
                $this->DestroyEc2( $st );
                break;
            default:
        }
    }

    ////////////////////////////////////////

    function PrepareForTest() {
        // Call to this function must be as a first start of the first test

        // Do after install
        foreach( $this->data->do_phase_after_install as $setupItem ) {
            if( $setupItem->type == 'webapp' ) {
                if( $setupItem->after_install == 'setup' ) {
                    $this->SetupWebApp( $setupItem->slug );
                }
            }
        }
    }

    ////////////////////////////////////////

    function CreateEc2() {
        $st = $this->st;

        $this->EchoMsg( "Create Amazon AWS EC2 server" );

        // create the VM, based on an AMI
        $st->usingEc2()->createVm( $this->data->instanceName, "centos6", "ami-1f23522f", 't1.micro', "default" );

        // we need to make sure the root filesystem is destroyed on termination
        $st->usingEc2Instance($this->data->instanceName)->markAllVolumesAsDeleteOnTermination();

        // we need to wait for a bit to allow EC2 to catch up :(
        $st->usingTimer()->waitFor(function($st) {
            // we need to run a command (any command) on the host, to get it added
            // to SSH's known_hosts file
            $st->usingHost($this->data->instanceName)->runCommandAsUser("ls", "root");
        }, 'PT5M');

        $this->data->testSettings->domain = $st->fromHost( $this->data->instanceName )->getIpAddress();
    //    $this->data->testSettings->domain = $st->fromEc2Instance( $this->data->instanceName )->getPublicDnsName();
        $this->wordpress->SetDomain( $this->data->testSettings->domain );
    }

    ////////////////////////////////////////

    function DestroyEc2() {
        $st = $this->st;

        $this->EchoMsg( "Destroy Amazon AWS EC2 server" );

        // destroy the instance we created
        if (isset($this->data->instanceName)) {
            // do we have a test VM to destroy?
            $hostDetails = $st->fromHostsTable()->getDetailsForHost($this->data->instanceName);
            if ($hostDetails !== null) {
                // destroy this host
                $st->usingEc2()->destroyVm($this->data->instanceName);
            }
        }

        // destroy the image that we booted to test
        if (isset($this->data->imageName)) {
            // do we have a test VM to destroy?
            $hostDetails = $st->fromHostsTable()->getDetailsForHost($this->data->imageName);
            if ($hostDetails !== null) {
                // destroy this host
                $st->usingEc2()->destroyVm($this->data->imageName);
            }
        }
    }

    ////////////////////////////////////////

    function InstallWebApp( $setupItem ) {
        $st = $this->st;

        $this->EchoMsg( "Install Web App" );

        if( $st->getParams()[ 'settings' ] == "ec2" ) {
            if( $setupItem->slug == "wordpress" ) {
                $this->wordpress->Install( $setupItem );
            }
        } else {
            // dorh
        }
    }

    ////////////////////////////////////////

    function SetupWebApp( $slug ) {
        global $ssStory;

        if( $slug == "wordpress" ) {
    //        ActionSetupWordpress( $st );
            $ssStory->wordpress->Setup();
        }
    }

    ////////////////////////////////////////

    function FindElementsByXpath( $xpath ) {
        $st = $this->st;

        return $st->fromBrowser()->getElementsByXpath( array( $xpath ) );
    //    $topElement = $st->fromBrowser()->getTopElement();
    //    $elements = $topElement->getElements('xpath', $xpath);
    }

    ////////////////////////////////////////

    function FindElementsByXpathMustExist( $xpath ) {
        $st = $this->st;

        $elements = $st->fromBrowser()->getElementsByXpath( array( $xpath ) );
        if( count( $elements ) > 0 ) {
            return $elements;
        } else {
            // dorh Throw exception (or something) so teardown will be done correctly
        }
        return null;
    }

    ////////////////////////////////////////

    function FindElementByXpath( $xpath ) {
        $st = $this->st;

        // Find an element without throwing an error is no element found.
        $elements = $st->fromBrowser()->getElementsByXpath( array( $xpath ) );
        if( count( $elements ) > 0 ) {
            return $elements[ 0 ];
        }
        return null;
    }

    ////////////////////////////////////////

    function FindElementByXpathMustExist( $xpath ) {
        $st = $this->st;

        // Will throw an error if the element is not found
        return $st->getRunningDevice()->getElement( 'xpath', $xpath );
    }

    ////////////////////////////////////////

    function HoverElementByXpath( $xpath ) {
        $st = $this->st;

        $element = $this->FindElementByXpathMustExist( $xpath );
        $st->getRunningDevice()->moveto( array( 'element' => $element->getID() ) );
        $st->usingTimer()->wait( 1, "Wait for the hover to take effect (for instance a dropdown)." );
    }

    ////////////////////////////////////////

    function ClickElementByXpath( $xpath, $mode ) {
        $element = $this->FindElementByXpath( $xpath );
        if( $element || $mode != "graceful" ) {
            $element->click();
        }
    }

    ////////////////////////////////////////

    function EchoMsg( $s ) {
        echo "\n######################################################################\n" . $s . "\n######################################################################\n\n";
    }

    ////////////////////////////////////////

}

////////////////////////////////////////

$story = newStoryFor('Wordpress')
         ->inGroup('Swifty Page Manager')
         ->called('Test Swifty Page Manager general behaviour.');

$story->addTestSetup( function( StoryTeller $st ) {
    $ssStory = $GLOBALS['ssStory'];
    $ssStory->st = $st;
    $ssStory->TestSetup();
} );

$story->addTestTeardown( function( StoryTeller $st ) {
    $ssStory = $GLOBALS['ssStory'];
    $ssStory->st = $st;
    $ssStory->TestTeardown();
} );

$story->addAction( function( StoryTeller $st ) {
    $ssStory = $GLOBALS['ssStory'];
    $ssStory->st = $st;
    $ssStory->TakeAction();
} );

$story->addPostTestInspection( function( StoryTeller $st ) {
//    $st->assertsString($this->data->testText)->equals("Akismet");
} );

