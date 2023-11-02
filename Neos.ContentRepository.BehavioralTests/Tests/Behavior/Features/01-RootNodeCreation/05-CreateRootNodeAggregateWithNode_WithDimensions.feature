@contentrepository @adapters=DoctrineDBAL,Postgres
Feature: Create a root node aggregate

  As a user of the CR I want to create a new root node aggregate with an initial node.

  This is the tale of venerable root node aggregate Lady Eleonode Rootford already persistent in the content graph for quite some time
  and Nody McNodeface, a new root node aggregate to be added.

  Background:
    Given using the following content dimensions:
      | Identifier | Values           | Generalizations       |
      | language   | mul, de, en, gsw | gsw->de->mul, en->mul |
    And using the following node types:
    """yaml
    'Neos.ContentRepository:AnotherRoot':
      superTypes:
        'Neos.ContentRepository:Root': true
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootWorkspace is executed with payload:
      | Key                  | Value                |
      | workspaceName        | "live"               |
      | workspaceTitle       | "Live"               |
      | workspaceDescription | "The live workspace" |
      | newContentStreamId   | "cs-identifier"      |
    And the graph projection is fully up to date
    And I am in content stream "cs-identifier"

  Scenario: Create the initial root node aggregate using valid payload with dimensions
    When the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |

    Then I expect exactly 2 events to be published on stream "ContentStream:cs-identifier"
    And event at index 1 is of type "RootNodeAggregateWithNodeWasCreated" with payload:
      | Key                         | Expected                                                                    |
      | contentStreamId             | "cs-identifier"                                                             |
      | nodeAggregateId             | "lady-eleonode-rootford"                                                    |
      | nodeTypeName                | "Neos.ContentRepository:Root"                                               |
      | coveredDimensionSpacePoints | [{"language":"mul"},{"language":"de"},{"language":"en"},{"language":"gsw"}] |
      | nodeAggregateClassification | "root"                                                                      |
    And event metadata at index 1 is:
      | Key              | Expected                     |

    When the graph projection is fully up to date
    Then I expect the node aggregate "lady-eleonode-rootford" to exist
    And I expect this node aggregate to be classified as "root"
    And I expect this node aggregate to be of type "Neos.ContentRepository:Root"
    And I expect this node aggregate to be unnamed
    And I expect this node aggregate to occupy dimension space points [[]]
    And I expect this node aggregate to cover dimension space points [{"language":"mul"},{"language":"de"},{"language":"en"},{"language":"gsw"}]
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
    When I am in dimension space point {"language":"gsw"}
    Then I expect the subgraph projection to consist of exactly 1 node
    And I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    When I am in dimension space point {"language":"en"}
    Then I expect the subgraph projection to consist of exactly 1 node
    And I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}

  Scenario: Create a root node aggregate using valid payload without dimensions
    Given the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the graph projection is fully up to date

    When the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "nody-mc-nodeface"            |
      | nodeTypeName    | "Neos.ContentRepository:AnotherRoot" |

    Then I expect exactly 3 events to be published on stream "ContentStream:cs-identifier"
    And event at index 2 is of type "RootNodeAggregateWithNodeWasCreated" with payload:
      | Key                         | Expected                                                                    |
      | contentStreamId             | "cs-identifier"                                                             |
      | nodeAggregateId             | "nody-mc-nodeface"                                                          |
      | nodeTypeName                | "Neos.ContentRepository:AnotherRoot"                                               |
      | coveredDimensionSpacePoints | [{"language":"mul"},{"language":"de"},{"language":"en"},{"language":"gsw"}] |
      | nodeAggregateClassification | "root"                                                                      |

    When the graph projection is fully up to date
    Then I expect the node aggregate "lady-eleonode-rootford" to exist
    And I expect this node aggregate to have no parent node aggregates
    And I expect this node aggregate to have no child node aggregates
    And I expect the node aggregate "nody-mc-nodeface" to exist
    And I expect this node aggregate to be classified as "root"
    And I expect this node aggregate to be of type "Neos.ContentRepository:AnotherRoot"
    And I expect this node aggregate to be unnamed
    And I expect this node aggregate to occupy dimension space points [[]]
    And I expect this node aggregate to cover dimension space points [{"language":"mul"},{"language":"de"},{"language":"en"},{"language":"gsw"}]
    And I expect this node aggregate to disable dimension space points []
    And I expect this node aggregate to have no parent node aggregates
    And I expect this node aggregate to have no child node aggregates

    And I expect the graph projection to consist of exactly 2 nodes
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{} to exist in the content graph
    And I expect this node to be classified as "root"
    And I expect this node to be of type "Neos.ContentRepository:AnotherRoot"
    And I expect this node to be unnamed
    And I expect this node to have no properties

    When I am in dimension space point {"language":"mul"}
    Then I expect the subgraph projection to consist of exactly 2 nodes
    And I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                 |
      | cs-identifier;nody-mc-nodeface;{} |
    And I expect this node to have no references
    And I expect this node to not be referenced

    And I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{}
    And I expect this node to have no parent node
    And I expect this node to have no child nodes
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                       |
      | cs-identifier;lady-eleonode-rootford;{} |
    And I expect this node to have no succeeding siblings
    And I expect this node to have no references
    And I expect this node to not be referenced

    When I am in dimension space point {"language":"de"}
    Then I expect the subgraph projection to consist of exactly 2 nodes
    And I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{}
    When I am in dimension space point {"language":"gsw"}
    Then I expect the subgraph projection to consist of exactly 2 nodes
    And I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{}
    When I am in dimension space point {"language":"en"}
    Then I expect the subgraph projection to consist of exactly 2 nodes
    And I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{}
