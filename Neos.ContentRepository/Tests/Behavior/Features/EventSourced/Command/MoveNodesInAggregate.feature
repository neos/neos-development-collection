@fixtures
Feature: Move nodes in aggregate before, into or after nodes in another aggregate

  As a user of the CR I want to move all nodes in an aggregate before, into or after matching nodes in another
  aggregate.

  Background:
    Given I have the following content dimensions:
      | Identifier | Default | Values          | Generalizations      |
      | language   | mul     | mul, de, en, ch | ch->de->mul, en->mul |
    And the command "CreateRootWorkspace" is executed with payload:
      | Key                      | Value                                | Type |
      | workspaceName            | live                                 |      |
      | workspaceTitle           | Live                                 |      |
      | workspaceDescription     | The live workspace                   |      |
      | initiatingUserIdentifier | 00000000-0000-0000-0000-000000000000 |      |
      | contentStreamIdentifier  | c75ae6a2-7254-4d42-a31b-a629e264069d |      |
      | rootNodeIdentifier       | 5387cb08-2aaf-44dc-a8a1-483497aa0a03 |      |
      | rootNodeTypeName         | Neos.ContentRepository:Root          |      |
    And I have the following NodeTypes configuration:
    """
    unstructured: []
    'Neos.ContentRepository.Testing:Document':
      aggregate: true
    """
    # We have to add another node since the root node has no aggregate to find the new parent of the translated node
    # Node /sites
    And the Event "Neos.ContentRepository:NodeAggregateWithNodeWasCreated" was published to stream "Neos.ContentRepository:ContentStream:c75ae6a2-7254-4d42-a31b-a629e264069d:NodeAggregate:c2037dc4-a20d-11e7-ba09-b3eb6d631979" with payload:
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

  Scenario: Move nodes in aggregate into another node aggregate
    # Node /sites/home (language=de)
    Given the Event "Neos.ContentRepository:NodeAggregateWithNodeWasCreated" was published to stream "Neos.ContentRepository:ContentStream:c75ae6a2-7254-4d42-a31b-a629e264069d:NodeAggregate:35411439-94d1-4bd4-8fac-0646856c6a1f" with payload:
      | Key                           | Value                                                                            | Type |
      | contentStreamIdentifier       | c75ae6a2-7254-4d42-a31b-a629e264069d                                             |      |
      | nodeAggregateIdentifier       | 35411439-94d1-4bd4-8fac-0646856c6a1f                                             |      |
      | nodeTypeName                  | Neos.ContentRepository.Testing:Document                                          |      |
      | dimensionSpacePoint           | {"coordinates":{"language":"de"}}                                                | json |
      | visibleDimensionSpacePoints   | {"points":[{"coordinates":{"language":"de"}},{"coordinates":{"language":"ch"}}]} | json |
      | nodeIdentifier                | 75106e9a-7dfb-4b48-8b7a-3c4ab2546b81                                             |      |
      | parentNodeIdentifier          | ead94f26-a20d-11e7-8ecc-43aabe596a03                                             |      |
      | nodeName                      | home                                                                             |      |
      | propertyDefaultValuesAndTypes | {}                                                                               | json |
    # Translated node /sites/home (language=en)
    And the Event "Neos.ContentRepository:NodeInAggregateWasTranslated" was published to stream "Neos.ContentRepository:ContentStream:c75ae6a2-7254-4d42-a31b-a629e264069d:NodeAggregate:35411439-94d1-4bd4-8fac-0646856c6a1f" with payload:
      | Key                             | Value                                          | Type |
      | contentStreamIdentifier         | c75ae6a2-7254-4d42-a31b-a629e264069d           |      |
      | sourceNodeIdentifier            | 75106e9a-7dfb-4b48-8b7a-3c4ab2546b81           |      |
      | destinationNodeIdentifier       | 01831e48-a20c-11e7-851a-dfef4f55c64c           |      |
      | destinationParentNodeIdentifier | ead94f26-a20d-11e7-8ecc-43aabe596a03           |      |
      | dimensionSpacePoint             | {"coordinates":{"language":"en"}}              | json |
      | visibleDimensionSpacePoints     | {"points":[{"coordinates":{"language":"en"}}]} | json |
    # Node /sites/contact (language=de)
    And the Event "Neos.ContentRepository:NodeAggregateWithNodeWasCreated" was published to stream "Neos.ContentRepository:ContentStream:c75ae6a2-7254-4d42-a31b-a629e264069d:NodeAggregate:4983ed6a-a2cc-11e7-9d06-fb695f94a9d8" with payload:
      | Key                           | Value                                                                            | Type |
      | contentStreamIdentifier       | c75ae6a2-7254-4d42-a31b-a629e264069d                                             |      |
      | nodeAggregateIdentifier       | 4983ed6a-a2cc-11e7-9d06-fb695f94a9d8                                             |      |
      | nodeTypeName                  | Neos.ContentRepository.Testing:Document                                          |      |
      | dimensionSpacePoint           | {"coordinates":{"language":"de"}}                                                | json |
      | visibleDimensionSpacePoints   | {"points":[{"coordinates":{"language":"de"}},{"coordinates":{"language":"ch"}}]} | json |
      | nodeIdentifier                | 5a98db56-a2cc-11e7-8b35-634eadb201dd                                             |      |
      | parentNodeIdentifier          | ead94f26-a20d-11e7-8ecc-43aabe596a03                                             |      |
      | nodeName                      | contact                                                                          |      |
      | propertyDefaultValuesAndTypes | {}                                                                               | json |
    # Translated node /sites/contact (language=en)
    And the Event "Neos.ContentRepository:NodeInAggregateWasTranslated" was published to stream "Neos.ContentRepository:ContentStream:c75ae6a2-7254-4d42-a31b-a629e264069d:NodeAggregate:4983ed6a-a2cc-11e7-9d06-fb695f94a9d8" with payload:
      | Key                             | Value                                          | Type |
      | contentStreamIdentifier         | c75ae6a2-7254-4d42-a31b-a629e264069d           |      |
      | sourceNodeIdentifier            | 5a98db56-a2cc-11e7-8b35-634eadb201dd           |      |
      | destinationNodeIdentifier       | 6d626626-a2cc-11e7-bef7-9fb027205d35           |      |
      | destinationParentNodeIdentifier | ead94f26-a20d-11e7-8ecc-43aabe596a03           |      |
      | dimensionSpacePoint             | {"coordinates":{"language":"en"}}              | json |
      | visibleDimensionSpacePoints     | {"points":[{"coordinates":{"language":"en"}}]} | json |

    When the command "MoveNodesInAggregate" is executed with payload:
      | Key                              | Value                                | Type |
      | contentStreamIdentifier          | c75ae6a2-7254-4d42-a31b-a629e264069d |      |
      | nodeAggregateIdentifier          | 4983ed6a-a2cc-11e7-9d06-fb695f94a9d8 |      |
      | referencePosition                | into                                 |      |
      | referenceNodeAggregateIdentifier | 35411439-94d1-4bd4-8fac-0646856c6a1f |      |


    Then I expect exactly 8 events to be published on stream with prefix "Neos.ContentRepository:ContentStream:c75ae6a2-7254-4d42-a31b-a629e264069d"
    And event at index 7 is of type "Neos.ContentRepository:NodesInAggregateWereMoved" with payload:
      | Key                                                        | Expected                             | AssertionType |
      | contentStreamIdentifier                                    | c75ae6a2-7254-4d42-a31b-a629e264069d |               |
      | nodeAggregateIdentifier                                    | 4983ed6a-a2cc-11e7-9d06-fb695f94a9d8 |               |
      | referencePosition                                          | into                                 |               |
      | referenceNodeAggregateIdentifier                           | 35411439-94d1-4bd4-8fac-0646856c6a1f |               |
      | nodesToReferenceNodes.5a98db56-a2cc-11e7-8b35-634eadb201dd | 75106e9a-7dfb-4b48-8b7a-3c4ab2546b81 |               |
      | nodesToReferenceNodes.6d626626-a2cc-11e7-bef7-9fb027205d35 | 01831e48-a20c-11e7-851a-dfef4f55c64c |               |
