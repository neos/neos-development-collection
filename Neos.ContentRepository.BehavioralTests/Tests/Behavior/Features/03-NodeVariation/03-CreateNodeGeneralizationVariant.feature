@contentrepository @adapters=DoctrineDBAL,Postgres
Feature: Create node generalization

  As a user of the CR I want to create a copy of a node within an aggregate to a more general dimension space point.

  Background:
    Given using the following content dimensions:
      | Identifier | Values                              | Generalizations                                                   |
      | example    | rootGeneral, general, source, specB | source -> general -> rootGeneral, specB -> general -> rootGeneral |
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Tethered': []
    'Neos.ContentRepository.Testing:TetheredDocument':
      childNodes:
        tethered:
          type: 'Neos.ContentRepository.Testing:Tethered'
    'Neos.ContentRepository.Testing:Document':
      childNodes:
        tethered-document:
          type: 'Neos.ContentRepository.Testing:TetheredDocument'
    'Neos.ContentRepository.Testing:DocumentWithoutTetheredChildren': []
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootWorkspace is executed with payload:
      | Key                  | Value                |
      | workspaceName        | "live"               |
      | newContentStreamId   | "cs-identifier"      |
    And I am in workspace "live" and dimension space point {"example":"source"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId        | originDimensionSpacePoint | nodeName            | parentNodeAggregateId  | succeedingSiblingNodeAggregateId | nodeTypeName                                                   | tetheredDescendantNodeAggregateIds                                                            |
    # We have to add another node since root nodes have no origin dimension space points and thus cannot be varied.
    # We also need a tethered child node to test that it is reachable from the freshly created generalization variant of the parent
    # and we need a tethered child node of the tethered child node to test that this works recursively
    # Let's create some siblings, both in source and target, to check ordering
      | eldest-mc-nodeface     | {"example":"general"}     | eldest-document     | lady-eleonode-rootford |                                  | Neos.ContentRepository.Testing:DocumentWithoutTetheredChildren | {}                                                                                            |
      | nody-mc-nodeface       | {"example":"source"}      | document            | lady-eleonode-rootford |                                  | Neos.ContentRepository.Testing:Document                        | {"tethered-document": "nodewyn-tetherton", "tethered-document/tethered": "nodimer-tetherton"} |
      | elder-mc-nodeface      | {"example":"source"}      | elder-document      | lady-eleonode-rootford | nody-mc-nodeface                 | Neos.ContentRepository.Testing:DocumentWithoutTetheredChildren | {}                                                                                            |
      | younger-mc-nodeface    | {"example":"general"}     | younger-document    | lady-eleonode-rootford |                                  | Neos.ContentRepository.Testing:DocumentWithoutTetheredChildren | {}                                                                                            |
      | youngest-mc-nodeface   | {"example":"source"}      | youngest-document   | lady-eleonode-rootford |                                  | Neos.ContentRepository.Testing:DocumentWithoutTetheredChildren | {}                                                                                            |
    # ...and we need a non-tethered child node to make sure it is _not_ reachable from the freshly created generalization of the parent
      | invariable-mc-nodeface | {"example":"source"}      | invariable-document | nody-mc-nodeface       |                                  | Neos.ContentRepository.Testing:DocumentWithoutTetheredChildren | {}                                                                                            |

  Scenario: Create generalization of node to dimension space point with further generalization and specializations
    When the command CreateNodeVariant is executed with payload:
      | Key             | Value                 |
      | nodeAggregateId | "nody-mc-nodeface"    |
      | sourceOrigin    | {"example":"source"}  |
      | targetOrigin    | {"example":"general"} |
    And the command CreateNodeVariant is executed with payload:
      | Key             | Value                 |
      | nodeAggregateId | "elder-mc-nodeface"   |
      | sourceOrigin    | {"example":"source"}  |
      | targetOrigin    | {"example":"general"} |
    And the command CreateNodeVariant is executed with payload:
      | Key             | Value                  |
      | nodeAggregateId | "youngest-mc-nodeface" |
      | sourceOrigin    | {"example":"source"}   |
      | targetOrigin    | {"example":"general"}  |

    Then I expect exactly 15 events to be published on stream "ContentStream:cs-identifier"
    And event at index 10 is of type "NodeGeneralizationVariantWasCreated" with payload:
      | Key                       | Expected                                                                                                                                                                    |
      | contentStreamId           | "cs-identifier"                                                                                                                                                             |
      | nodeAggregateId           | "nody-mc-nodeface"                                                                                                                                                          |
      | sourceOrigin              | {"example":"source"}                                                                                                                                                        |
      | generalizationOrigin      | {"example":"general"}                                                                                                                                                       |
      | variantSucceedingSiblings | [{"dimensionSpacePoint":{"example":"general"},"nodeAggregateId":"younger-mc-nodeface"},{"dimensionSpacePoint":{"example":"specB"},"nodeAggregateId":"younger-mc-nodeface"}] |
    And event at index 11 is of type "NodeGeneralizationVariantWasCreated" with payload:
      | Key                       | Expected                                                                                                                                  |
      | contentStreamId           | "cs-identifier"                                                                                                                           |
      | nodeAggregateId           | "nodewyn-tetherton"                                                                                                                       |
      | sourceOrigin              | {"example":"source"}                                                                                                                      |
      | generalizationOrigin      | {"example":"general"}                                                                                                                     |
      | variantSucceedingSiblings | [{"dimensionSpacePoint":{"example":"general"},"nodeAggregateId":null},{"dimensionSpacePoint":{"example":"specB"},"nodeAggregateId":null}] |
    And event at index 12 is of type "NodeGeneralizationVariantWasCreated" with payload:
      | Key                       | Expected                                                                                                                                  |
      | contentStreamId           | "cs-identifier"                                                                                                                           |
      | nodeAggregateId           | "nodimer-tetherton"                                                                                                                       |
      | sourceOrigin              | {"example":"source"}                                                                                                                      |
      | generalizationOrigin      | {"example":"general"}                                                                                                                     |
      | variantSucceedingSiblings | [{"dimensionSpacePoint":{"example":"general"},"nodeAggregateId":null},{"dimensionSpacePoint":{"example":"specB"},"nodeAggregateId":null}] |
    And event at index 13 is of type "NodeGeneralizationVariantWasCreated" with payload:
      | Key                       | Expected                                                                                                                                                              |
      | contentStreamId           | "cs-identifier"                                                                                                                                                       |
      | nodeAggregateId           | "elder-mc-nodeface"                                                                                                                                                   |
      | sourceOrigin              | {"example":"source"}                                                                                                                                                  |
      | generalizationOrigin      | {"example":"general"}                                                                                                                                                 |
      | variantSucceedingSiblings | [{"dimensionSpacePoint":{"example":"general"},"nodeAggregateId":"nody-mc-nodeface"},{"dimensionSpacePoint":{"example":"specB"},"nodeAggregateId":"nody-mc-nodeface"}] |
    And event at index 14 is of type "NodeGeneralizationVariantWasCreated" with payload:
      | Key                       | Expected                                                                                                                                  |
      | contentStreamId           | "cs-identifier"                                                                                                                           |
      | nodeAggregateId           | "youngest-mc-nodeface"                                                                                                                    |
      | sourceOrigin              | {"example":"source"}                                                                                                                      |
      | generalizationOrigin      | {"example":"general"}                                                                                                                     |
      | variantSucceedingSiblings | [{"dimensionSpacePoint":{"example":"general"},"nodeAggregateId":null},{"dimensionSpacePoint":{"example":"specB"},"nodeAggregateId":null}] |

    Then I expect the graph projection to consist of exactly 14 nodes
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect a node identified by cs-identifier;eldest-mc-nodeface;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;elder-mc-nodeface;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;elder-mc-nodeface;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodewyn-tetherton;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodewyn-tetherton;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimer-tetherton;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimer-tetherton;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;younger-mc-nodeface;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;youngest-mc-nodeface;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;youngest-mc-nodeface;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;invariable-mc-nodeface;{"example":"source"} to exist in the content graph

    When I am in workspace "live"
    Then I expect the node aggregate "lady-eleonode-rootford" to exist
    And I expect this node aggregate to occupy dimension space points [{}]
    And I expect this node aggregate to cover dimension space points [{"example":"rootGeneral"},{"example":"general"},{"example":"source"},{"example":"specB"}]

    And I expect the node aggregate "eldest-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"general"}]
    And I expect this node aggregate to cover dimension space points [{"example":"general"},{"example":"source"},{"example":"specB"}]

    And I expect the node aggregate "elder-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"},{"example":"general"}]
    And I expect this node aggregate to cover dimension space points [{"example":"general"},{"example":"source"},{"example":"specB"}]

    And I expect the node aggregate "nody-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"},{"example":"general"}]
    And I expect this node aggregate to cover dimension space points [{"example":"general"},{"example":"source"},{"example":"specB"}]

    And I expect the node aggregate "nodewyn-tetherton" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"},{"example":"general"}]
    And I expect this node aggregate to cover dimension space points [{"example":"general"},{"example":"source"},{"example":"specB"}]

    And I expect the node aggregate "nodimer-tetherton" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"},{"example":"general"}]
    And I expect this node aggregate to cover dimension space points [{"example":"general"},{"example":"source"},{"example":"specB"}]

    And I expect the node aggregate "invariable-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"}]

    And I expect the node aggregate "younger-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"general"}]
    And I expect this node aggregate to cover dimension space points [{"example":"general"},{"example":"source"},{"example":"specB"}]

    And I expect the node aggregate "youngest-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"},{"example":"general"}]
    And I expect this node aggregate to cover dimension space points [{"example":"general"},{"example":"source"},{"example":"specB"}]

    When I am in workspace "live" and dimension space point {"example":"source"}
    Then I expect the subgraph projection to consist of exactly 9 nodes
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name              | NodeDiscriminator                                       |
      | eldest-document   | cs-identifier;eldest-mc-nodeface;{"example":"general"}  |
      | elder-document    | cs-identifier;elder-mc-nodeface;{"example":"source"}    |
      | document          | cs-identifier;nody-mc-nodeface;{"example":"source"}     |
      | younger-document  | cs-identifier;younger-mc-nodeface;{"example":"general"} |
      | youngest-document | cs-identifier;youngest-mc-nodeface;{"example":"source"} |
    And I expect node aggregate identifier "eldest-mc-nodeface" and node path "eldest-document" to lead to node cs-identifier;eldest-mc-nodeface;{"example":"general"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;elder-mc-nodeface;{"example":"source"}    |
      | cs-identifier;nody-mc-nodeface;{"example":"source"}     |
      | cs-identifier;younger-mc-nodeface;{"example":"general"} |
      | cs-identifier;youngest-mc-nodeface;{"example":"source"} |
    And I expect node aggregate identifier "elder-mc-nodeface" and node path "elder-document" to lead to node cs-identifier;elder-mc-nodeface;{"example":"source"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;nody-mc-nodeface;{"example":"source"}     |
      | cs-identifier;younger-mc-nodeface;{"example":"general"} |
      | cs-identifier;youngest-mc-nodeface;{"example":"source"} |
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"source"}
    And I expect this node to have the following child nodes:
      | Name                | NodeDiscriminator                                         |
      | tethered-document   | cs-identifier;nodewyn-tetherton;{"example":"source"}      |
      | invariable-document | cs-identifier;invariable-mc-nodeface;{"example":"source"} |
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;elder-mc-nodeface;{"example":"source"}   |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;younger-mc-nodeface;{"example":"general"} |
      | cs-identifier;youngest-mc-nodeface;{"example":"source"} |
    And I expect node aggregate identifier "nodewyn-tetherton" and node path "document/tethered-document" to lead to node cs-identifier;nodewyn-tetherton;{"example":"source"}
    And I expect node aggregate identifier "nodimer-tetherton" and node path "document/tethered-document/tethered" to lead to node cs-identifier;nodimer-tetherton;{"example":"source"}
    And I expect node aggregate identifier "invariable-mc-nodeface" and node path "document/invariable-document" to lead to node cs-identifier;invariable-mc-nodeface;{"example":"source"}
    And I expect node aggregate identifier "younger-mc-nodeface" and node path "younger-document" to lead to node cs-identifier;younger-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;nody-mc-nodeface;{"example":"source"}    |
      | cs-identifier;elder-mc-nodeface;{"example":"source"}   |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;youngest-mc-nodeface;{"example":"source"} |
    And I expect node aggregate identifier "youngest-mc-nodeface" and node path "youngest-document" to lead to node cs-identifier;youngest-mc-nodeface;{"example":"source"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;younger-mc-nodeface;{"example":"general"} |
      | cs-identifier;nody-mc-nodeface;{"example":"source"}     |
      | cs-identifier;elder-mc-nodeface;{"example":"source"}    |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"}  |
    And I expect this node to have no succeeding siblings

    When I am in workspace "live" and dimension space point {"example":"general"}
    Then I expect the subgraph projection to consist of exactly 8 nodes
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name              | NodeDiscriminator                                        |
      | eldest-document   | cs-identifier;eldest-mc-nodeface;{"example":"general"}   |
      | elder-document    | cs-identifier;elder-mc-nodeface;{"example":"general"}    |
      | document          | cs-identifier;nody-mc-nodeface;{"example":"general"}     |
      | younger-document  | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | youngest-document | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "eldest-mc-nodeface" and node path "eldest-document" to lead to node cs-identifier;eldest-mc-nodeface;{"example":"general"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}    |
      | cs-identifier;nody-mc-nodeface;{"example":"general"}     |
      | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "elder-mc-nodeface" and node path "elder-document" to lead to node cs-identifier;elder-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;nody-mc-nodeface;{"example":"general"}     |
      | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to have the following child nodes:
      | Name              | NodeDiscriminator                                     |
      | tethered-document | cs-identifier;nodewyn-tetherton;{"example":"general"} |
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}  |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "nodewyn-tetherton" and node path "document/tethered-document" to lead to node cs-identifier;nodewyn-tetherton;{"example":"general"}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                     |
      | tethered | cs-identifier;nodimer-tetherton;{"example":"general"} |
    And I expect this node to have no preceding siblings
    And I expect this node to have no succeeding siblings
    And I expect node aggregate identifier "nodimer-tetherton" and node path "document/tethered-document/tethered" to lead to node cs-identifier;nodimer-tetherton;{"example":"general"}
    And I expect node aggregate identifier "younger-mc-nodeface" and node path "younger-document" to lead to node cs-identifier;younger-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;nody-mc-nodeface;{"example":"general"}   |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}  |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "youngest-mc-nodeface" and node path "youngest-document" to lead to node cs-identifier;youngest-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;younger-mc-nodeface;{"example":"general"} |
      | cs-identifier;nody-mc-nodeface;{"example":"general"}    |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}   |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"}  |
    And I expect this node to have no succeeding siblings
    And I expect node aggregate identifier "invariable-mc-nodeface" and node path "document/invariable-document" to lead to no node

    When I am in workspace "live" and dimension space point {"example":"rootGeneral"}
    Then I expect the subgraph projection to consist of exactly 1 nodes
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no child nodes
    And I expect node aggregate identifier "eldest-mc-nodeface" and node path "eldest-document" to lead to no node
    And I expect node aggregate identifier "elder-mc-nodeface" and node path "elder-document" to lead to no node
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document" to lead to no node
    And I expect node aggregate identifier "nodewyn-tetherton" and node path "document/tethered-document" to lead to no node
    And I expect node aggregate identifier "nodimer-tetherton" and node path "document/tethered-document/tethered" to lead to no node
    And I expect node aggregate identifier "younger-mc-nodeface" and node path "younger-document" to lead to no node
    And I expect node aggregate identifier "youngest-mc-nodeface" and node path "youngest-document" to lead to no node
    And I expect node aggregate identifier "invariable-mc-nodeface" and node path "document/invariable-document" to lead to no node

    When I am in workspace "live" and dimension space point {"example":"specB"}
    Then I expect the subgraph projection to consist of exactly 8 nodes
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name              | NodeDiscriminator                                        |
      | eldest-document   | cs-identifier;eldest-mc-nodeface;{"example":"general"}   |
      | elder-document    | cs-identifier;elder-mc-nodeface;{"example":"general"}    |
      | document          | cs-identifier;nody-mc-nodeface;{"example":"general"}     |
      | younger-document  | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | youngest-document | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "eldest-mc-nodeface" and node path "eldest-document" to lead to node cs-identifier;eldest-mc-nodeface;{"example":"general"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}    |
      | cs-identifier;nody-mc-nodeface;{"example":"general"}     |
      | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "elder-mc-nodeface" and node path "elder-document" to lead to node cs-identifier;elder-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;nody-mc-nodeface;{"example":"general"}     |
      | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to have the following child nodes:
      | Name              | NodeDiscriminator                                     |
      | tethered-document | cs-identifier;nodewyn-tetherton;{"example":"general"} |
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}  |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "nodewyn-tetherton" and node path "document/tethered-document" to lead to node cs-identifier;nodewyn-tetherton;{"example":"general"}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                     |
      | tethered | cs-identifier;nodimer-tetherton;{"example":"general"} |
    And I expect this node to have no preceding siblings
    And I expect this node to have no succeeding siblings
    And I expect node aggregate identifier "nodimer-tetherton" and node path "document/tethered-document/tethered" to lead to node cs-identifier;nodimer-tetherton;{"example":"general"}
    And I expect node aggregate identifier "younger-mc-nodeface" and node path "younger-document" to lead to node cs-identifier;younger-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;nody-mc-nodeface;{"example":"general"}   |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}  |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "youngest-mc-nodeface" and node path "youngest-document" to lead to node cs-identifier;youngest-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;younger-mc-nodeface;{"example":"general"} |
      | cs-identifier;nody-mc-nodeface;{"example":"general"}    |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}   |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"}  |
    And I expect this node to have no succeeding siblings
    And I expect node aggregate identifier "invariable-mc-nodeface" and node path "document/invariable-document" to lead to no node

  Scenario: Create generalization of a node to dimension space point with specializations that are partially occupied
    Given the command CreateNodeVariant is executed with payload:
      | Key             | Value                |
      | nodeAggregateId | "nody-mc-nodeface"   |
      | sourceOrigin    | {"example":"source"} |
      | targetOrigin    | {"example":"specB"}  |

    When the command CreateNodeVariant is executed with payload:
      | Key             | Value                 |
      | nodeAggregateId | "nody-mc-nodeface"    |
      | sourceOrigin    | {"example":"source"}  |
      | targetOrigin    | {"example":"general"} |
    Then I expect exactly 16 events to be published on stream "ContentStream:cs-identifier"
    And event at index 10 is of type "NodePeerVariantWasCreated" with payload:
      | Key                    | Expected                                                                              |
      | contentStreamId        | "cs-identifier"                                                                       |
      | nodeAggregateId        | "nody-mc-nodeface"                                                                    |
      | sourceOrigin           | {"example":"source"}                                                                  |
      | peerOrigin             | {"example":"specB"}                                                                   |
      | peerSucceedingSiblings | [{"dimensionSpacePoint":{"example":"specB"},"nodeAggregateId":"younger-mc-nodeface"}] |
    And event at index 11 is of type "NodePeerVariantWasCreated" with payload:
      | Key                    | Expected                                                             |
      | contentStreamId        | "cs-identifier"                                                      |
      | nodeAggregateId        | "nodewyn-tetherton"                                                  |
      | sourceOrigin           | {"example":"source"}                                                 |
      | peerOrigin             | {"example":"specB"}                                                  |
      | peerSucceedingSiblings | [{"dimensionSpacePoint":{"example":"specB"},"nodeAggregateId":null}] |
    And event at index 12 is of type "NodePeerVariantWasCreated" with payload:
      | Key                    | Expected                                                             |
      | contentStreamId        | "cs-identifier"                                                      |
      | nodeAggregateId        | "nodimer-tetherton"                                                  |
      | sourceOrigin           | {"example":"source"}                                                 |
      | peerOrigin             | {"example":"specB"}                                                  |
      | peerSucceedingSiblings | [{"dimensionSpacePoint":{"example":"specB"},"nodeAggregateId":null}] |
    And event at index 13 is of type "NodeGeneralizationVariantWasCreated" with payload:
      | Key                       | Expected                                                                                |
      | contentStreamId           | "cs-identifier"                                                                         |
      | nodeAggregateId           | "nody-mc-nodeface"                                                                      |
      | sourceOrigin              | {"example":"source"}                                                                    |
      | generalizationOrigin      | {"example":"general"}                                                                   |
      | variantSucceedingSiblings | [{"dimensionSpacePoint":{"example":"general"},"nodeAggregateId":"younger-mc-nodeface"}] |
    And event at index 14 is of type "NodeGeneralizationVariantWasCreated" with payload:
      | Key                       | Expected                                                               |
      | contentStreamId           | "cs-identifier"                                                        |
      | nodeAggregateId           | "nodewyn-tetherton"                                                    |
      | sourceOrigin              | {"example":"source"}                                                   |
      | generalizationOrigin      | {"example":"general"}                                                  |
      | variantSucceedingSiblings | [{"dimensionSpacePoint":{"example":"general"},"nodeAggregateId":null}] |
    And event at index 15 is of type "NodeGeneralizationVariantWasCreated" with payload:
      | Key                       | Expected                                                               |
      | contentStreamId           | "cs-identifier"                                                        |
      | nodeAggregateId           | "nodimer-tetherton"                                                    |
      | sourceOrigin              | {"example":"source"}                                                   |
      | generalizationOrigin      | {"example":"general"}                                                  |
      | variantSucceedingSiblings | [{"dimensionSpacePoint":{"example":"general"},"nodeAggregateId":null}] |

    Then I expect the graph projection to consist of exactly 15 nodes
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect a node identified by cs-identifier;eldest-mc-nodeface;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;elder-mc-nodeface;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"example":"specB"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodewyn-tetherton;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodewyn-tetherton;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodewyn-tetherton;{"example":"specB"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimer-tetherton;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimer-tetherton;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimer-tetherton;{"example":"specB"} to exist in the content graph
    And I expect a node identified by cs-identifier;younger-mc-nodeface;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;youngest-mc-nodeface;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;invariable-mc-nodeface;{"example":"source"} to exist in the content graph

    When I am in workspace "live"
    Then I expect the node aggregate "lady-eleonode-rootford" to exist
    And I expect this node aggregate to occupy dimension space points [{}]
    And I expect this node aggregate to cover dimension space points [{"example":"rootGeneral"},{"example":"general"},{"example":"source"},{"example":"specB"}]

    Then I expect the node aggregate "eldest-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"general"}]
    And I expect this node aggregate to cover dimension space points [{"example":"general"},{"example":"source"},{"example":"specB"}]

    Then I expect the node aggregate "elder-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"}]

    And I expect the node aggregate "nody-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"},{"example":"general"},{"example":"specB"}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"},{"example":"general"},{"example":"specB"}]

    And I expect the node aggregate "nodewyn-tetherton" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"},{"example":"general"},{"example":"specB"}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"},{"example":"general"},{"example":"specB"}]

    And I expect the node aggregate "nodimer-tetherton" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"},{"example":"general"},{"example":"specB"}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"},{"example":"general"},{"example":"specB"}]

    Then I expect the node aggregate "youngest-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"}]

    Then I expect the node aggregate "invariable-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"}]

    When I am in workspace "live" and dimension space point {"example":"general"}
    Then I expect the subgraph projection to consist of exactly 6 nodes
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name             | NodeDiscriminator                                       |
      | eldest-document  | cs-identifier;eldest-mc-nodeface;{"example":"general"}  |
      | document         | cs-identifier;nody-mc-nodeface;{"example":"general"}    |
      | younger-document | cs-identifier;younger-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "eldest-mc-nodeface" and node path "eldest-document" to lead to node cs-identifier;eldest-mc-nodeface;{"example":"general"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;nody-mc-nodeface;{"example":"general"}    |
      | cs-identifier;younger-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "elder-mc-nodeface" and node path "elder-document" to lead to no node
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to have the following child nodes:
      | Name              | NodeDiscriminator                                     |
      | tethered-document | cs-identifier;nodewyn-tetherton;{"example":"general"} |
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;younger-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "nodewyn-tetherton" and node path "document/tethered-document" to lead to node cs-identifier;nodewyn-tetherton;{"example":"general"}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                     |
      | tethered | cs-identifier;nodimer-tetherton;{"example":"general"} |
    And I expect node aggregate identifier "nodimer-tetherton" and node path "document/tethered-document/tethered" to lead to node cs-identifier;nodimer-tetherton;{"example":"general"}
    And I expect node aggregate identifier "younger-mc-nodeface" and node path "younger-document" to lead to node cs-identifier;younger-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;nody-mc-nodeface;{"example":"general"}   |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
    And I expect this node to have no succeeding siblings
    And I expect node aggregate identifier "youngest-mc-nodeface" and node path "youngest-document" to lead to no node
    And I expect node aggregate identifier "invariable-mc-nodeface" and node path "document/invariable-document" to lead to no node

    When I am in workspace "live" and dimension space point {"example": "specB"}
    Then I expect the subgraph projection to consist of exactly 6 nodes
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name             | NodeDiscriminator                                       |
      | eldest-document  | cs-identifier;eldest-mc-nodeface;{"example":"general"}  |
      | document         | cs-identifier;nody-mc-nodeface;{"example":"specB"}      |
      | younger-document | cs-identifier;younger-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "eldest-mc-nodeface" and node path "eldest-document" to lead to node cs-identifier;eldest-mc-nodeface;{"example":"general"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;nody-mc-nodeface;{"example":"specB"}      |
      | cs-identifier;younger-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "elder-mc-nodeface" and node path "elder-document" to lead to no node
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"specB"}
    And I expect this node to have the following child nodes:
      | Name              | NodeDiscriminator                                   |
      | tethered-document | cs-identifier;nodewyn-tetherton;{"example":"specB"} |
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;younger-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "nodewyn-tetherton" and node path "document/tethered-document" to lead to node cs-identifier;nodewyn-tetherton;{"example":"specB"}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                   |
      | tethered | cs-identifier;nodimer-tetherton;{"example":"specB"} |
    And I expect node aggregate identifier "nodimer-tetherton" and node path "document/tethered-document/tethered" to lead to node cs-identifier;nodimer-tetherton;{"example":"specB"}
    And I expect node aggregate identifier "younger-mc-nodeface" and node path "younger-document" to lead to node cs-identifier;younger-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;nody-mc-nodeface;{"example":"specB"}     |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
    And I expect this node to have no succeeding siblings
    And I expect node aggregate identifier "youngest-mc-nodeface" and node path "youngest-document" to lead to no node
    And I expect node aggregate identifier "invariable-mc-nodeface" and node path "document/invariable-document" to lead to no node

  Scenario: Create generalization of a node to dimension space point with specializations that are partially covered
    Given the command CreateNodeVariant is executed with payload:
      | Key             | Value                 |
      | nodeAggregateId | "nody-mc-nodeface"    |
      | sourceOrigin    | {"example":"source"}  |
      | targetOrigin    | {"example":"general"} |

    When the command CreateNodeVariant is executed with payload:
      | Key             | Value                |
      | nodeAggregateId | "nody-mc-nodeface"   |
      | sourceOrigin    | {"example":"source"} |
      | targetOrigin    | {"example":"specB"}  |
    Then I expect exactly 16 events to be published on stream "ContentStream:cs-identifier"
    And event at index 10 is of type "NodeGeneralizationVariantWasCreated" with payload:
      | Key                       | Expected                                                                                                                                                                    |
      | contentStreamId           | "cs-identifier"                                                                                                                                                             |
      | nodeAggregateId           | "nody-mc-nodeface"                                                                                                                                                          |
      | sourceOrigin              | {"example":"source"}                                                                                                                                                        |
      | generalizationOrigin      | {"example":"general"}                                                                                                                                                       |
      | variantSucceedingSiblings | [{"dimensionSpacePoint":{"example":"general"},"nodeAggregateId":"younger-mc-nodeface"},{"dimensionSpacePoint":{"example":"specB"},"nodeAggregateId":"younger-mc-nodeface"}] |
    And event at index 11 is of type "NodeGeneralizationVariantWasCreated" with payload:
      | Key                       | Expected                                                                                                                                  |
      | contentStreamId           | "cs-identifier"                                                                                                                           |
      | nodeAggregateId           | "nodewyn-tetherton"                                                                                                                       |
      | sourceOrigin              | {"example":"source"}                                                                                                                      |
      | generalizationOrigin      | {"example":"general"}                                                                                                                     |
      | variantSucceedingSiblings | [{"dimensionSpacePoint":{"example":"general"},"nodeAggregateId":null},{"dimensionSpacePoint":{"example":"specB"},"nodeAggregateId":null}] |
    And event at index 12 is of type "NodeGeneralizationVariantWasCreated" with payload:
      | Key                       | Expected                                                                                                                                  |
      | contentStreamId           | "cs-identifier"                                                                                                                           |
      | nodeAggregateId           | "nodimer-tetherton"                                                                                                                       |
      | sourceOrigin              | {"example":"source"}                                                                                                                      |
      | generalizationOrigin      | {"example":"general"}                                                                                                                     |
      | variantSucceedingSiblings | [{"dimensionSpacePoint":{"example":"general"},"nodeAggregateId":null},{"dimensionSpacePoint":{"example":"specB"},"nodeAggregateId":null}] |
    And event at index 13 is of type "NodePeerVariantWasCreated" with payload:
      | Key                    | Expected                                                                              |
      | contentStreamId        | "cs-identifier"                                                                       |
      | nodeAggregateId        | "nody-mc-nodeface"                                                                    |
      | sourceOrigin           | {"example":"source"}                                                                  |
      | peerOrigin             | {"example":"specB"}                                                                   |
      | peerSucceedingSiblings | [{"dimensionSpacePoint":{"example":"specB"},"nodeAggregateId":"younger-mc-nodeface"}] |
    And event at index 14 is of type "NodePeerVariantWasCreated" with payload:
      | Key                    | Expected                                                             |
      | contentStreamId        | "cs-identifier"                                                      |
      | nodeAggregateId        | "nodewyn-tetherton"                                                  |
      | sourceOrigin           | {"example":"source"}                                                 |
      | peerOrigin             | {"example":"specB"}                                                  |
      | peerSucceedingSiblings | [{"dimensionSpacePoint":{"example":"specB"},"nodeAggregateId":null}] |
    And event at index 15 is of type "NodePeerVariantWasCreated" with payload:
      | Key                    | Expected                                                             |
      | contentStreamId        | "cs-identifier"                                                      |
      | nodeAggregateId        | "nodimer-tetherton"                                                  |
      | sourceOrigin           | {"example":"source"}                                                 |
      | peerOrigin             | {"example":"specB"}                                                  |
      | peerSucceedingSiblings | [{"dimensionSpacePoint":{"example":"specB"},"nodeAggregateId":null}] |

    Then I expect the graph projection to consist of exactly 15 nodes
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect a node identified by cs-identifier;eldest-mc-nodeface;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;elder-mc-nodeface;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"example":"specB"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodewyn-tetherton;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodewyn-tetherton;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodewyn-tetherton;{"example":"specB"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimer-tetherton;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimer-tetherton;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimer-tetherton;{"example":"specB"} to exist in the content graph
    And I expect a node identified by cs-identifier;younger-mc-nodeface;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;youngest-mc-nodeface;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;invariable-mc-nodeface;{"example":"source"} to exist in the content graph

    When I am in workspace "live"
    Then I expect the node aggregate "lady-eleonode-rootford" to exist
    And I expect this node aggregate to occupy dimension space points [{}]
    And I expect this node aggregate to cover dimension space points [{"example":"rootGeneral"},{"example":"general"},{"example":"source"},{"example":"specB"}]

    Then I expect the node aggregate "eldest-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"general"}]
    And I expect this node aggregate to cover dimension space points [{"example":"general"},{"example":"source"},{"example":"specB"}]

    Then I expect the node aggregate "elder-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"}]

    And I expect the node aggregate "nody-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"},{"example":"general"},{"example":"specB"}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"},{"example":"general"},{"example":"specB"}]

    And I expect the node aggregate "nodewyn-tetherton" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"},{"example":"general"},{"example":"specB"}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"},{"example":"general"},{"example":"specB"}]

    And I expect the node aggregate "nodimer-tetherton" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"},{"example":"general"},{"example":"specB"}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"},{"example":"general"},{"example":"specB"}]

    Then I expect the node aggregate "youngest-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"}]

    Then I expect the node aggregate "invariable-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"}]

    When I am in workspace "live" and dimension space point {"example":"general"}
    Then I expect the subgraph projection to consist of exactly 6 nodes
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name             | NodeDiscriminator                                       |
      | eldest-document  | cs-identifier;eldest-mc-nodeface;{"example":"general"}  |
      | document         | cs-identifier;nody-mc-nodeface;{"example":"general"}    |
      | younger-document | cs-identifier;younger-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "eldest-mc-nodeface" and node path "eldest-document" to lead to node cs-identifier;eldest-mc-nodeface;{"example":"general"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;nody-mc-nodeface;{"example":"general"}    |
      | cs-identifier;younger-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "elder-mc-nodeface" and node path "elder-document" to lead to no node
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to have the following child nodes:
      | Name              | NodeDiscriminator                                     |
      | tethered-document | cs-identifier;nodewyn-tetherton;{"example":"general"} |
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;younger-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "nodewyn-tetherton" and node path "document/tethered-document" to lead to node cs-identifier;nodewyn-tetherton;{"example":"general"}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                     |
      | tethered | cs-identifier;nodimer-tetherton;{"example":"general"} |
    And I expect node aggregate identifier "nodimer-tetherton" and node path "document/tethered-document/tethered" to lead to node cs-identifier;nodimer-tetherton;{"example":"general"}
    And I expect node aggregate identifier "younger-mc-nodeface" and node path "younger-document" to lead to node cs-identifier;younger-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;nody-mc-nodeface;{"example":"general"}   |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
    And I expect this node to have no succeeding siblings
    And I expect node aggregate identifier "youngest-mc-nodeface" and node path "youngest-document" to lead to no node
    And I expect node aggregate identifier "invariable-mc-nodeface" and node path "document/invariable-document" to lead to no node

    When I am in workspace "live" and dimension space point {"example": "specB"}
    Then I expect the subgraph projection to consist of exactly 6 nodes
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name             | NodeDiscriminator                                       |
      | eldest-document  | cs-identifier;eldest-mc-nodeface;{"example":"general"}  |
      | document         | cs-identifier;nody-mc-nodeface;{"example":"specB"}      |
      | younger-document | cs-identifier;younger-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "eldest-mc-nodeface" and node path "eldest-document" to lead to node cs-identifier;eldest-mc-nodeface;{"example":"general"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;nody-mc-nodeface;{"example":"specB"}      |
      | cs-identifier;younger-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "elder-mc-nodeface" and node path "elder-document" to lead to no node
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"specB"}
    And I expect this node to have the following child nodes:
      | Name              | NodeDiscriminator                                   |
      | tethered-document | cs-identifier;nodewyn-tetherton;{"example":"specB"} |
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;younger-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "nodewyn-tetherton" and node path "document/tethered-document" to lead to node cs-identifier;nodewyn-tetherton;{"example":"specB"}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                   |
      | tethered | cs-identifier;nodimer-tetherton;{"example":"specB"} |
    And I expect node aggregate identifier "nodimer-tetherton" and node path "document/tethered-document/tethered" to lead to node cs-identifier;nodimer-tetherton;{"example":"specB"}
    And I expect node aggregate identifier "younger-mc-nodeface" and node path "younger-document" to lead to node cs-identifier;younger-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;nody-mc-nodeface;{"example":"specB"}     |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
    And I expect this node to have no succeeding siblings
    And I expect node aggregate identifier "youngest-mc-nodeface" and node path "youngest-document" to lead to no node
    And I expect node aggregate identifier "invariable-mc-nodeface" and node path "document/invariable-document" to lead to no node

  Scenario: Create generalization of a node to a dimension space point that is already covered by a more general generalization
    Given the command CreateNodeVariant is executed with payload:
      | Key             | Value                     |
      | nodeAggregateId | "nody-mc-nodeface"        |
      | sourceOrigin    | {"example":"source"}      |
      | targetOrigin    | {"example":"rootGeneral"} |

    When the command CreateNodeVariant is executed with payload:
      | Key             | Value                 |
      | nodeAggregateId | "nody-mc-nodeface"    |
      | sourceOrigin    | {"example":"source"}  |
      | targetOrigin    | {"example":"general"} |
    Then I expect exactly 16 events to be published on stream "ContentStream:cs-identifier"
    And event at index 10 is of type "NodeGeneralizationVariantWasCreated" with payload:
      | Key                       | Expected                                                                                                                                                                                                                                             |
      | contentStreamId           | "cs-identifier"                                                                                                                                                                                                                                      |
      | nodeAggregateId           | "nody-mc-nodeface"                                                                                                                                                                                                                                   |
      | sourceOrigin              | {"example":"source"}                                                                                                                                                                                                                                 |
      | generalizationOrigin      | {"example":"rootGeneral"}                                                                                                                                                                                                                            |
      | variantSucceedingSiblings | [{"dimensionSpacePoint":{"example":"rootGeneral"},"nodeAggregateId":null},{"dimensionSpacePoint":{"example":"general"},"nodeAggregateId":"younger-mc-nodeface"},{"dimensionSpacePoint":{"example":"specB"},"nodeAggregateId":"younger-mc-nodeface"}] |
    And event at index 11 is of type "NodeGeneralizationVariantWasCreated" with payload:
      | Key                       | Expected                                                                                                                                                                                                           |
      | contentStreamId           | "cs-identifier"                                                                                                                                                                                                    |
      | nodeAggregateId           | "nodewyn-tetherton"                                                                                                                                                                                                |
      | sourceOrigin              | {"example":"source"}                                                                                                                                                                                               |
      | generalizationOrigin      | {"example":"rootGeneral"}                                                                                                                                                                                          |
      | variantSucceedingSiblings | [{"dimensionSpacePoint":{"example":"rootGeneral"},"nodeAggregateId":null},{"dimensionSpacePoint":{"example":"general"},"nodeAggregateId":null},{"dimensionSpacePoint":{"example":"specB"},"nodeAggregateId":null}] |
    And event at index 12 is of type "NodeGeneralizationVariantWasCreated" with payload:
      | Key                       | Expected                                                                                                                                                                                                           |
      | contentStreamId           | "cs-identifier"                                                                                                                                                                                                    |
      | nodeAggregateId           | "nodimer-tetherton"                                                                                                                                                                                                |
      | sourceOrigin              | {"example":"source"}                                                                                                                                                                                               |
      | generalizationOrigin      | {"example":"rootGeneral"}                                                                                                                                                                                          |
      | variantSucceedingSiblings | [{"dimensionSpacePoint":{"example":"rootGeneral"},"nodeAggregateId":null},{"dimensionSpacePoint":{"example":"general"},"nodeAggregateId":null},{"dimensionSpacePoint":{"example":"specB"},"nodeAggregateId":null}] |
    And event at index 13 is of type "NodeGeneralizationVariantWasCreated" with payload:
      | Key                       | Expected                                                                                                                                                                    |
      | contentStreamId           | "cs-identifier"                                                                                                                                                             |
      | nodeAggregateId           | "nody-mc-nodeface"                                                                                                                                                          |
      | sourceOrigin              | {"example":"source"}                                                                                                                                                        |
      | generalizationOrigin      | {"example":"general"}                                                                                                                                                       |
      | variantSucceedingSiblings | [{"dimensionSpacePoint":{"example":"general"},"nodeAggregateId":"younger-mc-nodeface"},{"dimensionSpacePoint":{"example":"specB"},"nodeAggregateId":"younger-mc-nodeface"}] |
    And event at index 14 is of type "NodeGeneralizationVariantWasCreated" with payload:
      | Key                       | Expected                                                                                                                                  |
      | contentStreamId           | "cs-identifier"                                                                                                                           |
      | nodeAggregateId           | "nodewyn-tetherton"                                                                                                                       |
      | sourceOrigin              | {"example":"source"}                                                                                                                      |
      | generalizationOrigin      | {"example":"general"}                                                                                                                     |
      | variantSucceedingSiblings | [{"dimensionSpacePoint":{"example":"general"},"nodeAggregateId":null},{"dimensionSpacePoint":{"example":"specB"},"nodeAggregateId":null}] |
    And event at index 15 is of type "NodeGeneralizationVariantWasCreated" with payload:
      | Key                       | Expected                                                                                                                                  |
      | contentStreamId           | "cs-identifier"                                                                                                                           |
      | nodeAggregateId           | "nodimer-tetherton"                                                                                                                       |
      | sourceOrigin              | {"example":"source"}                                                                                                                      |
      | generalizationOrigin      | {"example":"general"}                                                                                                                     |
      | variantSucceedingSiblings | [{"dimensionSpacePoint":{"example":"general"},"nodeAggregateId":null},{"dimensionSpacePoint":{"example":"specB"},"nodeAggregateId":null}] |

    Then I expect the graph projection to consist of exactly 15 nodes
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect a node identified by cs-identifier;eldest-mc-nodeface;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;elder-mc-nodeface;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"example":"rootGeneral"} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodewyn-tetherton;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodewyn-tetherton;{"example":"rootGeneral"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodewyn-tetherton;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimer-tetherton;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimer-tetherton;{"example":"rootGeneral"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimer-tetherton;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;younger-mc-nodeface;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;youngest-mc-nodeface;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;invariable-mc-nodeface;{"example":"source"} to exist in the content graph

    When I am in workspace "live"
    Then I expect the node aggregate "lady-eleonode-rootford" to exist
    And I expect this node aggregate to occupy dimension space points [{}]
    And I expect this node aggregate to cover dimension space points [{"example":"rootGeneral"},{"example":"general"},{"example":"source"},{"example":"specB"}]

    Then I expect the node aggregate "eldest-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"general"}]
    And I expect this node aggregate to cover dimension space points [{"example":"general"},{"example":"source"},{"example":"specB"}]

    Then I expect the node aggregate "elder-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"}]

    And I expect the node aggregate "nody-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"},{"example":"rootGeneral"},{"example":"general"}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"},{"example":"rootGeneral"},{"example":"general"},{"example":"specB"}]

    And I expect the node aggregate "nodewyn-tetherton" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"},{"example":"rootGeneral"},{"example":"general"}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"},{"example":"rootGeneral"},{"example":"general"},{"example":"specB"}]

    And I expect the node aggregate "nodimer-tetherton" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"},{"example":"rootGeneral"},{"example":"general"}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"},{"example":"rootGeneral"},{"example":"general"},{"example":"specB"}]

    Then I expect the node aggregate "youngest-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"}]

    Then I expect the node aggregate "invariable-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"}]

    When I am in workspace "live" and dimension space point {"example":"rootGeneral"}
    Then I expect the subgraph projection to consist of exactly 4 nodes
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                        |
      | document | cs-identifier;nody-mc-nodeface;{"example":"rootGeneral"} |
    And I expect node aggregate identifier "eldest-mc-nodeface" and node path "eldest-document" to lead to no node
    And I expect node aggregate identifier "elder-mc-nodeface" and node path "elder-document" to lead to no node
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"rootGeneral"}
    And I expect this node to have the following child nodes:
      | Name              | NodeDiscriminator                                         |
      | tethered-document | cs-identifier;nodewyn-tetherton;{"example":"rootGeneral"} |
    And I expect this node to have no preceding siblings
    And I expect this node to have no succeeding siblings
    And I expect node aggregate identifier "nodewyn-tetherton" and node path "document/tethered-document" to lead to node cs-identifier;nodewyn-tetherton;{"example":"rootGeneral"}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                         |
      | tethered | cs-identifier;nodimer-tetherton;{"example":"rootGeneral"} |
    And I expect node aggregate identifier "nodimer-tetherton" and node path "document/tethered-document/tethered" to lead to node cs-identifier;nodimer-tetherton;{"example":"rootGeneral"}
    And I expect node aggregate identifier "younger-mc-nodeface" and node path "younger-document" to lead to no node
    And I expect node aggregate identifier "youngest-mc-nodeface" and node path "youngest-document" to lead to no node
    And I expect node aggregate identifier "invariable-mc-nodeface" and node path "document/invariable-document" to lead to no node

    When I am in workspace "live" and dimension space point {"example": "general"}
    Then I expect the subgraph projection to consist of exactly 6 nodes
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name             | NodeDiscriminator                                       |
      | eldest-document  | cs-identifier;eldest-mc-nodeface;{"example":"general"}  |
      | document         | cs-identifier;nody-mc-nodeface;{"example":"general"}    |
      | younger-document | cs-identifier;younger-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "eldest-mc-nodeface" and node path "eldest-document" to lead to node cs-identifier;eldest-mc-nodeface;{"example":"general"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;nody-mc-nodeface;{"example":"general"}    |
      | cs-identifier;younger-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "elder-mc-nodeface" and node path "elder-document" to lead to no node
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to have the following child nodes:
      | Name              | NodeDiscriminator                                     |
      | tethered-document | cs-identifier;nodewyn-tetherton;{"example":"general"} |
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;younger-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "nodewyn-tetherton" and node path "document/tethered-document" to lead to node cs-identifier;nodewyn-tetherton;{"example":"general"}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                     |
      | tethered | cs-identifier;nodimer-tetherton;{"example":"general"} |
    And I expect node aggregate identifier "nodimer-tetherton" and node path "document/tethered-document/tethered" to lead to node cs-identifier;nodimer-tetherton;{"example":"general"}
    And I expect node aggregate identifier "younger-mc-nodeface" and node path "younger-document" to lead to node cs-identifier;younger-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;nody-mc-nodeface;{"example":"general"}   |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
    And I expect this node to have no succeeding siblings
    And I expect node aggregate identifier "youngest-mc-nodeface" and node path "youngest-document" to lead to no node
    And I expect node aggregate identifier "invariable-mc-nodeface" and node path "document/invariable-document" to lead to no node

    When I am in workspace "live" and dimension space point {"example": "specB"}
    Then I expect the subgraph projection to consist of exactly 6 nodes
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name             | NodeDiscriminator                                       |
      | eldest-document  | cs-identifier;eldest-mc-nodeface;{"example":"general"}  |
      | document         | cs-identifier;nody-mc-nodeface;{"example":"general"}    |
      | younger-document | cs-identifier;younger-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "eldest-mc-nodeface" and node path "eldest-document" to lead to node cs-identifier;eldest-mc-nodeface;{"example":"general"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;nody-mc-nodeface;{"example":"general"}    |
      | cs-identifier;younger-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "elder-mc-nodeface" and node path "elder-document" to lead to no node
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to have the following child nodes:
      | Name              | NodeDiscriminator                                     |
      | tethered-document | cs-identifier;nodewyn-tetherton;{"example":"general"} |
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;younger-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "nodewyn-tetherton" and node path "document/tethered-document" to lead to node cs-identifier;nodewyn-tetherton;{"example":"general"}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                     |
      | tethered | cs-identifier;nodimer-tetherton;{"example":"general"} |
    And I expect node aggregate identifier "nodimer-tetherton" and node path "document/tethered-document/tethered" to lead to node cs-identifier;nodimer-tetherton;{"example":"general"}
    And I expect node aggregate identifier "younger-mc-nodeface" and node path "younger-document" to lead to node cs-identifier;younger-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;nody-mc-nodeface;{"example":"general"}   |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
    And I expect this node to have no succeeding siblings
    And I expect node aggregate identifier "youngest-mc-nodeface" and node path "youngest-document" to lead to no node
    And I expect node aggregate identifier "invariable-mc-nodeface" and node path "document/invariable-document" to lead to no node
