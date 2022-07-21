@fixtures @adapters=DoctrineDBAL
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
    And the event RootWorkspaceWasCreated was published with payload:
      | Key                        | Value                                  |
      | workspaceName              | "live"                                 |
      | workspaceTitle             | "Live"                                 |
      | workspaceDescription       | "The live workspace"                   |
      | initiatingUserIdentifier   | "00000000-0000-0000-0000-000000000000" |
      | newContentStreamIdentifier | "cs-identifier"                        |
    And the event RootNodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                                                                                                                   |
      | contentStreamIdentifier     | "cs-identifier"                                                                                                                         |
      | nodeAggregateIdentifier     | "lady-eleonode-rootford"                                                                                                                |
      | nodeTypeName                | "Neos.ContentRepository:Root"                                                                                                           |
      | coveredDimensionSpacePoints | [{"market":"DE", "language":"de"},{"market":"DE", "language":"gsw"},{"market":"CH", "language":"de"},{"market":"CH", "language":"gsw"}] |
      | initiatingUserIdentifier    | "00000000-0000-0000-0000-000000000000"                                                                                                  |
      | nodeAggregateClassification | "root"                                                                                                                                  |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                                                                                                   |
      | contentStreamIdentifier       | "cs-identifier"                                                                                                                         |
      | nodeAggregateIdentifier       | "sir-david-nodenborough"                                                                                                                |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:DocumentWithTetheredChildNode"                                                                          |
      | originDimensionSpacePoint     | {"market":"DE", "language":"de"}                                                                                                        |
      | coveredDimensionSpacePoints   | [{"market":"DE", "language":"de"},{"market":"DE", "language":"gsw"},{"market":"CH", "language":"de"},{"market":"CH", "language":"gsw"}] |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                                                                                                                |
      | nodeName                      | "document"                                                                                                                              |
      | nodeAggregateClassification   | "regular"                                                                                                                               |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                                                                                                   |
      | contentStreamIdentifier       | "cs-identifier"                                                                                                                         |
      | nodeAggregateIdentifier       | "nodewyn-tetherton"                                                                                                                     |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Content"                                                                                                |
      | originDimensionSpacePoint     | {"market":"DE", "language":"de"}                                                                                                        |
      | coveredDimensionSpacePoints   | [{"market":"DE", "language":"de"},{"market":"DE", "language":"gsw"},{"market":"CH", "language":"de"},{"market":"CH", "language":"gsw"}] |
      | parentNodeAggregateIdentifier | "sir-david-nodenborough"                                                                                                                |
      | nodeName                      | "tethered"                                                                                                                              |
      | nodeAggregateClassification   | "tethered"                                                                                                                              |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                                                                                                   |
      | contentStreamIdentifier       | "cs-identifier"                                                                                                                         |
      | nodeAggregateIdentifier       | "sir-nodeward-nodington-iii"                                                                                                            |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document"                                                                                               |
      | originDimensionSpacePoint     | {"market":"DE", "language":"de"}                                                                                                        |
      | coveredDimensionSpacePoints   | [{"market":"DE", "language":"de"},{"market":"DE", "language":"gsw"},{"market":"CH", "language":"de"},{"market":"CH", "language":"gsw"}] |
      | parentNodeAggregateIdentifier | "sir-david-nodenborough"                                                                                                                |
      | nodeName                      | "esquire"                                                                                                                               |
      | nodeAggregateClassification   | "regular"                                                                                                                               |
    And the graph projection is fully up to date

  Scenario: Try to move a node in a non-existing content stream:
    When the command MoveNodeAggregate is executed with payload and exceptions are caught:
      | Key                          | Value                              |
      | contentStreamIdentifier      | "non-existing"                     |
      | nodeAggregateIdentifier      | "sir-david-nodenborough"           |
      | dimensionSpacePoint          | {"market": "DE", "language": "de"} |
      | relationDistributionStrategy | "scatter"                          |
      | initiatingUserIdentifier     | "user"                             |
    Then the last command should have thrown an exception of type "ContentStreamDoesNotExistYet"

  Scenario: Try to move a node of a non-existing node aggregate:
    When the command MoveNodeAggregate is executed with payload and exceptions are caught:
      | Key                          | Value                              |
      | contentStreamIdentifier      | "cs-identifier"                    |
      | nodeAggregateIdentifier      | "i-do-not-exist"                   |
      | dimensionSpacePoint          | {"market": "DE", "language": "de"} |
      | relationDistributionStrategy | "scatter"                          |
      | initiatingUserIdentifier     | "user"                             |
    Then the last command should have thrown an exception of type "NodeAggregateCurrentlyDoesNotExist"

  Scenario: Try to move a node of a root node aggregate:
    When the command MoveNodeAggregate is executed with payload and exceptions are caught:
      | Key                          | Value                            |
      | contentStreamIdentifier      | "cs-identifier"                  |
      | nodeAggregateIdentifier      | "lady-eleonode-rootford"         |
      | dimensionSpacePoint          | {"market":"DE", "language":"de"} |
      | relationDistributionStrategy | "scatter"                        |
      | initiatingUserIdentifier     | "user"                           |
    Then the last command should have thrown an exception of type "NodeAggregateIsRoot"

  Scenario: Try to move a node of a tethered node aggregate:
    When the command MoveNodeAggregate is executed with payload and exceptions are caught:
      | Key                          | Value                              |
      | contentStreamIdentifier      | "cs-identifier"                    |
      | nodeAggregateIdentifier      | "nodewyn-tetherton"                |
      | dimensionSpacePoint          | {"market": "DE", "language": "de"} |
      | relationDistributionStrategy | "scatter"                          |
      | initiatingUserIdentifier     | "user"                             |
    Then the last command should have thrown an exception of type "NodeAggregateIsTethered"

  Scenario: Try to move a node in a non-existing dimension space point:
    When the command MoveNodeAggregate is executed with payload and exceptions are caught:
      | Key                          | Value                                     |
      | contentStreamIdentifier      | "cs-identifier"                           |
      | nodeAggregateIdentifier      | "sir-david-nodenborough"                  |
      | dimensionSpacePoint          | {"market": "nope", "language": "neither"} |
      | relationDistributionStrategy | "scatter"                                 |
      | initiatingUserIdentifier     | "user"                                    |
    Then the last command should have thrown an exception of type "DimensionSpacePointNotFound"

  Scenario: Try to move a node in a dimension space point the aggregate does not cover
    When the command MoveNodeAggregate is executed with payload and exceptions are caught:
      | Key                          | Value                              |
      | contentStreamIdentifier      | "cs-identifier"                    |
      | nodeAggregateIdentifier      | "sir-david-nodenborough"           |
      | dimensionSpacePoint          | {"market": "DE", "language": "fr"} |
      | relationDistributionStrategy | "scatter"                          |
      | initiatingUserIdentifier     | "user"                             |
    Then the last command should have thrown an exception of type "NodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint"

  Scenario: Try to move existing node to a non-existing parent
    When the command MoveNodeAggregate is executed with payload and exceptions are caught:
      | Key                              | Value                              |
      | contentStreamIdentifier          | "cs-identifier"                    |
      | dimensionSpacePoint              | {"market": "DE", "language": "de"} |
      | nodeAggregateIdentifier          | "sir-david-nodenborough"           |
      | newParentNodeAggregateIdentifier | "non-existing-parent-identifier"   |
      | relationDistributionStrategy     | "scatter"                          |
      | initiatingUserIdentifier         | "user"                             |
    Then the last command should have thrown an exception of type "NodeAggregateCurrentlyDoesNotExist"

  Scenario: Try to move a node to a parent that already has a child node of the same name
    Given the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                                                                                                              |
      | contentStreamIdentifier       | "cs-identifier"                                                                                                                                    |
      | nodeAggregateIdentifier       | "nody-mc-nodeface"                                                                                                                                 |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document"                                                                                                          |
      | originDimensionSpacePoint     | {"market": "DE", "language": "de"}                                                                                                                 |
      | coveredDimensionSpacePoints   | [{"market": "DE", "language": "de"}, {"market": "DE", "language": "gsw"}, {"market": "CH", "language": "de"}, {"market": "CH", "language": "gsw"}] |
      | parentNodeAggregateIdentifier | "sir-david-nodenborough"                                                                                                                           |
      | nodeName                      | "document"                                                                                                                                         |
      | nodeAggregateClassification   | "regular"                                                                                                                                          |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload and exceptions are caught:
      | Key                              | Value                              |
      | contentStreamIdentifier          | "cs-identifier"                    |
      | dimensionSpacePoint              | {"market": "DE", "language": "de"} |
      | nodeAggregateIdentifier          | "nody-mc-nodeface"                 |
      | newParentNodeAggregateIdentifier | "lady-eleonode-rootford"           |
      | relationDistributionStrategy     | "scatter"                          |
      | initiatingUserIdentifier         | "user"                             |
    Then the last command should have thrown an exception of type "NodeNameIsAlreadyCovered"

  Scenario: Move a node that has no name
    Given the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                                                                                                              |
      | contentStreamIdentifier       | "cs-identifier"                                                                                                                                    |
      | nodeAggregateIdentifier       | "nody-mc-nodeface"                                                                                                                                 |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document"                                                                                                          |
      | originDimensionSpacePoint     | {"market": "DE", "language": "de"}                                                                                                                 |
      | coveredDimensionSpacePoints   | [{"market": "DE", "language": "de"}, {"market": "DE", "language": "gsw"}, {"market": "CH", "language": "de"}, {"market": "CH", "language": "gsw"}] |
      | parentNodeAggregateIdentifier | "sir-david-nodenborough"                                                                                                                           |
      | nodeAggregateClassification   | "regular"                                                                                                                                          |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload:
      | Key                              | Value                              |
      | contentStreamIdentifier          | "cs-identifier"                    |
      | dimensionSpacePoint              | {"market": "DE", "language": "de"} |
      | nodeAggregateIdentifier          | "nody-mc-nodeface"                 |
      | newParentNodeAggregateIdentifier | "lady-eleonode-rootford"           |
      | relationDistributionStrategy     | "scatter"                          |
      | initiatingUserIdentifier         | "user"                             |
    And the graph projection is fully up to date
    When I am in content stream "cs-identifier" and dimension space point {"market": "DE", "language": "de"}
    And I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"market":"DE","language":"de"}


  Scenario: Try to move a node to a parent whose node type does not allow child nodes of the node's type
    Given the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                                                                                                              |
      | contentStreamIdentifier       | "cs-identifier"                                                                                                                                    |
      | nodeAggregateIdentifier       | "nody-mc-nodeface"                                                                                                                                 |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document"                                                                                                          |
      | originDimensionSpacePoint     | {"market": "DE", "language": "de"}                                                                                                                 |
      | coveredDimensionSpacePoints   | [{"market": "DE", "language": "de"}, {"market": "DE", "language": "gsw"}, {"market": "CH", "language": "de"}, {"market": "CH", "language": "gsw"}] |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                                                                                                                           |
      | nodeName                      | "other-document"                                                                                                                                   |
      | nodeAggregateClassification   | "regular"                                                                                                                                          |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload and exceptions are caught:
      | Key                              | Value                              |
      | contentStreamIdentifier          | "cs-identifier"                    |
      | dimensionSpacePoint              | {"market": "DE", "language": "de"} |
      | nodeAggregateIdentifier          | "nody-mc-nodeface"                 |
      | newParentNodeAggregateIdentifier | "nodewyn-tetherton"                |
      | relationDistributionStrategy     | "scatter"                          |
      | initiatingUserIdentifier         | "user"                             |
    Then the last command should have thrown an exception of type "NodeConstraintException"

  Scenario: Try to move a node to a parent whose parent's node type does not allow grand child nodes of the node's type
    Given the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                                                                                                              |
      | contentStreamIdentifier       | "cs-identifier"                                                                                                                                    |
      | nodeAggregateIdentifier       | "nody-mc-nodeface"                                                                                                                                 |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Content"                                                                                                           |
      | originDimensionSpacePoint     | {"market": "DE", "language": "de"}                                                                                                                 |
      | coveredDimensionSpacePoints   | [{"market": "DE", "language": "de"}, {"market": "DE", "language": "gsw"}, {"market": "CH", "language": "de"}, {"market": "CH", "language": "gsw"}] |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                                                                                                                           |
      | nodeName                      | "content"                                                                                                                                          |
      | nodeAggregateClassification   | "regular"                                                                                                                                          |
    And the graph projection is fully up to date
    When the command MoveNodeAggregate is executed with payload and exceptions are caught:
      | Key                              | Value                              |
      | contentStreamIdentifier          | "cs-identifier"                    |
      | dimensionSpacePoint              | {"market": "DE", "language": "de"} |
      | nodeAggregateIdentifier          | "nody-mc-nodeface"                 |
      | newParentNodeAggregateIdentifier | "nodewyn-tetherton"                |
      | relationDistributionStrategy     | "scatter"                          |
      | initiatingUserIdentifier         | "user"                             |
    Then the last command should have thrown an exception of type "NodeConstraintException"

  Scenario: Try to move existing node to a non-existing succeeding sibling
    When the command MoveNodeAggregate is executed with payload and exceptions are caught:
      | Key                                         | Value                              |
      | contentStreamIdentifier                     | "cs-identifier"                    |
      | dimensionSpacePoint                         | {"market": "DE", "language": "de"} |
      | nodeAggregateIdentifier                     | "sir-david-nodenborough"           |
      | newSucceedingSiblingNodeAggregateIdentifier | "i-do-not-exist"                   |
      | relationDistributionStrategy                | "scatter"                          |
      | initiatingUserIdentifier                    | "user"                             |
    Then the last command should have thrown an exception of type "NodeAggregateCurrentlyDoesNotExist"

  Scenario: Try to move existing node to a non-existing preceding sibling
    When the command MoveNodeAggregate is executed with payload and exceptions are caught:
      | Key                                        | Value                              |
      | contentStreamIdentifier                    | "cs-identifier"                    |
      | dimensionSpacePoint                        | {"market": "DE", "language": "de"} |
      | nodeAggregateIdentifier                    | "sir-david-nodenborough"           |
      | newPrecedingSiblingNodeAggregateIdentifier | "i-do-not-exist"                   |
      | relationDistributionStrategy               | "scatter"                          |
      | initiatingUserIdentifier                   | "user"                             |
    Then the last command should have thrown an exception of type "NodeAggregateCurrentlyDoesNotExist"

  Scenario: Try to move a node to one of its children
    When the command MoveNodeAggregate is executed with payload and exceptions are caught:
      | Key                              | Value                              |
      | contentStreamIdentifier          | "cs-identifier"                    |
      | dimensionSpacePoint              | {"market": "DE", "language": "de"} |
      | nodeAggregateIdentifier          | "sir-david-nodenborough"           |
      | newParentNodeAggregateIdentifier | "nodewyn-tetherton"                |
      | relationDistributionStrategy     | "scatter"                          |
      | initiatingUserIdentifier         | "user"                             |
    Then the last command should have thrown an exception of type "NodeAggregateIsDescendant"
