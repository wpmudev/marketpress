/*global module, require */
module.exports = function(grunt) {

	// Load Grunt tasks
	grunt.loadNpmTasks('grunt-postcss');
	grunt.loadNpmTasks('grunt-sass');
	grunt.loadNpmTasks('grunt-wp-i18n');
	grunt.loadNpmTasks('grunt-contrib-watch');

	grunt.initConfig({

		// Read package meta data
		pkg: grunt.file.readJSON('package.json'),

		// Project variables
		project: {
			assets: 'ui',
			src: '<%= project.assets %>/src',
			fcss: '<%= project.assets %>/css',
			fscss: '<%= project.src %>/scss/front',
			tcss: '<%= project.assets %>/themes',
			tscss: '<%= project.src %>/scss/themes'
		},

		// PostCSS
		postcss: {
            options: {
                processors: [
                    require('autoprefixer')({
                        browsers: ['> 5%']['last 2 versions']
                    })
                ]
            },
            frontprod: {
                src: '<%= project.fcss %>/marketpress.css'
            }
        },

        // SASS
		sass: {
			frontdev: {
				files: {
					'<%= project.fcss %>/marketpress.css': '<%= project.fscss %>/marketpress.scss'
				},
				options: {
					sourceMap: true,
					outputStyle: 'expanded',
					imagePath: 'images/'
				}
			},
			frontprod: {
				files: {
					'<%= project.fcss %>/marketpress.css': '<%= project.fscss %>/marketpress.scss'
				},
				options: {
					sourceMap: false,
					outputStyle: 'compressed',
					imagePath: 'images/'
				}
			}
		},

		// Make POT
		makepot: {
			target: {
				options: {
					domainPath: 'languages/',
					type: 'wp-plugin'
				}
			}
		},

		// Watch
		watch: {
			sass: {
				files: '<%= project.src %>/scss/{,*/}*.{scss,sass}',
				tasks: ['sass:frontdev']
			}
		},

	});

	// Grunt default task (run `grunt`)
	grunt.registerTask('default', [
		'watch'
	]);

	grunt.registerTask('ccss', [
		'sass:frontprod',
		'postcss:frontprod'
	]);

	grunt.registerTask('release', [
		'sass:frontprod',
		'postcss:frontprod',
		'makepot'
	]);

};
