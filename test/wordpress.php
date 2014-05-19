<?php

use DataSift\Storyplayer\PlayerLib\StoryTeller;

$story = newStoryFor('Wikipedia')
         ->inGroup('Search')
         ->called('Make sure that "Testing" redirects to "Test"');

////////////////////////////////////////
// SETUP and TEARDOWN
////////////////////////////////////////

$story->addTestSetup( function( StoryTeller $st ) {
    // we're going to store some information in here
    $checkpoint = $st->getCheckpoint();

    // load the test settings; any settings in private will overrule the same settings in public
    $settingsPublic = json_decode( file_get_contents( dirname(__FILE__) . '/settings_public.json' ), true );
    $settingsPublic[ $st->getParams()[ 'settings' ] ] = ( isset( $settingsPublic[ $st->getParams()[ 'settings' ] ] ) && is_array( $settingsPublic[ $st->getParams()[ 'settings' ] ] )) ? $settingsPublic[ $st->getParams()[ 'settings' ] ] : array(); // initialize if necessary
    $settingsPrivate = json_decode( file_get_contents( dirname(__FILE__) . '/settings_private.json' ), true );
    $checkpoint->testSettings = (object) array_merge(
        $settingsPublic[ 'default' ],
        $settingsPublic[ $st->getParams()[ 'settings' ] ],
        $settingsPrivate[ 'default' ],
        $settingsPrivate[ $st->getParams()[ 'settings' ] ]
    );

    // what are we calling this host?
    $checkpoint->instanceName = 'storyplayer1';

    // What settings name was given? (Use # vendor/bin/storyplayer -D settings=...)
    switch( $st->getParams()[ 'settings' ] ) {
        case 'ec2':
            CreateEc2( $st );
            break;
        case 'local':
            break;
        default:
    }

    // Install items defined in settings
    $checkpoint->do_phase_after_install = array();
    $checkpoint->do_phase_after_login = array();
    usort( $checkpoint->testSettings->setup, function( $a, $b ) {
        if( $a[ 'order' ] < $b[ 'order' ] ) {
            return -1;
        }
        return 1;
    } );
    var_dump( $checkpoint->testSettings);
    foreach( $checkpoint->testSettings->setup as $setupItem ) {
        $setupItem = (object) $setupItem ;
        if( $setupItem->action == 'install' ) {
            if( $setupItem->type == 'webapp' ) {
                InstallWebApp( $st, $setupItem->slug );
                if( $setupItem->after_install == 'setup' ) {
                    array_push( $checkpoint->do_phase_after_install, $setupItem );
                }
            }
            if( $setupItem->type == 'wp_plugin' ) {
                InstallPlugin( $st, $setupItem->relpath, $setupItem->to_abspath );
            }
        }
    }

} );

$story->addTestTeardown( function( StoryTeller $st ) {
    switch( $st->getParams()[ 'settings' ] ) {
        case 'ec2':
            DestroyEc2( $st );
            break;
        default:
    }
} );

function PrepareForTest( $st ) {
    // Must be as a first start of the first test

    // get 'global' data
    $checkpoint = $st->getCheckpoint();

    // Do after install
    foreach( $checkpoint->do_phase_after_install as $setupItem ) {
        if( $setupItem->type == 'webapp' ) {
            if( $setupItem->after_install == 'setup' ) {
                SetupWebApp( $st, $setupItem->slug );
            }
        }
    }
}

////////////////////////////////////////
// Tests
////////////////////////////////////////

$story->addAction( function( StoryTeller $st ) {
    PrepareForTest( $st);

    ActionLoginWordpress( $st );

    ActionCheckPluginRunning( $st, 'Swifty Page Manager' );
} );

////////////////////////////////////////
// Post inspections
////////////////////////////////////////

$story->addPostTestInspection( function( StoryTeller $st ) {
//    $checkpoint = $st->getCheckpoint();
//    $st->assertsString($checkpoint->testText)->equals("Akismet");
} );

////////////////////////////////////////

