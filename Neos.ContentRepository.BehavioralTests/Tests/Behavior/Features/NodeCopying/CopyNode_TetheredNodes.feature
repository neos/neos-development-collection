Feature: Copy nodes with tethered nodes

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Document': []
    'Neos.ContentRepository.Testing:DocumentWithTethered':
      childNodes:
        tethered:
          type: 'Neos.ContentRepository.Testing:Document'
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
      | nodeAggregateId          | parentNodeAggregateId  | nodeTypeName                                        | tetheredDescendantNodeAggregateIds |
      | sir-david-nodenborough   | lady-eleonode-rootford | Neos.ContentRepository.Testing:Document             | {}                                 |
      | nody-mc-nodeface         | sir-david-nodenborough | Neos.ContentRepository.Testing:Document             |                                    |
      | sir-nodeward-nodington-i | lady-eleonode-rootford | Neos.ContentRepository.Testing:DocumentWithTethered | {"tethered": "nodewyn-tetherton"}  |

  Scenario: Coping a tethered node turns it into a regular node
    And I expect the node aggregate "nodewyn-tetherton" to exist
    And I expect this node aggregate to be classified as "tethered"

    When the command CopyNodesRecursively is executed with payload:
      | Key                         | Value                                           |
      | sourceDimensionSpacePoint   | {}                                              |
      | sourceNodeAggregateId       | "nodewyn-tetherton"                             |
      | targetDimensionSpacePoint   | {}                                              |
      | targetParentNodeAggregateId | "sir-david-nodenborough"                        |
      | nodeAggregateIdMapping      | {"nodewyn-tetherton": "nodewyn-tetherton-copy"} |

    And I expect the node aggregate "nodewyn-tetherton-copy" to exist
    # must not be tethered!
    And I expect this node aggregate to be classified as "regular"
    And I expect this node aggregate to be of type "Neos.ContentRepository.Testing:Document"
    And I expect this node aggregate to be unnamed
    And I expect this node aggregate to occupy dimension space points [[]]
    And I expect this node aggregate to disable dimension space points []
    And I expect this node aggregate to have no child node aggregates
    And I expect this node aggregate to have the parent node aggregates ["sir-david-nodenborough"]

  Scenario: Coping a node with tethered node keeps the child node tethered
    And I expect the node aggregate "nodewyn-tetherton" to exist
    And I expect this node aggregate to be classified as "tethered"

    When the command CopyNodesRecursively is executed with payload:
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
    And I expect this node aggregate to be of type "Neos.ContentRepository.Testing:Document"
    And I expect this node aggregate to occupy dimension space points [[]]
    And I expect this node aggregate to disable dimension space points []
    And I expect this node aggregate to have no child node aggregates
    And I expect this node aggregate to have the parent node aggregates ["sir-nodeward-nodington-ii"]
