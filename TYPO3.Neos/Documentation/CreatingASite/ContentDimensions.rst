.. _content-dimensions:

==================
Content Dimensions
==================

Introduction
============

Content dimensions are a generic concept to have multiple *variants* of a node. A dimension can be anything like
"language", "country" or "customer segment". The content repository supports any number of dimensions.
Node variants can have multiple values for each dimension and are connected by the same identifier. This enables a
*single-tree* approach for localization, personalization or other variations of the content in a site.

If content is rendered and thus fetched from the content repository, it will always happen in a *context*. This context
contains a list of values for each dimension that specifies which dimension values are visible and in which *fallback
order* these should apply. So the same node variants can yield different results depending on the context that is used
to fetch the nodes.

*Dimension presets* assign a name to the list of dimension values and are used to display dimensions in the
user interface or in the routing. They represent the allowed combinations of dimension values.

.. TODO Include a diagram of dimension fall-backs and node variants
.. TODO Document vs. content node behavior

.. tip:: See the :ref:`cookbook-translating-content` cookbook for a step-by-step guide to create a multi-lingual
         website with Neos.

Dimension Configuration
=======================

The available dimensions and presets can be configured via settings:

.. code-block:: yaml

	TYPO3:
	  TYPO3CR:
	    contentDimensions:

	      # Content dimension "language" serves for translation of content into different languages. Its value specifies
	      # the language or language variant by means of a locale.
	      'language':
	        # The default dimension that is applied when creating nodes without specifying a dimension
	        default: 'mul_ZZ'
	        # The default preset to use if no URI segment was given when resolving languages in the router
	        defaultPreset: 'all'
	        label: 'Language'
	        icon: 'icon-language'
	        presets:
	          'all':
	            label: 'All languages'
	            values: ['mul_ZZ']
	            uriSegment: 'all'
	          # Example for additional languages:

	          'en_GB':
	            label: 'English (Great Britain)'
	            values: ['en_GB', 'en_ZZ', 'mul_ZZ']
	            uriSegment: 'gb'
	          'de':
	            label: 'German (Germany)'
	            values: ['de_DE', 'de_ZZ', 'mul_ZZ']
	            uriSegment: 'de'

The TYPO3CR and Neos packages don't provide any dimension configuration per default.

Preset Constraints
==================

Neos can be configured to work with more than one content dimension. A typical use case is to define separate dimensions
for language and country: pages with product descriptions may be available in English and German, but the English
content needs to be different for the markets target to the UK or Germany respectively. However, not all possible
combinations of ``language`` and ``country`` make sense and thus should not be accessible. The allowed combinations
of content dimension presets can be controlled via the preset constraints feature.

Consider a website which has dedicated content for the US, Germany and France. The content for each country is available
in English and their respective local language. The following configuration would make sure that the combinations
"German – US", "German - France", "French - US" and "French - Germany" are not allowed:

.. code-block:: yaml

	TYPO3:
	  TYPO3CR:
	    contentDimensions:
	      'language':
	        default: 'en'
	        defaultPreset: 'en'
	        label: 'Language'
	        icon: 'icon-language'
	        presets:
	          'en':
	            label: 'English'
	            values: ['en']
	            uriSegment: 'en'
	          'de':
	            label: 'German'
	            values: ['de']
	            uriSegment: 'de'
	            constraints:
	              country:
	                'us': false
	                'fr': false
	          'fr':
	            label: 'French'
	            values: ['fr']
	            uriSegment: 'fr'
	            constraints:
	              country:
	                'us': false
	                'de': false
	      'country':
	        default: 'us'
	        defaultPreset: 'us'
	        label: 'Country'
	        icon: 'icon-globe'
	        presets:
	          'us':
	            label: 'United States'
	            values: ['us']
	            uriSegment: 'us'
	          'de':
	            label: 'Germany'
	            values: ['de']
	            uriSegment: 'de'
	          'fr':
	            label: 'France'
	            values: ['fr']
	            uriSegment: 'fr'

Instead of configuring every constraint preset explicitly, it is also possible to allow or disallow all presets of a
given dimension by using the wildcard identifier. The following configuration has the same effect like in the previous
example:

.. code-block:: yaml

	TYPO3:
	  TYPO3CR:
	    contentDimensions:
	      'language':
	        default: 'en'
	        defaultPreset: 'en'
	        label: 'Language'
	        icon: 'icon-language'
	        presets:
	          'en':
	            label: 'English'
	            values: ['en']
	            uriSegment: 'en'
	          'de':
	            label: 'German'
	            values: ['de']
	            uriSegment: 'de'
	            constraints:
	              country:
	                'de': true
	                '*': false
	          'fr':
	            label: 'French'
	            values: ['fr']
	            uriSegment: 'fr'
	            constraints:
	              country:
	                'fr': true
	                '*': false
	      'country':
	        default: 'us'
	        defaultPreset: 'us'
	        label: 'Country'
	        icon: 'icon-globe'
	        presets:
	          'us':
	            label: 'United States'
	            values: ['us']
	            uriSegment: 'us'
	          'de':
	            label: 'Germany'
	            values: ['de']
	            uriSegment: 'de'
	          'fr':
	            label: 'France'
	            values: ['fr']
	            uriSegment: 'fr'

While the examples only defined constraints in the ``language`` dimension configuration, it is perfectly possible to
additionally or exclusively define constraints in ``country`` or other dimensions.

Migration of existing content
=============================

Adjusting content dimensions configuration can lead to issues for existing content. When a new content dimension is added,
a corresponding value needs to be added to existing content, otherwise no nodes would be found.

This can be done with a node migration which is included in the ``TYPO3.TYPO3CR`` package::

	./flow node:migrate 20150716212459

This migration adds missing content dimensions by setting the default value on all existing nodes, if not already set.

Alternatively a custom node migration can be created allowing flexibility and constraints. See :ref:`node-migrations`.

Routing
=======

Neos provides a route-part handler that will include a prefix with the value of the ``uriSegment`` setting of a
dimension preset for all configured dimensions. This means URIs will not contain any prefix by default as long as
no content dimension is configured. Multiple dimensions are joined with a ``_`` character, so the ``uriSegment`` value
must not include an underscore.

Limitations
===========

In Neos 1.2 node variants can only be created by having a common fallback value in the presets. This means a node
can only be translated to some other dimension value if it "shined" through from a fallback value.

In Neos 2.0, it is possible to create node variants across dimension borders, i.e. to translate an English version
of a Document to German, without having fall-backs from German to English or vice versa.
