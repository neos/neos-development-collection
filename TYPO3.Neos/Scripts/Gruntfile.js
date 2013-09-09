module.exports = function(grunt) {
	var gruntConfig = {};

	grunt.loadNpmTasks('grunt-contrib-concat');
	grunt.loadNpmTasks('grunt-trimtrailingspaces');

	var baseUri = '../Resources/Public/Library/';

	gruntConfig.concat = {
		bootstrap: {
			src: [
				baseUri + 'twitter-bootstrap/js/bootstrap-alert.js',
				baseUri + 'twitter-bootstrap/js/bootstrap-dropdown.js',
				baseUri + 'twitter-bootstrap/js/bootstrap-tooltip.js',
				baseUri + 'bootstrap-notify/js/bootstrap-notify.js',
				baseUri + 'bootstrap-datetimepicker/js/bootstrap-datetimepicker.js'
			],
			dest: baseUri + 'bootstrap-components.js',
			options: {
				banner: '',
				footer: '',
				process: function(src, filepath) {
					src = src.replace(/keydown\./g, 'keydown.neos-');
					src = src.replace(/focus\./g, 'focus.neos-');
					src = src.replace(/click\./g, 'click.neos-');
					src = src.replace(/Class\('(?!icon)/g, "Class('neos-");
					src = src.replace(/\.divider/g, ".neos-divider");
					src = src.replace(/pull-right/g, 'neos-pull-right');
					src = src.replace(/class="(?!icon)/g, 'class="neos-');
					src = src.replace(/(find|is|closest|filter)\(('|")\./g, "$1($2.neos-");
					src = src.replace(/, \./g, '., .neos-');

					// Dropdown
					src = src.replace(/' dropdown-menu'/g, "' neos-dropdown-menu'");
					src = src.replace(/\.dropdown form/g, '.neos-dropdown form');

					// Tooltip
					src = src.replace(/in top bottom left right/g, 'neos-in neos-top neos-bottom neos-left neos-right');
					src = src.replace(/\.addClass\(placement\)/g, ".addClass('neos-' + placement)");

					// Datetimepicker
					src = src.replace(/case '(switch|prev|next|today)'/g, "case 'neos-$1'");
					src = src.replace(/= ' (old|new|disabled|active|today)'/g, "= ' neos-$1'");
					src = src.replace(/th\.today/g, 'th.neos-today');

					return src;
				}
			}
		},
		jQueryWithDependencies: {
			src: [
				baseUri + 'jquery/jquery-1.10.2.js',
				baseUri + 'jquery/jquery-migrate-1.2.1.js',
				baseUri + 'jquery-ui/js/jquery-ui-1.10.3.custom.js',
				baseUri + 'jquery-dynatree/js/jquery.dynatree.js',
				baseUri + 'chosen/chosen/chosen.jquery.js',
				baseUri + 'jcrop/js/jquery.Jcrop.js',
				baseUri + 'bootstrap-components.js'
			],
			dest: baseUri + 'jquery-with-dependencies.js',
			options: {
				banner: 'define(function() {',
				footer: 'return jQuery.noConflict(true);' +
						'});',
				process: function(src, filepath) {
					// Replace call to define() in jquery which conflicts with the dependency resolution in r.js
					return src.replace('define( "jquery", [], function () { return jQuery; } );', 'jQuery.migrateMute = true;');
				}
			}
		},

		handlebars: {
			src: [
				baseUri + 'handlebars/handlebars-1.0.0.js'
			],
			dest: baseUri + 'handlebars.js',
			options: {
				banner: 'define(function() {',
				footer: '  return Handlebars;' +
						'});'
			}
		},

		// This file needs jQueryWithDependencies first
		ember: {
			src: [
				baseUri + 'emberjs/ember-1.0.0.js'
			],
			dest: baseUri + 'ember.js',
			options: {
				banner: 'define(["Library/jquery-with-dependencies", "Library/handlebars"], function(jQuery, Handlebars) {' +
						'  var Ember = {exports: {}};' +
						'  var ENV = {LOG_VERSION: false};' +
						'  Ember.imports = {jQuery: jQuery, Handlebars: Handlebars};' +
						// TODO: window.T3 can be removed!
						'  Ember.lookup = { Ember: Ember, T3: window.T3};' +
						'  window.Ember = Ember;',
				footer: '  return Ember;' +
						'});'
			}
		},

		// This file needs jQueryWithDependencies first
		underscore: {
			src: [
				baseUri + 'vie/lib/underscoreJS/underscore.js'
			],
			dest: baseUri + 'underscore.js',
			options: {
				banner: 'define(function() {' +
						'  var root = {};' +
						'  (function() {',
				footer: '  }).apply(root);' +
						'  return root._;' +
						'});'
			}
		},

		backbone: {
			src: [
				baseUri + 'vie/lib/backboneJS/backbone.js'
			],
			dest: baseUri + 'backbone.js',
			options: {
				banner: 'define(["Library/underscore", "Library/jquery-with-dependencies"], function(_, jQuery) {' +
						'  var root = {_:_, jQuery:jQuery};' +
						'  (function() {',
				footer: '  }).apply(root);' +
						'  return root.Backbone;' +
						'});'
			}
		},

		vie: {
			src: [
				baseUri + 'vie/vie.js'
			],
			dest: baseUri + 'vie.js',
			options: {
				banner: 'define(["Library/underscore", "Library/backbone", "Library/jquery-with-dependencies"], function(_, Backbone, jQuery) {' +
						'  var root = {_:_, jQuery: jQuery, Backbone: Backbone};' +
						'  (function() {',
				footer: '  }).apply(root);' +
						'  return root.VIE;' +
						'});'
			}
		},

		mousetrap: {
			src: [
				baseUri + 'createjs/deps/mousetrap.min.js'
			],
			dest: baseUri + 'mousetrap.js',
			options: {
				banner: 'define([], function() {',
				footer: 'return window.Mousetrap;' +
						'});'
			}
		},

		create: {
			src: [
				baseUri + 'createjs/create.js'
			],
			dest: baseUri + 'create.js',
			options: {
				banner: 'define(["Library/underscore", "Library/backbone", "Library/jquery-with-dependencies"], function(_, Backbone, jQuery) {',
				footer: '});'
			}
		},

		hallo: {
			src: [
				baseUri + 'hallo/hallo.js'
			],
			dest: baseUri + 'hallo.js',
			options: {
				banner: 'define(["Library/jquery-with-dependencies"], function(jQuery) {' +
						'  var root = {jQuery: jQuery};' +
						'  (function() {',
				footer: '  }).apply(root);' +
						'});'
			}
		},

		plupload: {
			src: [
				baseUri + 'plupload/js/plupload.js',
				baseUri + 'plupload/js/plupload.html5.js'
			],
			dest: baseUri + 'plupload.js',
			options: {
				banner: 'define(["Library/jquery-with-dependencies"], function(jQuery) {',
				// TODO: get rid of the global 'window.plupload'.
				footer: '  return window.plupload;' +
						'});'
			}
		},

		codemirror: {
			src: [
				baseUri + 'codemirror2/lib/codemirror.js',
				baseUri + 'codemirror2/mode/xml/xml.js',
				baseUri + 'codemirror2/mode/css/css.js',
				baseUri + 'codemirror2/mode/javascript/javascript.js',
				baseUri + 'codemirror2/mode/htmlmixed/htmlmixed.js'
			],
			dest: baseUri + 'codemirror.js',
			options: {
				banner: 'define(function() {',
				footer: '  window.CodeMirror = CodeMirror;' +
						'  return CodeMirror;' +
						'});'
			}
		},

		xregexp: {
			src: [
				baseUri + 'XRegExp/xregexp.min.js'
			],
			dest: baseUri + 'xregexp.js',
			options: {
				banner: 'define(function() {',
				footer: '  return XRegExp;' +
						'});'
			}
		},

		iso8601JsPeriod: {
			src: [
				baseUri + 'iso8601-js-period/iso8601.min.js'
			],
			dest: baseUri + 'iso8601-js-period.js',
			options: {
				banner: 'define(function() {' +
						'var iso8601JsPeriod = {};',
				footer: '  return iso8601JsPeriod.iso8601;' +
						'});',
				process: function(src, filepath) {
					return src.replace('window.nezasa=window.nezasa||{}', 'iso8601JsPeriod');
				}
			}
		}
	};

	/**
	 * SECTION: Convenience Helpers for documentation rendering.
	 *
	 * In order to render documents automatically:
	 * - make sure you have installed Node.js / NPM
	 * - make sure you have installed grunt-cli GLOBALLY "npm install -g grunt-cli"
	 * - install all dependencies of this grunt file: "npm install"
	 *
	 * Exposed Targets:
	 * - "grunt watch": compile docs with OmniGraffle support as soon as they change
	 * - "grunt docs": compile docs with OmniGraffle support
	 */
	grunt.loadNpmTasks('grunt-contrib-watch');
	grunt.loadNpmTasks('grunt-bg-shell');

	gruntConfig.watch = {
		documentation: {
			files: '../Documentation/**/*.rst',
			tasks: ['bgShell:compileDocumentation'],
			options: {
				debounceDelay: 100,
				nospawn: true
			}
		},
		omnigraffle: {
			files: '../Documentation/IntegratorGuide/IntegratorDiagrams.graffle',
			tasks: ['docs'],
			options: {
				debounceDelay: 100,
				nospawn: true
			}
		},
		generatedDocumentationChanged: {
			files: '../Documentation/_make/build/html/**',
			tasks: ['_empty'],
			options: {
				livereload: true,
				debounceDelay: 100
			}
		}
	};

	gruntConfig.bgShell = {
		compileDocumentation: {
			cmd: 'cd ../Documentation/_make; make html',
			bg: false
		},
		compileOmnigraffle: {
			cmd: 'cd ../Documentation/IntegratorGuide; rm -Rf Diagrams/; osascript ../../Scripts/export_from_omnigraffle.scpt png `pwd`/IntegratorDiagrams.graffle `pwd`/Diagrams'
		}
	};
	grunt.registerTask('_empty', function() {
		// empty
	});
	grunt.registerTask('docs', ['bgShell:compileOmnigraffle', 'bgShell:compileDocumentation']);

	grunt.initConfig(gruntConfig);
};
