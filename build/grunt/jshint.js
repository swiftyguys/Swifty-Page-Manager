module.exports = function( grunt/*, options*/ ) {
    return {
        options: {
            jshintrc: '../.jshintrc',
            ignores: grunt.myCfg.jshint.ignores
    //                reporter: require( 'jshint-stylish' )
        },
        dist: grunt.myCfg.jshint.src
    };
};