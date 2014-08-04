module.exports = function( grunt/*, options*/ ) {
    return {
        options: {
//                banner: '/*! Minified for <%= pkg.name %> <%= grunt.template.today("yyyy-mm-dd") %> */\n',
            compress: {
                drop_console: true
            },
            report: 'gzip'
        },
        dist: {
            files: grunt.myCfg.uglify.src
// {
//                '<%= grunt.getDestPathPlugin() %>js/swifty-page-manager.min.js': [ '<%= grunt.getSourcePath() %>js/swifty-page-manager.js' ],
//                '<%= grunt.getDestPathPlugin() %>js/jquery.alerts.min.js': [ '<%= grunt.getSourcePath() %>js/jquery.alerts.js' ],
//                '<%= grunt.getDestPathPlugin() %>js/jquery.biscuit.min.js': [ '<%= grunt.getSourcePath() %>js/jquery.biscuit.js' ],
//                '<%= grunt.getDestPathPlugin() %>js/jquery.jstree.min.js': [ '<%= grunt.getSourcePath() %>js/jquery.jstree.js' ]
//            }
        }
    };
};