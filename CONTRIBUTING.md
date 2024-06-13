# Contributing

**Neos and Flow are open-source software built by a global community to create an innovative Content Application Platform. Everyone is welcome to contribute to the community and has the possibility to influence the project.**

Please be considerate of our [code of conduct](https://www.neos.io/about/code-of-conduct.html)! We like to provide a positive and enjoyable environment for everybody.

Do you want to get a certain improvement in Neos or Flow? Did you find a problem? Do you want to help translate, promote or document the products?

Become part of the Neos project community and help us build a content management system with a great user experience!

----------

## How to participate

The development of the project is structured in an agile way. You can found more details about the current teams on the official Neos website. You can join one of those team or decide to simply send a single or irregular contributions. All contributions are welcome.
Our [Code contribution guidline](https://discuss.neos.io/t/code-contribution-guideline/503) might also be helpful to start with.

### You are a design or UX expert?

The Neos project is focused on providing first class UX for our products. Help out with Neos CMS user interaction design or graphic design! Get in touch using our [Design & UX](https://discuss.neos.io/c/creating/design-ux) discussion forum.

### You are a Content Strategist?

We love content, so we try to solve today's (and tomorrows's!) problems in producing, curating and managing content. Input from you as a content strategist help us to reach our goal. Contact us hello@neos.io to see how we can collaborate.

### You are a coder? Backend, frontend or both?

#### Our stack

We spend our time on the following tools, you should have a good understanding of this tooling.

- PHP
- MySQL / PostgreSQL
- ReactJS
- Git

You can find us on [GitHub](https://github.com/neos) and we use it as our main issue tracker as well.

#### Related resources

Read on further down!

### You have skills in marketing, branding or product strategy?

The Neos project is responsible for the Neos brand and we manage two product Neos CMS, our content application platform, and Flow Framework, a modern PHP based framework designed to support Neos or any complex PHP application.

We are currently in the process to create the identity for the Neos brand. If you have skills in this area your are highly welcome, just ping us to have more information on the current status of the branding project.

### You have other skills that you think can be useful for the community?

Who knows, some of your skills are not in this list, but can be really useful for the community. You are a project manager, an event organizer, a brewer, a team builder and yoga trainer and you want to support an open source community, connect with us: **hello@neos.io** 

We certainly have a something that matches your dream job and where you can help the project.

### Test

Your are an end user, enthusiast developer and you have some hours to help the community? Let's see how you can setup Neos, test the product and report issue to the community.


#### Related resources

Read on further down!

#### Our stack

We spend our time on the following tools, you should have a good understanding of this tooling.

- PHPunit (unit and functional testing)
- Behat (functional testing)
- GIT 

You can find us on [Github](https://github.com/neos) and we also use it as our main issue tracker.

### Translate and document

You can contribute to the translation process, by creating an account at [Crowdin](https://crowdin.com/project/neos).

On a regularly basis we synchronize the translation from Crowdin to our Git repositories (internally XLIFF is used for storing translations). See the [description of the translation process](https://www.neos.io/contribute/translating-neos.html) on our website for details.

### You have some knowledge, try to help others

Answer questions about the products use and development.

- Join our community discussion on [discuss.neos.io](https://discuss.neos.io)
- Meet us on Slack [slack.neos.io](http://slack.neos.io)

### Support the Neos Project development with bounties

The Neos project is in the process of founding a legal body. Until then it is not possible to directly donate to the Neos project. For the time being Sandstorm Media has therefore agreed with the Neos team to sell Neos supporter badges and use all revenues to support the Neos project. [Check our donations page](https://www.neos.io/contribute/donating-to-neos.html)

## Thanks for helping Neos CMS and Flow Framework

If you have any questions, [we are happy to help you](https://www.neos.io/contact).

---

## Details on development and testing

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

To run tests, run `composer run test-unit` for unit tests or `composer test-functional` for functional/integration tests.

---

**Note**

We use an upmerging strategy: create all bugfixes to the lowest maintained branch that contains the issue. Typically, this is the second last LTS release - see the diagram at https://www.neos.io/features/release-process.html.

For new features, pull requests should be made against the branch for the next minor version (named like `x.y`). Breaking changes must only go into the branch for the next major version.

---

For more detailed information, see https://discuss.neos.io/t/development-setup/504,
https://discuss.neos.io/t/creating-a-pull-request/506 and
https://discuss.neos.io/t/git-branch-handling-in-the-neos-project/6013


### Neos 9


#### Prerequisites

- You need PHP 8.2 installed.
- Please be sure to run off the Neos development distribution in branch 9.0, to avoid dependency issues.

#### Setup

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

#### Running the Tests

##### Behavioural tests (Behat)

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

##### Benchmark tests

For those you need to install `phive` first, see https://phar.io/#Install, and then run

```shell
phive install phpbench
```

to install the needed [PHPBench]([url](https://github.com/phpbench/phpbench)) tool.

Then run

```shell
composer run benchmark
```
