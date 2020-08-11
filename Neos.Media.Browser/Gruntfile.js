module.exports = function (grunt) {
	var path = require('path'),
		packagePath = __dirname;

	grunt.loadNpmTasks('grunt-contrib-cssmin');

	grunt.initConfig({
		watch: {
			css: {
				files: path.join(packagePath, 'Resources/Private/Styles/**/*.scss'),
				tasks: ['compass'],
				options: {
					spawn: false,
					debounceDelay: 250,
					interrupt: true
				}
			}
		},
		compass: {
			compile: {
				options: {
					config: path.join(packagePath, 'Resources/Private/Styles/config.rb'),
					basePath: path.join(packagePath, 'Resources/Private/Styles'),
					trace: false,
					quiet: true,
					sourcemap: false,
					bundleExec: true
				}
			}
		},
		cssmin: {
			target: {
				files: [{
					expand: true,
					cwd: path.join(packagePath, 'Resources/Public/Styles'),
					src: ['*.css', '!*.min.css'],
					dest: path.join(packagePath, 'Resources/Public/Styles'),
					ext: '.min.css'
				}]
			}
		}
	});

	/**
	 * Load and register tasks
	 */
	require('matchdep').filter('grunt-*').forEach(grunt.loadNpmTasks);

	/**
	 * Build commands for execution in the build pipeline
	 */
	grunt.registerTask('build', ['compass', 'cssmin']);
	grunt.registerTask('build:css', ['build']);
};
