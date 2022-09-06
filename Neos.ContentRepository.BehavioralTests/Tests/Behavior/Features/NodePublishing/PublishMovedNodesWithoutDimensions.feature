@contentrepository @adapters=DoctrineDBAL
Feature: Publishing moved nodes without dimensions

  As a user of the CR I want to move a node
  - to the end of its siblings
  - before one of its siblings
  - to a new parent and the end of its children
  - to a new parent and before one of its children
  and then publish the result to the target workspace.

  These are the test cases for publishing moved nodes without content dimensions being involved

  Background:
    Given I have no content dimensions
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Testing:Document': []
    """
    And the command CreateRootWorkspace is executed with payload:
      | Key                        | Value           |
      | workspaceName              | "live"          |
      | newContentStreamId | "cs-identifier" |
    And the graph projection is fully up to date
    And the event RootNodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                  |
      | contentStreamId     | "cs-identifier"                        |
      | nodeAggregateId     | "lady-eleonode-rootford"               |
      | nodeTypeName                | "Neos.ContentRepository:Root"          |
      | coveredDimensionSpacePoints | [{}]                                   |
      | nodeAggregateClassification | "root"                                 |
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

    And the command CreateWorkspace is executed with payload:
      | Key                        | Value                |
      | workspaceName              | "user"               |
      | baseWorkspaceName          | "live"               |
      | newContentStreamId | "user-cs-identifier" |
    And the graph projection is fully up to date

  Scenario: Publish the move of a node to the end of its siblings
    When the command MoveNodeAggregate is executed with payload:
      | Key                                         | Value                    |
      | contentStreamId                     | "user-cs-identifier"     |
      | dimensionSpacePoint                         | {}                       |
      | nodeAggregateId                     | "sir-david-nodenborough" |
      | newParentNodeAggregateId            | null                     |
      | newSucceedingSiblingNodeAggregateId | null                     |
    And the command PublishIndividualNodesFromWorkspace is executed with payload:
      | Key                      | Value                                                                                                                               |
      | workspaceName            | "user"                                                                                                                              |
      | nodesToPublish           | [{"contentStreamId": "user-cs-identifier", "dimensionSpacePoint": {}, "nodeAggregateId": "sir-david-nodenborough"}] |
    And the graph projection is fully up to date

    Then I expect the graph projection to consist of exactly 4 nodes
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect a node identified by cs-identifier;sir-david-nodenborough;{} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{} to exist in the content graph
    And I expect a node identified by cs-identifier;sir-nodeward-nodington-iii;{} to exist in the content graph

    # node aggregate occupation and coverage is not relevant without dimensions and thus not tested

    When I am in content stream "cs-identifier" and dimension space point {}
    Then I expect node aggregate identifier "sir-nodeward-nodington-iii" and node path "esquire" to lead to node cs-identifier;sir-nodeward-nodington-iii;{}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                       |
      | cs-identifier;sir-david-nodenborough;{} |
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                           |
      | cs-identifier;sir-nodeward-nodington-iii;{} |
    And I expect this node to have no succeeding siblings
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to node cs-identifier;nody-mc-nodeface;{}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{}
    And I expect this node to have no preceding siblings
    And I expect this node to have no succeeding siblings

  Scenario: Publish the move of a node before one of its siblings
    When the command MoveNodeAggregate is executed with payload:
      | Key                                         | Value                        |
      | contentStreamId                     | "user-cs-identifier"         |
      | nodeAggregateId                     | "sir-nodeward-nodington-iii" |
      | dimensionSpacePoint                         | {}                           |
      | newParentNodeAggregateId            | null                         |
      | newSucceedingSiblingNodeAggregateId | "sir-david-nodenborough"     |
    And the command PublishIndividualNodesFromWorkspace is executed with payload:
      | Key                      | Value                                                                                                                                   |
      | workspaceName            | "user"                                                                                                                                  |
      | nodesToPublish           | [{"contentStreamId": "user-cs-identifier", "dimensionSpacePoint": {}, "nodeAggregateId": "sir-nodeward-nodington-iii"}] |
    And the graph projection is fully up to date

    Then I expect the graph projection to consist of exactly 4 nodes
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect a node identified by cs-identifier;sir-david-nodenborough;{} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{} to exist in the content graph
    And I expect a node identified by cs-identifier;sir-nodeward-nodington-iii;{} to exist in the content graph

    # node aggregate occupation and coverage is not relevant without dimensions and thus not tested

    When I am in content stream "cs-identifier" and dimension space point {}
    Then I expect node aggregate identifier "sir-nodeward-nodington-iii" and node path "esquire" to lead to node cs-identifier;sir-nodeward-nodington-iii;{}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                       |
      | cs-identifier;sir-david-nodenborough;{} |
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                           |
      | cs-identifier;sir-nodeward-nodington-iii;{} |
    And I expect this node to have no succeeding siblings
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to node cs-identifier;nody-mc-nodeface;{}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{}
    And I expect this node to have no preceding siblings
    And I expect this node to have no succeeding siblings

  Scenario: Publish the move of a node to a new parent and the end of its children
    Given the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                     |
      | contentStreamId       | "cs-identifier"                           |
      | nodeAggregateId       | "lady-abigail-nodenborough"               |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint     | {}                                        |
      | coveredDimensionSpacePoints   | [{}]                                      |
      | parentNodeAggregateId | "sir-nodeward-nodington-iii"              |
      | nodeName                      | "other-document"                          |
      | nodeAggregateClassification   | "regular"                                 |
    And the graph projection is fully up to date
    When the command MoveNodeAggregate is executed with payload:
      | Key                              | Value                        |
      | contentStreamId          | "user-cs-identifier"         |
      | nodeAggregateId          | "sir-david-nodenborough"     |
      | dimensionSpacePoint              | {}                           |
      | newParentNodeAggregateId | "sir-nodeward-nodington-iii" |
    And the command PublishIndividualNodesFromWorkspace is executed with payload:
      | Key                      | Value                                                                                                                               |
      | workspaceName            | "user"                                                                                                                              |
      | nodesToPublish           | [{"contentStreamId": "user-cs-identifier", "dimensionSpacePoint": {}, "nodeAggregateId": "sir-david-nodenborough"}] |
    And the graph projection is fully up to date

    Then I expect the graph projection to consist of exactly 5 nodes
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect a node identified by cs-identifier;sir-david-nodenborough;{} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{} to exist in the content graph
    And I expect a node identified by cs-identifier;sir-nodeward-nodington-iii;{} to exist in the content graph
    And I expect a node identified by cs-identifier;lady-abigail-nodenborough;{} to exist in the content graph

    # node aggregate occupation and coverage is not relevant without dimensions and thus not tested

    When I am in content stream "cs-identifier" and dimension space point {}
    And I expect node aggregate identifier "sir-nodeward-nodington-iii" and node path "esquire" to lead to node cs-identifier;sir-nodeward-nodington-iii;{}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no preceding siblings
    And I expect this node to have no succeeding siblings
    And I expect node aggregate identifier "lady-abigail-nodenborough" and node path "esquire/other-document" to lead to node cs-identifier;lady-abigail-nodenborough;{}
    And I expect this node to be a child of node cs-identifier;sir-nodeward-nodington-iii;{}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                       |
      | cs-identifier;sir-david-nodenborough;{} |
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "esquire/document" to lead to node cs-identifier;sir-david-nodenborough;{}
    And I expect this node to be a child of node cs-identifier;sir-nodeward-nodington-iii;{}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                          |
      | cs-identifier;lady-abigail-nodenborough;{} |
    And I expect this node to have no succeeding siblings
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document/child-document" to lead to node cs-identifier;nody-mc-nodeface;{}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{}
    And I expect this node to have no preceding siblings
    And I expect this node to have no succeeding siblings

  Scenario: Publish the move of a node to a new parent and before one of its children
    When the command MoveNodeAggregate is executed with payload:
      | Key                                         | Value                        |
      | contentStreamId                     | "user-cs-identifier"         |
      | nodeAggregateId                     | "nody-mc-nodeface"           |
      | dimensionSpacePoint                         | {}                           |
      | newParentNodeAggregateId            | "lady-eleonode-rootford"     |
      | newSucceedingSiblingNodeAggregateId | "sir-nodeward-nodington-iii" |
    And the command PublishIndividualNodesFromWorkspace is executed with payload:
      | Key                      | Value                                                                                                                         |
      | workspaceName            | "user"                                                                                                                        |
      | nodesToPublish           | [{"contentStreamId": "user-cs-identifier", "dimensionSpacePoint": {}, "nodeAggregateId": "nody-mc-nodeface"}] |

    When the graph projection is fully up to date
    Then I expect the graph projection to consist of exactly 4 nodes
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect a node identified by cs-identifier;sir-david-nodenborough;{} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{} to exist in the content graph
    And I expect a node identified by cs-identifier;sir-nodeward-nodington-iii;{} to exist in the content graph

    # node aggregate occupation and coverage is not relevant without dimensions and thus not tested

    When I am in content stream "cs-identifier" and dimension space point {}
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                           |
      | cs-identifier;nody-mc-nodeface;{}           |
      | cs-identifier;sir-nodeward-nodington-iii;{} |
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "child-document" to lead to node cs-identifier;nody-mc-nodeface;{}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                       |
      | cs-identifier;sir-david-nodenborough;{} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                           |
      | cs-identifier;sir-nodeward-nodington-iii;{} |
    And I expect node aggregate identifier "sir-nodeward-nodington-iii" and node path "esquire" to lead to node cs-identifier;sir-nodeward-nodington-iii;{}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                       |
      | cs-identifier;nody-mc-nodeface;{}       |
      | cs-identifier;sir-david-nodenborough;{} |
    And I expect this node to have no succeeding siblings
