module.exports = function( grunt/*, options*/ ) {
    return {
        dist: {
            src: '<%= grunt.getDestPathPlugin() %>' + grunt.myCfg.plugin_code + '.php',
            dest: '<%= grunt.getDestPathPlugin() %>' + grunt.myCfg.plugin_code + '.php'
        }
    };
};