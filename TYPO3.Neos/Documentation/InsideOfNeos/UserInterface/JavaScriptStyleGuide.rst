======================
JavaScript Style Guide
======================

Code Conventions
================

* We use only the ``TAB`` character for indentation.
* We use ``UpperCamelCase`` for class names, and ``lowerCamelCase`` for method and property names.
* Methods and properties that begin with an underscore (``_``) are ``private``.
* Variables which contain *jQuery* elements should be named like ``$element``, starting with a ``$``.
  If it is a private jQuery element, prefix it with ``_$``
* We use ``that`` as a name for a closure reference to ``this``, but try to avoid it if there's the possibility of scope binding.
  Unfortunately jQuery's event handlers do not allow easy scope binding.

Code Documentation
------------------

TODO: still determine this.

RequireJS module skeleton
-------------------------

All JavaScript files are RequireJS modules. They should follow this structure:

WARNING: still has to be done and discussed

<javascript>
TODO
</javascript>

Public API, Private methods and attributes
------------------------------------------

All methods and properties which are public API are marked with ``@api``. The public API is supported
for a longer period, and when a public API changes, this is clearly communicated in the
Release Notes of a version.

On the contrary, we prefix ``private`` methods and attributes with an underscore. The *user* of an API should never
override or call methods ``private`` methods as they are not meant for him to be overridden.

There's also a type in between: methods which are not ``private`` but do not have a ``@api`` annotation. They
can be safely overridden by the user, and he should not experience any unwanted behavior. Still, the names or
functionality of these methods can change without notice between releases.
In the long run, all of these methods should become part of the public API, as soon as they are proven in real
life.

To sum it up, we have three types of methods/properties:

* ``@api`` methods: Public API, the user of the object can rely on the functionality to be stable, changes in @api are clearly communicated
* non-``@api`` but also not private: The user can use it, but needs to be aware the method might still change.
* private (prefixed with ``_``): The user should never ever call or access this. Very strange things might happen.

.. note::

	It is allowed to observe or bind to private properties within the Neos javascript code. This is because the property
	is not just meant as private object property, but as a non-api property.

When to use a new file
----------------------

JavaScript files can become pretty large, so there should be a point to create a new file. Having just one class per file
would be too much though, as this would end up in possibly hundreds of files, from which a lot will just have 20 lines
of code.

As we use requirejs for loading dependencies we came up with the following guidelines for creating a new file:

* Classes using a template include using the ``!text`` plugin should be in a separate file
* If a class is extended by another class, then it should be in a separate file so it can be easily loaded as dependency
* If a class is huge, and affecting readability of the definition file, then it should be moved to a single file
* It has preference to keep classes grouped together, so classes with just a few lines stay grouped together, so if none
  of the above is true the classes stays in the main file.

