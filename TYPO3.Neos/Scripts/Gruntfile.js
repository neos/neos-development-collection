module.exports = function(grunt) {
	grunt.loadNpmTasks('grunt-contrib-concat');
	var baseUri = '../Resources/Public/Library/';
	grunt.initConfig({
		concat: {
			jQueryWithDependencies: {
				src: [
					baseUri + 'jquery/jquery-1.9.1.js',
					baseUri + 'jquery/jquery-migrate-1.2.1.js',
					baseUri + 'jquery-ui/js/jquery-ui-1.9b1.js',
					baseUri + 'jquery-dynatree/js/jquery.dynatree.js',
					baseUri + 'jquery-popover/jquery.popover.js',
					baseUri + 'chosen/chosen/chosen.jquery.js',
					baseUri + 'jcrop/js/jquery.Jcrop.js',
					baseUri + 'twitter-bootstrap/js/bootstrap-alert.js',
					baseUri + 'twitter-bootstrap/js/bootstrap-dropdown.js',
					baseUri + 'bootstrap-notify/js/bootstrap-notify.js'
				],
				dest: baseUri + 'jquery-with-dependencies.js',
				options: {
					banner: 'define(function() { ',
					footer: ';  jQuery.migrateMute = true;' +
								'return jQuery.noConflict(true);' +
							'});',
					process: function(src, filepath) {
						// Replace call to define() in jquery which conflicts with the dependency resolution in r.js
						return src.replace('define( "jquery", [], function () { return jQuery; } );', '');
					}
				}
			},
			handlebars: {
				src: [
					baseUri + 'handlebars/handlebars-1.0.0-rc.3.js'
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
					baseUri + 'emberjs/ember-1.0.0-rc.3.js'
				],
				dest: baseUri + 'ember.js',
				options: {
					banner: 'define(["Library/jquery-with-dependencies", "Library/handlebars"], function(jQuery, Handlebars) {' +
							'  var Ember = {exports: {}};' +
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
					footer: '});',
					process: function(src, filepath) {
						return src.replace(
							'window.localStorage.setItem(key, JSON.stringify(model.toJSONLD()));',
							'var newType = [], tmp = model.toJSONLD();' +
								'_.each(tmp["type"], function(v) { newType.push(v.id); });' +
								'tmp["@type"] = newType;' +
								'window.localStorage.setItem(key, JSON.stringify(tmp));');
					}
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
			}
		}
	});
};
