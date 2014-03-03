For building the file jquery-with-dependecies in `TYPO3.Neos/Resources/Public/Library` it is necessary to install
node.js and grunt

Node.Js
=======

Visit the project homepage [http://nodejs.org/](http://nodejs.org/) and download node.js

Click on "install" and the right version for your OS will be downloaded and a installer will be started.
Use the default configuration

Grunt
=====

For using grunt we need a global installation of grunt-cli. All other modules can be installed locally. If you don't
have grunt-cli installed yet, install it using:

	npm install -g grunt-cli

To install grunt for running the Neos commands, you install the node modules into the Scripts directory:

	cd Packages/Application/TYPO3.Neos/Scripts
	npm install

Grunt Tasks
-----------

* grunt concat

	This command concatenates all JavaScript sources into the minified files we include into Neos.

* grunt docs

	Renders the documentation. (This command needs sphinx-builds and OmniGraffle 5 Professional installed)

