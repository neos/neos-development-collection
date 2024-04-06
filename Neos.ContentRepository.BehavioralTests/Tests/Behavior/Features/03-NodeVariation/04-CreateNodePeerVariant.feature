@contentrepository @adapters=DoctrineDBAL,Postgres
Feature: Create node peer variant

  As a user of the CR I want to create a copy of a node within an aggregate to a peer dimension space point, i.e. one that is neither a generalization nor a specialization.

  Background:
    Given using the following content dimensions:
      | Identifier | Values               | Generalizations |
      | example    | source,peer,peerSpec | peerSpec->peer  |
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
      | workspaceTitle       | "Live"               |
      | workspaceDescription | "The live workspace" |
      | newContentStreamId   | "cs-identifier"      |
    And the graph projection is fully up to date
    And I am in the active content stream of workspace "live" and dimension space point {"example":"source"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the graph projection is fully up to date
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId        | originDimensionSpacePoint | nodeName            | parentNodeAggregateId  | succeedingSiblingNodeAggregateId | nodeTypeName                                                   | tetheredDescendantNodeAggregateIds                                                            |
    # We have to add another node since root nodes have no origin dimension space points and thus cannot be varied.
    # We also need a tethered child node to test that it is reachable from the freshly created peer variant of the parent
    # and we need a tethered child node of the tethered child node to test that this works recursively
      | nody-mc-nodeface       | {"example":"source"}      | document            | lady-eleonode-rootford |                                  | Neos.ContentRepository.Testing:Document                        | {"tethered-document": "nodewyn-tetherton", "tethered-document/tethered": "nodimer-tetherton"} |
    # Let's create some siblings, both in source and target, to check ordering
      | elder-mc-nodeface      | {"example":"source"}      | elder-document      | lady-eleonode-rootford | nody-mc-nodeface                 | Neos.ContentRepository.Testing:DocumentWithoutTetheredChildren | {}                                                                                            |
      | youngest-mc-nodeface   | {"example":"source"}      | youngest-document   | lady-eleonode-rootford |                                  | Neos.ContentRepository.Testing:DocumentWithoutTetheredChildren | {}                                                                                            |
      | eldest-mc-nodeface     | {"example":"peer"}        | eldest-document     | lady-eleonode-rootford |                                  | Neos.ContentRepository.Testing:DocumentWithoutTetheredChildren | {}                                                                                            |
    # ...and we need a non-tethered child node to make sure it is _not_ reachable from the freshly created peer variant of the parent
      | invariable-mc-nodeface | {"example":"source"}      | invariable-document | nody-mc-nodeface       |                                  | Neos.ContentRepository.Testing:DocumentWithoutTetheredChildren | {}                                                                                            |

  Scenario: Create peer variant of node to dimension space point with specialization
    When the command CreateNodeVariant is executed with payload:
      | Key             | Value                |
      | nodeAggregateId | "nody-mc-nodeface"   |
      | sourceOrigin    | {"example":"source"} |
      | targetOrigin    | {"example":"peer"}   |
    And the graph projection is fully up to date
    And the command CreateNodeVariant is executed with payload:
      | Key             | Value                |
      | nodeAggregateId | "elder-mc-nodeface"  |
      | sourceOrigin    | {"example":"source"} |
      | targetOrigin    | {"example":"peer"}   |
    And the graph projection is fully up to date
    # Complete the sibling set with a node in the target DSP between the middle and last node
    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                                            |
      | nodeAggregateId           | "younger-mc-nodeface"                                            |
      | nodeTypeName              | "Neos.ContentRepository.Testing:DocumentWithoutTetheredChildren" |
      | parentNodeAggregateId     | "lady-eleonode-rootford"                                         |
      | originDimensionSpacePoint | {"example":"peer"}                                               |
      | nodeName                  | "younger-document"                                               |
    And the graph projection is fully up to date
    And the command CreateNodeVariant is executed with payload:
      | Key             | Value                  |
      | nodeAggregateId | "youngest-mc-nodeface" |
      | sourceOrigin    | {"example":"source"}   |
      | targetOrigin    | {"example":"peer"}     |

    Then I expect exactly 15 events to be published on stream "ContentStream:cs-identifier"
    And event at index 9 is of type "NodePeerVariantWasCreated" with payload:
      | Key                    | Expected                                                                                                                                  |
      | contentStreamId        | "cs-identifier"                                                                                                                           |
      | nodeAggregateId        | "nody-mc-nodeface"                                                                                                                        |
      | sourceOrigin           | {"example":"source"}                                                                                                                      |
      | peerOrigin             | {"example":"peer"}                                                                                                                        |
      | peerSucceedingSiblings | [{"dimensionSpacePoint":{"example":"peer"},"nodeAggregateId":null},{"dimensionSpacePoint":{"example":"peerSpec"},"nodeAggregateId":null}] |
    And event at index 10 is of type "NodePeerVariantWasCreated" with payload:
      | Key                    | Expected                                                                                                                                  |
      | contentStreamId        | "cs-identifier"                                                                                                                           |
      | nodeAggregateId        | "nodewyn-tetherton"                                                                                                                       |
      | sourceOrigin           | {"example":"source"}                                                                                                                      |
      | peerOrigin             | {"example":"peer"}                                                                                                                        |
      | peerSucceedingSiblings | [{"dimensionSpacePoint":{"example":"peer"},"nodeAggregateId":null},{"dimensionSpacePoint":{"example":"peerSpec"},"nodeAggregateId":null}] |
    And event at index 11 is of type "NodePeerVariantWasCreated" with payload:
      | Key                    | Expected                                                                                                                                  |
      | contentStreamId        | "cs-identifier"                                                                                                                           |
      | nodeAggregateId        | "nodimer-tetherton"                                                                                                                       |
      | sourceOrigin           | {"example":"source"}                                                                                                                      |
      | peerOrigin             | {"example":"peer"}                                                                                                                        |
      | peerSucceedingSiblings | [{"dimensionSpacePoint":{"example":"peer"},"nodeAggregateId":null},{"dimensionSpacePoint":{"example":"peerSpec"},"nodeAggregateId":null}] |
    And event at index 12 is of type "NodePeerVariantWasCreated" with payload:
      | Key                    | Expected                                                                                                                                                              |
      | contentStreamId        | "cs-identifier"                                                                                                                                                       |
      | nodeAggregateId        | "elder-mc-nodeface"                                                                                                                                                   |
      | sourceOrigin           | {"example":"source"}                                                                                                                                                  |
      | peerOrigin             | {"example":"peer"}                                                                                                                                                    |
      | peerSucceedingSiblings | [{"dimensionSpacePoint":{"example":"peer"},"nodeAggregateId":"nody-mc-nodeface"},{"dimensionSpacePoint":{"example":"peerSpec"},"nodeAggregateId":"nody-mc-nodeface"}] |
    # 13 is the additional creation event
    And event at index 14 is of type "NodePeerVariantWasCreated" with payload:
      | Key                    | Expected                                                                                                                                  |
      | contentStreamId        | "cs-identifier"                                                                                                                           |
      | nodeAggregateId        | "youngest-mc-nodeface"                                                                                                                    |
      | sourceOrigin           | {"example":"source"}                                                                                                                      |
      | peerOrigin             | {"example":"peer"}                                                                                                                        |
      | peerSucceedingSiblings | [{"dimensionSpacePoint":{"example":"peer"},"nodeAggregateId":null},{"dimensionSpacePoint":{"example":"peerSpec"},"nodeAggregateId":null}] |

    When the graph projection is fully up to date
    Then I expect the graph projection to consist of exactly 14 nodes
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"example":"peer"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodewyn-tetherton;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodewyn-tetherton;{"example":"peer"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimer-tetherton;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimer-tetherton;{"example":"peer"} to exist in the content graph
    And I expect a node identified by cs-identifier;eldest-mc-nodeface;{"example":"peer"} to exist in the content graph
    And I expect a node identified by cs-identifier;elder-mc-nodeface;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;elder-mc-nodeface;{"example":"peer"} to exist in the content graph
    And I expect a node identified by cs-identifier;younger-mc-nodeface;{"example":"peer"} to exist in the content graph
    And I expect a node identified by cs-identifier;youngest-mc-nodeface;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;youngest-mc-nodeface;{"example":"peer"} to exist in the content graph
    And I expect a node identified by cs-identifier;invariable-mc-nodeface;{"example":"source"} to exist in the content graph

    When I am in the active content stream of workspace "live"
    Then I expect the node aggregate "lady-eleonode-rootford" to exist
    And I expect this node aggregate to occupy dimension space points [{}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"},{"example":"peer"},{"example":"peerSpec"}]

    And I expect the node aggregate "eldest-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"peer"}]
    And I expect this node aggregate to cover dimension space points [{"example":"peer"},{"example":"peerSpec"}]

    And I expect the node aggregate "elder-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"},{"example":"peer"}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"},{"example":"peer"},{"example":"peerSpec"}]

    And I expect the node aggregate "nody-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"},{"example":"peer"}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"},{"example":"peer"},{"example":"peerSpec"}]

    And I expect the node aggregate "nodewyn-tetherton" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"},{"example":"peer"}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"},{"example":"peer"},{"example":"peerSpec"}]

    And I expect the node aggregate "nodimer-tetherton" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"},{"example":"peer"}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"},{"example":"peer"},{"example":"peerSpec"}]

    And I expect the node aggregate "invariable-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"}]

    And I expect the node aggregate "younger-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"peer"}]
    And I expect this node aggregate to cover dimension space points [{"example":"peer"},{"example":"peerSpec"}]

    And I expect the node aggregate "youngest-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"},{"example":"peer"}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"},{"example":"peer"},{"example":"peerSpec"}]

    When I am in the active content stream of workspace "live" and dimension space point {"example":"source"}
    Then I expect the subgraph projection to consist of exactly 7 nodes
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name              | NodeDiscriminator                                       |
      | elder-document    | cs-identifier;elder-mc-nodeface;{"example":"source"}    |
      | document          | cs-identifier;nody-mc-nodeface;{"example":"source"}     |
      | youngest-document | cs-identifier;youngest-mc-nodeface;{"example":"source"} |
    And I expect node aggregate identifier "eldest-mc-nodeface" and node path "eldest-document" to lead to no node
    And I expect node aggregate identifier "elder-mc-nodeface" and node path "elder-document" to lead to node cs-identifier;elder-mc-nodeface;{"example":"source"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;nody-mc-nodeface;{"example":"source"}     |
      | cs-identifier;youngest-mc-nodeface;{"example":"source"} |
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"source"}
    And I expect this node to have the following child nodes:
      | Name                | NodeDiscriminator                                         |
      | tethered-document   | cs-identifier;nodewyn-tetherton;{"example":"source"}      |
      | invariable-document | cs-identifier;invariable-mc-nodeface;{"example":"source"} |
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                    |
      | cs-identifier;elder-mc-nodeface;{"example":"source"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;youngest-mc-nodeface;{"example":"source"} |
    And I expect node aggregate identifier "nodewyn-tetherton" and node path "document/tethered-document" to lead to node cs-identifier;nodewyn-tetherton;{"example":"source"}
    And I expect node aggregate identifier "nodimer-tetherton" and node path "document/tethered-document/tethered" to lead to node cs-identifier;nodimer-tetherton;{"example":"source"}
    And I expect node aggregate identifier "invariable-mc-nodeface" and node path "document/invariable-document" to lead to node cs-identifier;invariable-mc-nodeface;{"example":"source"}
    And I expect node aggregate identifier "younger-mc-nodeface" and node path "younger-document" to lead to no node
    And I expect node aggregate identifier "youngest-mc-nodeface" and node path "youngest-document" to lead to node cs-identifier;youngest-mc-nodeface;{"example":"source"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                    |
      | cs-identifier;nody-mc-nodeface;{"example":"source"}  |
      | cs-identifier;elder-mc-nodeface;{"example":"source"} |
    And I expect this node to have no succeeding siblings

    When I am in the active content stream of workspace "live" and dimension space point {"example":"peer"}
    Then I expect the subgraph projection to consist of exactly 8 nodes
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name              | NodeDiscriminator                                     |
      | eldest-document   | cs-identifier;eldest-mc-nodeface;{"example":"peer"}   |
      | elder-document    | cs-identifier;elder-mc-nodeface;{"example":"peer"}    |
      | document          | cs-identifier;nody-mc-nodeface;{"example":"peer"}     |
      | younger-document  | cs-identifier;younger-mc-nodeface;{"example":"peer"}  |
      | youngest-document | cs-identifier;youngest-mc-nodeface;{"example":"peer"} |
    And I expect node aggregate identifier "eldest-mc-nodeface" and node path "eldest-document" to lead to node cs-identifier;eldest-mc-nodeface;{"example":"peer"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                     |
      | cs-identifier;elder-mc-nodeface;{"example":"peer"}    |
      | cs-identifier;nody-mc-nodeface;{"example":"peer"}     |
      | cs-identifier;younger-mc-nodeface;{"example":"peer"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"peer"} |
    And I expect node aggregate identifier "elder-mc-nodeface" and node path "elder-document" to lead to node cs-identifier;elder-mc-nodeface;{"example":"peer"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                   |
      | cs-identifier;eldest-mc-nodeface;{"example":"peer"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                     |
      | cs-identifier;nody-mc-nodeface;{"example":"peer"}     |
      | cs-identifier;younger-mc-nodeface;{"example":"peer"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"peer"} |
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"peer"}
    And I expect this node to have the following child nodes:
      | Name              | NodeDiscriminator                                  |
      | tethered-document | cs-identifier;nodewyn-tetherton;{"example":"peer"} |
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                   |
      | cs-identifier;elder-mc-nodeface;{"example":"peer"}  |
      | cs-identifier;eldest-mc-nodeface;{"example":"peer"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                     |
      | cs-identifier;younger-mc-nodeface;{"example":"peer"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"peer"} |
    And I expect node aggregate identifier "nodewyn-tetherton" and node path "document/tethered-document" to lead to node cs-identifier;nodewyn-tetherton;{"example":"peer"}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                  |
      | tethered | cs-identifier;nodimer-tetherton;{"example":"peer"} |
    And I expect this node to have no preceding siblings
    And I expect this node to have no succeeding siblings
    And I expect node aggregate identifier "nodimer-tetherton" and node path "document/tethered-document/tethered" to lead to node cs-identifier;nodimer-tetherton;{"example":"peer"}
    And I expect node aggregate identifier "younger-mc-nodeface" and node path "younger-document" to lead to node cs-identifier;younger-mc-nodeface;{"example":"peer"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                   |
      | cs-identifier;nody-mc-nodeface;{"example":"peer"}   |
      | cs-identifier;elder-mc-nodeface;{"example":"peer"}  |
      | cs-identifier;eldest-mc-nodeface;{"example":"peer"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                     |
      | cs-identifier;youngest-mc-nodeface;{"example":"peer"} |
    And I expect node aggregate identifier "youngest-mc-nodeface" and node path "youngest-document" to lead to node cs-identifier;youngest-mc-nodeface;{"example":"peer"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                    |
      | cs-identifier;younger-mc-nodeface;{"example":"peer"} |
      | cs-identifier;nody-mc-nodeface;{"example":"peer"}    |
      | cs-identifier;elder-mc-nodeface;{"example":"peer"}   |
      | cs-identifier;eldest-mc-nodeface;{"example":"peer"}  |
    And I expect this node to have no succeeding siblings
    And I expect node aggregate identifier "invariable-mc-nodeface" and node path "document/invariable-document" to lead to no node

    When I am in the active content stream of workspace "live" and dimension space point {"example":"peerSpec"}
    Then I expect the subgraph projection to consist of exactly 8 nodes
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name              | NodeDiscriminator                                     |
      | eldest-document   | cs-identifier;eldest-mc-nodeface;{"example":"peer"}   |
      | elder-document    | cs-identifier;elder-mc-nodeface;{"example":"peer"}    |
      | document          | cs-identifier;nody-mc-nodeface;{"example":"peer"}     |
      | younger-document  | cs-identifier;younger-mc-nodeface;{"example":"peer"}  |
      | youngest-document | cs-identifier;youngest-mc-nodeface;{"example":"peer"} |
    And I expect node aggregate identifier "eldest-mc-nodeface" and node path "eldest-document" to lead to node cs-identifier;eldest-mc-nodeface;{"example":"peer"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                     |
      | cs-identifier;elder-mc-nodeface;{"example":"peer"}    |
      | cs-identifier;nody-mc-nodeface;{"example":"peer"}     |
      | cs-identifier;younger-mc-nodeface;{"example":"peer"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"peer"} |
    And I expect node aggregate identifier "elder-mc-nodeface" and node path "elder-document" to lead to node cs-identifier;elder-mc-nodeface;{"example":"peer"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                   |
      | cs-identifier;eldest-mc-nodeface;{"example":"peer"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                     |
      | cs-identifier;nody-mc-nodeface;{"example":"peer"}     |
      | cs-identifier;younger-mc-nodeface;{"example":"peer"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"peer"} |
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"peer"}
    And I expect this node to have the following child nodes:
      | Name              | NodeDiscriminator                                  |
      | tethered-document | cs-identifier;nodewyn-tetherton;{"example":"peer"} |
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                   |
      | cs-identifier;elder-mc-nodeface;{"example":"peer"}  |
      | cs-identifier;eldest-mc-nodeface;{"example":"peer"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                     |
      | cs-identifier;younger-mc-nodeface;{"example":"peer"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"peer"} |
    And I expect node aggregate identifier "nodewyn-tetherton" and node path "document/tethered-document" to lead to node cs-identifier;nodewyn-tetherton;{"example":"peer"}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                  |
      | tethered | cs-identifier;nodimer-tetherton;{"example":"peer"} |
    And I expect this node to have no preceding siblings
    And I expect this node to have no succeeding siblings
    And I expect node aggregate identifier "nodimer-tetherton" and node path "document/tethered-document/tethered" to lead to node cs-identifier;nodimer-tetherton;{"example":"peer"}
    And I expect node aggregate identifier "younger-mc-nodeface" and node path "younger-document" to lead to node cs-identifier;younger-mc-nodeface;{"example":"peer"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                   |
      | cs-identifier;nody-mc-nodeface;{"example":"peer"}   |
      | cs-identifier;elder-mc-nodeface;{"example":"peer"}  |
      | cs-identifier;eldest-mc-nodeface;{"example":"peer"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                     |
      | cs-identifier;youngest-mc-nodeface;{"example":"peer"} |
    And I expect node aggregate identifier "youngest-mc-nodeface" and node path "youngest-document" to lead to node cs-identifier;youngest-mc-nodeface;{"example":"peer"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                    |
      | cs-identifier;younger-mc-nodeface;{"example":"peer"} |
      | cs-identifier;nody-mc-nodeface;{"example":"peer"}    |
      | cs-identifier;elder-mc-nodeface;{"example":"peer"}   |
      | cs-identifier;eldest-mc-nodeface;{"example":"peer"}  |
    And I expect this node to have no succeeding siblings
    And I expect node aggregate identifier "invariable-mc-nodeface" and node path "document/invariable-document" to lead to no node

  Scenario: Create peer variant of node to dimension space point with specializations that are partially occupied
    Given the command CreateNodeVariant is executed with payload:
      | Key             | Value                  |
      | nodeAggregateId | "nody-mc-nodeface"     |
      | sourceOrigin    | {"example":"source"}   |
      | targetOrigin    | {"example":"peerSpec"} |
    And the graph projection is fully up to date

    When the command CreateNodeVariant is executed with payload:
      | Key             | Value                |
      | nodeAggregateId | "nody-mc-nodeface"   |
      | sourceOrigin    | {"example":"source"} |
      | targetOrigin    | {"example":"peer"}   |
    Then I expect exactly 15 events to be published on stream "ContentStream:cs-identifier"
    And event at index 9 is of type "NodePeerVariantWasCreated" with payload:
      | Key                    | Expected                                                                |
      | contentStreamId        | "cs-identifier"                                                         |
      | nodeAggregateId        | "nody-mc-nodeface"                                                      |
      | sourceOrigin           | {"example":"source"}                                                    |
      | peerOrigin             | {"example":"peerSpec"}                                                  |
      | peerSucceedingSiblings | [{"dimensionSpacePoint":{"example":"peerSpec"},"nodeAggregateId":null}] |
    And event at index 10 is of type "NodePeerVariantWasCreated" with payload:
      | Key                    | Expected                                                                |
      | contentStreamId        | "cs-identifier"                                                         |
      | nodeAggregateId        | "nodewyn-tetherton"                                                     |
      | sourceOrigin           | {"example":"source"}                                                    |
      | peerOrigin             | {"example":"peerSpec"}                                                  |
      | peerSucceedingSiblings | [{"dimensionSpacePoint":{"example":"peerSpec"},"nodeAggregateId":null}] |
    And event at index 11 is of type "NodePeerVariantWasCreated" with payload:
      | Key                    | Expected                                                                |
      | contentStreamId        | "cs-identifier"                                                         |
      | nodeAggregateId        | "nodimer-tetherton"                                                     |
      | sourceOrigin           | {"example":"source"}                                                    |
      | peerOrigin             | {"example":"peerSpec"}                                                  |
      | peerSucceedingSiblings | [{"dimensionSpacePoint":{"example":"peerSpec"},"nodeAggregateId":null}] |
    And event at index 12 is of type "NodePeerVariantWasCreated" with payload:
      | Key                    | Expected                                                            |
      | contentStreamId        | "cs-identifier"                                                     |
      | nodeAggregateId        | "nody-mc-nodeface"                                                  |
      | sourceOrigin           | {"example":"source"}                                                |
      | peerOrigin             | {"example":"peer"}                                                  |
      | peerSucceedingSiblings | [{"dimensionSpacePoint":{"example":"peer"},"nodeAggregateId":null}] |
    And event at index 13 is of type "NodePeerVariantWasCreated" with payload:
      | Key                    | Expected                                                            |
      | contentStreamId        | "cs-identifier"                                                     |
      | nodeAggregateId        | "nodewyn-tetherton"                                                 |
      | sourceOrigin           | {"example":"source"}                                                |
      | peerOrigin             | {"example":"peer"}                                                  |
      | peerSucceedingSiblings | [{"dimensionSpacePoint":{"example":"peer"},"nodeAggregateId":null}] |
    And event at index 14 is of type "NodePeerVariantWasCreated" with payload:
      | Key                    | Expected                                                            |
      | contentStreamId        | "cs-identifier"                                                     |
      | nodeAggregateId        | "nodimer-tetherton"                                                 |
      | sourceOrigin           | {"example":"source"}                                                |
      | peerOrigin             | {"example":"peer"}                                                  |
      | peerSucceedingSiblings | [{"dimensionSpacePoint":{"example":"peer"},"nodeAggregateId":null}] |

    When the graph projection is fully up to date
    Then I expect the graph projection to consist of exactly 14 nodes
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect a node identified by cs-identifier;eldest-mc-nodeface;{"example":"peer"} to exist in the content graph
    And I expect a node identified by cs-identifier;elder-mc-nodeface;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"example":"peer"} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"example":"peerSpec"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodewyn-tetherton;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodewyn-tetherton;{"example":"peer"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodewyn-tetherton;{"example":"peerSpec"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimer-tetherton;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimer-tetherton;{"example":"peer"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimer-tetherton;{"example":"peerSpec"} to exist in the content graph
    And I expect a node identified by cs-identifier;youngest-mc-nodeface;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;invariable-mc-nodeface;{"example":"source"} to exist in the content graph

    When I am in the active content stream of workspace "live"
    Then I expect the node aggregate "lady-eleonode-rootford" to exist
    And I expect this node aggregate to occupy dimension space points [{}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"},{"example":"peer"},{"example":"peerSpec"}]

    Then I expect the node aggregate "eldest-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"peer"}]
    And I expect this node aggregate to cover dimension space points [{"example":"peer"},{"example":"peerSpec"}]

    Then I expect the node aggregate "elder-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"}]

    And I expect the node aggregate "nody-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"},{"example":"peer"},{"example":"peerSpec"}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"},{"example":"peer"},{"example":"peerSpec"}]

    And I expect the node aggregate "nodewyn-tetherton" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"},{"example":"peer"},{"example":"peerSpec"}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"},{"example":"peer"},{"example":"peerSpec"}]

    And I expect the node aggregate "nodimer-tetherton" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"},{"example":"peer"},{"example":"peerSpec"}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"},{"example":"peer"},{"example":"peerSpec"}]

    Then I expect the node aggregate "youngest-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"}]

    Then I expect the node aggregate "invariable-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"}]

    When I am in the active content stream of workspace "live" and dimension space point {"example":"peer"}
    Then I expect the subgraph projection to consist of exactly 5 nodes
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name            | NodeDiscriminator                                   |
      | eldest-document | cs-identifier;eldest-mc-nodeface;{"example":"peer"} |
      | document        | cs-identifier;nody-mc-nodeface;{"example":"peer"}   |
    And I expect node aggregate identifier "eldest-mc-nodeface" and node path "eldest-document" to lead to node cs-identifier;eldest-mc-nodeface;{"example":"peer"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                 |
      | cs-identifier;nody-mc-nodeface;{"example":"peer"} |
    And I expect node aggregate identifier "elder-mc-nodeface" and node path "elder-document" to lead to no node
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"peer"}
    And I expect this node to have the following child nodes:
      | Name              | NodeDiscriminator                                  |
      | tethered-document | cs-identifier;nodewyn-tetherton;{"example":"peer"} |
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                   |
      | cs-identifier;eldest-mc-nodeface;{"example":"peer"} |
    And I expect this node to have no succeeding siblings
    And I expect node aggregate identifier "nodewyn-tetherton" and node path "document/tethered-document" to lead to node cs-identifier;nodewyn-tetherton;{"example":"peer"}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                  |
      | tethered | cs-identifier;nodimer-tetherton;{"example":"peer"} |
    And I expect node aggregate identifier "nodimer-tetherton" and node path "document/tethered-document/tethered" to lead to node cs-identifier;nodimer-tetherton;{"example":"peer"}
    And I expect node aggregate identifier "younger-mc-nodeface" and node path "younger-document" to lead to no node
    And I expect node aggregate identifier "youngest-mc-nodeface" and node path "youngest-document" to lead to no node
    And I expect node aggregate identifier "invariable-mc-nodeface" and node path "document/invariable-document" to lead to no node

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peerSpec"}
    Then I expect the subgraph projection to consist of exactly 5 nodes
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name            | NodeDiscriminator                                     |
      | eldest-document | cs-identifier;eldest-mc-nodeface;{"example":"peer"}   |
      | document        | cs-identifier;nody-mc-nodeface;{"example":"peerSpec"} |
    And I expect node aggregate identifier "eldest-mc-nodeface" and node path "eldest-document" to lead to node cs-identifier;eldest-mc-nodeface;{"example":"peer"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                     |
      | cs-identifier;nody-mc-nodeface;{"example":"peerSpec"} |
    And I expect node aggregate identifier "elder-mc-nodeface" and node path "elder-document" to lead to no node
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"peerSpec"}
    And I expect this node to have the following child nodes:
      | Name              | NodeDiscriminator                                      |
      | tethered-document | cs-identifier;nodewyn-tetherton;{"example":"peerSpec"} |
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                   |
      | cs-identifier;eldest-mc-nodeface;{"example":"peer"} |
    And I expect this node to have no succeeding siblings
    And I expect node aggregate identifier "nodewyn-tetherton" and node path "document/tethered-document" to lead to node cs-identifier;nodewyn-tetherton;{"example":"peerSpec"}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                      |
      | tethered | cs-identifier;nodimer-tetherton;{"example":"peerSpec"} |
    And I expect node aggregate identifier "nodimer-tetherton" and node path "document/tethered-document/tethered" to lead to node cs-identifier;nodimer-tetherton;{"example":"peerSpec"}
    And I expect node aggregate identifier "younger-mc-nodeface" and node path "younger-document" to lead to no node
    And I expect node aggregate identifier "youngest-mc-nodeface" and node path "youngest-document" to lead to no node
    And I expect node aggregate identifier "invariable-mc-nodeface" and node path "document/invariable-document" to lead to no node

  Scenario: Create peer variant of node to dimension space point that is already covered
    Given the command CreateNodeVariant is executed with payload:
      | Key             | Value                |
      | nodeAggregateId | "nody-mc-nodeface"   |
      | sourceOrigin    | {"example":"source"} |
      | targetOrigin    | {"example":"peer"}   |
    And the graph projection is fully up to date

    When the command CreateNodeVariant is executed with payload:
      | Key             | Value                  |
      | nodeAggregateId | "nody-mc-nodeface"     |
      | sourceOrigin    | {"example":"source"}   |
      | targetOrigin    | {"example":"peerSpec"} |
    Then I expect exactly 15 events to be published on stream "ContentStream:cs-identifier"
    And event at index 9 is of type "NodePeerVariantWasCreated" with payload:
      | Key                    | Expected                                                                                                                                  |
      | contentStreamId        | "cs-identifier"                                                                                                                           |
      | nodeAggregateId        | "nody-mc-nodeface"                                                                                                                        |
      | sourceOrigin           | {"example":"source"}                                                                                                                      |
      | peerOrigin             | {"example":"peer"}                                                                                                                        |
      | peerSucceedingSiblings | [{"dimensionSpacePoint":{"example":"peer"},"nodeAggregateId":null},{"dimensionSpacePoint":{"example":"peerSpec"},"nodeAggregateId":null}] |
    And event at index 10 is of type "NodePeerVariantWasCreated" with payload:
      | Key                    | Expected                                                                                                                                  |
      | contentStreamId        | "cs-identifier"                                                                                                                           |
      | nodeAggregateId        | "nodewyn-tetherton"                                                                                                                       |
      | sourceOrigin           | {"example":"source"}                                                                                                                      |
      | peerOrigin             | {"example":"peer"}                                                                                                                        |
      | peerSucceedingSiblings | [{"dimensionSpacePoint":{"example":"peer"},"nodeAggregateId":null},{"dimensionSpacePoint":{"example":"peerSpec"},"nodeAggregateId":null}] |
    And event at index 11 is of type "NodePeerVariantWasCreated" with payload:
      | Key                    | Expected                                                                                                                                  |
      | contentStreamId        | "cs-identifier"                                                                                                                           |
      | nodeAggregateId        | "nodimer-tetherton"                                                                                                                       |
      | sourceOrigin           | {"example":"source"}                                                                                                                      |
      | peerOrigin             | {"example":"peer"}                                                                                                                        |
      | peerSucceedingSiblings | [{"dimensionSpacePoint":{"example":"peer"},"nodeAggregateId":null},{"dimensionSpacePoint":{"example":"peerSpec"},"nodeAggregateId":null}] |
    And event at index 12 is of type "NodePeerVariantWasCreated" with payload:
      | Key                    | Expected                                                                |
      | contentStreamId        | "cs-identifier"                                                         |
      | nodeAggregateId        | "nody-mc-nodeface"                                                      |
      | sourceOrigin           | {"example":"source"}                                                    |
      | peerOrigin             | {"example":"peerSpec"}                                                  |
      | peerSucceedingSiblings | [{"dimensionSpacePoint":{"example":"peerSpec"},"nodeAggregateId":null}] |
    And event at index 13 is of type "NodePeerVariantWasCreated" with payload:
      | Key                    | Expected                                                                |
      | contentStreamId        | "cs-identifier"                                                         |
      | nodeAggregateId        | "nodewyn-tetherton"                                                     |
      | sourceOrigin           | {"example":"source"}                                                    |
      | peerOrigin             | {"example":"peerSpec"}                                                  |
      | peerSucceedingSiblings | [{"dimensionSpacePoint":{"example":"peerSpec"},"nodeAggregateId":null}] |
    And event at index 14 is of type "NodePeerVariantWasCreated" with payload:
      | Key                    | Expected                                                                |
      | contentStreamId        | "cs-identifier"                                                         |
      | nodeAggregateId        | "nodimer-tetherton"                                                     |
      | sourceOrigin           | {"example":"source"}                                                    |
      | peerOrigin             | {"example":"peerSpec"}                                                  |
      | peerSucceedingSiblings | [{"dimensionSpacePoint":{"example":"peerSpec"},"nodeAggregateId":null}] |

    When the graph projection is fully up to date
    Then I expect the graph projection to consist of exactly 14 nodes
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect a node identified by cs-identifier;eldest-mc-nodeface;{"example":"peer"} to exist in the content graph
    And I expect a node identified by cs-identifier;elder-mc-nodeface;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"example":"peer"} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"example":"peerSpec"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodewyn-tetherton;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodewyn-tetherton;{"example":"peer"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodewyn-tetherton;{"example":"peerSpec"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimer-tetherton;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimer-tetherton;{"example":"peer"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimer-tetherton;{"example":"peerSpec"} to exist in the content graph
    And I expect a node identified by cs-identifier;youngest-mc-nodeface;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;invariable-mc-nodeface;{"example":"source"} to exist in the content graph

    When I am in the active content stream of workspace "live"
    Then I expect the node aggregate "lady-eleonode-rootford" to exist
    And I expect this node aggregate to occupy dimension space points [{}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"},{"example":"peer"},{"example":"peerSpec"}]

    Then I expect the node aggregate "eldest-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"peer"}]
    And I expect this node aggregate to cover dimension space points [{"example":"peer"},{"example":"peerSpec"}]

    Then I expect the node aggregate "elder-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"}]

    And I expect the node aggregate "nody-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"},{"example":"peer"},{"example":"peerSpec"}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"},{"example":"peer"},{"example":"peerSpec"}]

    And I expect the node aggregate "nodewyn-tetherton" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"},{"example":"peer"},{"example":"peerSpec"}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"},{"example":"peer"},{"example":"peerSpec"}]

    And I expect the node aggregate "nodimer-tetherton" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"},{"example":"peer"},{"example":"peerSpec"}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"},{"example":"peer"},{"example":"peerSpec"}]

    Then I expect the node aggregate "youngest-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"}]

    Then I expect the node aggregate "invariable-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"}]

    When I am in the active content stream of workspace "live" and dimension space point {"example":"peer"}
    Then I expect the subgraph projection to consist of exactly 5 nodes
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name            | NodeDiscriminator                                   |
      | eldest-document | cs-identifier;eldest-mc-nodeface;{"example":"peer"} |
      | document        | cs-identifier;nody-mc-nodeface;{"example":"peer"}   |
    And I expect node aggregate identifier "eldest-mc-nodeface" and node path "eldest-document" to lead to node cs-identifier;eldest-mc-nodeface;{"example":"peer"}
    And I expect node aggregate identifier "elder-mc-nodeface" and node path "elder-document" to lead to no node
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                 |
      | cs-identifier;nody-mc-nodeface;{"example":"peer"} |
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"peer"}
    And I expect this node to have the following child nodes:
      | Name              | NodeDiscriminator                                  |
      | tethered-document | cs-identifier;nodewyn-tetherton;{"example":"peer"} |
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                   |
      | cs-identifier;eldest-mc-nodeface;{"example":"peer"} |
    And I expect this node to have no succeeding siblings
    And I expect node aggregate identifier "nodewyn-tetherton" and node path "document/tethered-document" to lead to node cs-identifier;nodewyn-tetherton;{"example":"peer"}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                  |
      | tethered | cs-identifier;nodimer-tetherton;{"example":"peer"} |
    And I expect node aggregate identifier "nodimer-tetherton" and node path "document/tethered-document/tethered" to lead to node cs-identifier;nodimer-tetherton;{"example":"peer"}
    And I expect node aggregate identifier "younger-mc-nodeface" and node path "younger-document" to lead to no node
    And I expect node aggregate identifier "youngest-mc-nodeface" and node path "youngest-document" to lead to no node
    And I expect node aggregate identifier "invariable-mc-nodeface" and node path "document/invariable-document" to lead to no node

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peerSpec"}
    Then I expect the subgraph projection to consist of exactly 5 nodes
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name            | NodeDiscriminator                                     |
      | eldest-document | cs-identifier;eldest-mc-nodeface;{"example":"peer"}   |
      | document        | cs-identifier;nody-mc-nodeface;{"example":"peerSpec"} |
    And I expect node aggregate identifier "eldest-mc-nodeface" and node path "eldest-document" to lead to node cs-identifier;eldest-mc-nodeface;{"example":"peer"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                     |
      | cs-identifier;nody-mc-nodeface;{"example":"peerSpec"} |
    And I expect node aggregate identifier "elder-mc-nodeface" and node path "elder-document" to lead to no node
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"peerSpec"}
    And I expect this node to have the following child nodes:
      | Name              | NodeDiscriminator                                      |
      | tethered-document | cs-identifier;nodewyn-tetherton;{"example":"peerSpec"} |
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                   |
      | cs-identifier;eldest-mc-nodeface;{"example":"peer"} |
    And I expect this node to have no succeeding siblings
    And I expect node aggregate identifier "nodewyn-tetherton" and node path "document/tethered-document" to lead to node cs-identifier;nodewyn-tetherton;{"example":"peerSpec"}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                      |
      | tethered | cs-identifier;nodimer-tetherton;{"example":"peerSpec"} |
    And I expect node aggregate identifier "nodimer-tetherton" and node path "document/tethered-document/tethered" to lead to node cs-identifier;nodimer-tetherton;{"example":"peerSpec"}
    And I expect node aggregate identifier "younger-mc-nodeface" and node path "younger-document" to lead to no node
    And I expect node aggregate identifier "youngest-mc-nodeface" and node path "youngest-document" to lead to no node
    And I expect node aggregate identifier "invariable-mc-nodeface" and node path "document/invariable-document" to lead to no node

  Scenario: Create a peer node variant to a dimension space point with specializations and where the parent node aggregate is already specialized in
    # We need a new node for this
    Given the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                                            |
      | nodeAggregateId           | "elder-child-mc-nodeface"                                        |
      | nodeTypeName              | "Neos.ContentRepository.Testing:DocumentWithoutTetheredChildren" |
      | parentNodeAggregateId     | "elder-mc-nodeface"                                              |
      | originDimensionSpacePoint | {"example":"source"}                                             |
      | nodeName                  | "elder-child-document"                                           |
    And the command CreateNodeVariant is executed with payload:
      | Key             | Value                |
      | nodeAggregateId | "elder-mc-nodeface"  |
      | sourceOrigin    | {"example":"source"} |
      | targetOrigin    | {"example":"peer"}   |
    And the graph projection is fully up to date
    And the command CreateNodeVariant is executed with payload:
      | Key             | Value                     |
      | nodeAggregateId | "elder-child-mc-nodeface" |
      | sourceOrigin    | {"example":"source"}      |
      | targetOrigin    | {"example":"peer"}        |

    Then I expect exactly 12 events to be published on stream "ContentStream:cs-identifier"
    And event at index 10 is of type "NodePeerVariantWasCreated" with payload:
      | Key                    | Expected                                                                                                                                  |
      | contentStreamId        | "cs-identifier"                                                                                                                           |
      | nodeAggregateId        | "elder-mc-nodeface"                                                                                                                       |
      | sourceOrigin           | {"example":"source"}                                                                                                                      |
      | peerOrigin             | {"example":"peer"}                                                                                                                        |
      | peerSucceedingSiblings | [{"dimensionSpacePoint":{"example":"peer"},"nodeAggregateId":null},{"dimensionSpacePoint":{"example":"peerSpec"},"nodeAggregateId":null}] |
    And event at index 11 is of type "NodePeerVariantWasCreated" with payload:
      | Key                    | Expected                                                                                                                                  |
      | contentStreamId        | "cs-identifier"                                                                                                                           |
      | nodeAggregateId        | "elder-child-mc-nodeface"                                                                                                                 |
      | sourceOrigin           | {"example":"source"}                                                                                                                      |
      | peerOrigin             | {"example":"peer"}                                                                                                                        |
      | peerSucceedingSiblings | [{"dimensionSpacePoint":{"example":"peer"},"nodeAggregateId":null},{"dimensionSpacePoint":{"example":"peerSpec"},"nodeAggregateId":null}] |

    When the graph projection is fully up to date
    Then I expect the graph projection to consist of exactly 11 nodes
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect a node identified by cs-identifier;eldest-mc-nodeface;{"example":"peer"} to exist in the content graph
    And I expect a node identified by cs-identifier;elder-mc-nodeface;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;elder-mc-nodeface;{"example":"peer"} to exist in the content graph
    And I expect a node identified by cs-identifier;elder-child-mc-nodeface;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;elder-child-mc-nodeface;{"example":"peer"} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodewyn-tetherton;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimer-tetherton;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;youngest-mc-nodeface;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;invariable-mc-nodeface;{"example":"source"} to exist in the content graph

    When I am in the active content stream of workspace "live"
    Then I expect the node aggregate "lady-eleonode-rootford" to exist
    And I expect this node aggregate to occupy dimension space points [{}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"},{"example":"peer"},{"example":"peerSpec"}]

    Then I expect the node aggregate "eldest-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"peer"}]
    And I expect this node aggregate to cover dimension space points [{"example":"peer"},{"example":"peerSpec"}]

    Then I expect the node aggregate "elder-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"},{"example":"peer"}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"},{"example":"peer"},{"example":"peerSpec"}]

    And I expect the node aggregate "nody-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"}]

    And I expect the node aggregate "nodewyn-tetherton" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"}]

    And I expect the node aggregate "nodimer-tetherton" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"}]

    Then I expect the node aggregate "youngest-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"}]

    Then I expect the node aggregate "invariable-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"}]

    When I am in the active content stream of workspace "live" and dimension space point {"example":"peer"}
    Then I expect the subgraph projection to consist of exactly 4 nodes
    And I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name            | NodeDiscriminator                                   |
      | eldest-document | cs-identifier;eldest-mc-nodeface;{"example":"peer"} |
      | elder-document  | cs-identifier;elder-mc-nodeface;{"example":"peer"}  |
    And I expect node aggregate identifier "eldest-mc-nodeface" and node path "eldest-document" to lead to node cs-identifier;eldest-mc-nodeface;{"example":"peer"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                  |
      | cs-identifier;elder-mc-nodeface;{"example":"peer"} |
    And I expect node aggregate identifier "elder-mc-nodeface" and node path "elder-document" to lead to node cs-identifier;elder-mc-nodeface;{"example":"peer"}
    And I expect this node to have the following child nodes:
      | Name                 | NodeDiscriminator                                        |
      | elder-child-document | cs-identifier;elder-child-mc-nodeface;{"example":"peer"} |
    And I expect node aggregate identifier "elder-child-mc-nodeface" and node path "elder-document/elder-child-document" to lead to node cs-identifier;elder-child-mc-nodeface;{"example":"peer"}

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peerSpec"}
    Then I expect the subgraph projection to consist of exactly 4 nodes
    And I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name            | NodeDiscriminator                                   |
      | eldest-document | cs-identifier;eldest-mc-nodeface;{"example":"peer"} |
      | elder-document  | cs-identifier;elder-mc-nodeface;{"example":"peer"}  |
    And I expect node aggregate identifier "eldest-mc-nodeface" and node path "eldest-document" to lead to node cs-identifier;eldest-mc-nodeface;{"example":"peer"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                  |
      | cs-identifier;elder-mc-nodeface;{"example":"peer"} |
    And I expect node aggregate identifier "elder-mc-nodeface" and node path "elder-document" to lead to node cs-identifier;elder-mc-nodeface;{"example":"peer"}
    And I expect this node to have the following child nodes:
      | Name                 | NodeDiscriminator                                        |
      | elder-child-document | cs-identifier;elder-child-mc-nodeface;{"example":"peer"} |
    And I expect node aggregate identifier "elder-child-mc-nodeface" and node path "elder-document/elder-child-document" to lead to node cs-identifier;elder-child-mc-nodeface;{"example":"peer"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document" to lead to no node
    And I expect node aggregate identifier "younger-mc-nodeface" and node path "younger-document" to lead to no node
    And I expect node aggregate identifier "youngest-mc-nodeface" and node path "youngest-document" to lead to no node
    And I expect node aggregate identifier "invariable-mc-nodeface" and node path "document/invariable-document" to lead to no node
