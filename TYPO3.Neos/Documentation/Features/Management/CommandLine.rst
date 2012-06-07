=================
Commandline tools
=================

Managing users
==============

Create a user
-------------

To create a user you can use the `flow3 user:create` command. By default a user will get the role `Editor` assigned.

::

	./flow3 user:create --username 'your@email.address' --password 'secret' --first-name 'Your' --last-name 'Name' --roles='Administrator'

Add a role to a user
--------------------

::

	./flow3 user:addrole --username 'your@email.address' --role='Administrator'

Remove a role from a user
-------------------------

::

	./flow3 user:removerole --username 'your@email.address' --role='Administrator'