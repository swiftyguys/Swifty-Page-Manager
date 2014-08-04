module.exports = function( grunt/*, options*/ ) {
    return {
        dist: {
            force: true,
            options: {
                'unqualified-attributes': true,
                ids: false,
                'overqualified-elements': false,
                important: false,
                'adjoining-classes': false,
                'box-model': false,
                'qualified-headings': false,
                'fallback-colors': false,
                'box-sizing': false,
                'universal-selector': false // Could this throw a warning instead of error?
            },
            src: grunt.myCfg.csslint.src /*['<%= grunt.getSourcePath() %>css/styles.css']*/
        }
    };
};