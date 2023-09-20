module.exports = function(grunt) {

	grunt.initConfig({

		pkg: grunt.file.readJSON('package.json'),

		dirs: {
			lang: 'languages'
		},

		// Convert the .po files to .mo files
		potomo: {
			dist: {
				options: {
					poDel: false
				},
				files: [{
					expand: true,
					cwd: '<%= dirs.lang %>',
					src: ['*.po'],
					dest: '<%= dirs.lang %>',
					ext: '.mo',
					nonull: true
				}]
			}
		},

		// Pull in the latest translations
		exec: {
			transifex: 'tx pull -a',

			// Create a ZIP file
			// Create a ZIP file
			zip: {
				cmd: function( version = '' ) {

					var filename = ( version === '' ) ? 'gravityview-dashboard-views' : 'gravityview-dashboard-views-' + version;

					// First, create the full archive
					var command = 'git-archive-all gravityview-dashboard-views.zip &&';

					command += 'unzip -o gravityview-dashboard-views.zip &&';

					command += 'zip -r ../' + filename + '.zip "gravityview-dashboard-views" &&';

					command += 'rm -rf "gravityview-dashboard-views/" && rm -f "gravityview-dashboard-views.zip"';

					return command;
				}
			}
		},

		// Build translations without POEdit
		makepot: {
			target: {
				options: {
					mainFile: 'gravityview-dashboard-views.php',
					type: 'wp-plugin',
					domainPath: '/languages',
					updateTimestamp: false,
					exclude: ['node_modules/.*', 'assets/.*', 'tmp/.*', 'vendor/.*', 'includes/lib/xml-parsers/.*', 'includes/lib/jquery-cookie/.*' ],
					potHeaders: {
						poedit: true,
						'x-poedit-keywordslist': true
					},
					processPot: function( pot, options ) {
						pot.headers['language'] = 'en_US';
						pot.headers['language-team'] = 'GravityKit <support@gravitykit.com>';
						pot.headers['last-translator'] = 'GravityKit <support@gravitykit.com>';
						pot.headers['report-msgid-bugs-to'] = 'https://www.gravitykit.com/support/';

						var translation,
							excluded_meta = [
								'GravityView - Dashboard Views',
								'Display Views in the WordPress Dashboard.',
								'https://www.gravitykit.com',
								'GravityView',
								'https://www.gravitykit.com/extensions/dashboard-views/'
							];

						for ( translation in pot.translations[''] ) {
							if ( 'undefined' !== typeof pot.translations[''][ translation ].comments.extracted ) {
								if ( excluded_meta.indexOf( pot.translations[''][ translation ].msgid ) >= 0 ) {
									console.log( 'Excluded meta: ' + pot.translations[''][ translation ].msgid );
									delete pot.translations[''][ translation ];
								}
							}
						}

						return pot;
					}
				}
			}
		},

		// Add textdomain to all strings, and modify existing textdomains in included packages.
		addtextdomain: {
			options: {
				textdomain: 'gravityview-dashboard-views',    // Project text domain.
				updateDomains: [ 'gravityview', 'gravity-view', 'gravityforms', 'edd_sl', 'edd', 'easy-digital-downloads' ]  // List of text domains to replace.
			},
			target: {
				files: {
					src: [
						'*.php',
						'**/*.php',
						'!node_modules/**',
						'!tests/**',
						'!tmp/**',
						'!vendor/**'
					]
				}
			}
		}
	});

	grunt.loadNpmTasks('grunt-potomo');
	grunt.loadNpmTasks('grunt-exec');
	grunt.loadNpmTasks('grunt-wp-i18n');

	grunt.registerTask( 'default', [ 'exec:transifex', 'potomo', 'addtextdomain', 'makepot' ] );

};
