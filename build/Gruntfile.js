module.exports = function( grunt ) {

    // Project configuration.
    grunt.initConfig( {
        pkg: grunt.file.readJSON( 'package.json' ),
        env : {
            dist : {
                PROBE: 'none' // 'none', 'include'
            }
        },
        clean: {
            dist: [ "generated/dist/<%= pkg.version %>" ],
            probe: [ 'generated/dist/<%= pkg.version %>/swifty-page-manager/js/probe' ]
        },
        copy: {
            dist: {
                files: [
                    { expand: true, cwd: '../plugin/swifty-page-manager/', src: [ '**' ], dest: 'generated/dist/<%= pkg.version %>/swifty-page-manager/' }
                ]
            }
        },
        preprocess: {
            dist: {
                src: '../plugin/swifty-page-manager/swifty-page-manager.php',
                dest: 'generated/dist/<%= pkg.version %>/swifty-page-manager/swifty-page-manager.php'
            }
        },
        uglify: {
            options: {
//                banner: '/*! Minified for <%= pkg.name %> <%= grunt.template.today("yyyy-mm-dd") %> */\n',
                compress: {
                    drop_console: true
                },
                report: 'gzip'
            },
            dist: {
                files: {
                    'generated/dist/<%= pkg.version %>/swifty-page-manager/js/swifty-page-manager.min.js': [ '../plugin/swifty-page-manager/js/swifty-page-manager.js' ],
                    'generated/dist/<%= pkg.version %>/swifty-page-manager/js/jquery.alerts.min.js': [ '../plugin/swifty-page-manager/js/jquery.alerts.js' ],
                    'generated/dist/<%= pkg.version %>/swifty-page-manager/js/jquery.biscuit.min.js': [ '../plugin/swifty-page-manager/js/jquery.biscuit.js' ],
                    'generated/dist/<%= pkg.version %>/swifty-page-manager/js/jquery.jstree.min.js': [ '../plugin/swifty-page-manager/js/jquery.jstree.js' ]
                }
            }
        },
        cssmin: {
            options: {
//                banner: '/*! Minified for <%= pkg.name %> <%= grunt.template.today("yyyy-mm-dd") %> */\n',
                report: 'gzip'
            },
            dist: {
                files: {
                    'generated/dist/<%= pkg.version %>/swifty-page-manager/css/styles.min.css': [ '../plugin/swifty-page-manager/css/styles.css' ],
                    'generated/dist/<%= pkg.version %>/swifty-page-manager/css/jquery.alerts.min.css': [ '../plugin/swifty-page-manager/css/jquery.alerts.css' ]
                }
            }
        },
        compress: {
            dist: {
                options: {
                    archive: 'generated/dist/<%= pkg.name %>_<%= pkg.version %>.zip',
                    mode: 'zip',
                    pretty: true
                },
                files: [
                    { expand: true, cwd: 'generated/dist/<%= pkg.version %>/', src: ['**'], dest: '' }
                ]
            }
        }
    } );

    // Load plugins.
    grunt.loadNpmTasks( 'grunt-contrib-clean' );
    grunt.loadNpmTasks( 'grunt-contrib-copy' );
    grunt.loadNpmTasks( 'grunt-contrib-uglify' );
    grunt.loadNpmTasks( 'grunt-contrib-cssmin' );
    grunt.loadNpmTasks( 'grunt-contrib-compress' );
    grunt.loadNpmTasks( 'grunt-preprocess' );
    grunt.loadNpmTasks( 'grunt-env' );

    // Helper tasks.
    grunt.registerTask( 'clean_probe', function() {
        if( process.env.PROBE === 'none' ) {
            grunt.task.run( [
                'clean:probe'
            ] );
        }
    } );

    // Default tasks.
    grunt.registerTask( 'default', [
        'env',
        'clean:dist',
        'copy',
        'clean_probe',
        'preprocess',
        'uglify',
        'cssmin',
        'compress'
    ] );

};