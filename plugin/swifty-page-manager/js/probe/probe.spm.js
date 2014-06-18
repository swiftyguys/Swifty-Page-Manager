( function( $, probe ) {
    probe.SPM = probe.SPM || {};

    probe.SPM.CheckRunning = {
        Start: function( input ) {
            probe.QueueStory(
                'WP.AdminOpenSubmenu',
                {
                    'plugin_code': 'pages',
                    'submenu_text': input.plugin_name
                },
                'Step2'
            );
        },

        Step2: function( input ) {
            $( 'h2:contains("' + input.plugin_name + '")' ).MustExist();
        }
    };

    probe.SPM.NoPagesExist = {
        noPostSel: '.spm-no-posts-add',

        Start: function( input ) {
            probe.QueueStory(
                'WP.AdminOpenSubmenu',
                {
                    'plugin_code': 'pages',
                    'submenu_text': input.plugin_name
                },
                'Step2'
            );
        },

        Step2: function( /*input*/ ) {
            $( this.noPostSel ).WaitForVisible( 'Step3' );
        },

        Step3: function( /*input*/ ) {
            $( this.noPostSel ).MustExistTimes( 1 );
        }
    };

    ////////////////////////////////////////

    probe.SPM.XPagesExist = {
        Start: function( input ) {
            probe.QueueStory(
                'WP.AdminOpenSubmenu',
                {
                    'plugin_code': 'pages',
                    'submenu_text': input.plugin_name
                },
                'Step2'
            );
        },

        Step2: function( /*input*/ ) {
            $( probe.SPM.Helpers.getPageSelector() ).WaitForVisible( 'Step3' );
        },

        Step3: function( input ) {
            $( probe.SPM.Helpers.getPageSelector() ).MustExistTimes( input.x_pages );
        }
    };

    ////////////////////////////////////////

    probe.SPM.CreatePage = {
        Start: function( input ) {
            probe.QueueStory(
                'WP.AdminOpenSubmenu',
                {
                    'plugin_code': 'pages',
                    'submenu_text': input.plugin_name
                },
                'Step2'
            );
        },

        Step2: function( /*input*/ ) {
            $( probe.SPM.Helpers.getPageSelector() ).WaitForVisible( 'Step3' );
        },

        Step3: function( input ) {
            // Click to see the page buttons
            $( probe.SPM.Helpers.getPageSelector( input ) ).MustExist().Click();

            // Click on the 'Add page' button (+ button)
            $( '.spm-page-button:first' ).MustBeVisible().Click();

            probe.SPM.Helpers.setValues( input.values );

            // Click the 'Save' confirm button
            $( 'input[value="Save"]' ).MustBeVisible().Click();
        }
    };

    ////////////////////////////////////////

    probe.SPM.PageExists = {
        Start: function( input ) {
            probe.QueueStory(
                'WP.AdminOpenSubmenu',
                {
                    'plugin_code': 'pages',
                    'submenu_text': input.plugin_name
                },
                'Step2'
            );
        },

        Step2: function( /*input*/ ) {
            $( probe.SPM.Helpers.getPageSelector() ).WaitForVisible( 'Step3' );
        },

        Step3: function( input ) {
            var pageSel = probe.SPM.Helpers.getPageSelector( input );
            var actualPos;

            if ( input.x_pages ) {
                $( pageSel ).MustExistTimes( input.x_pages );
            }

            if ( typeof input.at_pos === 'number' ) {
                actualPos = $( probe.SPM.Helpers.getPageSelector() ).index( $( pageSel ) ) + 1;

                if ( actualPos !== input.at_pos ) {
                    probe.SetFail( 'Element exists on position ' + actualPos +
                                   ' and not on the expected position ' + input.at_pos );
                }
            }
        }
    };

    ////////////////////////////////////////

    probe.SPM.EditPage = {
        Start: function( input ) {
            probe.QueueStory(
                'WP.AdminOpenSubmenu',
                {
                    'plugin_code': 'pages',
                    'submenu_text': input.plugin_name
                },
                'Step2'
            );
        },

        Step2: function( /*input*/ ) {
            $( probe.SPM.Helpers.getPageSelector() ).WaitForVisible( 'Step3' );
        },

        Step3: function( input ) {
            // Click to see the page buttons
            $( probe.SPM.Helpers.getPageSelector( input ) ).MustExist().Click();

            // Click on the 'Add page' button (+ button)
            $( '.spm-page-button:eq( 1 )' ).MustBeVisible().Click();

            // Enter or select the form values
            probe.SPM.Helpers.setValues( input.values );

            // Click the 'Save' confirm button
            $( 'input[value="Save"]' ).MustBeVisible().Click();
        }
    };

    ////////////////////////////////////////

    probe.SPM.CheckPageStatus = {
        Start: function( input ) {
            probe.QueueStory(
                'WP.AdminOpenSubmenu',
                {
                    'plugin_code': 'pages',
                    'submenu_text': input.plugin_name
                },
                'Step2'
            );
        },

        Step2: function( /*input*/ ) {
            $( probe.SPM.Helpers.getPageSelector() ).WaitForVisible( 'Step3' );
        },

        Step3: function( input ) {
            var status = input.is_status === 'publish' ? '' : input.is_status;
            var statusSpan = $( probe.SPM.Helpers.getPageSelector( input ) ).MustExist().find( ':nth-child( 2 )' );
            var text = '';

            if ( statusSpan.is( 'span' ) ) {
                text = $.trim( statusSpan.text().toLowerCase() );
            }

            if ( status !== text ) {
                probe.SetFail( 'Page status does not equals the expected status "' + input.is_status + '"' );
            }
        }
    };

    ////////////////////////////////////////

    probe.SPM.DeletePage = {
        Start: function( input ) {
            probe.QueueStory(
                'WP.AdminOpenSubmenu',
                {
                    'plugin_code': 'pages',
                    'submenu_text': input.plugin_name
                },
                'Step2'
            );
        },

        Step2: function( /*input*/ ) {
            $( probe.SPM.Helpers.getPageSelector() ).WaitForVisible( 'Step3' );
        },

        Step3: function( input ) {
            // Click to see the page buttons
            $( probe.SPM.Helpers.getPageSelector( input ) ).MustExist().Click();

            // Click on the 'Delete page' button
            $( '.spm-page-button:eq(2)' ).MustBeVisible().Click();

            // Click the 'Delete' confirm button
            $( 'input[value="Delete"]' ).MustBeVisible().Click();
        }
    };

    ////////////////////////////////////////

    probe.SPM.PublishPage = {
        Start: function( input ) {
            probe.QueueStory(
                'WP.AdminOpenSubmenu',
                {
                    'plugin_code': 'pages',
                    'submenu_text': input.plugin_name
                },
                'Step2'
            );
        },

        Step2: function( /*input*/ ) {
            $( probe.SPM.Helpers.getPageSelector() ).WaitForVisible( 'Step3' );
        },

        Step3: function( input ) {
            // Click to see the page buttons
            $( probe.SPM.Helpers.getPageSelector( input ) ).MustExist().Click();

            // Click on the 'Publish page' button
            $( '.spm-page-button:eq(5)' ).MustBeVisible().Click();

            // Click the 'Publish' confirm button
            $( 'input[value="Publish"]' ).MustExist().Click();
        }
    };

    ////////////////////////////////////////

    probe.SPM.EditPageContent = {
        Start: function( input ) {
            probe.QueueStory(
                'WP.AdminOpenSubmenu',
                {
                    'plugin_code': 'pages',
                    'submenu_text': input.plugin_name
                },
                'Step2'
            );
        },

        Step2: function( /*input*/ ) {
            $( probe.SPM.Helpers.getPageSelector() ).WaitForVisible( 'Step3' );
        },

        Step3: function( input ) {
            // Click to see the page buttons
            $( probe.SPM.Helpers.getPageSelector( input ) ).MustExist().Click();

            // Click on the 'Edit page content' button
            $( '.spm-page-button:eq( 3 )' ).MustBeVisible().Click();

            $( 'h2:contains("Edit Page")' ).WaitForVisible( 'Step4' );
        },

        Step4: function( input ) {
            // Enter or select the form values
            probe.SPM.Helpers.setValues( input.values );

            // Click the 'Save' button
            $( '#save-post' ).MustBeVisible().Click();
        }
    };

    ////////////////////////////////////////

    probe.SPM.OpenSubMenu = {
        Start: function( input ) {
            probe.QueueStory(
                'WP.AdminOpenSubmenu',
                {
                    'plugin_code': 'pages',
                    'submenu_text': input.plugin_name
                },
                'Step2'
            );
        },

        Step2: function( /*input*/ ) {
            $( probe.SPM.Helpers.getPageSelector() ).WaitForVisible( 'Step3' );
        },

        Step3: function( input ) {
            $( probe.SPM.Helpers.getPageSelector( input ) ).MustExist().prev( 'jstree-icon' ).Click();
        }
    };

    ////////////////////////////////////////

    probe.SPM.Helpers = {
        setValues: function( values ) {
            // Enter or select the form values
            if ( values && typeof values === 'string' ) {
                values = JSON.parse( values );

                $.each( values, function( key, value ) {
                    var pair = value.split( ':' );
                    var type = pair[ 0 ];
                    var val = pair[ 1 ];
                    var sel = 'input[name="' + key + '"]';

                    switch ( type ) {
                        case 'text':
                            $( sel ).val( val );

                            break;
                        case 'radio':
                            $( sel ).val( [ val ] );

                            break;
                    }
                } );
            }
        },

        getPageSelector: function( input ) {
            var selector = '.spm-page-tree-element';

            if ( input ) {
                if ( input.post_title ) {
                    selector += ':contains("' + input.post_title + '")';
                } else if ( input.page_nr ) {
                    if ( typeof input.page_nr === 'number' ) {
                        selector += ':eq(' + --input.page_nr + ')';
                    } else if ( typeof input.page_nr === 'string' && $.inArray( input.page_nr, [ 'first', 'last' ] ) ) {
                        selector += ':' + input.page_nr;
                    }
                }
            }

            return selector;
        }
    };

    ////////////////////////////////////////

} )( jQuery, swiftyProbe );