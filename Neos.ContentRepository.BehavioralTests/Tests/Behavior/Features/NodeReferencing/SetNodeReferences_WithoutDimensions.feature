@fixtures
Feature: Node References without Dimensions

  As a user of the CR I want to be able to create, overwrite, reorder an delete reference between nodes

  Background:
    Given I have no content dimensions
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Testing:NodeWithReferences':
      properties:
        referenceProperty:
          type: reference
        referencesProperty:
          type: references
    """
    And the command CreateRootWorkspace is executed with payload:
      | Key                        | Value                                  |
      | workspaceName              | "live"                                 |
      | workspaceTitle             | "Live"                                 |
      | workspaceDescription       | "The live workspace"                   |
      | initiatingUserIdentifier   | "00000000-0000-0000-0000-000000000000" |
      | newContentStreamIdentifier | "cs-identifier"                        |
    And the graph projection is fully up to date
    And I am in content stream "cs-identifier" and dimension space point {}
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                     | Value                         |
      | nodeAggregateIdentifier | "lady-eleonode-rootford"      |
      | nodeTypeName            | "Neos.ContentRepository:Root" |
    And the graph projection is fully up to date
    And the following intermediary CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateIdentifier | parentNodeAggregateIdentifier | nodeTypeName                                      |
      | source-nodandaise       | lady-eleonode-rootford        | Neos.ContentRepository.Testing:NodeWithReferences |
      | anthony-destinode       | lady-eleonode-rootford        | Neos.ContentRepository.Testing:NodeWithReferences |
      | berta-destinode         | lady-eleonode-rootford        | Neos.ContentRepository.Testing:NodeWithReferences |
      | carl-destinode          | lady-eleonode-rootford        | Neos.ContentRepository.Testing:NodeWithReferences |

  Scenario: Ensure that a reference between nodes can be set and read
    When the command SetNodeReferences is executed with payload:
      | Key                                 | Value                 |
      | sourceNodeAggregateIdentifier       | "source-nodandaise"   |
      | destinationNodeAggregateIdentifiers | ["anthony-destinode"] |
      | referenceName                       | "referenceProperty"   |
      | initiatingUserIdentifier            | "user"                |
    And the graph projection is fully up to date

    Then I expect node aggregate identifier "source-nodandaise" to lead to node cs-identifier;source-nodandaise;{}
    And I expect this node to have the following references:
      | Key               | Value                                  |
      | referenceProperty | ["cs-identifier;anthony-destinode;{}"] |

    And I expect node aggregate identifier "anthony-destinode" to lead to node cs-identifier;anthony-destinode;{}
    And I expect this node to be referenced by:
      | Key               | Value                                  |
      | referenceProperty | ["cs-identifier;source-nodandaise;{}"] |

  Scenario: Ensure that references between nodes can be set and read
    When the command SetNodeReferences is executed with payload:
      | Key                                 | Value                                 |
      | sourceNodeAggregateIdentifier       | "source-nodandaise"                   |
      | destinationNodeAggregateIdentifiers | ["berta-destinode", "carl-destinode"] |
      | referenceName                       | "referencesProperty"                  |
      | initiatingUserIdentifier            | "user"                                |
    And the graph projection is fully up to date

    Then I expect node aggregate identifier "source-nodandaise" to lead to node cs-identifier;source-nodandaise;{}
    And I expect this node to have the following references:
      | Key                | Value                                                                   |
      | referencesProperty | ["cs-identifier;berta-destinode;{}", "cs-identifier;carl-destinode;{}"] |

    And I expect node aggregate identifier "berta-destinode" to lead to node cs-identifier;berta-destinode;{}
    And I expect this node to be referenced by:
      | Key                | Value                                  |
      | referencesProperty | ["cs-identifier;source-nodandaise;{}"] |

    And I expect node aggregate identifier "carl-destinode" to lead to node cs-identifier;carl-destinode;{}
    And I expect this node to be referenced by:
      | Key                | Value                                  |
      | referencesProperty | ["cs-identifier;source-nodandaise;{}"] |

  Scenario: Ensure that references between nodes can be set and overwritten
    When the command SetNodeReferences is executed with payload:
      | Key                                 | Value                                 |
      | sourceNodeAggregateIdentifier       | "source-nodandaise"                   |
      | destinationNodeAggregateIdentifiers | ["berta-destinode", "carl-destinode"] |
      | referenceName                       | "referencesProperty"                  |
      | initiatingUserIdentifier            | "user"                                |
    And the graph projection is fully up to date

    And the command SetNodeReferences is executed with payload:
      | Key                                 | Value                 |
      | sourceNodeAggregateIdentifier       | "source-nodandaise"   |
      | destinationNodeAggregateIdentifiers | ["anthony-destinode"] |
      | referenceName                       | "referencesProperty"  |
      | initiatingUserIdentifier            | "user"                |
    And the graph projection is fully up to date

    Then I expect node aggregate identifier "source-nodandaise" to lead to node cs-identifier;source-nodandaise;{}
    And I expect this node to have the following references:
      | Key                | Value                                  |
      | referencesProperty | ["cs-identifier;anthony-destinode;{}"] |

    And I expect node aggregate identifier "anthony-destinode" to lead to node cs-identifier;anthony-destinode;{}
    And I expect this node to be referenced by:
      | Key                | Value                                  |
      | referencesProperty | ["cs-identifier;source-nodandaise;{}"] |

    And I expect node aggregate identifier "berta-destinode" to lead to node cs-identifier;berta-destinode;{}
    And I expect this node to not be referenced

    And I expect node aggregate identifier "carl-destinode" to lead to node cs-identifier;carl-destinode;{}
    And I expect this node to not be referenced

  Scenario: Ensure that references between nodes can be set and reordered

    When the command SetNodeReferences is executed with payload:
      | Key                                 | Value                                 |
      | sourceNodeAggregateIdentifier       | "source-nodandaise"                   |
      | destinationNodeAggregateIdentifiers | ["berta-destinode", "carl-destinode"] |
      | referenceName                       | "referencesProperty"                  |
      | initiatingUserIdentifier            | "user"                                |
    And the graph projection is fully up to date

    And the command SetNodeReferences is executed with payload:
      | Key                                 | Value                                 |
      | sourceNodeAggregateIdentifier       | "source-nodandaise"                   |
      | destinationNodeAggregateIdentifiers | ["carl-destinode", "berta-destinode"] |
      | referenceName                       | "referencesProperty"                  |
      | initiatingUserIdentifier            | "user"                                |
    And the graph projection is fully up to date

    Then I expect node aggregate identifier "source-nodandaise" to lead to node cs-identifier;source-nodandaise;{}
    And I expect this node to have the following references:
      | Key                | Value                                                                   |
      | referencesProperty | ["cs-identifier;carl-destinode;{}", "cs-identifier;berta-destinode;{}"] |

  Scenario: Ensure that references between nodes can be deleted

    When the command SetNodeReferences is executed with payload:
      | Key                                 | Value                                 |
      | sourceNodeAggregateIdentifier       | "source-nodandaise"                   |
      | destinationNodeAggregateIdentifiers | ["berta-destinode", "carl-destinode"] |
      | referenceName                       | "referencesProperty"                  |
      | initiatingUserIdentifier            | "user"                                |
    And the graph projection is fully up to date

    And the command SetNodeReferences is executed with payload:
      | Key                                 | Value                |
      | sourceNodeAggregateIdentifier       | "source-nodandaise"  |
      | destinationNodeAggregateIdentifiers | []                   |
      | referenceName                       | "referencesProperty" |
      | initiatingUserIdentifier            | "user"               |

    And the graph projection is fully up to date

    Then I expect node aggregate identifier "source-nodandaise" to lead to node cs-identifier;source-nodandaise;{}
    And I expect this node to have no references

    And I expect node aggregate identifier "berta-destinode" to lead to node cs-identifier;berta-destinode;{}
    And I expect this node to not be referenced

    And I expect node aggregate identifier "carl-destinode" to lead to node cs-identifier;carl-destinode;{}
    And I expect this node to not be referenced

  Scenario: Ensure that references from multiple nodes read from the opposing side

    When the command SetNodeReferences is executed with payload:
      | Key                                 | Value                 |
      | sourceNodeAggregateIdentifier       | "source-nodandaise"   |
      | destinationNodeAggregateIdentifiers | ["anthony-destinode"] |
      | referenceName                       | "referenceProperty"   |
      | initiatingUserIdentifier            | "user"                |
    And the graph projection is fully up to date

    And the command SetNodeReferences is executed with payload:
      | Key                                 | Value                 |
      | sourceNodeAggregateIdentifier       | "berta-destinode"     |
      | destinationNodeAggregateIdentifiers | ["anthony-destinode"] |
      | referenceName                       | "referenceProperty"   |
      | initiatingUserIdentifier            | "user"                |
    And the graph projection is fully up to date

    Then I expect node aggregate identifier "anthony-destinode" to lead to node cs-identifier;anthony-destinode;{}
    And I expect this node to be referenced by:
      | Key               | Value                                                                      |
      | referenceProperty | ["cs-identifier;source-nodandaise;{}", "cs-identifier;berta-destinode;{}"] |

