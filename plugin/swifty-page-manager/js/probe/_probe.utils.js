( function( $, probe ) {
    probe.Utils = probe.Utils || {};

    $.extend( probe.Utils, {
        setValues: function ( values, key ) {
            var self = this;
            var fields = this.getFieldProps( values );

            if ( key ) {
                if ( fields[ key ] ) {
                    this.setSingleValue( key, fields[ key ] );
                }
            } else {
                $.each( fields, function ( field, props ) {
                    self.setSingleValue( field, props );
                } );
            }
        },

        setSingleValue: function ( field, props ) {
            var type = props.type;
            var val = props.value;
            var selector = '[name="' + field + '"]:visible';

            // Enter or select the form values
            switch ( type ) {
                case 'text':
                    $( selector ).simulate( 'click' );
                    $( selector ).simulate( 'key-sequence', {
                        'sequence': '{selectall}{del}' + val,
                        'delay': 50
                    } );

                    break;
                case 'radio':
                    selector += '[value="' + val + '"]';

                    $( selector ).simulate( 'click' );

                    break;
                case 'select':
                    selector += ' option:contains("' + val + '")';

                    $( selector ).prop( 'selected', true );

                    break;
            }
        },

        getFieldProps: function ( values ) {
            return JSON.parse( values );
        },

        getPageSelector: function ( input ) {
            var selector = '.spm-page-tree-element';

            if ( input && typeof input === 'string' ) {
                selector += ':contains("' + input + '")';
            }

            return selector;
        }
    } );

    ////////////////////////////////////////

} )( jQuery, swiftyProbe );