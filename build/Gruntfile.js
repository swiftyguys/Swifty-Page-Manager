module.exports = function( grunt ) {

    // dorh TODO in mycfg.json: uglify, csslint, cssmin, shell

    grunt.myCfg = grunt.file.readJSON( 'mycfg.json' );
    grunt.myPkg = grunt.file.readJSON( 'package.json' );
//    console.log( 'aaa', grunt.myCfg );

    grunt.getSourcePath = function() {
        return grunt.myCfg.base_path;
    };
    grunt.getDestBasePath = function() {
        return 'generated/dist/';
    };
    grunt.getDestPath = function() {
//        return grunt.getDestBasePath() + '<%= pkg.version %>';
        return grunt.getDestBasePath() + grunt.myPkg.version;
    };
    grunt.getDestPathPlugin = function() {
        return grunt.getDestPath() + '/' + grunt.myCfg.plugin_code + '/';
    };
    grunt.getDestZip = function() {
//        return grunt.getDestBasePath() + '<%= pkg.name %>_<%= pkg.version %>.zip';
        return grunt.getDestBasePath() + grunt.myPkg.name + '_' + grunt.myPkg.version + '.zip';
    };

    // Project configuration.
    grunt.initConfig( {
        pkg: grunt.file.readJSON( 'package.json' )
    } );

    // Load 'helpers' from the grunt dir and load plugins defined in package.json.
    require( 'load-grunt-config' )( grunt );

    // Helper tasks.
    grunt.registerTask( 'if_requirejs', function() {
        if( grunt.myCfg.requirejs.do ) {
            grunt.task.run( [
                'requirejs'
            ] );
        }
    } );

    grunt.registerTask( 'if_rename', function() {
        if( grunt.myCfg.rename.do ) {
            grunt.task.run( [
                'rename:post_requirejs'
            ] );
        }
    } );

    grunt.registerTask( 'if_clean_unwanted', function() {
        if( process.env.PROBE === 'none' ) {
            grunt.task.run( [
                'clean:unwanted'
            ] );
        }
    } );

    grunt.registerTask( 'check_changelog', function() {
        var fileContent = grunt.file.read( grunt.getSourcePath() + 'readme.txt' );
        if( fileContent.indexOf( grunt.myPkg.version ) < 0 ) {
            grunt.fatal( "\n\n========================================\n\nREADME FILE DOES NOT CONTAIN CHANGELOG FOR " + grunt.myPkg.version + "!!!!!!!!!!!!!!\n\n========================================\n\n\n" );
        }
    } );
};