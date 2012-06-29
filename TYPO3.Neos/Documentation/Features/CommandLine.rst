=================
Commandline tools
=================

Managing users
==============

Create a user
-------------

To create a user you can use the `flow3 user:create` command. By default a user will get the role `Editor` assigned.

::

	./flow3 user:create --username 'your@email.address' --password 'secret' --first-name 'Your' --last-name 'Name' --roles 'Administrator'

Add a role to a user
--------------------

::

	./flow3 user:addrole --username 'your@email.address' --roles 'Administrator'

Remove a role from a user
-------------------------

::

	./flow3 user:removerole --username 'your@email.address' --roles 'Administrator'

Managing domains
================

List available domain records
-----------------------------

::

	./flow3 domain:list

Add a domain record
-------------------

::

	./flow3 domain:add --site-node-name 'yourrootnode' --host-pattern 'your.host'

Delete a domain record
----------------------

::

	./flow3 domain:delete --host-pattern 'your.host'

Managing sites
==============

List available sites
--------------------

::

	./flow3 site:list

Import a site
-------------

::

	./flow3 site:import --packageKey My.Site

Alternatively you can specify the exact filename:

::

	./flow3 site:import --filename 'Packages/Sites/My.Site/Resources/Private/Content/Sites.xml'

Export a site
-------------

::

	./flow3 site:export > Packages/Sites/My.Site/Resources/Private/Content/Sites.xml

Remove ALL site related content
-------------------------------

::

	./flow3 site:prune --confirmation TRUE
