( function( $, probe ) {
    probe.WP = probe.WP || {};

    probe.WP.AdminOpenSubmenu = {
        Start: function( /*input*/ ) {
            // Check if the WP menu is collapsed (to one icon) ( happens on small screens )
            $( "li#wp-admin-bar-menu-toggle" )
                .IfVisible()
                .OtherIfNotVisible( "ul#adminmenu" )
                .Click();

            // Wait until the submenu becomes visible
            $( "ul#adminmenu" ).WaitForVisible( "Step2" );
        },

        Step2: function( input ) {
            // Click on the menu item in the left admin bar
            $( this.GetSelMainmenu( input.plugin_code ) )
                .MustExist()
                .Click();

            // Wait until the submenu becomes visible
            $( this.GetSelSubmenu( input.submenu_text ) ).WaitForFn( "Wait2", "Step3" );
        },

        Wait2: function( input ) {
            // Trick WP into thinking the mouse hovers over the menu item (so the submenu popup opens)
            // In some cases (WP version, screen size) this hover is needed
            $( this.GetSelMainmenu( input.plugin_code ) ).AddClass( "opensub" );

            // Is the submenu item visible?
            var check = $( this.GetSelSubmenu( input.submenu_text ) ).IsVisible();

            return { "wait_result": check };
        },

        Step3: function( input ) {
            // Click on the sub menu
            $( this.GetSelSubmenu( input.submenu_text ) )
                .MustExist()
                .Click();
        },

        GetSelMainmenu: function( pluginCode ) {
            return "li#menu-" + pluginCode;
        },

        GetSelSubmenu: function( submenuText ) {
            return "a:contains('" + submenuText + "')";
        }
    };

    ////////////////////////////////////////

    probe.WP.ActivatePlugin = {
        Start: function( input ) { // dorh Not tested
            $( "a:contains('" + input.s_activate + "')[href*='plugin=" + input.plugin_code + "']" ).Click();
        }
    };

    ////////////////////////////////////////

} )( jQuery, swiftyProbe );