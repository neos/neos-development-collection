@fixtures
Feature: Translate node in aggregate

  As a user of the CR I want to create a copy of a node in an aggregate in another dimension space point as a
  translation.

  Background:
    Given I have the following content dimensions:
      | Identifier | Default | Values          | Generalizations      |
      | language   | mul     | mul, de, en, ch | ch->de->mul, en->mul |
    And I have the following NodeTypes configuration:
    """
    unstructured: []
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Testing:Content': []
    'Neos.ContentRepository.Testing:SubSubNode': {}
    'Neos.ContentRepository.Testing:SubNode':
      childNodes:
        foo:
          type: 'Neos.ContentRepository.Testing:SubSubNode'
    'Neos.ContentRepository.Testing:NodeWithAutoCreatedChildNodes':
      childNodes:
        main:
          type: 'Neos.ContentRepository.Testing:SubNode'
    """
    And the command "CreateRootWorkspace" is executed with payload:
      | Key                      | Value                                | Type |
      | workspaceName            | live                                 |      |
      | workspaceTitle           | Live                                 |      |
      | workspaceDescription     | The live workspace                   |      |
      | initiatingUserIdentifier | 00000000-0000-0000-0000-000000000000 |      |
      | contentStreamIdentifier  | c75ae6a2-7254-4d42-a31b-a629e264069d |      |
      | rootNodeIdentifier       | 5387cb08-2aaf-44dc-a8a1-483497aa0a03 |      |
      | rootNodeTypeName         | Neos.ContentRepository:Root          |      |
    # We have to add another node since the root node has no aggregate to find the new parent of the translated node
    # Node /sites
    And the Event "Neos.EventSourcedContentRepository:NodeAggregateWithNodeWasCreated" was published to stream "Neos.ContentRepository:ContentStream:c75ae6a2-7254-4d42-a31b-a629e264069d:NodeAggregate:c2037dc4-a20d-11e7-ba09-b3eb6d631979" with payload:
      | Key                           | Value                                                                      | Type |
      | contentStreamIdentifier       | c75ae6a2-7254-4d42-a31b-a629e264069d                                       |      |
      | nodeAggregateIdentifier       | c2037dc4-a20d-11e7-ba09-b3eb6d631979                                       |      |
      | nodeTypeName                  | unstructured                                                               |      |
      | dimensionSpacePoint           | {"language":"mul"}                                                         | json |
      | visibleInDimensionSpacePoints   | [{"language":"mul"},{"language":"de"},{"language":"en"},{"language":"ch"}] | json |
      | nodeIdentifier                | ead94f26-a20d-11e7-8ecc-43aabe596a03                                       |      |
      | parentNodeIdentifier          | 5387cb08-2aaf-44dc-a8a1-483497aa0a03                                       |      |
      | nodeName                      | sites                                                                      |      |
      | propertyDefaultValuesAndTypes | {}                                                                         | json |

  Scenario: Translate node with "mul" parent
    # Node /sites/text1
    Given the Event "Neos.EventSourcedContentRepository:NodeAggregateWithNodeWasCreated" was published to stream "Neos.ContentRepository:ContentStream:c75ae6a2-7254-4d42-a31b-a629e264069d:NodeAggregate:35411439-94d1-4bd4-8fac-0646856c6a1f" with payload:
      | Key                           | Value                                  | Type |
      | contentStreamIdentifier       | c75ae6a2-7254-4d42-a31b-a629e264069d   |      |
      | nodeAggregateIdentifier       | 35411439-94d1-4bd4-8fac-0646856c6a1f   |      |
      | nodeTypeName                  | Neos.ContentRepository.Testing:Content |      |
      | dimensionSpacePoint           | {"language":"de"}                      | json |
      | visibleInDimensionSpacePoints   | [{"language":"de"},{"language":"ch"}]  | json |
      | nodeIdentifier                | 75106e9a-7dfb-4b48-8b7a-3c4ab2546b81   |      |
      | parentNodeIdentifier          | ead94f26-a20d-11e7-8ecc-43aabe596a03   |      |
      | nodeName                      | text1                                  |      |
      | propertyDefaultValuesAndTypes | {}                                     | json |

    When the command "TranslateNodeInAggregate" is executed with payload:
      | Key                       | Value                                | Type |
      | contentStreamIdentifier   | c75ae6a2-7254-4d42-a31b-a629e264069d |      |
      | sourceNodeIdentifier      | 75106e9a-7dfb-4b48-8b7a-3c4ab2546b81 |      |
      | destinationNodeIdentifier | 01831e48-a20c-11e7-851a-dfef4f55c64c |      |
      | dimensionSpacePoint       | {"language":"en"}                    | json |

    Then I expect exactly 5 events to be published on stream with prefix "Neos.ContentRepository:ContentStream:c75ae6a2-7254-4d42-a31b-a629e264069d"
    And event at index 4 is of type "Neos.EventSourcedContentRepository:NodeInAggregateWasTranslated" with payload:
      | Key                             | Expected                             | AssertionType |
      | contentStreamIdentifier         | c75ae6a2-7254-4d42-a31b-a629e264069d |               |
      | sourceNodeIdentifier            | 75106e9a-7dfb-4b48-8b7a-3c4ab2546b81 |               |
      | destinationNodeIdentifier       | 01831e48-a20c-11e7-851a-dfef4f55c64c |               |
      | destinationParentNodeIdentifier | ead94f26-a20d-11e7-8ecc-43aabe596a03 |               |
      | dimensionSpacePoint             | {"language":"en"}                    | json          |
      | visibleInDimensionSpacePoints     | [{"language":"en"}]                  | json          |

  Scenario: Translate node with "mul" parent and auto-created child nodes
    # Node /sites/home
    Given the Event "Neos.EventSourcedContentRepository:NodeAggregateWithNodeWasCreated" was published to stream "Neos.ContentRepository:ContentStream:c75ae6a2-7254-4d42-a31b-a629e264069d:NodeAggregate:35411439-94d1-4bd4-8fac-0646856c6a1f" with payload:
      | Key                           | Value                                                        | Type |
      | contentStreamIdentifier       | c75ae6a2-7254-4d42-a31b-a629e264069d                         |      |
      | nodeAggregateIdentifier       | 35411439-94d1-4bd4-8fac-0646856c6a1f                         |      |
      | nodeTypeName                  | Neos.ContentRepository.Testing:NodeWithAutoCreatedChildNodes |      |
      | dimensionSpacePoint           | {"language":"de"}                                            | json |
      | visibleInDimensionSpacePoints   | [{"language":"de"},{"language":"ch"}]                        | json |
      | nodeIdentifier                | 75106e9a-7dfb-4b48-8b7a-3c4ab2546b81                         |      |
      | parentNodeIdentifier          | ead94f26-a20d-11e7-8ecc-43aabe596a03                         |      |
      | nodeName                      | home                                                         |      |
      | propertyDefaultValuesAndTypes | {}                                                           | json |
    # Auto-created node /sites/home/main
    And the Event "Neos.EventSourcedContentRepository:NodeAggregateWithNodeWasCreated" was published to stream "Neos.ContentRepository:ContentStream:c75ae6a2-7254-4d42-a31b-a629e264069d:NodeAggregate:c7dff472-a35f-11e7-86d3-8f1201f8ad78" with payload:
      | Key                           | Value                                  | Type |
      | contentStreamIdentifier       | c75ae6a2-7254-4d42-a31b-a629e264069d   |      |
      | nodeAggregateIdentifier       | c7dff472-a35f-11e7-86d3-8f1201f8ad78   |      |
      | nodeTypeName                  | Neos.ContentRepository.Testing:SubNode |      |
      | dimensionSpacePoint           | {"language":"de"}                      | json |
      | visibleInDimensionSpacePoints   | [{"language":"de"},{"language":"ch"}]  | json |
      | nodeIdentifier                | d527f9fe-a35f-11e7-a5e7-43351e1ec8d8   |      |
      | parentNodeIdentifier          | 75106e9a-7dfb-4b48-8b7a-3c4ab2546b81   |      |
      | nodeName                      | main                                   |      |
      | propertyDefaultValuesAndTypes | {}                                     | json |
    # Auto-created node /sites/home/main/foo
    And the Event "Neos.EventSourcedContentRepository:NodeAggregateWithNodeWasCreated" was published to stream "Neos.ContentRepository:ContentStream:c75ae6a2-7254-4d42-a31b-a629e264069d:NodeAggregate:f06a0838-a35f-11e7-a124-13b6f0b1a336" with payload:
      | Key                           | Value                                     | Type |
      | contentStreamIdentifier       | c75ae6a2-7254-4d42-a31b-a629e264069d      |      |
      | nodeAggregateIdentifier       | f06a0838-a35f-11e7-a124-13b6f0b1a336      |      |
      | nodeTypeName                  | Neos.ContentRepository.Testing:SubSubNode |      |
      | dimensionSpacePoint           | {"language":"de"}                         | json |
      | visibleInDimensionSpacePoints   | [{"language":"de"},{"language":"ch"}]     | json |
      | nodeIdentifier                | f87b4c26-a35f-11e7-8914-6f1247f2215a      |      |
      | parentNodeIdentifier          | d527f9fe-a35f-11e7-a5e7-43351e1ec8d8      |      |
      | nodeName                      | foo                                       |      |
      | propertyDefaultValuesAndTypes | {}                                        | json |

    When the command "TranslateNodeInAggregate" is executed with payload:
      | Key                       | Value                                | Type |
      | contentStreamIdentifier   | c75ae6a2-7254-4d42-a31b-a629e264069d |      |
      | sourceNodeIdentifier      | 75106e9a-7dfb-4b48-8b7a-3c4ab2546b81 |      |
      | destinationNodeIdentifier | 01831e48-a20c-11e7-851a-dfef4f55c64c |      |
      | dimensionSpacePoint       | {"language":"en"}                    | json |

    Then I expect exactly 9 events to be published on stream with prefix "Neos.ContentRepository:ContentStream:c75ae6a2-7254-4d42-a31b-a629e264069d"
    And event at index 6 is of type "Neos.EventSourcedContentRepository:NodeInAggregateWasTranslated" with payload:
      | Key                             | Expected                             | AssertionType |
      | contentStreamIdentifier         | c75ae6a2-7254-4d42-a31b-a629e264069d |               |
      | sourceNodeIdentifier            | 75106e9a-7dfb-4b48-8b7a-3c4ab2546b81 |               |
      | destinationNodeIdentifier       | 01831e48-a20c-11e7-851a-dfef4f55c64c |               |
      | destinationParentNodeIdentifier | ead94f26-a20d-11e7-8ecc-43aabe596a03 |               |
      | dimensionSpacePoint             | {"language":"en"}                    | json          |
      | visibleInDimensionSpacePoints     | [{"language":"en"}]                  | json          |
    And event at index 7 is of type "Neos.EventSourcedContentRepository:NodeInAggregateWasTranslated" with payload:
      | Key                             | Expected                             | AssertionType |
      | contentStreamIdentifier         | c75ae6a2-7254-4d42-a31b-a629e264069d |               |
      | sourceNodeIdentifier            | d527f9fe-a35f-11e7-a5e7-43351e1ec8d8 |               |
