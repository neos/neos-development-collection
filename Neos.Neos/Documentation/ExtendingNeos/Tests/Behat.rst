====================
Behat tests for Neos
====================

Setting up Neos for running Behat tests
=======================================

The Neos package contains a growing suite of Behat tests which you should take into account while fixing bugs or
adding new features. Please note that running these tests require that the Neos demo site package (Neos.Demo)
is installed and activated.

Install Behat for the base distribution
---------------------------------------

Behat is installed in a separate folder and has a custom composer root file. To install Behat run the following composer
command in `FLOW_ROOT/Build/Behat`::

	cd Build/Behat
	composer install

A special package `Neos.Behat` is used to integrate Flow with Behat and is installed if the base distribution was
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

The Behat tests for Neos are shipped inside the Neos.Neos package in the folder `Tests/Behavior`. Behat uses a
configuration file distributed with Neos, `behat.yml.dist`, or a local version, `behat.yml`. To run the tests, Behat
needs a base URI pointing to the special virtual host running with the `Development/Behat` context. To set a custom
base URI the default file should be copied and customized::

	cd Packages/Application/Neos.Neos/Tests/Behavior
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

If using Saucelabs, you do not need your own Selenium setup.

Running Behat tests
-------------------

Behat tests can be run from the Flow root folder with the `bin/behat` command by specifying the Behat configuration
file::

	bin/behat -c Packages/Application/Neos.Neos/Tests/Behavior/behat.yml

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

Run Behat tests on several browsers using Saucelabs
===================================================

.. note::

	Make sure that your Behat version is uptodate. Otherwise the saucelabs connection won't work. The
	`behat/mink-extension` need to be at least version 1.3.

Saucelabs (http://saucelabs.com) provides a VM infrastructure you can use to run your selenium tests on.

Using this infrastructure you can run the @javascript tagged tests on several Browsers and OSs autmatically without
setting up your own selenium infrastructure.

To run Neos Behat tests with saucelabs you need to do the following steps.

Configure Behat
---------------

To talk to saucelabs you need to uncomment the following lines in the `behat.yml` and add your saucelabs username
and access_key::

	javascript_session: saucelabs
	  saucelabs:
	    username: <username>
	    access_key: <access_key>

.. tip::

	Saucelabs provides unlimited video time for TYPO3 core development. If you want to contribute to Neos by writing
	tests ask Christian Müller.

To make tests with more browsers than the default browser you need to tell saucelabs which browser, version and OS you
want to test on. You can add several browsers, each in its own profile. There are a lot of browsers configured already
in the `saucelabsBrowsers.yml` file. You can include that into your behat configuration::

	imports:
	  - saucelabsBrowsers.yml

Open a tunnel to saucelabs
--------------------------

If you want to run the tests on your local machine you need to open a tunnel to saucelabs. This can be easily done by
downloading Sauce Connect at https://docs.saucelabs.com/reference/sauce-connect/ and follow the instructions to setup
and start it.

Run Behat tests
---------------

A test with Internet Explorer 10 on Windows8 would look like this then::

	bin/behat -c Packages/Application/Neos.Neos/Tests/Behavior/behat.yml --profile windows8-ie-10

You might just want to run the tests that need javascript on different browsers (all other tests won't use a browser
anyways). Limit the tests to the @javascript tagged to do so::

	bin/behat -c Packages/Application/Neos.Neos/Tests/Behavior/behat.yml --tags javascript --profile windows8-ie-10

.. note::

	The possible configuration settings for browsers can be found at https://saucelabs.com/docs/platforms. Choose
	"WebDriver" and "php" and click on the platform/browser combination you are interested in.

