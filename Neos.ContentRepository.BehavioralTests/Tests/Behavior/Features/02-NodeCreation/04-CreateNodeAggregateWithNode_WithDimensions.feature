@contentrepository @adapters=DoctrineDBAL,Postgres
Feature: Create node aggregate with node

  As a user of the CR I want to create a new externally referencable node aggregate of a specific type with a node
  in a specific dimension space point.

  Background:
    Given using the following content dimensions:
      | Identifier | Values           | Generalizations       |
      | language   | mul, de, en, gsw | gsw->de->mul, en->mul |
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes':
      properties:
        defaultText:
          defaultValue: 'my default'
          type: string
        text:
          type: string
        nullText:
          type: string
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
    And I am in the active content stream of workspace "live"
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the graph projection is fully up to date

  Scenario:  Create node aggregate with initial node with content dimensions
    When the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId               | originDimensionSpacePoint | nodeName            | parentNodeAggregateId  | succeedingSiblingNodeAggregateId | nodeTypeName                                                 | initialPropertyValues    |
      | sir-david-nodenborough        | {"language":"mul"}        | node                | lady-eleonode-rootford |                                  | Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes | {"text": "initial text"} |
      | nody-mc-nodeface              | {"language":"de"}         | child-node          | sir-david-nodenborough |                                  | Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes | {}                       |
      | nody-mc-nodeface-the-eldest   | {"language":"gsw"}        | eldest-child-node   | sir-david-nodenborough | nody-mc-nodeface                 | Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes | {}                       |
      | nody-mc-nodeface-the-younger  | {"language":"gsw"}        | younger-child-node  | sir-david-nodenborough |                                  | Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes | {}                       |
      | nody-mc-nodeface-the-elder    | {"language":"de"}         | elder-child-node    | sir-david-nodenborough | nody-mc-nodeface                 | Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes | {}                       |
      | nody-mc-nodeface-the-youngest | {"language":"de"}         | youngest-child-node | sir-david-nodenborough |                                  | Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes | {}                       |
      | sir-nodeward-nodington-iii    | {"language":"en"}         | esquire             | lady-eleonode-rootford |                                  | Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes | {}                       |

    Then I expect exactly 9 events to be published on stream "ContentStream:cs-identifier"
    And event at index 2 is of type "NodeAggregateWithNodeWasCreated" with payload:
      | Key                         | Expected                                                                                                        |
      | contentStreamId             | "cs-identifier"                                                                                                 |
      | nodeAggregateId             | "sir-david-nodenborough"                                                                                        |
      | nodeTypeName                | "Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes"                                                  |
      | originDimensionSpacePoint   | {"language":"mul"}                                                                                              |
      | coveredDimensionSpacePoints | [{"language":"mul"},{"language":"de"},{"language":"en"},{"language":"gsw"}]                                     |
      | parentNodeAggregateId       | "lady-eleonode-rootford"                                                                                        |
      | nodeName                    | "node"                                                                                                          |
      | initialPropertyValues       | {"defaultText": {"value": "my default", "type": "string"}, "text": {"value": "initial text", "type": "string"}} |
      | nodeAggregateClassification | "regular"                                                                                                       |
    And event at index 3 is of type "NodeAggregateWithNodeWasCreated" with payload:
      | Key                         | Expected                                                       |
      | contentStreamId             | "cs-identifier"                                                |
      | nodeAggregateId             | "nody-mc-nodeface"                                             |
      | nodeTypeName                | "Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes" |
      | originDimensionSpacePoint   | {"language":"de"}                                              |
      | coveredDimensionSpacePoints | [{"language":"de"},{"language":"gsw"}]                         |
      | parentNodeAggregateId       | "sir-david-nodenborough"                                       |
      | nodeName                    | "child-node"                                                   |
      | initialPropertyValues       | {"defaultText": {"value": "my default", "type": "string"}}     |
      | nodeAggregateClassification | "regular"                                                      |
    And event at index 4 is of type "NodeAggregateWithNodeWasCreated" with payload:
      | Key                         | Expected                                                       |
      | contentStreamId             | "cs-identifier"                                                |
      | nodeAggregateId             | "nody-mc-nodeface-the-eldest"                                  |
      | nodeTypeName                | "Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes" |
      | originDimensionSpacePoint   | {"language":"gsw"}                                             |
      | coveredDimensionSpacePoints | [{"language":"gsw"}]                                           |
      | parentNodeAggregateId       | "sir-david-nodenborough"                                       |
      | succeedingNodeAggregateId   | "nody-mc-nodeface"                                             |
      | nodeName                    | "eldest-child-node"                                            |
      | initialPropertyValues       | {"defaultText": {"value": "my default", "type": "string"}}     |
      | nodeAggregateClassification | "regular"                                                      |
    And event at index 5 is of type "NodeAggregateWithNodeWasCreated" with payload:
      | Key                         | Expected                                                       |
      | contentStreamId             | "cs-identifier"                                                |
      | nodeAggregateId             | "nody-mc-nodeface-the-younger"                                 |
      | nodeTypeName                | "Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes" |
      | originDimensionSpacePoint   | {"language":"gsw"}                                             |
      | coveredDimensionSpacePoints | [{"language":"gsw"}]                                           |
      | parentNodeAggregateId       | "sir-david-nodenborough"                                       |
      | nodeName                    | "younger-child-node"                                           |
      | initialPropertyValues       | {"defaultText": {"value": "my default", "type": "string"}}     |
      | nodeAggregateClassification | "regular"                                                      |
    And event at index 6 is of type "NodeAggregateWithNodeWasCreated" with payload:
      | Key                         | Expected                                                       |
      | contentStreamId             | "cs-identifier"                                                |
      | nodeAggregateId             | "nody-mc-nodeface-the-elder"                                   |
      | nodeTypeName                | "Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes" |
      | originDimensionSpacePoint   | {"language":"de"}                                              |
      | coveredDimensionSpacePoints | [{"language":"de"},{"language":"gsw"}]                         |
      | parentNodeAggregateId       | "sir-david-nodenborough"                                       |
      | succeedingNodeAggregateId   | "nody-mc-nodeface"                                             |
      | nodeName                    | "elder-child-node"                                             |
      | initialPropertyValues       | {"defaultText": {"value": "my default", "type": "string"}}     |
      | nodeAggregateClassification | "regular"                                                      |
    And event at index 7 is of type "NodeAggregateWithNodeWasCreated" with payload:
      | Key                         | Expected                                                       |
      | contentStreamId             | "cs-identifier"                                                |
      | nodeAggregateId             | "nody-mc-nodeface-the-youngest"                                |
      | nodeTypeName                | "Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes" |
      | originDimensionSpacePoint   | {"language":"de"}                                              |
      | coveredDimensionSpacePoints | [{"language":"de"},{"language":"gsw"}]                         |
      | parentNodeAggregateId       | "sir-david-nodenborough"                                       |
      | nodeName                    | "youngest-child-node"                                          |
      | initialPropertyValues       | {"defaultText": {"value": "my default", "type": "string"}}     |
      | nodeAggregateClassification | "regular"                                                      |
    And event at index 8 is of type "NodeAggregateWithNodeWasCreated" with payload:
      | Key                         | Expected                                                       |
      | contentStreamId             | "cs-identifier"                                                |
      | nodeAggregateId             | "sir-nodeward-nodington-iii"                                   |
      | nodeTypeName                | "Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes" |
      | originDimensionSpacePoint   | {"language":"en"}                                              |
      | coveredDimensionSpacePoints | [{"language":"en"}]                                            |
      | parentNodeAggregateId       | "lady-eleonode-rootford"                                       |
      | nodeName                    | "esquire"                                                      |
      | initialPropertyValues       | {"defaultText": {"value": "my default", "type": "string"}}     |
      | nodeAggregateClassification | "regular"                                                      |

    And I expect the node aggregate "lady-eleonode-rootford" to exist
    And I expect this node aggregate to have the child node aggregates ["sir-david-nodenborough", "sir-nodeward-nodington-iii"]

    And I expect the node aggregate "sir-david-nodenborough" to exist
    And I expect this node aggregate to occupy dimension space points [{"language":"mul"}]
    And I expect this node aggregate to cover dimension space points [{"language":"mul"},{"language":"de"},{"language":"en"},{"language":"gsw"}]
    And I expect this node aggregate to disable dimension space points []
    And I expect this node aggregate to have the parent node aggregates ["lady-eleonode-rootford"]
    And I expect this node aggregate to have the child node aggregates ["nody-mc-nodeface-the-eldest","nody-mc-nodeface-the-elder","nody-mc-nodeface","nody-mc-nodeface-the-younger","nody-mc-nodeface-the-youngest"]

    And I expect the node aggregate "nody-mc-nodeface-the-eldest" to exist
    And I expect this node aggregate to occupy dimension space points [{"language":"gsw"}]
    And I expect this node aggregate to cover dimension space points [{"language":"gsw"}]
    And I expect this node aggregate to disable dimension space points []
    And I expect this node aggregate to have the parent node aggregates ["sir-david-nodenborough"]
    And I expect this node aggregate to have no child node aggregates
    And I expect this node aggregate to be named "eldest-child-node"

    And I expect the node aggregate "nody-mc-nodeface-the-elder" to exist
    And I expect this node aggregate to occupy dimension space points [{"language":"de"}]
    And I expect this node aggregate to cover dimension space points [{"language":"de"},{"language":"gsw"}]
    And I expect this node aggregate to disable dimension space points []
    And I expect this node aggregate to have the parent node aggregates ["sir-david-nodenborough"]
    And I expect this node aggregate to have no child node aggregates
    And I expect this node aggregate to be named "elder-child-node"

    And I expect the node aggregate "nody-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"language":"de"}]
    And I expect this node aggregate to cover dimension space points [{"language":"de"},{"language":"gsw"}]
    And I expect this node aggregate to disable dimension space points []
    And I expect this node aggregate to have the parent node aggregates ["sir-david-nodenborough"]
    And I expect this node aggregate to have no child node aggregates
    And I expect this node aggregate to be named "child-node"

    And I expect the node aggregate "nody-mc-nodeface-the-younger" to exist
    And I expect this node aggregate to occupy dimension space points [{"language":"gsw"}]
    And I expect this node aggregate to cover dimension space points [{"language":"gsw"}]
    And I expect this node aggregate to disable dimension space points []
    And I expect this node aggregate to have the parent node aggregates ["sir-david-nodenborough"]
    And I expect this node aggregate to have no child node aggregates
    And I expect this node aggregate to be named "younger-child-node"

    And I expect the node aggregate "nody-mc-nodeface-the-youngest" to exist
    And I expect this node aggregate to occupy dimension space points [{"language":"de"}]
    And I expect this node aggregate to cover dimension space points [{"language":"de"},{"language":"gsw"}]
    And I expect this node aggregate to disable dimension space points []
    And I expect this node aggregate to have the parent node aggregates ["sir-david-nodenborough"]
    And I expect this node aggregate to have no child node aggregates
    And I expect this node aggregate to be named "youngest-child-node"

    And I expect the node aggregate "sir-nodeward-nodington-iii" to exist
    And I expect this node aggregate to occupy dimension space points [{"language":"en"}]
    And I expect this node aggregate to cover dimension space points [{"language":"en"}]
    And I expect this node aggregate to disable dimension space points []
    And I expect this node aggregate to have the parent node aggregates ["lady-eleonode-rootford"]
    And I expect this node aggregate to have no child node aggregates
    And I expect this node aggregate to be named "esquire"

    And I expect the graph projection to consist of exactly 8 nodes
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect this node to be classified as "root"
    And I expect this node to be of type "Neos.ContentRepository:Root"
    And I expect this node to be unnamed
    And I expect this node to have no properties
    And I expect a node identified by cs-identifier;sir-david-nodenborough;{"language":"mul"} to exist in the content graph
    And I expect this node to be classified as "regular"
    And I expect this node to be of type "Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes"
    And I expect this node to be named "node"
    And I expect this node to have the following properties:
      | Key         | Value          |
      | defaultText | "my default"   |
      | text        | "initial text" |
    And I expect a node identified by cs-identifier;nody-mc-nodeface-the-eldest;{"language":"gsw"} to exist in the content graph
    And I expect this node to be classified as "regular"
    And I expect this node to be of type "Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes"
    And I expect this node to be named "eldest-child-node"
    And I expect this node to have the following properties:
      | Key         | Value        |
      | defaultText | "my default" |
    And I expect a node identified by cs-identifier;nody-mc-nodeface-the-elder;{"language":"de"} to exist in the content graph
    And I expect this node to be classified as "regular"
    And I expect this node to be of type "Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes"
    And I expect this node to be named "elder-child-node"
    And I expect this node to have the following properties:
      | Key         | Value        |
      | defaultText | "my default" |
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"language":"de"} to exist in the content graph
    And I expect this node to be classified as "regular"
    And I expect this node to be of type "Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes"
    And I expect this node to be named "child-node"
    And I expect this node to have the following properties:
      | Key         | Value        |
      | defaultText | "my default" |
    And I expect a node identified by cs-identifier;nody-mc-nodeface-the-younger;{"language":"gsw"} to exist in the content graph
    And I expect this node to be classified as "regular"
    And I expect this node to be of type "Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes"
    And I expect this node to be named "younger-child-node"
    And I expect this node to have the following properties:
      | Key         | Value        |
      | defaultText | "my default" |
    And I expect a node identified by cs-identifier;nody-mc-nodeface-the-youngest;{"language":"de"} to exist in the content graph
    And I expect this node to be classified as "regular"
    And I expect this node to be of type "Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes"
    And I expect this node to be named "youngest-child-node"
    And I expect this node to have the following properties:
      | Key         | Value        |
      | defaultText | "my default" |
    And I expect a node identified by cs-identifier;sir-nodeward-nodington-iii;{"language":"en"} to exist in the content graph
    And I expect this node to be classified as "regular"
    And I expect this node to be of type "Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes"
    And I expect this node to be named "esquire"
    And I expect this node to have the following properties:
      | Key         | Value        |
      | defaultText | "my default" |


    When I am in dimension space point {"language":"mul"}
    Then I expect the subgraph projection to consist of exactly 2 nodes
    And I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no parent node
    And I expect this node to have the following child nodes:
      | Name | NodeDiscriminator                                       |
      | node | cs-identifier;sir-david-nodenborough;{"language":"mul"} |
    And I expect this node to have no preceding siblings
    And I expect this node to have no succeeding siblings
    And I expect this node to have no references
    And I expect this node to not be referenced

    And I expect node aggregate identifier "sir-david-nodenborough" and node path "node" to lead to node cs-identifier;sir-david-nodenborough;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no child nodes
    And I expect this node to have no preceding siblings
    And I expect this node to have no succeeding siblings
    And I expect this node to have no references
    And I expect this node to not be referenced

    And I expect node aggregate identifier "nody-mc-nodeface-the-eldest" and node path "node/eldest-child-node" to lead to no node
    And I expect node aggregate identifier "nody-mc-nodeface-the-elder" and node path "node/elder-child-node" to lead to no node
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "node/child-node" to lead to no node
    And I expect node aggregate identifier "nody-mc-nodeface-the-younger" and node path "node/younger-child-node" to lead to no node
    And I expect node aggregate identifier "nody-mc-nodeface-the-youngest" and node path "node/youngest-child-node" to lead to no node
    And I expect node aggregate identifier "sir-nodeward-nodington-iii" and node path "esquire" to lead to no node


    When I am in dimension space point {"language":"de"}
    Then I expect the subgraph projection to consist of exactly 5 nodes
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name | NodeDiscriminator                                       |
      | node | cs-identifier;sir-david-nodenborough;{"language":"mul"} |

    And I expect node aggregate identifier "sir-david-nodenborough" and node path "node" to lead to node cs-identifier;sir-david-nodenborough;{"language":"mul"}
    And I expect this node to have the following child nodes:
      | Name               | NodeDiscriminator                                            |
      | elder-child-node   | cs-identifier;nody-mc-nodeface-the-elder;{"language":"de"}   |
      | child-node         | cs-identifier;nody-mc-nodeface;{"language":"de"}             |
      | youngest-child-node | cs-identifier;nody-mc-nodeface-the-youngest;{"language":"de"} |
    And I expect this node to have no preceding siblings
    And I expect this node to have no succeeding siblings
    And I expect this node to have no references
    And I expect this node to not be referenced

    And I expect node aggregate identifier "nody-mc-nodeface-the-elder" and node path "node/elder-child-node" to lead to node cs-identifier;nody-mc-nodeface-the-elder;{"language":"de"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language":"mul"}
    And I expect this node to have no child nodes
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                            |
      | cs-identifier;nody-mc-nodeface;{"language":"de"}             |
      | cs-identifier;nody-mc-nodeface-the-youngest;{"language":"de"} |
    And I expect this node to have no references
    And I expect this node to not be referenced

    And I expect node aggregate identifier "nody-mc-nodeface" and node path "node/child-node" to lead to node cs-identifier;nody-mc-nodeface;{"language":"de"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language":"mul"}
    And I expect this node to have no child nodes
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                          |
      | cs-identifier;nody-mc-nodeface-the-elder;{"language":"de"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                            |
      | cs-identifier;nody-mc-nodeface-the-youngest;{"language":"de"} |
    And I expect this node to have no references
    And I expect this node to not be referenced

    And I expect node aggregate identifier "nody-mc-nodeface-the-youngest" and node path "node/youngest-child-node" to lead to node cs-identifier;nody-mc-nodeface-the-youngest;{"language":"de"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language":"mul"}
    And I expect this node to have no child nodes
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                          |
      | cs-identifier;nody-mc-nodeface;{"language":"de"}           |
      | cs-identifier;nody-mc-nodeface-the-elder;{"language":"de"} |
    And I expect this node to have no succeeding siblings
    And I expect this node to have no references
    And I expect this node to not be referenced

    And I expect node aggregate identifier "nody-mc-nodeface-the-eldest" and node path "node/eldest-child-node" to lead to no node
    And I expect node aggregate identifier "nody-mc-nodeface-the-younger" and node path "node/younger-child-node" to lead to no node
    And I expect node aggregate identifier "sir-nodeward-nodington-iii" and node path "esquire" to lead to no node


    When I am in dimension space point {"language":"gsw"}
    Then I expect the subgraph projection to consist of exactly 7 nodes
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name | NodeDiscriminator                                       |
      | node | cs-identifier;sir-david-nodenborough;{"language":"mul"} |

    And I expect node aggregate identifier "sir-david-nodenborough" and node path "node" to lead to node cs-identifier;sir-david-nodenborough;{"language":"mul"}
    And I expect this node to have the following child nodes:
      | Name                | NodeDiscriminator                                             |
      | eldest-child-node   | cs-identifier;nody-mc-nodeface-the-eldest;{"language":"gsw"}  |
      | elder-child-node    | cs-identifier;nody-mc-nodeface-the-elder;{"language":"de"}    |
      | child-node          | cs-identifier;nody-mc-nodeface;{"language":"de"}              |
      | younger-child-node  | cs-identifier;nody-mc-nodeface-the-younger;{"language":"gsw"} |
      | youngest-child-node | cs-identifier;nody-mc-nodeface-the-youngest;{"language":"de"} |
    And I expect this node to have no preceding siblings
    And I expect this node to have no succeeding siblings
    And I expect this node to have no references
    And I expect this node to not be referenced

    And I expect node aggregate identifier "nody-mc-nodeface-the-eldest" and node path "node/eldest-child-node" to lead to node cs-identifier;nody-mc-nodeface-the-eldest;{"language":"gsw"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language":"mul"}
    And I expect this node to have no child nodes
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;nody-mc-nodeface-the-elder;{"language":"de"}    |
      | cs-identifier;nody-mc-nodeface;{"language":"de"}              |
      | cs-identifier;nody-mc-nodeface-the-younger;{"language":"gsw"} |
      | cs-identifier;nody-mc-nodeface-the-youngest;{"language":"de"} |
    And I expect this node to have no references
    And I expect this node to not be referenced

    And I expect node aggregate identifier "nody-mc-nodeface-the-elder" and node path "node/elder-child-node" to lead to node cs-identifier;nody-mc-nodeface-the-elder;{"language":"de"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language":"mul"}
    And I expect this node to have no child nodes
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                            |
      | cs-identifier;nody-mc-nodeface-the-eldest;{"language":"gsw"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;nody-mc-nodeface;{"language":"de"}              |
      | cs-identifier;nody-mc-nodeface-the-younger;{"language":"gsw"} |
      | cs-identifier;nody-mc-nodeface-the-youngest;{"language":"de"} |
    And I expect this node to have no references
    And I expect this node to not be referenced

    And I expect node aggregate identifier "nody-mc-nodeface" and node path "node/child-node" to lead to node cs-identifier;nody-mc-nodeface;{"language":"de"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language":"mul"}
    And I expect this node to have no child nodes
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                            |
      | cs-identifier;nody-mc-nodeface-the-elder;{"language":"de"}   |
      | cs-identifier;nody-mc-nodeface-the-eldest;{"language":"gsw"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;nody-mc-nodeface-the-younger;{"language":"gsw"} |
      | cs-identifier;nody-mc-nodeface-the-youngest;{"language":"de"} |
    And I expect this node to have no references
    And I expect this node to not be referenced

    And I expect node aggregate identifier "nody-mc-nodeface-the-younger" and node path "node/younger-child-node" to lead to node cs-identifier;nody-mc-nodeface-the-younger;{"language":"gsw"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language":"mul"}
    And I expect this node to have no child nodes
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                            |
      | cs-identifier;nody-mc-nodeface;{"language":"de"}             |
      | cs-identifier;nody-mc-nodeface-the-elder;{"language":"de"}   |
      | cs-identifier;nody-mc-nodeface-the-eldest;{"language":"gsw"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;nody-mc-nodeface-the-youngest;{"language":"de"} |
    And I expect this node to have no references
    And I expect this node to not be referenced

    And I expect node aggregate identifier "nody-mc-nodeface-the-youngest" and node path "node/youngest-child-node" to lead to node cs-identifier;nody-mc-nodeface-the-youngest;{"language":"de"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language":"mul"}
    And I expect this node to have no child nodes
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;nody-mc-nodeface-the-younger;{"language":"gsw"} |
      | cs-identifier;nody-mc-nodeface;{"language":"de"}              |
      | cs-identifier;nody-mc-nodeface-the-elder;{"language":"de"}    |
      | cs-identifier;nody-mc-nodeface-the-eldest;{"language":"gsw"}  |
    And I expect this node to have no succeeding siblings
    And I expect this node to have no references
    And I expect this node to not be referenced

    And I expect node aggregate identifier "sir-nodeward-nodington-iii" and node path "esquire" to lead to no node


    When I am in dimension space point {"language":"en"}
    Then I expect the subgraph projection to consist of exactly 3 nodes
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name    | NodeDiscriminator                                          |
      | node    | cs-identifier;sir-david-nodenborough;{"language":"mul"}    |
      | esquire | cs-identifier;sir-nodeward-nodington-iii;{"language":"en"} |

    And I expect node aggregate identifier "sir-david-nodenborough" and node path "node" to lead to node cs-identifier;sir-david-nodenborough;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no child nodes
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                          |
      | cs-identifier;sir-nodeward-nodington-iii;{"language":"en"} |
    And I expect this node to have no references
    And I expect this node to not be referenced

    And I expect node aggregate identifier "nody-mc-nodeface-the-elder" and node path "node/elder-child-node" to lead to no node
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "node/child-node" to lead to no node
    And I expect node aggregate identifier "nody-mc-nodeface-the-younger" and node path "node/younger-child-node" to lead to no node

    And I expect node aggregate identifier "sir-nodeward-nodington-iii" and node path "esquire" to lead to node cs-identifier;sir-nodeward-nodington-iii;{"language":"en"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no child nodes
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;sir-david-nodenborough;{"language":"mul"} |
    And I expect this node to have no succeeding siblings
    And I expect this node to have no references
    And I expect this node to not be referenced
