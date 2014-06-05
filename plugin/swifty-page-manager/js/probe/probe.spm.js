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
            $( "h2:contains('" + input.plugin_name + "')" ).MustExist();
        }
    };

    ////////////////////////////////////////

    probe.SPM.CheckIfXPagesExist = {
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
            $( 'li[id^="spm-id-"]' ).WaitForVisible( 'Step3' );
        },

        Step3: function( input ) {
            $( 'li[id^="spm-id-"]' ).MustExistTimes( input.x_pages );
        }
    };

    ////////////////////////////////////////

} )( jQuery, swiftyProbe );