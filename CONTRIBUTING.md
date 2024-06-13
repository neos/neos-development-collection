# Contributing

**For (specific) Notes on Neos 9, see further down…**

If you want to contribute to Neos and want to set up a development environment, then follow these steps:

```shell
composer create-project neos/neos-development-distribution neos-development 8.3.x-dev --keep-vcs
```

Note the **-distribution** repository you create a project from, instead of just checking out this repository.

If you need a different branch, you can either use it from the start (replace the ``8.3.x-dev`` by ``9.0.x-dev`` or whatever you need), or switch after checkout (just make sure to run composer update afterwards to get matching dependencies installed.) In a nutshell, to switch the branch you intend to work on, run:

```shell
git checkout 9.0 && composer update
```

The code of the CMS can then be found inside `Packages/Neos`, which itself is the neos-development-collection Git repository. You commit changes and create pull requests from this repository.

- To commit changes to Neos switch into the `Neos` directory (`cd Packages/Neos`) and do all Git-related work (`git add .`, `git commit`, etc) there.
- If you want to contribute to the Neos UI, please take a look at the explanations at https://github.com/neos/neos-ui#contributing on how to work with that.
- If you want to contribute to the Flow Framework, you find that inside the `Packages/Framework` folder. See https://github.com/neos/flow-development-collection

In the root directory of the development distribution, you can do the following things:

To run tests, run `./bin/phpunit -c ./Build/BuildEssentials/PhpUnit/UnitTests.xml` for unit tests or `./bin/phpunit -c ./Build/BuildEssentials/PhpUnit/FunctionalTests.xml` for functional/integration tests.

---

**Note**

We use an upmerging strategy: create all bugfixes to the lowest maintained branch that contains the issue. Typically, this is the second last LTS release - see the diagram at https://www.neos.io/features/release-process.html.

  For new features, pull requests should be made against the branch for the next minor version (named like `x.y`). Breaking changes must only go into the branch for the next major version.

---

For more detailed information, see https://discuss.neos.io/t/development-setup/504,
https://discuss.neos.io/t/creating-a-pull-request/506 and
https://discuss.neos.io/t/git-branch-handling-in-the-neos-project/6013


## Neos 9


### Prerequisites

- You need PHP 8.2 installed.
- Please be sure to run off the Neos development distribution in branch 9.0, to avoid dependency issues.

### Setup

The Event-Sourced Content Repository has a Docker Compose file included in `Neos.ContentRepository.BehavioralTests` which starts both Mariadb and Postgres in compatible versions. Additionally, there exists a helper to change the configuration in your distribution (`/Configuration`) to the correct values matching this database.

Do the following for setting up everything:

1. Start the databases:

   ```shell
   pushd Packages/Neos/Neos.ContentRepository.BehavioralTests; docker compose up -d; popd

   # to stop the databases:
   pushd Packages/Neos/Neos.ContentRepository.BehavioralTests; docker compose down; popd
   # to stop the databases AND remove the stored data:
   pushd Packages/Neos/Neos.ContentRepository.BehavioralTests; docker compose down -v; popd
   ```

2. Copy matching Configuration:

   ```shell
   cp -R Packages/Neos/Neos.ContentRepository.BehavioralTests/DistributionConfigurationTemplate/* Configuration/
   ```

3. Run Doctrine Migrations:

   ```shell
   ./flow doctrine:migrate
   FLOW_CONTEXT=Testing/Postgres ./flow doctrine:migrate
   ```

4. Setup the Content Repository

   ```shell
   ./flow cr:setup
   ```

5. Set up Behat

   ```shell
   cp -R Packages/Neos/Neos.ContentRepository.BehavioralTests/DistributionBehatTemplate/ Build/Behat
   pushd Build/Behat/
   rm composer.lock
   composer install
   popd
   ```

### Running the Tests

The normal mode is running PHP locally, but running Mariadb / Postgres in containers (so we know
we use the right versions etc).

```shell
 cd Packages/Neos
 composer test:behavioral
```

Alternatively, if you want to reproduce errors as they happen inside the CI system, but you
develop on Mac OS, you might want to run the Behat tests in a Docker container (= a linux environment)
as well. We have seen cases where they behave differently, i.e. where they run without race
conditions on OSX, but with race conditions in Linux/Docker. Additionally, the Linux/Docker setup
described below also makes it easy to run the race-condition-detector:

```shell
docker compose --project-directory . --file Packages/Neos/Neos.ContentRepository.BehavioralTests/docker-compose-full.yml build
docker compose --project-directory . --file Packages/Neos/Neos.ContentRepository.BehavioralTests/docker-compose-full.yml up -d
docker compose --project-directory . --file Packages/Neos/Neos.ContentRepository.BehavioralTests/docker-compose-full.yml run neos /bin/bash

# the following commands now run INSIDE the Neos docker container:
FLOW_CONTEXT=Testing/Behat ../../../../../flow raceConditionTracker:reset

../../../../../bin/behat -c behat.yml.dist

FLOW_CONTEXT=Testing/Behat ../../../../../flow raceConditionTracker:analyzeTrace
```
