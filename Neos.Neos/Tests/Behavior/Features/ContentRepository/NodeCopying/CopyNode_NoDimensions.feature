@contentrepository @adapters=DoctrineDBAL
Feature: Copy nodes (without dimensions)

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Document':
      properties:
        title:
          type: string
        array:
          type: array
        uri:
          type: GuzzleHttp\Psr7\Uri
        date:
          type: DateTimeImmutable
      references:
        ref: []
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And I am in workspace "live"
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |

    When the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId            | parentNodeAggregateId  | nodeTypeName                            |
      | sir-david-nodenborough     | lady-eleonode-rootford | Neos.ContentRepository.Testing:Document |
      | nody-mc-nodeface           | sir-david-nodenborough | Neos.ContentRepository.Testing:Document |
      | node-wan-kenodi            | lady-eleonode-rootford | Neos.ContentRepository.Testing:Document |
      | sir-nodeward-nodington-iii | node-wan-kenodi        | Neos.ContentRepository.Testing:Document |

  Scenario: Simple singular node aggregate is copied
    When I am in workspace "live" and dimension space point {}
    When copy nodes recursively is executed with payload:
      | Key                                    | Value                                                             |
      | sourceDimensionSpacePoint              | {}                                                                |
      | sourceNodeAggregateId                  | "sir-nodeward-nodington-iii"                                      |
      | targetDimensionSpacePoint              | {}                                                                |
      | targetParentNodeAggregateId            | "nody-mc-nodeface"                                                |
      | targetSucceedingSiblingnodeAggregateId | null                                                              |
      | nodeAggregateIdMapping                 | {"sir-nodeward-nodington-iii": "sir-nodeward-nodington-iii-copy"} |

    Then I expect node aggregate identifier "sir-nodeward-nodington-iii-copy" to lead to node cs-identifier;sir-nodeward-nodington-iii-copy;{}
    And I expect the node aggregate "sir-nodeward-nodington-iii-copy" to exist
    And I expect this node aggregate to be classified as "regular"
    And I expect this node aggregate to be unnamed
    And I expect this node aggregate to be of type "Neos.ContentRepository.Testing:Document"
    And I expect this node aggregate to occupy dimension space points [[]]
    And I expect this node aggregate to disable dimension space points []
    And I expect this node aggregate to have no child node aggregates
    And I expect this node aggregate to have the parent node aggregates ["nody-mc-nodeface"]

  Scenario: Singular node aggregate is copied with (complex) properties
    When I am in workspace "live" and dimension space point {}
    And the command SetNodeProperties is executed with payload:
      | Key             | Value                                                                                                                                                                                                                                           |
      | nodeAggregateId | "sir-nodeward-nodington-iii"                                                                                                                                                                                                                    |
      | propertyValues  | {"title": "Original Text", "array": {"givenName":"Nody", "familyName":"McNodeface"}, "uri": {"__type": "GuzzleHttp\\\\Psr7\\\\Uri", "value": "https://neos.de"}, "date": {"__type": "DateTimeImmutable", "value": "2001-09-22T12:00:00+00:00"}} |

    When copy nodes recursively is executed with payload:
      | Key                                    | Value                                                             |
      | sourceDimensionSpacePoint              | {}                                                                |
      | sourceNodeAggregateId                  | "sir-nodeward-nodington-iii"                                      |
      | targetDimensionSpacePoint              | {}                                                                |
      | targetParentNodeAggregateId            | "nody-mc-nodeface"                                                |
      | targetSucceedingSiblingnodeAggregateId | null                                                              |
      | nodeAggregateIdMapping                 | {"sir-nodeward-nodington-iii": "sir-nodeward-nodington-iii-copy"} |

    And I expect node aggregate identifier "sir-nodeward-nodington-iii-copy" to lead to node cs-identifier;sir-nodeward-nodington-iii-copy;{}
    And I expect this node to have the following serialized properties:
      | Key   | Type                | Value                                          |
      | title | string              | "Original Text"                                |
      | array | array               | {"givenName":"Nody","familyName":"McNodeface"} |
      | date  | DateTimeImmutable   | "2001-09-22T12:00:00+00:00"                    |
      | uri   | GuzzleHttp\Psr7\Uri | "https://neos.de"                              |

  Scenario: Singular node aggregate is copied with references
    When I am in workspace "live" and dimension space point {}
    And the command SetNodeReferences is executed with payload:
      | Key                   | Value                                                                            |
      | sourceNodeAggregateId | "sir-nodeward-nodington-iii"                                                     |
      | references            | [{"referenceName": "ref", "references": [{"target": "sir-david-nodenborough"}]}] |

    When copy nodes recursively is executed with payload:
      | Key                                    | Value                                                             |
      | sourceDimensionSpacePoint              | {}                                                                |
      | sourceNodeAggregateId                  | "sir-nodeward-nodington-iii"                                      |
      | targetDimensionSpacePoint              | {}                                                                |
      | targetParentNodeAggregateId            | "nody-mc-nodeface"                                                |
      | targetSucceedingSiblingnodeAggregateId | null                                                              |
      | nodeAggregateIdMapping                 | {"sir-nodeward-nodington-iii": "sir-nodeward-nodington-iii-copy"} |

    And I expect node aggregate identifier "sir-nodeward-nodington-iii-copy" to lead to node cs-identifier;sir-nodeward-nodington-iii-copy;{}
    And I expect this node to have the following references:
      | Name | Node                                    | Properties |
      | ref  | cs-identifier;sir-david-nodenborough;{} | null       |

  Scenario: Singular node aggregate is copied with subtree tags (and disabled state)
    When I am in workspace "live" and dimension space point {}

    Given the command DisableNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {}                           |
      | nodeVariantSelectionStrategy | "allVariants"                |

    Given the command TagSubtree is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {}                           |
      | nodeVariantSelectionStrategy | "allVariants"                |
      | tag                          | "tag1"                       |

    Given the command TagSubtree is executed with payload:
      | Key                          | Value             |
      | nodeAggregateId              | "node-wan-kenodi" |
      | coveredDimensionSpacePoint   | {}                |
      | nodeVariantSelectionStrategy | "allVariants"     |
      | tag                          | "parent-tag"      |

    And VisibilityConstraints are set to "withoutRestrictions"

    # we inherit the tag here but DONT copy it!
    Then I expect the node with aggregate identifier "sir-nodeward-nodington-iii" to inherit the tag "parent-tag"

    When copy nodes recursively is executed with payload:
      | Key                                    | Value                                                             |
      | sourceDimensionSpacePoint              | {}                                                                |
      | sourceNodeAggregateId                  | "sir-nodeward-nodington-iii"                                      |
      | targetDimensionSpacePoint              | {}                                                                |
      | targetParentNodeAggregateId            | "nody-mc-nodeface"                                                |
      | targetSucceedingSiblingnodeAggregateId | null                                                              |
      | nodeAggregateIdMapping                 | {"sir-nodeward-nodington-iii": "sir-nodeward-nodington-iii-copy"} |

    And I expect the node aggregate "sir-nodeward-nodington-iii-copy" to exist
    And I expect this node aggregate to disable dimension space points [{}]

    And I expect node aggregate identifier "sir-nodeward-nodington-iii-copy" to lead to node cs-identifier;sir-nodeward-nodington-iii-copy;{}
    And I expect this node to be exactly explicitly tagged "disabled,tag1"

  Scenario: Node aggregate is copied children recursively
    When I am in workspace "live" and dimension space point {}
    When the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId | parentNodeAggregateId      | nodeTypeName                            | initialPropertyValues     |
      | child-a         | sir-nodeward-nodington-iii | Neos.ContentRepository.Testing:Document | {}                        |
      | child-a1        | child-a                    | Neos.ContentRepository.Testing:Document | {"title": "I am Node A1"} |
      | child-a2        | child-a                    | Neos.ContentRepository.Testing:Document | {}                        |
      | child-b         | sir-nodeward-nodington-iii | Neos.ContentRepository.Testing:Document | {}                        |

    When copy nodes recursively is executed with payload:
      | Key                                    | Value                                                                                                                                                                             |
      | sourceDimensionSpacePoint              | {}                                                                                                                                                                                |
      | sourceNodeAggregateId                  | "sir-nodeward-nodington-iii"                                                                                                                                                      |
      | targetDimensionSpacePoint              | {}                                                                                                                                                                                |
      | targetParentNodeAggregateId            | "nody-mc-nodeface"                                                                                                                                                                |
      | nodeAggregateIdMapping                 | {"sir-nodeward-nodington-iii": "sir-nodeward-nodington-iii-copy", "child-a": "child-a-copy", "child-b": "child-b-copy", "child-a1": "child-a1-copy", "child-a2": "child-a2-copy"} |

    And I expect the node aggregate "sir-nodeward-nodington-iii-copy" to exist
    And I expect this node aggregate to have the child node aggregates ["child-a-copy","child-b-copy"]

    And I expect the node aggregate "child-a-copy" to exist
    And I expect this node aggregate to have the child node aggregates ["child-a1-copy","child-a2-copy"]

    And I expect the node aggregate "child-b-copy" to exist
    And I expect this node aggregate to have no child node aggregates

    And I expect node aggregate identifier "child-a1-copy" to lead to node cs-identifier;child-a1-copy;{}
    And I expect this node to have the following serialized properties:
      | Key   | Type   | Value          |
      | title | string | "I am Node A1" |

  Scenario: Soft removed nodes are not copied
    When I am in workspace "live" and dimension space point {}
    When the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId | parentNodeAggregateId      | nodeTypeName                            | initialPropertyValues     |
      | child-a         | sir-nodeward-nodington-iii | Neos.ContentRepository.Testing:Document | {}                        |
      | child-a1        | child-a                    | Neos.ContentRepository.Testing:Document | {"title": "I am Node A1"} |
      | child-a2        | child-a                    | Neos.ContentRepository.Testing:Document | {}                        |
      | child-b         | sir-nodeward-nodington-iii | Neos.ContentRepository.Testing:Document | {}                        |
    Given the command TagSubtree is executed with payload:
      | Key                          | Value         |
      | nodeAggregateId              | "child-a"     |
      | coveredDimensionSpacePoint   | {}            |
      | nodeVariantSelectionStrategy | "allVariants" |
      | tag                          | "removed"     |

    When copy nodes recursively is executed with payload:
      | Key                                    | Value                                                                                        |
      | sourceDimensionSpacePoint              | {}                                                                                           |
      | sourceNodeAggregateId                  | "sir-nodeward-nodington-iii"                                                                 |
      | targetDimensionSpacePoint              | {}                                                                                           |
      | targetParentNodeAggregateId            | "nody-mc-nodeface"                                                                           |
      | targetSucceedingSiblingnodeAggregateId | null                                                                                         |
      | nodeAggregateIdMapping                 | {"sir-nodeward-nodington-iii": "sir-nodeward-nodington-iii-copy", "child-b": "child-b-copy"} |

    And I expect the node aggregate "sir-nodeward-nodington-iii-copy" to exist
    And I expect this node aggregate to have the child node aggregates ["child-b-copy"]

  Scenario: References are copied for child nodes
    When I am in workspace "live" and dimension space point {}
    When the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId | parentNodeAggregateId      | nodeTypeName                            | references                                                                       |
      | child-a         | sir-nodeward-nodington-iii | Neos.ContentRepository.Testing:Document | [{"referenceName": "ref", "references": [{"target": "sir-david-nodenborough"}]}] |

    When copy nodes recursively is executed with payload:
      | Key                                    | Value                                                                                        |
      | sourceDimensionSpacePoint              | {}                                                                                           |
      | sourceNodeAggregateId                  | "sir-nodeward-nodington-iii"                                                                 |
      | targetDimensionSpacePoint              | {}                                                                                           |
      | targetParentNodeAggregateId            | "nody-mc-nodeface"                                                                           |
      | nodeAggregateIdMapping                 | {"sir-nodeward-nodington-iii": "sir-nodeward-nodington-iii-copy", "child-a": "child-a-copy"} |

    And I expect node aggregate identifier "child-a-copy" to lead to node cs-identifier;child-a-copy;{}
    And I expect this node to have the following references:
      | Name | Node                                    | Properties |
      | ref  | cs-identifier;sir-david-nodenborough;{} | null       |

  Scenario: Subtree tags are copied for child nodes
    When I am in workspace "live" and dimension space point {}
    When the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId | parentNodeAggregateId      | nodeTypeName                            |
      | child-a         | sir-nodeward-nodington-iii | Neos.ContentRepository.Testing:Document |

    Given the command TagSubtree is executed with payload:
      | Key                          | Value         |
      | nodeAggregateId              | "child-a"     |
      | coveredDimensionSpacePoint   | {}            |
      | nodeVariantSelectionStrategy | "allVariants" |
      | tag                          | "tag1"        |

    When copy nodes recursively is executed with payload:
      | Key                                    | Value                                                                                        |
      | sourceDimensionSpacePoint              | {}                                                                                           |
      | sourceNodeAggregateId                  | "sir-nodeward-nodington-iii"                                                                 |
      | targetDimensionSpacePoint              | {}                                                                                           |
      | targetParentNodeAggregateId            | "nody-mc-nodeface"                                                                           |
      | nodeAggregateIdMapping                 | {"sir-nodeward-nodington-iii": "sir-nodeward-nodington-iii-copy", "child-a": "child-a-copy"} |

    And I expect node aggregate identifier "child-a-copy" to lead to node cs-identifier;child-a-copy;{}
    And I expect this node to be exactly explicitly tagged "tag1"
