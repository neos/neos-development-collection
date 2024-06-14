[![Code
Climate](https://codeclimate.com/github/neos/neos-development-collection/badges/gpa.svg)](https://codeclimate.com/github/neos/neos-development-collection)
[![StyleCI](https://styleci.io/repos/40964014/shield?style=flat)](https://styleci.io/repos/40964014)
[![Latest Stable
Version](https://poser.pugx.org/neos/neos-development-collection/v/9.0)](https://packagist.org/packages/neos/neos-development-collection)
[![License](https://poser.pugx.org/neos/neos-development-collection/license)](https://raw.githubusercontent.com/neos/neos-development-collection/9.0/LICENSE)
[![Documentation](https://img.shields.io/badge/documentation-master-blue.svg)](https://neos.readthedocs.org/en/9.0/)
[![Slack](http://slack.neos.io/badge.svg)](http://slack.neos.io)
[![Discussion Forum](https://img.shields.io/badge/forum-Discourse-39c6ff.svg)](https://discuss.neos.io/)
[![Issues](https://img.shields.io/github/issues/neos/neos-development-collection.svg)](https://github.com/neos/neos-development-collection/issues)
[![Translation](https://img.shields.io/badge/translate-weblate-85ae52.svg)](https://hosted.weblate.org/projects/neos/)
[![Twitter](https://img.shields.io/twitter/follow/neoscms.svg?style=social)](https://twitter.com/NeosCMS)

# Neos development collection

This repository is a collection of packages for the Neos content
application platform (learn more on <https://www.neos.io/>). The
repository is used for development and all pull requests should go into
it.

## Installation and Setup

If you want to install Neos, please have a look at the installation &
setup documentation:
<https://docs.neos.io/guide/installation-development-setup>

**For (specific) documentation on Neos 9, read on below...**

## Contributing

If you want to contribute to Neos and want to set up a development
environment, then please read the instructions in [CONTRIBUTING.md](CONTRIBUTING.md)

**For (specific) documentation on Neos 9, read on below...**

## Neos 9 and the Event-Sourced Content Repository (ES CR)

### Prerequisites

- You need PHP 8.2 installed.
- Please be sure to run off the Neos development distribution in branch 9.0, to avoid dependency issues.

### Setup

Follow the usual configuration steps (as for Neos 8) to install Composer dependencies and configure the database connection in `Settings.yaml` Then:

1.  Run Doctrine Migrations:

    ``` bash
    ./flow doctrine:migrate
    FLOW_CONTEXT=Testing/Postgres ./flow doctrine:migrate
    ```

2.  Setup the Content Repository

    ``` bash
    ./flow cr:setup
    ```

### Site Setup

You can chose from one of the following options:

#### Creating a new Site

``` bash
./flow site:create neosdemo Neos.Demo Neos.Demo:Document.Homepage
```

#### Migrating an existing (Neos < 9.0) Site

``` bash
# WORKAROUND: for now, you still need to create a site (which must match the root node name)
# !! in the future, you would want to import *INTO* a given site (and replace its root node)
./flow site:create neosdemo Neos.Demo Neos.Demo:Document.Homepage

# the following config points to a Neos 8.0 database (adjust to your needs), created by
# the legacy "./flow site:import Neos.Demo" command.
./flow cr:migrateLegacyData --config '{"dbal": {"dbname": "neos80"}, "resourcesPath": "/path/to/neos-8.0/Data/Persistent/Resources"}'
```

#### Importing an existing (Neos >= 9.0) Site from an Export

``` bash
# import the event stream from the Neos.Demo package
./flow cr:import Packages/Sites/Neos.Demo/Resources/Private/Content
```

### Running Neos

> ``` bash
> ./flow server:run
> ```
