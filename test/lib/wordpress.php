<?php

class Wordpress {

    function Wordpress( $story, $st, $version, $lang, $user, $pass ) {
        $this->story = $story; // The main/global application object
        $this->st = $st; // StoryTeller
        $this->version = $version;
        $this->lang = $lang;
        $this->user = $user;
        $this->pass = $pass;

        $this->tmpl = $this->story->data->testSettings->tmpl[ 'wp_' . $this->version ];
        $tl = $this->story->data->testSettings->tmpl[ 'wp_' . $this->lang . '_' . $this->version ];
        if( isset( $tl ) ) {
            $this->tmpl = $tl;
        }

        $this->strings = array();
        foreach( $this->story->data->testSettings->tmpl as $key => $tmpl ) {
            $l = 5;
            if( substr( $key, 0, $l ) == 's_wp_' ) { // global version
                $v = floatval( substr( $key, $l ) );
                if( $v > 0 && floatval( $this->version ) >= $v ) {
                    $this->strings = array_replace_recursive( $this->strings, $tmpl );
                }
            }
            $l = 6 + strlen( $this->lang );
            if( substr( $key, 0, $l ) == 's_wp_' . $this->lang . '_' ) { // specific lang
                $v = floatval( substr( $key, $l ) );
                if( $v > 0 && floatval( $this->version ) >= $v ) {
                    $this->strings = array_replace_recursive( $this->strings, $tmpl );
                }
            }
        }
    }

    ////////////////////////////////////////

    function SetDomain( $domain ) {
        $this->domain = $domain;
    }

    ////////////////////////////////////////

    function Install( $setupItem ) {
        $st = $this->st;

        $this->story->EchoMsg( "Install Wordpress" );

        // Settings for Ansible Wordpress install
        // Make sure these settings are not in Ansible's group_vars/all file otherwise those seem to take precedence
        $vmParams = array (
            "install_now" => "wordpress",
            "wp_version" => $this->version,
            "wp_sha256sum" => $this->tmpl[ 'wp_sha256sum' ],
            "wp_subsite" => $this->lang == 'en' ? '' : $this->lang . '.',
            "wp_fileadd" => $this->lang == 'en' ? '' : '-' . $this->lang . '_' . strtoupper( $this->lang ),
            "wp_conf_lang" => $this->lang == 'en' ? '' : $this->lang . '_' . strtoupper( $this->lang ),
            "wp_db_name" => "wordpress",
            "wp_db_user" => "wordpress",
            "wp_db_password" => "secret",
            "mysql_port" => "3306"
        );

        // build up the provisioning definition
        $def = $st->usingProvisioning()->createDefinition();
        $st->usingProvisioningDefinition($def)->addRole('wordpress-server')->toHost($this->story->data->instanceName);
        $st->usingProvisioningDefinition($def)->addParams($vmParams)->toHost($this->story->data->instanceName);

        // provision our VM
        $st->usingProvisioningEngine('ansible')->provisionHosts($def);
    }

    ////////////////////////////////////////

    function Setup() {
        $st = $this->st;

        $this->story->EchoMsg( "Setup Wordpress" );

        $st->usingBrowser()->gotoPage( "http://" . $this->domain );
        $st->usingBrowser()->type( "storyplayer_test" )->intoFieldWithId( "weblog_title" );
        $st->usingBrowser()->clear()->intoFieldWithId( "user_login" );
        $st->usingBrowser()->type( $this->user )->intoFieldWithId( "user_login" );
        $st->usingBrowser()->type( $this->pass )->intoFieldWithId( "pass1" );
        $st->usingBrowser()->type( $this->pass )->intoFieldWithId( "pass2" );
        $st->usingBrowser()->type( "test@test.test" )->intoFieldWithId( "admin_email" );
        $st->usingBrowser()->click()->fieldWithName( "Submit" );
        $st->usingBrowser()->click()->fieldWithText( $this->strings[ 's_login_button' ] );
    }

    ////////////////////////////////////////

    function Login() {
        $st = $this->st;

        $this->story->EchoMsg( "Login Wordpress" );

        // Login
        $st->usingBrowser()->gotoPage( "http://" . $this->story->data->testSettings->domain . "/wp-login.php?loggedout=true" );
        $st->usingBrowser()->type( $this->user )->intoFieldWithId( "user_login" );
        $st->usingBrowser()->type( $this->pass )->intoFieldWithId( "user_pass" );
        $st->usingBrowser()->click()->fieldWithName( 'wp-submit' );

        // Do setup actions that need to be done after login
        foreach( $this->story->data->testSettings->setup as $setupItem ) {
            $setupItem = (object) $setupItem ;
            if( $setupItem->after_login == 'activate' && $setupItem->type == 'wp_plugin' ) {
                $this->ActivatePlugin( $setupItem->slug );
            }
        }
    }

    ////////////////////////////////////////

    function InstallPlugin( $relpath, $toAbspath ) {
        $st = $this->st;

        $this->story->EchoMsg( "Install plugin: " . $relpath );

        if( $this->story->params[ 'platform' ] == "ec2" ) {
            // Copy plugin to remote server via Ansible

            // create the parameters for Ansible
            $vmParams = array (
                "install_now" => "plugin",
                "code" => "swifty-page-manager",
                "wp_plugin_relpath" => $relpath
            );

            // build up the provisioning definition
            $def = $st->usingProvisioning()->createDefinition();
            $st->usingProvisioningDefinition($def)->addRole('wordpress-server')->toHost($this->story->data->instanceName);
            $st->usingProvisioningDefinition($def)->addParams($vmParams)->toHost($this->story->data->instanceName);

            // provision our VM
            $st->usingProvisioningEngine('ansible')->provisionHosts($def);
        } else {
            // Copy plugin locally
            shell_exec( 'cp -a ' . dirname(__FILE__) . '/' . $relpath . ' ' . $toAbspath );
        }
    }

