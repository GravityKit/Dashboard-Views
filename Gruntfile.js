module.exports = function ( grunt ) {
	'use strict';

	grunt.loadNpmTasks( 'grunt-exec' );
	grunt.loadNpmTasks( 'grunt-wp-i18n' );
	grunt.loadNpmTasks( 'grunt-contrib-watch' );
	grunt.loadNpmTasks( 'grunt-contrib-concat' );
	grunt.loadNpmTasks( 'grunt-contrib-sass' );
	grunt.loadNpmTasks( '@lodder/grunt-postcss' );
	grunt.loadNpmTasks( 'grunt-contrib-cssmin' );
	grunt.loadNpmTasks( 'grunt-contrib-clean' );
	grunt.loadNpmTasks( 'grunt-contrib-uglify' );
	grunt.loadNpmTasks( 'grunt-browserify' );

	grunt.initConfig( {
		browserify: {
			dist: {
				options: {
					transform: [
						[ 'babelify', {
							presets: [ '@babel/preset-env' ],
						} ],
					],
				},
				files: {
					'build/js/_dashboard-view-editor.js': 'assets/js/dashboard-view-editor.js',
				},
			},
		},

		postcss: {
			options: {
				processors: [
					require( 'postcss-selector-namespace' )( {
						namespace: '.wrap.dashboard-view',
						not: [ ':root', '@keyframes', '@font-face' ]
					} ),
					require( 'autoprefixer' )( { overrideBrowserslist: [ 'last 2 versions' ] } ),
				]
			},
			dist: {
				files: {
					'build/css/_pico.postcss.css': 'node_modules/@picocss/pico/css/pico.min.css',
					'build/css/_simple.postcss.css': 'node_modules/simpledotcss/simple.min.css',
					'build/css/_marx.postcss.css': 'node_modules/marx-css/css/marx.min.css',
					'build/css/_mvp.postcss.css': 'node_modules/mvp.css/mvp.css',
					'build/css/_sakura.postcss.css': 'node_modules/sakura.css/css/sakura.css',
					'build/css/_pure.postcss.css': 'node_modules/purecss/build/pure-min.css',
					'build/css/_picnic.postcss.css': 'node_modules/picnic/releases/picnic.min.css',
					'build/css/_chota.postcss.css': 'node_modules/chota/dist/chota.min.css',
					'build/css/_cirrus.postcss.css': 'node_modules/cirrus-ui/dist/cirrus-all.css',
				}
			}
		},

		sass: {
			options: {
				style: 'expanded',
				sourceMap: false
			},
			dist: {
				files: {
					'build/css/_dashboard-view-editor.sass.css': 'assets/css/dashboard-view-editor.scss',
					'build/css/_dashboard-view-pico.sass.css': 'assets/css/dashboard-view.scss',
					'build/css/_dashboard-view-simple.sass.css': 'assets/css/dashboard-view.scss',
					'build/css/_dashboard-view-marx.sass.css': 'assets/css/dashboard-view.scss',
					'build/css/_dashboard-view-mvp.sass.css': 'assets/css/dashboard-view.scss',
					'build/css/_dashboard-view-sakura.sass.css': 'assets/css/dashboard-view.scss',
					'build/css/_dashboard-view-pure.sass.css': 'assets/css/dashboard-view.scss',
					'build/css/_dashboard-view-picnic.sass.css': 'assets/css/dashboard-view.scss',
					'build/css/_dashboard-view-chota.sass.css': 'assets/css/dashboard-view.scss',
					'build/css/_dashboard-view-cirrus.sass.css': 'assets/css/dashboard-view.scss',
				}
			}
		},

		concat: {
			viewEditorCss: {
				src: [ 'node_modules/tom-select/dist/css/tom-select.css', 'build/css/_dashboard-view-editor.sass.css' ],
				dest: 'build/css/dashboard-view-editor.css',
			},
			picoCss: {
				src: [ 'build/css/_dashboard-view-pico.postcss.css', 'build/css/_dashboard-view-pico.sass.css' ],
				dest: 'build/css/pico.css',
			},
			simpleCss: {
				src: [ 'build/css/_dashboard-view-simple.postcss.css', 'build/css/_dashboard-view-simple.sass.css' ],
				dest: 'build/css/simple.css',
			},
			marxCss: {
				src: [ 'build/css/_dashboard-view-marx.postcss.css', 'build/css/_dashboard-view-marx.sass.css' ],
				dest: 'build/css/marx.css',
			},
			mvpCss: {
				src: [ 'build/css/_dashboard-view-mvp.postcss.css', 'build/css/_dashboard-view-mvp.sass.css' ],
				dest: 'build/css/mvp.css',
			},
			sakuraCss: {
				src: [ 'build/css/_dashboard-view-sakura.postcss.css', 'build/css/_dashboard-view-sakura.sass.css' ],
				dest: 'build/css/sakura.css',
			},
			pureCss: {
				src: [ 'build/css/_dashboard-view-pure.postcss.css', 'build/css/_dashboard-view-pure.sass.css' ],
				dest: 'build/css/pure.css',
			},
			picnicCss: {
				src: [ 'build/css/_dashboard-view-picnic.postcss.css', 'build/css/_dashboard-view-picnic.sass.css' ],
				dest: 'build/css/dashboard-view-picnic.css',
			},
			chotaCss: {
				src: [ 'build/css/_dashboard-view-chota.postcss.css', 'build/css/_dashboard-view-chota.sass.css' ],
				dest: 'build/css/dashboard-view-chota.css',
			},
			cirrusCss: {
				src: [ 'build/css/_dashboard-view-cirrus.postcss.css', 'build/css/_dashboard-view-cirrus.sass.css' ],
				dest: 'build/css/dashboard-view-cirrus.css',
			}
		},

		cssmin: {
			options: {
				format: 'keep-breaks',
				mergeIntoShorthands: false,
				roundingPrecision: -1
			},
			target: {
				files: {
					'build/css/dashboard-view-editor.min.css': [ 'build/css/dashboard-view-editor.css' ],
					'build/css/dashboard-view-pico.min.css': [ 'build/css/dashboard-view-pico.css' ],
					'build/css/dashboard-view-simple.min.css': [ 'build/css/dashboard-view-simple.css' ],
					'build/css/dashboard-view-marx.min.css': [ 'build/css/dashboard-view-marx.css' ],
					'build/css/dashboard-view-mvp.min.css': [ 'build/css/dashboard-view-mvp.css' ],
					'build/css/dashboard-view-sakura.min.css': [ 'build/css/dashboard-view-sakura.css' ],
					'build/css/dashboard-view-pure.min.css': [ 'build/css/dashboard-view-pure.css' ],
					'build/css/dashboard-view-picnic.min.css': [ 'build/css/dashboard-view-picnic.css' ],
					'build/css/dashboard-view-chota.min.css': [ 'build/css/dashboard-view-chota.css' ],
					'build/css/dashboard-view-cirrus.min.css': [ 'build/css/dashboard-view-cirrus.css' ]
				}
			}
		},

		uglify: {
			dashboardView: {
				files: {
					'build/js/dashboard-view.min.js': 'assets/js/dashboard-view.js'
				}
			},
			dashboardViewEditor: {
				files: {
					'build/js/dashboard-view-editor.min.js': 'build/js/_dashboard-view-editor.js'
				}
			}
		},

		clean: {
			css: [
				'build/css/*',
				'!build/css/*.min.css',
			],
			js: [
				'build/js/*',
				'!build/js/*.min.js',
			]
		},

		watch: {
			css: {
				files: [ 'assets/css/**/*.css', 'assets/css/**/*.scss' ],
				tasks: [ 'styles' ],
				options: {
					spawn: false,
				},
			},
			js: {
				files: [ 'assets/js/**/*.js' ],
				tasks: [ 'scripts' ],
				options: {
					spawn: false,
				},
			},
		},

		addtextdomain: {
			options: {
				textdomain: 'gk-gravityview-dashboard-views',
				updateDomains: []
			},
			target: {
				files: {
					src: [
						'*.php',
						'templates/**',
					]
				}
			}
		},

		exec: {
			makepot: {
				cmd: function () {
					var fileComments = [
						'Copyright (C) ' + new Date().getFullYear() + ' GravityKit',
						'This file is distributed under the GPLv2 or later',
					];

					var headers = {
						'Last-Translator': 'GravityKit <support@gravitykit.com>',
						'Language-Team': 'GravityKit <support@gravitykit.com>',
						'Language': 'en_US',
						'Plural-Forms': 'nplurals=2; plural=(n != 1);',
						'Report-Msgid-Bugs-To': 'https://www.gravitykit.com/support',
					};

					var command = 'wp i18n make-pot --exclude=build . translations.pot';

					command += ' --file-comment="' + fileComments.join( '\n' ) + '"';

					command += ' --headers=\'' + JSON.stringify( headers ) + '\'';

					return command;
				}
			}
		}
	} );

	grunt.registerTask( 'default', [ 'styles', 'scripts', 'addtextdomain', 'exec:makepot' ] );
	grunt.registerTask( 'styles', [ 'postcss', 'sass', 'concat', 'cssmin', 'clean:css' ] );
	grunt.registerTask( 'scripts', [ 'browserify', 'uglify','clean:js' ] );
};
