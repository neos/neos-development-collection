@fixtures
Feature: Move node to a new parent / within the current parent before a sibling / to the end of the sibling list

  As a user of the CR I want to move a node to a new parent / within the current parent before a sibling / to the end of the sibling list,
  without affecting other nodes in the node aggregate.

  Background:
    Given I have the following content dimensions:
      | Identifier | Default | Values  | Generalizations |
      | market     | DE      | DE, CH  | CH->DE          |
      | language   | de      | de, gsw | gsw->de         |
    And the command "CreateRootWorkspace" is executed with payload:
      | Key                      | Value                                | Type |
      | workspaceName            | live                                 |      |
      | workspaceTitle           | Live                                 |      |
      | workspaceDescription     | The live workspace                   |      |
      | initiatingUserIdentifier | 00000000-0000-0000-0000-000000000000 |      |
      | contentStreamIdentifier  | cs-identifier                        | Uuid |
      | rootNodeIdentifier       | rn-identifier                        | Uuid |
      | rootNodeTypeName         | Neos.ContentRepository:Root          |      |
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository.Testing:Document': []
    'Neos.ContentRepository.Testing:Content':
      constraints:
        nodeTypes:
          '*': TRUE
          'Neos.ContentRepository.Testing:Document': FALSE
    'Neos.ContentRepository.Testing:DocumentWithAutoCreatedChildNode':
      childNodes:
        autocreated:
          type: 'Neos.ContentRepository.Testing:Content'
          constraints:
            nodeTypes:
              '*': TRUE
              'Neos.ContentRepository.Testing:Content': FALSE
    """

  #Try to move a root node:
  #  Root nodes cannot be moved via command because they don't have node aggregate identifiers.
  #  Thus, we don't have to test for thrown exceptions here

  Scenario: Try to move a node in a non-existing dimension space point:
    When the command "MoveNode" is executed with payload and exceptions are caught:
      | Key                                         | Value                                     | Type                |
      | contentStreamIdentifier                     | cs-identifier                             | Uuid                |
      | dimensionSpacePoint                         | {"market": "nope", "language": "neither"} | DimensionSpacePoint |
      | nodeAggregateIdentifier                     | na-identifier                             | Uuid                |
      | newParentNodeAggregateIdentifier            |                                           | null                |
      | newSucceedingSiblingNodeAggregateIdentifier |                                           | null                |
      | relationDistributionStrategy                | scatter                                   |                     |
    Then the last command should have thrown an exception of type "Exception"

  Scenario: Try to move a non-existing node
    When the command "MoveNode" is executed with payload and exceptions are caught:
      | Key                                         | Value                              | Type                |
      | contentStreamIdentifier                     | cs-identifier                      | Uuid                |
      | dimensionSpacePoint                         | {"market": "DE", "language": "de"} | DimensionSpacePoint |
      | nodeAggregateIdentifier                     | na-identifier                      | Uuid                |
      | newParentNodeAggregateIdentifier            |                                    | null                |
      | newSucceedingSiblingNodeAggregateIdentifier |                                    | null                |
      | relationDistributionStrategy                | scatter                            |                     |
    Then the last command should have thrown an exception of type "NodeAggregateNotFound"

  Scenario: Try to move existing node to a non-existing parent
    Given the Event "Neos.ContentRepository:NodeAggregateWithNodeWasCreated" was published to stream "Neos.ContentRepository:ContentStream:cs-identifier:NodeAggregate:na-identifier" with payload:
      | Key                           | Value                                                                                                                                                                                                                         | Type                   |
      | contentStreamIdentifier       | cs-identifier                                                                                                                                                                                                                 | Uuid                   |
      | nodeAggregateIdentifier       | na-identifier                                                                                                                                                                                                                 | Uuid                   |
      | nodeTypeName                  | Neos.ContentRepository.Testing:Document                                                                                                                                                                                       |                        |
      | dimensionSpacePoint           | {"market": "DE", "language": "de"}                                                                                                                                                                                            | DimensionSpacePoint    |
      | visibleDimensionSpacePoints   | {"points":[{"coordinates":{"market": "DE", "language": "de"}}, {"coordinates":{"market": "DE", "language": "gsw"}}, {"coordinates":{"market": "CH", "language": "de"}}, {"coordinates":{"market": "CH", "language": "gsw"}}]} | DimensionSpacePointSet |
      | nodeIdentifier                | doc-identifier                                                                                                                                                                                                                | Uuid                   |
      | parentNodeIdentifier          | rn-identifier                                                                                                                                                                                                                 | Uuid                   |
      | nodeName                      | document                                                                                                                                                                                                                      |                        |
      | propertyDefaultValuesAndTypes | {}                                                                                                                                                                                                                            | json                   |
    When the command "MoveNode" is executed with payload and exceptions are caught:
      | Key                                         | Value                              | Type                |
      | contentStreamIdentifier                     | cs-identifier                      | Uuid                |
      | dimensionSpacePoint                         | {"market": "DE", "language": "de"} | DimensionSpacePoint |
      | nodeAggregateIdentifier                     | na-identifier                      | Uuid                |
      | newParentNodeAggregateIdentifier            | non-existing-parent-identifier     | Uuid                |
      | newSucceedingSiblingNodeAggregateIdentifier |                                    | null                |
      | relationDistributionStrategy                | scatter                            |                     |
    Then the last command should have thrown an exception of type "NodeAggregateNotFound"

  Scenario: Try to move a node to a parent that already has a child node of the same name
    Given the Event "Neos.ContentRepository:NodeAggregateWithNodeWasCreated" was published to stream "Neos.ContentRepository:ContentStream:c75ae6a2-7254-4d42-a31b-a629e264069d:NodeAggregate:413f8404-fae9-448b-8170-b6a4bea74213" with payload:
      | Key                           | Value                                                                                                                                                                                                                         | Type                   |
      | contentStreamIdentifier       | cs-identifier                                                                                                                                                                                                                 | Uuid                   |
      | nodeAggregateIdentifier       | doc-agg-identifier                                                                                                                                                                                                            | Uuid                   |
      | nodeTypeName                  | Neos.ContentRepository.Testing:Document                                                                                                                                                                                       |                        |
      | dimensionSpacePoint           | {"market": "DE", "language": "de"}                                                                                                                                                                                            | DimensionSpacePoint    |
      | visibleDimensionSpacePoints   | {"points":[{"coordinates":{"market": "DE", "language": "de"}}, {"coordinates":{"market": "DE", "language": "gsw"}}, {"coordinates":{"market": "CH", "language": "de"}}, {"coordinates":{"market": "CH", "language": "gsw"}}]} | DimensionSpacePointSet |
      | nodeIdentifier                | doc-identifier                                                                                                                                                                                                                | Uuid                   |
      | parentNodeIdentifier          | rn-identifier                                                                                                                                                                                                                 | Uuid                   |
      | nodeName                      | document                                                                                                                                                                                                                      |                        |
      | propertyDefaultValuesAndTypes | {}                                                                                                                                                                                                                            | json                   |
    And the Event "Neos.ContentRepository:NodeAggregateWithNodeWasCreated" was published to stream "Neos.ContentRepository:ContentStream:c75ae6a2-7254-4d42-a31b-a629e264069d:NodeAggregate:afbf2dec-cb77-4f7b-b252-0363e6a38770" with payload:
      | Key                           | Value                                                                                                                                                                                                                         | Type                   |
      | contentStreamIdentifier       | cs-identifier                                                                                                                                                                                                                 | Uuid                   |
      | nodeAggregateIdentifier       | cdoc-agg-identifier                                                                                                                                                                                                           | Uuid                   |
      | nodeTypeName                  | Neos.ContentRepository.Testing:Document                                                                                                                                                                                       |                        |
      | dimensionSpacePoint           | {"market": "DE", "language": "de"}                                                                                                                                                                                            | DimensionSpacePoint    |
      | visibleDimensionSpacePoints   | {"points":[{"coordinates":{"market": "DE", "language": "de"}}, {"coordinates":{"market": "DE", "language": "gsw"}}, {"coordinates":{"market": "CH", "language": "de"}}, {"coordinates":{"market": "CH", "language": "gsw"}}]} | DimensionSpacePointSet |
      | nodeIdentifier                | cdoc-identifier                                                                                                                                                                                                               | Uuid                   |
      | parentNodeIdentifier          | doc-identifier                                                                                                                                                                                                                | Uuid                   |
      | nodeName                      | document                                                                                                                                                                                                                      |                        |
      | propertyDefaultValuesAndTypes | {}                                                                                                                                                                                                                            | json                   |
    And the Event "Neos.ContentRepository:NodeAggregateWithNodeWasCreated" was published to stream "Neos.ContentRepository:ContentStream:c75ae6a2-7254-4d42-a31b-a629e264069d:NodeAggregate:6ce151a5-a4e1-4070-b4f1-95ba108f1db9" with payload:
      | Key                           | Value                                                                                                                                                                                                                         | Type                   |
      | contentStreamIdentifier       | cs-identifier                                                                                                                                                                                                                 | Uuid                   |
      | nodeAggregateIdentifier       | gcdoc-agg-identifier                                                                                                                                                                                                          | Uuid                   |
      | nodeTypeName                  | Neos.ContentRepository.Testing:Document                                                                                                                                                                                       |                        |
      | dimensionSpacePoint           | {"market": "DE", "language": "de"}                                                                                                                                                                                            | DimensionSpacePoint    |
      | visibleDimensionSpacePoints   | {"points":[{"coordinates":{"market": "DE", "language": "de"}}, {"coordinates":{"market": "DE", "language": "gsw"}}, {"coordinates":{"market": "CH", "language": "de"}}, {"coordinates":{"market": "CH", "language": "gsw"}}]} | DimensionSpacePointSet |
      | nodeIdentifier                | gcdoc-identifier                                                                                                                                                                                                              | Uuid                   |
      | parentNodeIdentifier          | cdoc-identifier                                                                                                                                                                                                               | Uuid                   |
      | nodeName                      | document                                                                                                                                                                                                                      |                        |
      | propertyDefaultValuesAndTypes | {}                                                                                                                                                                                                                            | json                   |
    When the command "MoveNode" is executed with payload and exceptions are caught:
      | Key                                         | Value                              | Type                |
      | contentStreamIdentifier                     | cs-identifier                      | Uuid                |
      | dimensionSpacePoint                         | {"market": "DE", "language": "de"} | DimensionSpacePoint |
      | nodeAggregateIdentifier                     | gcdoc-agg-identifier               | Uuid                |
      | newParentNodeAggregateIdentifier            | doc-agg-identifier                 | Uuid                |
      | newSucceedingSiblingNodeAggregateIdentifier |                                    | null                |
      | relationDistributionStrategy                | scatter                            |                     |
    Then the last command should have thrown an exception of type "NodeExistsException"

  Scenario: Try to move a node to a parent whose node type does not allow child nodes of the node's type
    Given the Event "Neos.ContentRepository:NodeAggregateWithNodeWasCreated" was published to stream "Neos.ContentRepository:ContentStream:c75ae6a2-7254-4d42-a31b-a629e264069d:NodeAggregate:c8973421-c835-44dc-9e90-933c0672b096" with payload:
      | Key                           | Value                                                                                                                                                                                                                         | Type                   |
      | contentStreamIdentifier       | cs-identifier                                                                                                                                                                                                                 | Uuid                   |
      | nodeAggregateIdentifier       | doc-agg-identifier                                                                                                                                                                                                            | Uuid                   |
      | nodeTypeName                  | Neos.ContentRepository.Testing:Document                                                                                                                                                                                       |                        |
      | dimensionSpacePoint           | {"market": "DE", "language": "de"}                                                                                                                                                                                            | DimensionSpacePoint    |
      | visibleDimensionSpacePoints   | {"points":[{"coordinates":{"market": "DE", "language": "de"}}, {"coordinates":{"market": "DE", "language": "gsw"}}, {"coordinates":{"market": "CH", "language": "de"}}, {"coordinates":{"market": "CH", "language": "gsw"}}]} | DimensionSpacePointSet |
      | nodeIdentifier                | doc-identifier                                                                                                                                                                                                                | Uuid                   |
      | parentNodeIdentifier          | rn-identifier                                                                                                                                                                                                                 | Uuid                   |
      | nodeName                      | document                                                                                                                                                                                                                      |                        |
      | propertyDefaultValuesAndTypes | {}                                                                                                                                                                                                                            | json                   |
    And the Event "Neos.ContentRepository:NodeAggregateWithNodeWasCreated" was published to stream "Neos.ContentRepository:ContentStream:c75ae6a2-7254-4d42-a31b-a629e264069d:NodeAggregate:01a87ee4-cfc6-443b-9809-2568910b7e8f" with payload:
      | Key                           | Value                                                                                                                                                                                                                         | Type                   |
      | contentStreamIdentifier       | cs-identifier                                                                                                                                                                                                                 | Uuid                   |
      | nodeAggregateIdentifier       | cdoc-agg-identifier                                                                                                                                                                                                           | Uuid                   |
      | nodeTypeName                  | Neos.ContentRepository.Testing:Document                                                                                                                                                                                       |                        |
      | dimensionSpacePoint           | {"market": "DE", "language": "de"}                                                                                                                                                                                            | DimensionSpacePoint    |
      | visibleDimensionSpacePoints   | {"points":[{"coordinates":{"market": "DE", "language": "de"}}, {"coordinates":{"market": "DE", "language": "gsw"}}, {"coordinates":{"market": "CH", "language": "de"}}, {"coordinates":{"market": "CH", "language": "gsw"}}]} | DimensionSpacePointSet |
      | nodeIdentifier                | cdoc-identifier                                                                                                                                                                                                               | Uuid                   |
      | parentNodeIdentifier          | doc-identifier                                                                                                                                                                                                                | Uuid                   |
      | nodeName                      | document                                                                                                                                                                                                                      |                        |
      | propertyDefaultValuesAndTypes | {}                                                                                                                                                                                                                            | json                   |
    And the Event "Neos.ContentRepository:NodeAggregateWithNodeWasCreated" was published to stream "Neos.ContentRepository:ContentStream:c75ae6a2-7254-4d42-a31b-a629e264069d:NodeAggregate:a5c593f5-944b-47b0-b2dd-e399b85a6d85" with payload:
      | Key                           | Value                                                                                                                                                                                                                         | Type                   |
      | contentStreamIdentifier       | cs-identifier                                                                                                                                                                                                                 | Uuid                   |
      | nodeAggregateIdentifier       | content-agg-identifier                                                                                                                                                                                                        | Uuid                   |
      | nodeTypeName                  | Neos.ContentRepository.Testing:Content                                                                                                                                                                                        |                        |
      | dimensionSpacePoint           | {"market": "DE", "language": "de"}                                                                                                                                                                                            | DimensionSpacePoint    |
      | visibleDimensionSpacePoints   | {"points":[{"coordinates":{"market": "DE", "language": "de"}}, {"coordinates":{"market": "DE", "language": "gsw"}}, {"coordinates":{"market": "CH", "language": "de"}}, {"coordinates":{"market": "CH", "language": "gsw"}}]} | DimensionSpacePointSet |
      | nodeIdentifier                | content-identifier                                                                                                                                                                                                            | Uuid                   |
      | parentNodeIdentifier          | doc-identifier                                                                                                                                                                                                                | Uuid                   |
      | nodeName                      | content                                                                                                                                                                                                                       |                        |
      | propertyDefaultValuesAndTypes | {}                                                                                                                                                                                                                            | json                   |
    When the command "MoveNode" is executed with payload and exceptions are caught:
      | Key                                         | Value                              | Type                         |
      | contentStreamIdentifier                     | cs-identifier                      | Uuid                         |
      | dimensionSpacePoint                         | {"market": "DE", "language": "de"} | DimensionSpacePoint          |
      | nodeAggregateIdentifier                     | cdoc-agg-identifier                | Uuid                         |
      | newParentNodeAggregateIdentifier            | content-agg-identifier             | Uuid                         |
      | newSucceedingSiblingNodeAggregateIdentifier |                                    | null                         |
      | relationDistributionStrategy                | scatter                            | RelationDistributionStrategy |
    Then the last command should have thrown an exception of type "NodeConstraintException"

  Scenario: Try to move a node to a parent whose parent's node type does not allow grand child nodes of the node's type
    Given the Event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                                                                                                                                                                                         | Type                   |
      | contentStreamIdentifier       | cs-identifier                                                                                                                                                                                                                 | Uuid                   |
      | nodeAggregateIdentifier       | doc-agg-identifier                                                                                                                                                                                                            | Uuid                   |
      | nodeTypeName                  | Neos.ContentRepository.Testing:DocumentWithAutoCreatedChildNode                                                                                                                                                               |                        |
      | dimensionSpacePoint           | {"market": "DE", "language": "de"}                                                                                                                                                                                            | DimensionSpacePoint    |
      | visibleDimensionSpacePoints   | {"points":[{"coordinates":{"market": "DE", "language": "de"}}, {"coordinates":{"market": "DE", "language": "gsw"}}, {"coordinates":{"market": "CH", "language": "de"}}, {"coordinates":{"market": "CH", "language": "gsw"}}]} | DimensionSpacePointSet |
      | nodeIdentifier                | doc-identifier                                                                                                                                                                                                                | Uuid                   |
      | parentNodeIdentifier          | rn-identifier                                                                                                                                                                                                                 | Uuid                   |
      | nodeName                      | document                                                                                                                                                                                                                      |                        |
      | propertyDefaultValuesAndTypes | {}                                                                                                                                                                                                                            | json                   |
    And the Event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                                                                                                                                                                                         | Type                   |
      | contentStreamIdentifier       | cs-identifier                                                                                                                                                                                                                 | Uuid                   |
      | nodeAggregateIdentifier       | autoc-agg-identifier                                                                                                                                                                                                          | Uuid                   |
      | nodeTypeName                  | Neos.ContentRepository.Testing:Content                                                                                                                                                                                        |                        |
      | dimensionSpacePoint           | {"market": "DE", "language": "de"}                                                                                                                                                                                            | DimensionSpacePoint    |
      | visibleDimensionSpacePoints   | {"points":[{"coordinates":{"market": "DE", "language": "de"}}, {"coordinates":{"market": "DE", "language": "gsw"}}, {"coordinates":{"market": "CH", "language": "de"}}, {"coordinates":{"market": "CH", "language": "gsw"}}]} | DimensionSpacePointSet |
      | nodeIdentifier                | autoc-identifier                                                                                                                                                                                                              | Uuid                   |
      | parentNodeIdentifier          | doc-identifier                                                                                                                                                                                                                | Uuid                   |
      | nodeName                      | autocreated                                                                                                                                                                                                                   |                        |
      | propertyDefaultValuesAndTypes | {}                                                                                                                                                                                                                            | json                   |
    And the Event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                                                                                                                                                                                         | Type                   |
      | contentStreamIdentifier       | cs-identifier                                                                                                                                                                                                                 | Uuid                   |
      | nodeAggregateIdentifier       | c-agg-identifier                                                                                                                                                                                                              | Uuid                   |
      | nodeTypeName                  | Neos.ContentRepository.Testing:Content                                                                                                                                                                                        |                        |
      | dimensionSpacePoint           | {"market": "DE", "language": "de"}                                                                                                                                                                                            | DimensionSpacePoint    |
      | visibleDimensionSpacePoints   | {"points":[{"coordinates":{"market": "DE", "language": "de"}}, {"coordinates":{"market": "DE", "language": "gsw"}}, {"coordinates":{"market": "CH", "language": "de"}}, {"coordinates":{"market": "CH", "language": "gsw"}}]} | DimensionSpacePointSet |
      | nodeIdentifier                | c-identifier                                                                                                                                                                                                                  | Uuid                   |
      | parentNodeIdentifier          | doc-identifier                                                                                                                                                                                                                | Uuid                   |
      | nodeName                      | content                                                                                                                                                                                                                       |                        |
      | propertyDefaultValuesAndTypes | {}                                                                                                                                                                                                                            | json                   |
    When the command "MoveNode" is executed with payload and exceptions are caught:
      | Key                                         | Value                              | Type                |
      | contentStreamIdentifier                     | cs-identifier                      | Uuid                |
      | dimensionSpacePoint                         | {"market": "DE", "language": "de"} | DimensionSpacePoint |
      | nodeAggregateIdentifier                     | c-agg-identifier                   | Uuid                |
      | newParentNodeAggregateIdentifier            | autoc-agg-identifier               | Uuid                |
      | newSucceedingSiblingNodeAggregateIdentifier |                                    | null                |
      | relationDistributionStrategy                | scatter                            |                     |
    Then the last command should have thrown an exception of type "NodeConstraintException"

  Scenario: Try to move existing node to a non-existing succeeding sibling
    Given the Event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                                                                                                                                                                                         | Type                   |
      | contentStreamIdentifier       | cs-identifier                                                                                                                                                                                                                 | Uuid                   |
      | nodeAggregateIdentifier       | na-identifier                                                                                                                                                                                                                 | Uuid                   |
      | nodeTypeName                  | Neos.ContentRepository.Testing:Document                                                                                                                                                                                       |                        |
      | dimensionSpacePoint           | {"market": "DE", "language": "de"}                                                                                                                                                                                            | DimensionSpacePoint    |
      | visibleDimensionSpacePoints   | {"points":[{"coordinates":{"market": "DE", "language": "de"}}, {"coordinates":{"market": "DE", "language": "gsw"}}, {"coordinates":{"market": "CH", "language": "de"}}, {"coordinates":{"market": "CH", "language": "gsw"}}]} | DimensionSpacePointSet |
      | nodeIdentifier                | n-identifier                                                                                                                                                                                                                  | Uuid                   |
      | parentNodeIdentifier          | rn-identifier                                                                                                                                                                                                                 | Uuid                   |
      | nodeName                      | document                                                                                                                                                                                                                      |                        |
      | propertyDefaultValuesAndTypes | {}                                                                                                                                                                                                                            | json                   |
    When the command "MoveNode" is executed with payload and exceptions are caught:
      | Key                                         | Value                              | Type                |
      | contentStreamIdentifier                     | cs-identifier                      | Uuid                |
      | dimensionSpacePoint                         | {"market": "DE", "language": "de"} | DimensionSpacePoint |
      | nodeAggregateIdentifier                     | na-identifier                      | Uuid                |
      | newParentNodeAggregateIdentifier            |                                    | null                |
      | newSucceedingSiblingNodeAggregateIdentifier | nonexistent-agg-identifier         | Uuid                |
      | relationDistributionStrategy                | scatter                            |                     |
    Then the last command should have thrown an exception of type "NodeAggregateNotFound"

  Scenario: Try to move an autogenerated child node to a new parent
    Given the Event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                                                                                                                                                                                         | Type                   |
      | contentStreamIdentifier       | cs-identifier                                                                                                                                                                                                                 | Uuid                   |
      | nodeAggregateIdentifier       | odoc-agg-identifier                                                                                                                                                                                                           | Uuid                   |
      | nodeTypeName                  | Neos.ContentRepository.Testing:Document                                                                                                                                                                                       |                        |
      | dimensionSpacePoint           | {"market": "DE", "language": "de"}                                                                                                                                                                                            | DimensionSpacePoint    |
      | visibleDimensionSpacePoints   | {"points":[{"coordinates":{"market": "DE", "language": "de"}}, {"coordinates":{"market": "DE", "language": "gsw"}}, {"coordinates":{"market": "CH", "language": "de"}}, {"coordinates":{"market": "CH", "language": "gsw"}}]} | DimensionSpacePointSet |
      | nodeIdentifier                | odoc-identifier                                                                                                                                                                                                               | Uuid                   |
      | parentNodeIdentifier          | rn-identifier                                                                                                                                                                                                                 | Uuid                   |
      | nodeName                      | other-document                                                                                                                                                                                                                |                        |
      | propertyDefaultValuesAndTypes | {}                                                                                                                                                                                                                            | json                   |
    Given the Event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                                                                                                                                                                                         | Type                   |
      | contentStreamIdentifier       | cs-identifier                                                                                                                                                                                                                 | Uuid                   |
      | nodeAggregateIdentifier       | doc-agg-identifier                                                                                                                                                                                                            | Uuid                   |
      | nodeTypeName                  | Neos.ContentRepository.Testing:DocumentWithAutoCreatedChildNode                                                                                                                                                               |                        |
      | dimensionSpacePoint           | {"market": "DE", "language": "de"}                                                                                                                                                                                            | DimensionSpacePoint    |
      | visibleDimensionSpacePoints   | {"points":[{"coordinates":{"market": "DE", "language": "de"}}, {"coordinates":{"market": "DE", "language": "gsw"}}, {"coordinates":{"market": "CH", "language": "de"}}, {"coordinates":{"market": "CH", "language": "gsw"}}]} | DimensionSpacePointSet |
      | nodeIdentifier                | doc-identifier                                                                                                                                                                                                                | Uuid                   |
      | parentNodeIdentifier          | rn-identifier                                                                                                                                                                                                                 | Uuid                   |
      | nodeName                      | document                                                                                                                                                                                                                      |                        |
      | propertyDefaultValuesAndTypes | {}                                                                                                                                                                                                                            | json                   |
    And the Event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                                                                                                                                                                                         | Type                   |
      | contentStreamIdentifier       | cs-identifier                                                                                                                                                                                                                 | Uuid                   |
      | nodeAggregateIdentifier       | autoc-agg-identifier                                                                                                                                                                                                          | Uuid                   |
      | nodeTypeName                  | Neos.ContentRepository.Testing:Content                                                                                                                                                                                        |                        |
      | dimensionSpacePoint           | {"market": "DE", "language": "de"}                                                                                                                                                                                            | DimensionSpacePoint    |
      | visibleDimensionSpacePoints   | {"points":[{"coordinates":{"market": "DE", "language": "de"}}, {"coordinates":{"market": "DE", "language": "gsw"}}, {"coordinates":{"market": "CH", "language": "de"}}, {"coordinates":{"market": "CH", "language": "gsw"}}]} | DimensionSpacePointSet |
      | nodeIdentifier                | autoc-identifier                                                                                                                                                                                                              | Uuid                   |
      | parentNodeIdentifier          | doc-identifier                                                                                                                                                                                                                | Uuid                   |
      | nodeName                      | autocreated                                                                                                                                                                                                                   |                        |
      | propertyDefaultValuesAndTypes | {}                                                                                                                                                                                                                            | json                   |
    When the command "MoveNode" is executed with payload and exceptions are caught:
      | Key                                         | Value                              | Type                |
      | contentStreamIdentifier                     | cs-identifier                      | Uuid                |
      | dimensionSpacePoint                         | {"market": "DE", "language": "de"} | DimensionSpacePoint |
      | nodeAggregateIdentifier                     | autoc-agg-identifier               | Uuid                |
      | newParentNodeAggregateIdentifier            | odoc-agg-identifier                | Uuid                |
      | newSucceedingSiblingNodeAggregateIdentifier |                                    | null                |
      | relationDistributionStrategy                | scatter                            |                     |
    Then the last command should have thrown an exception of type "NodeConstraintException"