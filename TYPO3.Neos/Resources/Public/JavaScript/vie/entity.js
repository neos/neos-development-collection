define(['Library/jquery-with-dependencies', 'Library/underscore', 'Content/Application', 'vie/instance', 'emberjs', 'emberjs/dictionary-object'], function($, _, ContentModule, vieInstance, Ember, DictionaryObject) {
	if (window._requirejsLoadingTrace) window._requirejsLoadingTrace.push('vie/entity');

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
		_vieEntity: null,

		/**
		 * Triggered each time "publishable" and "modified" properties change.
		 *
		 * If something is *publishable* and *modified*, i.e. is already saved
		 * in the current workspace AND has more local changes, the system
		 * will NOT publish the not-visible changes.
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

		_applyHiddenClass: function() {
			if (this.get('attributes._hidden')) {
				this.get('$element').addClass('neos-contentelement-hidden');
			} else {
				this.get('$element').removeClass('neos-contentelement-hidden');
			}
		}.observes('typo3:_hidden'),

		nodeType: function() {
			return Entity.extractNodeTypeFromVieEntity(this.get('_vieEntity'));
		}.property('_vieEntity').volatile(),

		init: function() {
			var that = this,
				vieEntity = this.get('_vieEntity');

			this.set('modified', !$.isEmptyObject(vieEntity.changed));
			this.set('publishable', vieEntity.get(ContentModule.TYPO3_NAMESPACE + '__workspacename') !== 'live');

			var $entityElement = vieInstance.service('rdfa').getElementBySubject(vieEntity.getSubject(), $(document));
				// this event fires if inline content changes
			$entityElement.bind('midgardeditablechanged', function(event, data) {
				that.set('modified', !$.isEmptyObject(vieEntity.changed));
			});
				// this event fires if content changes through the property inspector
			vieEntity.on('change', function() {
				that.set('modified', !$.isEmptyObject(vieEntity.changed));
				that.set('publishable', vieEntity.get(ContentModule.TYPO3_NAMESPACE + '__workspacename') !== 'live');
			});

			this.set('$element', $entityElement);
		},

		attributes: function(k, v) {
			if (arguments.length == 1) {
				return Entity.extractAttributesFromVieEntity(this.get('_vieEntity'));
			}
		}.property('_vieEntity').volatile(),

		/**
		 * Set an attribute on the underlying VIE entity
		 *
		 * @param {string} key
		 * @param {mixed} value
		 * @param {object} options
		 */
		setAttribute: function(key, value, options) {
			var attributeName = 'typo3:' + key;
			this.propertyWillChange(attributeName);
			this.get('_vieEntity').set(attributeName, value, options);
			this.propertyDidChange(attributeName);
		},

		nodePath: function() {
			var subject = this.get('_vieEntity').getSubject();
			return subject.substring(1, subject.length - 1);
		}.property('_vieEntity').cacheable(),

		/**
		 * Receive the node type schema
		 */
		nodeTypeSchema: function() {
			return T3.Configuration.Schema[this.get('nodeType')];
		}.property().cacheable()

	});

	Entity.reopenClass({
		extractAttributesFromVieEntity: function(vieEntity, attributes, filterFn) {
			var cleanAttributes = {};
			attributes = _.isEmpty(attributes) ? vieEntity.attributes : attributes;
			_.each(attributes, function(value, subject) {
				var property = vieEntity.fromReference(subject);
				if (property.indexOf(ContentModule.TYPO3_NAMESPACE) === 0) {
					property = property.replace(ContentModule.TYPO3_NAMESPACE, '');
					if (!filterFn || filterFn(property, value)) {
						cleanAttributes[property] = value;
					}
				}
			});
			return cleanAttributes;
		},
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
					return type.indexOf('<' + ContentModule.TYPO3_NAMESPACE) === 0;
				});

			if (type) {
				type = type.substr(ContentModule.TYPO3_NAMESPACE.length + 1);
				type = type.substr(0, type.length - 1);
			}
			return type;
		}
	});
	return Entity;
});