======================
Customize Login Screen
======================

You can customize the login screen by editing your ``Settings.yaml``::

  Neos:
    Neos:
      userInterface:
        backendLoginForm:
          backgroundImage: 'resource://Your.Package/Public/Images/LoginScreen.jpg'

Or alternatively add a custom stylesheet::

  Neos:
    Neos:
      userInterface:
        backendLoginForm:
          stylesheets:
            'Your.Package:CustomStyles': 'resource://Your.Package/Public/Styles/Login.css'

.. note::

    In this case ``Your.Package:CustomStyles`` is a simple key, used only internally.


How to disable a stylesheet ?
=============================

You can disable existing stylesheets, by setting the value to ``FALSE``, the following snippet will disable
the stylesheet provided by Neos, so your are free to implement your own::

  Neos:
    Neos:
      userInterface:
        backendLoginForm:
          stylesheets:
            'Neos.Neos:DefaultStyles': FALSE
            'Your.Package:CustomStyles': 'resource://Your.Package/Public/Styles/Login.css'

