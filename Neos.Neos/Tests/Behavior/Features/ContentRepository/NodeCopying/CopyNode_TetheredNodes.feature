Feature: Copy nodes with tethered nodes

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Tethered':
      properties:
        title:
          type: string
      references:
        ref: []
    'Neos.ContentRepository.Testing:DocumentWithTethered':
      childNodes:
        tethered:
          type: 'Neos.ContentRepository.Testing:Tethered'
    'Neos.ContentRepository.Testing:RootDocument':
      childNodes:
        tethered-document:
          type: 'Neos.ContentRepository.Testing:DocumentWithTethered'
    'Neos.ContentRepository.Testing:DocumentWithoutTetheredChildren': []
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    When I am in workspace "live" and dimension space point {}
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |

    When the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId          | parentNodeAggregateId  | nodeTypeName                                                   | tetheredDescendantNodeAggregateIds                                                                  |
      | sir-david-nodenborough   | lady-eleonode-rootford | Neos.ContentRepository.Testing:DocumentWithoutTetheredChildren | {}                                                                                                  |
      | nody-mc-nodeface         | sir-david-nodenborough | Neos.ContentRepository.Testing:DocumentWithoutTetheredChildren |                                                                                                     |
      | nodimus-primus           | lady-eleonode-rootford | Neos.ContentRepository.Testing:DocumentWithoutTetheredChildren |                                                                                                     |
      | sir-nodeward-nodington-i | nodimus-primus         | Neos.ContentRepository.Testing:DocumentWithTethered            | {"tethered": "nodewyn-tetherton"}                                                                   |
      | node-wan-kenodi          | sir-david-nodenborough | Neos.ContentRepository.Testing:RootDocument                    | {"tethered-document": "tethered-document", "tethered-document/tethered": "tethered-document-child"} |

  Scenario: Coping a tethered node turns it into a regular node
    And I expect the node aggregate "nodewyn-tetherton" to exist
    And I expect this node aggregate to be classified as "tethered"

    When copy nodes recursively is executed with payload:
      | Key                         | Value                                           |
      | sourceDimensionSpacePoint   | {}                                              |
      | sourceNodeAggregateId       | "nodewyn-tetherton"                             |
      | targetDimensionSpacePoint   | {}                                              |
      | targetParentNodeAggregateId | "sir-david-nodenborough"                        |
      | nodeAggregateIdMapping      | {"nodewyn-tetherton": "nodewyn-tetherton-copy"} |

    And I expect the node aggregate "nodewyn-tetherton-copy" to exist
    # must not be tethered!
    And I expect this node aggregate to be classified as "regular"
    And I expect this node aggregate to be of type "Neos.ContentRepository.Testing:Tethered"
    And I expect this node aggregate to be unnamed
    And I expect this node aggregate to occupy dimension space points [[]]
    And I expect this node aggregate to disable dimension space points []
    And I expect this node aggregate to have no child node aggregates
    And I expect this node aggregate to have the parent node aggregates ["sir-david-nodenborough"]

  Scenario: Coping a node with tethered node keeps the child node tethered
    And I expect the node aggregate "nodewyn-tetherton" to exist
    And I expect this node aggregate to be classified as "tethered"

    When copy nodes recursively is executed with payload:
      | Key                         | Value                                                                                                    |
      | sourceDimensionSpacePoint   | {}                                                                                                       |
      | sourceNodeAggregateId       | "sir-nodeward-nodington-i"                                                                               |
      | targetDimensionSpacePoint   | {}                                                                                                       |
      | targetParentNodeAggregateId | "sir-david-nodenborough"                                                                                 |
      | nodeAggregateIdMapping      | {"sir-nodeward-nodington-i": "sir-nodeward-nodington-ii", "nodewyn-tetherton": "nodewyn-tetherton-copy"} |

    And I expect the node aggregate "sir-nodeward-nodington-ii" to exist
    And I expect this node aggregate to be classified as "regular"
    And I expect this node aggregate to be unnamed
    And I expect this node aggregate to be of type "Neos.ContentRepository.Testing:DocumentWithTethered"
    And I expect this node aggregate to occupy dimension space points [[]]
    And I expect this node aggregate to disable dimension space points []
    And I expect this node aggregate to have the child node aggregates ["nodewyn-tetherton-copy"]
    And I expect this node aggregate to have the parent node aggregates ["sir-david-nodenborough"]

    And I expect the node aggregate "nodewyn-tetherton-copy" to exist
    And I expect this node aggregate to be classified as "tethered"
    And I expect this node aggregate to be named "tethered"
    And I expect this node aggregate to be of type "Neos.ContentRepository.Testing:Tethered"
    And I expect this node aggregate to occupy dimension space points [[]]
    And I expect this node aggregate to disable dimension space points []
    And I expect this node aggregate to have no child node aggregates
    And I expect this node aggregate to have the parent node aggregates ["sir-nodeward-nodington-ii"]

  Scenario: Coping a node with nested tethered nodes keeps the child nodes tethered
    When copy nodes recursively is executed with payload:
      | Key                         | Value                                                                                                                                                 |
      | sourceDimensionSpacePoint   | {}                                                                                                                                                    |
      | sourceNodeAggregateId       | "node-wan-kenodi"                                                                                                                                     |
      | targetDimensionSpacePoint   | {}                                                                                                                                                    |
      | targetParentNodeAggregateId | "sir-david-nodenborough"                                                                                                                              |
      | nodeAggregateIdMapping      | {"node-wan-kenodi": "node-wan-kenodi-copy", "tethered-document": "tethered-document-copy", "tethered-document-child": "tethered-document-child-copy"} |

    And I expect the node aggregate "node-wan-kenodi-copy" to exist
    And I expect this node aggregate to be classified as "regular"
    And I expect this node aggregate to be unnamed
    And I expect this node aggregate to be of type "Neos.ContentRepository.Testing:RootDocument"
    And I expect this node aggregate to occupy dimension space points [[]]
    And I expect this node aggregate to disable dimension space points []
    And I expect this node aggregate to have the child node aggregates ["tethered-document-copy"]
    And I expect this node aggregate to have the parent node aggregates ["sir-david-nodenborough"]

    And I expect the node aggregate "tethered-document-copy" to exist
    And I expect this node aggregate to be classified as "tethered"
    And I expect this node aggregate to be named "tethered-document"
    And I expect this node aggregate to be of type "Neos.ContentRepository.Testing:DocumentWithTethered"
    And I expect this node aggregate to occupy dimension space points [[]]
    And I expect this node aggregate to disable dimension space points []
    And I expect this node aggregate to have the child node aggregates ["tethered-document-child-copy"]

    And I expect the node aggregate "tethered-document-child-copy" to exist
    And I expect this node aggregate to be classified as "tethered"
    And I expect this node aggregate to be named "tethered"
    And I expect this node aggregate to be of type "Neos.ContentRepository.Testing:Tethered"
    And I expect this node aggregate to occupy dimension space points [[]]
    And I expect this node aggregate to disable dimension space points []
    And I expect this node aggregate to have no child node aggregates

  Scenario: Coping a regular node with a child node that has a tethered child node
    And I expect the node aggregate "nodewyn-tetherton" to exist
    And I expect this node aggregate to be classified as "tethered"

    When copy nodes recursively is executed with payload:
      | Key                         | Value                                                                                                                                                 |
      | sourceDimensionSpacePoint   | {}                                                                                                                                                    |
      | sourceNodeAggregateId       | "nodimus-primus"                                                                                                                                      |
      | targetDimensionSpacePoint   | {}                                                                                                                                                    |
      | targetParentNodeAggregateId | "sir-david-nodenborough"                                                                                                                              |
      | nodeAggregateIdMapping      | {"nodimus-primus": "nodimus-primus-copy", "sir-nodeward-nodington-i": "sir-nodeward-nodington-i-copy", "nodewyn-tetherton": "nodewyn-tetherton-copy"} |

    And I expect the node aggregate "nodimus-primus-copy" to exist
    And I expect this node aggregate to be classified as "regular"
    And I expect this node aggregate to be unnamed
    And I expect this node aggregate to be of type "Neos.ContentRepository.Testing:DocumentWithoutTetheredChildren"
    And I expect this node aggregate to occupy dimension space points [[]]
    And I expect this node aggregate to disable dimension space points []
    And I expect this node aggregate to have the child node aggregates ["sir-nodeward-nodington-i-copy"]
    And I expect this node aggregate to have the parent node aggregates ["sir-david-nodenborough"]

    And I expect the node aggregate "sir-nodeward-nodington-i-copy" to exist
    And I expect this node aggregate to be classified as "regular"
    And I expect this node aggregate to be unnamed
    And I expect this node aggregate to be of type "Neos.ContentRepository.Testing:DocumentWithTethered"
    And I expect this node aggregate to occupy dimension space points [[]]
    And I expect this node aggregate to disable dimension space points []
    And I expect this node aggregate to have the child node aggregates ["nodewyn-tetherton-copy"]
    And I expect this node aggregate to have the parent node aggregates ["nodimus-primus-copy"]

    And I expect the node aggregate "nodewyn-tetherton-copy" to exist
    And I expect this node aggregate to be classified as "tethered"
    And I expect this node aggregate to be named "tethered"
    And I expect this node aggregate to be of type "Neos.ContentRepository.Testing:Tethered"
    And I expect this node aggregate to occupy dimension space points [[]]
    And I expect this node aggregate to disable dimension space points []
    And I expect this node aggregate to have no child node aggregates
    And I expect this node aggregate to have the parent node aggregates ["sir-nodeward-nodington-i-copy"]

  Scenario: Properties and references are copied for tethered child nodes
    And the command SetNodeReferences is executed with payload:
      | Key                   | Value                                                                            |
      | sourceNodeAggregateId | "nodewyn-tetherton"                                                              |
      | references            | [{"referenceName": "ref", "references": [{"target": "sir-david-nodenborough"}]}] |

    And the command SetNodeProperties is executed with payload:
      | Key             | Value                      |
      | nodeAggregateId | "nodewyn-tetherton"        |
      | propertyValues  | {"title": "Original Text"} |

    When copy nodes recursively is executed with payload:
      | Key                         | Value                                                                                                    |
      | sourceDimensionSpacePoint   | {}                                                                                                       |
      | sourceNodeAggregateId       | "sir-nodeward-nodington-i"                                                                               |
      | targetDimensionSpacePoint   | {}                                                                                                       |
      | targetParentNodeAggregateId | "sir-david-nodenborough"                                                                                 |
      | nodeAggregateIdMapping      | {"sir-nodeward-nodington-i": "sir-nodeward-nodington-ii", "nodewyn-tetherton": "nodewyn-tetherton-copy"} |

    And I expect node aggregate identifier "nodewyn-tetherton-copy" to lead to node cs-identifier;nodewyn-tetherton-copy;{}
    And I expect this node to have the following properties:
      | Key   | Value           |
      | title | "Original Text" |
    And I expect this node to have the following references:
      | Name | Node                                    | Properties |
      | ref  | cs-identifier;sir-david-nodenborough;{} | null       |

  Scenario: Properties are copied for deeply nested tethered nodes
    And the command SetNodeProperties is executed with payload:
      | Key             | Value                      |
      | nodeAggregateId | "tethered-document-child"  |
      | propertyValues  | {"title": "Original Text"} |

    When copy nodes recursively is executed with payload:
      | Key                         | Value                                                                                                                                                                                                          |
      | sourceDimensionSpacePoint   | {}                                                                                                                                                                                                             |
      | sourceNodeAggregateId       | "sir-david-nodenborough"                                                                                                                                                                                       |
      | targetDimensionSpacePoint   | {}                                                                                                                                                                                                             |
      | targetParentNodeAggregateId | "nodimus-primus"                                                                                                                                                                                               |
      | nodeAggregateIdMapping      | {"sir-david-nodenborough": "sir-david-nodenborough-copy", "node-wan-kenodi": "node-wan-kenodi-copy", "tethered-document": "tethered-document-copy", "tethered-document-child": "tethered-document-child-copy"} |

    And I expect node aggregate identifier "tethered-document-child-copy" to lead to node cs-identifier;tethered-document-child-copy;{}
    And I expect this node to have the following properties:
      | Key   | Value           |
      | title | "Original Text" |
