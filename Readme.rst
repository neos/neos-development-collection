|Code Climate| |StyleCI| |Latest Stable Version| |License| |Docs| |Slack| |Forum| |Issues| |Translate| |Twitter|

.. |Code Climate| image:: https://codeclimate.com/github/neos/neos-development-collection/badges/gpa.svg
   :target: https://codeclimate.com/github/neos/neos-development-collection
.. |StyleCI| image:: https://styleci.io/repos/40964014/shield?style=flat
   :target: https://styleci.io/repos/40964014
.. |Latest Stable Version| image:: https://poser.pugx.org/neos/neos-development-collection/v/stable
   :target: https://packagist.org/packages/neos/neos-development-collection
.. |License| image:: https://poser.pugx.org/neos/neos-development-collection/license
   :target: https://raw.githubusercontent.com/neos/neos-development-collection/4.3/LICENSE
.. |Docs| image:: https://img.shields.io/badge/documentation-master-blue.svg
   :target: https://neos.readthedocs.org/en/8.0/
   :alt: Documentation
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

---------------------------
Neos development collection
---------------------------

**FOR DOCS ON THE EVENT SOURCED CONTENT REPOSITORY, READ ON BELOW**

This repository is a collection of packages for the Neos content application platform (learn more on https://www.neos.io/).
The repository is used for development and all pull requests should go into it.

If you want to install Neos, please have a look at the documentation: https://neos.readthedocs.org/en/latest/

Contributing
============

If you want to contribute to Neos and want to set up a development environment, then follow these steps:

``composer create-project neos/neos-development-distribution neos-development dev-9.0 --keep-vcs --prefer-install=auto``

Note the **-distribution** package you create a project from, instead of just checking out this repository.

The code of the CMS can then be found inside ``Packages/Neos``, which itself is the neos-development-collection Git repository (due to the ``--prefer-install`` option above). You commit changes and create pull requests from this repository.
To commit changes to Neos switch into the ``Neos`` directory (``cd Packages/Neos``) and do all Git-related work (``git add .``, ``git commit``, etc) there.
If you want to contribute to the Neos UI, please take a look at the explanations at https://github.com/neos/neos-ui#contributing on how to work with that.
If you want to contribute to the Flow Framework, you find that inside the ``Packages/Framework`` folder. See https://github.com/neos/flow-development-collection

In the root directory of the development distribution, you can do the following things:

To run tests, run ``./bin/phpunit -c ./Build/BuildEssentials/PhpUnit/UnitTests.xml`` for unit or ``./bin/phpunit -c ./Build/BuildEssentials/PhpUnit/FunctionalTests.xml`` for functional/integration tests.

To switch the branch you intend to work on:
``git checkout 9.0 && composer update``

.. note:: We use an upmerging strategy, so create all bugfixes to lowest maintained branch that contains the issue (typically the second last LTS release, which is 5.3 currently), or master for new features.

For more detailed information, see https://discuss.neos.io/t/development-setup/504 and https://discuss.neos.io/t/creating-a-pull-request/506


----------------------------------------------
New (Event Sourced) Content Repository (ES CR)
----------------------------------------------

Prerequisites
=============

- You need PHP 8.1 installed.
- Please be sure to run off the Neos-Development-Distribution in Branch 9.0, to avoid dependency issues (as described above).

Setup
=====

The ES CR has a Docker-compose file included in `Neos.ContentRepository.BehavioralTests` which starts both
Mariadb and Postgres in compatible versions. Additionally, there exists a helper to change the configuration
in your distribution (`/Configuration`) to the correct values matching this database.

Do the following for setting up everything:

1. Start the databases:

   .. code-block:: bash

       pushd Packages/Neos/Neos.ContentRepository.BehavioralTests; docker compose up -d; popd

       # to stop the databases:
       pushd Packages/Neos/Neos.ContentRepository.BehavioralTests; docker compose down; popd
       # to stop the databases AND remove the stored data:
       pushd Packages/Neos/Neos.ContentRepository.BehavioralTests; docker compose down -v; popd

2. Copy matching Configuration:

   .. code-block:: bash

       cp -R Packages/Neos/Neos.ContentRepository.BehavioralTests/DistributionConfigurationTemplate/* Configuration/

3. Run Doctrine Migrations:

   .. code-block:: bash

       ./flow doctrine:migrate
       FLOW_CONTEXT=Testing/Postgres ./flow doctrine:migrate

4. Setup the Content Repository

   .. code-block:: bash

       ./flow cr:setup

5. Set up Behat

   .. code-block:: bash

       cp -R Packages/Neos/Neos.ContentRepository.BehavioralTests/DistributionBehatTemplate/* Build/Behat/
       pushd Build/Behat/
       rm composer.lock
       composer install
       popd


Starting Neos
=============

   .. code-block:: bash

       ./flow server:run


Running the Tests
=================

   .. code-block:: bash

       pushd Packages/Neos/Neos.ContentRepository.BehavioralTests/Tests/Behavior
       ../../../../../bin/behat -c behat.yml.dist Features/
       popd

