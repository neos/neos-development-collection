@contentrepository @adapters=DoctrineDBAL
Feature: Copy nodes (without dimensions)

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Document': []
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                            | Value                                  |
      | workspaceName                  | "live"                                 |
      | workspaceTitle                 | "Live"                                 |
      | workspaceDescription           | "The live workspace"                   |
      | newContentStreamId     | "cs-identifier"                        |
    And the graph projection is fully up to date
    And I am in the active content stream of workspace "live"
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                         | Value                                  |
      | nodeAggregateId     | "lady-eleonode-rootford"               |
      | nodeTypeName                | "Neos.ContentRepository:Root"          |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                     |
      | contentStreamId       | "cs-identifier"                           |
      | nodeAggregateId       | "sir-david-nodenborough"                  |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint     | {}                                        |
      | coveredDimensionSpacePoints   | [{}]                                      |
      | parentNodeAggregateId | "lady-eleonode-rootford"                  |
      | nodeName                      | "document"                                |
      | nodeAggregateClassification   | "regular"                                 |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                     |
      | contentStreamId       | "cs-identifier"                           |
      | nodeAggregateId       | "nody-mc-nodeface"                        |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint     | {}                                        |
      | coveredDimensionSpacePoints   | [{}]                                      |
      | parentNodeAggregateId | "sir-david-nodenborough"                  |
      | nodeName                      | "child-document"                          |
      | nodeAggregateClassification   | "regular"                                 |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                     |
      | contentStreamId       | "cs-identifier"                           |
      | nodeAggregateId       | "sir-nodeward-nodington-iii"              |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint     | {}                                        |
      | coveredDimensionSpacePoints   | [{}]                                      |
      | parentNodeAggregateId | "lady-eleonode-rootford"                  |
      | nodeName                      | "esquire"                                 |
      | nodeAggregateClassification   | "regular"                                 |
    And the graph projection is fully up to date

  Scenario: Copy
    When I am in the active content stream of workspace "live" and dimension space point {}
    # node to copy (currentNode): "sir-nodeward-nodington-iii"
    Then I expect node aggregate identifier "sir-nodeward-nodington-iii" to lead to node cs-identifier;sir-nodeward-nodington-iii;{}
    When the command CopyNodesRecursively is executed, copying the current node aggregate with payload:
      | Key                                            | Value                                                             |
      | contentStreamId                        | "cs-identifier"                                                   |
      | targetDimensionSpacePoint                      | {}                                                                |
      | targetParentNodeAggregateId            | "nody-mc-nodeface"                                                |
      | targetNodeName                                 | "target-nn"                                                       |
      | targetSucceedingSiblingnodeAggregateId | null                                                              |
      | nodeAggregateIdMapping                 | {"sir-nodeward-nodington-iii": "sir-nodeward-nodington-iii-copy"} |

    And the graph projection is fully up to date
    Then I expect node aggregate identifier "sir-nodeward-nodington-iii-copy" to lead to node cs-identifier;sir-nodeward-nodington-iii-copy;{}
