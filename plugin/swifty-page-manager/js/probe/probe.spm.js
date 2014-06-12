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

    probe.SPM.CreatePageAfterLastPage = {
        lastPageSel: '.spm-page-tree-element:last',
        addBtnSel: '.spm-page-button:first',
        postTitleSel: 'input[name="post_title"]',
        saveBtnSel: '[data-spm-action="save"]',
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
            $( this.lastPageSel ).WaitForVisible( 'Step3' );
        },

        Step3: function( input ) {
            // Click to see the page buttons
            $( this.lastPageSel ).MustExist().Click();

            // Click on the 'Add page' button (+ button)
            $( this.addBtnSel ).MustBeVisible().Click();

            // Enter a value into the post_type input field
            $( this.postTitleSel ).val( 'SPM Page' );

            // Click the 'Save' button
            $( this.saveBtnSel ).MustBeVisible().Click();

            probe.QueueStory(
                'WP.AdminOpenSubmenu',
                {
                    'plugin_code': 'pages',
                    'submenu_text': input.plugin_name
                },
                'Step4'
            );
        },

        Step4: function( /*input*/ ) {
            $( this.pageLiSel ).WaitForVisible( 'Step5' );
        },

        Step5: function( /*input*/ ) {
            $( this.pageLiSel + ' a:contains("SPM Page")' ).MustExistTimes( 1 );
            $( this.pageLiSel + ' a:contains("Draft")' ).MustExistTimes( 3 );
        }
    };

    ////////////////////////////////////////

} )( jQuery, swiftyProbe );