define([
	'emberjs',
	'Library/jquery-with-dependencies',
	'Library/underscore',
	'Shared/Configuration',
	'Shared/I18n',
	'vie'
], function(
	Ember,
	$,
	_,
	Configuration,
	I18n,
	vieInstance
) {
	var Entity = Ember.Object.extend({
		/**
		 * The jQuery element of the entity
		 */
		$element: null,

		/**
		 * If TRUE, the entity contains modifications.
		 */
		modified: false,
		// TODO: "publishable"
		publishable: false,
		status: '',

		/**
		 * The underlying VIE entity
		 */
		_vieEntity: Ember.required(),

		/**
		 * Triggered each time "publishable" and "modified" properties change.
		 *
		 * If something is *publishable* and *modified*, i.e. is already saved
		 * in the current workspace AND has more local changes, the system
		 * will NOT publish the not-visible changes.
		 *
		 * @return {void}
		 */
		_onStateChange: function() {
			if (this.get('modified')) {
				this.set('status', 'modified');
			} else if (this.get('publishable')) {
				this.set('status', 'publishable');
			} else {
				this.set('status', '');
			}
		}.observes('publishable', 'modified'),

		/**
		 * @return {void}
		 */
		_applyHiddenClass: function() {
			if (this.get('attributes._hidden')) {
				this.get('$element').addClass('neos-contentelement-hidden');
			} else {
				this.get('$element').removeClass('neos-contentelement-hidden');
			}
		}.observes('typo3:_hidden'),

		/**
		 * @return {object}
		 */
		nodeType: function() {
			return Entity.extractNodeTypeFromVieEntity(this.get('_vieEntity'));
		}.property('_vieEntity').volatile(),

		nodeTypeLabel: function() {
			return I18n.translate(this.get('nodeTypeSchema.ui.label'));
		}.property('nodeTypeSchema.ui.label'),

		/**
		 * @return {void}
		 */
		init: function() {
			var that = this,
				vieEntity = this.get('_vieEntity');

			this.set('modified', !$.isEmptyObject(vieEntity.changed));
			this.set('publishable', this.getAttribute('__workspaceName') !== 'live');

			var $entityElement = vieInstance.service('rdfa').getElementBySubject(vieEntity.getSubject(), $(document));

			// this event fires if inline content changes
			$entityElement.bind('midgardeditablechanged', function(event, data) {
				that.set('modified', !$.isEmptyObject(vieEntity.changed));
			});

			// this event fires if content changes through the property inspector
			vieEntity.on('change', function() {
				that.set('modified', !$.isEmptyObject(vieEntity.changed));
				that.set('publishable', that.getAttribute('__workspacename') !== 'live');
				var changedAttributes = Entity.extractAttributesFromVieEntity(vieEntity, vieEntity.changed);
				_.each(changedAttributes, function(value, key) {
					that.notifyPropertyChange('typo3:' + key);
				});
			});

			this.addObserver('typo3:__label', function() {
				this.notifyPropertyChange('nodeLabel');
			});

			this.set('$element', $entityElement);
		},

		/**
		 * @return {object}
		 */
		attributes: function() {
			if (arguments.length === 1) {
				return Entity.extractAttributesFromVieEntity(this.get('_vieEntity'));
			}
			return {};
		}.property('_vieEntity').volatile(),

		/**
		 * Check if an attribute exists on the underlying VIE entity
		 *
		 * @param {string} key
		 * @return {void}
		 */
		hasAttribute: function(key) {
			var attributeName = 'typo3:' + key;
			return this.get('_vieEntity').has(attributeName);
		},

		/**
		 * Set an attribute on the underlying VIE entity
		 *
		 * @param {string} key
		 * @param {mixed} value
		 * @param {object} options
		 * @return {void}
		 */
		setAttribute: function(key, value, options) {
			var attributeName = 'typo3:' + key;
			this.propertyWillChange(attributeName);
			this.get('_vieEntity').set(attributeName, value, options);
			this.propertyDidChange(attributeName);
		},

		/**
		 * Set attributes on the underlying VIE entity
		 *
		 * @param {object} attributes
		 * @param {object} options
		 * @return {void}
		 */
		setAttributes: function(attributes, options) {
			var prefixedAttributes = {};
			_.each(attributes, function(value, key) {
				var prefixedAttributeName = 'typo3:' + key;
				prefixedAttributes[prefixedAttributeName] = value;
				this.propertyWillChange(prefixedAttributeName);
			}, this);
			this.get('_vieEntity').set(prefixedAttributes, options);
			_.each(prefixedAttributes, function(value, key) {
				this.propertyDidChange(key);
			}, this);
		},

		/**
		 * Get an attribute on the underlying VIE entity
		 *
		 * @param {string} key
		 * @return {void}
		 */
		getAttribute: function(key) {
			var attributeName = 'typo3:' + key;
			return this.get('_vieEntity').get(attributeName);
		},

		/**
		 * @param {string} key
		 * @return {object}
		 */
		getPreviousAttribute: function(key) {
			var vieEntity = this.get('_vieEntity');
			return Entity.extractAttributesFromVieEntity(vieEntity, vieEntity.previousAttributes())[key];
		},

		/**
		 * @return {string}
		 */
		nodePath: function() {
			var subject = this.get('_vieEntity').getSubject();
			return subject.slice(1, -1);
		}.property('_vieEntity').volatile(),

		/**
		 * @return {boolean}
		 */
		isHideable: function() {
			return this.hasAttribute('_hidden');
		},

		/**
		 * @return {boolean}
		 */
		isHidden: function() {
			return this.getAttribute('_hidden');
		},

		/**
		 * @return {string}
		 */
		nodeLabel: function() {
			var label = this.getAttribute('__label');
			if (label) {
				return label;
			}

			var entity = this.get('_vieEntity');
			if (this.hasAttribute('title') && this.getAttribute('title')) {
				label = this.getAttribute('title');
			} else if (this.hasAttribute('text') && this.getAttribute('text')) {
				label = this.getAttribute('text');
			} else {
				label = (this.get('nodeTypeLabel') || this.get('nodeType')) + ' (' + this.getAttribute('_name') + ')';
			}

			label = $('<u/>').html(label).text().trim();

			var croppedLabel = label.substr(0, 30).trim();
			return croppedLabel + (croppedLabel.length < label.length ? ' …' : '');
		}.property().volatile(),

		/**
		 * Receive the node type schema
		 *
		 * @return {object}
		 */
		nodeTypeSchema: function() {
			var schema = Configuration.get('Schema');
			return schema[this.get('nodeType')];
		}.property('nodeType')
	});

	Entity.reopenClass({
		/**
		 * @param {object} vieEntity
		 * @param {object} attributes
		 * @param {function} filterFn
		 * @return {object}
		 */
		extractAttributesFromVieEntity: function(vieEntity, attributes, filterFn) {
			var cleanAttributes = {},
				namespace = vieEntity.vie.namespaces.get('typo3');
			attributes = _.isEmpty(attributes) ? vieEntity.attributes : attributes;
			_.each(attributes, function(value, subject) {
				var property = vieEntity.fromReference(subject);
				if (property.indexOf(namespace) === 0) {
					property = property.replace(namespace, '');
					if (!filterFn || filterFn(property, value)) {
						cleanAttributes[property] = value;
					}
				}
			});
			return cleanAttributes;
		},

		/**
		 * @param {object} vieEntity
		 * @return {string}
		 */
		extractNodeTypeFromVieEntity: function(vieEntity) {
			var types = vieEntity.get('@type'),
				type,
				namespace = vieEntity.vie.namespaces.get('typo3');
			if (!_.isArray(types)) {
				types = [types];
			}

			type = _.find(
				_.map(types, function(type) {
					return type.toString();
				}), function(type) {
					return type.indexOf('<' + namespace) === 0;
				}
			);

			if (type) {
				type = type.substr(namespace.length + 1);
				type = type.slice(0, -1);
			}
			return type;
		}
	});
	return Entity;
});
