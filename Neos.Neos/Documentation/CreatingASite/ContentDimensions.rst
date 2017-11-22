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

   Neos:
     ContentRepository:
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
               resolutionValue: 'all'
             # Example for additional languages:

             'en_GB':
               label: 'English (Great Britain)'
               values: ['en_GB', 'en_ZZ', 'mul_ZZ']
               resolutionValue: 'gb'
             'de':
               label: 'German (Germany)'
               values: ['de_DE', 'de_ZZ', 'mul_ZZ']
               resolutionValue: 'de'

.. note::
   The ``uriSegment`` configuration option from previous versions has been deprecated but still works until the next major release.

.. note::
   The Neos ContentRepository and Neos packages don't provide any dimension configuration per default.

Preset resolution
=================

As of version 3.3, content dimension presets can be resolved in different ways additional to the "classic" way of using an URI path segment.
Thus further configuration and implementation options have been added.

Neos comes with three basic `resolution modes` which can be combined arbitrarily and configured individually.

URI path segment based resolution
---------------------------------

The default resolution mode is ``uriPathSegment``. As by default in previous versions, it operates on an additional path segment,
e.g. ``https://domain.tld/{language}_{market}/home.html``. These are the configuration options available:

.. code-block:: yaml

   Neos:
     ContentRepository:
       contentDimensions:
         'market':
           resolution:
             mode: 'uriPathSegment'
             options:
               # The offset defines the dimension's position in the path segment. Offset 1 means this is the second part.
               # This allows for market being the second uriPath part although it's the primary dimension.
               offset: 1
         'language':
           resolution:
             mode: 'uriPathSegment'
             options:
               # Offset 0 means this is the first part.
               offset: 0
     Neos:
       contentDimensions:
        resolution:
          # Delimiter to separate values if multiple dimension are present
          uriPathSegmentDelimiter: '-'

With the given configuration, URIs will be resolved like ``domain.tld/{language}-{market}/home.html``

.. note::
   An arbitrary number of dimensions can be resolved via uriPathSegment.
   The other way around, as long as no content dimensions resolved via uriPathSegment are defined, URIs will not contain any prefix.

The default preset can have an empty `resolutionValue` value. The following example will lead to URLs that do not contain
`en` if the `en_US` preset is active, but will show the `resolutionValue` for other languages that are defined as well:

.. code-block:: yaml

   Neos:
     ContentRepository:
       contentDimensions:

         'language':
           default: 'en'
           resolution:
             mode: 'uriPathSegment'
           defaultPreset: 'en_US'
           label: 'Language'
           icon: 'icon-language'
           presets:
             'en':
               label: 'English (US)'
               values: ['en_US']
               resolutionValue: ''

The only limitation is that all resolution values must be unique across all dimensions that are resolved via uriPathSegment.
If you need non-unique resolution values, you can switch support for non-empty dimensions off:

.. code-block:: yaml

   Neos:
     Neos:
       routing:
         supportEmptySegmentForDimensions: FALSE

Subdomain based resolution
--------------------------

Another resolution mode is ``subdomain``. This mode extracts information from the first part of the host and adds it respectively
when generating URIs.

.. code-block:: yaml

   Neos:
     ContentRepository:
       contentDimensions:
         'language':
           default: 'en'
           defaultPreset: 'en'
           resolution:
             mode: 'subdomain'
             options:
               # true means that if no preset can be detected, the default one will be used.
               # Also when rendering new links, no subdomain will be added for the default preset
               allowEmptyValue: true
           presets:
             'en_GB':
               label: 'English'
               values: ['en']
               resolutionValue: 'en'
             'de':
               label: 'German (Germany)'
               values: ['de_DE']
               resolutionValue: 'de'

With the given configuration, URIs will be resolved like ``{language}.domain.tld/home.html``

.. note::
   Only one dimension can be resolved via subdomain.

Top level domain based resolution
---------------------------------

The final resolution mode is ``topLevelDomain``. This modes extracts information from the last part of the host and adds it respectively
when generating URIs.

.. code-block:: yaml

   Neos:
     ContentRepository:
       contentDimensions:
         'market':
           default: 'eu'
           defaultPreset: 'eu'
           resolution:
             mode: 'topLevelDomain'
           presets:
             'EU':
               label: 'European Union'
               values: ['EU']
               resolutionValue: 'eu'
             'GB':
               label: 'Great Britain'
               values: ['GB']
               resolutionValue: 'co.uk'
             'DE':
               label: 'Germany'
               values: ['DE', 'EU']
               resolutionValue: 'de'

With the given configuration, URIs will be resolved like ``domain.{market}/home.html``

.. note::
   Only one dimension can be resolved via top level domain.

Custom resolution
-----------------

There are planned extension points in place to support custom implementations in case the basic ones do not suffice.

Defining custom resolution components
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Each resolution mode is defined by two components: An implementation of ``Neos\Neos\Http\ContentDimensionDetection\ContentDimensionPresetDetectorInterface``
to extract the preset from an HTTP request and an implementation of ``Neos\Neos\Http\ContentDimensionLinking\ContentDimensionPresetLinkProcessorInterface``
for post processing links matching the given dimension presets.

