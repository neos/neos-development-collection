Feature: Copying a document node will adjust its uriPathSegment

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.Neos:Sites':
      superTypes:
        Neos.ContentRepository:Root: true
    'Neos.Neos:Site': []
    'Neos.Neos:Document':
      properties:
        uriPathSegment:
          type: string
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
      | Key             | Value                    |
      | nodeAggregateId | "lady-eleonode-rootford" |
      | nodeTypeName    | "Neos.Neos:Sites"        |

    When the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId            | parentNodeAggregateId      | nodeTypeName       | initialPropertyValues                |
      | sir-david-nodenborough     | lady-eleonode-rootford     | Neos.Neos:Site     | {}                                   |
      | nody-mc-nodeface           | sir-david-nodenborough     | Neos.Neos:Document | {"uriPathSegment": "document"}       |
      | nodimus-mediocre           | nody-mc-nodeface           | Neos.Neos:Document | {"uriPathSegment": "child-document"} |
      | sir-nodeward-nodington-iii | sir-david-nodenborough     | Neos.Neos:Document | {"uriPathSegment": "other-document"} |
      | sir-nodeward-nodington-iv  | sir-nodeward-nodington-iii | Neos.Neos:Document | {"uriPathSegment": "document"}       |

  Scenario: Copying a document node as a sibling adjusts its uri path segment
    When copy nodes recursively is executed with payload:
      | Key                         | Value                                                                                          |
      | sourceDimensionSpacePoint   | {}                                                                                             |
      | sourceNodeAggregateId       | "nody-mc-nodeface"                                                                             |
      | targetDimensionSpacePoint   | {}                                                                                             |
      | targetParentNodeAggregateId | "sir-david-nodenborough"                                                                       |
      | nodeAggregateIdMapping      | {"nody-mc-nodeface": "nody-mc-nodeface-copied", "nodimus-mediocre": "nodimus-mediocre-copied"} |

    Then I expect node aggregate identifier "nody-mc-nodeface-copied" to lead to node cs-identifier;nody-mc-nodeface-copied;{}
    And I expect this node to have the following properties:
      | Key            | Value      |
      | uriPathSegment | document-1 |

    And I expect node aggregate identifier "nodimus-mediocre-copied" to lead to node cs-identifier;nodimus-mediocre-copied;{}
    And I expect this node to have the following properties:
      | Key            | Value          |
      | uriPathSegment | child-document |

    When copy nodes recursively is executed with payload:
      | Key                         | Value                                                                                              |
      | sourceDimensionSpacePoint   | {}                                                                                                 |
      | sourceNodeAggregateId       | "nody-mc-nodeface"                                                                                 |
      | targetDimensionSpacePoint   | {}                                                                                                 |
      | targetParentNodeAggregateId | "sir-david-nodenborough"                                                                           |
      | nodeAggregateIdMapping      | {"nody-mc-nodeface": "nody-mc-nodeface-copied-2", "nodimus-mediocre": "nodimus-mediocre-copied-2"} |

    Then I expect node aggregate identifier "nody-mc-nodeface-copied-2" to lead to node cs-identifier;nody-mc-nodeface-copied-2;{}
    And I expect this node to have the following properties:
      | Key            | Value      |
      | uriPathSegment | document-2 |

    And I expect node aggregate identifier "nodimus-mediocre-copied-2" to lead to node cs-identifier;nodimus-mediocre-copied-2;{}
    And I expect this node to have the following properties:
      | Key            | Value          |
      | uriPathSegment | child-document |

  Scenario: Copying a document node to another adjusts its uri path segment
    When copy nodes recursively is executed with payload:
      | Key                         | Value                                                                                          |
      | sourceDimensionSpacePoint   | {}                                                                                             |
      | sourceNodeAggregateId       | "nody-mc-nodeface"                                                                             |
      | targetDimensionSpacePoint   | {}                                                                                             |
      | targetParentNodeAggregateId | "sir-nodeward-nodington-iii"                                                                   |
      | nodeAggregateIdMapping      | {"nody-mc-nodeface": "nody-mc-nodeface-copied", "nodimus-mediocre": "nodimus-mediocre-copied"} |

    Then I expect node aggregate identifier "nody-mc-nodeface-copied" to lead to node cs-identifier;nody-mc-nodeface-copied;{}
    And I expect this node to have the following properties:
      | Key            | Value      |
      | uriPathSegment | document-1 |

    And I expect node aggregate identifier "nodimus-mediocre-copied" to lead to node cs-identifier;nodimus-mediocre-copied;{}
    And I expect this node to have the following properties:
      | Key            | Value          |
      | uriPathSegment | child-document |

    When copy nodes recursively is executed with payload:
      | Key                         | Value                                                                                              |
      | sourceDimensionSpacePoint   | {}                                                                                                 |
      | sourceNodeAggregateId       | "nody-mc-nodeface"                                                                                 |
      | targetDimensionSpacePoint   | {}                                                                                                 |
      | targetParentNodeAggregateId | "sir-nodeward-nodington-iii"                                                                       |
      | nodeAggregateIdMapping      | {"nody-mc-nodeface": "nody-mc-nodeface-copied-2", "nodimus-mediocre": "nodimus-mediocre-copied-2"} |

    Then I expect node aggregate identifier "nody-mc-nodeface-copied-2" to lead to node cs-identifier;nody-mc-nodeface-copied-2;{}
    And I expect this node to have the following properties:
      | Key            | Value      |
      | uriPathSegment | document-2 |

    And I expect node aggregate identifier "nodimus-mediocre-copied-2" to lead to node cs-identifier;nodimus-mediocre-copied-2;{}
    And I expect this node to have the following properties:
      | Key            | Value          |
      | uriPathSegment | child-document |
