@contentrepository @adapters=DoctrineDBAL,Postgres
Feature: Node References without Dimensions

  As a user of the CR I want to be able to create, overwrite, reorder and delete reference between nodes

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:NodeWithReferences':
      references:
        reference: {}
        references: {}
        restrictedReference:
          constraints:
            nodeTypes:
              '*': false
              'Neos.ContentRepository.Testing:NodeWithReferences': true
        referenceWithProperty:
          properties:
            text:
              type: string
            dayOfWeek:
              type: Neos\ContentRepository\Core\Tests\Behavior\Fixtures\DayOfWeek
            postalAddress:
              type: 'Neos\ContentRepository\Core\Tests\Behavior\Fixtures\PostalAddress'
        referencesWithProperty:
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
    And the graph projection is fully up to date
    And I am in content stream "cs-identifier" and dimension space point {}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the graph projection is fully up to date
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId   | parentNodeAggregateId  | nodeTypeName                                      |
      | source-nodandaise | lady-eleonode-rootford | Neos.ContentRepository.Testing:NodeWithReferences |
      | anthony-destinode | lady-eleonode-rootford | Neos.ContentRepository.Testing:NodeWithReferences |
      | berta-destinode   | lady-eleonode-rootford | Neos.ContentRepository.Testing:NodeWithReferences |
      | carl-destinode    | lady-eleonode-rootford | Neos.ContentRepository.Testing:NodeWithReferences |

  Scenario: Ensure that a single reference between nodes can be set and read
    When the command SetNodeReferences is executed with payload:
      | Key                   | Value                             |
      | sourceNodeAggregateId | "source-nodandaise"               |
      | referenceName         | "reference"               |
      | references            | [{"target": "anthony-destinode"}] |
    And the graph projection is fully up to date

    Then I expect node aggregate identifier "source-nodandaise" to lead to node cs-identifier;source-nodandaise;{}
    And I expect this node to have the following references:
      | Name              | Node                               | Properties |
      | reference | cs-identifier;anthony-destinode;{} | null       |

    And I expect node aggregate identifier "anthony-destinode" to lead to node cs-identifier;anthony-destinode;{}
    And I expect this node to be referenced by:
      | Name              | Node                               | Properties |
      | reference | cs-identifier;source-nodandaise;{} | null       |

  Scenario: Ensure that a single reference with properties between nodes can be set and read
    When the command SetNodeReferences is executed with payload:
      | Key                   | Value                                                                                                     |
      | sourceNodeAggregateId | "source-nodandaise"                                                                                       |
      | referenceName         | "referenceWithProperty"                                                                           |
      | references            | [{"target": "anthony-destinode", "properties":{"text":"my text", "dayOfWeek":"DayOfWeek:https://schema.org/Friday", "postalAddress":"PostalAddress:dummy"}}] |
    And the graph projection is fully up to date

    Then I expect node aggregate identifier "source-nodandaise" to lead to node cs-identifier;source-nodandaise;{}
    And I expect this node to have the following references:
      | Name                          | Node                               | Properties                                                |
      | referenceWithProperty | cs-identifier;anthony-destinode;{} | {"text":"my text", "dayOfWeek":"DayOfWeek:https://schema.org/Friday", "postalAddress":"PostalAddress:dummy"} |

    And I expect node aggregate identifier "anthony-destinode" to lead to node cs-identifier;anthony-destinode;{}
    And I expect this node to be referenced by:
      | Name                          | Node                               | Properties                                                |
      | referenceWithProperty | cs-identifier;source-nodandaise;{} | {"text":"my text", "dayOfWeek":"DayOfWeek:https://schema.org/Friday", "postalAddress":"PostalAddress:dummy"} |

  Scenario: Ensure that multiple references between nodes can be set and read
    When the command SetNodeReferences is executed with payload:
      | Key                   | Value                                                         |
      | sourceNodeAggregateId | "source-nodandaise"                                           |
      | referenceName         | "references"                                          |
      | references            | [{"target": "berta-destinode"}, {"target": "carl-destinode"}] |
    And the graph projection is fully up to date

    Then I expect node aggregate identifier "source-nodandaise" to lead to node cs-identifier;source-nodandaise;{}
    And I expect this node to have the following references:
      | Name               | Node                             | Properties |
      | references | cs-identifier;berta-destinode;{} | null       |
      | references | cs-identifier;carl-destinode;{}  | null       |

    And I expect node aggregate identifier "berta-destinode" to lead to node cs-identifier;berta-destinode;{}
    And I expect this node to be referenced by:
      | Name               | Node                               | Properties |
      | references | cs-identifier;source-nodandaise;{} | null       |

    And I expect node aggregate identifier "carl-destinode" to lead to node cs-identifier;carl-destinode;{}
    And I expect this node to be referenced by:
      | Name               | Node                               | Properties |
      | references | cs-identifier;source-nodandaise;{} | null       |

  Scenario: Ensure that multiple references with properties between nodes can be set and read
    When the command SetNodeReferences is executed with payload:
      | Key                   | Value                                                                                                                                                                                                                    |
      | sourceNodeAggregateId | "source-nodandaise"                                                                                                                                                                                                      |
      | referenceName         | "referencesWithProperty"                                                                                                                                                                                         |
      | references            | [{"target":"berta-destinode", "properties":{"text":"my text", "dayOfWeek":"DayOfWeek:https://schema.org/Wednesday", "postalAddress":"PostalAddress:dummy"}}, {"target":"carl-destinode", "properties":{"text":"my other text", "dayOfWeek":"DayOfWeek:https://schema.org/Friday", "postalAddress":"PostalAddress:anotherDummy"}}] |
    And the graph projection is fully up to date

    Then I expect node aggregate identifier "source-nodandaise" to lead to node cs-identifier;source-nodandaise;{}
    And I expect this node to have the following references:
      | Name                           | Node                             | Properties                                                             |
      | referencesWithProperty | cs-identifier;berta-destinode;{} | {"text":"my text", "dayOfWeek":"DayOfWeek:https://schema.org/Wednesday", "postalAddress":"PostalAddress:dummy"}              |
      | referencesWithProperty | cs-identifier;carl-destinode;{}  | {"text":"my other text", "dayOfWeek":"DayOfWeek:https://schema.org/Friday", "postalAddress":"PostalAddress:anotherDummy"} |

    And I expect node aggregate identifier "berta-destinode" to lead to node cs-identifier;berta-destinode;{}
    And I expect this node to be referenced by:
      | Name                           | Node                               | Properties                                                |
      | referencesWithProperty | cs-identifier;source-nodandaise;{} | {"text":"my text", "dayOfWeek":"DayOfWeek:https://schema.org/Wednesday", "postalAddress":"PostalAddress:dummy"} |

    And I expect node aggregate identifier "carl-destinode" to lead to node cs-identifier;carl-destinode;{}
    And I expect this node to be referenced by:
      | Name                           | Node                               | Properties                                                             |
      | referencesWithProperty | cs-identifier;source-nodandaise;{} | {"text":"my other text", "dayOfWeek":"DayOfWeek:https://schema.org/Friday", "postalAddress":"PostalAddress:anotherDummy"} |

  Scenario: Ensure that references between nodes can be set and overwritten
    When the command SetNodeReferences is executed with payload:
      | Key                   | Value                                                         |
      | sourceNodeAggregateId | "source-nodandaise"                                           |
      | references            | [{"target": "berta-destinode"}, {"target": "carl-destinode"}] |
      | referenceName         | "references"                                          |
    And the graph projection is fully up to date

    And the command SetNodeReferences is executed with payload:
      | Key                   | Value                             |
      | sourceNodeAggregateId | "source-nodandaise"               |
      | references            | [{"target": "anthony-destinode"}] |
      | referenceName         | "references"              |
    And the graph projection is fully up to date

    Then I expect node aggregate identifier "source-nodandaise" to lead to node cs-identifier;source-nodandaise;{}
    And I expect this node to have the following references:
      | Name               | Node                               | Properties |
      | references | cs-identifier;anthony-destinode;{} | null       |

    And I expect node aggregate identifier "anthony-destinode" to lead to node cs-identifier;anthony-destinode;{}
    And I expect this node to be referenced by:
      | Name               | Node                               | Properties |
      | references | cs-identifier;source-nodandaise;{} | null       |

    And I expect node aggregate identifier "berta-destinode" to lead to node cs-identifier;berta-destinode;{}
    And I expect this node to not be referenced

    And I expect node aggregate identifier "carl-destinode" to lead to node cs-identifier;carl-destinode;{}
    And I expect this node to not be referenced

  Scenario: Ensure that references between nodes can be set and reordered

    When the command SetNodeReferences is executed with payload:
      | Key                   | Value                                                         |
      | sourceNodeAggregateId | "source-nodandaise"                                           |
      | references            | [{"target": "berta-destinode"}, {"target": "carl-destinode"}] |
      | referenceName         | "references"                                          |
    And the graph projection is fully up to date

    And the command SetNodeReferences is executed with payload:
      | Key                   | Value                                                         |
      | sourceNodeAggregateId | "source-nodandaise"                                           |
      | references            | [{"target": "carl-destinode"}, {"target": "berta-destinode"}] |
      | referenceName         | "references"                                          |
    And the graph projection is fully up to date

    Then I expect node aggregate identifier "source-nodandaise" to lead to node cs-identifier;source-nodandaise;{}
    And I expect this node to have the following references:
      | Name               | Node                             | Properties |
      | references | cs-identifier;carl-destinode;{}  | null       |
      | references | cs-identifier;berta-destinode;{} | null       |

  Scenario: Ensure that references between nodes can be deleted

    When the command SetNodeReferences is executed with payload:
      | Key                   | Value                                                         |
      | sourceNodeAggregateId | "source-nodandaise"                                           |
      | references            | [{"target": "berta-destinode"}, {"target": "carl-destinode"}] |
      | referenceName         | "references"                                          |
    And the graph projection is fully up to date

    And the command SetNodeReferences is executed with payload:
      | Key                   | Value                |
      | sourceNodeAggregateId | "source-nodandaise"  |
      | references            | []                   |
      | referenceName         | "references" |

    And the graph projection is fully up to date

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
      | referenceName         | "reference"               |
    And the graph projection is fully up to date

    And the command SetNodeReferences is executed with payload:
      | Key                   | Value                             |
      | sourceNodeAggregateId | "berta-destinode"                 |
      | references            | [{"target": "anthony-destinode"}] |
      | referenceName         | "reference"               |
    And the graph projection is fully up to date

    Then I expect node aggregate identifier "anthony-destinode" to lead to node cs-identifier;anthony-destinode;{}
    And I expect this node to be referenced by:
      | Name              | Node                               | Properties |
      | reference | cs-identifier;berta-destinode;{}   | null       |
      | reference | cs-identifier;source-nodandaise;{} | null       |

  Scenario: Ensure that a reference between nodes can be set and read when matching constraints
    When the command SetNodeReferences is executed with payload:
      | Key                   | Value                             |
      | sourceNodeAggregateId | "source-nodandaise"               |
      | references            | [{"target": "anthony-destinode"}] |
      | referenceName         | "restrictedReference"     |
    And the graph projection is fully up to date

    Then I expect node aggregate identifier "source-nodandaise" to lead to node cs-identifier;source-nodandaise;{}
    And I expect this node to have the following references:
      | Name                        | Node                               | Properties |
      | restrictedReference | cs-identifier;anthony-destinode;{} | null       |

    And I expect node aggregate identifier "anthony-destinode" to lead to node cs-identifier;anthony-destinode;{}
    And I expect this node to be referenced by:
      | Name                        | Node                               | Properties |
      | restrictedReference | cs-identifier;source-nodandaise;{} | null       |
