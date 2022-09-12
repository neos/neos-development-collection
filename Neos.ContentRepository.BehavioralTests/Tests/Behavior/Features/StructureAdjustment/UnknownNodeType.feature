@contentrepository @adapters=DoctrineDBAL
Feature: Unknown node types

  As a user of the CR I want to be able to detect and remove unknown node types

  Background:
    Given I have no content dimensions
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Testing:Document': []
    'Neos.Neos:FallbackNode': []
    """
    And the command CreateRootWorkspace is executed with payload:
      | Key                        | Value                |
      | workspaceName              | "live"               |
      | workspaceTitle             | "Live"               |
      | workspaceDescription       | "The live workspace" |
      | newContentStreamId | "cs-identifier"      |
      | initiatingUserId   | "system-user"        |
    And the graph projection is fully up to date
    And the event RootNodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                         |
      | contentStreamId     | "cs-identifier"               |
      | nodeAggregateId     | "lady-eleonode-rootford"      |
      | nodeTypeName                | "Neos.ContentRepository:Root" |
      | coveredDimensionSpacePoints | [{}]                          |
      | initiatingUserId    | "system-user"                 |
      | nodeAggregateClassification | "root"                        |
    # Node /document
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
    And the graph projection is fully up to date
    Then I expect no needed structure adjustments for type "Neos.ContentRepository.Testing:Document"

  Scenario: When removing "Neos.ContentRepository.Testing:Document", we find a missing node type.
    When I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.Neos:FallbackNode': []
    """
    Then I expect the following structure adjustments for type "Neos.ContentRepository.Testing:Document":
      | Type              | nodeAggregateId |
      | NODE_TYPE_MISSING | sir-david-nodenborough  |

    When I adjust the node structure for node type "Neos.ContentRepository.Testing:Document"
    Then I expect no needed structure adjustments for type "Neos.ContentRepository.Testing:Document"
    When I am in content stream "cs-identifier" and dimension space point {"market":"CH", "language":"gsw"}
    And I expect node aggregate identifier "sir-david-nodenborough" to lead to no node

