( function( $, probe ) {
    probe.WP = probe.WP || {};

    probe.WP.AdminOpenSubmenu = {
        Start: function( /*input*/ ) {
            // Check if the WP menu is collapsed (to one icon) ( happens on small screens )
            $( 'li#wp-admin-bar-menu-toggle' )
                .IfVisible()
                .OtherIfNotVisible( 'ul#adminmenu' )
                .Click();

            // Wait until the submenu becomes visible
            $( 'ul#adminmenu' ).WaitForVisible( 'Step2' );
        },

        Step2: function( input ) {
            // Click on the menu item in the left admin bar
            $( this.GetSelMainmenu( input.plugin_code ) )
                .MustExist()
                .Click();

            // Wait until the submenu becomes visible
            $( this.GetSelSubmenu( input.submenu_text ) ).WaitForFn( 'Wait2', 'Step3' );
        },

        Wait2: function( input ) {
            // Trick WP into thinking the mouse hovers over the menu item (so the submenu popup opens)
            // In some cases (WP version, screen size) this hover is needed
            $( this.GetSelMainmenu( input.plugin_code ) ).AddClass( 'opensub' );

            // Is the submenu item visible?
            var check = $( this.GetSelSubmenu( input.submenu_text ) ).IsVisible();

            return { 'wait_result': check };
        },

        Step3: function( input ) {
            // Click on the sub menu
            $( this.GetSelSubmenu( input.submenu_text ) )
                .MustExist()
                .Click();
        },

        /**
         * @return string
         */
        GetSelMainmenu: function( pluginCode ) {
            return 'li#menu-' + pluginCode;
        },

        /**
         * @return string
         */
        GetSelSubmenu: function( submenuText ) {
            return 'a:contains("' + submenuText + '")';
        }
    };

    ////////////////////////////////////////

    probe.WP.ActivatePlugin = {
        Start: function( input ) { // dorh Not tested
            $( 'a:contains("' + input.s_activate + '")[href*="plugin=' + input.plugin_code + '"]' ).Click();
        }
    };

    probe.WP.DeleteAllPages = {
        Start: function( /*input*/ ) {
            probe.QueueStory(
                'WP.AdminOpenSubmenu',
                {
                    'plugin_code': 'pages',
                    'submenu_text': 'All Pages'   // Here we need something for translations
                },
                'Step2'
            );
        },

        Step2: function( /*input*/ ) {
            // Click on the checkbox to select all pages
            $( '#cb-select-all-1' )
                .MustExist()
                .Click();

            // Select the trash option
            $( 'select[name="action"]' )
                .MustExist()
                .val( [ 'trash' ] );

            // Click on the Apply button
            $( '#doaction' )
                .MustExist()
                .Click();
        }
    };

    ////////////////////////////////////////

    probe.WP.EmptyTrash = {
        trashSel: 'li.trash a',
        deleteAllSel: '#delete_all',

        Start: function( /*input*/ ) {
            probe.QueueStory(
                'WP.AdminOpenSubmenu',
                {
                    'plugin_code': 'pages',
                    'submenu_text': 'All Pages'   // Here we need something for translations
                },
                'Step2'
            );
        },

        Step2: function( /*input*/ ) {
            var trashLink = $( this.trashSel );

            // Click on the 'Trash' link
            if ( trashLink.length ) {
                trashLink.Click();

                // Wait until the 'Empty Trash' button becomes visible
                $( this.deleteAllSel ).WaitForVisible( 'Step3' );
            }
        },

        Step3: function( /*input*/ ) {
            // Click on the 'Empty Trash' button
            $( this.deleteAllSel ).MustExist().Click();
        }
    };

    ////////////////////////////////////////

    probe.WP.CreateXDraftPages = {
        addNewSel: 'a.add-new-h2',
        pageListSel: '#the-list',
        postTitleSel: 'input[name="post_title"]',

        Start: function( /*input*/ ) {
            probe.QueueStory(
                'WP.AdminOpenSubmenu',
                {
                    'plugin_code': 'pages',
                    'submenu_text': 'All Pages'   // dojh Here we need something for translations
                },
                'Step2'
            );
        },

        Step2: function( /*input*/ ) {
            // Wait until the 'Add new' link becomes visible
            $( this.addNewSel ).WaitForVisible( 'Step3', 5000, 1 );
        },

        Step3: function( input ) {
            if ( input.wait_data <= input.x_pages ) {
                // Click on the 'Add new' link
                $( this.addNewSel ).MustExist().Click();

                // Wait until the 'post_type' input field becomes visible
                $( this.postTitleSel ).WaitForVisible( 'Step4', 5000, input.wait_data );
            } else {
                probe.QueueStory(
                    'WP.AdminOpenSubmenu',
                    {
                        'plugin_code': 'pages',
                        'submenu_text': 'All Pages'   //dojh Here we need something for translations
                    },
                    'Step5'
                );
            }
        },

        Step4: function( input ) {
            var currentNr = input.wait_data;

            // Enter a value into the post_type input field
            $( this.postTitleSel ).val( 'WP Page ' + currentNr );

            // Click the 'Save Draft' button
            $( '#save-post' ).MustExist().Click();

            if ( currentNr <= input.x_pages ) {
                currentNr++;

                // Wait until the 'Add new' link becomes visible and proceed to step 3 again.
                $( this.addNewSel ).WaitForVisible( 'Step3', 5000, currentNr );
            }
        },

        Step5: function( /*input*/ ) {
            // Wait until the WP page list becomes visible
            $( this.pageListSel ).WaitForVisible( 'Step6' );
        },

        Step6: function( /*input*/ ) {
            // Check to see if there are really 2 pages created
            $( this.pageListSel ).find( 'tr' ).MustExistTimes( 2 );
        }
    };

    ////////////////////////////////////////

} )( jQuery, swiftyProbe );