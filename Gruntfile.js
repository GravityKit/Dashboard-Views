module.exports = function( grunt ) {
	'use strict';

	grunt.initConfig( {
		// Add text domain to all strings, and modify existing text domains in included packages.
		addtextdomain: {
			options: {
				textdomain: 'gk-gravityview-dashboard-views',    // Project text domain.
				updateDomains: [ ]  // List of text domains to replace.
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
			// Generate POT file.
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
			},
		}
	} );

	grunt.loadNpmTasks( 'grunt-exec' );
	grunt.loadNpmTasks( 'grunt-wp-i18n' );

	// Translation stuff.
	grunt.registerTask( 'default', [ 'addtextdomain', 'exec:makepot' ] );
};
