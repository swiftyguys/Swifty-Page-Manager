module.exports = function( grunt/*, options*/ ) {
    return {
        options: {
//                banner: '/*! Minified for <%= pkg.name %> <%= grunt.template.today("yyyy-mm-dd") %> */\n',
            report: 'gzip'
        },
        dist: {
            files: grunt.myCfg.cssmin.src /*{
                '<%= grunt.getDestPathPlugin() %>css/styles.min.css': [ '<%= grunt.getSourcePath() %>css/styles.css' ],
                '<%= grunt.getDestPathPlugin() %>css/jquery.alerts.min.css': [ '<%= grunt.getSourcePath() %>css/jquery.alerts.css' ]
            }*/
        }
    };
};