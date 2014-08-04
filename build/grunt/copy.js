module.exports = function( grunt/*, options*/ ) {
    return {
        dist: {
            files: [
                { expand: true, cwd: '<%= grunt.getSourcePath() %>', src: [ '**' ], dest: '<%= grunt.getDestPathPlugin() %>' }
            ]
        },
        svn: {
            files: [
                { expand: true, cwd: '<%= grunt.getDestPathPlugin() %>', src: [ '**' ], dest: 'svn/' + grunt.myCfg.plugin_code + '/trunk/' }
            ]
        },
        post_requirejs: {
            files: [
                { expand: true, cwd: '<%= grunt.getDestPathPlugin() %>js/', src: [ 'swifty-content-creator.css' ], dest: '<%= grunt.getDestPathPlugin() %>css/' }
            ]
        }
    };
};