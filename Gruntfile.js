/*global module, require */
module.exports = function(grunt) {
	// Load all Grunt tasks
	require('load-grunt-tasks')(grunt);

	// Files to exclude during build process
	var includeCopyFiles = [
		'includes/**',
		'languages/**',
		'ui/**',
		'marketpress.php'
	];

	var includeCopyFilesDEV = includeCopyFiles.slice(0).concat( [ 'changelog.txt' ] );
	var includeCopyFilesWPorg = includeCopyFiles.slice(0).concat( [ '!includes/admin/dash-notice/**', 'readme.txt' ] );

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

		// Clean build directory
		clean: {
			main: ['build/']
		},

		checktextdomain: {
			options:{
				text_domain: 'mp',
				keywords: [
					'__:1,2d',
					'_e:1,2d',
					'_x:1,2c,3d',
					'esc_html__:1,2d',
					'esc_html_e:1,2d',
					'esc_html_x:1,2c,3d',
					'esc_attr__:1,2d',
					'esc_attr_e:1,2d',
					'esc_attr_x:1,2c,3d',
					'_ex:1,2c,3d',
					'_n:1,2,4d',
					'_nx:1,2,4c,5d',
					'_n_noop:1,2,3d',
					'_nx_noop:1,2,3c,4d'
				]
			},
			files: {
				src:  [
					'include/**/*.php',
					'ui/**/*.php',
					'marketpress.php'
				],
				expand: true
			}
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

		// Copy all the files
		copy: {
			main: {
				src:  includeCopyFilesDEV,
				dest: 'build/<%= pkg.name %>/'
			},
			wporg: {
				src:  includeCopyFilesWPorg,
				dest: 'build/wordpress-ecommerce/'
			}
		},

		// Prepare zip files
		compress: {
			main: {
				options: {
					mode: 'zip',
					archive: './build/<%= pkg.name %>-<%= pkg.version %>.zip'
				},
				expand: true,
				cwd: 'build/<%= pkg.name %>/',
				src: ['**/*'],
				dest: '<%= pkg.name %>/'
			},
			wporg: {
				options: {
					mode: 'zip',
					archive: './build/wordpress-ecommerce-<%= pkg.version %>.zip'
				},
				expand: true,
				cwd: 'build/wordpress-ecommerce/',
				src: ['**/*'],
				dest: 'wordpress-ecommerce/'
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

	grunt.registerTask('css', [
		'sass:frontdev',
		'sass:themedev',
		'sass:admindev',
		'postcss:frontprod',
		'postcss:themeprod',
		'postcss:adminprod'
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

	grunt.registerTask('build', [
		'clean',
		'checktextdomain',
		'sass:frontprod',
		'postcss:frontprod',
		'sass:themeprod',
		'postcss:themeprod',
		'sass:adminprod',
		'postcss:adminprod',
		'makepot',
		'copy:main',
		'compress:main',
		'copy:wporg',
		'compress:wporg'
	]);

};
