module.exports = function ( grunt ) {
	'use strict';

	// Project configuration.
	grunt.initConfig( {
		pkg: grunt.file.readJSON( 'package.json' ),

		makepot: {
			target: {
				options: {
					domainPath: '/languages',
					potFilename: '<%= pkg._project.textdomain %>.pot',
					potHeaders: {
						poedit: true,
						'x-poedit-keywordslist': true,
					},
					type: 'wp-plugin',
					updateTimestamp: true,
				},
			},
		},

		addtextdomain: {
			options: {
				textdomain: '<%= pkg._project.textdomain %>',
				updateDomains: [ 'wpzoom', 'zoom-instagram-widget' ],
			},
			target: {
				files: {
					src: [
						'*.php',
						'**/*.php',
						'!node_modules/**',
						'!tests/**',
						'!docs/**',
						'!vendor/**',
					],
				},
			},
		},

		bumpup: {
			options: {
				updateProps: {
					pkg: 'package.json',
				},
			},
			file: 'package.json',
		},

		replace: {
			plugin_main: {
				src: [ 'style.css', 'readme.txt', 'instagram-widget-by-wpzoom.php' ],
				overwrite: true,
				replacements: [
					{
						from: /Version: \bv?(?:0|[1-9]\d*)\.(?:0|[1-9]\d*)\.(?:0|[1-9]\d*)(?:-[\da-z-A-Z-]+(?:\.[\da-z-A-Z-]+)*)?(?:\+[\da-z-A-Z-]+(?:\.[\da-z-A-Z-]+)*)?\b/g,
						to: 'Version: <%= pkg.version %>',
					},
				],
			},

			plugin_const: {
				src: [ 'instagram-widget-by-wpzoom.php' ],
				overwrite: true,
				replacements: [
					{
						from: /WPZOOM_INSTAGRAM_VERSION', '.*?'/g,
						to: "WPZOOM_INSTAGRAM_VERSION', '<%= pkg.version %>'",
					},
				],
			},

			plugin_function_comment: {
				src: [
					'*.php',
					'**/*.php',
					'!node_modules/**',
					'!php-tests/**',
					'!bin/**',
				],
				overwrite: true,
				replacements: [
					{
						from: 'x.x.x',
						to: '<%= pkg.version %>',
					},
				],
			},

			changelog: {
				src: [ 'readme.txt' ],
				overwrite: true,
				replacements: [
					{
						from: 'x.x.x',
						to: '<%= pkg.version %>',
					},
				],
			},
		},
	} );

	// Load grunt tasks.
	grunt.loadNpmTasks( 'grunt-wp-i18n' );
	grunt.loadNpmTasks( 'grunt-bumpup' );
	grunt.loadNpmTasks( 'grunt-text-replace' );

	// Register Tasks.

	// Bump Version - `grunt version-bump --ver=<version-number>`
	grunt.registerTask( 'version-bump', function ( ver ) {
		let newVersion = grunt.option( 'ver' );

		if ( newVersion ) {
			newVersion = newVersion ? newVersion : 'patch';

			grunt.task.run( 'bumpup:' + newVersion );
			grunt.task.run(
				'replace:plugin_main',
				'replace:plugin_const',
				'replace:plugin_function_comment',
				'replace:changelog',
			);
		}
	} );

	// i18n
	grunt.registerTask( 'i18n', [ 'addtextdomain', 'makepot' ] );

	// Default task.
	grunt.registerTask( 'default', [
		'addtextdomain',
	] );

	grunt.util.linefeed = '\n';
};
