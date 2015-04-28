// DO NOT CHANGE THIS FILE AT ALL. Do changes in gruntfile_helper only.

module.exports = function( grunt ) {

    var gruntfileHelper = require('./grunt/gruntfile_helper');
    gruntfileHelper.init( grunt );

    // Load 'helpers' from the grunt dir and load plugins defined in package.json.
    require( 'load-grunt-config' )( grunt );

    gruntfileHelper.addHelpers( grunt );
};