module.exports = function( /*grunt, options*/ ) {
    return {
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
    };
};