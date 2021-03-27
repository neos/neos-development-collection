|Travis Build Status| |Code Climate| |StyleCI| |Latest Stable Version| |License| |Docs| |API| |Slack| |Forum| |Issues| |Translate| |Twitter| |Pull Reminders|

.. |Travis Build Status| image:: https://travis-ci.org/neos/neos-development-collection.svg?branch=master
   :target: https://travis-ci.org/neos/neos-development-collection
.. |Code Climate| image:: https://codeclimate.com/github/neos/neos-development-collection/badges/gpa.svg
   :target: https://codeclimate.com/github/neos/neos-development-collection
.. |StyleCI| image:: https://styleci.io/repos/40964014/shield?style=flat
   :target: https://styleci.io/repos/40964014
.. |Latest Stable Version| image:: https://poser.pugx.org/neos/neos-development-collection/v/stable
   :target: https://packagist.org/packages/neos/neos-development-collection
.. |License| image:: https://poser.pugx.org/neos/neos-development-collection/license
   :target: https://raw.githubusercontent.com/neos/neos-development-collection/4.3/LICENSE
.. |Docs| image:: https://img.shields.io/badge/documentation-master-blue.svg
   :target: https://neos.readthedocs.org/en/4.3/
   :alt: Documentation
.. |API| image:: https://img.shields.io/badge/API%20docs-master-blue.svg
   :target: http://neos.github.io/neos/4.3/
   :alt: API Docs
.. |Slack| image:: http://slack.neos.io/badge.svg
   :target: http://slack.neos.io
   :alt: Slack
.. |Forum| image:: https://img.shields.io/badge/forum-Discourse-39c6ff.svg
   :target: https://discuss.neos.io/
   :alt: Discussion Forum
.. |Issues| image:: https://img.shields.io/github/issues/neos/neos-development-collection.svg
   :target: https://github.com/neos/neos-development-collection/issues
   :alt: Issues
.. |Translate| image:: https://img.shields.io/badge/translate-Crowdin-85ae52.svg
   :target: http://translate.neos.io/
   :alt: Translation
.. |Twitter| image:: https://img.shields.io/twitter/follow/neoscms.svg?style=social
   :target: https://twitter.com/NeosCMS
   :alt: Twitter
.. |Pull Reminders| image:: https://pullreminders.com/badge.svg
   :target: https://pullreminders.com?ref=badge
   :alt: Pull Reminders

---------------------------
Neos development collection
---------------------------

This repository is a collection of packages for the Neos content application platform (learn more on https://www.neos.io/).
The repository is used for development and all pull requests should go into it.

If you want to install Neos, please have a look at the documentation: https://neos.readthedocs.org/en/latest/

Contributing
============

If you want to contribute to Neos and want to set up a development environment, then follow these steps:

``composer create-project neos/neos-development-distribution neos-development dev-master --keep-vcs``

Note the **-distribution** package you create a project from, instead of just checking out this repository.

The code of the CMS can then be found inside ``Packages/Neos``, which itself is the neos-development-collection Git repository (due to the ``--keep-vcs`` option above). You commit changes and create pull requests from this repository.
To commit changes to Neos switch into the ``Neos`` directory (``cd Packages/Neos``) and do all Git-related work (``git add .``, ``git commit``, etc) there.
If you want to contribute to the Neos UI, please take a look at the explanations at https://github.com/neos/neos-ui#contributing on how to work with that.
If you want to contribute to the Flow Framework, you find that inside the ``Packages/Framework`` folder. See https://github.com/neos/flow-development-collection

In the root directory of the development distribution, you can do the following things:

To run tests, run ``./bin/phpunit -c ./Build/BuildEssentials/PhpUnit/UnitTests.xml`` for unit or ``./bin/phpunit -c ./Build/BuildEssentials/PhpUnit/FunctionalTests.xml`` for functional/integration tests.

To switch the branch you intend to work on:
``git checkout 7.1 && composer update``

.. note:: We use an upmerging strategy, so create all bugfixes to lowest maintained branch that contains the issue (typically the second last LTS release, which is 4.3 currently), or master for new features.

For more detailed information, see https://discuss.neos.io/t/development-setup/504 and https://discuss.neos.io/t/creating-a-pull-request/506
