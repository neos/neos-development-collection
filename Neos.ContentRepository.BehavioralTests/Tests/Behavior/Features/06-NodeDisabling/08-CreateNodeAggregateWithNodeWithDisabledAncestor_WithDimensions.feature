@fixtures @adapters=DoctrineDBAL,Postgres
Feature: Creation of nodes underneath disabled nodes

  If we create new nodes underneath of disabled nodes, they must be marked as disabled as well;
  i.e. they must have the proper restriction edges as well.

  These are the test cases with dimensions

  Background:
    Given I have the following content dimensions:
      | Identifier | Values                | Generalizations                     |
      | language   | mul, de, en, gsw, ltz | ltz->de->mul, gsw->de->mul, en->mul |
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': {}
    'Neos.ContentRepository.Testing:Document': {}
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
      | nodeAggregateIdentifier | nodeTypeName                            | parentNodeAggregateIdentifier | nodeName |
      | the-great-nodini        | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford        | document |
    # We need both a real and a virtual specialization to test the different selection strategies
    And the command CreateNodeVariant is executed with payload:
      | Key                     | Value              |
      | nodeAggregateIdentifier | "the-great-nodini" |
      | sourceOrigin            | {"language":"mul"} |
      | targetOrigin            | {"language":"ltz"} |
    And the graph projection is fully up to date
    And VisibilityConstraints are set to "frontend"

  Scenario: Create a new node with parent disabled with strategy onlyGivenVariant
    Given the command DisableNodeAggregate is executed with payload:
      | Key                          | Value              |
      | nodeAggregateIdentifier      | "the-great-nodini" |
      | coveredDimensionSpacePoint   | {"language":"de"}  |
      | nodeVariantSelectionStrategy | "onlyGivenVariant" |
    When the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateIdentifier | nodeTypeName                            | parentNodeAggregateIdentifier | nodeName     |
      | nodingers-cat           | Neos.ContentRepository.Testing:Document | the-great-nodini              | pet-document |

    Then I expect the node aggregate "nodingers-cat" to exist
    And I expect this node aggregate to disable dimension space points []

    When I am in dimension space point {"language":"mul"}
    And I expect node aggregate identifier "nodingers-cat" and node path "document/pet-document" to lead to node cs-identifier;nodingers-cat;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;the-great-nodini;{"language":"mul"}

    When I am in dimension space point {"language":"de"}
    And I expect node aggregate identifier "nodingers-cat" and node path "document/pet-document" to lead to no node

    When I am in dimension space point {"language":"gsw"}
    And I expect node aggregate identifier "nodingers-cat" and node path "document/pet-document" to lead to node cs-identifier;nodingers-cat;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;the-great-nodini;{"language":"mul"}

    When I am in dimension space point {"language":"ltz"}
    And I expect node aggregate identifier "nodingers-cat" and node path "document/pet-document" to lead to node cs-identifier;nodingers-cat;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;the-great-nodini;{"language":"ltz"}

    When I am in dimension space point {"language":"en"}
    And I expect node aggregate identifier "nodingers-cat" and node path "document/pet-document" to lead to node cs-identifier;nodingers-cat;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;the-great-nodini;{"language":"mul"}

    And the command EnableNodeAggregate is executed with payload:
      | Key                          | Value              |
      | nodeAggregateIdentifier      | "the-great-nodini" |
      | coveredDimensionSpacePoint   | {"language":"de"}  |
      | nodeVariantSelectionStrategy | "onlyGivenVariant" |
    And the graph projection is fully up to date

    When I am in dimension space point {"language":"de"}
    And I expect node aggregate identifier "nodingers-cat" and node path "document/pet-document" to lead to node cs-identifier;nodingers-cat;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;the-great-nodini;{"language":"mul"}

  Scenario: Create a new node with parent disabled with strategy virtualSpecializations
    Given the command DisableNodeAggregate is executed with payload:
      | Key                          | Value              |
      | nodeAggregateIdentifier      | "the-great-nodini" |
      | coveredDimensionSpacePoint   | {"language":"de"}  |
      | nodeVariantSelectionStrategy | "virtualSpecializations" |
    When the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateIdentifier | nodeTypeName                            | parentNodeAggregateIdentifier | nodeName     |
      | nodingers-cat           | Neos.ContentRepository.Testing:Document | the-great-nodini              | pet-document |

    Then I expect the node aggregate "nodingers-cat" to exist
    And I expect this node aggregate to disable dimension space points []

    When I am in dimension space point {"language":"mul"}
    And I expect node aggregate identifier "nodingers-cat" and node path "document/pet-document" to lead to node cs-identifier;nodingers-cat;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;the-great-nodini;{"language":"mul"}

    When I am in dimension space point {"language":"de"}
    And I expect node aggregate identifier "nodingers-cat" and node path "document/pet-document" to lead to no node

    When I am in dimension space point {"language":"gsw"}
    And I expect node aggregate identifier "nodingers-cat" and node path "document/pet-document" to lead to no node

    When I am in dimension space point {"language":"ltz"}
    And I expect node aggregate identifier "nodingers-cat" and node path "document/pet-document" to lead to node cs-identifier;nodingers-cat;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;the-great-nodini;{"language":"ltz"}

    When I am in dimension space point {"language":"en"}
    And I expect node aggregate identifier "nodingers-cat" and node path "document/pet-document" to lead to node cs-identifier;nodingers-cat;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;the-great-nodini;{"language":"mul"}

    And the command EnableNodeAggregate is executed with payload:
      | Key                          | Value              |
      | nodeAggregateIdentifier      | "the-great-nodini" |
      | coveredDimensionSpacePoint   | {"language":"de"}  |
      | nodeVariantSelectionStrategy | "virtualSpecializations" |
    And the graph projection is fully up to date

    When I am in dimension space point {"language":"de"}
    And I expect node aggregate identifier "nodingers-cat" and node path "document/pet-document" to lead to node cs-identifier;nodingers-cat;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;the-great-nodini;{"language":"mul"}

    When I am in dimension space point {"language":"gsw"}
    And I expect node aggregate identifier "nodingers-cat" and node path "document/pet-document" to lead to node cs-identifier;nodingers-cat;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;the-great-nodini;{"language":"mul"}

  Scenario: Create a new node with parent disabled with strategy allSpecializations
    Given the command DisableNodeAggregate is executed with payload:
      | Key                          | Value              |
      | nodeAggregateIdentifier      | "the-great-nodini" |
      | coveredDimensionSpacePoint   | {"language":"de"}  |
      | nodeVariantSelectionStrategy | "allSpecializations" |
    When the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateIdentifier | nodeTypeName                            | parentNodeAggregateIdentifier | nodeName     |
      | nodingers-cat           | Neos.ContentRepository.Testing:Document | the-great-nodini              | pet-document |

    Then I expect the node aggregate "nodingers-cat" to exist
    And I expect this node aggregate to disable dimension space points []

    When I am in dimension space point {"language":"mul"}
    And I expect node aggregate identifier "nodingers-cat" and node path "document/pet-document" to lead to node cs-identifier;nodingers-cat;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;the-great-nodini;{"language":"mul"}

    When I am in dimension space point {"language":"de"}
    And I expect node aggregate identifier "nodingers-cat" and node path "document/pet-document" to lead to no node

    When I am in dimension space point {"language":"gsw"}
    And I expect node aggregate identifier "nodingers-cat" and node path "document/pet-document" to lead to no node

    When I am in dimension space point {"language":"ltz"}
    And I expect node aggregate identifier "nodingers-cat" and node path "document/pet-document" to lead to no node

    When I am in dimension space point {"language":"en"}
    And I expect node aggregate identifier "nodingers-cat" and node path "document/pet-document" to lead to node cs-identifier;nodingers-cat;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;the-great-nodini;{"language":"mul"}

    And the command EnableNodeAggregate is executed with payload:
      | Key                          | Value              |
      | nodeAggregateIdentifier      | "the-great-nodini" |
      | coveredDimensionSpacePoint   | {"language":"de"}  |
      | nodeVariantSelectionStrategy | "allSpecializations" |
    And the graph projection is fully up to date

    When I am in dimension space point {"language":"de"}
    And I expect node aggregate identifier "nodingers-cat" and node path "document/pet-document" to lead to node cs-identifier;nodingers-cat;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;the-great-nodini;{"language":"mul"}

    When I am in dimension space point {"language":"gsw"}
    And I expect node aggregate identifier "nodingers-cat" and node path "document/pet-document" to lead to node cs-identifier;nodingers-cat;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;the-great-nodini;{"language":"mul"}

    When I am in dimension space point {"language":"ltz"}
    And I expect node aggregate identifier "nodingers-cat" and node path "document/pet-document" to lead to node cs-identifier;nodingers-cat;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;the-great-nodini;{"language":"ltz"}

  Scenario: Create a new node with parent disabled with strategy allVariants
    Given the command DisableNodeAggregate is executed with payload:
      | Key                          | Value              |
      | nodeAggregateIdentifier      | "the-great-nodini" |
      | coveredDimensionSpacePoint   | {"language":"de"}  |
      | nodeVariantSelectionStrategy | "allVariants" |
    When the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateIdentifier | nodeTypeName                            | parentNodeAggregateIdentifier | nodeName     |
      | nodingers-cat           | Neos.ContentRepository.Testing:Document | the-great-nodini              | pet-document |

    Then I expect the node aggregate "nodingers-cat" to exist
    And I expect this node aggregate to disable dimension space points []

    When I am in dimension space point {"language":"mul"}
    And I expect node aggregate identifier "nodingers-cat" and node path "document/pet-document" to lead to no node

    When I am in dimension space point {"language":"de"}
    And I expect node aggregate identifier "nodingers-cat" and node path "document/pet-document" to lead to no node

    When I am in dimension space point {"language":"gsw"}
    And I expect node aggregate identifier "nodingers-cat" and node path "document/pet-document" to lead to no node

    When I am in dimension space point {"language":"ltz"}
    And I expect node aggregate identifier "nodingers-cat" and node path "document/pet-document" to lead to no node

    When I am in dimension space point {"language":"en"}
    And I expect node aggregate identifier "nodingers-cat" and node path "document/pet-document" to lead to no node

    And the command EnableNodeAggregate is executed with payload:
      | Key                          | Value              |
      | nodeAggregateIdentifier      | "the-great-nodini" |
      | coveredDimensionSpacePoint   | {"language":"de"}  |
      | nodeVariantSelectionStrategy | "allVariants" |
    And the graph projection is fully up to date

    When I am in dimension space point {"language":"mul"}
    And I expect node aggregate identifier "nodingers-cat" and node path "document/pet-document" to lead to node cs-identifier;nodingers-cat;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;the-great-nodini;{"language":"mul"}

    When I am in dimension space point {"language":"de"}
    And I expect node aggregate identifier "nodingers-cat" and node path "document/pet-document" to lead to node cs-identifier;nodingers-cat;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;the-great-nodini;{"language":"mul"}

    When I am in dimension space point {"language":"gsw"}
    And I expect node aggregate identifier "nodingers-cat" and node path "document/pet-document" to lead to node cs-identifier;nodingers-cat;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;the-great-nodini;{"language":"mul"}

    When I am in dimension space point {"language":"ltz"}
    And I expect node aggregate identifier "nodingers-cat" and node path "document/pet-document" to lead to node cs-identifier;nodingers-cat;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;the-great-nodini;{"language":"ltz"}

    When I am in dimension space point {"language":"en"}
    And I expect node aggregate identifier "nodingers-cat" and node path "document/pet-document" to lead to node cs-identifier;nodingers-cat;{"language":"mul"}
    And I expect this node to be a child of node cs-identifier;the-great-nodini;{"language":"mul"}
