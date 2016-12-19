define(['emberjs'], function(Ember) {
	var DictionaryObject = Ember.Object.extend({
		setUnknownProperty: function(key, value) {
			this[key] = value;
			this.addObserver(key, this, 'anyPropertyChanged');
			this.anyPropertyChanged(this, key);
		},

		anyPropertyChanged: function(object, propertyName) {
			this.propertyWillChange('@any');
			this.propertyDidChange('@any');
		}
	});
	DictionaryObject.reopenClass({
		create: function(properties) {
			var instance = Ember.Object.create.apply(DictionaryObject, arguments),
				key;
			for (key in properties) {
				instance.addObserver(key, instance, 'anyPropertyChanged');
			}
			return instance;
		}
	});
	return DictionaryObject;
});