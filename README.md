# Event Sourced Content Repository Collection

This is the package bundle you can install alongside a plain Neos to play around with the event-sourced CR.

## Feature comparison

✅ Done

⏩ Currently worked on

🚫 Will not be supported

| Feature                     | Current CR | Event Sourced CR |
| --------------------------- |:----------:|:----------------:|
| **Basics**                  |            |                  |
| Create/ Edit / Delete Nodes |     ✅     |        ⏩        |
| Shortcut Handling            |    ✅     |                  |
| Query Nodes                 |     ✅     |        ⏩        |
| Cut / Copy / Paste          |     ✅     |                  |
| Move Nodes                  |     ✅     |        ⏩        |
| Hide Nodes                  |     ✅     |                  |
| History                     |     ✅     |                  |
| Undo / Redo                 |     🚫     |                  |
| Setting Start / End time    |     ✅     |                  |
| Workspaces                  |     ✅     |                  |
| Resolving Referencing Nodes |     🚫     |        ⏩        |
| **Advanced**                |            |                  |
| Dimensions                  |     ✅     |        ⏩        |
| Dimension Fallback          |     ✅     |        ⏩        |
| Multiple Sites              |     ✅     |                  |
| **Maintenance**             |            |                  |
| Export / Import             |     ✅     |                  |
| Node Migrations             |     ✅     |                  |
| Node Repair                 |     ✅     |                  |



## Getting Started / Installation

1. In your distribution's `composer.json`, add this repository to the `repositories` section:

    ```json
    {
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
    }
    ```

2. Adjust the distribution `composer.json` as follows:

    ```
    {
        "require": {
            "neos/neos-development-collection": "dev-event-sourced-patch as dev-master",
            "neos/flow-development-collection": "@dev",

            "neos/contentrepository-development-collection": "@dev",
            "neos/event-sourcing": "dev-master",
            "neos/neos-ui": "dev-event-sourced-patch as dev-master",
            ...
        }
    }
    ```

3. Then, run `composer update`.

4. If using dimensions, the dimension configuration has changed. Use the following configuration in `Settings.yaml` for the Demo site (Adjust as needed):

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
                  value: us
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

4. create necessary tables using `./flow doctrine:migrate`

5. create events from your (legacy) NodeData by running `./flow contentrepositorymigrate:run` - this also populates the projection

6. Do a manual UI rebuild due to https://github.com/neos/neos-ui/pull/2178 currently needed:

    ```
    cd Packages/Application/Neos.Neos.Ui
    make setup
    make build
    ```

7. Enable FrontendDevelopmentMode in `Settings.yaml`:

    ```
    Neos:
      Neos:
        Ui:
          frontendDevelopmentMode: true
    ```

## Road to first running alpha

- [x] create standalone package collection instead of branches
- [x] command to import from NodeData to events
- [x] make it work with Neos.ContentRepository.DimensionSpace package
- [ ] ensure Behavioral Tests run again
- [ ] ensure Functional Tests run again
- [x] figure out how/if to use event sourced Site/Domain (!possibly difficult!) -> fixed
- [x] change RoutePart Handler when using event-sourced mode
- [x] adjust NodeController when using event-sourced mode
- [x] add switch to use event-sourced read model in Fusion rendering (!possibly difficult due to method signatures!)
- [x] allow to open User Interface based on Event-Sourced read model
- [ ] create Commands in response to UI interaction
- [x] create feature list in this README, detailing what is supported and what not yet.
- [x] support empty content dimension values in URL; e.g. "/de/..." for german, and "/..." for english
- [ ] (further TODOs here; this list is not complete yet)
- [ ] absolute node referencing for ContentCollection (e.g. shared footer in Demo Site


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