function ActionSetupWordpress( StoryTeller $st ) {
    // get 'global' data
    $checkpoint = $st->getCheckpoint();

    EchoMsg( "Setup Wordpress" );

    $st->usingBrowser()->gotoPage( "http://" . $checkpoint->testSettings->domain );
    $st->usingBrowser()->type( "storyplayer_test" )->intoFieldWithId( "weblog_title" );
    $st->usingBrowser()->type( $checkpoint->testSettings->wp_user )->intoFieldWithId( "user_login" );
    $st->usingBrowser()->type( $checkpoint->testSettings->wp_pass )->intoFieldWithId( "pass1" );
    $st->usingBrowser()->type( $checkpoint->testSettings->wp_pass )->intoFieldWithId( "pass2" );
    $st->usingBrowser()->type( "test@test.test" )->intoFieldWithId( "admin_email" );
    $st->usingBrowser()->click()->fieldWithName( "Submit" );
    $st->usingBrowser()->click()->fieldWithText( "Log In" );
}

////////////////////////////////////////

function ActionLoginWordpress( StoryTeller $st ) {
    // get 'global' data
    $checkpoint = $st->getCheckpoint();

    EchoMsg( "Login Wordpress" );

    // Login
    $st->usingBrowser()->gotoPage( "http://" . $checkpoint->testSettings->domain . "/wp-login.php?loggedout=true" );
    $st->usingBrowser()->type( $checkpoint->testSettings->wp_user )->intoFieldWithId( "user_login" );
    $st->usingBrowser()->type( $checkpoint->testSettings->wp_pass )->intoFieldWithId( "user_pass" );
    $st->usingBrowser()->click()->fieldWithName( 'wp-submit' );

    // Do setup actions that need to be done after login
    foreach( $checkpoint->testSettings->setup as $setupItem ) {
        $setupItem = (object) $setupItem ;
        if( $setupItem->after_login == 'activate' && $setupItem->type == 'wp_plugin' ) {
            ActionActivatePlugin( $st, $setupItem->slug );
        }
    }
}

////////////////////////////////////////

function ActionCheckPlugins( StoryTeller $st ) {
    EchoMsg( "Check plugins" );

    ActionWPOpenAdminSubMenu( $st, 'plugins', 'Installed Plugins' );
    $st->usingTimer()->wait( 1, "Wait for Installed Plugin page." );
    $txt = $st->fromBrowser()->getText()->fromFieldWithText( 'Akismet' );
    $st->assertsString( $txt )->equals( "Akismet" );
}

////////////////////////////////////////

function ActionActivatePlugin( StoryTeller $st, $pluginCode ) {
    EchoMsg( "Activate plugin: " . $pluginCode );

    ActionWPOpenAdminSubMenu( $st, 'plugins', 'Installed Plugins' );
    $st->usingTimer()->wait( 1, "Wait for Installed Plugin page." );
    ClickElementByXpath( $st, 'descendant::tr[@id = "' . $pluginCode . '"]//a[normalize-space(text()) = "Activate"]', "graceful" );
}

////////////////////////////////////////

function ActionCheckPluginRunning( StoryTeller $st, $pluginName ) {
    EchoMsg( "Check plugin running: " . $pluginName );

    ActionWPOpenAdminSubMenu( $st, 'pages', $pluginName );
    $txt = $st->fromBrowser()->getText()->fromHeadingWithText( $pluginName );
    $st->assertsString( $txt )->equals( $pluginName );
}

////////////////////////////////////////

