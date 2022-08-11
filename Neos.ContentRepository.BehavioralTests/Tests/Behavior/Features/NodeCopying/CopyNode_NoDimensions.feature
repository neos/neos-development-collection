@contentrepository @adapters=DoctrineDBAL
Feature: Copy nodes (without dimensions)

  Background:
    Given I have no content dimensions
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Testing:Document': []
    """
    And the command CreateRootWorkspace is executed with payload:
      | Key                            | Value                                  |
      | workspaceName                  | "live"                                 |
      | workspaceTitle                 | "Live"                                 |
      | workspaceDescription           | "The live workspace"                   |
      | initiatingUserIdentifier       | "00000000-0000-0000-0000-000000000000" |
      | newContentStreamIdentifier     | "cs-identifier"                        |
    And the graph projection is fully up to date
    And the event RootNodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                  |
      | contentStreamIdentifier     | "cs-identifier"                        |
      | nodeAggregateIdentifier     | "lady-eleonode-rootford"               |
      | nodeTypeName                | "Neos.ContentRepository:Root"          |
      | coveredDimensionSpacePoints | [{}]                                   |
      | initiatingUserIdentifier    | "00000000-0000-0000-0000-000000000000" |
      | nodeAggregateClassification | "root"                                 |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                     |
      | contentStreamIdentifier       | "cs-identifier"                           |
      | nodeAggregateIdentifier       | "sir-david-nodenborough"                  |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint     | {}                                        |
      | coveredDimensionSpacePoints   | [{}]                                      |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                  |
      | nodeName                      | "document"                                |
      | nodeAggregateClassification   | "regular"                                 |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                     |
      | contentStreamIdentifier       | "cs-identifier"                           |
      | nodeAggregateIdentifier       | "nody-mc-nodeface"                        |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint     | {}                                        |
      | coveredDimensionSpacePoints   | [{}]                                      |
      | parentNodeAggregateIdentifier | "sir-david-nodenborough"                  |
      | nodeName                      | "child-document"                          |
      | nodeAggregateClassification   | "regular"                                 |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                     |
      | contentStreamIdentifier       | "cs-identifier"                           |
      | nodeAggregateIdentifier       | "sir-nodeward-nodington-iii"              |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint     | {}                                        |
      | coveredDimensionSpacePoints   | [{}]                                      |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                  |
      | nodeName                      | "esquire"                                 |
      | nodeAggregateClassification   | "regular"                                 |
    And the graph projection is fully up to date

  Scenario: Copy
    When I am in content stream "cs-identifier" and dimension space point {}
    # node to copy (currentNode): "sir-nodeward-nodington-iii"
    Then I expect node aggregate identifier "sir-nodeward-nodington-iii" to lead to node cs-identifier;sir-nodeward-nodington-iii;{}
    When the command CopyNodesRecursively is executed, copying the current node aggregate with payload:
      | Key                                            | Value                                                             |
      | contentStreamIdentifier                        | "cs-identifier"                                                   |
      | targetDimensionSpacePoint                      | {}                                                                |
      | initiatingUserIdentifier                       | "00000000-0000-0000-0000-000000000000"                            |
      | targetParentNodeAggregateIdentifier            | "nody-mc-nodeface"                                                |
      | targetNodeName                                 | "target-nn"                                                       |
      | targetSucceedingSiblingNodeAggregateIdentifier | null                                                              |
      | nodeAggregateIdentifierMapping                 | {"sir-nodeward-nodington-iii": "sir-nodeward-nodington-iii-copy"} |

    And the graph projection is fully up to date
    Then I expect node aggregate identifier "sir-nodeward-nodington-iii-copy" to lead to node cs-identifier;sir-nodeward-nodington-iii-copy;{}
