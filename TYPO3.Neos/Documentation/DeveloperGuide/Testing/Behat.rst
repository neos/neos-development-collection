==========================
Behat tests for TYPO3 Neos
==========================

Setting up Neos for running Behat tests
=======================================

The TYPO3 Neos package contains a growing suite of Behat tests which you should take into account while fixing bugs or
adding new features. Please note that running these tests require that the Neos demo site package (TYPO3.NeosDemoTypo3Org)
is installed and activated.

Install Behat for the base distribution
---------------------------------------

Behat is installed in a separate folder and has a custom composer root file. To install Behat run the following composer
command in `FLOW_ROOT/Build/Behat`::

	cd Build/Behat
	composer install

A special package `Flowpack.Behat` is used to integrate Flow with Behat and is installed if the base distribution was
installed with `composer install --dev`.

Create configuration for subcontexts
------------------------------------

Behat needs two special Flow contexts, `Development/Behat` and `Testing/Behat`.

* The context `Development/Behat` should be mounted as a separate virtual host and is used by Behat to do the actual
  HTTP requests.
* The context `Testing/Behat` is used inside the Behat feature context to set up test data and reset the database after
  each scenario.

These contexts should share the same database to work properly. Make sure to create a new database for the Behat tests
since all the data will be removed after each scenario.

`FLOW_ROOT/Configuration/Development/Behat/Settings.yaml`::

	TYPO3:
	  Flow:
	    persistence:
	      backendOptions:
	        dbname: 'neos_testing_behat'

`FLOW_ROOT/Configuration/Testing/Behat/Settings.yaml`::

	TYPO3:
	  Flow:
	    persistence:
	      backendOptions:
	        dbname: 'neos_testing_behat'
	        driver: pdo_mysql
	        user: ''
	        password: ''

Example virtual host configuration for Apache::

	<VirtualHost *:80>
		DocumentRoot "FLOW_ROOT/Web"
		ServerName neos.behat.test
		SetEnv FLOW_CONTEXT Development/Behat
	</VirtualHost>

Configure Behat
---------------

The Behat tests for Neos are shipped inside the TYPO3.Neos package in the folder `Tests/Behavior`. Behat uses a
configuration file distributed with Neos, `behat.yml.dist`, or a local version, `behat.yml`. To run the tests, Behat
needs a base URI pointing to the special virtual host running with the `Development/Behat` context. To set a custom
base URI the default file should be copied and customized::

	cd Packages/Application/TYPO3.Neos/Tests/Behavior
	cp behat.yml.dist behat.yml
	# Edit file behat.yml

Customized `behat.yml`::

	default:
	  paths:
	    features: Features
	    bootstrap: %behat.paths.features%/Bootstrap
	  extensions:
	    Behat\MinkExtension\Extension:
	      files_path: features/Resources
	      show_cmd: 'open %s'
	      goutte: ~
	      selenium2: ~

	      base_url: http://neos.behat.test/

Selenium
--------

Some tests require a running Selenium server for testing browser advanced interaction and JavaScript.
Selenium Server can be downloaded at http://docs.seleniumhq.org/download/ and started with::

	java -jar selenium-server-standalone-2.x.0.jar

Running Behat tests
-------------------

Behat tests can be run from the Flow root folder with the `bin/behat` command by specifying the Behat configuration
file::

	bin/behat -c Packages/Application/TYPO3.Neos/Tests/Behavior/behat.yml

In case the executable file `bin/behat` is missing, create a symlink by running the following command in `FLOW_ROOT/bin`::

	ln -s ../Build/Behat/vendor/behat/behat/bin/behat

.. tip::

	You might want to warmup the cache before you start the test. Otherwise the tests might fail due to a timeout.
	You can do that with `FLOW_CONTEXT=Development/Behat ./flow flow:cache:warmup`.

Debugging
---------

* Make sure to use a new database and configure the same databse for `Development/Behat` and `Testing/Behat`
* Run Behat with the `-v` option to get more information about errors and failed tests
* A failed step can be inspected by inserting "Then show last response" in the `.feature` definition
