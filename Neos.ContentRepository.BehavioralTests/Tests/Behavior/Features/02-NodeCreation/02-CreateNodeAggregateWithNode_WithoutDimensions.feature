@fixtures @adapters=DoctrineDBAL,Postgres
Feature: Create node aggregate with node

  As a user of the CR I want to create a new externally referencable node aggregate of a specific type with an initial node
  in a specific dimension space point.

  This is the tale of venerable root node aggregate Lady Eleonode Rootford already persistent in the content graph
  and its soon-to-be descendants

  Background:
    Given I have no content dimensions
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes': []
    """
    And the event RootWorkspaceWasCreated was published with payload:
      | Key                        | Value                                  |
      | workspaceName              | "live"                                 |
      | workspaceTitle             | "Live"                                 |
      | workspaceDescription       | "The live workspace"                   |
      | initiatingUserIdentifier   | "00000000-0000-0000-0000-000000000000" |
      | newContentStreamIdentifier | "cs-identifier"                        |
    And I am in content stream "cs-identifier"
    And I am in dimension space point {}
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                     | Value                         |
      | nodeAggregateIdentifier | "lady-eleonode-rootford"      |
      | nodeTypeName            | "Neos.ContentRepository:Root" |
    And the graph projection is fully up to date

  Scenario: Create node aggregate with initial node without auto-created child nodes
    Given I have the following additional NodeTypes configuration:
    """
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
    When the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateIdentifier    | nodeName   | parentNodeAggregateIdentifier | nodeTypeName                                                 | initialPropertyValues    |
      | sir-david-nodenborough     | node       | lady-eleonode-rootford        | Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes | {"text": "initial text"} |
      | nody-mc-nodeface           | child-node | sir-david-nodenborough        | Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes | {}                       |
      | sir-nodeward-nodington-iii | esquire    | lady-eleonode-rootford        | Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes | {}                       |

    Then I expect exactly 5 events to be published on stream "Neos.ContentRepository:ContentStream:cs-identifier"
    And event at index 2 is of type "NodeAggregateWithNodeWasCreated" with payload:
      | Key                           | Expected                                                                                                        |
      | contentStreamIdentifier       | "cs-identifier"                                                                                                 |
      | nodeAggregateIdentifier       | "sir-david-nodenborough"                                                                                        |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes"                                                  |
      | originDimensionSpacePoint     | []                                                                                                              |
      | coveredDimensionSpacePoints   | [[]]                                                                                                            |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                                                                                        |
      | nodeName                      | "node"                                                                                                          |
      | initialPropertyValues         | {"defaultText": {"value": "my default", "type": "string"}, "text": {"value": "initial text", "type": "string"}} |
      | nodeAggregateClassification   | "regular"                                                                                                       |
    And event at index 3 is of type "NodeAggregateWithNodeWasCreated" with payload:
      | Key                           | Expected                                                       |
      | contentStreamIdentifier       | "cs-identifier"                                                |
      | nodeAggregateIdentifier       | "nody-mc-nodeface"                                             |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes" |
      | originDimensionSpacePoint     | []                                                             |
      | coveredDimensionSpacePoints   | [[]]                                                           |
      | parentNodeAggregateIdentifier | "sir-david-nodenborough"                                       |
      | nodeName                      | "child-node"                                                   |
      | initialPropertyValues         | {"defaultText": {"value": "my default", "type": "string"}}     |
      | nodeAggregateClassification   | "regular"                                                      |
    And event at index 4 is of type "NodeAggregateWithNodeWasCreated" with payload:
      | Key                           | Expected                                                       |
      | contentStreamIdentifier       | "cs-identifier"                                                |
      | nodeAggregateIdentifier       | "sir-nodeward-nodington-iii"                                   |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes" |
      | originDimensionSpacePoint     | []                                                             |
      | coveredDimensionSpacePoints   | [[]]                                                           |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                                       |
      | nodeName                      | "esquire"                                                      |
      | initialPropertyValues         | {"defaultText": {"value": "my default", "type": "string"}}     |
      | nodeAggregateClassification   | "regular"                                                      |

    And I expect the node aggregate "lady-eleonode-rootford" to exist
    And I expect this node aggregate to be classified as "root"
    And I expect this node aggregate to be of type "Neos.ContentRepository:Root"
    And I expect this node aggregate to be unnamed
    And I expect this node aggregate to occupy dimension space points [[]]
    And I expect this node aggregate to cover dimension space points [[]]
    And I expect this node aggregate to disable dimension space points []
    And I expect this node aggregate to have no parent node aggregates
    And I expect this node aggregate to have the child node aggregates ["sir-david-nodenborough", "sir-nodeward-nodington-iii"]

    And I expect the node aggregate "sir-david-nodenborough" to exist
    And I expect this node aggregate to be classified as "regular"
    And I expect this node aggregate to be of type "Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes"
    And I expect this node aggregate to be named "node"
    And I expect this node aggregate to occupy dimension space points [[]]
    And I expect this node aggregate to cover dimension space points [[]]
    And I expect this node aggregate to disable dimension space points []
    And I expect this node aggregate to have the parent node aggregates ["lady-eleonode-rootford"]
    And I expect this node aggregate to have the child node aggregates ["nody-mc-nodeface"]

    And I expect the node aggregate "nody-mc-nodeface" to exist
    And I expect this node aggregate to be classified as "regular"
    And I expect this node aggregate to be of type "Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes"
    And I expect this node aggregate to be named "child-node"
    And I expect this node aggregate to occupy dimension space points [[]]
    And I expect this node aggregate to cover dimension space points [[]]
    And I expect this node aggregate to disable dimension space points []
    And I expect this node aggregate to have the parent node aggregates ["sir-david-nodenborough"]
    And I expect this node aggregate to have no child node aggregates

    And I expect the node aggregate "sir-nodeward-nodington-iii" to exist
    And I expect this node aggregate to be classified as "regular"
    And I expect this node aggregate to be of type "Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes"
    And I expect this node aggregate to be named "esquire"
    And I expect this node aggregate to occupy dimension space points [[]]
    And I expect this node aggregate to cover dimension space points [[]]
    And I expect this node aggregate to disable dimension space points []
    And I expect this node aggregate to have the parent node aggregates ["lady-eleonode-rootford"]
    And I expect this node aggregate to have no child node aggregates

    And I expect the graph projection to consist of exactly 4 nodes
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect this node to be classified as "root"
    And I expect this node to be of type "Neos.ContentRepository:Root"
    And I expect this node to be unnamed
    And I expect this node to have no properties
    And I expect a node identified by cs-identifier;sir-david-nodenborough;{} to exist in the content graph
    And I expect this node to be classified as "regular"
    And I expect this node to be of type "Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes"
    And I expect this node to be named "node"
    And I expect this node to have the following properties:
      | Key         | Value          |
      | defaultText | "my default"   |
      | text        | "initial text" |
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{} to exist in the content graph
    And I expect this node to be classified as "regular"
    And I expect this node to be of type "Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes"
    And I expect this node to be named "child-node"
    And I expect this node to have the following properties:
      | Key         | Value        |
      | defaultText | "my default" |
    And I expect a node identified by cs-identifier;sir-nodeward-nodington-iii;{} to exist in the content graph
    And I expect this node to be classified as "regular"
    And I expect this node to be of type "Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes"
    And I expect this node to be named "esquire"
    And I expect this node to have the following properties:
      | Key         | Value        |
      | defaultText | "my default" |

    When I am in content stream "cs-identifier" and dimension space point {}
    Then I expect the subgraph projection to consist of exactly 4 nodes
    And I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no parent node
    And I expect this node to have the following child nodes:
      | Name    | NodeDiscriminator                           |
      | node    | cs-identifier;sir-david-nodenborough;{}     |
      | esquire | cs-identifier;sir-nodeward-nodington-iii;{} |
    And I expect this node to have no siblings
    And I expect this node to have no references
    And I expect this node to not be referenced
    And I expect this node to have no preceding siblings
    And I expect this node to have no succeeding siblings

    And I expect node aggregate identifier "sir-david-nodenborough" and node path "node" to lead to node cs-identifier;sir-david-nodenborough;{}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name       | NodeDiscriminator                 |
      | child-node | cs-identifier;nody-mc-nodeface;{} |
    And I expect this node to have the following siblings:
      | NodeDiscriminator                           |
      | cs-identifier;sir-nodeward-nodington-iii;{} |
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                           |
      | cs-identifier;sir-nodeward-nodington-iii;{} |

    And I expect node aggregate identifier "nody-mc-nodeface" and node path "node/child-node" to lead to node cs-identifier;nody-mc-nodeface;{}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{}
    And I expect this node to have no child nodes
    And I expect this node to have no siblings
    And I expect this node to have no preceding siblings
    And I expect this node to have no succeeding siblings

    And I expect node aggregate identifier "sir-nodeward-nodington-iii" and node path "esquire" to lead to node cs-identifier;sir-nodeward-nodington-iii;{}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no child nodes
    And I expect this node to have the following siblings:
      | NodeDiscriminator                       |
      | cs-identifier;sir-david-nodenborough;{} |
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                       |
      | cs-identifier;sir-david-nodenborough;{} |
    And I expect this node to have no succeeding siblings

  Scenario: Create node aggregate with initial node without auto-created child nodes before another one
    Given the command CreateNodeAggregateWithNodeAndSerializedProperties is executed with payload:
      | Key                           | Value                                                          |
      | nodeAggregateIdentifier       | "sir-david-nodenborough"                                       |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes" |
      | originDimensionSpacePoint     | {}                                                             |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                                       |
      | nodeName                      | "node"                                                         |
    And the graph projection is fully up to date
    And the command CreateNodeAggregateWithNodeAndSerializedProperties is executed with payload:
      | Key                                      | Value                                                          |
      | nodeAggregateIdentifier                  | "sir-nodeward-nodington-iii"                                   |
      | nodeTypeName                             | "Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes" |
      | originDimensionSpacePoint                | {}                                                             |
      | parentNodeAggregateIdentifier            | "lady-eleonode-rootford"                                       |
      | nodeName                                 | "esquire"                                                      |
      | succeedingSiblingNodeAggregateIdentifier | "sir-david-nodenborough"                                       |

    Then I expect exactly 4 events to be published on stream "Neos.ContentRepository:ContentStream:cs-identifier"
    And event at index 3 is of type "NodeAggregateWithNodeWasCreated" with payload:
      | Key                               | Expected                                                       |
      | contentStreamIdentifier           | "cs-identifier"                                                |
      | nodeAggregateIdentifier           | "sir-nodeward-nodington-iii"                                   |
      | nodeTypeName                      | "Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes" |
      | originDimensionSpacePoint         | []                                                             |
      | coveredDimensionSpacePoints       | [[]]                                                           |
      | parentNodeAggregateIdentifier     | "lady-eleonode-rootford"                                       |
      | nodeName                          | "esquire"                                                      |
      | initialPropertyValues             | []                                                             |
      | nodeAggregateClassification       | "regular"                                                      |
      | succeedingNodeAggregateIdentifier | "sir-david-nodenborough"                                       |

    When the graph projection is fully up to date
    And I am in content stream "cs-identifier" and dimension space point {}
    And I expect node aggregate identifier "sir-nodeward-nodington-iii" and node path "esquire" to lead to node cs-identifier;sir-nodeward-nodington-iii;{}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no child nodes
    And I expect this node to have the following siblings:
      | NodeDiscriminator                       |
      | cs-identifier;sir-david-nodenborough;{} |
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                       |
      | cs-identifier;sir-david-nodenborough;{} |

  Scenario: Create node aggregate with node with tethered child nodes
    Given I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Testing:SubSubNode':
      properties:
        text:
          defaultValue: 'my sub sub default'
          type: string
    'Neos.ContentRepository.Testing:SubNode':
      childNodes:
        grandchild-node:
          type: 'Neos.ContentRepository.Testing:SubSubNode'
      properties:
        text:
          defaultValue: 'my sub default'
          type: string
    'Neos.ContentRepository.Testing:NodeWithTetheredChildNodes':
      childNodes:
        child-node:
          type: 'Neos.ContentRepository.Testing:SubNode'
      properties:
        text:
          defaultValue: 'my default'
          type: string
    """

    When the command CreateNodeAggregateWithNodeAndSerializedProperties is executed with payload:
      | Key                                        | Value                                                                             |
      | nodeAggregateIdentifier                    | "sir-david-nodenborough"                                                          |
      | nodeTypeName                               | "Neos.ContentRepository.Testing:NodeWithTetheredChildNodes"                       |
      | originDimensionSpacePoint                  | {}                                                                                |
      | parentNodeAggregateIdentifier              | "lady-eleonode-rootford"                                                          |
      | nodeName                                   | "node"                                                                            |
      | tetheredDescendantNodeAggregateIdentifiers | {"child-node": "nody-mc-nodeface", "child-node/grandchild-node": "nodimus-prime"} |
    And the graph projection is fully up to date

    Then I expect exactly 5 events to be published on stream "Neos.ContentRepository:ContentStream:cs-identifier"
    And event at index 2 is of type "NodeAggregateWithNodeWasCreated" with payload:
      | Key                           | Expected                                                    |
      | contentStreamIdentifier       | "cs-identifier"                                             |
      | nodeAggregateIdentifier       | "sir-david-nodenborough"                                    |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:NodeWithTetheredChildNodes" |
      | originDimensionSpacePoint     | []                                                          |
      | coveredDimensionSpacePoints   | [[]]                                                        |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                                    |
      | nodeName                      | "node"                                                      |
      | initialPropertyValues         | {"text": {"value": "my default", "type": "string"}}         |
      | nodeAggregateClassification   | "regular"                                                   |
    And event at index 3 is of type "NodeAggregateWithNodeWasCreated" with payload:
      | Key                           | Expected                                                |
      | contentStreamIdentifier       | "cs-identifier"                                         |
      | nodeAggregateIdentifier       | "nody-mc-nodeface"                                      |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:SubNode"                |
      | originDimensionSpacePoint     | []                                                      |
      | coveredDimensionSpacePoints   | [[]]                                                    |
      | parentNodeAggregateIdentifier | "sir-david-nodenborough"                                |
      | nodeName                      | "child-node"                                            |
      | initialPropertyValues         | {"text": {"value": "my sub default", "type": "string"}} |
      | nodeAggregateClassification   | "tethered"                                              |
    And event at index 4 is of type "NodeAggregateWithNodeWasCreated" with payload:
      | Key                           | Expected                                                    |
      | contentStreamIdentifier       | "cs-identifier"                                             |
      | nodeAggregateIdentifier       | "nodimus-prime"                                             |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:SubSubNode"                 |
      | originDimensionSpacePoint     | []                                                          |
      | coveredDimensionSpacePoints   | [[]]                                                        |
      | parentNodeAggregateIdentifier | "nody-mc-nodeface"                                          |
      | nodeName                      | "grandchild-node"                                           |
      | initialPropertyValues         | {"text": {"value": "my sub sub default", "type": "string"}} |
      | nodeAggregateClassification   | "tethered"                                                  |

    And I expect the node aggregate "lady-eleonode-rootford" to exist
    And I expect this node aggregate to be classified as "root"
    And I expect this node aggregate to be of type "Neos.ContentRepository:Root"
    And I expect this node aggregate to be unnamed
    And I expect this node aggregate to occupy dimension space points [[]]
    And I expect this node aggregate to cover dimension space points [[]]
    And I expect this node aggregate to disable dimension space points []
    And I expect this node aggregate to have no parent node aggregates
    And I expect this node aggregate to have the child node aggregates ["sir-david-nodenborough"]

    And I expect the node aggregate "sir-david-nodenborough" to exist
    And I expect this node aggregate to be classified as "regular"
    And I expect this node aggregate to be of type "Neos.ContentRepository.Testing:NodeWithTetheredChildNodes"
    And I expect this node aggregate to be named "node"
    And I expect this node aggregate to occupy dimension space points [[]]
    And I expect this node aggregate to cover dimension space points [[]]
    And I expect this node aggregate to have the parent node aggregates ["lady-eleonode-rootford"]
    And I expect this node aggregate to have the child node aggregates ["nody-mc-nodeface"]

    And I expect the node aggregate "nody-mc-nodeface" to exist
    And I expect this node aggregate to be classified as "tethered"
    And I expect this node aggregate to be of type "Neos.ContentRepository.Testing:SubNode"
    And I expect this node aggregate to be named "child-node"
    And I expect this node aggregate to occupy dimension space points [[]]
    And I expect this node aggregate to cover dimension space points [[]]
    And I expect this node aggregate to disable dimension space points []
    And I expect this node aggregate to have the parent node aggregates ["sir-david-nodenborough"]
    And I expect this node aggregate to have the child node aggregates ["nodimus-prime"]

    And I expect the node aggregate "nodimus-prime" to exist
    And I expect this node aggregate to be classified as "tethered"
    And I expect this node aggregate to be of type "Neos.ContentRepository.Testing:SubSubNode"
    And I expect this node aggregate to be named "grandchild-node"
    And I expect this node aggregate to occupy dimension space points [[]]
    And I expect this node aggregate to cover dimension space points [[]]
    And I expect this node aggregate to disable dimension space points []
    And I expect this node aggregate to have the parent node aggregates ["nody-mc-nodeface"]
    And I expect this node aggregate to have no child node aggregates

    And I expect the graph projection to consist of exactly 4 nodes
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect this node to be classified as "root"
    And I expect this node to be of type "Neos.ContentRepository:Root"
    And I expect this node to be unnamed
    And I expect this node to have no properties

    And I expect a node identified by cs-identifier;sir-david-nodenborough;{} to exist in the content graph
    And I expect this node to be classified as "regular"
    And I expect this node to be of type "Neos.ContentRepository.Testing:NodeWithTetheredChildNodes"
    And I expect this node to be named "node"
    And I expect this node to have the following properties:
      | Key  | Value        |
      | text | "my default" |
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{} to exist in the content graph
    And I expect this node to be classified as "tethered"
    And I expect this node to be of type "Neos.ContentRepository.Testing:SubNode"
    And I expect this node to be named "child-node"
    And I expect this node to have the following properties:
      | Key  | Value            |
      | text | "my sub default" |
    And I expect a node identified by cs-identifier;nodimus-prime;{} to exist in the content graph
    And I expect this node to be classified as "tethered"
    And I expect this node to be of type "Neos.ContentRepository.Testing:SubSubNode"
    And I expect this node to be named "grandchild-node"
    And I expect this node to have the following properties:
      | Key  | Value                |
      | text | "my sub sub default" |

    When I am in content stream "cs-identifier" and dimension space point []
    And I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no parent node
    And I expect this node to have the following child nodes:
      | Name | NodeDiscriminator                       |
      | node | cs-identifier;sir-david-nodenborough;{} |
    And I expect this node to have no siblings
    And I expect this node to have no preceding siblings
    And I expect this node to have no succeeding siblings
    And I expect this node to have no references
    And I expect this node to not be referenced

    And I expect node aggregate identifier "sir-david-nodenborough" and node path "node" to lead to node cs-identifier;sir-david-nodenborough;{}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name       | NodeDiscriminator                 |
      | child-node | cs-identifier;nody-mc-nodeface;{} |
    And I expect this node to have no siblings
    And I expect this node to have no preceding siblings
    And I expect this node to have no succeeding siblings
    And I expect this node to have no references
    And I expect this node to not be referenced

    And I expect node aggregate identifier "nody-mc-nodeface" and node path "node/child-node" to lead to node cs-identifier;nody-mc-nodeface;{}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{}
    And I expect this node to have the following child nodes:
      | Name            | NodeDiscriminator              |
      | grandchild-node | cs-identifier;nodimus-prime;{} |
    And I expect this node to have no siblings
    And I expect this node to have no preceding siblings
    And I expect this node to have no succeeding siblings
    And I expect this node to have no references
    And I expect this node to not be referenced

    And I expect node aggregate identifier "nodimus-prime" and node path "node/child-node/grandchild-node" to lead to node cs-identifier;nodimus-prime;{}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{}
    And I expect this node to have no child nodes
    And I expect this node to have no siblings
    And I expect this node to have no preceding siblings
    And I expect this node to have no succeeding siblings
    And I expect this node to have no references
    And I expect this node to not be referenced
