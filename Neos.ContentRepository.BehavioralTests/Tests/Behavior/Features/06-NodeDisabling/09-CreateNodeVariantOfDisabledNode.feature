@contentrepository @adapters=DoctrineDBAL,Postgres
Feature: Variation of hidden nodes

  If we create variants of nodes hidden in the respective dimension space point(s),
  the variants must be hidden as well

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
      | Key                        | Value                |
      | workspaceName              | "live"               |
      | workspaceTitle             | "Live"               |
      | workspaceDescription       | "The live workspace" |
      | newContentStreamId | "cs-identifier"      |
    And I am in the active content stream of workspace "live" and dimension space point {"language":"mul"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                     | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName            | "Neos.ContentRepository:Root" |
    And VisibilityConstraints are set to "frontend"

  Scenario: Specialize a node where the specialization target is enabled
    Given I am in dimension space point {"language":"de"}
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId | nodeTypeName                            | parentNodeAggregateId | nodeName       |
      | the-great-nodini        | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford        | court-magician |
    And the command DisableNodeAggregate is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId      | "the-great-nodini"   |
      | coveredDimensionSpacePoint   | {"language":"de"}    |
      | nodeVariantSelectionStrategy | "allSpecializations" |
    And the command EnableNodeAggregate is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId      | "the-great-nodini"   |
      | coveredDimensionSpacePoint   | {"language":"gsw"}   |
      | nodeVariantSelectionStrategy | "allSpecializations" |

    When the command CreateNodeVariant is executed with payload:
      | Key                     | Value              |
      | nodeAggregateId | "the-great-nodini" |
      | sourceOrigin            | {"language":"de"}  |
      | targetOrigin            | {"language":"gsw"} |
    And I am in dimension space point {"language":"de"}
    And I expect node aggregate identifier "the-great-nodini" and node path "court-magician" to lead to no node

    When I am in dimension space point {"language":"gsw"}
    And I expect node aggregate identifier "the-great-nodini" and node path "court-magician" to lead to node cs-identifier;the-great-nodini;{"language":"gsw"}

  Scenario: Specialize a node where the specialization target is disabled
    Given I am in dimension space point {"language":"de"}
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId | nodeTypeName                            | parentNodeAggregateId | nodeName       |
      | the-great-nodini        | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford        | court-magician |
    And the command DisableNodeAggregate is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId      | "the-great-nodini"   |
      | coveredDimensionSpacePoint   | {"language":"de"}    |
      | nodeVariantSelectionStrategy | "allSpecializations" |

    When the command CreateNodeVariant is executed with payload:
      | Key                     | Value              |
      | nodeAggregateId | "the-great-nodini" |
      | sourceOrigin            | {"language":"de"}  |
      | targetOrigin            | {"language":"gsw"} |

    When I am in dimension space point {"language":"de"}
    Then I expect node aggregate identifier "the-great-nodini" and node path "court-magician" to lead to no node

    When I am in dimension space point {"language":"gsw"}
    Then I expect node aggregate identifier "the-great-nodini" and node path "court-magician" to lead to no node

    When the command EnableNodeAggregate is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId      | "the-great-nodini"   |
      | coveredDimensionSpacePoint   | {"language":"gsw"}   |
      | nodeVariantSelectionStrategy | "allSpecializations" |

    When I am in dimension space point {"language":"gsw"}
    Then I expect node aggregate identifier "the-great-nodini" and node path "court-magician" to lead to node cs-identifier;the-great-nodini;{"language":"gsw"}

  Scenario: Generalize a node where the generalization target is enabled
    Given I am in dimension space point {"language":"de"}
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId | nodeTypeName                            | parentNodeAggregateId | nodeName       |
      | the-great-nodini        | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford        | court-magician |
    And the command DisableNodeAggregate is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId      | "the-great-nodini"   |
      | coveredDimensionSpacePoint   | {"language":"de"}    |
      | nodeVariantSelectionStrategy | "allSpecializations" |

    When the command CreateNodeVariant is executed with payload:
      | Key                     | Value              |
      | nodeAggregateId | "the-great-nodini" |
      | sourceOrigin            | {"language":"de"}  |
      | targetOrigin            | {"language":"mul"} |

    When I am in dimension space point {"language":"de"}
    Then I expect node aggregate identifier "the-great-nodini" and node path "court-magician" to lead to no node

    When I am in dimension space point {"language":"mul"}
    Then I expect node aggregate identifier "the-great-nodini" and node path "court-magician" to lead to no node
  #cs-identifier;the-great-nodini;{"language":"mul"}

  Scenario: Generalize a node where the generalization target is disabled
    Given I am in dimension space point {"language":"ltz"}
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId | nodeTypeName                            | parentNodeAggregateId | nodeName       |
      | the-great-nodini        | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford        | court-magician |

    And the command CreateNodeVariant is executed with payload:
      | Key                     | Value              |
      | nodeAggregateId | "the-great-nodini" |
      | sourceOrigin            | {"language":"ltz"} |
      | targetOrigin            | {"language":"mul"} |
    And the command DisableNodeAggregate is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId      | "the-great-nodini"   |
      | coveredDimensionSpacePoint   | {"language":"mul"}   |
      | nodeVariantSelectionStrategy | "allSpecializations" |

    When the command CreateNodeVariant is executed with payload:
      | Key                     | Value              |
      | nodeAggregateId | "the-great-nodini"    |
      | sourceOrigin            | {"language":"ltz"} |
      | targetOrigin            | {"language":"de"}  |

    When I am in dimension space point {"language":"ltz"}
    Then I expect node aggregate identifier "the-great-nodini" and node path "court-magician" to lead to no node

    When I am in dimension space point {"language":"de"}
    Then I expect node aggregate identifier "the-great-nodini" and node path "court-magician" to lead to no node

    When the command EnableNodeAggregate is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId      | "the-great-nodini"      |
      | coveredDimensionSpacePoint   | {"language":"de"}    |
      | nodeVariantSelectionStrategy | "allSpecializations" |

    When I am in dimension space point {"language":"de"}
    Then I expect node aggregate identifier "the-great-nodini" and node path "court-magician" to lead to node cs-identifier;the-great-nodini;{"language":"de"}

  Scenario: Peer vary a node where the peer target is enabled
    Given I am in dimension space point {"language":"de"}
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId | nodeTypeName                            | parentNodeAggregateId | nodeName       |
      | the-great-nodini        | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford        | court-magician |
    And the command DisableNodeAggregate is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId      | "the-great-nodini"   |
      | coveredDimensionSpacePoint   | {"language":"de"}    |
      | nodeVariantSelectionStrategy | "allSpecializations" |

    When the command CreateNodeVariant is executed with payload:
      | Key                     | Value              |
      | nodeAggregateId | "the-great-nodini" |
      | sourceOrigin            | {"language":"de"}  |
      | targetOrigin            | {"language":"en"}  |

    When I am in dimension space point {"language":"de"}
    Then I expect node aggregate identifier "the-great-nodini" and node path "court-magician" to lead to no node

    When I am in dimension space point {"language":"en"}
    Then I expect node aggregate identifier "the-great-nodini" and node path "court-magician" to lead to node cs-identifier;the-great-nodini;{"language":"en"}

  Scenario: Peer vary a node where the peer target is disabled
    Given I am in dimension space point {"language":"de"}
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId | nodeTypeName                            | parentNodeAggregateId | nodeName       |
      | the-great-nodini        | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford        | court-magician |
    And the command CreateNodeVariant is executed with payload:
      | Key                     | Value              |
      | nodeAggregateId | "the-great-nodini" |
      | sourceOrigin            | {"language":"de"}  |
      | targetOrigin            | {"language":"mul"} |
    And the command DisableNodeAggregate is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId      | "the-great-nodini"   |
      | coveredDimensionSpacePoint   | {"language":"mul"}   |
      | nodeVariantSelectionStrategy | "allSpecializations" |

    When the command CreateNodeVariant is executed with payload:
      | Key                     | Value              |
      | nodeAggregateId | "the-great-nodini" |
      | sourceOrigin            | {"language":"de"}  |
      | targetOrigin            | {"language":"en"}  |

    When I am in dimension space point {"language":"de"}
    Then I expect node aggregate identifier "the-great-nodini" and node path "court-magician" to lead to no node

    When I am in dimension space point {"language":"en"}
    Then I expect node aggregate identifier "the-great-nodini" and node path "court-magician" to lead to no node

    When the command EnableNodeAggregate is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId      | "the-great-nodini"   |
      | coveredDimensionSpacePoint   | {"language":"en"}    |
      | nodeVariantSelectionStrategy | "allSpecializations" |

    When I am in dimension space point {"language":"en"}
    Then I expect node aggregate identifier "the-great-nodini" and node path "court-magician" to lead to node cs-identifier;the-great-nodini;{"language":"en"}
