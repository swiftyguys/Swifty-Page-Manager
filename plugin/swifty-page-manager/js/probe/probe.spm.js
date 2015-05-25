( function( $, probe ) {
    probe.SPM = probe.SPM || {};
    var spm = probe.SPM;

    ////////////////////////////////////////

    probe.SPM.OpenSPM = function( input ) {
        return probe.QueueStory(
            'WP.AdminOpenSubmenu',
            {
                'plugin_code': 'pages',
                'submenu_text': input.plugin_name
            }
        );
    };

    ////////////////////////////////////////

    probe.SPM.SavePage = {
        running: false,
        titleLabel: 'span:contains("Title")',   // dojh: translation issue -> Title.
        titleEdit: 'input[name="post_title"]',
        saveButton: 'input[value="Save"]',   // dojh: translation issue -> Save.
        moreButton: 'input[value="More"]',   // dojh: translation issue -> Save.
        statusLabel: 'span:contains("Title")',   // dojh: translation issue -> Title.

        Start: function( input ) {
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
            var dfds = probe.NewDfds();

            dfds.add( probe.Utils.setValues( input.values, 'post_title' ) );

            if ( input.action === 'add' ) {
                dfds.add( probe.Utils.setValues( input.values, 'add_mode' ) );
            }

            dfds.add( probe.Utils.setValues( input.values, 'post_status' ) );
            dfds.add( probe.Utils.setValues( input.values, 'page_template' ) );

            probe.WaitForDfds( dfds, 'Step6', 60000 );
        },

        Step6: function( input ) {
            var $curPage = $( probe.Utils.getPageSelector( input.page ) );
            var $li = $curPage.closest( 'li' );

            // Click the 'Save' confirm button
            $li.find( this.saveButton ).MustBeVisible().Click();
        }
    };

    ////////////////////////////////////////

    probe.RegisterTry(
        'I see the SPM main page', {
            Start: function( input ) {
                $( 'h2:contains("' + input.plugin_name + '")' ).WaitForVisible( '' );
            }
        }
    );

    ////////////////////////////////////////

    probe.RegisterTry(
        /I see SPM mode "(.*)"/, {
            Start: function( input ) {
                if( input.ss_mode === 'WP' ) {
                    $( 'ul#adminmenu:visible' ).MustExistOnce();
                } else {
                    $( 'ul#adminmenu:visible' ).MustExistTimes( 0 );
                }
            }
        }, {
            'ss_mode': '{{match 0}}'
        }
    );

    ////////////////////////////////////////

    probe.RegisterTry(
        'I see the no pages message', {
            messageSel: '.spm-message',

            Start: function( input ) {
                spm.OpenSPM( input ).next( 'Step2' );
            },

            Step2: function( /*input*/ ) {
                $( this.messageSel ).WaitForVisible( 'Step3' );
            },

            Step3: function( /*input*/ ) {
                // dojh: translation issue -> No pages found.
                $( this.messageSel + ' p:contains("No pages found")' ).MustExistOnce();
            }
        }
    );

    ////////////////////////////////////////

    probe.RegisterTry(
        /I see (\d+) pages/, {
            Start: function( input ) {
                spm.OpenSPM( input ).next( 'Step2' );
            },

            Step2: function( /*input*/ ) {
                $( probe.Utils.getPageSelector() ).WaitForVisible( 'Step3' );
            },

            Step3: function( input ) {
                $( probe.Utils.getPageSelector() ).MustExistTimes( input.x_pages );
            }
        }, {
            'x_pages': '{{match 0}}'
        }
    );

    ////////////////////////////////////////

    probe.RegisterTry(
        /I drag page "(.*)" (before|after|inside) page "(.*)"/, {
            step_size: 38,
            dnd_done: false,

            Start: function( input ) {
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
                        setTimeout( function() {
                            self.dnd_done = true;
                        }, 1000 );
                    }
                } );

                $pageToMove.WaitForFn( 'Wait2', '' );
            },

            Wait2: function( /*input*/ ) {
                return { 'wait_result': this.dnd_done };
            }
        }, {
            'page': '{{match 0}}',
            'destination': '{{match 2}}',
            'position': '{{match 1}}'
        }
    );

    ////////////////////////////////////////

    probe.RegisterTry(
        /Page "(.*)" exist at pos (\d+)/, {
            Start: function( input ) {
                spm.OpenSPM( input ).next( 'Step2' );
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
        }, {
            'page': '{{match 0}}',
            'at_pos': '{{match 1}}'
        }
    );

    ////////////////////////////////////////

    probe.RegisterTry(
        /Page "(.*)" has a sub-page "(.*)"/, {
            Start: function( input ) {
                spm.OpenSPM( input ).next( 'Step2' );
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
        }, {
            'page': '{{match 0}}',
            'sub_page': '{{match 1}}'
        }
    );

    ////////////////////////////////////////

    probe.RegisterTry(
        /Page "(.*)" has status "(.*)"/, {
            Start: function( input ) {
                spm.OpenSPM( input ).next( 'Step2' );
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
        }, {
            'page': '{{match 0}}',
            'is_status': '{{match 1}}'
        }
    );

    ////////////////////////////////////////

    probe.RegisterTry(
        /I add a page after page "(.*)" WITH PARAMS (.*)/,
        'SPM.SavePage', {
            'action': 'add',
            'page': '{{match 0}}',
            'values': '{{match 1}}'
        }
    );

    ////////////////////////////////////////

    probe.RegisterTry(
        /I edit the options of page "(.*)" WITH PARAMS (.*)/,
        'SPM.SavePage', {
            'action': 'edit',
            'page': '{{match 0}}',
            'values': '{{match 1}}'
        }
    );

    ////////////////////////////////////////

    probe.RegisterTry(
        /I delete page "(.*)"/, {
            deleteButton: 'input[value="Delete"]',   // dojh: translation issue -> Delete.

            Start: function( input ) {
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
        }, {
            'page': '{{match 0}}'
        }
    );

    ////////////////////////////////////////

    probe.RegisterTry(
        /I publish page "(.*)"/, {
            publishButton: 'input[value="Publish"]',   // dojh: translation issue -> Publish.

            Start: function( input ) {
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
        }, {
            'page': '{{match 0}}'
        }
    );

    ////////////////////////////////////////

    probe.RegisterTry(
        /I click the edit content button for page "(.*)"/, {
            Start: function( input ) {
                $( probe.Utils.getPageSelector( input.page ) ).WaitForVisible( 'Step3' );
            },

            Step3: function( input ) {
                // Click to see the page buttons
                $( probe.Utils.getPageSelector( input.page ) ).MustExist().Click();

                // Click on the 'Edit page content' button
                $( '.spm-page-button:eq(3)' ).MustBeVisible().Click();
            }
        }, {
            'page': '{{match 0}}'
        }
    );

} )( jQuery, swiftyProbe );