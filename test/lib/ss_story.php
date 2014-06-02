<?php

use DataSift\Storyplayer\PlayerLib\StoryTeller;
use DataSift\Storyplayer\Prose\E5xx_ActionFailed;

include 'wordpress.php';

class SSStory {

    public $data;

    ////////////////////////////////////////

    function getText() { // Shortcut
        return $this->st->fromBrowser()->getText();
    }

    ////////////////////////////////////////

    function assertsString( $txt ) { // Shortcut
        return $this->st->assertsString( $txt );
    }

    ////////////////////////////////////////

    function TakeAction() { // Must be called first in a derived method
        $this->PrepareForTest();
    }

    ////////////////////////////////////////

    function TestSetup() {
        $st = $this->st;

        // Can be overwritten in command line, via -D platform=...
        $this->ori_story->setParams( array(
            'platform' => 'local',
            'wp_version' => '3.9.1',
            'lang' => 'en'
        ) );
        // get the final list of params
        // this will include any changes made from the command-line
        $this->params = $st->getParams();

        // load the test settings; any settings in private will overrule the same settings in public
        $settingsPublic = json_decode( file_get_contents( dirname(__FILE__) . '/../settings_public.json' ), true );
        $settingsPublic[ $this->params[ 'platform' ] ] = ( isset( $settingsPublic[ $this->params[ 'platform' ] ] ) && is_array( $settingsPublic[ $this->params[ 'platform' ] ] )) ? $settingsPublic[ $this->params[ 'platform' ] ] : array(); // initialize if necessary
        $settingsPrivate = json_decode( file_get_contents( dirname(__FILE__) . '/../settings_private.json' ), true );
        $this->data = new stdClass(); // Empty object
        $this->data->testSettings = (object) array_replace_recursive(
            $settingsPublic[ 'default' ],
            $settingsPublic[ $this->params[ 'platform' ] ],
            $settingsPrivate[ 'default' ],
            $settingsPrivate[ $this->params[ 'platform' ] ]
        );
        // Sort the settings by order field
        uasort( $this->data->testSettings->setup, function( $a, $b ) {
            if( $a[ 'order' ] < $b[ 'order' ] ) {
                return -1;
            }
            return 1;
        } );

        $this->wordpress = new Wordpress(
            $this,
            $st,
            $this->params[ 'wp_version' ],
            $this->params[ 'lang' ],
            $this->data->testSettings->wp_user,
            $this->data->testSettings->wp_pass
        );

        // what are we calling this host?
        $this->data->instanceName = 'storyplayer1';

        // What settings name was given? (Use # vendor/bin/storyplayer -D platform=...)
        switch( $this->params[ 'platform' ] ) {
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

        switch( $this->params[ 'platform' ] ) {
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

        if( $this->params[ 'platform' ] == "ec2" ) {
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

    function EchoMsgJs( $s ) {
        echo "+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++\nJS = " . $s;
    }

    ////////////////////////////////////////

    function ContainingClass( $className ) {
        return "contains(concat(' ',normalize-space(@class),' '),' " . $className . " ')";
    }

    ////////////////////////////////////////

    function Probe( $functionName, $input ) {
        $js = 'return swiftyProbe.DoStart(arguments);';
        $ret = $this->st->getRunningDevice()->execute( array( 'script' => $js, 'args' => Array( $functionName, $input ) ) );

        $this->ProbeProcessRet( $functionName, $input, $ret );
    }

    ////////////////////////////////////////

    function ProbeProcessRet( $functionName, $input, $returned ) {
        $ret = $returned;

        if( ! isset( $ret[ 'ret' ] ) ) {
            $this->EchoMsgJs( "NO DATA RETURNED:\n" );
            throw new E5xx_ActionFailed( "JS NO DATA RETURNED", "No data returned" );
        } else {
            if( isset( $ret[ 'ret' ][ 'fail' ] ) ) {
                $this->EchoMsgJs( "FAIL:". $ret[ 'ret' ][ 'fail' ] . "\n" );
                throw new E5xx_ActionFailed( "JS FAIL", $ret[ 'ret' ][ 'fail' ] );
            } else {
                $this->EchoMsgJs( $ret[ 'ret' ][ 'tmp_log' ] );
//                $this->EchoMsg( "DEBUG:\n". print_r( $ret, true ) );

                if( isset( $ret[ 'ret' ][ 'queue' ] ) ) {
                    $js = 'return swiftyProbe.DoStart(arguments);';

//                    echo "\n\n\neeeeeeeeeeeeeeeeeeeee:\n".$ret[ 'ret' ][ 'queue' ][ 'new_fn_name' ]."\n\n\n";

                    $prevFunctionName = $functionName;
                    $prevInput = $input;
                    $functionName = $ret[ 'ret' ][ 'queue' ][ 'new_fn_name' ];
                    $input = $ret[ 'ret' ][ 'queue' ][ 'new_input' ];
                    $nextFnName = $ret[ 'ret' ][ 'queue' ][ 'next_fn_name' ];
                    $ret = $this->st->getRunningDevice()->execute( array( 'script' => $js, 'args' => Array( $functionName, $input ) ) );
//                    $this->st->usingTimer()->wait( 5, "------------" );
                    $ret = $this->ProbeProcessRet( $functionName, $input, $ret );
                    $functionName = $prevFunctionName;
                    $input = $prevInput;
                    $input[ 'next_fn_name' ] = $nextFnName;
                    $js = 'return swiftyProbe.DoNext(arguments);';
                    $ret = $this->st->getRunningDevice()->execute( array( 'script' => $js, 'args' => Array( $functionName, $input ) ) );
                    $ret = $this->ProbeProcessRet( $functionName, $input, $ret );
                }

                if( isset( $returned[ 'ret' ][ 'wait' ] ) ) {
                    $wait = $returned[ 'ret' ][ 'wait' ];
                    $js = 'return swiftyProbe.DoWait(arguments);';
                    $waiting = true;
                    while( $waiting ) {
                        $ret = $this->st->getRunningDevice()->execute( array( 'script' => $js, 'args' => Array( $wait, $functionName, $input ) ) );
//                        $this->EchoMsg( "DEBUG 2:\n". print_r( $ret, true ) );
                        if( ! isset( $ret[ 'ret' ][ 'wait_status' ] )
                            || $ret[ 'ret' ][ 'wait_status' ] != "waiting"
                            || isset( $ret[ 'ret' ][ 'fail' ] )
                        ) {
                            $waiting = false;
                        }
                    }
                    $ret = $this->ProbeProcessRet( $functionName, $input, $ret );
                }
            }
        }

        return $ret;
    }

    ////////////////////////////////////////
}

////////////////////////////////////////

$story = $GLOBALS['story'] = newStoryFor('Wordpress')
         ->inGroup('Swifty Page Manager')
         ->called('Test Swifty Page Manager general behaviour.');

$story->addTestSetup( function( StoryTeller $st ) {
    $ssStory = $GLOBALS['ssStory'];
    $ssStory->st = $st;
    $ssStory->ori_story = $GLOBALS['story'];
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

