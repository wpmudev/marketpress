/*global module, require */
module.exports = function(grunt) {
	grunt.loadNpmTasks('grunt-wp-i18n');

	grunt.initConfig({
		makepot: {
			target: {
				options: {
					domainPath: 'languages/',
					type: 'wp-plugin'
				}
			}
		},
	});

	grunt.registerTask('default', ['makepot']);
};
