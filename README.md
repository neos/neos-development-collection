# Event Sourced Content Repository Collection

This is the package bundle you can install alongside a plain Neos to play around with the event-sourced CR.

## Getting Started / Installation

1. In your distribution's `composer.json`, add this repository to the `repositories` section:

    ```json
    {
        "repositories": [
            {
                "type": "git",
                "url": "https://github.com/skurfuerst/contentrepository-development-collection.git"
            }
        ]
    }
    ```

2. Then, run `composer require neos/contentrepository-development-collection:@dev neos/event-sourcing:dev-master`.

3. If using dimensions, the dimension configuration has changed. Use the following configuration in `Settings.yaml` for the Demo site (Adjust as needed):

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


## Road to first running alpha

- [x] create standalone package collection instead of branches
- [x] command to import from NodeData to events
- [ ] ensure Behavioral Tests run again
- [ ] ensure Functional Tests run again
- [X] ensure Unit Tests run again
- [ ] add switch to use event-sourced read model in Fusion rendering (!possibly difficult due to method signatures!)
- [ ] figure out how/if to use event sourced Site/Domain (!possibly difficult!) -> maybe already bring upstream?
- [ ] change RoutePart Handler when using event-sourced mode
- [ ] allow to open User Interface based on Event-Sourced read model
- [ ] create Commands in response to UI interaction
- [ ] create feature list in this README, detailing what is supported and what not yet.
- [ ] (further TODOs here; this list is not complete yet)
