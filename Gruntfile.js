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
			tscss: '<%= project.src %>/scss/themes',
			adminassets: 'includes/admin/ui',
			admincss: '<%= project.adminassets %>/css',
			adminscss: '<%= project.src %>/scss/admin'
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
            },
            themeprod: {
                src: '<%= project.tcss %>/default.css'
            },
            adminprod: {
                src: '<%= project.admincss %>/admin.css'
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
			},
			themedev: {
				files: {
					'<%= project.tcss %>/default.css': '<%= project.tscss %>/default.scss'
				},
				options: {
					sourceMap: true,
					outputStyle: 'expanded',
					imagePath: 'images/'
				}
			},
			themeprod: {
				files: {
					'<%= project.tcss %>/default.css': '<%= project.tscss %>/default.scss'
				},
				options: {
					sourceMap: false,
					outputStyle: 'compressed',
					imagePath: 'images/'
				}
			},
			admindev: {
				files: {
					'<%= project.admincss %>/admin.css': '<%= project.adminscss %>/admin.scss'
				},
				options: {
					sourceMap: true,
					outputStyle: 'expanded',
					imagePath: 'images/'
				}
			},
			adminprod: {
				files: {
					'<%= project.admincss %>/admin.css': '<%= project.adminscss %>/admin.scss'
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
				files: ['<%= project.src %>/scss/{,*/}*.{scss,sass}'],
				tasks: ['sass:frontdev', 'sass:themedev', 'sass:admindev']
			}
		},

	});

	// Grunt default task (used for dev)
	grunt.registerTask('default', [
		'watch'
	]);

	// Grunt task to compress CSS files
	grunt.registerTask('ccss', [
		'sass:frontprod',
		'postcss:frontprod',
		'sass:themeprod',
		'postcss:themeprod'
	]);

	// Grunt task to compress admin CSS files
	grunt.registerTask('accss', [
		'sass:adminprod',
		'postcss:adminprod'
	]);

	// Grun task to prepare MP to release (styles, js & pot)
	grunt.registerTask('release', [
		'sass:frontprod',
		'postcss:frontprod',
		'sass:themeprod',
		'postcss:themeprod',
		'sass:adminprod',
		'postcss:adminprod',
		'makepot'
	]);

};
