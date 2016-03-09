================================
Custom Login Screen Style Sheets
================================

You can customize the login stylesheet by editing your ``Settings.yaml``::

    TYPO3:
      Neos:
        backendLoginForm:
          stylesheets:
            'Your.Package:CustomStyles': 'resource://Your.Package/Public/Styles/Login.css'

.. note::

    In this case ``Your.Package:CustomStyles`` is a simple key, used only internally.

A basic CSS should contain something like this::

    html {
        margin:0;
        padding:0;
        background: url(//c1.staticflickr.com/1/731/21893992221_46a57b6043_h.jpg) no-repeat center fixed;
        -webkit-background-size: cover;
        background-size: cover;
    }
    .neos #neos-login-footer {
        padding: 1em;
        background-color: white;
        opacity: .7;
    }
    .neos #neos-login-box {
        background: rgba(20,20,20,.6);
        padding: 40px;
    }
    .neos form {
        margin-bottom: 0;
    }
    .neos input[type="text"], .neos input[type="password"] {
        background-color: white;
        border-color: white;
        opacity: .8;
    }
    .neos input:hover[type="text"], .neos input:hover[type="password"],
    .neos input:focus[type="text"], .neos input:focus[type="password"] {
        opacity: 1;
    }
    .neos #neos-login-box .neos-actions button.neos-login-btn {
        background-color: black;
    }

How to disable a stylesheet ?
=============================

You can disable existing stylesheets, by setting the value to ``FALSE``, the following snippet will disable
the stylesheet provided by Neos, so your are free to implement your own::

    TYPO3:
      Neos:
        backendLoginForm:
          stylesheets:
            'TYPO3.Neos:DefaultStyles': FALSE
            'Your.Package:CustomStyles': 'resource://Your.Package/Public/Styles/Login.css'

