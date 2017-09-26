@fixtures
Feature: Translate node in aggregate

  As a user of the CR I want to create a copy of a node in an aggregate in another dimension space point as a
  translation.

  Background:
    Given I have the following content dimensions:
      | Identifier | Default | Presets                                     |
      | language   | mul     | mul=mul; de=de,mul; en=en,mul; ch=ch,de,mul |
    And I have the following NodeTypes configuration:
    """
    unstructured: []
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Testing:Content': []
    """
    And the command "CreateRootWorkspace" is executed with payload:
      | Key                      | Value                                | Type |
      | workspaceName            | live                                 |      |
      | workspaceTitle           | Live                                 |      |
      | workspaceDescription     | The live workspace                   |      |
      | initiatingUserIdentifier | 00000000-0000-0000-0000-000000000000 |      |
      | contentStreamIdentifier  | c75ae6a2-7254-4d42-a31b-a629e264069d |      |
      | rootNodeIdentifier       | 5387cb08-2aaf-44dc-a8a1-483497aa0a03 |      |
    And the Event "Neos.ContentRepository:NodeAggregateWithNodeWasCreated" was published to stream "Neos.ContentRepository:ContentStream:c75ae6a2-7254-4d42-a31b-a629e264069d" with payload:
      | Key                           | Value                                                                                                                                                 | Type |
      | contentStreamIdentifier       | c75ae6a2-7254-4d42-a31b-a629e264069d                                                                                                                  |      |
      | nodeAggregateIdentifier       | c2037dc4-a20d-11e7-ba09-b3eb6d631979                                                                                                                  |      |
      | nodeTypeName                  | unstructured                                                                                                                                          |      |
      | dimensionSpacePoint           | {"coordinates":{"language":"mul"}}                                                                                                                    | json |
      | visibleDimensionSpacePoints   | {"points":[{"coordinates":{"language":"mul"}},{"coordinates":{"language":"de"}},{"coordinates":{"language":"en"}},{"coordinates":{"language":"ch"}}]} | json |
      | nodeIdentifier                | ead94f26-a20d-11e7-8ecc-43aabe596a03                                                                                                                  |      |
      | parentNodeIdentifier          | 5387cb08-2aaf-44dc-a8a1-483497aa0a03                                                                                                                  |      |
      | nodeName                      | sites                                                                                                                                                 |      |
      | propertyDefaultValuesAndTypes | {}                                                                                                                                                    | json |

  Scenario: Translate existing node with "mul" parent
    # Node /text1
    Given the Event "Neos.ContentRepository:NodeAggregateWithNodeWasCreated" was published to stream "Neos.ContentRepository:ContentStream:c75ae6a2-7254-4d42-a31b-a629e264069d" with payload:
      | Key                           | Value                                                                            | Type |
      | contentStreamIdentifier       | c75ae6a2-7254-4d42-a31b-a629e264069d                                             |      |
      | nodeAggregateIdentifier       | 35411439-94d1-4bd4-8fac-0646856c6a1f                                             |      |
      | nodeTypeName                  | Neos.ContentRepository.Testing:Content                                           |      |
      | dimensionSpacePoint           | {"coordinates":{"language":"de"}}                                                | json |
      | visibleDimensionSpacePoints   | {"points":[{"coordinates":{"language":"de"}},{"coordinates":{"language":"ch"}}]} | json |
      | nodeIdentifier                | 75106e9a-7dfb-4b48-8b7a-3c4ab2546b81                                             |      |
      | parentNodeIdentifier          | ead94f26-a20d-11e7-8ecc-43aabe596a03                                             |      |
      | nodeName                      | text1                                                                            |      |
      | propertyDefaultValuesAndTypes | {}                                                                               | json |

    When the command "TranslateNodeInAggregate" is executed with payload:
      | Key                       | Value                                | Type |
      | contentStreamIdentifier   | c75ae6a2-7254-4d42-a31b-a629e264069d |      |
      | sourceNodeIdentifier      | 75106e9a-7dfb-4b48-8b7a-3c4ab2546b81 |      |
      | destinationNodeIdentifier | 01831e48-a20c-11e7-851a-dfef4f55c64c |      |
      | dimensionSpacePoint       | {"coordinates":{"language":"en"}}    | json |

    Then I expect exactly 5 events to be published on stream "Neos.ContentRepository:ContentStream:c75ae6a2-7254-4d42-a31b-a629e264069d"
    And event at index 4 is of type "Neos.ContentRepository:NodeInAggregateWasTranslated" with payload:
      | Key                             | Expected                                       | AssertionType |
      | contentStreamIdentifier         | c75ae6a2-7254-4d42-a31b-a629e264069d           |               |
      | sourceNodeIdentifier            | 75106e9a-7dfb-4b48-8b7a-3c4ab2546b81           |               |
      | destinationNodeIdentifier       | 01831e48-a20c-11e7-851a-dfef4f55c64c           |               |
      | destinationParentNodeIdentifier | ead94f26-a20d-11e7-8ecc-43aabe596a03           |               |
      | dimensionSpacePoint             | {"coordinates":{"language":"en"}}              | json          |
      | visibleDimensionSpacePoints     | {"points":[{"coordinates":{"language":"en"}}]} | json          |
