@fixtures @adapters=DoctrineDBAL,Postgres
Feature: Enable a node aggregate

  As a user of the CR I want to disable a node aggregate and expect its descendants to also be disabled.

  These are the test cases with dimensions being involved

  Background:
    Given I have the following content dimensions:
      | Identifier | Default | Values                | Generalizations                     |
      | language   | mul     | mul, de, en, gsw, ltz | ltz->de->mul, gsw->de->mul, en->mul |
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Testing:Document':
      properties:
        references:
          type: references
    """
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootWorkspace is executed with payload:
      | Key                        | Value                |
      | workspaceName              | "live"               |
      | workspaceTitle             | "Live"               |
      | workspaceDescription       | "The live workspace" |
      | newContentStreamIdentifier | "cs-identifier"      |
    And I am in content stream "cs-identifier" and dimension space point {"language":"mul"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                     | Value                         |
      | nodeAggregateIdentifier | "lady-eleonode-rootford"      |
      | nodeTypeName            | "Neos.ContentRepository:Root" |
    And the graph projection is fully up to date
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateIdentifier | nodeTypeName                            | parentNodeAggregateIdentifier | nodeName            |
      | preceding-nodenborough  | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford        | preceding-document  |
      | sir-david-nodenborough  | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford        | document            |
      | succeeding-nodenborough | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford        | succeeding-document |
      | nody-mc-nodeface        | Neos.ContentRepository.Testing:Document | sir-david-nodenborough        | child-document      |
      | the-great-nodini        | Neos.ContentRepository.Testing:Document | sir-david-nodenborough        | court-magician      |
    And the command SetNodeReferences is executed with payload:
      | Key                                 | Value                      |
      | sourceNodeAggregateIdentifier       | "preceding-nodenborough"   |
      | destinationNodeAggregateIdentifiers | ["sir-david-nodenborough"] |
      | referenceName                       | "references"               |
    # We need both a real and a virtual specialization to test the different selection strategies
    And the command CreateNodeVariant is executed with payload:
      | Key                     | Value                    |
      | nodeAggregateIdentifier | "sir-david-nodenborough" |
      | sourceOrigin            | {"language":"mul"}       |
      | targetOrigin            | {"language":"ltz"}       |
    And the graph projection is fully up to date
    # Disable our reference node aggregate in all variants
    And the command DisableNodeAggregate is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateIdentifier      | "sir-david-nodenborough" |
      | nodeVariantSelectionStrategy | "allVariants"            |
    And the graph projection is fully up to date
    # Explicitly disable a child node aggregate in all variants
    And the command DisableNodeAggregate is executed with payload:
      | Key                          | Value              |
      | nodeAggregateIdentifier      | "the-great-nodini" |
      | nodeVariantSelectionStrategy | "allVariants"      |
    And the graph projection is fully up to date
    # Set the DSP to the "central" variant having variants of all kind
    And I am in dimension space point {"language":"de"}

  Scenario: Enable node aggregate with strategy onlyGivenVariant
    When the command EnableNodeAggregate is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateIdentifier      | "sir-david-nodenborough" |
      | nodeVariantSelectionStrategy | "onlyGivenVariant"       |

    Then I expect exactly 12 events to be published on stream with prefix "Neos.ContentRepository:ContentStream:cs-identifier"
    And event at index 11 is of type "Neos.EventSourcedContentRepository:NodeAggregateWasEnabled" with payload:
      | Key                          | Expected                     |
      | contentStreamIdentifier      | "cs-identifier"              |
      | nodeAggregateIdentifier      | "sir-david-nodenborough"     |
      | affectedDimensionSpacePoints | [{"language":"de"}]          |
      | initiatingUserIdentifier     | "initiating-user-identifier" |

    When the graph projection is fully up to date
    And I am in content stream "cs-identifier"
    Then I expect the graph projection to consist of exactly 7 nodes
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect a node identified by cs-identifier;preceding-nodenborough;{"language":"mul"} to exist in the content graph
    And I expect a node identified by cs-identifier;sir-david-nodenborough;{"language":"mul"} to exist in the content graph
    And I expect a node identified by cs-identifier;sir-david-nodenborough;{"language":"ltz"} to exist in the content graph
    And I expect a node identified by cs-identifier;succeeding-nodenborough;{"language":"mul"} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"language":"mul"} to exist in the content graph
    And I expect a node identified by cs-identifier;the-great-nodini;{"language":"mul"} to exist in the content graph

    And I expect the node aggregate "sir-david-nodenborough" to exist
    And I expect this node aggregate to disable dimension space points [{"language":"mul"},{"language":"ltz"},{"language":"gsw"},{"language":"en"}]

    And I expect the node aggregate "the-great-nodini" to exist
    And I expect this node aggregate to disable dimension space points [{"language":"mul"},{"language":"de"},{"language":"ltz"},{"language":"gsw"},{"language":"en"}]

    # Tests for the given variant
    When I am in dimension space point {"language":"de"}
    And VisibilityConstraints are set to "withoutRestrictions"
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name                | NodeDiscriminator                                        |
      | preceding-document  | cs-identifier;preceding-nodenborough;{"language":"mul"}  |
      | document            | cs-identifier;sir-david-nodenborough;{"language":"mul"}  |
      | succeeding-document | cs-identifier;succeeding-nodenborough;{"language":"mul"} |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | NodeAggregateIdentifier |
      | 0     | lady-eleonode-rootford  |
      | 1     | preceding-nodenborough  |
      | 1     | sir-david-nodenborough  |
      | 2     | nody-mc-nodeface        |
      | 2     | the-great-nodini        |
      | 1     | succeeding-nodenborough |
    And I expect node aggregate identifier "preceding-nodenborough" and node path "preceding-document" to lead to node cs-identifier;preceding-nodenborough;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;sir-david-nodenborough;{"language":"mul"}  |
      | cs-identifier;succeeding-nodenborough;{"language":"mul"} |
    And I expect this node to have the following references:
      | Key        | Value                                                           |
      | references | ["cs-identifier;sir-david-nodenborough;{\"language\":\"mul\"}"] |
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;preceding-nodenborough;{"language":"mul"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;succeeding-nodenborough;{"language":"mul"} |
    And I expect this node to be referenced by:
      | Key        | Value                                                           |
      | references | ["cs-identifier;preceding-nodenborough;{\"language\":\"mul\"}"] |
    And I expect this node to have the following child nodes:
      | Name           | NodeDiscriminator                                 |
      | child-document | cs-identifier;nody-mc-nodeface;{"language":"mul"} |
      | court-magician | cs-identifier;the-great-nodini;{"language":"mul"} |
    And I expect node aggregate identifier "succeeding-nodenborough" and node path "succeeding-document" to lead to node cs-identifier;succeeding-nodenborough;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;sir-david-nodenborough;{"language":"mul"} |
      | cs-identifier;preceding-nodenborough;{"language":"mul"} |
    And I expect this node to have no succeeding siblings
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to node cs-identifier;nody-mc-nodeface;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language":"mul"}
    And I expect node aggregate identifier "the-great-nodini" and node path "document/court-magician" to lead to node cs-identifier;the-great-nodini;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language":"mul"}

    When VisibilityConstraints are set to "frontend"
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name                | NodeDiscriminator                                        |
      | preceding-document  | cs-identifier;preceding-nodenborough;{"language":"mul"}  |
      | document            | cs-identifier;sir-david-nodenborough;{"language":"mul"}  |
      | succeeding-document | cs-identifier;succeeding-nodenborough;{"language":"mul"} |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | NodeAggregateIdentifier |
      | 0     | lady-eleonode-rootford  |
      | 1     | preceding-nodenborough  |
      | 1     | sir-david-nodenborough  |
      | 2     | nody-mc-nodeface        |
      | 1     | succeeding-nodenborough |
    And I expect node aggregate identifier "preceding-nodenborough" and node path "preceding-document" to lead to node cs-identifier;preceding-nodenborough;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;sir-david-nodenborough;{"language":"mul"}  |
      | cs-identifier;succeeding-nodenborough;{"language":"mul"} |
    And I expect this node to have the following references:
      | Key        | Value                                                           |
      | references | ["cs-identifier;sir-david-nodenborough;{\"language\":\"mul\"}"] |
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;preceding-nodenborough;{"language":"mul"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;succeeding-nodenborough;{"language":"mul"} |
    And I expect this node to be referenced by:
      | Key        | Value                                                           |
      | references | ["cs-identifier;preceding-nodenborough;{\"language\":\"mul\"}"] |
    And I expect this node to have the following child nodes:
      | Name           | NodeDiscriminator                                 |
      | child-document | cs-identifier;nody-mc-nodeface;{"language":"mul"} |
    And I expect node aggregate identifier "succeeding-nodenborough" and node path "succeeding-document" to lead to node cs-identifier;succeeding-nodenborough;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;sir-david-nodenborough;{"language":"mul"} |
      | cs-identifier;preceding-nodenborough;{"language":"mul"} |
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to node cs-identifier;nody-mc-nodeface;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language":"mul"}
    And I expect node aggregate identifier "the-great-nodini" and node path "document/court-magician" to lead to no node

    # Tests for the generalization
    When I am in dimension space point {"language":"mul"}
    And VisibilityConstraints are set to "frontend"
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name                | NodeDiscriminator                                        |
      | preceding-document  | cs-identifier;preceding-nodenborough;{"language":"mul"}  |
      | succeeding-document | cs-identifier;succeeding-nodenborough;{"language":"mul"} |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | NodeAggregateIdentifier |
      | 0     | lady-eleonode-rootford  |
      | 1     | preceding-nodenborough  |
      | 1     | succeeding-nodenborough |
    And I expect node aggregate identifier "preceding-nodenborough" and node path "preceding-document" to lead to node cs-identifier;preceding-nodenborough;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;succeeding-nodenborough;{"language":"mul"} |
    And I expect this node to have no references
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to no node
    And I expect node aggregate identifier "succeeding-nodenborough" and node path "succeeding-document" to lead to node cs-identifier;succeeding-nodenborough;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;preceding-nodenborough;{"language":"mul"} |
    And I expect this node to have no succeeding siblings
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to no node
    And I expect node aggregate identifier "the-great-nodini" and node path "document/court-magician" to lead to no node

    # Tests for the virtual specialization
    When I am in dimension space point {"language":"gsw"}
    And VisibilityConstraints are set to "frontend"
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name                | NodeDiscriminator                                        |
      | preceding-document  | cs-identifier;preceding-nodenborough;{"language":"mul"}  |
      | succeeding-document | cs-identifier;succeeding-nodenborough;{"language":"mul"} |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | NodeAggregateIdentifier |
      | 0     | lady-eleonode-rootford  |
      | 1     | preceding-nodenborough  |
      | 1     | succeeding-nodenborough |
    And I expect node aggregate identifier "preceding-nodenborough" and node path "preceding-document" to lead to node cs-identifier;preceding-nodenborough;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;succeeding-nodenborough;{"language":"mul"} |
    And I expect this node to have no references
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to no node
    And I expect node aggregate identifier "succeeding-nodenborough" and node path "succeeding-document" to lead to node cs-identifier;succeeding-nodenborough;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;preceding-nodenborough;{"language":"mul"} |
    And I expect this node to have no succeeding siblings
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to no node
    And I expect node aggregate identifier "the-great-nodini" and node path "document/court-magician" to lead to no node

    # Tests for the real specialization
    When I am in dimension space point {"language":"ltz"}
    And VisibilityConstraints are set to "frontend"
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name                | NodeDiscriminator                                        |
      | preceding-document  | cs-identifier;preceding-nodenborough;{"language":"mul"}  |
      | succeeding-document | cs-identifier;succeeding-nodenborough;{"language":"mul"} |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | NodeAggregateIdentifier |
      | 0     | lady-eleonode-rootford  |
      | 1     | preceding-nodenborough  |
      | 1     | succeeding-nodenborough |
    And I expect node aggregate identifier "preceding-nodenborough" and node path "preceding-document" to lead to node cs-identifier;preceding-nodenborough;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;succeeding-nodenborough;{"language":"mul"} |
    And I expect this node to have no references
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to no node
    And I expect node aggregate identifier "succeeding-nodenborough" and node path "succeeding-document" to lead to node cs-identifier;succeeding-nodenborough;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;preceding-nodenborough;{"language":"mul"} |
    And I expect this node to have no succeeding siblings
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to no node
    And I expect node aggregate identifier "the-great-nodini" and node path "document/court-magician" to lead to no node

    # Tests for the peer variant
    When I am in dimension space point {"language":"en"}
    And VisibilityConstraints are set to "frontend"
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name                | NodeDiscriminator                                        |
      | preceding-document  | cs-identifier;preceding-nodenborough;{"language":"mul"}  |
      | succeeding-document | cs-identifier;succeeding-nodenborough;{"language":"mul"} |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | NodeAggregateIdentifier |
      | 0     | lady-eleonode-rootford  |
      | 1     | preceding-nodenborough  |
      | 1     | succeeding-nodenborough |
    And I expect node aggregate identifier "preceding-nodenborough" and node path "preceding-document" to lead to node cs-identifier;preceding-nodenborough;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;succeeding-nodenborough;{"language":"mul"} |
    And I expect this node to have no references
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to no node
    And I expect node aggregate identifier "succeeding-nodenborough" and node path "succeeding-document" to lead to node cs-identifier;succeeding-nodenborough;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;preceding-nodenborough;{"language":"mul"} |
    And I expect this node to have no succeeding siblings
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to no node
    And I expect node aggregate identifier "the-great-nodini" and node path "document/court-magician" to lead to no node

  Scenario: Enable node aggregate with strategy virtualSpecializations
    When I am in dimension space point {"language":"de"}
    And the command EnableNodeAggregate is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateIdentifier      | "sir-david-nodenborough" |
      | nodeVariantSelectionStrategy | "virtualSpecializations" |

    Then I expect exactly 12 events to be published on stream with prefix "Neos.ContentRepository:ContentStream:cs-identifier"
    And event at index 11 is of type "Neos.EventSourcedContentRepository:NodeAggregateWasEnabled" with payload:
      | Key                          | Expected                               |
      | contentStreamIdentifier      | "cs-identifier"                        |
      | nodeAggregateIdentifier      | "sir-david-nodenborough"               |
      | affectedDimensionSpacePoints | [{"language":"de"},{"language":"gsw"}] |
      | initiatingUserIdentifier     | "initiating-user-identifier"           |

    When the graph projection is fully up to date
    And I am in content stream "cs-identifier"
    Then I expect the graph projection to consist of exactly 7 nodes
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect a node identified by cs-identifier;preceding-nodenborough;{"language":"mul"} to exist in the content graph
    And I expect a node identified by cs-identifier;sir-david-nodenborough;{"language":"mul"} to exist in the content graph
    And I expect a node identified by cs-identifier;sir-david-nodenborough;{"language":"ltz"} to exist in the content graph
    And I expect a node identified by cs-identifier;succeeding-nodenborough;{"language":"mul"} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"language":"mul"} to exist in the content graph
    And I expect a node identified by cs-identifier;the-great-nodini;{"language":"mul"} to exist in the content graph

    And I expect the node aggregate "sir-david-nodenborough" to exist
    And I expect this node aggregate to disable dimension space points [{"language":"mul"},{"language":"ltz"},{"language":"en"}]

    And I expect the node aggregate "the-great-nodini" to exist
    And I expect this node aggregate to disable dimension space points [{"language":"mul"},{"language":"de"},{"language":"ltz"},{"language":"gsw"},{"language":"en"}]

    # Tests for the given variant
    When I am in dimension space point {"language":"de"}
    And VisibilityConstraints are set to "withoutRestrictions"
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name                | NodeDiscriminator                                        |
      | preceding-document  | cs-identifier;preceding-nodenborough;{"language":"mul"}  |
      | document            | cs-identifier;sir-david-nodenborough;{"language":"mul"}  |
      | succeeding-document | cs-identifier;succeeding-nodenborough;{"language":"mul"} |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | NodeAggregateIdentifier |
      | 0     | lady-eleonode-rootford  |
      | 1     | preceding-nodenborough  |
      | 1     | sir-david-nodenborough  |
      | 2     | nody-mc-nodeface        |
      | 2     | the-great-nodini        |
      | 1     | succeeding-nodenborough |
    And I expect node aggregate identifier "preceding-nodenborough" and node path "preceding-document" to lead to node cs-identifier;preceding-nodenborough;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;sir-david-nodenborough;{"language":"mul"}  |
      | cs-identifier;succeeding-nodenborough;{"language":"mul"} |
    And I expect this node to have the following references:
      | Key        | Value                                                           |
      | references | ["cs-identifier;sir-david-nodenborough;{\"language\":\"mul\"}"] |
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name           | NodeDiscriminator                                 |
      | child-document | cs-identifier;nody-mc-nodeface;{"language":"mul"} |
      | court-magician | cs-identifier;the-great-nodini;{"language":"mul"} |
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;preceding-nodenborough;{"language":"mul"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;succeeding-nodenborough;{"language":"mul"} |
    And I expect this node to be referenced by:
      | Key        | Value                                                           |
      | references | ["cs-identifier;preceding-nodenborough;{\"language\":\"mul\"}"] |
    And I expect node aggregate identifier "succeeding-nodenborough" and node path "succeeding-document" to lead to node cs-identifier;succeeding-nodenborough;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;sir-david-nodenborough;{"language":"mul"} |
      | cs-identifier;preceding-nodenborough;{"language":"mul"} |
    And I expect this node to have no succeeding siblings
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to node cs-identifier;nody-mc-nodeface;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language":"mul"}
    And I expect node aggregate identifier "the-great-nodini" and node path "document/court-magician" to lead to node cs-identifier;the-great-nodini;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language":"mul"}

    When VisibilityConstraints are set to "frontend"
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name                | NodeDiscriminator                                        |
      | preceding-document  | cs-identifier;preceding-nodenborough;{"language":"mul"}  |
      | document            | cs-identifier;sir-david-nodenborough;{"language":"mul"}  |
      | succeeding-document | cs-identifier;succeeding-nodenborough;{"language":"mul"} |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | NodeAggregateIdentifier |
      | 0     | lady-eleonode-rootford  |
      | 1     | preceding-nodenborough  |
      | 1     | sir-david-nodenborough  |
      | 2     | nody-mc-nodeface        |
      | 1     | succeeding-nodenborough |
    And I expect node aggregate identifier "preceding-nodenborough" and node path "preceding-document" to lead to node cs-identifier;preceding-nodenborough;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;sir-david-nodenborough;{"language":"mul"}  |
      | cs-identifier;succeeding-nodenborough;{"language":"mul"} |
    And I expect this node to have the following references:
      | Key        | Value                                                           |
      | references | ["cs-identifier;sir-david-nodenborough;{\"language\":\"mul\"}"] |
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name           | NodeDiscriminator                                 |
      | child-document | cs-identifier;nody-mc-nodeface;{"language":"mul"} |
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;preceding-nodenborough;{"language":"mul"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;succeeding-nodenborough;{"language":"mul"} |
    And I expect this node to be referenced by:
      | Key        | Value                                                           |
      | references | ["cs-identifier;preceding-nodenborough;{\"language\":\"mul\"}"] |
    And I expect node aggregate identifier "succeeding-nodenborough" and node path "succeeding-document" to lead to node cs-identifier;succeeding-nodenborough;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;sir-david-nodenborough;{"language":"mul"} |
      | cs-identifier;preceding-nodenborough;{"language":"mul"} |
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to node cs-identifier;nody-mc-nodeface;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language":"mul"}
    And I expect node aggregate identifier "the-great-nodini" and node path "document/court-magician" to lead to no node

    # Tests for the generalization
    When I am in dimension space point {"language":"mul"}
    And VisibilityConstraints are set to "frontend"
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name                | NodeDiscriminator                                        |
      | preceding-document  | cs-identifier;preceding-nodenborough;{"language":"mul"}  |
      | succeeding-document | cs-identifier;succeeding-nodenborough;{"language":"mul"} |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | NodeAggregateIdentifier |
      | 0     | lady-eleonode-rootford  |
      | 1     | preceding-nodenborough  |
      | 1     | succeeding-nodenborough |
    And I expect node aggregate identifier "preceding-nodenborough" and node path "preceding-document" to lead to node cs-identifier;preceding-nodenborough;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;succeeding-nodenborough;{"language":"mul"} |
    And I expect this node to have no references
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to no node
    And I expect node aggregate identifier "succeeding-nodenborough" and node path "succeeding-document" to lead to node cs-identifier;succeeding-nodenborough;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;preceding-nodenborough;{"language":"mul"} |
    And I expect this node to have no succeeding siblings
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to no node
    And I expect node aggregate identifier "the-great-nodini" and node path "document/court-magician" to lead to no node

    # Tests for the virtual specialization
    When I am in dimension space point {"language":"gsw"}
    And VisibilityConstraints are set to "frontend"
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name                | NodeDiscriminator                                        |
      | preceding-document  | cs-identifier;preceding-nodenborough;{"language":"mul"}  |
      | document            | cs-identifier;sir-david-nodenborough;{"language":"mul"}  |
      | succeeding-document | cs-identifier;succeeding-nodenborough;{"language":"mul"} |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | NodeAggregateIdentifier |
      | 0     | lady-eleonode-rootford  |
      | 1     | preceding-nodenborough  |
      | 1     | sir-david-nodenborough  |
      | 2     | nody-mc-nodeface        |
      | 1     | succeeding-nodenborough |
    And I expect node aggregate identifier "preceding-nodenborough" and node path "preceding-document" to lead to node cs-identifier;preceding-nodenborough;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;sir-david-nodenborough;{"language":"mul"}  |
      | cs-identifier;succeeding-nodenborough;{"language":"mul"} |
    And I expect this node to have the following references:
      | Key        | Value                                                           |
      | references | ["cs-identifier;sir-david-nodenborough;{\"language\":\"mul\"}"] |
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name           | NodeDiscriminator                                 |
      | child-document | cs-identifier;nody-mc-nodeface;{"language":"mul"} |
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;preceding-nodenborough;{"language":"mul"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;succeeding-nodenborough;{"language":"mul"} |
    And I expect this node to be referenced by:
      | Key        | Value                                                           |
      | references | ["cs-identifier;preceding-nodenborough;{\"language\":\"mul\"}"] |
    And I expect node aggregate identifier "succeeding-nodenborough" and node path "succeeding-document" to lead to node cs-identifier;succeeding-nodenborough;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;sir-david-nodenborough;{"language":"mul"} |
      | cs-identifier;preceding-nodenborough;{"language":"mul"} |
    And I expect this node to have no succeeding siblings
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to node cs-identifier;nody-mc-nodeface;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language":"mul"}
    And I expect node aggregate identifier "the-great-nodini" and node path "document/court-magician" to lead to no node

    # Tests for the real specialization
    When I am in dimension space point {"language":"ltz"}
    And VisibilityConstraints are set to "frontend"
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name                | NodeDiscriminator                                        |
      | preceding-document  | cs-identifier;preceding-nodenborough;{"language":"mul"}  |
      | succeeding-document | cs-identifier;succeeding-nodenborough;{"language":"mul"} |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | NodeAggregateIdentifier |
      | 0     | lady-eleonode-rootford  |
      | 1     | preceding-nodenborough  |
      | 1     | succeeding-nodenborough |
    And I expect node aggregate identifier "preceding-nodenborough" and node path "preceding-document" to lead to node cs-identifier;preceding-nodenborough;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;succeeding-nodenborough;{"language":"mul"} |
    And I expect this node to have no references
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to no node
    And I expect node aggregate identifier "succeeding-nodenborough" and node path "succeeding-document" to lead to node cs-identifier;succeeding-nodenborough;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;preceding-nodenborough;{"language":"mul"} |
    And I expect this node to have no succeeding siblings
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to no node
    And I expect node aggregate identifier "the-great-nodini" and node path "document/court-magician" to lead to no node

    # Tests for the peer variant
    When I am in dimension space point {"language":"en"}
    And VisibilityConstraints are set to "frontend"
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name                | NodeDiscriminator                                        |
      | preceding-document  | cs-identifier;preceding-nodenborough;{"language":"mul"}  |
      | succeeding-document | cs-identifier;succeeding-nodenborough;{"language":"mul"} |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | NodeAggregateIdentifier |
      | 0     | lady-eleonode-rootford  |
      | 1     | preceding-nodenborough  |
      | 1     | succeeding-nodenborough |
    And I expect node aggregate identifier "preceding-nodenborough" and node path "preceding-document" to lead to node cs-identifier;preceding-nodenborough;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;succeeding-nodenborough;{"language":"mul"} |
    And I expect this node to have no references
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to no node
    And I expect node aggregate identifier "succeeding-nodenborough" and node path "succeeding-document" to lead to node cs-identifier;succeeding-nodenborough;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;preceding-nodenborough;{"language":"mul"} |
    And I expect this node to have no succeeding siblings
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to no node
    And I expect node aggregate identifier "the-great-nodini" and node path "document/court-magician" to lead to no node

  Scenario: Enable node aggregate with strategy allSpecializations
    When I am in dimension space point {"language":"de"}
    And the command EnableNodeAggregate is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateIdentifier      | "sir-david-nodenborough" |
      | nodeVariantSelectionStrategy | "allSpecializations"     |

    Then I expect exactly 12 events to be published on stream with prefix "Neos.ContentRepository:ContentStream:cs-identifier"
    And event at index 11 is of type "Neos.EventSourcedContentRepository:NodeAggregateWasEnabled" with payload:
      | Key                          | Expected                                                  |
      | contentStreamIdentifier      | "cs-identifier"                                           |
      | nodeAggregateIdentifier      | "sir-david-nodenborough"                                  |
      | affectedDimensionSpacePoints | [{"language":"de"},{"language":"ltz"},{"language":"gsw"}] |
      | initiatingUserIdentifier     | "initiating-user-identifier"                              |

    When the graph projection is fully up to date
    And I am in content stream "cs-identifier"
    Then I expect the graph projection to consist of exactly 7 nodes
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect a node identified by cs-identifier;preceding-nodenborough;{"language":"mul"} to exist in the content graph
    And I expect a node identified by cs-identifier;sir-david-nodenborough;{"language":"mul"} to exist in the content graph
    And I expect a node identified by cs-identifier;sir-david-nodenborough;{"language":"ltz"} to exist in the content graph
    And I expect a node identified by cs-identifier;succeeding-nodenborough;{"language":"mul"} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"language":"mul"} to exist in the content graph
    And I expect a node identified by cs-identifier;the-great-nodini;{"language":"mul"} to exist in the content graph

    And I expect the node aggregate "sir-david-nodenborough" to exist
    And I expect this node aggregate to disable dimension space points [{"language":"mul"},{"language":"en"}]

    And I expect the node aggregate "the-great-nodini" to exist
    And I expect this node aggregate to disable dimension space points [{"language":"mul"},{"language":"de"},{"language":"ltz"},{"language":"gsw"},{"language":"en"}]

    # Tests for the given variant
    When I am in dimension space point {"language":"de"}
    And VisibilityConstraints are set to "withoutRestrictions"
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name                | NodeDiscriminator                                        |
      | preceding-document  | cs-identifier;preceding-nodenborough;{"language":"mul"}  |
      | document            | cs-identifier;sir-david-nodenborough;{"language":"mul"}  |
      | succeeding-document | cs-identifier;succeeding-nodenborough;{"language":"mul"} |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | NodeAggregateIdentifier |
      | 0     | lady-eleonode-rootford  |
      | 1     | preceding-nodenborough  |
      | 1     | sir-david-nodenborough  |
      | 2     | nody-mc-nodeface        |
      | 2     | the-great-nodini        |
      | 1     | succeeding-nodenborough |
    And I expect node aggregate identifier "preceding-nodenborough" and node path "preceding-document" to lead to node cs-identifier;preceding-nodenborough;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;sir-david-nodenborough;{"language":"mul"}  |
      | cs-identifier;succeeding-nodenborough;{"language":"mul"} |
    And I expect this node to have the following references:
      | Key        | Value                                                           |
      | references | ["cs-identifier;sir-david-nodenborough;{\"language\":\"mul\"}"] |
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name           | NodeDiscriminator                                 |
      | child-document | cs-identifier;nody-mc-nodeface;{"language":"mul"} |
      | court-magician | cs-identifier;the-great-nodini;{"language":"mul"} |
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;preceding-nodenborough;{"language":"mul"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;succeeding-nodenborough;{"language":"mul"} |
    And I expect this node to be referenced by:
      | Key        | Value                                                           |
      | references | ["cs-identifier;preceding-nodenborough;{\"language\":\"mul\"}"] |
    And I expect node aggregate identifier "succeeding-nodenborough" and node path "succeeding-document" to lead to node cs-identifier;succeeding-nodenborough;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;sir-david-nodenborough;{"language":"mul"} |
      | cs-identifier;preceding-nodenborough;{"language":"mul"} |
    And I expect this node to have no succeeding siblings
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to node cs-identifier;nody-mc-nodeface;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language":"mul"}
    And I expect node aggregate identifier "the-great-nodini" and node path "document/court-magician" to lead to node cs-identifier;the-great-nodini;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language":"mul"}

    When VisibilityConstraints are set to "frontend"
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name                | NodeDiscriminator                                        |
      | preceding-document  | cs-identifier;preceding-nodenborough;{"language":"mul"}  |
      | document            | cs-identifier;sir-david-nodenborough;{"language":"mul"}  |
      | succeeding-document | cs-identifier;succeeding-nodenborough;{"language":"mul"} |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | NodeAggregateIdentifier |
      | 0     | lady-eleonode-rootford  |
      | 1     | preceding-nodenborough  |
      | 1     | sir-david-nodenborough  |
      | 2     | nody-mc-nodeface        |
      | 1     | succeeding-nodenborough |
    And I expect node aggregate identifier "preceding-nodenborough" and node path "preceding-document" to lead to node cs-identifier;preceding-nodenborough;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;sir-david-nodenborough;{"language":"mul"}  |
      | cs-identifier;succeeding-nodenborough;{"language":"mul"} |
    And I expect this node to have the following references:
      | Key        | Value                                                           |
      | references | ["cs-identifier;sir-david-nodenborough;{\"language\":\"mul\"}"] |
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name           | NodeDiscriminator                                 |
      | child-document | cs-identifier;nody-mc-nodeface;{"language":"mul"} |
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;preceding-nodenborough;{"language":"mul"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;succeeding-nodenborough;{"language":"mul"} |
    And I expect this node to be referenced by:
      | Key        | Value                                                           |
      | references | ["cs-identifier;preceding-nodenborough;{\"language\":\"mul\"}"] |
    And I expect node aggregate identifier "succeeding-nodenborough" and node path "succeeding-document" to lead to node cs-identifier;succeeding-nodenborough;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;sir-david-nodenborough;{"language":"mul"} |
      | cs-identifier;preceding-nodenborough;{"language":"mul"} |
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to node cs-identifier;nody-mc-nodeface;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language":"mul"}
    And I expect node aggregate identifier "the-great-nodini" and node path "document/court-magician" to lead to no node

    # Tests for the generalization
    When I am in dimension space point {"language":"mul"}
    And VisibilityConstraints are set to "frontend"
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name                | NodeDiscriminator                                        |
      | preceding-document  | cs-identifier;preceding-nodenborough;{"language":"mul"}  |
      | succeeding-document | cs-identifier;succeeding-nodenborough;{"language":"mul"} |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | NodeAggregateIdentifier |
      | 0     | lady-eleonode-rootford  |
      | 1     | preceding-nodenborough  |
      | 1     | succeeding-nodenborough |
    And I expect node aggregate identifier "preceding-nodenborough" and node path "preceding-document" to lead to node cs-identifier;preceding-nodenborough;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;succeeding-nodenborough;{"language":"mul"} |
    And I expect this node to have no references
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to no node
    And I expect node aggregate identifier "succeeding-nodenborough" and node path "succeeding-document" to lead to node cs-identifier;succeeding-nodenborough;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;preceding-nodenborough;{"language":"mul"} |
    And I expect this node to have no succeeding siblings
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to no node
    And I expect node aggregate identifier "the-great-nodini" and node path "document/court-magician" to lead to no node

    # Tests for the virtual specialization
    When I am in dimension space point {"language":"gsw"}
    And VisibilityConstraints are set to "frontend"
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name                | NodeDiscriminator                                        |
      | preceding-document  | cs-identifier;preceding-nodenborough;{"language":"mul"}  |
      | document            | cs-identifier;sir-david-nodenborough;{"language":"mul"}  |
      | succeeding-document | cs-identifier;succeeding-nodenborough;{"language":"mul"} |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | NodeAggregateIdentifier |
      | 0     | lady-eleonode-rootford  |
      | 1     | preceding-nodenborough  |
      | 1     | sir-david-nodenborough  |
      | 2     | nody-mc-nodeface        |
      | 1     | succeeding-nodenborough |
    And I expect node aggregate identifier "preceding-nodenborough" and node path "preceding-document" to lead to node cs-identifier;preceding-nodenborough;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;sir-david-nodenborough;{"language":"mul"}  |
      | cs-identifier;succeeding-nodenborough;{"language":"mul"} |
    And I expect this node to have the following references:
      | Key        | Value                                                           |
      | references | ["cs-identifier;sir-david-nodenborough;{\"language\":\"mul\"}"] |
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name           | NodeDiscriminator                                 |
      | child-document | cs-identifier;nody-mc-nodeface;{"language":"mul"} |
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;preceding-nodenborough;{"language":"mul"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;succeeding-nodenborough;{"language":"mul"} |
    And I expect this node to be referenced by:
      | Key        | Value                                                           |
      | references | ["cs-identifier;preceding-nodenborough;{\"language\":\"mul\"}"] |
    And I expect node aggregate identifier "succeeding-nodenborough" and node path "succeeding-document" to lead to node cs-identifier;succeeding-nodenborough;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;sir-david-nodenborough;{"language":"mul"} |
      | cs-identifier;preceding-nodenborough;{"language":"mul"} |
    And I expect this node to have no succeeding siblings
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to node cs-identifier;nody-mc-nodeface;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language":"mul"}
    And I expect node aggregate identifier "the-great-nodini" and node path "document/court-magician" to lead to no node

    # Tests for the real specialization
    When I am in dimension space point {"language":"ltz"}
    And VisibilityConstraints are set to "frontend"
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name                | NodeDiscriminator                                        |
      | preceding-document  | cs-identifier;preceding-nodenborough;{"language":"mul"}  |
      | document            | cs-identifier;sir-david-nodenborough;{"language":"ltz"}  |
      | succeeding-document | cs-identifier;succeeding-nodenborough;{"language":"mul"} |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | NodeAggregateIdentifier |
      | 0     | lady-eleonode-rootford  |
      | 1     | preceding-nodenborough  |
      | 1     | sir-david-nodenborough  |
      | 2     | nody-mc-nodeface        |
      | 1     | succeeding-nodenborough |
    And I expect node aggregate identifier "preceding-nodenborough" and node path "preceding-document" to lead to node cs-identifier;preceding-nodenborough;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;sir-david-nodenborough;{"language":"ltz"}  |
      | cs-identifier;succeeding-nodenborough;{"language":"mul"} |
    And I expect this node to have the following references:
      | Key        | Value                                                           |
      | references | ["cs-identifier;sir-david-nodenborough;{\"language\":\"ltz\"}"] |
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"language":"ltz"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name           | NodeDiscriminator                                 |
      | child-document | cs-identifier;nody-mc-nodeface;{"language":"mul"} |
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;preceding-nodenborough;{"language":"mul"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;succeeding-nodenborough;{"language":"mul"} |
    And I expect this node to be referenced by:
      | Key        | Value                                                           |
      | references | ["cs-identifier;preceding-nodenborough;{\"language\":\"mul\"}"] |
    And I expect node aggregate identifier "succeeding-nodenborough" and node path "succeeding-document" to lead to node cs-identifier;succeeding-nodenborough;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;sir-david-nodenborough;{"language":"ltz"} |
      | cs-identifier;preceding-nodenborough;{"language":"mul"} |
    And I expect this node to have no succeeding siblings
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to node cs-identifier;nody-mc-nodeface;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language":"ltz"}
    And I expect node aggregate identifier "the-great-nodini" and node path "document/court-magician" to lead to no node

    # Tests for the peer variant
    When I am in dimension space point {"language":"en"}
    And VisibilityConstraints are set to "frontend"
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name                | NodeDiscriminator                                        |
      | preceding-document  | cs-identifier;preceding-nodenborough;{"language":"mul"}  |
      | succeeding-document | cs-identifier;succeeding-nodenborough;{"language":"mul"} |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | NodeAggregateIdentifier |
      | 0     | lady-eleonode-rootford  |
      | 1     | preceding-nodenborough  |
      | 1     | succeeding-nodenborough |
    And I expect node aggregate identifier "preceding-nodenborough" and node path "preceding-document" to lead to node cs-identifier;preceding-nodenborough;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;succeeding-nodenborough;{"language":"mul"} |
    And I expect this node to have no references
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to no node
    And I expect node aggregate identifier "succeeding-nodenborough" and node path "succeeding-document" to lead to node cs-identifier;succeeding-nodenborough;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;preceding-nodenborough;{"language":"mul"} |
    And I expect this node to have no succeeding siblings
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to no node
    And I expect node aggregate identifier "the-great-nodini" and node path "document/court-magician" to lead to no node

  Scenario: Enable node aggregate with strategy allVariants
    When I am in dimension space point {"language":"de"}
    And the command EnableNodeAggregate is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateIdentifier      | "sir-david-nodenborough" |
      | nodeVariantSelectionStrategy | "allVariants"            |

    Then I expect exactly 12 events to be published on stream with prefix "Neos.ContentRepository:ContentStream:cs-identifier"
    And event at index 11 is of type "Neos.EventSourcedContentRepository:NodeAggregateWasEnabled" with payload:
      | Key                          | Expected                                                                                       |
      | contentStreamIdentifier      | "cs-identifier"                                                                                |
      | nodeAggregateIdentifier      | "sir-david-nodenborough"                                                                       |
      | affectedDimensionSpacePoints | [{"language":"ltz"},{"language":"mul"},{"language":"de"},{"language":"en"},{"language":"gsw"}] |
      | initiatingUserIdentifier     | "initiating-user-identifier"                                                                   |

    When the graph projection is fully up to date
    And I am in content stream "cs-identifier"
    Then I expect the graph projection to consist of exactly 7 nodes
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect a node identified by cs-identifier;preceding-nodenborough;{"language":"mul"} to exist in the content graph
    And I expect a node identified by cs-identifier;sir-david-nodenborough;{"language":"mul"} to exist in the content graph
    And I expect a node identified by cs-identifier;sir-david-nodenborough;{"language":"ltz"} to exist in the content graph
    And I expect a node identified by cs-identifier;succeeding-nodenborough;{"language":"mul"} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"language":"mul"} to exist in the content graph
    And I expect a node identified by cs-identifier;the-great-nodini;{"language":"mul"} to exist in the content graph

    And I expect the node aggregate "sir-david-nodenborough" to exist
    And I expect this node aggregate to disable dimension space points []

    And I expect the node aggregate "the-great-nodini" to exist
    And I expect this node aggregate to disable dimension space points [{"language":"mul"},{"language":"de"},{"language":"ltz"},{"language":"gsw"},{"language":"en"}]

    # Tests for the given variant
    When I am in dimension space point {"language":"de"}
    And VisibilityConstraints are set to "withoutRestrictions"
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name                | NodeDiscriminator                                        |
      | preceding-document  | cs-identifier;preceding-nodenborough;{"language":"mul"}  |
      | document            | cs-identifier;sir-david-nodenborough;{"language":"mul"}  |
      | succeeding-document | cs-identifier;succeeding-nodenborough;{"language":"mul"} |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | NodeAggregateIdentifier |
      | 0     | lady-eleonode-rootford  |
      | 1     | preceding-nodenborough  |
      | 1     | sir-david-nodenborough  |
      | 2     | nody-mc-nodeface        |
      | 2     | the-great-nodini        |
      | 1     | succeeding-nodenborough |
    And I expect node aggregate identifier "preceding-nodenborough" and node path "preceding-document" to lead to node cs-identifier;preceding-nodenborough;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;sir-david-nodenborough;{"language":"mul"}  |
      | cs-identifier;succeeding-nodenborough;{"language":"mul"} |
    And I expect this node to have the following references:
      | Key        | Value                                                           |
      | references | ["cs-identifier;sir-david-nodenborough;{\"language\":\"mul\"}"] |
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name           | NodeDiscriminator                                 |
      | child-document | cs-identifier;nody-mc-nodeface;{"language":"mul"} |
      | court-magician | cs-identifier;the-great-nodini;{"language":"mul"} |
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;preceding-nodenborough;{"language":"mul"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;succeeding-nodenborough;{"language":"mul"} |
    And I expect this node to be referenced by:
      | Key        | Value                                                           |
      | references | ["cs-identifier;preceding-nodenborough;{\"language\":\"mul\"}"] |
    And I expect node aggregate identifier "succeeding-nodenborough" and node path "succeeding-document" to lead to node cs-identifier;succeeding-nodenborough;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;sir-david-nodenborough;{"language":"mul"} |
      | cs-identifier;preceding-nodenborough;{"language":"mul"} |
    And I expect this node to have no succeeding siblings
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to node cs-identifier;nody-mc-nodeface;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language":"mul"}
    And I expect node aggregate identifier "the-great-nodini" and node path "document/court-magician" to lead to node cs-identifier;the-great-nodini;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language":"mul"}

    When VisibilityConstraints are set to "frontend"
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name                | NodeDiscriminator                                        |
      | preceding-document  | cs-identifier;preceding-nodenborough;{"language":"mul"}  |
      | document            | cs-identifier;sir-david-nodenborough;{"language":"mul"}  |
      | succeeding-document | cs-identifier;succeeding-nodenborough;{"language":"mul"} |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | NodeAggregateIdentifier |
      | 0     | lady-eleonode-rootford  |
      | 1     | preceding-nodenborough  |
      | 1     | sir-david-nodenborough  |
      | 2     | nody-mc-nodeface        |
      | 1     | succeeding-nodenborough |
    And I expect node aggregate identifier "preceding-nodenborough" and node path "preceding-document" to lead to node cs-identifier;preceding-nodenborough;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;sir-david-nodenborough;{"language":"mul"}  |
      | cs-identifier;succeeding-nodenborough;{"language":"mul"} |
    And I expect this node to have the following references:
      | Key        | Value                                                           |
      | references | ["cs-identifier;sir-david-nodenborough;{\"language\":\"mul\"}"] |
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name           | NodeDiscriminator                                 |
      | child-document | cs-identifier;nody-mc-nodeface;{"language":"mul"} |
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;preceding-nodenborough;{"language":"mul"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;succeeding-nodenborough;{"language":"mul"} |
    And I expect this node to be referenced by:
      | Key        | Value                                                           |
      | references | ["cs-identifier;preceding-nodenborough;{\"language\":\"mul\"}"] |
    And I expect node aggregate identifier "succeeding-nodenborough" and node path "succeeding-document" to lead to node cs-identifier;succeeding-nodenborough;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;sir-david-nodenborough;{"language":"mul"} |
      | cs-identifier;preceding-nodenborough;{"language":"mul"} |
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to node cs-identifier;nody-mc-nodeface;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language":"mul"}
    And I expect node aggregate identifier "the-great-nodini" and node path "document/court-magician" to lead to no node

    # Tests for the generalization
    When I am in dimension space point {"language":"mul"}
    And VisibilityConstraints are set to "frontend"
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name                | NodeDiscriminator                                        |
      | preceding-document  | cs-identifier;preceding-nodenborough;{"language":"mul"}  |
      | document            | cs-identifier;sir-david-nodenborough;{"language":"mul"}  |
      | succeeding-document | cs-identifier;succeeding-nodenborough;{"language":"mul"} |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | NodeAggregateIdentifier |
      | 0     | lady-eleonode-rootford  |
      | 1     | preceding-nodenborough  |
      | 1     | sir-david-nodenborough  |
      | 2     | nody-mc-nodeface        |
      | 1     | succeeding-nodenborough |
    And I expect node aggregate identifier "preceding-nodenborough" and node path "preceding-document" to lead to node cs-identifier;preceding-nodenborough;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;sir-david-nodenborough;{"language":"mul"}  |
      | cs-identifier;succeeding-nodenborough;{"language":"mul"} |
    And I expect this node to have the following references:
      | Key        | Value                                                           |
      | references | ["cs-identifier;sir-david-nodenborough;{\"language\":\"mul\"}"] |
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name           | NodeDiscriminator                                 |
      | child-document | cs-identifier;nody-mc-nodeface;{"language":"mul"} |
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;preceding-nodenborough;{"language":"mul"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;succeeding-nodenborough;{"language":"mul"} |
    And I expect this node to be referenced by:
      | Key        | Value                                                           |
      | references | ["cs-identifier;preceding-nodenborough;{\"language\":\"mul\"}"] |
    And I expect node aggregate identifier "succeeding-nodenborough" and node path "succeeding-document" to lead to node cs-identifier;succeeding-nodenborough;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;sir-david-nodenborough;{"language":"mul"} |
      | cs-identifier;preceding-nodenborough;{"language":"mul"} |
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to node cs-identifier;nody-mc-nodeface;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language":"mul"}
    And I expect node aggregate identifier "the-great-nodini" and node path "document/court-magician" to lead to no node

    # Tests for the virtual specialization
    When I am in dimension space point {"language":"gsw"}
    And VisibilityConstraints are set to "frontend"
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name                | NodeDiscriminator                                        |
      | preceding-document  | cs-identifier;preceding-nodenborough;{"language":"mul"}  |
      | document            | cs-identifier;sir-david-nodenborough;{"language":"mul"}  |
      | succeeding-document | cs-identifier;succeeding-nodenborough;{"language":"mul"} |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | NodeAggregateIdentifier |
      | 0     | lady-eleonode-rootford  |
      | 1     | preceding-nodenborough  |
      | 1     | sir-david-nodenborough  |
      | 2     | nody-mc-nodeface        |
      | 1     | succeeding-nodenborough |
    And I expect node aggregate identifier "preceding-nodenborough" and node path "preceding-document" to lead to node cs-identifier;preceding-nodenborough;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;sir-david-nodenborough;{"language":"mul"}  |
      | cs-identifier;succeeding-nodenborough;{"language":"mul"} |
    And I expect this node to have the following references:
      | Key        | Value                                                           |
      | references | ["cs-identifier;sir-david-nodenborough;{\"language\":\"mul\"}"] |
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name           | NodeDiscriminator                                 |
      | child-document | cs-identifier;nody-mc-nodeface;{"language":"mul"} |
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;preceding-nodenborough;{"language":"mul"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;succeeding-nodenborough;{"language":"mul"} |
    And I expect this node to be referenced by:
      | Key        | Value                                                           |
      | references | ["cs-identifier;preceding-nodenborough;{\"language\":\"mul\"}"] |
    And I expect node aggregate identifier "succeeding-nodenborough" and node path "succeeding-document" to lead to node cs-identifier;succeeding-nodenborough;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;sir-david-nodenborough;{"language":"mul"} |
      | cs-identifier;preceding-nodenborough;{"language":"mul"} |
    And I expect this node to have no succeeding siblings
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to node cs-identifier;nody-mc-nodeface;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language":"mul"}
    And I expect node aggregate identifier "the-great-nodini" and node path "document/court-magician" to lead to no node

    # Tests for the real specialization
    When I am in dimension space point {"language":"ltz"}
    And VisibilityConstraints are set to "frontend"
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name                | NodeDiscriminator                                        |
      | preceding-document  | cs-identifier;preceding-nodenborough;{"language":"mul"}  |
      | document            | cs-identifier;sir-david-nodenborough;{"language":"ltz"}  |
      | succeeding-document | cs-identifier;succeeding-nodenborough;{"language":"mul"} |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | NodeAggregateIdentifier |
      | 0     | lady-eleonode-rootford  |
      | 1     | preceding-nodenborough  |
      | 1     | sir-david-nodenborough  |
      | 2     | nody-mc-nodeface        |
      | 1     | succeeding-nodenborough |
    And I expect node aggregate identifier "preceding-nodenborough" and node path "preceding-document" to lead to node cs-identifier;preceding-nodenborough;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;sir-david-nodenborough;{"language":"ltz"}  |
      | cs-identifier;succeeding-nodenborough;{"language":"mul"} |
    And I expect this node to have the following references:
      | Key        | Value                                                           |
      | references | ["cs-identifier;sir-david-nodenborough;{\"language\":\"ltz\"}"] |
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"language":"ltz"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name           | NodeDiscriminator                                 |
      | child-document | cs-identifier;nody-mc-nodeface;{"language":"mul"} |
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;preceding-nodenborough;{"language":"mul"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;succeeding-nodenborough;{"language":"mul"} |
    And I expect this node to be referenced by:
      | Key        | Value                                                           |
      | references | ["cs-identifier;preceding-nodenborough;{\"language\":\"mul\"}"] |
    And I expect node aggregate identifier "succeeding-nodenborough" and node path "succeeding-document" to lead to node cs-identifier;succeeding-nodenborough;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;sir-david-nodenborough;{"language":"ltz"} |
      | cs-identifier;preceding-nodenborough;{"language":"mul"} |
    And I expect this node to have no succeeding siblings
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to node cs-identifier;nody-mc-nodeface;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language":"ltz"}
    And I expect node aggregate identifier "the-great-nodini" and node path "document/court-magician" to lead to no node

    # Tests for the peer variant
    When I am in dimension space point {"language":"en"}
    And VisibilityConstraints are set to "frontend"
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name                | NodeDiscriminator                                        |
      | preceding-document  | cs-identifier;preceding-nodenborough;{"language":"mul"}  |
      | document            | cs-identifier;sir-david-nodenborough;{"language":"mul"}  |
      | succeeding-document | cs-identifier;succeeding-nodenborough;{"language":"mul"} |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | NodeAggregateIdentifier |
      | 0     | lady-eleonode-rootford  |
      | 1     | preceding-nodenborough  |
      | 1     | sir-david-nodenborough  |
      | 2     | nody-mc-nodeface        |
      | 1     | succeeding-nodenborough |
    And I expect node aggregate identifier "preceding-nodenborough" and node path "preceding-document" to lead to node cs-identifier;preceding-nodenborough;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;sir-david-nodenborough;{"language":"mul"}  |
      | cs-identifier;succeeding-nodenborough;{"language":"mul"} |
    And I expect this node to have the following references:
      | Key        | Value                                                           |
      | references | ["cs-identifier;sir-david-nodenborough;{\"language\":\"mul\"}"] |
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name           | NodeDiscriminator                                 |
      | child-document | cs-identifier;nody-mc-nodeface;{"language":"mul"} |
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;preceding-nodenborough;{"language":"mul"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;succeeding-nodenborough;{"language":"mul"} |
    And I expect this node to be referenced by:
      | Key        | Value                                                           |
      | references | ["cs-identifier;preceding-nodenborough;{\"language\":\"mul\"}"] |
    And I expect node aggregate identifier "succeeding-nodenborough" and node path "succeeding-document" to lead to node cs-identifier;succeeding-nodenborough;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;sir-david-nodenborough;{"language":"mul"} |
      | cs-identifier;preceding-nodenborough;{"language":"mul"} |
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to node cs-identifier;nody-mc-nodeface;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language":"mul"}
    And I expect node aggregate identifier "the-great-nodini" and node path "document/court-magician" to lead to no node

  Scenario: Enable node aggregate with hidden ancestors
    When I am in dimension space point {"language":"de"}
    And the command EnableNodeAggregate is executed with payload:
      | Key                          | Value              |
      | nodeAggregateIdentifier      | "the-great-nodini" |
      | nodeVariantSelectionStrategy | "allVariants"      |

    When the graph projection is fully up to date
    And I am in content stream "cs-identifier"

    Then I expect the node aggregate "the-great-nodini" to exist
    And I expect this node aggregate to disable dimension space points []

    # Tests for the given variant
    When I am in dimension space point {"language":"de"}
    And VisibilityConstraints are set to "withoutRestrictions"
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"language":"mul"}
    And I expect this node to have the following child nodes:
      | Name           | NodeDiscriminator                                 |
      | child-document | cs-identifier;nody-mc-nodeface;{"language":"mul"} |
      | court-magician | cs-identifier;the-great-nodini;{"language":"mul"} |
    And the subtree for node aggregate "sir-david-nodenborough" with node types "" and 2 levels deep should be:
      | Level | NodeAggregateIdentifier |
      | 0     | sir-david-nodenborough  |
      | 1     | nody-mc-nodeface        |
      | 1     | the-great-nodini        |
    And I expect node aggregate identifier "the-great-nodini" and node path "document/court-magician" to lead to node cs-identifier;the-great-nodini;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language":"mul"}

    When VisibilityConstraints are set to "frontend"
    Then I expect node aggregate identifier "the-great-nodini" and node path "document/court-magician" to lead to no node

    # Tests for the generalization
    When I am in dimension space point {"language":"mul"}
    And VisibilityConstraints are set to "frontend"
    Then I expect node aggregate identifier "the-great-nodini" and node path "document/court-magician" to lead to no node

    # Tests for the virtual specialization
    When I am in dimension space point {"language":"gsw"}
    And VisibilityConstraints are set to "frontend"
    Then I expect node aggregate identifier "the-great-nodini" and node path "document/court-magician" to lead to no node

    # Tests for the real specialization
    When I am in dimension space point {"language":"ltz"}
    And VisibilityConstraints are set to "frontend"
    Then I expect node aggregate identifier "the-great-nodini" and node path "document/court-magician" to lead to no node

    # Tests for the peer variant
    When I am in dimension space point {"language":"en"}
    And VisibilityConstraints are set to "frontend"
    Then I expect node aggregate identifier "the-great-nodini" and node path "document/court-magician" to lead to no node
