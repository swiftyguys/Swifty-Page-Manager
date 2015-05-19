( function( $, probe ) {
    probe.SPM = probe.SPM || {};
    var spm = probe.SPM;

    $.extend( probe.SPM, {

        OpenSPM: function( input ) {
            return probe.QueueStory(
                'WP.AdminOpenSubmenu',
                {
                    'plugin_code': 'pages',
                    'submenu_text': input.plugin_name
                }
            );
        },

        CheckRunning: function( input ) {
            spm.OpenSPM( input ).next( function( input ) {
                $( 'h2:contains("' + input.plugin_name + '")' ).MustExistOnce();
            } );
        }

    } );

    probe.SPM.NoPagesExist = {
        messageSel: '.spm-message',

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
            $( this.messageSel ).WaitForVisible( 'Step3' );
        },

        Step3: function( /*input*/ ) {
            // dojh: translation issue -> No pages found.
            $( this.messageSel + ' p:contains("No pages found")' ).MustExistOnce();
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
            $( probe.Utils.getPageSelector() ).WaitForVisible( 'Step3' );
        },

        Step3: function( input ) {
            $( probe.Utils.getPageSelector() ).MustExistTimes( input.x_pages );
        }
    };

    ////////////////////////////////////////

    probe.SPM.SavePage = {
        running: false,
        titleLabel: 'span:contains("Title")',   // dojh: translation issue -> Title.
        titleEdit: 'input[name="post_title"]',
        saveButton: 'input[value="Save"]',   // dojh: translation issue -> Save.
        moreButton: 'input[value="More"]',   // dojh: translation issue -> Save.
        statusLabel: 'span:contains("Title")',   // dojh: translation issue -> Title.
        dfd_done: false,

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
            $( probe.Utils.getPageSelector( input.page ) ).WaitForVisible( 'Step3' );
        },

        Step3: function( input ) {
            var $curPage = $( probe.Utils.getPageSelector( input.page ) );
            var $li = $curPage.closest( 'li' );
            var buttonNr = input.action === 'add' ? 0 : 1;

            // Click to see the page buttons
            $curPage.MustExist().Click();

            // Click on the 'Add page' button (+ button)
            $li.find( '.spm-page-button:eq(' + buttonNr + ')' ).MustBeVisible().Click();

            $li.find( this.titleLabel ).WaitForVisible( 'Step4' );
        },

        Step4: function( input ) {
            var $curPage = $( probe.Utils.getPageSelector( input.page ) );
            var $li = $curPage.closest( 'li' );
            var $more = $li.find( this.moreButton );

            // In Swifty mode the More button must be clicked first
            if( $more.length > 0 ) {
                $more.Click();
            }

            $li.find( this.statusLabel ).WaitForVisible( 'Step5' );
        },

        Step5: function( input ) {
            var self = this;
            var dfdArray = [];

            dfdArray.push( probe.Utils.setValues( input.values, 'post_title' ) );

            if ( input.action === 'add' ) {
                dfdArray.push( probe.Utils.setValues( input.values, 'add_mode' ) );
            }

            dfdArray.push( probe.Utils.setValues( input.values, 'post_status' ) );
            dfdArray.push( probe.Utils.setValues( input.values, 'page_template' ) );

            $.when.apply( $, dfdArray ).done( function () {
                self.dfd_done = true;
            } );

            probe.WaitForFn( 'Wait', 'Step6', 60000 );
        },

        Wait: function( /*input*/ ) {
            return { 'wait_result': this.dfd_done };
        },

        Step6: function( input ) {
            var $curPage = $( probe.Utils.getPageSelector( input.page ) );
            var $li = $curPage.closest( 'li' );

            // Click the 'Save' confirm button
            $li.find( this.saveButton ).MustBeVisible().Click();
        }
    };

    ////////////////////////////////////////

    probe.SPM.MovePage = {
        step_size: 38,
        dnd_done: false,

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
            $( probe.Utils.getPageSelector( input.page ) ).WaitForVisible( 'Step3' );
        },

        Step3: function( input ) {
            var self = this;
            var $allPages = $( probe.Utils.getPageSelector() );
            var $pageToMove = $( probe.Utils.getPageSelector( input.page ) );
            var pageToMoveIndex = $allPages.index( $pageToMove );
            var destinationIndex = $allPages.index( $( probe.Utils.getPageSelector( input.destination ) ) );
            var multiplier = destinationIndex;
            var yMove;

            if ( pageToMoveIndex < destinationIndex ) {
                multiplier = destinationIndex - pageToMoveIndex;

                if ( input.position === 'before' ) {
                    multiplier--;
                }
            } else {
                multiplier = pageToMoveIndex - destinationIndex;

                if ( input.position === 'after' ) {
                    multiplier--;
                }

                multiplier *= -1;
            }

            yMove = multiplier * this.step_size;

            if ( input.position === 'inside' ) {
                yMove--;
            }

            $pageToMove.simulate( 'drag-n-drop', {
                'dy': yMove,
                'interpolation': {
                    'stepWidth': 2,
                    'stepDelay': 5
                },
                'callback': function() {
                    self.dnd_done = true;
                }
            } );

            $pageToMove.WaitForFn( 'Wait2', 'Step4' );
        },

        Wait2: function( /*input*/ ) {
            return { 'wait_result': this.dnd_done };
        },

        Step4: function( /*input*/ ) {
            probe.TmpLog( 'Drag-n-Drop finished' );
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

        Step2: function( input ) {
            $( probe.Utils.getPageSelector( input.page ) ).WaitForVisible( 'Step3' );
        },

        Step3: function( input ) {
            var $page = $( probe.Utils.getPageSelector( input.page ) );
            var actualPos;

            if ( input.x_pages ) {
                $page.MustExistTimes( input.x_pages );
            }

            if ( typeof input.at_pos === 'number' ) {
                actualPos = $( probe.Utils.getPageSelector() ).index( $page ) + 1;

                if ( actualPos !== input.at_pos ) {
                    probe.SetFail( 'Element exists on position ' + actualPos +
                                   ' and not on the expected position ' + input.at_pos );
                }
            }
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

        Step2: function( input ) {
            $( probe.Utils.getPageSelector( input.page ) ).WaitForVisible( 'Step3' );
        },

        Step3: function( input ) {
            if( input.is_status === 'publish' ) {
                var $statusSpan = $( probe.Utils.getPageSelector( input.page ) ).MustExist().find( '.post_type_draft' );
                if( $statusSpan.length > 0 ) {
                    probe.SetFail( 'Page status does not equals the expected status "' + input.is_status + '"' );
                }
            } else {
                var status = input.is_status === 'publish' ? '' : input.is_status;
                var $statusSpan = $( probe.Utils.getPageSelector( input.page ) ).MustExist().find( ':nth-child( 2 )' );
                var text = '';

                if( $statusSpan.is( 'span' ) ) {
                    text = $.trim( $statusSpan.text().toLowerCase() );
                }

                if( status !== text ) {
                    probe.SetFail( 'Page status does not equals the expected status "' + input.is_status + '"' );
                }
            }
        }
    };

    ////////////////////////////////////////

    probe.SPM.DeletePage = {
        deleteButton: 'input[value="Delete"]',   // dojh: translation issue -> Delete.

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
            $( probe.Utils.getPageSelector( input.page ) ).WaitForVisible( 'Step3' );
        },

        Step3: function( input ) {
            // Click to see the page buttons
            $( probe.Utils.getPageSelector( input.page ) ).MustExist().Click();

            // Click on the 'Delete page' button
            $( '.spm-page-button:eq(2)' ).MustBeVisible().Click();

            $( this.deleteButton ).WaitForVisible( 'Step4' );
        },

        Step4: function( input ) {
            // Click the 'Delete' confirm button
            $( this.deleteButton ).MustBeVisible().Click();
        }
    };

    ////////////////////////////////////////

    probe.SPM.PublishPage = {
        publishButton: 'input[value="Publish"]',   // dojh: translation issue -> Publish.

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
            $( probe.Utils.getPageSelector( input.page ) ).WaitForVisible( 'Step3' );
        },

        Step3: function( input ) {
            // Click to see the page buttons
            $( probe.Utils.getPageSelector( input.page ) ).MustExist().Click();

            // Click on the 'Publish page' button
            $( '.spm-page-button:eq(5)' ).MustBeVisible().Click();

            $( probe.Utils.getPageSelector( input.page ) ).WaitForFn( 'Wait3', 'Step4', 1000, new Date().getTime() + 800 );
        },

        Wait3: function( input ) {

            // has the time come to continue?
            var check = input.wait_data < new Date().getTime();
            return { 'wait_result': check };
        },

        Step4: function( input ) {

            // Click the 'Publish' confirm button
            $( this.publishButton ).MustExist().Click();
        }
    };

    ////////////////////////////////////////

    probe.SPM.EditPageContent = {
        running: false,
        titleLabel: 'label:contains("Enter title here")',   // dojh: translation issue -> Enter title here.

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
            $( probe.Utils.getPageSelector( input.page ) ).WaitForVisible( 'Step3' );
        },

        Step3: function( input ) {
            // Click to see the page buttons
            $( probe.Utils.getPageSelector( input.page ) ).MustExist().Click();

            // Click on the 'Edit page content' button
            $( '.spm-page-button:eq(3)' ).MustBeVisible().Click();

            $( 'h2:contains("Edit Page")' ).WaitForVisible( 'Step4' );   // dojh: translation issue -> Edit Page.
        },

        Step4: function( input ) {
            var $titleLabel = $( this.titleLabel );
            var fields = probe.Utils.getFieldProps( input.values );

            if ( input.wait_data && input.wait_data === fields.post_title.value ) {
                probe.Utils.setValues( input.values );

                // Click the 'Save' confirm button
                $( 'input[value="Save Draft"]' )   // dojh: translation issue -> Save Draft.
                    .IfVisible()
                    .OtherIfNotVisible( 'input[value="Publish"]' )   // dojh: translation issue -> Publish.
                    .Click();
            } else {
                if ( !this.running ) {
                    this.running = true;

                    probe.Utils.setValues( input.values, 'post_title' );
                }

                $titleLabel.WaitForVisible( 'Step4', 5000, $titleLabel.next( 'input' ).val() );
            }
        }
    };

    ////////////////////////////////////////

    probe.SPM.SubPageExist = {
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
            $( probe.Utils.getPageSelector( input.page ) ).WaitForVisible( 'Step3' );
        },

        Step3: function( input ) {
            var $li = $( probe.Utils.getPageSelector( input.page ) ).closest( 'li' );

            if ( $li.find( '> ul' ).length ) {
                if ( ! $li.find( '> ul' ).IsVisible() ) {
                    $li.find( '> ins' ).MustBeVisible().Click();
                }

                $( probe.Utils.getPageSelector( input.sub_page ) ).MustExistOnce();
            } else {
                probe.SetFail( 'Page "' + input.page + '" does not have a sub page.' );
            }
        },

        Step4: function( input ) {
            var $page = $( probe.Utils.getPageSelector( input.page ) );
            var actualPos;

            if ( input.x_pages ) {
                $page.MustExistTimes( input.x_pages );
            }

            if ( typeof input.at_pos === 'number' ) {
                actualPos = $( probe.Utils.getPageSelector() ).index( $page ) + 1;

                if ( actualPos !== input.at_pos ) {
                    probe.SetFail( 'Element exists on position ' + actualPos +
                                   ' and not on the expected position ' + input.at_pos );
                }
            }
        }
    };

    ////////////////////////////////////////

} )( jQuery, swiftyProbe );