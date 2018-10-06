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

4. Ensure to have your Site node marked with `Neos.Neos:Site`; for the Demo Site, put the following in `NodeTypes.yaml`:

    ```yaml
    'Neos.Demo:Homepage':
      superTypes:
        'Neos.Neos:Site': true
    ```

5. create necessary tables using `./flow doctrine:migrate`

6. create events from your (legacy) NodeData by running `./flow contentrepositorymigrate:run` - this also populates the projection

## Road to first running alpha

- [x] create standalone package collection instead of branches
- [x] command to import from NodeData to events
- [x] make it work with Neos.ContentRepository.DimensionSpace package
- [ ] ensure Behavioral Tests run again
- [ ] ensure Functional Tests run again
- [ ] figure out how/if to use event sourced Site/Domain (!possibly difficult!) -> maybe already bring upstream?
- [x] change RoutePart Handler when using event-sourced mode
- [x] adjust NodeController when using event-sourced mode
- [ ] add switch to use event-sourced read model in Fusion rendering (!possibly difficult due to method signatures!)
- [ ] allow to open User Interface based on Event-Sourced read model
- [ ] create Commands in response to UI interaction
- [x] create feature list in this README, detailing what is supported and what not yet.
- [ ] support empty content dimension values in URL; e.g. "/de/..." for german, and "/..." for english
- [ ] (further TODOs here; this list is not complete yet)
