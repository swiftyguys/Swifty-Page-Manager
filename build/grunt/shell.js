module.exports = function( grunt/*, options*/ ) {
    return {
        test1: {
            command: '~/storyplayer/vendor/bin/storyplayer' +
                     ' -D relpath="../build/<%= grunt.getDestPath() %>"' +
                     ' -D platform=ec2' +
//                         ' -D wp_version=3.7' +
                     ' -D wp_version=3.9.1' +
                     ' -D lang=en' +
                     ' -d sl_ie9_win7' +
                     ' test_dist.php',
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
        },
        svn_co: {
            command: 'svn co http://plugins.svn.wordpress.org/swifty-page-manager/ svn/swifty-page-manager',
            options: {
                execOptions: {
                    cwd: '../build/'
                },
                'callback': function(err, stdout, stderr, cb) {
                    cb();
                }
            }
        },
        svn_stat: {
            command: 'svn stat',
            options: {
                execOptions: {
                    cwd: 'svn/swifty-page-manager/'
                },
                'callback': function(err, stdout, stderr, cb) {
                    cb();
                }
            }
        },
        svn_add: {
            command: 'svn status | grep "^\\?" | sed -e \'s/? *//\' | sed -e \'s/ /\\\\ /g\' | xargs svn add',
            options: {
                execOptions: {
                    cwd: 'svn/swifty-page-manager/'
                },
                'callback': function(err, stdout, stderr, cb) {
                    cb();
                }
            }
        },
        svn_ci: {
            command: 'svn ci -m "v' + grunt.myPkg.version + '" --username "SwiftyLife" --force-interactive',
            options: {
                execOptions: {
                    cwd: 'svn/swifty-page-manager/'
                },
                'callback': function(err, stdout, stderr, cb) {
                    cb();
                }
            }
        },
        svn_cp_trunk: {
            command: 'svn cp trunk tags/' + grunt.myPkg.version,
            options: {
                execOptions: {
                    cwd: 'svn/swifty-page-manager/'
                },
                'callback': function(err, stdout, stderr, cb) {
                    cb();
                }
            }
        },
        svn_ci_tags: {
            command: 'svn ci -m "Tagging version ' + grunt.myPkg.version + '" --username "SwiftyLife" --force-interactive',
            options: {
                execOptions: {
                    cwd: 'svn/swifty-page-manager/'
                },
                'callback': function(err, stdout, stderr, cb) {
                    cb();
                }
            }
        },
        svn_check_tags: {
            command: 'svn ls ' + grunt.myCfg.svn_check_tags.url /*'svn ls http://plugins.svn.wordpress.org/swifty-page-manager/tags'*/,
            options: {
                stdout: false,
                execOptions: {
                    cwd: 'svn/' + grunt.myCfg.plugin_code + '/'
                },
                'callback': function(err, stdout, stderr, cb) {
                    if( stdout.indexOf( grunt.myPkg.version ) >= 0 ) {
                        grunt.fatal( "\n\n========================================\n\nCURRENT RELEASETAG ALREADY EXISTS IN SVN " + grunt.myPkg.version + "!!!!!!!!!!!!!!\n\n========================================\n\n\n" );
                    }
                    cb();
                }
            }
        },
        git_check_status: {
            command: 'git status',
            options: {
                stdout: false,
                execOptions: {
                },
                'callback': function(err, stdout, stderr, cb) {
                    if( stdout.indexOf( 'nothing to commit' ) < 0 ) {
                        grunt.fatal( "\n\n========================================\n\nGIT HAS UNCOMITTED FILES. PLEASE COMMIT FIRST!!!!!!!!!!!!!!\n\n========================================\n\n\n" );
                    }
                    cb();
                }
            }
        },
        git_tag: {
            command: 'git tag v' + grunt.myPkg.version,
            options: {
                execOptions: {
                },
                'callback': function(err, stdout, stderr, cb) {
                    cb();
                }
            }
        },
        git_push_tags: {
            command: 'git push origin --tags',
            options: {
                execOptions: {
                },
                'callback': function(err, stdout, stderr, cb) {
                    cb();
                }
            }
        },
        send_mail: {
            command: 'echo "<%= myTask.send_mail_msg %>" | mail -s "' + grunt.myPkg.description + ' - new build" ' + grunt.myCfg.send_mail.to,
            options: {
                execOptions: {
                },
                'callback': function(err, stdout, stderr, cb) {
                    cb();
                }
            }
        },
        upload_gdrive: {
            command: 'gdrive upload -f ' + grunt.getDestZip() + ' -p 0B9usKfgdtpwIZ3VFcVpZYmY5VUU --share',
            options: {
                execOptions: {
                },
                'callback': function(err, stdout, stderr, cb) {
                    var s = '';
                    var sc = 'readable by everyone @ ';
                    var i = stdout.indexOf( sc );
                    if( i > 0 ) {
                        s = stdout.substr( i + sc.length );
                        grunt.config.set(
                            'myTask.send_mail_msg',
                            'A new plugin was build on Cactus:\n\n' +
                                grunt.myPkg.description + '\n' +
                                'Version: ' + grunt.myPkg.version + '\n\n' +
                                'This is NOT a public release. For testing only.\n\n' +
                                'Downdload: \n' +
                                s
                        );
                        grunt.task.run( 'shell:send_mail' );
                    }
                    cb();
                }
            }
        }
    };
};