Feature: Subtree tags are only inserted to the current write layer if differing from the parent read layers
  This reduces the minimum set of hierarchies per layer and prevents obsolete copies.

  Background:
    Given using the following content dimensions:
      | Identifier | Values                      | Generalizations                      |
      | example    | general, source, peer, spec | spec->source->general, peer->general |
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Document': []
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And I am in workspace "live" and dimension space point {"example": "general"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId            | nodeTypeName                            | parentNodeAggregateId      |
      | lady-abigail-nodenborough  | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford     |
      | sir-nodeward-nodington-iii | Neos.ContentRepository.Testing:Document | lady-abigail-nodenborough  |
      | sir-david-nodenborough     | Neos.ContentRepository.Testing:Document | sir-nodeward-nodington-iii |
      | nody-mc-nodeface           | Neos.ContentRepository.Testing:Document | sir-david-nodenborough     |

  Scenario: Move a node in user without any tags
  If not implemented carefully move node could create hierarchies for all descendents and tag them with nothing.

    And I am in workspace "live"
    Then I expect 20 hierarchies to exist in the active write layer

    When the command CreateWorkspace is executed with payload:
      | Key                | Value        |
      | workspaceName      | "user"       |
      | baseWorkspaceName  | "live"       |
      | newContentStreamId | "user-cs-id" |

    Then the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                    |
      | workspaceName                | "user"                   |
      | dimensionSpacePoint          | {"example": "source"}    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | relationDistributionStrategy | "gatherAll"              |
      | newParentNodeAggregateId     | "lady-eleonode-rootford" |

    And I am in workspace "user"
    # All 4 covered DSPs of the single node
    Then I expect 4 hierarchies to exist in the active write layer

  Scenario: Move a node within a tagged subtree
  If not implemented carefully move node could create hierarchies for all descendents and tag them with the current tag
    When the command TagSubtree is executed with payload:
      | Key                          | Value                       |
      | workspaceName                | "live"                      |
      | nodeAggregateId              | "lady-abigail-nodenborough" |
      | nodeVariantSelectionStrategy | "allSpecializations"        |
      | tag                          | "tag1"                      |

    And I am in workspace "live"
    Then I expect 20 hierarchies to exist in the active write layer

    When the command CreateWorkspace is executed with payload:
      | Key                | Value        |
      | workspaceName      | "user"       |
      | baseWorkspaceName  | "live"       |
      | newContentStreamId | "user-cs-id" |

    Then the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                       |
      | workspaceName                | "user"                      |
      | dimensionSpacePoint          | {"example": "source"}       |
      | nodeAggregateId              | "sir-david-nodenborough"    |
      | relationDistributionStrategy | "gatherAll"                 |
      | newParentNodeAggregateId     | "lady-abigail-nodenborough" |

    And I am in workspace "user"
    # All 4 covered DSPs of the single node
    Then I expect 4 hierarchies to exist in the active write layer

  Scenario: Tag a node in user where its child node and descendants are already tagged via live
    When the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | workspaceName                | "live"                   |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | nodeVariantSelectionStrategy | "allSpecializations"     |
      | tag                          | "tag1"                   |

    And I am in workspace "live"
    Then I expect 20 hierarchies to exist in the active write layer

    When the command CreateWorkspace is executed with payload:
      | Key                | Value        |
      | workspaceName      | "user"       |
      | baseWorkspaceName  | "live"       |
      | newContentStreamId | "user-cs-id" |
    When the command TagSubtree is executed with payload:
      | Key                          | Value                        |
      | workspaceName                | "user"                       |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | nodeVariantSelectionStrategy | "allSpecializations"         |
      | tag                          | "tag1"                       |

    And I am in workspace "user"
    # All 4 covered DSPs of the single node
    Then I expect 4 hierarchies to exist in the active write layer

  Scenario: Untag a node in user where its child node and descendants are still tagged via live
    When the command TagSubtree is executed with payload:
      | Key                          | Value                        |
      | workspaceName                | "live"                       |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | nodeVariantSelectionStrategy | "allSpecializations"         |
      | tag                          | "tag1"                       |

    When the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | workspaceName                | "live"                   |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | nodeVariantSelectionStrategy | "allSpecializations"     |
      | tag                          | "tag1"                   |

    And I am in workspace "live"
    Then I expect 20 hierarchies to exist in the active write layer

    When the command CreateWorkspace is executed with payload:
      | Key                | Value        |
      | workspaceName      | "user"       |
      | baseWorkspaceName  | "live"       |
      | newContentStreamId | "user-cs-id" |
    When the command UntagSubtree is executed with payload:
      | Key                          | Value                        |
      | workspaceName                | "user"                       |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | nodeVariantSelectionStrategy | "allSpecializations"         |
      | tag                          | "tag1"                       |

    And I am in workspace "user"
    # All 4 covered DSPs of the single node
    Then I expect 4 hierarchies to exist in the active write layer
