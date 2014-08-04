module.exports = function( grunt/*, options*/ ) {
    return {
        dist: {
            src: [ '<%= grunt.getSourcePath() %>readme.txt' ],
            dest: '<%= grunt.getDestPathPlugin() %>readme.txt',
            replacements: [ {
                    from: 'RELEASE_TAG',
                    to: grunt.myPkg.version
            } ]
        }
    };
};