module.exports = function( grunt/*, options*/ ) {
    return {
        dist: [ '<%= grunt.getDestPath() %>', '<%= grunt.getDestZip() %>' ],
        unwanted: grunt.myCfg.clean.unwanted, // [ '<%= grunt.getDestPathPlugin() %>js/probe' ]
        svn: [ 'svn' ],
        svn_trunk: [ 'svn/' + grunt.myCfg.plugin_code + '/trunk' ]
    };
};