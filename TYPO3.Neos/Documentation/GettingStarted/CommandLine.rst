=================
Commandline tools
=================

This section gives you an overview of the available commmand line tools of Neos.

Managing users
==============

Create a user
-------------

To create a user you can use the `flow user:create` command. By default a user will get the role `Editor` assigned.

.. code-block:: bash

	./flow user:create --username 'your@email.address' --password 'secret' --first-name 'Your' --last-name 'Name' --roles 'Administrator'

Add a role to a user
--------------------

.. code-block:: bash

	./flow user:addrole --username 'your@email.address' --roles 'Administrator'

Remove a role from a user
-------------------------

.. code-block:: bash

	./flow user:removerole --username 'your@email.address' --roles 'Administrator'

Managing domains
================

List available domain records
-----------------------------

.. code-block:: bash

	./flow domain:list

Add a domain record
-------------------

.. code-block:: bash

	./flow domain:add --site-node-name 'yourrootnode' --host-pattern 'your.host'

Delete a domain record
----------------------

.. code-block:: bash

	./flow domain:delete --host-pattern 'your.host'

Managing sites
==============

List available sites
--------------------

.. code-block:: bash

	./flow site:list

Import a site
-------------

.. code-block:: bash

	./flow site:import --packageKey My.Site

Alternatively you can specify the exact filename:

.. code-block:: bash

	./flow site:import --filename 'Packages/Sites/My.Site/Resources/Private/Content/Sites.xml'

Export a site
-------------

.. code-block:: bash

	./flow site:export > Packages/Sites/My.Site/Resources/Private/Content/Sites.xml

A single site (as opposed to all sites) can be exported with:

.. code-block:: bash

	./flow site:export --site-name MySite > Packages/Sites/My.Site/Resources/Private/Content/MySite.xml

Remove ALL site related content
-------------------------------

.. code-block:: bash

	./flow site:prune --confirmation TRUE
