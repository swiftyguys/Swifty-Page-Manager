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
//    echo "=======================" . $checkpoint->testSettings->domain;

    // what are we calling this host?
    $checkpoint->instanceName = 'storyplayer1';

    // what are we setting up?
    switch( $st->getParams()[ 'settings' ] ) {
        case 'ec2':
            CreateEc2( $st );
            InstallWordpress( $st );
            break;
        case 'local':
            InstallMyPlugin( $st );
            break;
        default:
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

////////////////////////////////////////
// Tests
////////////////////////////////////////

$story->addAction( function( StoryTeller $st ) {
    // get 'global' data
    $checkpoint = $st->getCheckpoint();

    if( $checkpoint->testSettings->do_setup_wordpress == "true" ) {
        ActionSetupWordpress( $st );
    }
    ActionLoginWordpress( $st );
//    ActionCheckPlugins( $st );

    if( $st->getParams()[ 'settings' ] == 'local' ) {
        ActionActivatePlugin( $st, 'swifty-page-manager' );
        ActionCheckPluginRunning( $st, 'Swifty Page Manager' );
    }

//    $st->usingBrowser()->waitForTitle( 10, "bla bla bla");
} );

$story->addPostTestInspection( function( StoryTeller $st ) {
//    $checkpoint = $st->getCheckpoint();
//    $st->assertsString($checkpoint->testText)->equals("Akismet");
} );

////////////////////////////////////////

function ActionSetupWordpress( StoryTeller $st ) {
    // get 'global' data
    $checkpoint = $st->getCheckpoint();

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

    $st->usingBrowser()->gotoPage( "http://" . $checkpoint->testSettings->domain . "/wp-login.php?loggedout=true" );
    $st->usingBrowser()->type( $checkpoint->testSettings->wp_user )->intoFieldWithId( "user_login" );
    $st->usingBrowser()->type( $checkpoint->testSettings->wp_pass )->intoFieldWithId( "user_pass" );
    $st->usingBrowser()->click()->fieldWithName( 'wp-submit' );
}

////////////////////////////////////////

function ActionCheckPlugins( StoryTeller $st ) {
    ActionWPOpenAdminSubMenu( $st, 'plugins', 'Installed Plugins' );
    $txt = $st->fromBrowser()->getText()->fromFieldWithText( 'Akismet' );
    $st->assertsString( $txt )->equals( "Akismet" );
}

////////////////////////////////////////

function ActionActivatePlugin( StoryTeller $st, $pluginCode ) {
    ActionWPOpenAdminSubMenu( $st, 'plugins', 'Installed Plugins' );
    $st->fromBrowser()->getText()->fromFieldWithId( $pluginCode ); // Wait for the element to be there?
    $elements = $st->fromBrowser()->getElementsByXpath( array( 'descendant::tr[@id = "' . $pluginCode . '"]//a[normalize-space(text()) = "Activate"]' ) );
    foreach( $elements as $element ) {
        $element->click();
    }
}

////////////////////////////////////////

function ActionCheckPluginRunning( StoryTeller $st, $pluginName ) {
    // get 'global' data
    $checkpoint = $st->getCheckpoint();

    ActionWPOpenAdminSubMenu( $st, 'pages', $pluginName );
//    $st->usingBrowser()->gotoPage( "http://" . $checkpoint->testSettings->domain . "/wp-admin" );
////    $st->usingBrowser()->click()->linkWithClass( 'menu-icon-page' );
//    HoverElementByXpath( $st, 'descendant::li[@id = "menu-pages"]' );
//    $st->usingBrowser()->click()->linkWithText( $pluginName );
    $txt = $st->fromBrowser()->getText()->fromHeadingWithText( $pluginName );
    $st->assertsString( $txt )->equals( $pluginName );
}

////////////////////////////////////////

function ActionWPOpenAdminSubMenu( StoryTeller $st, $pluginCode, $submenuText ) {
    // get 'global' data
    $checkpoint = $st->getCheckpoint();

    // Xpath for main menu button
    $xpathMainmenuItem = 'descendant::li[@id = "menu-' . $pluginCode . '"]';

    // Open the admin page
    $st->usingBrowser()->gotoPage( "http://" . $checkpoint->testSettings->domain . "/wp-admin" );

    // Check if the WP menu is collapsed (to one icon) ( happens on small screens )
    $elements = $st->fromBrowser()->getElementsByXpath( array( 'descendant::span[@class = "ab-icon"]' ) );
    if( $elements[0]->displayed() ) {
        // Click on the collapse menu button, so the menu will appear
        $elements[0]->click();
    }

    // Click on the main menu button, as on other screens (sizes or touch ) a click is needed
    $elements = $st->fromBrowser()->getElementsByXpath( array( $xpathMainmenuItem ) );
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

    // create the parameters to inject into the test box
    $vmParams = array (
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
//    $st->usingProvisioner('ansible')->provisionHosts($def);
    $st->usingProvisioningEngine('ansible')->provisionHosts($def);

// make sure the ACL is installed and running
//    $st->expectsHost('pickle-node')->packageIsInstalled('ms-service-picklenode');
//    $st->expectsHost('pickle-node')->processIsRunning('pickle-node');
}

////////////////////////////////////////

function InstallMyPlugin( StoryTeller $st ) {
    shell_exec( 'cp -a ' . dirname(__FILE__) . '/../plugin /media/sf__ubuntu14/wordpress/wp-content/plugins/swifty-page-manager' );
}

////////////////////////////////////////

function HoverElementByXpath( $st, $xpath ) {
    $elements = $st->fromBrowser()->getElementsByXpath( array( $xpath ) );
//    foreach( $elements as $element ) {
        $st->getRunningDevice()->moveto( array( 'element' => $elements[0]->getID() ) );
//    }
//    $st->usingTimer()->wait( 10, "Wait for the hover to take effect (for instance a dropdown)." );
}

////////////////////////////////////////