# TODO We cannot tell the destinationNodeIdentifier in advance for recursive translations (fully specify in command?)
#      | destinationNodeIdentifier       | 01831e48-a20c-11e7-851a-dfef4f55c64c           |               |
      | destinationParentNodeIdentifier | 01831e48-a20c-11e7-851a-dfef4f55c64c |               |
      | dimensionSpacePoint             | {"language":"en"}                    | json          |
      | visibleInDimensionSpacePoints     | [{"language":"en"}]                  | json          |
    And event at index 8 is of type "Neos.EventSourcedContentRepository:NodeInAggregateWasTranslated" with payload:
      | Key                         | Expected                             | AssertionType |
      | contentStreamIdentifier     | c75ae6a2-7254-4d42-a31b-a629e264069d |               |
      | sourceNodeIdentifier        | f87b4c26-a35f-11e7-8914-6f1247f2215a |               |
# TODO We cannot tell the destinationNodeIdentifier in advance for recursive translations (fully specify in command?)
#      | destinationNodeIdentifier       | 01831e48-a20c-11e7-851a-dfef4f55c64c           |               |
#      | destinationParentNodeIdentifier | ead94f26-a20d-11e7-8ecc-43aabe596a03           |               |
      | dimensionSpacePoint         | {"language":"en"}                    | json          |
      | visibleInDimensionSpacePoints | [{"language":"en"}]                  | json          |