These can be implemented and configured individually per dimension:

.. code-block:: yaml

   Neos:
     ContentRepository:
       contentDimensions:
         weather:
           detectionComponent:
             implementationClassName: 'My\Package\Http\ContentDimensionDetection\WeatherDimensionPresetDetector'
           linkProcessorComponent:
             implementationClassName: 'My\Package\Http\ContentDimensionLinking\WeatherDimensionPresetLinkProcessor'

If your custom preset resolution components do not affect the URI, you can use the ``Neos\Neos\Http\ContentDimensionLinking\NullDimensionPresetLinkProcessor``
implementation as the link processor.

.. note::
   If you want to replace implementations of one of the basic resolution modes, you can do it this way, too.

Completely replacing resolution behaviour
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

The described configuration and extension points assume that all dimension presets can be resolved independently.
There may be more complex situations though, where the resolution of one dimension depends on the result of the resolution of another.
As an example, think of a subdomain (language) and top level domain (market) based scenario where you want to support ``domain.fr``,
``domain.de``, ``de.domain.ch``, ``fr.domain.ch`` and ``it.domain.ch``. Although you can define the subdomain as optional,
the default language depends on the market: ``domain.de`` should be resolved to default language ``de`` and ``domain.fr``
should be resolved to default language ``fr``.
Those complex scenarios are better served using individual implementations than complex configuration efforts.

To enable developers to deal with this in a nice way, there are predefined ways to deal with both detection and link processing.

Detection is done via an HTTP component that can be replaced via configuration:

.. code-block:: yaml

   Neos:
     Flow:
       http:
         chain:
           preprocess:
             chain:
               detectContentSubgraph:
                 component: Neos\Neos\Http\DetectContentSubgraphComponent

Link processing is done by the ``Neos\Neos\Http\ContentSubgraphUriProcessorInterface``. To introduce your custom behaviour,
implement the interface and declare it in ``Objects.yaml`` as usual in Flow.

.. note::
   Please refer to the default implementations for further hints and ideas on how to implement resolution.


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

   Neos:
     ContentRepository:
       contentDimensions:
         'language':
           default: 'en'
           resolution:
             mode: 'uriPathSegment'
             options:
               offset: 0
           defaultPreset: 'en'
           label: 'Language'
           icon: 'icon-language'
           presets:
             'en':
               label: 'English'
               values: ['en']
               resolutionValue: 'en'
             'de':
               label: 'German'
               values: ['de']
               resolutionValue: 'de'
               constraints:
                 country:
                   'us': false
                   'fr': false
             'fr':
               label: 'French'
               values: ['fr']
               resolutionValue: 'fr'
               constraints:
                 country:
                   'us': false
                   'de': false
         'country':
           default: 'us'
           resolution:
             mode: 'uriPathSegment'
             options:
               offset: 1
           defaultPreset: 'us'
           label: 'Country'
           icon: 'icon-globe'
           presets:
             'us':
               label: 'United States'
               values: ['us']
               resolutionValue: 'us'
             'de':
               label: 'Germany'
               values: ['de']
               resolutionValue: 'de'
             'fr':
               label: 'France'
               values: ['fr']
               resolutionValue: 'fr'

Instead of configuring every constraint preset explicitly, it is also possible to allow or disallow all presets of a
given dimension by using the wildcard identifier. The following configuration has the same effect like in the previous
example:

.. code-block:: yaml

   Neos:
     ContentRepository:
       contentDimensions:
         'language':
           default: 'en'
           resolution:
             mode: 'uriPathSegment'
             options:
               offset: 0
           defaultPreset: 'en'
           label: 'Language'
           icon: 'icon-language'
           presets:
             'en':
               label: 'English'
               values: ['en']
               resolutionValue: 'en'
             'de':
               label: 'German'
               values: ['de']
               resolutionValue: 'de'
               constraints:
                 country:
                   'de': true
                   '*': false
             'fr':
               label: 'French'
               values: ['fr']
               resolutionValue: 'fr'
               constraints:
                 country:
                   'fr': true
                   '*': false
         'country':
           default: 'us'
           resolution:
             mode: 'uriPathSegment'
             options:
               offset: 1
           defaultPreset: 'us'
           label: 'Country'
           icon: 'icon-globe'
           presets:
             'us':
               label: 'United States'
               values: ['us']
               resolutionValue: 'us'
             'de':
               label: 'Germany'
               values: ['de']
               resolutionValue: 'de'
             'fr':
               label: 'France'
               values: ['fr']
               resolutionValue: 'fr'

While the examples only defined constraints in the ``language`` dimension configuration, it is perfectly possible to
additionally or exclusively define constraints in ``country`` or other dimensions.

Migration of existing content
=============================

Adjusting content dimensions configuration can lead to issues for existing content. When a new content dimension is added,
a corresponding value needs to be added to existing content, otherwise no nodes would be found.

This can be done with a node migration which is included in the ``Neos.ContentRepository`` package::

	./flow node:migrate 20150716212459

This migration adds missing content dimensions by setting the default value on all existing nodes, if not already set.

Alternatively a custom node migration can be created allowing flexibility and constraints. See :ref:`node-migrations`.

