@contentrepository @adapters=DoctrineDBAL
Feature: Move node to a new parent / within the current parent before a sibling / to the end of the sibling list

  As a user of the CR I want to move a node to a new parent / within the current parent before a sibling / to the end of the sibling list,
  without affecting other nodes in the node aggregate.

  These are the base test cases for the NodeAggregateCommandHandler to block invalid commands

  Content Structure:
    - lady-eleonode-rootford (Neos.ContentRepository:Root)
      - sir-david-nodenborough (Neos.ContentRepository.Testing:DocumentWithTetheredChildNode)
        - "tethered" nodewyn-tetherton (Neos.ContentRepository.Testing:Content)
        - sir-nodeward-nodington-iii (Neos.ContentRepository.Testing:Document)

  Background:
    Given I have the following content dimensions:
      | Identifier | Values      | Generalizations |
      | market     | DE, CH      | CH->DE          |
      | language   | de, gsw, fr | gsw->de         |
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Testing:Document': []
    'Neos.ContentRepository.Testing:Content':
      constraints:
        nodeTypes:
          '*': true
          'Neos.ContentRepository.Testing:Document': false
    'Neos.ContentRepository.Testing:DocumentWithTetheredChildNode':
      childNodes:
        tethered:
          type: 'Neos.ContentRepository.Testing:Content'
          constraints:
            nodeTypes:
              '*': true
              'Neos.ContentRepository.Testing:Content': false
    """
    And the command CreateRootWorkspace is executed with payload:
      | Key                        | Value                                  |
      | workspaceName              | "live"                                 |
      | workspaceTitle             | "Live"                                 |
      | workspaceDescription       | "The live workspace"                   |
      | newContentStreamId | "cs-identifier"                        |
    And the graph projection is fully up to date
    And the event RootNodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                                                                                                                   |
      | contentStreamId     | "cs-identifier"                                                                                                                         |
      | nodeAggregateId     | "lady-eleonode-rootford"                                                                                                                |
      | nodeTypeName                | "Neos.ContentRepository:Root"                                                                                                           |
      | coveredDimensionSpacePoints | [{"market":"DE", "language":"de"},{"market":"DE", "language":"gsw"},{"market":"CH", "language":"de"},{"market":"CH", "language":"gsw"}] |
      | nodeAggregateClassification | "root"                                                                                                                                  |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                                                                                                   |
      | contentStreamId       | "cs-identifier"                                                                                                                         |
      | nodeAggregateId       | "sir-david-nodenborough"                                                                                                                |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:DocumentWithTetheredChildNode"                                                                          |
      | originDimensionSpacePoint     | {"market":"DE", "language":"de"}                                                                                                        |
      | coveredDimensionSpacePoints   | [{"market":"DE", "language":"de"},{"market":"DE", "language":"gsw"},{"market":"CH", "language":"de"},{"market":"CH", "language":"gsw"}] |
      | parentNodeAggregateId | "lady-eleonode-rootford"                                                                                                                |
      | nodeName                      | "document"                                                                                                                              |
      | nodeAggregateClassification   | "regular"                                                                                                                               |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                                                                                                   |
      | contentStreamId       | "cs-identifier"                                                                                                                         |
      | nodeAggregateId       | "nodewyn-tetherton"                                                                                                                     |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Content"                                                                                                |
      | originDimensionSpacePoint     | {"market":"DE", "language":"de"}                                                                                                        |
      | coveredDimensionSpacePoints   | [{"market":"DE", "language":"de"},{"market":"DE", "language":"gsw"},{"market":"CH", "language":"de"},{"market":"CH", "language":"gsw"}] |
      | parentNodeAggregateId | "sir-david-nodenborough"                                                                                                                |
      | nodeName                      | "tethered"                                                                                                                              |
      | nodeAggregateClassification   | "tethered"                                                                                                                              |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                                                                                                   |
      | contentStreamId       | "cs-identifier"                                                                                                                         |
      | nodeAggregateId       | "sir-nodeward-nodington-iii"                                                                                                            |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document"                                                                                               |
      | originDimensionSpacePoint     | {"market":"DE", "language":"de"}                                                                                                        |
      | coveredDimensionSpacePoints   | [{"market":"DE", "language":"de"},{"market":"DE", "language":"gsw"},{"market":"CH", "language":"de"},{"market":"CH", "language":"gsw"}] |
      | parentNodeAggregateId | "sir-david-nodenborough"                                                                                                                |
      | nodeName                      | "esquire"                                                                                                                               |
      | nodeAggregateClassification   | "regular"                                                                                                                               |
    And the graph projection is fully up to date

  Scenario: Try to move a node in a non-existing content stream:
    When the command MoveNodeAggregate is executed with payload and exceptions are caught:
      | Key                          | Value                              |
      | contentStreamId      | "non-existing"                     |
      | nodeAggregateId      | "sir-david-nodenborough"           |
      | dimensionSpacePoint          | {"market": "DE", "language": "de"} |
      | relationDistributionStrategy | "scatter"                          |
    Then the last command should have thrown an exception of type "ContentStreamDoesNotExistYet"

  Scenario: Try to move a node of a non-existing node aggregate:
    When the command MoveNodeAggregate is executed with payload and exceptions are caught:
      | Key                          | Value                              |
      | contentStreamId      | "cs-identifier"                    |
      | nodeAggregateId      | "i-do-not-exist"                   |
      | dimensionSpacePoint          | {"market": "DE", "language": "de"} |
      | relationDistributionStrategy | "scatter"                          |
    Then the last command should have thrown an exception of type "NodeAggregateCurrentlyDoesNotExist"

  Scenario: Try to move a node of a root node aggregate:
    When the command MoveNodeAggregate is executed with payload and exceptions are caught:
      | Key                          | Value                            |
      | contentStreamId      | "cs-identifier"                  |
      | nodeAggregateId      | "lady-eleonode-rootford"         |
      | dimensionSpacePoint          | {"market":"DE", "language":"de"} |
      | relationDistributionStrategy | "scatter"                        |
    Then the last command should have thrown an exception of type "NodeAggregateIsRoot"

  Scenario: Try to move a node of a tethered node aggregate:
    When the command MoveNodeAggregate is executed with payload and exceptions are caught:
      | Key                          | Value                              |
      | contentStreamId      | "cs-identifier"                    |
      | nodeAggregateId      | "nodewyn-tetherton"                |
      | dimensionSpacePoint          | {"market": "DE", "language": "de"} |
      | relationDistributionStrategy | "scatter"                          |
    Then the last command should have thrown an exception of type "NodeAggregateIsTethered"

  Scenario: Try to move a node in a non-existing dimension space point:
    When the command MoveNodeAggregate is executed with payload and exceptions are caught:
      | Key                          | Value                                     |
      | contentStreamId      | "cs-identifier"                           |
      | nodeAggregateId      | "sir-david-nodenborough"                  |
      | dimensionSpacePoint          | {"market": "nope", "language": "neither"} |
      | relationDistributionStrategy | "scatter"                                 |
    Then the last command should have thrown an exception of type "DimensionSpacePointNotFound"

  Scenario: Try to move a node in a dimension space point the aggregate does not cover
    When the command MoveNodeAggregate is executed with payload and exceptions are caught:
      | Key                          | Value                              |
      | contentStreamId      | "cs-identifier"                    |
      | nodeAggregateId      | "sir-david-nodenborough"           |
      | dimensionSpacePoint          | {"market": "DE", "language": "fr"} |
      | relationDistributionStrategy | "scatter"                          |
    Then the last command should have thrown an exception of type "NodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint"

  Scenario: Try to move existing node to a non-existing parent
    When the command MoveNodeAggregate is executed with payload and exceptions are caught:
      | Key                              | Value                              |
      | contentStreamId          | "cs-identifier"                    |
      | dimensionSpacePoint              | {"market": "DE", "language": "de"} |
      | nodeAggregateId          | "sir-david-nodenborough"           |
      | newParentNodeAggregateId | "non-existing-parent-identifier"   |
      | relationDistributionStrategy     | "scatter"                          |
    Then the last command should have thrown an exception of type "NodeAggregateCurrentlyDoesNotExist"

  Scenario: Try to move a node to a parent that already has a child node of the same name
    Given the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                                                                                                              |
      | contentStreamId       | "cs-identifier"                                                                                                                                    |
      | nodeAggregateId       | "nody-mc-nodeface"                                                                                                                                 |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document"                                                                                                          |
      | originDimensionSpacePoint     | {"market": "DE", "language": "de"}                                                                                                                 |
      | coveredDimensionSpacePoints   | [{"market": "DE", "language": "de"}, {"market": "DE", "language": "gsw"}, {"market": "CH", "language": "de"}, {"market": "CH", "language": "gsw"}] |
      | parentNodeAggregateId | "sir-david-nodenborough"                                                                                                                           |
      | nodeName                      | "document"                                                                                                                                         |
      | nodeAggregateClassification   | "regular"                                                                                                                                          |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload and exceptions are caught:
      | Key                              | Value                              |
      | contentStreamId          | "cs-identifier"                    |
      | dimensionSpacePoint              | {"market": "DE", "language": "de"} |
      | nodeAggregateId          | "nody-mc-nodeface"                 |
      | newParentNodeAggregateId | "lady-eleonode-rootford"           |
      | relationDistributionStrategy     | "scatter"                          |
    Then the last command should have thrown an exception of type "NodeNameIsAlreadyCovered"

  Scenario: Move a node that has no name
    Given the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                                                                                                              |
      | contentStreamId       | "cs-identifier"                                                                                                                                    |
      | nodeAggregateId       | "nody-mc-nodeface"                                                                                                                                 |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document"                                                                                                          |
      | originDimensionSpacePoint     | {"market": "DE", "language": "de"}                                                                                                                 |
      | coveredDimensionSpacePoints   | [{"market": "DE", "language": "de"}, {"market": "DE", "language": "gsw"}, {"market": "CH", "language": "de"}, {"market": "CH", "language": "gsw"}] |
      | parentNodeAggregateId | "sir-david-nodenborough"                                                                                                                           |
      | nodeAggregateClassification   | "regular"                                                                                                                                          |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload:
      | Key                              | Value                              |
      | contentStreamId          | "cs-identifier"                    |
      | dimensionSpacePoint              | {"market": "DE", "language": "de"} |
      | nodeAggregateId          | "nody-mc-nodeface"                 |
      | newParentNodeAggregateId | "lady-eleonode-rootford"           |
      | relationDistributionStrategy     | "scatter"                          |
    And the graph projection is fully up to date
    When I am in content stream "cs-identifier" and dimension space point {"market": "DE", "language": "de"}
    And I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"market":"DE","language":"de"}


  Scenario: Try to move a node to a parent whose node type does not allow child nodes of the node's type
    Given the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                                                                                                              |
      | contentStreamId       | "cs-identifier"                                                                                                                                    |
      | nodeAggregateId       | "nody-mc-nodeface"                                                                                                                                 |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document"                                                                                                          |
      | originDimensionSpacePoint     | {"market": "DE", "language": "de"}                                                                                                                 |
      | coveredDimensionSpacePoints   | [{"market": "DE", "language": "de"}, {"market": "DE", "language": "gsw"}, {"market": "CH", "language": "de"}, {"market": "CH", "language": "gsw"}] |
      | parentNodeAggregateId | "lady-eleonode-rootford"                                                                                                                           |
      | nodeName                      | "other-document"                                                                                                                                   |
      | nodeAggregateClassification   | "regular"                                                                                                                                          |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload and exceptions are caught:
      | Key                              | Value                              |
      | contentStreamId          | "cs-identifier"                    |
      | dimensionSpacePoint              | {"market": "DE", "language": "de"} |
      | nodeAggregateId          | "nody-mc-nodeface"                 |
      | newParentNodeAggregateId | "nodewyn-tetherton"                |
      | relationDistributionStrategy     | "scatter"                          |
    Then the last command should have thrown an exception of type "NodeConstraintException"

  Scenario: Try to move a node to a parent whose parent's node type does not allow grand child nodes of the node's type
    Given the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                                                                                                              |
      | contentStreamId       | "cs-identifier"                                                                                                                                    |
      | nodeAggregateId       | "nody-mc-nodeface"                                                                                                                                 |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Content"                                                                                                           |
      | originDimensionSpacePoint     | {"market": "DE", "language": "de"}                                                                                                                 |
      | coveredDimensionSpacePoints   | [{"market": "DE", "language": "de"}, {"market": "DE", "language": "gsw"}, {"market": "CH", "language": "de"}, {"market": "CH", "language": "gsw"}] |
      | parentNodeAggregateId | "lady-eleonode-rootford"                                                                                                                           |
      | nodeName                      | "content"                                                                                                                                          |
      | nodeAggregateClassification   | "regular"                                                                                                                                          |
    And the graph projection is fully up to date
    When the command MoveNodeAggregate is executed with payload and exceptions are caught:
      | Key                              | Value                              |
      | contentStreamId          | "cs-identifier"                    |
      | dimensionSpacePoint              | {"market": "DE", "language": "de"} |
      | nodeAggregateId          | "nody-mc-nodeface"                 |
      | newParentNodeAggregateId | "nodewyn-tetherton"                |
      | relationDistributionStrategy     | "scatter"                          |
    Then the last command should have thrown an exception of type "NodeConstraintException"

  Scenario: Try to move existing node to a non-existing succeeding sibling
    When the command MoveNodeAggregate is executed with payload and exceptions are caught:
      | Key                                         | Value                              |
      | contentStreamId                     | "cs-identifier"                    |
      | dimensionSpacePoint                         | {"market": "DE", "language": "de"} |
      | nodeAggregateId                     | "sir-david-nodenborough"           |
      | newSucceedingSiblingNodeAggregateId | "i-do-not-exist"                   |
      | relationDistributionStrategy                | "scatter"                          |
    Then the last command should have thrown an exception of type "NodeAggregateCurrentlyDoesNotExist"

  Scenario: Try to move existing node to a non-existing preceding sibling
    When the command MoveNodeAggregate is executed with payload and exceptions are caught:
      | Key                                        | Value                              |
      | contentStreamId                    | "cs-identifier"                    |
      | dimensionSpacePoint                        | {"market": "DE", "language": "de"} |
      | nodeAggregateId                    | "sir-david-nodenborough"           |
      | newPrecedingSiblingNodeAggregateId | "i-do-not-exist"                   |
      | relationDistributionStrategy               | "scatter"                          |
    Then the last command should have thrown an exception of type "NodeAggregateCurrentlyDoesNotExist"

  Scenario: Try to move a node to one of its children
    When the command MoveNodeAggregate is executed with payload and exceptions are caught:
      | Key                              | Value                              |
      | contentStreamId          | "cs-identifier"                    |
      | dimensionSpacePoint              | {"market": "DE", "language": "de"} |
      | nodeAggregateId          | "sir-david-nodenborough"           |
      | newParentNodeAggregateId | "nodewyn-tetherton"                |
      | relationDistributionStrategy     | "scatter"                          |
    Then the last command should have thrown an exception of type "NodeAggregateIsDescendant"
