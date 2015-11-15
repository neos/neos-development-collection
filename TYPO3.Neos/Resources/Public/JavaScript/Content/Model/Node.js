define([
	'emberjs',
	'Library/jquery-with-dependencies',
	'Library/underscore',
	'Shared/Configuration',
	'vie'
], function(
	Ember,
	$,
	_,
	Configuration,
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
			return this.get('nodeTypeSchema.ui.label');
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
			return subject.substring(1, subject.length - 1);
		}.property('_vieEntity'),

		/**
		 * @return {boolean}
		 */
		isHideable: function() {
			return this.get('_vieEntity').has('typo3:_hidden');
		},

		/**
		 * @return {boolean}
		 */
		isHidden: function() {
			return this.get('_vieEntity').get('typo3:_hidden');
		},

		/**
		 * @return {string}
		 */
		nodeLabel: function() {
			if (this.get('_vieEntity').get('typo3:title') !== undefined) {
				return this.get('_vieEntity').get('typo3:title');
			}

			return '';
		}.property('_vieEntity'),

		/**
		 * Receive the node type schema
		 *
		 * @return {object}
		 */
		nodeTypeSchema: function() {
			var schema = Configuration.get('Schema');
			return schema[this.get('nodeType')];
		}.property()
	});

	var namespace = Configuration.get('TYPO3_NAMESPACE');
	Entity.reopenClass({
		/**
		 * @param {object} vieEntity
		 * @param {object} attributes
		 * @param {function} filterFn
		 * @return {object}
		 */
		extractAttributesFromVieEntity: function(vieEntity, attributes, filterFn) {
			var cleanAttributes = {};
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
				type;
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
				type = type.substr(0, type.length - 1);
			}
			return type;
		}
	});
	return Entity;
});
