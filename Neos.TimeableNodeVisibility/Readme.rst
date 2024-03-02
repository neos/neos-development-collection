------------------------------------
The Timeable Node Visibility package
------------------------------------

.. note:: This repository is a **read-only subsplit** of a package that is part of the
          Neos project (learn more on `www.neos.io <https://www.neos.io/>`_).

If you want to use Neos, please have a look at the `Neos documentation <https://docs.neos.io>`_



Documentation
-------------

The Timeable Node Visibility package allows you to activate or deactivate nodes at a specific time.

Adding the ``Neos.TimeableNodeVisibility:Timeable`` nodetype mixin as superType to your nodetype definition will enable
the feature for your nodetype. The mixin will add the two properties ``enableAfterDateTime`` and ``disableAfterDateTime``
to configure, when your nodetype has to be enabled or disabled.

NodeType Mixin:
 - ``Neos.TimeableNodeVisibility:Timeable``

Properties:
 - ``enableAfterDateTime`` (DateTime)
 - ``disableAfterDateTime``  (DateTime)

Per default the mixin is already applied to following nodetypes:
 - ``Neos.Neos:Document``
 - ``Neos.Neos:Content``

An asynchronous job in background is (see: `Run the background job`_) checking if a node need to be enabled or disabled
an will execute that as a command in the ContentRepository.

Install Package
===============

You can install the package with composer.

.. code-block:: bash

	composer require neos/timeable-node-visibility

Run the background job
======================

There are two ways of running the background job. Each way uses a dedicated command, provided by the package.

1. **Run in a cronjob periodically** each minute / each five minutes. The command stops after each run and need to get
re-executed in your favorite time frame.

.. code-block::

  COMMAND:
    neos.timeablenodevisibility:timeablenodevisibility:execute

  USAGE:
    ./flow timeablenodevisibility:execute [<options>]

  OPTIONS:
    --content-repository contentRepository
    --quiet              quiet


2. **Run as daemon** for a longer period. The command keeps running for the given time (--ttl) and checks every interval
(--interval) for new nodes to enable or disable.

.. code-block::

  COMMAND:
    neos.timeablenodevisibility:timeablenodevisibility:rundaemon

  USAGE:
    ./flow timeablenodevisibility:rundaemon [<options>]

  OPTIONS:
    --content-repository The content repository identifier. (Default: 'default')
    --ttl                The time to live for the daemon in seconds. Set to '0'
                         for infinite. (Default: '900')
    --interval           Interval in seconds, when the command has to get
                         executed. (Default: '60')
    --quiet              Set to false if you need a more verbose output.
                         (Default: 'true')



Contribute
----------

If you want to contribute to Neos, please have a look at
https://github.com/neos/neos-development-collection - it is the repository
used for development and all pull requests should go into it.