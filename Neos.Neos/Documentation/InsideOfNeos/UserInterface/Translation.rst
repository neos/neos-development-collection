==============================
Translating the user interface
==============================

Default Language
================
The ``availableLanguages`` are defined in ``Packages/Application/Neos.Neos/Configuration/Settings.yaml``.

You may override the default language of your installation in ``Configuration/Settings.yaml``:

.. code-block:: yaml

  TYPO3:
    Neos:
      userInterface:
        defaultLanguage: 'en'


Label Scrambling
================

To help you find labels in the Neos editor interface that you still need to translate, you can use the
language label scrambling setting in your yaml file. This will replace all translations
by a string consisting of only `#` characters with the same length as the actual
translated label. With this setting enabled every still readable string in the backend
is either content or non-translated.

.. code-block:: yaml

 TYPO3:
   Neos:
     userInterface:
       scrambleTranslatedLabels: TRUE

.. note::

  The translation labels used in the javascript ui are parsed to a big json file.
  While changing xliff files this cached should be flushed, but still it can turn
  out useful to disable this cache. You can do so by using the following snippet
  in your `Caches.yaml`

.. code-block:: yaml

 TYPO3_Neos_XliffToJsonTranslations:
   backend: TYPO3\Flow\Cache\Backend\NullBackend