    ////////////////////////////////////////

    function ActivatePlugin( $pluginCode ) {
        $st = $this->st;

        $this->story->EchoMsg( "Activate plugin: " . $pluginCode . ' ' . $this->IsPluginActivated( $pluginCode ) );

        $this->OpenAdminSubMenu( 'plugins', $this->strings[ 's_submenu_installed_plugins' ] );
        $st->usingTimer()->wait( 1, "Wait for Installed Plugin page." );

        if ( ! $this->IsPluginActivated( $pluginCode ) ) {
            $this->story->ClickElementByXpath( 'descendant::a[contains(@href, "plugin=' . $pluginCode . '") and normalize-space(text()) = "' . $this->strings[ 's_activate' ] . '"]', "graceful" );
        }
    }

    function IsPluginActivated( $pluginCode ) {
        $element = $this->story->FindElementsByXpath( 'descendant::a[contains(@href, "plugin=' . $pluginCode . '") and normalize-space(text()) = "' . $this->strings[ 's_deactivate' ] . '"]' );

        return ( $element && count( $element ) === 1 );
    }


    ////////////////////////////////////////

    function OpenAdminSubMenu( $pluginCode, $submenuText ) {
        $st = $this->st;

        $this->story->EchoMsg( "Open admin sub-menu: " . $pluginCode . " -> " . $submenuText );

        // Xpath for main menu button
        $xpathMainmenuItem = 'descendant::li[@id = "menu-' . $pluginCode . '"]';

        // Open the admin page
        $st->usingBrowser()->gotoPage( "http://" . $this->story->data->testSettings->domain . "/wp-admin" );

        // Check if the WP menu is collapsed (to one icon) ( happens on small screens )
        $elements = $this->story->FindElementsByXpath( 'descendant::li[@id = "wp-admin-bar-menu-toggle"]' );
        if( count( $elements ) > 0 && $elements[0]->displayed() ) {
            // Click on the collapse menu button, so the menu will appear
            $elements[0]->click();
        }

        // Click on the main menu button, as on other screens (sizes or touch ) a click is needed
        $elements = $this->story->FindElementsByXpathMustExist( $xpathMainmenuItem );
        $elements[0]->click();
        // Hover the main menu button, as on some screens (sizes or touch) a hover is needed
        $this->story->HoverElementByXpath( $xpathMainmenuItem );

        // Click on the sub menuu
        $st->usingBrowser()->click()->linkWithText( $submenuText );

//        $this->story->Probe( 'WP.AdminOpenSubmenu', array( "plugin_code" => $pluginCode, "submenu_text" => $submenuText ) );
    }


    ////////////////////////////////////////

//    function DeleteAllPages()
//    {
//        $st = $this->st;
//
//        $this->story->EchoMsg( 'Delete All Pages' );
//
//        $this->OpenAdminSubMenu( 'pages', $this->strings[ 's_submenu_all_pages' ] );
//        $st->usingTimer()->wait( 1, 'Wait for Wordpress Pages page' );
//        $st->usingBrowser()->click()->fieldWithId( 'cb-select-all-1' );
//        $st->usingBrowser()->select( $this->strings[ 's_wp_pages_actions_delete' ] )->fromDropdownWithName( 'action' );
//        $st->usingBrowser()->click()->buttonWithId( 'doaction' );
//    }

    ////////////////////////////////////////

//    function EmptyTrash()
//    {
//        $st = $this->st;
//
//        $this->story->EchoMsg( 'Empty trash' );
//
//        $this->OpenAdminSubMenu( 'pages', $this->strings[ 's_submenu_all_pages' ] );
//        $st->usingTimer()->wait( 1, 'Wait for Wordpress Pages page' );
//        $elements = $this->story->FindElementsByXpathMustExist( '//li[@class="trash"]/a' );
//
//        if ( count( $elements ) > 0 && $elements[0]->displayed() ) {
//            $elements[0]->click();   // Click on the trash link
//            $st->usingBrowser()->click()->buttonWithText( $this->strings[ 's_wp_pages_empty_trash' ] );
//            $elements = $st->fromBrowser()->getElementsByClass( 'no-items' );
//            $this->st->assertsInteger( count( $elements ) )->equals( 1 );
//        }
//    }

    ////////////////////////////////////////

//    function CreateXDraftPages( $total = 1 )
//    {
//        $st = $this->st;
//
//        $this->story->EchoMsg( 'Create x draft pages' );
//
//        $this->OpenAdminSubMenu( 'pages', $this->strings[ 's_submenu_all_pages' ] );
//        $st->usingTimer()->wait( 1, 'Wait for Wordpress Pages page' );
//
//        for ( $i = 1; $i <= $total; $i++ ) {
//            $st->usingBrowser()->click()->linkWithText( $this->strings[ 's_wp_pages_create_new' ] );
//            $st->usingTimer()->wait( 1, 'Wait for Wordpress New Page page' );
//            $st->usingBrowser()->type( 'WP Pagina ' . $i )->intoFieldWithName( 'post_title' );
//            $st->usingBrowser()->click()->buttonWithText( $this->strings[ 's_wp_pages_save_concept' ] );
//            $st->usingTimer()->wait( 1, 'Wait for Wordpress Edit Page page' );
//        }
//    }

    ////////////////////////////////////////
}