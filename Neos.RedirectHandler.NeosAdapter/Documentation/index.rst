=========================================
Automatically generated redirects in Neos
=========================================

Whenever you change the ``URL path segment`` or move a document node, a redirect will automatically be generated as soon as it is published into the live workspace.

..note:: To get an overview over all currently active redirects you can always run ``./flow redirection:list``. For further details see ``Neos Command Reference``.

For the next release, there will also be a backend module to show and manage redirects in the Neos backend.


Possible configuration for redirects
------------------------------------

You can configure the default behaviour for automatically generated redirects within ``Settings.yaml``.

.. code-block:: yaml

Neos:
 RedirectHandler:
  features:
    hitCounter: true
  statusCode:
    'redirect': 307
    'gone': 410



Options
^^^^^^^

- ``hitCounter``: turn on/off the hit counter for redirects.
- ``statusCode``: define the default status code for redirect or gone status (node deleted).


It is also possible to add, change or remove redirects within the CLI.
The available CLI commands for custom redirect management can be found in ``Neos Command Reference``.