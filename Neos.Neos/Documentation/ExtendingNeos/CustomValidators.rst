.. _custom-validators:

Custom Validators
=================

It is possible to register paths into RequireJS (the JavaScript file and module loader used by Neos, see
http://requirejs.org) and by this custom validators into Neos. Validators should be named '<SomeType>Validator',
and can be referenced by ``My.Package/Public/Scripts/Validators/FooValidator`` for example.

Namespaces can be registered like this in *Settings.yaml*::

	TYPO3:
	  Neos:
	    userInterface:
	      requireJsPathMapping:
	        'My.Package/Validation': 'resource://My.Package/Public/Scripts/Validators'

Registering specific validators is also possible like this::

	TYPO3:
	  Neos:
	    userInterface:
	      validators:
	        'My.Package/AlphanumericValidator':
	          path: 'resource://My.Package/Public/Scripts/Validators/FooValidator'