@contentrepository
Feature: Copy nodes constraint checks

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Tethered': []
    'Neos.ContentRepository.Testing:TetheredDocument':
      childNodes:
        tethered:
          type: 'Neos.ContentRepository.Testing:Tethered'
    'Neos.ContentRepository.Testing:Document':
      childNodes:
        tethered-document:
          type: 'Neos.ContentRepository.Testing:TetheredDocument'
    'Neos.ContentRepository.Testing:DocumentWithoutTetheredChildren': []
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And I am in workspace "live" and dimension space point {}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId     | parentNodeAggregateId  | nodeTypeName                                                   | tetheredDescendantNodeAggregateIds                                                            |
      | node-mc-nodeface    | lady-eleonode-rootford | Neos.ContentRepository.Testing:DocumentWithoutTetheredChildren | {}                                                                                            |
      | node-wan-kenody     | lady-eleonode-rootford | Neos.ContentRepository.Testing:Document                        | {"tethered-document": "nodewyn-tetherton", "tethered-document/tethered": "nodimer-tetherton"} |
      | sir-david-nodenburg | lady-eleonode-rootford | Neos.ContentRepository.Testing:TetheredDocument                | {"tethered": "davids-tether"}                                                                 |

  Scenario: Coping fails if the leaf of a nested tethered node is attempted to be copied
    And I expect the node aggregate "nodewyn-tetherton" to exist
    And I expect this node aggregate to be classified as "tethered"

    And I expect the node aggregate "nodimer-tetherton" to exist
    And I expect this node aggregate to be classified as "tethered"

    When copy nodes recursively is executed with payload:
      | Key                         | Value               |
      | sourceDimensionSpacePoint   | {}                  |
      | sourceNodeAggregateId       | "nodewyn-tetherton" |
      | targetDimensionSpacePoint   | {}                  |
      | targetParentNodeAggregateId | "node-mc-nodeface"  |

    Then an exception of type TetheredNodesCannotBePartiallyCopied should be thrown with message:
    """
    Cannot copy tethered node nodewyn-tetherton because child node nodimer-tetherton is also tethered. Only standalone tethered nodes can be copied.
    """
