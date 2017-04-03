======================
Ember.JS Tips & Tricks
======================

Dealing with classes and objects
================================

* Always extend from ``Ember.Object`` (or a subclass)
* Extension is done using ``Ember.Object.extend({...})``
* Never use ``new`` to instantiate new objects. Instead, use ``TheObject.create(...)``
* All objects have generic ``set(key, value)`` and ``get(key)`` methods, *which should be used
  under all circumstances*!

The following example shows this:

.. code-block:: javascript

	var Foo = Ember.Object.extend({
		someValue: 'hello',
		myMethod: function() {
			alert(this.get('someValue'));
		}
	});

	var fooInstance = Foo.create({
		someValue: 'world'
	});
	fooInstance.myMethod(); // outputs "world"


Inheritance can be used just as in PHP, since Emberjs binds a special ``._super()`` function for every
method call (in fact the function is wrapped to create this special ``_super`` method). So calling the current method
of the superclass can be done without specifying the superclass and method name.

.. code-block:: javascript

	var Foo = Ember.Object.extend({
		greet: function(name) {
			return 'Hello, ' + name;
		}
	});
	var Bar = Foo.extend({
		greet: function(name) {
			return 'Aloha and ' + this._super(name);
		}
	});

	Bar.create().greet('Neos'); // outputs "Aloha and Hello, Neos"


Data Binding tips and tricks
============================

To create a *computed property*, implement it as function and append +.property()+:

.. code-block:: javascript

	var Foo = Ember.Object.extend({
		someComputedValue: function() {
			return "myMethod";
		}.property()
	});

If your computed property reads other values, specify the dependent values as
parameters to ``property()``. If the computed property is deterministic and depends only on the
dependant values, it should be marked further with ``.cacheable()``.

.. code-block:: javascript

	var Foo = Ember.Object.extend({
		name: 'world',
		greeting: function() {
			return "Hello " + this.attr('name');
		}.property('name').cacheable()

	});

Now, every time ``name`` changes, the system re-evaluates ``greeting``.

.. note:: Forgetting ``.cacheable()`` can have severe performance penalties and result
      in circular loops, in worst case freezing the browser completely.

You can also use a getter / setter on a property, if you do this it's **extremely important to return
the value of the property** in the setter method.

.. code-block:: javascript

	var Foo = Ember.Object.extend({
		firstName: null,
		lastName: null,

		fullName: function(key, value) {
			if (arguments.length === 1) {
				return this.get('firstName') + ' ' + this.get('lastName');
			} else {
				var parts = value.split(' ');
				this.set('firstName', parts[0]);
				this.set('lastName', parts[1]);

				return value;
			}
		}.property('firstName', 'lastName').cacheable()
	});


Observe changes
---------------

To react on changes of properties in models or views (or any other class extending ``Ember.Observable``), a method marked as an observer can be used. Call
``.observes('propertyName')`` on a private method to be notified whenever a property changes.

.. code-block:: javascript

	var Foo = Ember.Object.extend({
		name: 'world',
		_nameDidChange: function() {
			console.log('name changed to', this.get('name'));
		}.observes('name')
	});
