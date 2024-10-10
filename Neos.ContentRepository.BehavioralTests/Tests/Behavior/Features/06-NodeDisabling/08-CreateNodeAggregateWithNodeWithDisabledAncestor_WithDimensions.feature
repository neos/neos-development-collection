@contentrepository @adapters=DoctrineDBAL,Postgres
Feature: Creation of nodes underneath disabled nodes

  If we create new nodes underneath of disabled nodes, they must be marked as disabled as well;
  i.e. they must have the proper restriction edges as well.

  These are the test cases with dimensions

  Background:
    Given using the following content dimensions:
      | Identifier | Values                | Generalizations                     |
      | language   | mul, de, en, gsw, ltz | ltz->de->mul, gsw->de->mul, en->mul |
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Document': {}
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootWorkspace is executed with payload:
      | Key                  | Value                |
      | workspaceName        | "live"               |
      | newContentStreamId   | "cs-identifier"      |
    And I am in workspace "live" and dimension space point {"language":"mul"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId  | nodeTypeName                            | parentNodeAggregateId  | nodeName |
      | the-great-nodini | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford | document |
    # We need both a real and a virtual specialization to test the different selection strategies
    And the command CreateNodeVariant is executed with payload:
      | Key             | Value              |
      | nodeAggregateId | "the-great-nodini" |
      | sourceOrigin    | {"language":"mul"} |
      | targetOrigin    | {"language":"ltz"} |
    And VisibilityConstraints are set to "frontend"

  Scenario: Create a new node with parent disabled with strategy allSpecializations
    Given the command DisableNodeAggregate is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "the-great-nodini"   |
      | coveredDimensionSpacePoint   | {"language":"de"}    |
      | nodeVariantSelectionStrategy | "allSpecializations" |
    When the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId | nodeTypeName                            | parentNodeAggregateId | nodeName     |
      | nodingers-cat   | Neos.ContentRepository.Testing:Document | the-great-nodini      | pet-document |

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
      | Key                          | Value                |
      | nodeAggregateId              | "the-great-nodini"   |
      | coveredDimensionSpacePoint   | {"language":"de"}    |
      | nodeVariantSelectionStrategy | "allSpecializations" |

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
      | nodeAggregateId              | "the-great-nodini" |
      | coveredDimensionSpacePoint   | {"language":"de"}  |
      | nodeVariantSelectionStrategy | "allVariants"      |
    When the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId | nodeTypeName                            | parentNodeAggregateId | nodeName     |
      | nodingers-cat   | Neos.ContentRepository.Testing:Document | the-great-nodini      | pet-document |

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
      | nodeAggregateId              | "the-great-nodini" |
      | coveredDimensionSpacePoint   | {"language":"de"}  |
      | nodeVariantSelectionStrategy | "allVariants"      |

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
