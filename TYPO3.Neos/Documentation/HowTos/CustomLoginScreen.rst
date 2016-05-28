======================
Customize Login Screen
======================

You can customize the login screen by editing your ``Settings.yaml``::

  TYPO3:
    Neos:
      userInterface:
        backendLoginForm:
          backgroundImage: 'resource://Your.Package/Public/Images/LoginScreen.jpg'

Or alternatively add a custom stylesheet::

  TYPO3:
    Neos:
      userInterface:
        backendLoginForm:
          stylesheets:
            'Your.Package:CustomStyles': 'resource://Your.Package/Public/Styles/Login.css'

.. note::

    In this case ``Your.Package:CustomStyles`` is a simple key, used only internally.


You can also change the logo displayed above login form. To change the logo you can adjust the ``PartialRootPathPattern``
inside a ``Views.yaml`` to include your custom logo partial::

  # Login - Screen
  -
    requestFilter: 'isPackage("TYPO3.Neos") && isController("Login") && isAction("index")'
    options:
      partialRootPathPattern: resource://Neos.Demo/Private/Partials


With this example Neos would assume a logo in ``Packages/Sites/Neos.Demo/Resources/Private/Partials/Login/Logo.hml``

How to disable a stylesheet ?
=============================

You can disable existing stylesheets, by setting the value to ``FALSE``, the following snippet will disable
the stylesheet provided by Neos, so your are free to implement your own::

  TYPO3:
    Neos:
      userInterface:
        backendLoginForm:
          stylesheets:
            'TYPO3.Neos:DefaultStyles': FALSE
            'Your.Package:CustomStyles': 'resource://Your.Package/Public/Styles/Login.css'

