@fixtures
Feature: Move node to a new parent / within the current parent before a sibling / to the end of the sibling list

  As a user of the CR I want to move a node to a new parent / within the current parent before a sibling / to the end of the sibling list,
  without affecting other nodes in the node aggregate.

  Background:
    Given I have the following content dimensions:
      | Identifier | Default | Values  | Generalizations |
      | market     | DE      | DE, CH  | CH->DE          |
      | language   | de      | de, gsw | gsw->de         |
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
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
    And the event RootWorkspaceWasCreated was published with payload:
      | Key                            | Value                                  |
      | workspaceName                  | "live"                                 |
      | workspaceTitle                 | "Live"                                 |
      | workspaceDescription           | "The live workspace"                   |
      | initiatingUserIdentifier       | "00000000-0000-0000-0000-000000000000" |
      | currentContentStreamIdentifier | "cs-identifier"                        |
      | nodeAggregateClassification    | "root"                                 |
    And the event RootNodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                                                                                                   |
      | contentStreamIdentifier       | "cs-identifier"                                                                                                                         |
      | nodeAggregateIdentifier       | "lady-eleonode-nodesworth"                                                                                                              |
      | nodeTypeName                  | "Neos.ContentRepository:Root"                                                                                                           |
      | visibleInDimensionSpacePoints | [{"market":"DE", "language":"de"},{"market":"DE", "language":"gsw"},{"market":"CH", "language":"de"},{"market":"CH", "language":"gsw"}] |
      | initiatingUserIdentifier      | "00000000-0000-0000-0000-000000000000"                                                                                                  |
      | nodeAggregateClassification   | "root"                                                                                                                                  |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                                                                                                   |
      | contentStreamIdentifier       | "cs-identifier"                                                                                                                         |
      | nodeAggregateIdentifier       | "nody-mc-nodeface"                                                                                                                      |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document"                                                                                               |
      | originDimensionSpacePoint     | {"market":"DE", "language":"de"}                                                                                                        |
      | visibleInDimensionSpacePoints | [{"market":"DE", "language":"de"},{"market":"DE", "language":"gsw"},{"market":"CH", "language":"de"},{"market":"CH", "language":"gsw"}] |
      | parentNodeAggregateIdentifier | "lady-eleonode-nodesworth"                                                                                                              |
      | nodeName                      | "document"                                                                                                                              |
      | nodeAggregateClassification   | "regular"                                                                                                                               |
  #Try to move a root node:
  #  todo: test me
  # Try to move a tethered node:
  #  todo: test me

  Scenario: Try to move a node in a non-existing dimension space point:
    When the command MoveNode is executed with payload and exceptions are caught:
      | Key                          | Value                                     |
      | contentStreamIdentifier      | "cs-identifier"                           |
      | dimensionSpacePoint          | {"market": "nope", "language": "neither"} |
      | nodeAggregateIdentifier      | "nody-mc-nodeface"                        |
      | relationDistributionStrategy | "scatter"                                 |
    Then the last command should have thrown an exception of type "NodeAggregateNotFound"

  Scenario: Try to move a non-existing node
    When the command MoveNode is executed with payload and exceptions are caught:
      | Key                          | Value                              |
      | contentStreamIdentifier      | "cs-identifier"                    |
      | dimensionSpacePoint          | {"market": "DE", "language": "de"} |
      | nodeAggregateIdentifier      | "i-do-not-exist"                   |
      | relationDistributionStrategy | "scatter"                          |
    Then the last command should have thrown an exception of type "NodeAggregateNotFound"

  Scenario: Try to move existing node to a non-existing parent
    When the command MoveNode is executed with payload and exceptions are caught:
      | Key                              | Value                              |
      | contentStreamIdentifier          | "cs-identifier"                    |
      | dimensionSpacePoint              | {"market": "DE", "language": "de"} |
      | nodeAggregateIdentifier          | "nody-mc-nodeface"                 |
      | newParentNodeAggregateIdentifier | "non-existing-parent-identifier"   |
      | relationDistributionStrategy     | "scatter"                          |
    Then the last command should have thrown an exception of type "NodeAggregateNotFound"

  Scenario: Try to move a node to a parent that already has a child node of the same name
    Given the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                                                                                                              |
      | contentStreamIdentifier       | "cs-identifier"                                                                                                                                    |
      | nodeAggregateIdentifier       | "sir-david-nodenborough"                                                                                                                           |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document"                                                                                                          |
      | originDimensionSpacePoint     | {"market": "DE", "language": "de"}                                                                                                                 |
      | visibleInDimensionSpacePoints | [{"market": "DE", "language": "de"}, {"market": "DE", "language": "gsw"}, {"market": "CH", "language": "de"}, {"market": "CH", "language": "gsw"}] |
      | parentNodeAggregateIdentifier | "lady-eleonode-nodesworth"                                                                                                                         |
      | nodeAggregateClassification   | "regular"                                                                                                                                          |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                                                                                                              |
      | contentStreamIdentifier       | "cs-identifier"                                                                                                                                    |
      | nodeAggregateIdentifier       | "sir-nodeward-nodington-iii"                                                                                                                       |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document"                                                                                                          |
      | originDimensionSpacePoint     | {"market": "DE", "language": "de"}                                                                                                                 |
      | visibleInDimensionSpacePoints | [{"market": "DE", "language": "de"}, {"market": "DE", "language": "gsw"}, {"market": "CH", "language": "de"}, {"market": "CH", "language": "gsw"}] |
      | parentNodeAggregateIdentifier | "sir-david-nodenborough"                                                                                                                           |
      | nodeName                      | "document"                                                                                                                                         |
      | nodeAggregateClassification   | "regular"                                                                                                                                          |
    And the graph projection is fully up to date
    When the command MoveNode is executed with payload and exceptions are caught:
      | Key                              | Value                              |
      | contentStreamIdentifier          | "cs-identifier"                    |
      | dimensionSpacePoint              | {"market": "DE", "language": "de"} |
      | nodeAggregateIdentifier          | "nody-mc-nodeface"                 |
      | newParentNodeAggregateIdentifier | "sir-david-nodenborough"           |
      | relationDistributionStrategy     | "scatter"                          |
    Then the last command should have thrown an exception of type "NodeExistsException"

  Scenario: Try to move a node to a parent whose node type does not allow child nodes of the node's type
    Given the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                                                                                                              |
      | contentStreamIdentifier       | "cs-identifier"                                                                                                                                    |
      | nodeAggregateIdentifier       | "sir-david-nodenborough"                                                                                                                           |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Content"                                                                                                           |
      | originDimensionSpacePoint     | {"market": "DE", "language": "de"}                                                                                                                 |
      | visibleInDimensionSpacePoints | [{"market": "DE", "language": "de"}, {"market": "DE", "language": "gsw"}, {"market": "CH", "language": "de"}, {"market": "CH", "language": "gsw"}] |
      | nodeIdentifier                | "doc-identifier"                                                                                                                                   |
      | parentNodeAggregateIdentifier | "lady-eleonode-nodesworth"                                                                                                                         |
      | nodeAggregateClassification   | "regular"                                                                                                                                          |
    And the graph projection is fully up to date
    When the command MoveNode is executed with payload and exceptions are caught:
      | Key                              | Value                              |
      | contentStreamIdentifier          | "cs-identifier"                    |
      | dimensionSpacePoint              | {"market": "DE", "language": "de"} |
      | nodeAggregateIdentifier          | "nody-mc-nodeface"                 |
      | newParentNodeAggregateIdentifier | "sir-david-nodenborough"           |
      | relationDistributionStrategy     | "scatter"                          |
    Then the last command should have thrown an exception of type "NodeConstraintException"

  Scenario: Try to move a node to a parent whose parent's node type does not allow grand child nodes of the node's type
    Given the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                                                                                                              |
      | contentStreamIdentifier       | "cs-identifier"                                                                                                                                    |
      | nodeAggregateIdentifier       | "sir-david-nodenborough"                                                                                                                           |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:DocumentWithAutoCreatedChildNode"                                                                                  |
      | originDimensionSpacePoint     | {"market": "DE", "language": "de"}                                                                                                                 |
      | visibleInDimensionSpacePoints | [{"market": "DE", "language": "de"}, {"market": "DE", "language": "gsw"}, {"market": "CH", "language": "de"}, {"market": "CH", "language": "gsw"}] |
      | nodeIdentifier                | "doc-identifier"                                                                                                                                   |
      | parentNodeAggregateIdentifier | "lady-eleonode-nodesworth"                                                                                                                         |
      | nodeName                      | "document"                                                                                                                                         |
      | nodeAggregateClassification   | "regular"                                                                                                                                          |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                                                                                                              |
      | contentStreamIdentifier       | "cs-identifier"                                                                                                                                    |
      | nodeAggregateIdentifier       | "nodimus-prime"                                                                                                                                    |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Content"                                                                                                           |
      | originDimensionSpacePoint     | {"market": "DE", "language": "de"}                                                                                                                 |
      | visibleInDimensionSpacePoints | [{"market": "DE", "language": "de"}, {"market": "DE", "language": "gsw"}, {"market": "CH", "language": "de"}, {"market": "CH", "language": "gsw"}] |
      | parentNodeAggregateIdentifier | "sir-david-nodenborough"                                                                                                                           |
      | nodeName                      | "autocreated"                                                                                                                                      |
      | nodeAggregateClassification   | "tethered"                                                                                                                                         |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                                                                                                              |
      | contentStreamIdentifier       | "cs-identifier"                                                                                                                                    |
      | nodeAggregateIdentifier       | "nodasaurus-rex"                                                                                                                                   |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Content"                                                                                                           |
      | originDimensionSpacePoint     | {"market": "DE", "language": "de"}                                                                                                                 |
      | visibleInDimensionSpacePoints | [{"market": "DE", "language": "de"}, {"market": "DE", "language": "gsw"}, {"market": "CH", "language": "de"}, {"market": "CH", "language": "gsw"}] |
      | parentNodeAggregateIdentifier | "sir-david-nodenborough"                                                                                                                           |
      | nodeName                      | "content"                                                                                                                                          |
      | nodeAggregateClassification   | "regular"                                                                                                                                          |
    And the graph projection is fully up to date
    When the command MoveNode is executed with payload and exceptions are caught:
      | Key                              | Value                              |
      | contentStreamIdentifier          | "cs-identifier"                    |
      | dimensionSpacePoint              | {"market": "DE", "language": "de"} |
      | nodeAggregateIdentifier          | "nodasaurus-rex"                   |
      | newParentNodeAggregateIdentifier | "nodimus-prime"                    |
      | relationDistributionStrategy     | "scatter"                          |
    Then the last command should have thrown an exception of type "NodeConstraintException"

  Scenario: Try to move existing node to a non-existing succeeding sibling
    Given the graph projection is fully up to date
    When the command MoveNode is executed with payload and exceptions are caught:
      | Key                                         | Value                              |
      | contentStreamIdentifier                     | "cs-identifier"                    |
      | dimensionSpacePoint                         | {"market": "DE", "language": "de"} |
      | nodeAggregateIdentifier                     | "nody-mc-nodeface"                 |
      | newSucceedingSiblingNodeAggregateIdentifier | "nonexistent-agg-identifier"       |
      | relationDistributionStrategy                | "scatter"                          |
    Then the last command should have thrown an exception of type "NodeAggregateNotFound"

  Scenario: Try to move an autogenerated child node to a new parent
    Given the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                                                                                                              |
      | contentStreamIdentifier       | "cs-identifier"                                                                                                                                    |
      | nodeAggregateIdentifier       | "sir-david-nodenborough"                                                                                                                           |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:DocumentWithAutoCreatedChildNode"                                                                                  |
      | originDimensionSpacePoint     | {"market": "DE", "language": "de"}                                                                                                                 |
      | visibleInDimensionSpacePoints | [{"market": "DE", "language": "de"}, {"market": "DE", "language": "gsw"}, {"market": "CH", "language": "de"}, {"market": "CH", "language": "gsw"}] |
      | parentNodeAggregateIdentifier | "lady-eleonode-nodesworth"                                                                                                                         |
      | nodeName                      | "document"                                                                                                                                         |
      | nodeAggregateClassification   | "regular"                                                                                                                                          |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                                                                                                              |
      | contentStreamIdentifier       | "cs-identifier"                                                                                                                                    |
      | nodeAggregateIdentifier       | "nodimus-prime"                                                                                                                                    |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Content"                                                                                                           |
      | originDimensionSpacePoint     | {"market": "DE", "language": "de"}                                                                                                                 |
      | visibleInDimensionSpacePoints | [{"market": "DE", "language": "de"}, {"market": "DE", "language": "gsw"}, {"market": "CH", "language": "de"}, {"market": "CH", "language": "gsw"}] |
      | parentNodeAggregateIdentifier | "sir-david-nodenborough"                                                                                                                           |
      | nodeName                      | "autocreated"                                                                                                                                      |
      | nodeAggregateClassification   | "tethered"                                                                                                                                         |
    And the graph projection is fully up to date
    When the command MoveNode is executed with payload and exceptions are caught:
      | Key                              | Value                              |
      | contentStreamIdentifier          | "cs-identifier"                    |
      | dimensionSpacePoint              | {"market": "DE", "language": "de"} |
      | nodeAggregateIdentifier          | "nodimus-prime"                    |
      | newParentNodeAggregateIdentifier | "lady-eleonode-nodesworth"         |
      | relationDistributionStrategy     | "scatter"                          |
    Then the last command should have thrown an exception of type "NodeConstraintException"
