( function( $, probe, Sel ) {
    probe.SPM = probe.SPM || {}

    probe.SPM.CheckRunning = {
        Start: function( input ) {
            probe.QueueStory( "WP.AdminOpenSubmenu", { "plugin_code": 'pages', "submenu_text": input.plugin_name }, ".Step2" );
        },

        Step2: function( input ) {
            Sel( "h2:contains('" + input.plugin_name + "')" )
                .MustExist();
        }
    }

    ////////////////////////////////////////

} )( jQuery, swiftyProbe, swiftyProbe.Sel );