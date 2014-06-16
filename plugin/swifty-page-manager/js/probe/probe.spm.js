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
        pageLiSel: 'li[id^=spm-id-]',

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
            $( this.pageLiSel ).WaitForVisible( 'Step3' );
        },

        Step3: function( input ) {
            $( this.pageLiSel ).MustExistTimes( input.x_pages );
        }
    };

    ////////////////////////////////////////

    probe.SPM.CreatePage = {
        pageSel: '.spm-page-tree-element',

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
            $( this.pageSel ).WaitForVisible( 'Step3' );
        },

        Step3: function( input ) {
            if ( typeof input.page_nr === 'number' ) {
                this.pageSel += ':eq(' + --input.page_nr + ')';
            } else if ( typeof input.page_nr === 'string' && $.inArray( input.page_nr, [ 'first', 'last' ] ) ) {
                this.pageSel += ':' + input.page_nr;
            }

            // Click to see the page buttons
            $( this.pageSel ).MustExist().Click();

            // Click on the 'Add page' button (+ button)
            $( '.spm-page-button:first' ).MustBeVisible().Click();

            probe.SPM.Helpers.setValues( input.values );

            // Click the 'Save' button
            $( '[data-spm-action="save"]' ).MustBeVisible().Click();
        }
    };

    ////////////////////////////////////////

    probe.SPM.PageExists = {
        pageLiSel: 'li[id^=spm-id-]',

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
            $( this.pageLiSel ).WaitForVisible( 'Step3' );
        },

        Step3: function( input ) {
            var pageSel = this.pageLiSel + ':contains("' + input.post_title + '")';
            var actualPos;

            $( pageSel ).MustExistTimes( input.x_pages );

            if ( typeof input.at_pos === 'number' ) {
                actualPos = $( this.pageLiSel ).index( $( pageSel ) ) + 1;

                if ( actualPos !== input.at_pos ) {
                    probe.SetFail( 'Element exists on position ' + actualPos +
                                   ' and not on the expected position ' + input.at_pos );
                }
            }
        }
    };

    ////////////////////////////////////////

    probe.SPM.EditPage = {
        pageSel: '.spm-page-tree-element',

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
            $( this.pageSel ).WaitForVisible( 'Step3' );
        },

        Step3: function( input ) {
            // Click to see the page buttons
            $( this.pageSel + ':contains("' + input.post_title + '")' ).MustExist().Click();

            // Click on the 'Add page' button (+ button)
            $( '.spm-page-button:eq( 1 )' ).MustBeVisible().Click();

            // Enter or select the form values
            probe.SPM.Helpers.setValues( input.values );

            // Click the 'Save' button
            $( '[data-spm-action="save"]' ).MustBeVisible().Click();
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
        }
    };
} )( jQuery, swiftyProbe );