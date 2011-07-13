define(
['block/block'],
function(block) {
    "use strict";
	var exports = {};

	exports.AbstractBlock = block.AbstractBlock.extend({
		renderToolbar: function() {
			var that = this;
			var addAboveHandle = $('<span class="t3-add-above-handle">Add above</span>');
			this.element.prepend(addAboveHandle);
			/*addAboveHandle.click(function() {
				// TODO implement
				return false;
			});*/

			var addBelowHandle = $('<span class="t3-add-below-handle">Add below</span>');
			this.element.prepend(addBelowHandle);
			/*addBelowHandle.click(function() {
				// TODO implement
				return false;
			});*/
			if (this.attr('_status')) {
				// FIXME: do not output _status directly, but do it using CSS or localization.
				var statusIndicator = $('<span class="t3-status-indicator t3-status-indicator-' + this.attr('_status')  + '">' + this.attr('_status') + '</span>');
				this.element.prepend(statusIndicator);
			}
		}
	});

	// TODO: should be generic lateron.
	exports.TextBlock = exports.AbstractBlock.extend({
		title: 'Text',

		_alreadyRendered: false,

		init: function() {
			this.attr('headline', this.element.find('h1').html(), true);
			this.attr('text', this.element.find('*[data-propertyname="text"]').html(), true);
		},
		render: function(element) {
			if (this._alreadyRendered) return;
			return '<h1 class="aloha-editable" data-propertyname="headline">' + this.attr('headline') + '</h1><div class="aloha-editable"  data-propertyname="text"><p>' + this.attr('text') + '</p></div>'; // TODO: use templateable block here
		},
		_renderSurroundingElements: function() {
			if (this._alreadyRendered) return;

			this.element.empty();
			this.element.append(this.$innerElement);

			this.createEditables(this.$innerElement);

			this.renderToolbar();
			this._alreadyRendered = true;
		},
		getSchema: function() {
			return null;
		},
		_setAttribute: function(key, value) {
			if (key === 'about') {
				this.element.attr('about', value);
			} else {
				this.element.attr('data-' + key, value);
			}
			if (key === 'headline') {
				this.element.find('h1').html(value);
			} else if (key === 'text') {
				this.element.find('*[data-propertyname="text"]').html(value);
			}
		}
	});

	exports.PluginBlock = exports.AbstractBlock.extend({
		title: 'Plugin',

		getSchema: function() {
			return [
				{
					key: 'Plugin Settings',
					properties: [
						{
							key: 'package',
							type: 'string',
							label: 'Package'
						}, {
							key: 'controller',
							type: 'string',
							label: 'Controller'
						}
					]
				}
			];
		}
	});
	return exports;
});