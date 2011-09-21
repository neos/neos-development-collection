define(
['block/block'],
function(block) {
    "use strict";
	var exports = {};
	var $ = window.alohaQuery || window.jQuery;

	/**
	 * This is the TYPO3 Block, which we use for all TYPO3-related functionality.
	 *
	 * We only render this block *once*,
	 */
	exports.TYPO3Block = block.AbstractBlock.extend({

		/**
		 * Already rendered?
		 *
		 * @var Boolean
		 */
		_alreadyRendered: false,

		/**
		 * Cached content type schema
		 */
		_cachedContentTypeSchema: null,

		/**
		 * an object which maps the lowercased property name to the correctly
		 * cased property name.
		 *
		 * @var Object
		 */
		_caseSensitivePropertyNameCache: null,

		/**
		 * Receive the content type schema; and also build up the _caseSensitivePropertyNameCache
		 */
		_getContentTypeSchema: function() {
			if (!this._cachedContentTypeSchema) {
				this._cachedContentTypeSchema = T3.Configuration.Schema[this.attr('__contenttype')];
				if (!this._cachedContentTypeSchema || !this._cachedContentTypeSchema.properties) return this._cachedContentTypeSchema;

				var caseSensitivePropertyNameCache = {};
				$.each(this._cachedContentTypeSchema.properties, function(key) {
					caseSensitivePropertyNameCache[key.toLowerCase()] = key;
				});
				this._caseSensitivePropertyNameCache = caseSensitivePropertyNameCache;
			}
			return this._cachedContentTypeSchema;
		},

		/**
		 * Get the title of the block
		 */
		getTitle: function() {
			var schema = this._getContentTypeSchema();
			return (schema ? schema.label : '');
		},

		/**
		 * Rendering. Only called once, aloha-fies the editable sub properties (making them Aloha Editables).
		 */
		render: function() {
			if (this._alreadyRendered) return;

			if (!this._getContentTypeSchema() || !this._getContentTypeSchema().inlineEditableProperties) return;

			// Store the editable sub properties in the parent element
			this._getContentTypeSchema().inlineEditableProperties.forEach(function(propertyName) {
				this.attr(propertyName, this.$innerElement.find('*[data-propertyname="' + propertyName + '"]').html(), true);
			}, this);

			// Aloha-fy the editable sub elements
			this._getContentTypeSchema().inlineEditableProperties.forEach(function(propertyName) {
				this.$innerElement.find('*[data-propertyname="' + propertyName + '"]').aloha();
			}, this);

			this._alreadyRendered = true;
		},

		/**
		 * We override this parent method, as we want to only render the handles,
		 * and not the inner contents (as they are pre-rendered from the server and we do not
		 * want to touch them).
		 */
		_renderSurroundingElements: function() {
			this.renderHandles();
		},

		/**
		 * Renders the Handles around the block.
		 * This function can be called very often, and must internally deal
		 * with the situation that the handles are already created.
		 */
		renderHandles: function() {
			if (this.element.find('.t3-contentelement.t3-contentelement-removed').length === 0) {
				this._renderHandle('t3-delete-handle', 'Delete element', T3.Content.Controller.BlockActions.deleteBlock, T3.Content.Controller.BlockActions);
			}
			this._renderHandle('t3-cut-handle', 'Cut', T3.Content.Controller.BlockActions.cut, T3.Content.Controller.BlockActions);
			this._renderHandle('t3-paste-before-handle t3-handle-hidden', 'Paste before', T3.Content.Controller.BlockActions.pasteBefore, T3.Content.Controller.BlockActions);
			this._renderHandle('t3-paste-after-handle t3-handle-hidden', 'Paste after', T3.Content.Controller.BlockActions.pasteAfter, T3.Content.Controller.BlockActions);
			this._renderHandle('t3-copy-handle', 'Copy', T3.Content.Controller.BlockActions.copy, T3.Content.Controller.BlockActions);
			this._renderHandle('t3-add-above-handle', 'Add above', T3.Content.Controller.BlockActions.addAbove, T3.Content.Controller.BlockActions);
			this._renderHandle('t3-add-below-handle', 'Add below', T3.Content.Controller.BlockActions.addBelow, T3.Content.Controller.BlockActions);

			this.element.find('.t3-status-indicator').remove();
			if (this.attr('_status')) {
				// FIXME: do not output _status directly, but do it using CSS or localization.
				var statusIndicator = $('<span class="t3-status-indicator t3-status-indicator-' + this.attr('_status')  + '">' + this.attr('_status') + '</span>');
				this.element.prepend(statusIndicator);
			}
		},

		/**
		 * Helper function which renders a single handle, if it does not exist yet
		 *
		 * @param {String} cssClass CSS class for the handle
		 * @param {String} innerHTML Inner HTML for the handle
		 * @param {Function} clickHandler for the click event of the handle. This function gets the current T3.Content.Model.Block as first parameter, nodePath as second parameter and the handle as third parameter.
		 * @param {Function} callback the Callback function to be executed when clicking the handle.
		 * @param {Object} scope the scope to use for the callback
		 */
		_renderHandle: function(cssClass, innerHtml, clickHandler, scope) {
			if (this.element.find('.' + cssClass).length == 0) {
				var handle = $('<span class="t3-handle ' + cssClass + '">' + innerHtml + '</span>');
				this.element.prepend(handle);

				var nodePath = handle.parent('.aloha-block').attr('about');

				handle.click(function(event) {
					clickHandler.call(scope, nodePath, handle);
				});
			}
		},

		/**
		 * We need some custom attribute setter as we need to update the innerHTML if an
		 * inline-editable is modified. Furthermore, we lowercase the key beforehand.
		 */
		_setAttribute: function(key, value) {
			key = key.toLowerCase();
			this._super(key, value);

			if (!this._getContentTypeSchema() || !this._getContentTypeSchema().inlineEditableProperties) return;
			if (this._getContentTypeSchema().inlineEditableProperties.indexOf(key) !== -1) {
				this.$innerElement.find('*[data-propertyname="' + key + '"]').html(value);
			}
		},

		/**
		 * As '__contenttype' is used *inside* getContentTypeSchema and thus also
		 * inside getAttributes and setAttribute, we need to handle this case
		 * separately in order to prevent recursion.
		 */
		_getAttribute: function(key) {
			if (key === '__contenttype') {
				return this.element.attr('data-__contenttype');
			} else {
				return this._super(key);
			}
		},

		/**
		 * We also override getAttributes, which returns *case sensitive* property
		 * names.
		 */
		_getAttributes: function() {
			var attributesWithProperCase = {};
			var attributes = this._super();

			this._getContentTypeSchema(); // We just fetch the schema to be 100% sure that the _caseSensitivePropertyNameCache is built

			var caseSensitivePropertyNameCache = this._caseSensitivePropertyNameCache;
			$.each(attributes, function(key, value) {
				if (caseSensitivePropertyNameCache[key]) {
					attributesWithProperCase[caseSensitivePropertyNameCache[key]] = value;
				} else {
					attributesWithProperCase[key] = value;
				}

			});

			return attributesWithProperCase;
		},

		/**
		 * get schema returns a representation which is used to fill the *inspector*
		 */
		getSchema: function() {
			return this._getContentTypeSchema();
		}
	});

	return exports;
});