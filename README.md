# Event Sourced Content Repository Collection

[![Build Status](https://travis-ci.org/neos/contentrepository-development-collection.svg?branch=master)](https://travis-ci.org/neos/contentrepository-development-collection) [![StyleCI](https://github.styleci.io/repos/151722585/shield?branch=master)](https://github.styleci.io/repos/151722585)

This is the package bundle you can install alongside a plain Neos to play around with the event-sourced CR.

## Feature comparison

✅ Done

⏩ Currently worked on

🚫 Will not be supported

| Feature                     | Current CR | Event Sourced CR |
| --------------------------- |:----------:|:----------------:|
| **Basics**                  |            |                  |
| Create/ Edit / Delete Nodes |     ✅     |        ✅        |
| Shortcut Handling           |     ✅     |        ⏩        |
| Query Nodes                 |     ✅     |        ✅        |
| Cut / Copy / Paste          |     ✅     |        ⏩        |
| Move Nodes                  |     ✅     |        ✅⏩      |
| Hide Nodes                  |     ✅     |        ✅        |
| History                     |    (✅)    |                  |
| Basic Workspaces            |     ✅     |        ✅        |
| Workspace Module            |     ✅     |         ⏩       |
| Nested Workspaces           |     ✅     |                  |
| Undo / Redo                 |     🚫     |                  |
| Setting Start / End time    |     ✅     |        EASY      |
| Resolving Referencing Nodes |     🚫     |        ✅        |
| Menu Rendering              |    ✅      |       ✅         |
| Dimension Menu Rendering    |    ✅      |       ✅         |
| Supporting "not in menu"    |    ✅      |       ✅         |
| **Advanced**                |            |                  |
| Dimensions                  |     ✅     |        ✅        |
| Dimension Fallback          |     ✅     |        ✅        |
| Multiple Sites              |     ✅     |        ✅        |
| Permissions / Policy        |     ✅     |                  |
| **Maintenance**             |            |                  |
| Export / Import             |     ✅     |        EASY      |
| Node Migrations             |     ✅     |        ⏩ (MEDIUM)|
| Node Repair                 |     ✅     |        ⏩        |
| **API**                     |            |                  |
| Separate Read and Write API |     🚫     |        ✅        |
| More convenient write API   |            |                  |
| FlowQuery is compatible     |    ✅      |       ✅         |
| Advanced test cases         |    🚫      |       ✅         |
| Don't use ORM, but direct SQL queries|    🚫      |       ✅         |
| Asynchronous operations possible |    🚫      |       ✅         |
| performant node moving     |    🚫      |       ✅         |
| performant node deletion   |    🚫      |       ✅         |
| near-constant read performance |    🚫      |       ✅         |
| **User Interface**         |            |                  |
| Ensure node deletion can be published in UI    |   ✅     |                |
| Support Dimension Constraints    |   ✅     |               |
| Publish Workspace              |   ✅     |   ✅         |
| Publish Current Page           |   ✅     |   ✅          |
| Discard all                    |   ✅     |   ✅          |
| Discard Current Page           |   ✅     |   ✅          |


## Requirements

### DB

The Event Sourced Content Repository relies on a feature called (Recursive) Common Table Expressions (CTE) that require
* [MySQL](https://www.mysql.com/why-mysql/presentations/mysql-80-common-table-expressions/): 8.0+
* [MariaDB](https://mariadb.com/kb/en/library/recursive-common-table-expressions-overview/): 10.2.2+

Lateron, we will also support [PostgreSQL](https://www.postgresql.org/docs/8.4/queries-with.html). (We know it will work, but we did not create migrations or did testing yet).

### PHP

The new code should be compatible with **PHP 7.2**

## Getting Started / Installation

1. Get started by cloning the master branch of the [neos-development-distribution](https://github.com/neos/neos-development-distribution/).
Assuming you want to install to a directory named `neos-es`
```bash
git clone https://github.com/neos/neos-development-distribution.git neos-es
cd neos-es
composer install
```
**NOTE**: PHP7.2 is recommmend

2. Install Neos as usual, via the browser or manually:
   - define a database in `Configuration/Settings.yaml`
   - run `./flow doctrine:migrate`
   - import the demo site using `./flow site:import --package-key=Neos.Demo`
   - create a backend user using `./flow user:create --roles Administrator admin password My Admin`
   - ensure that when running `./flow server:run`, you get frontend output; and you can log into the backend.

### In order to get the necessary code base you need to do a few things:

1. Add `neos/contentrepository-development-collection` and `neos/content-repository-dimensionspace` dependencies ` as custom repositories`
```
"repositories": [
    {
        "type": "git",
        "url": "https://github.com/neos/contentrepository-development-collection.git"
    },
    {
        "type": "git",
        "url": "https://github.com/neos/content-repository-dimensionspace.git"
    }
]
```
2. Set `minimum-stability` to `dev`
```
"minimum-stability": "dev"
```
3. Set `prefer-stable` as `true`
```
"prefer-stable": true
```
4. use the `require` part ot the composer.json file below:

The resulting `composer.json` file should look something like this:
```yaml
{
    "config": {
        "vendor-dir": "Packages/Libraries",
        "bin-dir": "bin"
    },
    "minimum-stability": "dev",
    "prefer-stable": true,
    "repositories": [
        {
            "type": "git",
            "url": "https://github.com/neos/contentrepository-development-collection.git"
        },
        {
            "type": "git",
            "url": "https://github.com/neos/content-repository-dimensionspace.git"
        }
    ],
    "require": {
        "neos/neos-development-collection": "@dev",
        "neos/flow-development-collection": "@dev",

        "neos/contentrepository-development-collection": "dev-master",
        "neos/content-repository-dimensionspace": "dev-master",
        "neos/event-sourcing": "^2.0",
        "flowpack/jobqueue-common": "dev-master",

        "neos/demo": "@dev",

        "neos/neos-ui": "dev-event-sourced-patch as dev-master",
        "neos/neos-ui-compiled": "dev-master as dev-event-sourced-patch",

        "neos/party": "@dev",
        "neos/seo": "@dev",
        "neos/imagine": "@dev",
        "neos/twitter-bootstrap": "@dev",
        "neos/form": "@dev",
        "neos/setup": "@dev",
        "flowpack/neos-frontendlogin": "@dev",
        "neos/buildessentials": "@dev",
        "mikey179/vfsstream": "~1.6",
        "phpunit/phpunit": "~7.1.0",
        "symfony/css-selector": "~2.0",
        "neos/behat": "dev-master"
    },
    "scripts": {
        "post-update-cmd": "Neos\\Flow\\Composer\\InstallerScripts::postUpdateAndInstall",
        "post-install-cmd": "Neos\\Flow\\Composer\\InstallerScripts::postUpdateAndInstall",
        "post-package-update": "Neos\\Flow\\Composer\\InstallerScripts::postPackageUpdateAndInstall",
        "post-package-install": "Neos\\Flow\\Composer\\InstallerScripts::postPackageUpdateAndInstall"
    }
}
```
5. Then, run `composer update`.

6. After that, run `./flow flow:package:rescan`.

**HINT**: If you experice issues clear the `Data/Temporary` directory and run the command again.

7. If using dimensions, the dimension configuration has changed. Use the following configuration in `Settings.yaml` for the Demo site (Adjust as needed):

```yaml
Neos:
  EventSourcedContentRepository:
    contentDimensions:
      language:
        label: 'Neos.Demo:Main:contentDimensions.language'
        icon: icon-language
        defaultValue: en_US
        resolution:
          mode: 'uriPathSegment'
        values:
          en_US:
            label: 'English (US)'
            resolution:
              value: en
            specializations:
              en_UK:
                label: 'English (UK)'
                resolution:
                  value: uk
          de:
            label: German
            resolution:
              value: de
            specializations:
              nl:
                label: Dutch
                resolution:
                  value: nl
          fr:
            label: French
            resolution:
              value: fr
          da:
            label: Danish
            resolution:
              value: da
          lv:
            label: Latvian
            resolution:
              value: lv
```

8. create necessary tables using `./flow doctrine:migrate`

9. create events from your (legacy) NodeData by running `./flow contentrepositorymigrate:run` - this also populates the projection.

   NOTE: The output of this command is still a little weird; showing lots of var_dumps. When this command does
         not show a fatal error, it ran through successfully.

10. Do a manual UI rebuild due to https://github.com/neos/neos-ui/pull/2178 currently needed:

```
cd Packages/Application/Neos.Neos.Ui
make setup
```

11. Enable FrontendDevelopmentMode in `Settings.yaml`:

```yaml
Neos:
  Neos:
    Ui:
      frontendDevelopmentMode: true
```

12. The frontend should now work as expected. Test that the frontend rendering works.

13. After logging into the backend, you might still see a fatal error. In that case manually remove the URL query parameters so that the URL is only `/neos/content`

14. In case you want to start with clean events and a clean projection (after you did some changes), re-run `./flow contentrepositorymigrate:run`

15. To set up Behavioral tests, do the following:

    - install Behat: `composer require neos/behat ^6.0`
    - clear the cache: `rm -Rf Data/Temporary; rm -Rf Build/Behat`
    - set up behat: `./flow behat:setup`
    - Create a new database for behat
    - create `Configuration/Testing/Behat/Settings.yaml` with the following contents:

        ```yaml
        Neos:
          Flow:
            persistence:
              backendOptions:
                driver: pdo_mysql
                dbname: 'YOUR-DB-NAME-HERE'
                user: 'root'
                password: ''
        ```

        Important: the driver must be set to pdo_mysql; and the DB name and user need to be specified.

16. To run the behavioral tests, do:

    ```bash
    bin/behat -c Packages/CR/Neos.EventSourcedContentRepository/Tests/Behavior/behat.yml.dist
    ```


## Road to first running beta

- [x] create standalone package collection instead of branches
- [x] command to import from NodeData to events
- [x] make it work with Neos.ContentRepository.DimensionSpace package
- [x] ensure Behavioral Tests run again
- [x] Update CR for Neos 5
- [ ] Update EventSourcedNeosAdjustments for Neos 5
- [ ] Content Cache (#103)
- [x] ensure Functional Tests run again
- [x] figure out how/if to use event sourced Site/Domain (!possibly difficult!) -> fixed; won't use event sourced site/domain
- [x] change RoutePart Handler when using event-sourced mode
- [x] adjust NodeController when using event-sourced mode
- [x] add switch to use event-sourced read model in Fusion rendering (!possibly difficult due to method signatures!)
- [x] allow to open User Interface based on Event-Sourced read model
- [x] implement Show/Hide of nodes (recursively)
- [ ] create Commands in response to UI interaction
  - [x] SetProperty command
  - [x] CreateNode
  - [x] MoveNode
  - [x] ShowNode
  - [x] DisableNode
- [x] create feature list in this README, detailing what is supported and what not yet.
- [x] support empty content dimension values in URL; e.g. "/de/..." for german, and "/..." for english
- [x] absolute node referencing for ContentCollection (e.g. shared footer in Demo Site)
- [ ] fix Policy handling to configure permissions for various UI parts
- [x] fix structure tree
- [ ] show correct workspace state after reload (top publish button)
- [?] fix inline linking
- [x] fix node tree search
- [?] fix node tree filter
- [ ] Implement Node Repair
- [ ] (further TODOs here; this list is not complete yet)

# Technical Description (for developers)

This section should give an overview about the different involved packages, to ease understanding the different moving parts.


## Neos.ContentRepository

see https://github.com/neos/neos-development-collection/pull/2202 for the Pull Request.

- in namespace `Domain\Projection\Content`, the new `NodeInterface` and `TraversableNodeInterface` are defined.
- in namespace `Domain\ValueObject`, corresponding value objects are defined.
- the old `Neos\ContentRepository\Domain\Model\Node` implements the full new `NodeInterface` and most of `TraversableNodeInterface`.
  This is needed to ensure we can build FlowQuery implementations which can work with old and new API at once.
- adjusted FlowQuery operations to `TraversableNodeInterface` (TODO not yet all of them)


## Neos.Neos

see https://github.com/neos/neos-development-collection/pull/2202 for the Pull Request.

- various detail improvements to use `TraversableNodeInterface` in the core (e.g. FusionView)


## Neos.ContentRepository.DimensionSpace

APIs to query the configured dimension space


## CR / Neos.EventSourcedContentRepository

Transition package implementing the event sourced CR core. In the longer run, will probably be merged into Neos.ContentRepository.

- `Domain\Context\*` implements Commands, Command Handlers, Events for Workspace, Content Stream, Nodes
- `Domain\Projection\*` implements projections for changes, workspace listing; and contains the definition for the main `Content` Graph projection (`ContentGraphInterface` and `ContentSubgraphInterface`)

## CR / Neos.ContentGraph.DoctrineDbalAdapter

implementation of the `ContentGraphInterface` and `ContentSubgraphInterface` using MySQL queries.


## CR / Neos.EventSourcedNeosAdjustments

It turns out that there are numerous changes needed to the details of Neos.Neos - so this package hooks into
various places in the Neos lifecycle to override certain Neos functionality.

We often completely override certain classes / behaviors from the Neos core completely; so that should make merging the
changes back to the Neos.Neos package at some point a lot easier because we can then replace full classes instead of
only individual pieces.

This package consists of the following bounded contexts, listed in their order during request processing:


### NodeImportFromLegacyCR

This contains a CommandController and a service to generate events from reading `NodeData`. It can be activated using
the new CLI command.


### EventSourcedRouting

We replace the default `FrontendNodeRoutePartHandler` by providing an extra implementation of `FrontendNodeRoutePartHandlerInterface`.

**Activation**: We replace the implementation of `FrontendNodeRoutePartHandlerInterface` in `Objects.yaml`.

- internally, the `Http` and `Routing` namespaces are used for behaviours internal to the routing.


### EventSourcedFrontController

This is a replacement for `Frontend\NodeController` of Neos.Neos.

**Activation**: We trigger this controller by AOP (in `NodeControllerAspect`): We call the new controller when `processRequest()` is called for the Neos controller.


### Fusion

- We replace certain Fusion implementations which are already re-implemented to work more efficiently with the ContentGraph
  API; and which implement linking (because this API also changed). This includes:
  - `Menu / DimensionMenu`
  - `NodeUri, ConvertUris`
  - `ContentElementEditable / ContentElementWrapping` (because the ContentElementWrapping service has changed quite a lot)
  - **Activation**: using fusion `autoInclude` in `Settings.yaml`, we load the Fusion file `resource://Neos.EventSourcedNeosAdjustments/Private/Fusion/Root.fusion`.
    This `Root.fusion` *replaces the implementations* for the aforementioned Fusion objects, so things work as expected for integrators (without new namespaces).

- Eel `NodeHelper` and `WorkspaceHelper`
  - **Activation**: These helpers are registered under the names `Neos.EventSourcedNeosAdjustments.*`; so a separate name.
    These helpers are explicitely used in the `Root.fusion` mentioned a few lines above.

- custom `ExceptionHandler` because this also needs the replacement `ContentElementWrappingService`.
  - **Activation**: This helper is used as exception handlers in the `Root.fusion` mentioned a few lines above.
  - If people used these exception handlers themselves, they need to reconfigure them to the new implementations.


### Fluid

- We replace Linking and Content Element Wrapping ViewHelpers, because Node Linking has changed and ContentElementWrapping
  has changed as well.
  - **Activation**: Using AOP, the `ViewHelperReplacementAspect` implements aliasing of ViewHelper classes;
    effectively returning the VHs in this namespace instead of the default ones.


### ContentElementWrapping

We implement a completely new `ContentElementWrappingService` and `ContentElementWrappingService`; mainly because
they change quite a bit and their interfaces now require `TraversableNodeInterface` instead of the legacy `NodeInterface`.

The new services are used in the overridden ViewHelpers (see section *Fluid* above); and in overridden Fusion implementations
(see section *Fusion* above).


### NodeAddress (Domain\Context\Content)

A `NodeAddress` is an external representation of a node (used in routing). TODO: Move to Neos.EventSourcedContentRepository.


### Ui

- `BackendController` is an alternative implementation for `Neos.Neos.Ui` `BackendController`.
  - **Activation**: We trigger this controller by AOP (in `BackendControllerAspect`): We call the new controller when `processRequest()` is called for the Neos backend controller.
- We create Content Streams on backend login using the `EditorContentStreamZookeeper` (TODO change name maybe?).
  - **Activation**: We trigger this service by Signal/Slot in `Package.php`.
- `Fusion` (for backend)
  - **Activation**: We load a custom `resource://Neos.EventSourcedNeosAdjustments/Private/Fusion/Backend/Root.fusion` using `Views.yaml`.
  - custom `NodeInfoHelper`, calling to a custom `NodePropertyConverterService`
- adjust the *DimensionSwitcher* JS component in `Resources/Private/UiAdapter`
- TODO: this is not everything yet.
