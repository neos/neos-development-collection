/**
 * A simple map object that accepts any key/value combination.
 * Has a method to return all key/value combinations and a changed property to observe.
 */
define(
[
	'emberjs',
	'Library/underscore'
], function (Ember, _) {
	return Ember.Object.extend({

        changed: 0,
        propertyNames: [],

        init: function(initialMap) {
            this._super();
            initialMap = initialMap || {};
            var initialKeyValues = _.pairs(initialMap);
            Ember.beginPropertyChanges();
            for (var i = 0; i < initialKeyValues.length; i++) {
                this.setUnknownProperty(initialKeyValues[i][0], initialKeyValues[i][1]);
            }
            Ember.endPropertyChanges();
        },

        setUnknownProperty: function(key, value) {
            this.propertyNames.push(key);
            this[key] = value;
            this.incrementProperty('changed');
            this.addObserver(key, this._hasChanged);
        },

        _hasChanged: function() {
            this.incrementProperty('changed');
        },

        /**
         * Get a simple JavaScript object with all key/value combinations set in this map.
         *
         * @returns {object}
         */
        getAllProperties: function() {
            var propertyNames = _.uniq(this.propertyNames),
                allProperties = {};

            for (var i = 0; i < propertyNames.length; i++) {
                allProperties[propertyNames[i]] = this.get(propertyNames[i]);
            }

            return allProperties;
        },

        /**
         * Get a JavaScript array containing objects of the form {name, value} with all set properties.
         *
         * @returns {Array}
         */
        getKeyValueArray: function() {
            var propertyNames = _.uniq(this.propertyNames),
                keyValueArray = [];

            for (var i = 0; i < propertyNames.length; i++) {
                keyValueArray.push({name: propertyNames[i], value: this.get(propertyNames[i])});
            }

            return keyValueArray;
        }
	});
});
