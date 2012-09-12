define(['jquery', 'vie/instance', 'emberjs', 'emberjs/dictionary-object'], function($, vieInstance, Ember, DictionaryObject) {
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
		 * The underyling VIE entity
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
			if (this.getPath('attributes._hidden')) {
				this.get('$element').addClass('t3-contentelement-hidden');
			} else {
				this.get('$element').removeClass('t3-contentelement-hidden');
			}
		}.observes('typo3:_hidden'),

		contentType: function() {
			var types = this.get('_vieEntity').get('@type'),
				type;
			if (!_.isArray(types)) {
				types = [types];
			}

			type = _.find(
				_.map(types, function(type) {
					return type.toString();
				}), function(type) {
					return type.indexOf('<' + T3.ContentModule.TYPO3_NAMESPACE) === 0;
				});

			if (type) {
				type = type.substr(T3.ContentModule.TYPO3_NAMESPACE.length + 1);
				type = type.substr(0, type.length - 1);
			}
			return type;
		}.property('_vieEntity'),

		init: function() {
			var that = this;

			this.set('modified', !$.isEmptyObject(that.get('_vieEntity').changed));
			that.set('publishable', that.get('_vieEntity').get(T3.ContentModule.TYPO3_NAMESPACE + '__workspacename') !== 'live');

			var $entityElement = vieInstance.service("rdfa").getElementBySubject(this.get('_vieEntity').getSubject(), jQuery(document));
			$entityElement.bind("midgardeditablechanged", function(event, data) {
					// this event fires if inline content changes
				that.set('modified', !$.isEmptyObject(that.get('_vieEntity').changed));
			});
			this.get('_vieEntity').on('change', function() {
					// this event fires if content changes through the property inspector
				that.set('modified', !$.isEmptyObject(that.get('_vieEntity').changed));

				that.set('publishable', that.get('_vieEntity').get(T3.ContentModule.TYPO3_NAMESPACE + '__workspacename') !== 'live');
			});

			this.set('$element', $entityElement);
		},

		attributes: function(k, v) {
			if (arguments.length == 1) {
				return Entity.extractAttributesFromVieEntity(this.get('_vieEntity'));
			}
		}.property('_vieEntity'),

		/**
		 * Set an attribute on the underlying VIE entity
		 *
		 * @param key
		 * @param value
		 */
		setAttribute: function(key, value) {
			var attributeName = 'typo3:' + key;
			this.propertyWillChange(attributeName);
			this.get('_vieEntity').set(attributeName, value);
			this.propertyDidChange(attributeName);
		},

		/**
		 * Receive the content type schema; and also build up the _caseSensitivePropertyNameCache
		 */
		contentTypeSchema: function() {
			return T3.Configuration.Schema[this.get('contentType')];
		}.property().cacheable()

	});

	Entity.reopenClass({
		extractAttributesFromVieEntity: function(vieEntity, filterFn) {
			var cleanAttributes = {};
			_.each(vieEntity.attributes, function(value, subject) {
				var property = vieEntity.fromReference(subject);
				if (property.indexOf(T3.ContentModule.TYPO3_NAMESPACE) === 0) {
					property = property.replace(T3.ContentModule.TYPO3_NAMESPACE, '');
					if (!filterFn || filterFn(property, value)) {
						cleanAttributes[property] = value;
					}
				}
			});
			return cleanAttributes;
		}
	});
	return Entity;
});