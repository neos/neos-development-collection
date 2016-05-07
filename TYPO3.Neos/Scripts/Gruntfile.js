module.exports = function (grunt) {
	var path = require('path'),
		packagePath = path.join(__dirname, '../'),
		libraryPath = path.join(packagePath, 'Resources/Public/Library/');

	grunt.initConfig({
		watch: {
			css: {
				files: path.join(packagePath, 'Resources/Private/Styles/**/*.scss'),
				tasks: ['compile-css'],
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
		concat: {
			css: {
				src: [
					path.join(packagePath, 'Resources/Public/Styles/Neos.css'),
					path.join(libraryPath, 'jquery-ui/css/custom-theme/jquery-ui-1.8.16.custom.css'),
					path.join(libraryPath, 'jcrop/css/jquery.Jcrop.css'),
					path.join(libraryPath, 'chosen/chosen/chosen.min.css')
				],
				dest: path.join(packagePath, 'Resources/Public/Styles', 'Includes-built.css')
			}
		},
		requirejs: {
			compile: {
				options: {
					mainConfigFile: 'build.js'
				}
			}
		},
		trimtrailingspaces: {
			js: {
				src: [libraryPath + 'jquery-with-dependencies.js'],
				filter: 'isFile',
				encoding: 'utf8'
			}
		}
	});

	/**
	 * QUnit tasks
	 *
	 * We run the tests on phantomjs, if you don't have that installed and running a ubuntu like machine
	 * run ./install-phantomjs.sh first
	 */
	grunt.config.merge({
		qunit: {
			all: [
				'../Tests/JavaScript/**/*.html'
			]
		}
	});

	grunt.config.merge({
		concat: {
			requirejs: {
				src: [
					libraryPath + 'requirejs/src/require.js'
				],
				dest: libraryPath + 'requirejs/require.js',
				options: {
					banner: 'if (!requirejs) {',
					footer: '}'
				}
			},

			aloha: {
				src: [
					libraryPath + 'aloha/aloha.js'
				],
				dest: libraryPath + 'aloha/aloha.js',
				options: {
					process: function(src, filepath) {
						src = src.replace(/\$\(function \(\) {\s*\n\s+element.appendTo\('body'\);\n\s+}\);/, "$(function(){ element.appendTo('#neos-application'); });");
						src = src.replace("jQuery('body').append(layer).bind('click', function(e) {", "jQuery('#neos-application').append(layer).bind('click', function(e) {");
						src = src.replace('var editableTrimedContent = jQuery.trim(this.getContents()),', "var editableTrimedContent = $('<div />').html(this.getContents()).text().trim(),");

						// Compatibility with no conflict jQuery UI
						src = src.replace(/\.button\(/g, '.uibutton(');

						// Fix broken this reference in list plugin
						src = src.replace('jQuery.each(this.templates[nodeName].classes, function () {', 'jQuery.each(this.templates[nodeName].classes, function (i, cssClass) {');
						src = src.replace(/listToStyle\.removeClass\(this\);/g, 'listToStyle.removeClass(cssClass);');
						src = src.replace('jQuery.each(plugin.templates[listtype].classes, function () {', 'jQuery.each(plugin.templates[listtype].classes, function (i, cssClass) {');

						// Workaround for jQueryUI menu issue / poorly written code in list plugin
						src = src.replace("elem.data('aloha-ui-menubutton-select', function (){", "elem.click(function (){");

						// We need to patch this file to make Aloha compatible with jquery UI 1.10.4
						src = src.replace("this.container.tabs('select', this.index);", "this.container.tabs('option', 'active', this.index);");

						// Remove instantiation of language repository in link plugin
						src = src.replace(/\s+LANG_REPOSITORY[\s\w=('\-./,]+\);/, '');

						// Remove setting of hreflang attribute on links
						src = src.replace(/\s+this.hrefField.setAttribute\('hreflang', ''\);/, '');

						// add "code" element
						src = src.replace(/var componentNameByElement = {\n/, "var componentNameByElement = { 'code': 'code'," + "\n");
						src = src.replace("availableButtons: [ 'u',", "availableButtons: [ 'code', 'u',");

						// tooltips
						src = src.replace(/tooltipClass: 'aloha aloha-ui-tooltip',/g, "placement: 'bottom',");
						src = src.replace(".tooltip('close', null, true);", ".tooltip('hide');");
						src = src.replace(".tooltip('disable');", ".tooltip('hide');");
						src = src.replace(".tooltip('enable');", ".tooltip('show');");

						return src;
					}
				}
			},

			bootstrap: {
				src: [
					libraryPath + 'twitter-bootstrap/js/bootstrap-alert.js',
					libraryPath + 'twitter-bootstrap/js/bootstrap-dropdown.js',
					libraryPath + 'twitter-bootstrap/js/bootstrap-tooltip.js',
					libraryPath + 'twitter-bootstrap/js/bootstrap-popover.js',
					libraryPath + 'bootstrap-datetimepicker/js/bootstrap-datetimepicker.js'
				],
				dest: libraryPath + 'bootstrap-components.js',
				options: {
					banner: '',
					footer: '',
					process: function (src, filepath) {
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
						src = src.replace('data-toggle', 'data-neos-toggle');

						// Tooltip
						src = src.replace(/in top bottom left right/g, 'neos-in neos-top neos-bottom neos-left neos-right');
						src = src.replace(/\.addClass\(placement\)/g, ".addClass('neos-' + placement)");
						src = src.replace('delay: 0', "delay: { 'show': 500, 'hide': 100 }");

						// Popover
						src = src.replace(/fade top bottom left right in/g, 'neos-fade neos-top neos-bottom neos-left neos-right neos-in');

						// Datetimepicker
						src = src.replace(/case '(switch|prev|next|today)'/g, "case 'neos-$1'");
						src = src.replace(/'prev'/g, "'neos-prev'");
						src = src.replace(/= ' (old|new|disabled|active|today)'/g, "= ' neos-$1'");
						src = src.replace(/th\.today/g, 'th.neos-today');

						// clean up the mess:
						src = src.replace(/neos-neos/g, 'neos');

						return src;
					}
				}
			},

			select2: {
				src: [
					libraryPath + 'select2/select2.js'
				],
				dest: libraryPath + 'select2.js',
				options: {
					process: function (src, filepath) {
						src = src.replace(/window\.Select2/g, 'Utility.Select2');
						src = src.replace(/select2-(dropdown-open|measure-scrollbar|choice|resizer|chosen|search-choice-close|arrow|focusser|offscreen|drop|display-none|search|input|results|no-results|selected|selection-limit|more-results|match|active|container-active|container|default|allowclear|with-searchbox|focused|sizer|result|disabled|highlighted|locked)/g, 'neos-select2-$1');

						src = src.replace('if (this.indexOf("select2-") === 0) {', 'if (this.indexOf("neos-select2-") === 0) {');
						src = src.replace('if (this.indexOf("select2-") !== 0) {', 'if (this.indexOf("neos-select2-") !== 0) {');

						// make it work with position:fixed in the sidebar
						src = src.replace('if (above) {', 'if (false) {');
						src = src.replace('css.top = dropTop;', 'css.top = dropTop - $window.scrollTop();');

						// add bootstrap icon-close
						src = src.replace("<a href='#' onclick='return false;' class='neos-select2-search-choice-close' tabindex='-1'></a>", "<a href='#' onclick='return false;' class='neos-select2-search-choice-close'><i class='icon-remove'></i></a>");
						src = src.replace("<abbr class='neos-select2-search-choice-close'></abbr>", "<abbr class='neos-select2-search-choice-close'><i class='icon-remove'></i></abbr>");

						src = src.replace('this.body = thunk(function() { return opts.element.closest("body"); });', "this.body = thunk(function() { return opts.relative ? opts.element.parent() : $('#neos-application'); });");

						return src;
					}
				}
			},

			select2Css: {
				src: [
					libraryPath + 'select2/select2.css'
				],
				dest: libraryPath + 'select2/select2-prefixed.scss',
				options: {
					banner: '/* This file is autogenerated using the Gruntfile.*/',
					footer: '',
					process: function (src, filepath) {
						src = src.replace(/select2-(dropdown-open|measure-scrollbar|choice|resizer|chosen|search-choice-close|arrow|focusser|offscreen|drop|display-none|search|input|results|no-results|selected|selection-limit|more-results|match|active|container-active|container|default|allowclear|with-searchbox|focused|sizer|result|disabled|highlighted|locked)/g, 'neos-select2-$1');

						src = src.replace(/url\('select2.png'\)/g, "url('../Library/select2/select2.png')");
						src = src.replace(/url\('select2x2.png'\)/g, "url('../Library/select2/select2x2.png')");

						return src;
					}
				}
			},

			handlebars: {
				src: [
					libraryPath + 'handlebars/handlebars-1.0.0.js'
				],
				dest: libraryPath + 'handlebars.js',
				options: {
					banner: 'define(function() {',
					footer: '  return Handlebars;' +
					'});'
				}
			},

			// This file needs jQueryWithDependencies first
			ember: {
				src: [
					libraryPath + 'emberjs/ember-1.0.0.js',
					libraryPath + 'ember-i18n/lib/i18n.js'
				],
				dest: libraryPath + 'ember.js',
				options: {
					banner: 'define(["Library/jquery-with-dependencies", "Library/handlebars", "Library/cldr"], function(jQuery, Handlebars, CLDR) {' +
					'  CLDR.defaultLocale = window.T3Configuration.locale;' + // TODO: make configurable, as this is only used for plurals this is not highest prio (same behavior in cldr for most languages)
					'  var Ember = {exports: {}};' +
					'  var ENV = {LOG_VERSION: false};' +
					'  Ember.imports = {jQuery: jQuery, Handlebars: Handlebars};' +
						// TODO: window.T3 can be removed!
					'  Ember.lookup = { Ember: Ember, T3: window.T3};' +
					'  window.Ember = Ember;',
					footer: '  return Ember;' +
					'});',
					process: function(src) {
						src = src.replace('I18n.t(', 'I18n.translate(');
						src = src.replace("Handlebars.registerHelper('t'", "Handlebars.registerHelper('translate'");
						src = src.replace('t: function(key, context)', 'translate: function(key, context)');

						return src;
					}
				}
			},

			// This file needs jQueryWithDependencies first
			underscore: {
				src: [
					libraryPath + 'vie/lib/underscoreJS/underscore.js'
				],
				dest: libraryPath + 'underscore.js',
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
					libraryPath + 'vie/lib/backboneJS/backbone.js'
				],
				dest: libraryPath + 'backbone.js',
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
					libraryPath + 'vie/vie.js'
				],
				dest: libraryPath + 'vie.js',
				options: {
					banner: 'define(["Library/underscore", "Library/backbone", "Library/jquery-with-dependencies"], function(_, Backbone, jQuery) {' +
					'  var root = {_:_, jQuery: jQuery, Backbone: Backbone};' +
					'  (function() {',
					footer: '  }).apply(root);' +
					'  return root.VIE;' +
					'});',
					process: function(src) {
						// Set "overrideAttributes" option when updating existing entities to prevent it from converting values into an array with old values
						return src.replace('entityInstance = this.vie.entities.addOrUpdate(entityInstance, {', 'entityInstance = this.vie.entities.addOrUpdate(entityInstance, {overrideAttributes: true,');
					}
				}
			},

			mousetrap: {
				src: [
					libraryPath + 'mousetrap/mousetrap.js'
				],
				dest: libraryPath + 'mousetrap.js'
			},

			create: {
				src: [
					libraryPath + 'createjs/create.js'
				],
				dest: libraryPath + 'create.js',
				options: {
					banner: 'define(["Library/underscore", "Library/backbone", "Library/jquery-with-dependencies"], function(_, Backbone, jQuery) {',
					footer: '});',
					process: function(src, filepath) {
						src = src.replace(/widget.options.autoSaveInterval/g, 200);
						src = src.replace(
							/[ ]*widget.saveRemoteAll\({(.|\n)*?}\);/i,

							'        var version = widget.changedModels.length > 0 ? widget.changedModels[0].midgardStorageVersion : widget.changedModels.reduce(function(previousValue, currentValue) {' + "\n" +
							'          return previousValue ? previousValue.midgardStorageVersion + currentValue.midgardStorageVersion : currentValue.midgardStorageVersion;' + "\n" +
							'        });' + "\n" +
							'        if (version !== currentVersion) {' + "\n" +
							'          currentVersion = version;' + "\n" +
							'          debouncedDoAutoSave();' + "\n" +
							'        }'
						);
						src = src.replace(
							'      var doAutoSave = function () {',

							'      var throttledDoAutoSave = _.throttle(function() {' + "\n" +
							'        widget.saveRemoteAll({' + "\n" +
							'          // We make autosaves silent so that potential changes from server' + "\n" +
							'          // don\'t disrupt user while writing.' + "\n" +
							'          silent: true' + "\n" +
							'        });' + "\n" +
							'      }, widget.options.autoSaveInterval);' + "\n" +
							'      var currentVersion;' + "\n" +
							'      var debouncedDoAutoSave = _.debounce(function() {' + "\n" +
							'        currentVersion = null;' + "\n" +
							'        throttledDoAutoSave();' + "\n" +
							'      }, 500);' + "\n" +
							'      var doAutoSave = function () {'
						);
						return src;
					}
				}
			},

			plupload: {
				src: [
					libraryPath + 'plupload/js/plupload.js',
					libraryPath + 'plupload/js/plupload.html5.js'
				],
				dest: libraryPath + 'plupload.js',
				options: {
					banner: 'define(["Library/jquery-with-dependencies"], function(jQuery) {',
					// TODO: get rid of the global 'window.plupload'.
					footer: '  return window.plupload;' +
					'});'
				}
			},

			codemirror: {
				src: [
					libraryPath + 'codemirror/lib/codemirror.js',
					libraryPath + 'codemirror/mode/xml/xml.js',
					libraryPath + 'codemirror/mode/css/css.js',
					libraryPath + 'codemirror/mode/javascript/javascript.js',
					libraryPath + 'codemirror/mode/htmlmixed/htmlmixed.js'
				],
				dest: libraryPath + 'codemirror.js',
				options: {
					banner: 'define(function() {',
					footer: '  window.CodeMirror = CodeMirror;' +
					'  return CodeMirror;' +
					'});'
				}
			},

			xregexp: {
				src: [
					libraryPath + 'XRegExp/xregexp.min.js'
				],
				dest: libraryPath + 'xregexp.js',
				options: {
					banner: 'define(function() {',
					footer: '  return XRegExp;' +
					'});'
				}
			},

			iso8601JsPeriod: {
				src: [
					libraryPath + 'iso8601-js-period/iso8601.min.js'
				],
				dest: libraryPath + 'iso8601-js-period.js',
				options: {
					banner: 'define(function() {' +
					'var iso8601JsPeriod = {};',
					footer: '  return iso8601JsPeriod.iso8601;' +
					'});',
					process: function (src, filepath) {
						return src.replace('window.nezasa=window.nezasa||{}', 'iso8601JsPeriod');
					}
				}
			},

			toastr: {
				src: [
					libraryPath + 'toastr/toastr.js'
				],
				dest: libraryPath + 'toastr.js',
				options: {
					process: function (src, filepath) {
						src = src.replace('toast-close-button', 'neos-close-button');
						src = src.replace("define(['jquery']", "define(['Library/jquery-with-dependencies']");
						return src;
					}
				}
			},

			nprogress: {
				src: [
					libraryPath + 'nprogress/nprogress.js'
				],
				dest: libraryPath + 'nprogress.js',
				options: {
					process: function (src, filepath) {
						src = src.replace("id='nprogress'", "id='neos-nprogress'");
						src = src.replace(/\#nprogress/g, '#neos-nprogress');
						src = src.replace('appendTo(document.body)', "appendTo('#neos-application')");
						src = src.replace(/\.(add|remove)Class\('/g, ".$1Class('neos-");
						src = src.replace("define(['jquery']", "define(['Library/jquery-with-dependencies']");
						return src;
					}
				}
			},

			sly: {
				src: [
					libraryPath + 'sly/sly.js'
				],
				dest: libraryPath + 'sly.js',
				options: {
					process: function (src, filepath) {
						src = src.replace('jQuery.', '$.');
						return src;
					}
				}
			},

			jQueryWithDependencies: {
				src: [
					libraryPath + 'jquery/jquery-2.0.3.js',
					libraryPath + 'jquery/jquery-migrate-1.2.1.js',
					libraryPath + 'jquery-easing/jquery.easing.1.3.js',
					libraryPath + 'jquery-ui/js/jquery-ui-1.10.4.custom.js',
					libraryPath + 'jquery-cookie/jquery.cookie.js',
					libraryPath + 'jquery-dynatree/js/jquery.dynatree.js',
					libraryPath + 'chosen/chosen/chosen.jquery.js',
					libraryPath + 'jcrop/js/jquery.Jcrop.js',
					libraryPath + 'select2.js',
					libraryPath + 'sly.js',
					libraryPath + 'bootstrap-components.js'
				],
				dest: libraryPath + 'jquery-with-dependencies.js',
				options: {
					banner: 'define(["Shared/Utility"], function(Utility) {' + "\n",
					footer: "\n"  + 'return jQuery.noConflict(true);' + "\n" + '});',
					process: function(src, filepath) {
						switch (filepath) {
							case libraryPath + 'jquery/jquery-2.0.3.js':
								// Replace call to define() in jquery which conflicts with the dependency resolution in r.js
								src = src.replace('define( "jquery", [], function () { return jQuery; } );', 'jQuery.migrateMute = true;');
								break;
							case libraryPath + 'jquery-ui/js/jquery-ui-1.10.4.custom.js':
								// Prevent conflict with Twitter Bootstrap
								src += "$.widget.bridge('uitooltip', $.ui.tooltip);";
								src += "$.widget.bridge('uibutton', $.ui.button);";
								break;
						}
						return src;
					}
				}
			}
		},

		cldr: {
			src: [
				libraryPath + 'ember-i18n/vendor/cldr-1.0.0.js'
			],
			dest: libraryPath + 'cldr.js',
			options: {
				banner: 'define(function() {' +
				'  var root = {};' +
				'  (function() {',
				footer: '  }).apply(root);' +
				'  return root.CLDR;' +
				'});'
			}
		}
	});

	/**
	 * SECTION: Convenience Helpers for documentation rendering.
	 *
	 * In order to render documents automatically:
	 * - make sure you have installed Node.js / NPM
	 * - make sure you have installed grunt-cli GLOBALLY "npm install -g grunt-cli"
	 * - install all dependencies of this grunt file: "npm install"
	 *
	 * Exposed Targets:
	 * - "grunt watch-docs": compile docs with OmniGraffle support as soon as they change
	 * - "grunt build-docs": compile docs with OmniGraffle support
	 */
	grunt.config.merge({
		watch: {
			documentation: {
				files: '../Documentation/**/*.rst',
				tasks: ['bgShell:compileDocumentation'],
				options: {
					debounceDelay: 100,
					nospawn: true
				}
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
		},
		bgShell: {
			compileDocumentation: {
				cmd: 'cd ../Documentation/_make; make html',
				bg: false
			},
			compileOmnigraffle: {
				cmd: 'cd ../Documentation/IntegratorGuide; rm -Rf Diagrams/; osascript ../../Scripts/export_from_omnigraffle.scpt png `pwd`/IntegratorDiagrams.graffle `pwd`/Diagrams'
			}
		}
	});

	/**
	 * Load and register tasks
	 */
	require('matchdep').filter('grunt-*').forEach(grunt.loadNpmTasks);

	// Empty task
	grunt.registerTask('_empty', function () {});

	/**
	 * Build commands for execution in the build pipeline
	 */
	grunt.registerTask('build', ['build-js', 'build-css']);
	grunt.registerTask('build-js', ['compile-js', 'requirejs:compile']);
	grunt.registerTask('build-css', ['compile-css', 'concat:css']);
	grunt.registerTask('build-docs', ['bgShell:compileOmnigraffle', 'bgShell:compileDocumentation']);

	/**
	 * Compile commands for development context
	 */
	grunt.registerTask('compile', ['compile-js', 'compile-css']);
	grunt.registerTask('compile-js', function() {
		grunt.util._.forEach(grunt.config.get().concat, function(taskConfiguration, taskName) {
			if (taskName !== 'css') {
				grunt.task.run('concat:' + taskName);
				grunt.task.run('trimtrailingspaces:js');
			}
		});
	});
	grunt.registerTask('compile-css', ['compass:compile']);

	/**
	 * Watch commands
	 */
	grunt.registerTask('watch-js', function() { console.log('JavaScript sources are loaded by requirejs. Use the setting "TYPO3.Neos.userInterface.loadMinifiedJavascript: FALSE" instead')});
	grunt.registerTask('watch-css', ['watch:css']);
	grunt.registerTask('watch-docs', ['watch:documentation']);

	/**
	 * Testing commands
	 */
	grunt.registerTask('test', ['qunit']);
};
