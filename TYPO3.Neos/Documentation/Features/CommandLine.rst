=================
Commandline tools
=================

Managing users
==============

Create a user
-------------

To create a user you can use the `flow user:create` command. By default a user will get the role `Editor` assigned.

::

	./flow user:create --username 'your@email.address' --password 'secret' --first-name 'Your' --last-name 'Name' --roles 'Administrator'

Add a role to a user
--------------------

::

	./flow user:addrole --username 'your@email.address' --roles 'Administrator'

Remove a role from a user
-------------------------

::

	./flow user:removerole --username 'your@email.address' --roles 'Administrator'

Managing domains
================

List available domain records
-----------------------------

::

	./flow domain:list

Add a domain record
-------------------

::

	./flow domain:add --site-node-name 'yourrootnode' --host-pattern 'your.host'

Delete a domain record
----------------------

::

	./flow domain:delete --host-pattern 'your.host'

Managing sites
==============

List available sites
--------------------

::

	./flow site:list

Import a site
-------------

::

	./flow site:import --packageKey My.Site

Alternatively you can specify the exact filename:

::

	./flow site:import --filename 'Packages/Sites/My.Site/Resources/Private/Content/Sites.xml'

Export a site
-------------

::

	./flow site:export > Packages/Sites/My.Site/Resources/Private/Content/Sites.xml

Remove ALL site related content
-------------------------------

::

	./flow site:prune --confirmation TRUE