function ActionWPOpenAdminSubMenu( StoryTeller $st, $pluginCode, $submenuText ) {
    // get 'global' data
    $checkpoint = $st->getCheckpoint();

    EchoMsg( "Open admin sub-menu: " . $pluginCode . " -> " . $submenuText );

    // Xpath for main menu button
    $xpathMainmenuItem = 'descendant::li[@id = "menu-' . $pluginCode . '"]';

    // Open the admin page
    $st->usingBrowser()->gotoPage( "http://" . $checkpoint->testSettings->domain . "/wp-admin" );

    // Check if the WP menu is collapsed (to one icon) ( happens on small screens )
//    $elements = $st->fromBrowser()->getElementsByXpath( array( 'descendant::span[@class = "ab-icon"]' ) );
    $elements = FindElementsByXpath( $st, 'descendant::li[@id = "wp-admin-bar-menu-toggle"]' );
    if( count( $elements ) > 0 && $elements[0]->displayed() ) {
        // Click on the collapse menu button, so the menu will appear
        $elements[0]->click();
    }

    // Click on the main menu button, as on other screens (sizes or touch ) a click is needed
    $elements = FindElementsByXpathMustExist( $st, $xpathMainmenuItem );
    $elements[0]->click();
    // Hover the main menu button, as on some screens (sizes or touch) a hover is needed
    HoverElementByXpath( $st, $xpathMainmenuItem );

    // Click on the sub menuu
    $st->usingBrowser()->click()->linkWithText( $submenuText );
}

////////////////////////////////////////

function CreateEc2( StoryTeller $st ) {
    // get the checkpoint
    $checkpoint = $st->getCheckpoint();

    EchoMsg( "Create Amazon AWS EC2 server" );

    // create the VM, based on an AMI
    $st->usingEc2()->createVm( $checkpoint->instanceName, "centos6", "ami-1f23522f", 't1.micro', "default" );

    // we need to make sure the root filesystem is destroyed on termination
    $st->usingEc2Instance($checkpoint->instanceName)->markAllVolumesAsDeleteOnTermination();

    // we need to wait for a bit to allow EC2 to catch up :(
    $st->usingTimer()->waitFor(function($st) use($checkpoint) {
        // we need to run a command (any command) on the host, to get it added
        // to SSH's known_hosts file
        $st->usingHost($checkpoint->instanceName)->runCommandAsUser("ls", "root");
    }, 'PT5M');

    $checkpoint->testSettings->domain = $st->fromHost( $checkpoint->instanceName )->getIpAddress();
//    $checkpoint->testSettings->domain = $st->fromEc2Instance( $checkpoint->instanceName )->getPublicDnsName();
}

////////////////////////////////////////

function DestroyEc2( StoryTeller $st ) {
    // get the checkpoint
    $checkpoint = $st->getCheckpoint();

    EchoMsg( "Destroy Amazon AWS EC2 server" );

    // destroy the instance we created
    if (isset($checkpoint->instanceName)) {
        // do we have a test VM to destroy?
        $hostDetails = $st->fromHostsTable()->getDetailsForHost($checkpoint->instanceName);
        if ($hostDetails !== null) {
            // destroy this host
            $st->usingEc2()->destroyVm($checkpoint->instanceName);
        }
    }

    // destroy the image that we booted to test
    if (isset($checkpoint->imageName)) {
        // do we have a test VM to destroy?
        $hostDetails = $st->fromHostsTable()->getDetailsForHost($checkpoint->imageName);
        if ($hostDetails !== null) {
            // destroy this host
            $st->usingEc2()->destroyVm($checkpoint->imageName);
        }
    }
}

////////////////////////////////////////

function InstallWordpress( StoryTeller $st ) {
    // we're going to store some information in here
    $checkpoint = $st->getCheckpoint();

    EchoMsg( "Install Wordpress" );

    // create the parameters to inject into the test box
    $vmParams = array (
        "install_now" => "wordpress",
        // Which version of Wordpress to deploy
        "wp_version" => "3.7",
        "wp_sha256sum" => "94b8b7a7241ec0817defa1c35f738d777f01ac17a4e45ee325c0f1778504fd94",
        // These are the Wordpress database settings
        "wp_db_name" => "wordpress",
        "wp_db_user" => "wordpress",
        "wp_db_password" => "secret",
        // You shouldn't need to change this.
        "mysql_port" => "3306",
        // This is used for the nginx server configuration, but access to the
        // Wordpress site is not restricted by a named host.
        "server_hostname" => "www.example.com",
        // Disable All Updates
        // By default automatic updates are enabled, set this value to true to disable all automatic updates
        "auto_up_disable" => false,
        //Define Core Update Level
        //true  = Development, minor, and major updates are all enabled
        //false = Development, minor, and major updates are all disabled
        //minor = Minor updates are enabled, development, and major updates are disabled
        "core_update_level" => true
    );

    // build up the provisioning definition
    $def = $st->usingProvisioning()->createDefinition();
    $st->usingProvisioningDefinition($def)->addRole('wordpress-server')->toHost($checkpoint->instanceName);
    $st->usingProvisioningDefinition($def)->addParams($vmParams)->toHost($checkpoint->instanceName);

    // provision our VM
    $st->usingProvisioningEngine('ansible')->provisionHosts($def);
}

