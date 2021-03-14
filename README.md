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
| Cut / Copy / Paste          |     ✅     |        ✅        |
| Move Nodes                  |     ✅     |        ✅        |
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
| Change node type            |    ✅      |       ✅         |
| **Advanced**                |            |                  |
| Dimensions                  |     ✅     |        ✅        |
| Dimension Fallback          |     ✅     |        ✅        |
| Multiple Sites              |     ✅     |        ✅        |
| Permissions / Policy        |     ✅     |                  |
| **Maintenance**             |            |                  |
| Export / Import             |     ✅     |       ✅⏩      |
| Node Migrations             |     ✅     |        ⏩ (MEDIUM)|
| Node Repair                 |     ✅     |        ✅        |
| Integrity Checks            |     🚫     |        ⏩        |
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

The new code should be compatible with **PHP 7.4**

## Getting Started / Installation

See https://github.com/neos/neos-development-distribution/tree/event-sourced

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
- the old `Neos\ContentRepository\Domain\Model\Node` implements the full new `NodeInterface` and most of `TraversableNodeInterface`. This is needed to ensure we can build FlowQuery implementations which can work with old and new API at once.
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

## CR / Neos.ContentGraph.PostgreSQLAdapter

implementation of the `ContentGraphInterface` and `ContentSubgraphInterface` using PostgreSQL queries.

## CR / Neos.EventSourcedNeosAdjustments

It turns out that there are numerous changes needed to the details of Neos.Neos - so this package hooks into various places in the Neos lifecycle to override certain Neos functionality.

We often completely override certain classes / behaviors from the Neos core completely; so that should make merging the changes back to the Neos.Neos package at some point a lot easier because we can then replace full classes instead of only
individual pieces.

This package consists of the following bounded contexts, listed in their order during request processing:

### NodeImportFromLegacyCR

This contains a CommandController and a service to generate events from reading `NodeData`. It can be activated using the new CLI command.

### EventSourcedRouting

We replace the default `FrontendNodeRoutePartHandler` by providing an extra implementation of `FrontendNodeRoutePartHandlerInterface`.

**Activation**: We replace the implementation of `FrontendNodeRoutePartHandlerInterface` in `Objects.yaml`.

- internally, the `Http` and `Routing` namespaces are used for behaviours internal to the routing.

### EventSourcedFrontController

This is a replacement for `Frontend\NodeController` of Neos.Neos.

**Activation**: We trigger this controller by AOP (in `NodeControllerAspect`): We call the new controller when `processRequest()` is called for the Neos controller.

### Fusion

- We replace certain Fusion implementations which are already re-implemented to work more efficiently with the ContentGraph API; and which implement linking (because this API also changed). This includes:
  - `Menu / DimensionMenu`
  - `NodeUri, ConvertUris`
  - `ContentElementEditable / ContentElementWrapping` (because the ContentElementWrapping service has changed quite a lot)
  - **Activation**: using fusion `autoInclude` in `Settings.yaml`, we load the Fusion file `resource://Neos.EventSourcedNeosAdjustments/Private/Fusion/Root.fusion`. This `Root.fusion` *replaces the implementations* for the aforementioned Fusion
    objects, so things work as expected for integrators (without new namespaces).

- Eel `NodeHelper` and `WorkspaceHelper`
  - **Activation**: These helpers are registered under the names `Neos.EventSourcedNeosAdjustments.*`; so a separate name. These helpers are explicitely used in the `Root.fusion` mentioned a few lines above.

- custom `ExceptionHandler` because this also needs the replacement `ContentElementWrappingService`.
  - **Activation**: This helper is used as exception handlers in the `Root.fusion` mentioned a few lines above.
  - If people used these exception handlers themselves, they need to reconfigure them to the new implementations.

### Fluid

- We replace Linking and Content Element Wrapping ViewHelpers, because Node Linking has changed and ContentElementWrapping has changed as well.
  - **Activation**: Using AOP, the `ViewHelperReplacementAspect` implements aliasing of ViewHelper classes; effectively returning the VHs in this namespace instead of the default ones.

### ContentElementWrapping

We implement a completely new `ContentElementWrappingService` and `ContentElementWrappingService`; mainly because they change quite a bit and their interfaces now require `TraversableNodeInterface` instead of the legacy `NodeInterface`.

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
