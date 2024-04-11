@contentrepository @adapters=DoctrineDBAL,Postgres
Feature: Node References without Dimensions

  As a user of the CR I want to be able to create, overwrite, reorder and delete reference between nodes

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:ReferencedNode': []

    'Neos.ContentRepository.Testing:NodeWithReferences':
      # legacy notation
      properties:
        referenceProperty:
          type: reference
        referencesProperty:
          type: references
      references:
        restrictedReferenceProperty:
          constraints:
            nodeTypes:
              '*': false
              'Neos.ContentRepository.Testing:ReferencedNode': true
        referencePropertyWithProperty:
          constraints:
            maxItems: 1
          properties:
            text:
              type: string
            dayOfWeek:
              type: Neos\ContentRepository\Core\Tests\Behavior\Fixtures\DayOfWeek
            postalAddress:
              type: 'Neos\ContentRepository\Core\Tests\Behavior\Fixtures\PostalAddress'
        referencesPropertyWithProperty:
          properties:
            text:
              type: string
            dayOfWeek:
              type: Neos\ContentRepository\Core\Tests\Behavior\Fixtures\DayOfWeek
            postalAddress:
              type: 'Neos\ContentRepository\Core\Tests\Behavior\Fixtures\PostalAddress'
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
    And I am in the active content stream of workspace "live" and dimension space point {}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId   | parentNodeAggregateId  | nodeTypeName                                      |
      | source-nodandaise | lady-eleonode-rootford | Neos.ContentRepository.Testing:NodeWithReferences |
      | node-wan-kenodi   | lady-eleonode-rootford | Neos.ContentRepository.Testing:NodeWithReferences |
      | anthony-destinode | lady-eleonode-rootford | Neos.ContentRepository.Testing:ReferencedNode     |
      | berta-destinode   | lady-eleonode-rootford | Neos.ContentRepository.Testing:ReferencedNode     |
      | carl-destinode    | lady-eleonode-rootford | Neos.ContentRepository.Testing:ReferencedNode     |

  Scenario: Ensure that a single reference between nodes can be set and read
    When the command SetNodeReferences is executed with payload:
      | Key                   | Value                             |
      | sourceNodeAggregateId | "source-nodandaise"               |
      | referenceName         | "referenceProperty"               |
      | references            | [{"target": "anthony-destinode"}] |

    Then I expect node aggregate identifier "source-nodandaise" to lead to node cs-identifier;source-nodandaise;{}
    And I expect this node to have the following references:
      | Name              | Node                               | Properties |
      | referenceProperty | cs-identifier;anthony-destinode;{} | null       |

    And I expect node aggregate identifier "anthony-destinode" to lead to node cs-identifier;anthony-destinode;{}
    And I expect this node to be referenced by:
      | Name              | Node                               | Properties |
      | referenceProperty | cs-identifier;source-nodandaise;{} | null       |

  Scenario: Ensure that a single reference with properties between nodes can be set and read
    When the command SetNodeReferences is executed with payload:
      | Key                   | Value                                                                                                     |
      | sourceNodeAggregateId | "source-nodandaise"                                                                                       |
      | referenceName         | "referencePropertyWithProperty"                                                                           |
      | references            | [{"target": "anthony-destinode", "properties":{"text":"my text", "dayOfWeek":"DayOfWeek:https://schema.org/Friday", "postalAddress":"PostalAddress:dummy"}}] |

    Then I expect node aggregate identifier "source-nodandaise" to lead to node cs-identifier;source-nodandaise;{}
    And I expect this node to have the following references:
      | Name                          | Node                               | Properties                                                |
      | referencePropertyWithProperty | cs-identifier;anthony-destinode;{} | {"text":"my text", "dayOfWeek":"DayOfWeek:https://schema.org/Friday", "postalAddress":"PostalAddress:dummy"} |

    And I expect node aggregate identifier "anthony-destinode" to lead to node cs-identifier;anthony-destinode;{}
    And I expect this node to be referenced by:
      | Name                          | Node                               | Properties                                                |
      | referencePropertyWithProperty | cs-identifier;source-nodandaise;{} | {"text":"my text", "dayOfWeek":"DayOfWeek:https://schema.org/Friday", "postalAddress":"PostalAddress:dummy"} |

  Scenario: Ensure that multiple references between nodes can be set and read
    When the command SetNodeReferences is executed with payload:
      | Key                   | Value                                                         |
      | sourceNodeAggregateId | "source-nodandaise"                                           |
      | referenceName         | "referencesProperty"                                          |
      | references            | [{"target": "berta-destinode"}, {"target": "carl-destinode"}] |

    Then I expect node aggregate identifier "source-nodandaise" to lead to node cs-identifier;source-nodandaise;{}
    And I expect this node to have the following references:
      | Name               | Node                             | Properties |
      | referencesProperty | cs-identifier;berta-destinode;{} | null       |
      | referencesProperty | cs-identifier;carl-destinode;{}  | null       |

    And I expect node aggregate identifier "berta-destinode" to lead to node cs-identifier;berta-destinode;{}
    And I expect this node to be referenced by:
      | Name               | Node                               | Properties |
      | referencesProperty | cs-identifier;source-nodandaise;{} | null       |

    And I expect node aggregate identifier "carl-destinode" to lead to node cs-identifier;carl-destinode;{}
    And I expect this node to be referenced by:
      | Name               | Node                               | Properties |
      | referencesProperty | cs-identifier;source-nodandaise;{} | null       |

  Scenario: Ensure that multiple references with properties between nodes can be set and read
    When the command SetNodeReferences is executed with payload:
      | Key                   | Value                                                                                                                                                                                                                    |
      | sourceNodeAggregateId | "source-nodandaise"                                                                                                                                                                                                      |
      | referenceName         | "referencesPropertyWithProperty"                                                                                                                                                                                         |
      | references            | [{"target":"berta-destinode", "properties":{"text":"my text", "dayOfWeek":"DayOfWeek:https://schema.org/Wednesday", "postalAddress":"PostalAddress:dummy"}}, {"target":"carl-destinode", "properties":{"text":"my other text", "dayOfWeek":"DayOfWeek:https://schema.org/Friday", "postalAddress":"PostalAddress:anotherDummy"}}] |

    Then I expect node aggregate identifier "source-nodandaise" to lead to node cs-identifier;source-nodandaise;{}
    And I expect this node to have the following references:
      | Name                           | Node                             | Properties                                                             |
      | referencesPropertyWithProperty | cs-identifier;berta-destinode;{} | {"text":"my text", "dayOfWeek":"DayOfWeek:https://schema.org/Wednesday", "postalAddress":"PostalAddress:dummy"}              |
      | referencesPropertyWithProperty | cs-identifier;carl-destinode;{}  | {"text":"my other text", "dayOfWeek":"DayOfWeek:https://schema.org/Friday", "postalAddress":"PostalAddress:anotherDummy"} |

    And I expect node aggregate identifier "berta-destinode" to lead to node cs-identifier;berta-destinode;{}
    And I expect this node to be referenced by:
      | Name                           | Node                               | Properties                                                |
      | referencesPropertyWithProperty | cs-identifier;source-nodandaise;{} | {"text":"my text", "dayOfWeek":"DayOfWeek:https://schema.org/Wednesday", "postalAddress":"PostalAddress:dummy"} |

    And I expect node aggregate identifier "carl-destinode" to lead to node cs-identifier;carl-destinode;{}
    And I expect this node to be referenced by:
      | Name                           | Node                               | Properties                                                             |
      | referencesPropertyWithProperty | cs-identifier;source-nodandaise;{} | {"text":"my other text", "dayOfWeek":"DayOfWeek:https://schema.org/Friday", "postalAddress":"PostalAddress:anotherDummy"} |

  Scenario: Ensure that references between nodes can be set and overwritten
    When the command SetNodeReferences is executed with payload:
      | Key                   | Value                                                         |
      | sourceNodeAggregateId | "source-nodandaise"                                           |
      | references            | [{"target": "berta-destinode"}, {"target": "carl-destinode"}] |
      | referenceName         | "referencesProperty"                                          |

    And the command SetNodeReferences is executed with payload:
      | Key                   | Value                             |
      | sourceNodeAggregateId | "source-nodandaise"               |
      | references            | [{"target": "anthony-destinode"}] |
      | referenceName         | "referencesProperty"              |

    Then I expect node aggregate identifier "source-nodandaise" to lead to node cs-identifier;source-nodandaise;{}
    And I expect this node to have the following references:
      | Name               | Node                               | Properties |
      | referencesProperty | cs-identifier;anthony-destinode;{} | null       |

    And I expect node aggregate identifier "anthony-destinode" to lead to node cs-identifier;anthony-destinode;{}
    And I expect this node to be referenced by:
      | Name               | Node                               | Properties |
      | referencesProperty | cs-identifier;source-nodandaise;{} | null       |

    And I expect node aggregate identifier "berta-destinode" to lead to node cs-identifier;berta-destinode;{}
    And I expect this node to not be referenced

    And I expect node aggregate identifier "carl-destinode" to lead to node cs-identifier;carl-destinode;{}
    And I expect this node to not be referenced

  Scenario: Ensure that references between nodes can be set and reordered

    When the command SetNodeReferences is executed with payload:
      | Key                   | Value                                                         |
      | sourceNodeAggregateId | "source-nodandaise"                                           |
      | references            | [{"target": "berta-destinode"}, {"target": "carl-destinode"}] |
      | referenceName         | "referencesProperty"                                          |

    And the command SetNodeReferences is executed with payload:
      | Key                   | Value                                                         |
      | sourceNodeAggregateId | "source-nodandaise"                                           |
      | references            | [{"target": "carl-destinode"}, {"target": "berta-destinode"}] |
      | referenceName         | "referencesProperty"                                          |

    Then I expect node aggregate identifier "source-nodandaise" to lead to node cs-identifier;source-nodandaise;{}
    And I expect this node to have the following references:
      | Name               | Node                             | Properties |
      | referencesProperty | cs-identifier;carl-destinode;{}  | null       |
      | referencesProperty | cs-identifier;berta-destinode;{} | null       |

  Scenario: Ensure that references between nodes can be deleted

    When the command SetNodeReferences is executed with payload:
      | Key                   | Value                                                         |
      | sourceNodeAggregateId | "source-nodandaise"                                           |
      | references            | [{"target": "berta-destinode"}, {"target": "carl-destinode"}] |
      | referenceName         | "referencesProperty"                                          |

    And the command SetNodeReferences is executed with payload:
      | Key                   | Value                |
      | sourceNodeAggregateId | "source-nodandaise"  |
      | references            | []                   |
      | referenceName         | "referencesProperty" |

    Then I expect node aggregate identifier "source-nodandaise" to lead to node cs-identifier;source-nodandaise;{}
    And I expect this node to have no references

    And I expect node aggregate identifier "berta-destinode" to lead to node cs-identifier;berta-destinode;{}
    And I expect this node to not be referenced

    And I expect node aggregate identifier "carl-destinode" to lead to node cs-identifier;carl-destinode;{}
    And I expect this node to not be referenced

  Scenario: Ensure that references from multiple nodes read from the opposing side

    When the command SetNodeReferences is executed with payload:
      | Key                   | Value                             |
      | sourceNodeAggregateId | "source-nodandaise"               |
      | references            | [{"target": "anthony-destinode"}] |
      | referenceName         | "referenceProperty"               |

    And the command SetNodeReferences is executed with payload:
      | Key                   | Value                             |
      | sourceNodeAggregateId | "node-wan-kenodi"                 |
      | references            | [{"target": "anthony-destinode"}] |
      | referenceName         | "referenceProperty"               |

    Then I expect node aggregate identifier "anthony-destinode" to lead to node cs-identifier;anthony-destinode;{}
    And I expect this node to be referenced by:
      | Name              | Node                               | Properties |
      | referenceProperty | cs-identifier;node-wan-kenodi;{}   | null       |
      | referenceProperty | cs-identifier;source-nodandaise;{} | null       |

  Scenario: Ensure that a reference between nodes can be set and read when matching constraints
    When the command SetNodeReferences is executed with payload:
      | Key                   | Value                             |
      | sourceNodeAggregateId | "source-nodandaise"               |
      | references            | [{"target": "anthony-destinode"}] |
      | referenceName         | "restrictedReferenceProperty"     |

    Then I expect node aggregate identifier "source-nodandaise" to lead to node cs-identifier;source-nodandaise;{}
    And I expect this node to have the following references:
      | Name                        | Node                               | Properties |
      | restrictedReferenceProperty | cs-identifier;anthony-destinode;{} | null       |

    And I expect node aggregate identifier "anthony-destinode" to lead to node cs-identifier;anthony-destinode;{}
    And I expect this node to be referenced by:
      | Name                        | Node                               | Properties |
      | restrictedReferenceProperty | cs-identifier;source-nodandaise;{} | null       |
