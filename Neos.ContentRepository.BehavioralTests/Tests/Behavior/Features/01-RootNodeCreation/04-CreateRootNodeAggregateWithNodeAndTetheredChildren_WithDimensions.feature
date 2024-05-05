@contentrepository @adapters=DoctrineDBAL
Feature: Create a root node aggregate with tethered children

  As a user of the CR I want to create a new root node aggregate with an initial node and tethered children.

  These are the test cases with dimensions involved

  Background:
    Given using the following content dimensions:
      | Identifier | Values             | Generalizations    |
      | language   | de, en, gsw, en_US | en_US->en, gsw->de |
    And using the following node types:
    """yaml
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
    'Neos.ContentRepository.Testing:RootWithTetheredChildNodes':
      superTypes:
        'Neos.ContentRepository:Root': true
      childNodes:
        child-node:
          type: 'Neos.ContentRepository.Testing:SubNode'
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                  | Value                |
      | workspaceName        | "live"               |
      | workspaceTitle       | "Live"               |
      | workspaceDescription | "The live workspace" |
      | newContentStreamId   | "cs-identifier"      |
    And the graph projection is fully up to date
    And I am in the active content stream of workspace "live"
    And I am user identified by "initiating-user-identifier"

  Scenario: Create root node with tethered children
    When the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                                | Value                                                                             |
      | nodeAggregateId                    | "lady-eleonode-rootford"                                                          |
      | nodeTypeName                       | "Neos.ContentRepository.Testing:RootWithTetheredChildNodes"                       |
      | tetheredDescendantNodeAggregateIds | {"child-node": "nody-mc-nodeface", "child-node/grandchild-node": "nodimus-prime"} |
    And the graph projection is fully up to date

    Then I expect exactly 6 events to be published on stream "ContentStream:cs-identifier"
    And event at index 1 is of type "RootNodeAggregateWithNodeWasCreated" with payload:
      | Key                         | Expected                                                                             |
      | contentStreamId             | "cs-identifier"                                                                      |
      | nodeAggregateId             | "lady-eleonode-rootford"                                                             |
      | nodeTypeName                | "Neos.ContentRepository.Testing:RootWithTetheredChildNodes"                          |
      | coveredDimensionSpacePoints | [{"language": "de"}, {"language": "en"}, {"language": "gsw"}, {"language": "en_US"}] |
      | nodeAggregateClassification | "root"                                                                               |
    And event at index 2 is of type "NodeAggregateWithNodeWasCreated" with payload:
      | Key                           | Expected                                                                                                                               |
      | contentStreamId               | "cs-identifier"                                                                                                                        |
      | nodeAggregateId               | "nody-mc-nodeface"                                                                                                                     |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:SubNode"                                                                                               |
      | originDimensionSpacePoint     | {"language": "de"}                                                                                                                     |
      | succeedingSiblingsForCoverage | [{"dimensionSpacePoint":{"language": "de"},"nodeAggregateId":null},{"dimensionSpacePoint":{"language": "gsw"},"nodeAggregateId":null}] |
      | parentNodeAggregateId         | "lady-eleonode-rootford"                                                                                                               |
      | nodeName                      | "child-node"                                                                                                                           |
      | initialPropertyValues         | {"text": {"value": "my sub default", "type": "string"}}                                                                                |
      | nodeAggregateClassification   | "tethered"                                                                                                                             |
    And event at index 3 is of type "NodeAggregateWithNodeWasCreated" with payload:
      | Key                           | Expected                                                                                                                               |
      | contentStreamId               | "cs-identifier"                                                                                                                        |
      | nodeAggregateId               | "nodimus-prime"                                                                                                                        |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:SubSubNode"                                                                                            |
      | originDimensionSpacePoint     | {"language": "de"}                                                                                                                     |
      | succeedingSiblingsForCoverage | [{"dimensionSpacePoint":{"language": "de"},"nodeAggregateId":null},{"dimensionSpacePoint":{"language": "gsw"},"nodeAggregateId":null}] |
      | parentNodeAggregateId         | "nody-mc-nodeface"                                                                                                                     |
      | nodeName                      | "grandchild-node"                                                                                                                      |
      | initialPropertyValues         | {"text": {"value": "my sub sub default", "type": "string"}}                                                                            |
      | nodeAggregateClassification   | "tethered"                                                                                                                             |
    And event at index 4 is of type "NodeAggregateWithNodeWasCreated" with payload:
      | Key                           | Expected                                                                                                                                 |
      | contentStreamId               | "cs-identifier"                                                                                                                          |
      | nodeAggregateId               | "nody-mc-nodeface"                                                                                                                       |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:SubNode"                                                                                                 |
      | originDimensionSpacePoint     | {"language": "en"}                                                                                                                       |
      | succeedingSiblingsForCoverage | [{"dimensionSpacePoint":{"language": "en"},"nodeAggregateId":null},{"dimensionSpacePoint":{"language": "en_US"},"nodeAggregateId":null}] |
      | parentNodeAggregateId         | "lady-eleonode-rootford"                                                                                                                 |
      | nodeName                      | "child-node"                                                                                                                             |
      | initialPropertyValues         | {"text": {"value": "my sub default", "type": "string"}}                                                                                  |
      | nodeAggregateClassification   | "tethered"                                                                                                                               |
    And event at index 5 is of type "NodeAggregateWithNodeWasCreated" with payload:
      | Key                           | Expected                                                                                                                                 |
      | contentStreamId               | "cs-identifier"                                                                                                                          |
      | nodeAggregateId               | "nodimus-prime"                                                                                                                          |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:SubSubNode"                                                                                              |
      | originDimensionSpacePoint     | {"language": "en"}                                                                                                                       |
      | succeedingSiblingsForCoverage | [{"dimensionSpacePoint":{"language": "en"},"nodeAggregateId":null},{"dimensionSpacePoint":{"language": "en_US"},"nodeAggregateId":null}] |
      | parentNodeAggregateId         | "nody-mc-nodeface"                                                                                                                       |
      | nodeName                      | "grandchild-node"                                                                                                                        |
      | initialPropertyValues         | {"text": {"value": "my sub sub default", "type": "string"}}                                                                              |
      | nodeAggregateClassification   | "tethered"                                                                                                                               |

    And I expect the node aggregate "lady-eleonode-rootford" to exist
    And I expect this node aggregate to be classified as "root"
    And I expect this node aggregate to be of type "Neos.ContentRepository.Testing:RootWithTetheredChildNodes"
    And I expect this node aggregate to be unnamed
    And I expect this node aggregate to occupy dimension space points [{}]
    And I expect this node aggregate to cover dimension space points [{"language": "de"}, {"language": "en"}, {"language": "gsw"}, {"language": "en_US"}]
    And I expect this node aggregate to disable dimension space points []
    And I expect this node aggregate to have no parent node aggregates
    And I expect this node aggregate to have the child node aggregates ["nody-mc-nodeface"]

    And I expect the node aggregate "nody-mc-nodeface" to exist
    And I expect this node aggregate to be classified as "tethered"
    And I expect this node aggregate to be of type "Neos.ContentRepository.Testing:SubNode"
    And I expect this node aggregate to be named "child-node"
    And I expect this node aggregate to occupy dimension space points [{"language": "de"}, {"language": "en"}]
    And I expect this node aggregate to cover dimension space points [{"language": "de"}, {"language": "en"}, {"language": "gsw"}, {"language": "en_US"}]
    And I expect this node aggregate to disable dimension space points []
    And I expect this node aggregate to have the parent node aggregates ["lady-eleonode-rootford"]
    And I expect this node aggregate to have the child node aggregates ["nodimus-prime"]

    And I expect the node aggregate "nodimus-prime" to exist
    And I expect this node aggregate to be classified as "tethered"
    And I expect this node aggregate to be of type "Neos.ContentRepository.Testing:SubSubNode"
    And I expect this node aggregate to be named "grandchild-node"
    And I expect this node aggregate to occupy dimension space points [{"language": "de"}, {"language": "en"}]
    And I expect this node aggregate to cover dimension space points [{"language": "de"}, {"language": "en"}, {"language": "gsw"}, {"language": "en_US"}]
    And I expect this node aggregate to disable dimension space points []
    And I expect this node aggregate to have the parent node aggregates ["nody-mc-nodeface"]
    And I expect this node aggregate to have no child node aggregates

    And I expect the graph projection to consist of exactly 5 nodes
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect this node to be classified as "root"
    And I expect this node to be of type "Neos.ContentRepository.Testing:RootWithTetheredChildNodes"
    And I expect this node to be unnamed
    And I expect this node to have no properties

    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"language": "de"} to exist in the content graph
    And I expect this node to be classified as "tethered"
    And I expect this node to be of type "Neos.ContentRepository.Testing:SubNode"
    And I expect this node to be named "child-node"
    And I expect this node to have the following properties:
      | Key  | Value            |
      | text | "my sub default" |

    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"language": "en"} to exist in the content graph
    And I expect this node to be classified as "tethered"
    And I expect this node to be of type "Neos.ContentRepository.Testing:SubNode"
    And I expect this node to be named "child-node"
    And I expect this node to have the following properties:
      | Key  | Value            |
      | text | "my sub default" |

    And I expect a node identified by cs-identifier;nodimus-prime;{"language": "de"} to exist in the content graph
    And I expect this node to be classified as "tethered"
    And I expect this node to be of type "Neos.ContentRepository.Testing:SubSubNode"
    And I expect this node to be named "grandchild-node"
    And I expect this node to have the following properties:
      | Key  | Value                |
      | text | "my sub sub default" |

    And I expect a node identified by cs-identifier;nodimus-prime;{"language": "en"} to exist in the content graph
    And I expect this node to be classified as "tethered"
    And I expect this node to be of type "Neos.ContentRepository.Testing:SubSubNode"
    And I expect this node to be named "grandchild-node"
    And I expect this node to have the following properties:
      | Key  | Value                |
      | text | "my sub sub default" |

    When I am in the active content stream of workspace "live" and dimension space point {"language": "de"}
    And I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no parent node
    And I expect this node to have the following child nodes:
      | Name       | NodeDiscriminator                                 |
      | child-node | cs-identifier;nody-mc-nodeface;{"language": "de"} |
    And I expect this node to have no preceding siblings
    And I expect this node to have no succeeding siblings
    And I expect this node to have no references
    And I expect this node to not be referenced

    And I expect node aggregate identifier "nody-mc-nodeface" and node path "child-node" to lead to node cs-identifier;nody-mc-nodeface;{"language": "de"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name            | NodeDiscriminator                              |
      | grandchild-node | cs-identifier;nodimus-prime;{"language": "de"} |
    And I expect this node to have no preceding siblings
    And I expect this node to have no succeeding siblings
    And I expect this node to have no references
    And I expect this node to not be referenced

    And I expect node aggregate identifier "nodimus-prime" and node path "child-node/grandchild-node" to lead to node cs-identifier;nodimus-prime;{"language": "de"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"language": "de"}
    And I expect this node to have no child nodes
    And I expect this node to have no preceding siblings
    And I expect this node to have no succeeding siblings
    And I expect this node to have no references
    And I expect this node to not be referenced

    When I am in the active content stream of workspace "live" and dimension space point {"language": "gsw"}
    And I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no parent node
    And I expect this node to have the following child nodes:
      | Name       | NodeDiscriminator                                 |
      | child-node | cs-identifier;nody-mc-nodeface;{"language": "de"} |
    And I expect this node to have no preceding siblings
    And I expect this node to have no succeeding siblings
    And I expect this node to have no references
    And I expect this node to not be referenced

    And I expect node aggregate identifier "nody-mc-nodeface" and node path "child-node" to lead to node cs-identifier;nody-mc-nodeface;{"language": "de"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name            | NodeDiscriminator                              |
      | grandchild-node | cs-identifier;nodimus-prime;{"language": "de"} |
    And I expect this node to have no preceding siblings
    And I expect this node to have no succeeding siblings
    And I expect this node to have no references
    And I expect this node to not be referenced

    And I expect node aggregate identifier "nodimus-prime" and node path "child-node/grandchild-node" to lead to node cs-identifier;nodimus-prime;{"language": "de"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"language": "de"}
    And I expect this node to have no child nodes
    And I expect this node to have no preceding siblings
    And I expect this node to have no succeeding siblings
    And I expect this node to have no references
    And I expect this node to not be referenced


    When I am in the active content stream of workspace "live" and dimension space point {"language": "en"}
    And I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no parent node
    And I expect this node to have the following child nodes:
      | Name       | NodeDiscriminator                                 |
      | child-node | cs-identifier;nody-mc-nodeface;{"language": "en"} |
    And I expect this node to have no preceding siblings
    And I expect this node to have no succeeding siblings
    And I expect this node to have no references
    And I expect this node to not be referenced

    And I expect node aggregate identifier "nody-mc-nodeface" and node path "child-node" to lead to node cs-identifier;nody-mc-nodeface;{"language": "en"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name            | NodeDiscriminator                              |
      | grandchild-node | cs-identifier;nodimus-prime;{"language": "en"} |
    And I expect this node to have no preceding siblings
    And I expect this node to have no succeeding siblings
    And I expect this node to have no references
    And I expect this node to not be referenced

    And I expect node aggregate identifier "nodimus-prime" and node path "child-node/grandchild-node" to lead to node cs-identifier;nodimus-prime;{"language": "en"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"language": "en"}
    And I expect this node to have no child nodes
    And I expect this node to have no preceding siblings
    And I expect this node to have no succeeding siblings
    And I expect this node to have no references
    And I expect this node to not be referenced


    When I am in the active content stream of workspace "live" and dimension space point {"language": "en_US"}
    And I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no parent node
    And I expect this node to have the following child nodes:
      | Name       | NodeDiscriminator                                 |
      | child-node | cs-identifier;nody-mc-nodeface;{"language": "en"} |
    And I expect this node to have no preceding siblings
    And I expect this node to have no succeeding siblings
    And I expect this node to have no references
    And I expect this node to not be referenced

    And I expect node aggregate identifier "nody-mc-nodeface" and node path "child-node" to lead to node cs-identifier;nody-mc-nodeface;{"language": "en"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name            | NodeDiscriminator                              |
      | grandchild-node | cs-identifier;nodimus-prime;{"language": "en"} |
    And I expect this node to have no preceding siblings
    And I expect this node to have no succeeding siblings
    And I expect this node to have no references
    And I expect this node to not be referenced

    And I expect node aggregate identifier "nodimus-prime" and node path "child-node/grandchild-node" to lead to node cs-identifier;nodimus-prime;{"language": "en"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"language": "en"}
    And I expect this node to have no child nodes
    And I expect this node to have no preceding siblings
    And I expect this node to have no succeeding siblings
    And I expect this node to have no references
    And I expect this node to not be referenced

