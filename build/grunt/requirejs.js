module.exports = function( grunt/*, options*/ ) {
    return {
        dist: {
            options: {
                name: 'js/swcreator',
                baseUrl: '<%= grunt.getDestPathPlugin() %>',
                mainConfigFile: '<%= grunt.getDestPathPlugin() %>require_config.js',
                out: '<%= grunt.getDestPathPlugin() %>js/' + grunt.myCfg.plugin_code + '.js',

                include: [ 'requireLib' ], // Include RequireJs itself
                optimize: 'none',

                // css:
                exclude: [
                    'js/libs/css_normalize',
                    'js/libs/css-builder',
                    'js/libs/css_normalize'//,
//                    'js/libs/requirejs_plugin_css',
//                    'js/libs/requirejs_plugin_stache',
//                    'js/libs/requirejs_plugin_text',
//                    'js/libs/requirejs_plugin_json'
                ],
                stubModules : [ // So these will not be in the build
                    'json',
                    'text',
//                    'css', // Needed
                    'stache'
                ],
                separateCSS: true,
                pragmasOnSave: {
                    excludeRequireCss: false // can not be true because we have dynamically required css()fontawesome)
                }
            }
        }
    };
};