Feature: Copy nodes constraint checks

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Document':
      properties:
        title:
          type: string
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And I am in workspace "live" and dimension space point {}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |

    When the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId            | parentNodeAggregateId  | nodeTypeName                            | initialPropertyValues     |
      | nody-mc-nodeface           | lady-eleonode-rootford | Neos.ContentRepository.Testing:Document | {"title": "Toller Titel"} |
      | sir-nodeward-nodington-iii | lady-eleonode-rootford | Neos.ContentRepository.Testing:Document | {"title": "Toller Titel"} |
      | nody-jr                    | nody-mc-nodeface       | Neos.ContentRepository.Testing:Document | {"title": "Toller Titel"} |

  Scenario: Coping works even if previously present node properties are no longer defined
    Given I change the node types in content repository "default" to:
    """yaml
    'Neos.ContentRepository.Testing:Document': {}
    """
    When copy nodes recursively is executed with payload:
      | Key                                    | Value                                                             |
      | sourceDimensionSpacePoint              | {}                                                                |
      | sourceNodeAggregateId                  | "sir-nodeward-nodington-iii"                                      |
      | targetDimensionSpacePoint              | {}                                                                |
      | targetParentNodeAggregateId            | "nody-mc-nodeface"                                                |
      | targetSucceedingSiblingnodeAggregateId | null                                                              |
      | nodeAggregateIdMapping                 | {"sir-nodeward-nodington-iii": "sir-nodeward-nodington-iii-copy"} |


    Then I expect node aggregate identifier "sir-nodeward-nodington-iii-copy" to lead to node cs-identifier;sir-nodeward-nodington-iii-copy;{}
    And I expect this node to have no properties

  Scenario: Coping works even if previously present node properties are no longer defined on the child node
    Given I change the node types in content repository "default" to:
    """yaml
    'Neos.ContentRepository.Testing:Document': {}
    """
    When copy nodes recursively is executed with payload:
      | Key                                    | Value                         |
      | sourceDimensionSpacePoint              | {}                            |
      | sourceNodeAggregateId                  | "nody-mc-nodeface"            |
      | targetDimensionSpacePoint              | {}                            |
      | targetParentNodeAggregateId            | "sir-nodeward-nodington-iii"  |
      | targetSucceedingSiblingnodeAggregateId | null                          |
      | nodeAggregateIdMapping                 | {"nody-jr": "nody-jr-copy"}   |


    Then I expect node aggregate identifier "nody-jr-copy" to lead to node cs-identifier;nody-jr-copy;{}
    And I expect this node to have no properties