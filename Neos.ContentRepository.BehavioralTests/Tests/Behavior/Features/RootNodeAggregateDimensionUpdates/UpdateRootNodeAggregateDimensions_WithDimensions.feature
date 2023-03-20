@contentrepository @adapters=DoctrineDBAL
Feature: Update Root Node aggregate dimensions

  I want to update a root node aggregate's dimensions when the dimension config changes.

  Background:
    Given I have the following content dimensions:
      | Identifier | Values  | Generalizations |
      | language   | mul, de |                 |
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    """
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootWorkspace is executed with payload:
      | Key                  | Value                |
      | workspaceName        | "live"               |
      | workspaceTitle       | "Live"               |
      | workspaceDescription | "The live workspace" |
      | newContentStreamId   | "cs-identifier"      |
    And the graph projection is fully up to date
    And I am in content stream "cs-identifier"
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |


  Scenario: Initial setup of the root node (similar to 01/RootNodeCreation/03-...)
    Then I expect exactly 2 events to be published on stream "ContentStream:cs-identifier"
    And event at index 1 is of type "RootNodeAggregateWithNodeWasCreated" with payload:
      | Key                         | Expected                               |
      | contentStreamId             | "cs-identifier"                        |
      | nodeAggregateId             | "lady-eleonode-rootford"               |
      | nodeTypeName                | "Neos.ContentRepository:Root"          |
      | coveredDimensionSpacePoints | [{"language":"mul"},{"language":"de"}] |
      | nodeAggregateClassification | "root"                                 |
    And event metadata at index 1 is:
      | Key | Expected |

    When the graph projection is fully up to date
    Then I expect the node aggregate "lady-eleonode-rootford" to exist
    And I expect this node aggregate to be classified as "root"
    And I expect this node aggregate to be of type "Neos.ContentRepository:Root"
    And I expect this node aggregate to be unnamed
    And I expect this node aggregate to occupy dimension space points [[]]
    And I expect this node aggregate to cover dimension space points [{"language":"mul"},{"language":"de"}]
    And I expect this node aggregate to disable dimension space points []
    And I expect this node aggregate to have no parent node aggregates
    And I expect this node aggregate to have no child node aggregates

    And I expect the graph projection to consist of exactly 1 node
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect this node to be classified as "root"
    And I expect this node to be of type "Neos.ContentRepository:Root"
    And I expect this node to be unnamed
    And I expect this node to have no properties

    When I am in dimension space point {"language":"mul"}
    Then I expect the subgraph projection to consist of exactly 1 node
    And I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to be classified as "root"
    And I expect this node to have no parent node
    And I expect this node to have no child nodes
    And I expect this node to have no preceding siblings
    And I expect this node to have no succeeding siblings
    And I expect this node to have no references
    And I expect this node to not be referenced

    When I am in dimension space point {"language":"de"}
    Then I expect the subgraph projection to consist of exactly 1 node
    And I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}


  Scenario: Adding a dimension and updating the root node works
    When the graph projection is fully up to date
    Given I have the following content dimensions:
      | Identifier | Values      | Generalizations |
      | language   | mul, de, en |                 |

    # in "en", the root node does not exist.
    When I am in dimension space point {"language":"en"}
    Then I expect the subgraph projection to consist of exactly 0 nodes
    And I expect node aggregate identifier "lady-eleonode-rootford" to lead to no node

    And the command UpdateRootNodeAggregateDimensions is executed with payload:
      | Key             | Value                    |
      | nodeAggregateId | "lady-eleonode-rootford" |

    Then I expect exactly 3 events to be published on stream "ContentStream:cs-identifier"
    # the updated dimension config is persisted in the event stream
    And event at index 2 is of type "RootNodeAggregateDimensionsWereUpdated" with payload:
      | Key                         | Expected                                                 |
      | contentStreamId             | "cs-identifier"                                          |
      | nodeAggregateId             | "lady-eleonode-rootford"                                 |
      | coveredDimensionSpacePoints | [{"language":"mul"},{"language":"de"},{"language":"en"}] |
    And event metadata at index 1 is:
      | Key | Expected |

    When the graph projection is fully up to date
    Then I expect the node aggregate "lady-eleonode-rootford" to exist
    And I expect this node aggregate to be classified as "root"
    And I expect this node aggregate to be of type "Neos.ContentRepository:Root"
    And I expect this node aggregate to be unnamed
    And I expect this node aggregate to occupy dimension space points [[]]
    And I expect this node aggregate to cover dimension space points [{"language":"mul"},{"language":"de"},{"language":"en"}]
    And I expect this node aggregate to disable dimension space points []
    And I expect this node aggregate to have no parent node aggregates
    And I expect this node aggregate to have no child node aggregates

    And I expect the graph projection to consist of exactly 1 node
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect this node to be classified as "root"
    And I expect this node to be of type "Neos.ContentRepository:Root"
    And I expect this node to be unnamed
    And I expect this node to have no properties

    When I am in dimension space point {"language":"mul"}
    Then I expect the subgraph projection to consist of exactly 1 node
    And I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to be classified as "root"
    And I expect this node to have no parent node
    And I expect this node to have no child nodes
    And I expect this node to have no preceding siblings
    And I expect this node to have no succeeding siblings
    And I expect this node to have no references
    And I expect this node to not be referenced

    When I am in dimension space point {"language":"de"}
    Then I expect the subgraph projection to consist of exactly 1 node
    And I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}

    # now, the root node exists in "en"
    When I am in dimension space point {"language":"en"}
    Then I expect the subgraph projection to consist of exactly 1 node
    And I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}


  Scenario: Adding a dimension updating the root node, removing dimension, updating the root node, works (dimension gone again)
    When the graph projection is fully up to date
    Given I have the following content dimensions:
      | Identifier | Values      | Generalizations |
      | language   | mul, de, en |                 |
    And the command UpdateRootNodeAggregateDimensions is executed with payload:
      | Key             | Value                    |
      | nodeAggregateId | "lady-eleonode-rootford" |
    And the graph projection is fully up to date

    # now, the root node exists in "en"
    When I am in dimension space point {"language":"en"}
    Then I expect the subgraph projection to consist of exactly 1 nodes
    And I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}

    # again, remove "en"
    Given I have the following content dimensions:
      | Identifier | Values   | Generalizations |
      | language   | mul, de, |                 |
    And the command UpdateRootNodeAggregateDimensions is executed with payload:
      | Key             | Value                    |
      | nodeAggregateId | "lady-eleonode-rootford" |
    And the graph projection is fully up to date

    # now, the root node should not exist anymore in "en"
    When I am in dimension space point {"language":"en"}
    Then I expect the subgraph projection to consist of exactly 0 nodes
    And I expect node aggregate identifier "lady-eleonode-rootford" to lead to no node
