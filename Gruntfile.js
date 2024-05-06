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

	grunt.initConfig( {
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
					'build/css/_pico.sass.css': 'assets/css/default-styles.scss',
					'build/css/_simple.sass.css': 'assets/css/default-styles.scss',
					'build/css/_marx.sass.css': 'assets/css/default-styles.scss',
					'build/css/_mvp.sass.css': 'assets/css/default-styles.scss',
					'build/css/_sakura.sass.css': 'assets/css/default-styles.scss',
					'build/css/_pure.sass.css': 'assets/css/default-styles.scss',
					'build/css/_picnic.sass.css': 'assets/css/default-styles.scss',
					'build/css/_chota.sass.css': 'assets/css/default-styles.scss',
					'build/css/_cirrus.sass.css': 'assets/css/default-styles.scss',
				}
			}
		},

		concat: {
			picoCss: {
				src: [ 'build/css/_pico.postcss.css', 'build/css/_pico.sass.css' ],
				dest: 'build/css/pico.css',
			},
			simpleCss: {
				src: [ 'build/css/_simple.postcss.css', 'build/css/_simple.sass.css' ],
				dest: 'build/css/simple.css',
			},
			marxCss: {
				src: [ 'build/css/_marx.postcss.css', 'build/css/_marx.sass.css' ],
				dest: 'build/css/marx.css',
			},
			mvpCss: {
				src: [ 'build/css/_mvp.postcss.css', 'build/css/_mvp.sass.css' ],
				dest: 'build/css/mvp.css',
			},
			sakuraCss: {
				src: [ 'build/css/_sakura.postcss.css', 'build/css/_sakura.sass.css' ],
				dest: 'build/css/sakura.css',
			},
			pureCss: {
				src: [ 'build/css/_pure.postcss.css', 'build/css/_pure.sass.css' ],
				dest: 'build/css/pure.css',
			},
			picnicCss: {
				src: [ 'build/css/_picnic.postcss.css', 'build/css/_picnic.sass.css' ],
				dest: 'build/css/picnic.css',
			},
			chotaCss: {
				src: [ 'build/css/_chota.postcss.css', 'build/css/_chota.sass.css' ],
				dest: 'build/css/chota.css',
			},
			cirrusCss: {
				src: [ 'build/css/_cirrus.postcss.css', 'build/css/_cirrus.sass.css' ],
				dest: 'build/css/cirrus.css',
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
					'build/css/pico.min.css': [ 'build/css/pico.css' ],
					'build/css/simple.min.css': [ 'build/css/simple.css' ],
					'build/css/marx.min.css': [ 'build/css/marx.css' ],
					'build/css/mvp.min.css': [ 'build/css/mvp.css' ],
					'build/css/sakura.min.css': [ 'build/css/sakura.css' ],
					'build/css/pure.min.css': [ 'build/css/pure.css' ],
					'build/css/picnic.min.css': [ 'build/css/picnic.css' ],
					'build/css/chota.min.css': [ 'build/css/chota.css' ],
					'build/css/cirrus.min.css': [ 'build/css/cirrus.css' ]
				}
			}
		},

		clean: {
			css: [ 'build/css/_*', 'build/css/*.css', '!build/css/*.min.css' ]
		},

		watch: {
			css: {
				files: [ 'assets/css/**/*.css', 'assets/css/**/*.scss' ],
				tasks: [ 'styles' ],
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

	grunt.registerTask( 'default', [ 'styles', 'addtextdomain', 'exec:makepot' ] );
	grunt.registerTask( 'styles', [ 'postcss', 'sass', 'concat', 'cssmin', 'clean' ] );
};
