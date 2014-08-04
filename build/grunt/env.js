module.exports = function( grunt/*, options*/ ) {
    return {
        dist : {
            PROBE: 'none', // 'none', 'include'
            RELEASE_TAG: grunt.myPkg.version,
            BUILDUSE: 'build'
        },
        test : {
            PROBE: 'include',
            RELEASE_TAG: grunt.myPkg.version,
            BUILDUSE: 'source'
        }
    };
};