module.exports = function(grunt) {
	var gruntConfig = {};

	grunt.loadNpmTasks('grunt-contrib-concat');
	grunt.loadNpmTasks('grunt-trimtrailingspaces');

	var baseUri = '../Resources/Public/Library/';

	gruntConfig.concat = {
		requirejs: {
			src: [
				baseUri + 'requirejs/src/require.js'
			],
			dest: baseUri + 'requirejs/require.js',
			options: {
				banner: 'if (!requirejs) {',
				footer: '}'
			}
		},
		aloha: {
			src: [
				baseUri + 'aloha/aloha.js'
			],
			dest: baseUri + 'aloha/aloha.js',
			options: {
				process: function(src, filepath) {
					src = src.replace(/\$\(function \(\) {\s*\n\s+element.appendTo\('body'\);\n\s+}\);/, "$(function(){ element.appendTo('#neos-application'); });");
					src = src.replace("jQuery('body').append(layer).bind('click', function(e) {", "jQuery('#neos-application').append(layer).bind('click', function(e) {");
					src = src.replace('var editableTrimedContent = jQuery.trim(this.getContents()),', "var editableTrimedContent = $('<div />').html(this.getContents()).text().trim(),");

					// Compatibility with no conflict jQuery UI
					src = src.replace(/\.button\(/g, '.uibutton(');

					// Fix broken this reference in list plugin
					src = src.replace('jQuery.each(this.templates[nodeName].classes, function () {', 'jQuery.each(this.templates[nodeName].classes, function (i, cssClass) {');
					src = src.replace('if (listToStyle.hasClass(this.cssClass) && this.cssClass === style) {', 'if (listToStyle.hasClass(cssClass) && cssClass === style) {');
					src = src.replace('listToStyle.removeClass(this.cssClass);', 'listToStyle.removeClass(cssClass);');

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

					return src;
				}
			}
		},
		bootstrap: {
			src: [
				baseUri + 'twitter-bootstrap/js/bootstrap-alert.js',
				baseUri + 'twitter-bootstrap/js/bootstrap-dropdown.js',
				baseUri + 'twitter-bootstrap/js/bootstrap-tooltip.js',
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
				baseUri + 'select2/select2.js'
			],
			dest: baseUri + 'select2.js',
			options: {
				banner: '',
				footer: '',
				process: function(src, filepath) {
					src = src.replace(/select2-(dropdown-open|measure-scrollbar|choice|resizer|chosen|search-choice-close|arrow|focusser|offscreen|drop|display-none|search|input|results|no-results|selected|selection-limit|more-results|match|active|container-active|container|default|allowclear|with-searchbox|focused|sizer|result|disabled|highlighted|locked)/g, 'neos-select2-$1');

					src = src.replace('if (this.indexOf("select2-") === 0) {', 'if (this.indexOf("neos-select2-") === 0) {');
					src = src.replace('if (this.indexOf("select2-") !== 0) {', 'if (this.indexOf("neos-select2-") !== 0) {');

					// make it work with position:fixed in the sidebar
					src = src.replace('if (above) {', 'if (false) {');
					src = src.replace('css.top = dropTop;', 'css.top = dropTop - $window.scrollTop();');

					// add bootstrap icon-close
					src = src.replace("<a href='#' onclick='return false;' class='neos-select2-search-choice-close' tabindex='-1'></a>", "<a href='#' onclick='return false;' class='neos-select2-search-choice-close'><i class='icon-remove'></i></a>");

					src = src.replace('this.body = thunk(function() { return opts.element.closest("body"); });', "this.body = thunk(function() { return $('#neos-application'); });");

					return src;
				}
			}
		},
		select2Css: {
			src: [
				baseUri + 'select2/select2.css'
			],
			dest: baseUri + 'select2/select2-prefixed.scss',
			options: {
				banner: '/* This file is autogenerated using the Gruntfile.*/',
				footer: '',
				process: function(src, filepath) {
					src = src.replace(/select2-(dropdown-open|measure-scrollbar|choice|resizer|chosen|search-choice-close|arrow|focusser|offscreen|drop|display-none|search|input|results|no-results|selected|selection-limit|more-results|match|active|container-active|container|default|allowclear|with-searchbox|focused|sizer|result|disabled|highlighted|locked)/g, 'neos-select2-$1');

					src = src.replace(/url\('select2.png'\)/g, "url('../Library/select2/select2.png')");

					return src;
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
				baseUri + 'mousetrap/mousetrap.js'
			],
			dest: baseUri + 'mousetrap.js'
		},

		create: {
			src: [
				baseUri + 'createjs/create.js'
			],
			dest: baseUri + 'create.js',
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
				baseUri + 'codemirror/lib/codemirror.js',
				baseUri + 'codemirror/mode/xml/xml.js',
				baseUri + 'codemirror/mode/css/css.js',
				baseUri + 'codemirror/mode/javascript/javascript.js',
				baseUri + 'codemirror/mode/htmlmixed/htmlmixed.js'
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
		},

		toastr: {
			src: [
				baseUri + 'toastr/toastr.js'
			],
			dest: baseUri + 'toastr.js',
			options: {
				process: function(src, filepath) {
					src = src.replace('toast-close-button', 'neos-close-button');
					src = src.replace("define(['jquery']", "define(['Library/jquery-with-dependencies']");
					return src;
				}
			}
		},

		nprogress: {
			src: [
				baseUri + 'nprogress/nprogress.js'
			],
				dest: baseUri + 'nprogress.js',
				options: {
				process: function(src, filepath) {
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
				baseUri + 'sly/sly.js'
			],
			dest: baseUri + 'sly.js',
			options: {
				process: function(src, filepath) {
					src = src.replace('jQuery.', '$.');
					return src;
				}
			}
		},

		jQueryWithDependencies: {
			src: [
				baseUri + 'jquery/jquery-2.0.3.js',
				baseUri + 'jquery/jquery-migrate-1.2.1.js',
				baseUri + 'jquery-easing/jquery.easing.1.3.js',
				baseUri + 'jquery-ui/js/jquery-ui-1.10.4.custom.js',
				baseUri + 'jquery-cookie/jquery.cookie.js',
				baseUri + 'jquery-dynatree/js/jquery.dynatree.js',
				baseUri + 'chosen/chosen/chosen.jquery.js',
				baseUri + 'jcrop/js/jquery.Jcrop.js',
				baseUri + 'select2.js',
				baseUri + 'sly.js',
				baseUri + 'bootstrap-components.js'
			],
			dest: baseUri + 'jquery-with-dependencies.js',
			options: {
				banner: 'define(function() {',
				footer: 'return jQuery.noConflict(true);' +
				'});',
				process: function(src, filepath) {
					switch (filepath) {
						case baseUri + 'jquery/jquery-2.0.3.js':
							// Replace call to define() in jquery which conflicts with the dependency resolution in r.js
							src = src.replace('define( "jquery", [], function () { return jQuery; } );', 'jQuery.migrateMute = true;');
						break;
						case baseUri + 'jquery-ui/js/jquery-ui-1.10.4.custom.js':
							// Prevent conflict with Twitter Bootstrap
							src += "$.widget.bridge('uitooltip', $.ui.tooltip);";
							src += "$.widget.bridge('uibutton', $.ui.button);";
						break;
					}
					return src;
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
