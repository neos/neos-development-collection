===================
Legacy Ember UI - Development
===================

.. warning::
  This applies for the legacy Ember Neos UI. Learn more about our `current ReactJS UI in the docs <https://docs.neos.io/cms/contributing-to-neos/neos-ui>`_.

Setting up your machine for Neos UI development
===============================================

For user interface development of Neos we utilize `grunt` and some other
tools.

Setting up your machine could be done by using the installation script that can
be found in ``Neos.Neos/Scripts/install-dev-tools.sh``. If you want to do a manual
installation you will need to install the following software:

* nodejs
* npm
* grunt-cli (global, ``sudo npm install -g grunt-cli``)
* requirejs (``sudo npm install -g requirejs``)
* bower (``sudo npm install -g bower``)
* bundler (``sudo gem install bundler``)
* sass & compass (``sudo gem install sass compass``)

.. note::

	Make sure you call ``npm install``, ``bundle install --binstubs --path bundle``
	and ``bower install`` before running the grunt tasks.

Grunt tasks types
=================

We have different types of grunt tasks. All tasks have different purposes:

* build commands

	Those commands are used to package a production version of the code. Like
	for example minified javascript, minified css or rendered documentation.

* compile commands

	Those commands are meant for compiling resources that are used in development
	context. This could for example be a packed file containing jquery and related
	plugins which are loaded in development context using requirejs.

* watch commands

	Those commands are used for watching file changes. When a change is detected
	the compile commands for development are executed. Use those commands during
	your daily work for a fast development experience.

* test commmands

	Used for running automated tests. Those tests use phantomjs which is automatically
	installed by calling ``npm install``. Phantomjs needs some other dependencies though,
	check ``Neos.Neos/Scripts/install-phantomjs-dependencies.sh`` for ubuntu based systems.

Available grunt tasks
=====================

Build
-----

* ``grunt build``

	Executes ``grunt build-js`` and ``grunt build-css``.

* ``grunt build-js``

	Builds the minified and concatenated javascript sources to ``ContentModule-built.js``
	using requirejs.

* ``grunt build-css``

	Compiles and concatenates the css sources to ``Includes-built.css``.

* ``grunt build-docs``

	Renders the documentation. This task depends on a local installation of Omnigraffle.

Compile
-------

* ``grunt compile``

	Executes ``grunt compile-js`` and ``grunt compile-css``

* ``grunt compile-js``

	Compiles the javascript sources. This is the task to use if you want to package the
	jquery sources including plugins or if you want to recreated the wrapped libraries
	we include in Neos. During this process some of the included libraries are altered
	to prevent collisions with Neos or the website frontend.

* ``grunt compile-css``

	Compiles and concatenates the scss sources to css.

Watch
-----

* ``watch-css``

	Watches changes to the scss files and runs ``compile-css`` if a change is detected.

* ``watch-docs``

	Watches changes to the rst files of the documentation, and executes a compilation of
	all restructured text sources to html. This task depends on a local sphinx install but
	does not require Omnigraffle.

* ``watch``

	All of the above.

Test
----

* ``grunt test``

	Runs QUnit tests for javascript modules.