////////////////////////////////////////

function InstallPlugin( StoryTeller $st, $relpath, $toAbspath ) {
    // get 'global' data
    $checkpoint = $st->getCheckpoint();

    EchoMsg( "Install plugin: " . $relpath );

    if( $st->getParams()[ 'settings' ] == "ec2" ) {
        // Copy plugin to remote server via Ansible

        // create the parameters for Ansible
        $vmParams = array (
            "install_now" => "plugin",
            "code" => "swifty-page-manager"
        );

        // build up the provisioning definition
        $def = $st->usingProvisioning()->createDefinition();
        $st->usingProvisioningDefinition($def)->addRole('wordpress-server')->toHost($checkpoint->instanceName);
        $st->usingProvisioningDefinition($def)->addParams($vmParams)->toHost($checkpoint->instanceName);

        // provision our VM
        $st->usingProvisioningEngine('ansible')->provisionHosts($def);
    } else {
        // Copy plugin locally
        shell_exec( 'cp -a ' . dirname(__FILE__) . '/' . $relpath . ' ' . $toAbspath );
    }
}

////////////////////////////////////////

function InstallWebApp( StoryTeller $st, $slug ) {
    if( $st->getParams()[ 'settings' ] == "ec2" ) {
        if( $slug == "wordpress" ) {
            InstallWordpress( $st );
        }
    } else {
        // dorh
    }
}

////////////////////////////////////////

function SetupWebApp( StoryTeller $st, $slug ) {
    if( $slug == "wordpress" ) {
        ActionSetupWordpress( $st );
    }
}

////////////////////////////////////////

function FindElementsByXpath( $st, $xpath ) {
    return $st->fromBrowser()->getElementsByXpath( array( $xpath ) );
//    $topElement = $st->fromBrowser()->getTopElement();
//    $elements = $topElement->getElements('xpath', $xpath);
}

////////////////////////////////////////

function FindElementsByXpathMustExist( $st, $xpath ) {
    $elements = $st->fromBrowser()->getElementsByXpath( array( $xpath ) );
    if( count( $elements ) > 0 ) {
        return $elements;
    } else {
        // dorh Throw exception (or something) so teardown will be done correctly
    }
    return null;
}

////////////////////////////////////////

function FindElementByXpath( $st, $xpath ) {
    // Find an element without throwing an error is no element found.
    $elements = $st->fromBrowser()->getElementsByXpath( array( $xpath ) );
    if( count( $elements ) > 0 ) {
        return $elements[ 0 ];
    }
    return null;
}

////////////////////////////////////////

function FindElementByXpathMustExist( $st, $xpath ) {
    // Will throw an error if the element is not found
    return $st->getRunningDevice()->getElement( 'xpath', $xpath );
}

////////////////////////////////////////

function HoverElementByXpath( $st, $xpath ) {
    $element = FindElementByXpathMustExist( $st, $xpath );
    $st->getRunningDevice()->moveto( array( 'element' => $element->getID() ) );
    $st->usingTimer()->wait( 1, "Wait for the hover to take effect (for instance a dropdown)." );
}

////////////////////////////////////////

function ClickElementByXpath( $st, $xpath, $mode ) {
    $element = FindElementByXpath( $st, $xpath );
    if( $element || $mode != "graceful" ) {
        $element->click();
    }
}

////////////////////////////////////////

function EchoMsg( $s ) {
    echo "\n######################################################################\n" . $s . "\n######################################################################\n\n";
}

////////////////////////////////////////
