module.exports = function( grunt ) {

    grunt.getSourcePath = function() {
        return '../plugin/swifty-page-manager/';
    }
    grunt.getDestBasePath = function() {
        return 'generated/dist/';
    }
    grunt.getDestPath = function() {
        return grunt.getDestBasePath() + '<%= pkg.version %>';
    }
    grunt.getDestPathSPM = function() {
        return grunt.getDestPath() + '/swifty-page-manager/';
    }
    grunt.getDestZip = function() {
        return grunt.getDestBasePath() + '<%= pkg.name %>_<%= pkg.version %>.zip';
    }

    // Project configuration.
    grunt.initConfig( {
        pkg: grunt.file.readJSON( 'package.json' ),
        env : {
            dist : {
                PROBE: 'none' // 'none', 'include'
            },
            test : {
                PROBE: 'include'
            }
        },
        clean: {
            dist: [ "<%= grunt.getDestPath() %>", "<%= grunt.getDestZip() %>" ],
            probe: [ '<%= grunt.getDestPathSPM() %>js/probe' ]
        },
        copy: {
            dist: {
                files: [
                    { expand: true, cwd: '<%= grunt.getSourcePath() %>', src: [ '**' ], dest: '<%= grunt.getDestPathSPM() %>' }
                ]
            }
        },
        preprocess: {
            dist: {
                src: '<%= grunt.getSourcePath() %>swifty-page-manager.php',
                dest: '<%= grunt.getDestPathSPM() %>swifty-page-manager.php'
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
                    '<%= grunt.getDestPathSPM() %>js/swifty-page-manager.min.js': [ '<%= grunt.getSourcePath() %>js/swifty-page-manager.js' ],
                    '<%= grunt.getDestPathSPM() %>js/jquery.alerts.min.js': [ '<%= grunt.getSourcePath() %>js/jquery.alerts.js' ],
                    '<%= grunt.getDestPathSPM() %>js/jquery.biscuit.min.js': [ '<%= grunt.getSourcePath() %>js/jquery.biscuit.js' ],
                    '<%= grunt.getDestPathSPM() %>js/jquery.jstree.min.js': [ '<%= grunt.getSourcePath() %>js/jquery.jstree.js' ]
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
                    '<%= grunt.getDestPathSPM() %>css/styles.min.css': [ '<%= grunt.getSourcePath() %>css/styles.css' ],
                    '<%= grunt.getDestPathSPM() %>css/jquery.alerts.min.css': [ '<%= grunt.getSourcePath() %>css/jquery.alerts.css' ]
                }
            }
        },
        compress: {
            dist: {
                options: {
                    archive: '<%= grunt.getDestZip() %>',
                    mode: 'zip',
                    pretty: true
                },
                files: [
                    { expand: true, cwd: '<%= grunt.getDestPath() %>/', src: ['**'], dest: '' }
                ]
            }
        },
        shell: {
            test1: {
                command: '~/storyplayer/vendor/bin/storyplayer' +
                         ' -D relpath="../build/<%= grunt.getDestPath() %>"' +
                         ' -D platform=ec2' +
//                         ' -D wp_version=3.7' +
                         ' -D wp_version=3.9.1' +
                         ' -D lang=en' +
                         ' -d sl_ie9_win7' +
                         ' test.php',
                options: {
                    stderr: false,
                    execOptions: {
                        cwd: '../test/'
                    },
                    'callback': function(err, stdout, stderr, cb) {
                        if( stderr.indexOf( "action: COMPLETED" ) >= 0 ) {
                            console.log( "\n\n========================================\nTEST SUCCESFUL\n========================================\n\n\n" );
                        } else {
                            grunt.fatal( "\n\n========================================\n\nTEST FAILED!!!!!!!!!!!!!!\n\n" + stderr + "\n\n========================================\n\n\n" );
                        }
                        cb();
                    }
                }
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
    grunt.loadNpmTasks( 'grunt-shell' );

    // Helper tasks.
    grunt.registerTask( 'clean_probe', function() {
        if( process.env.PROBE === 'none' ) {
            grunt.task.run( [
                'clean:probe'
            ] );
        }
    } );

    // Main tasks.
    grunt.registerTask( 'main_build', [
        'clean:dist',
        'copy',
        'clean_probe',
        'preprocess',
        'uglify',
        'cssmin'
    ] );

    grunt.registerTask( 'build_and_test', [
        'env:test',
        'main_build',
        'shell:test1'
    ] );

    grunt.registerTask( 'build_dist', [
        'env:dist',
        'main_build',
        'compress'
    ] );

    // Default task.
    grunt.registerTask( 'default', [
        'build_and_test',
        'build_dist'
    ] );

};