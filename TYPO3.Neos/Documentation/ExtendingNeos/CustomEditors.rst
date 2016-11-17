.. _custom-editors:

Custom Editors
==============

Like with validators, using custom editors is possible as well. Every dataType has its default editor set, which
can have options applied like::

	TYPO3:
	  Neos:
	    userInterface:
	      inspector:
	        dataTypes:
	          'string':
	            editor: 'TYPO3.Neos/Inspector/Editors/TextFieldEditor'
	            editorOptions:
	              placeholder: 'This is a placeholder'

On a property level this can be overridden like::

	TYPO3:
	  Neos:
	    userInterface:
	      inspector:
	        properties:
	          'string':
	            editor: 'My.Package/Inspector/Editors/TextFieldEditor'
	            editorOptions:
	              placeholder: 'This is my custom placeholder'

Namespaces can be registered like this, as with validators::

	TYPO3:
	  Neos:
	    userInterface:
	      requireJsPathMapping:
	        'My.Package/Editors': 'resource://My.Package/Public/Scripts/Inspector/Editors'

Editors should be named `<SomeType>Editor` and can be referenced by `My.Package/Inspector/Editors/MyCustomEditor`
for example.

Registering specific editors is also possible like this::

	TYPO3:
	  Neos:
	    userInterface:
	      inspector:
	        editors:
	          'TYPO3.Neos/BooleanEditor':
	            path: 'resource://TYPO3.Neos/Public/JavaScript/Content/Inspector/Editors/BooleanEditor'
