@contentrepository @adapters=DoctrineDBAL
Feature: Change node aggregate type - behavior of DELETE strategy

  As a user of the CR I want to change the type of a node aggregate.

  Background:
    Given using the following content dimensions:
      | Identifier | Values  | Generalizations |
      | language   | de, gsw | gsw->de         |
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Tethered': []
    'Neos.ContentRepository.Testing:NodeTypeCCollection':
      childNodes:
        tethered:
          type: 'Neos.ContentRepository.Testing:Tethered'
      constraints:
        nodeTypes:
          '*': TRUE
          'Neos.ContentRepository.Testing:NodeTypeA': FALSE
          'Neos.ContentRepository.Testing:NodeTypeB': FALSE
    'Neos.ContentRepository.Testing:ParentNodeType':
      childNodes:
        tethered:
          type: 'Neos.ContentRepository.Testing:Tethered'
          constraints:
            nodeTypes:
              '*': TRUE
              'Neos.ContentRepository.Testing:NodeTypeB': FALSE
    'Neos.ContentRepository.Testing:ParentNodeTypeB':
      childNodes:
        tethered:
          type: 'Neos.ContentRepository.Testing:Tethered'
          constraints:
            nodeTypes:
              '*': TRUE
              'Neos.ContentRepository.Testing:NodeTypeA': FALSE
      constraints:
        nodeTypes:
          '*': TRUE
          'Neos.ContentRepository.Testing:NodeTypeA': FALSE
    'Neos.ContentRepository.Testing:ParentNodeTypeC':
      childNodes:
        tethered:
          type: 'Neos.ContentRepository.Testing:NodeTypeCCollection'
      constraints:
        nodeTypes:
          '*': TRUE
          'Neos.ContentRepository.Testing:NodeTypeA': FALSE
      properties:
        'parentCText':
          defaultValue: 'parentCTextDefault'
    'Neos.ContentRepository.Testing:GrandParentNodeTypeA':
      childNodes:
        tethered:
          type: 'Neos.ContentRepository.Testing:ParentNodeType'
    'Neos.ContentRepository.Testing:GrandParentNodeTypeB':
      childNodes:
        tethered:
          type: 'Neos.ContentRepository.Testing:ParentNodeTypeB'
    'Neos.ContentRepository.Testing:GrandParentNodeTypeC':
      childNodes:
        tethered:
          type: 'Neos.ContentRepository.Testing:ParentNodeTypeC'
    'Neos.ContentRepository.Testing:ChildOfNodeTypeA': []
    'Neos.ContentRepository.Testing:ChildOfNodeTypeB': []
    'Neos.ContentRepository.Testing:NodeTypeA':
      childNodes:
        child-of-type-a:
          type: 'Neos.ContentRepository.Testing:ChildOfNodeTypeA'
      properties:
        text:
          type: string
          defaultValue: 'text'
    'Neos.ContentRepository.Testing:NodeTypeB':
      childNodes:
        # !! NodeTypeB has BOTH childOfTypeA AND childOfTypeB as tethered child nodes...
        child-of-type-b:
          type: 'Neos.ContentRepository.Testing:ChildOfNodeTypeB'
        child-of-type-a:
          type: 'Neos.ContentRepository.Testing:ChildOfNodeTypeA'
      properties:
        otherText:
          type: string
          defaultValue: 'otherText'
      constraints:
        nodeTypes:
          # both of these types are forbidden.
          'Neos.ContentRepository.Testing:ChildOfNodeTypeA': false
          'Neos.ContentRepository.Testing:ChildOfNodeTypeB': false
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And I am in workspace "live" and dimension space point {"language":"de"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId        | originDimensionSpacePoint | parentNodeAggregateId  | nodeName | nodeTypeName                                  | tetheredDescendantNodeAggregateIds    |
      | sir-david-nodenborough | {"language":"de"}         | lady-eleonode-rootford | parent   | Neos.ContentRepository.Testing:ParentNodeType | {"tethered": "tethered-nodenborough"} |

  Scenario: Change to a node type that disallows already present children with the delete conflict resolution strategy
    When the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId  | originDimensionSpacePoint | parentNodeAggregateId  | nodeName | nodeTypeName                             |
      | nody-mc-nodeface | {"language":"de"}         | sir-david-nodenborough | parent   | Neos.ContentRepository.Testing:NodeTypeA |

    When the command ChangeNodeAggregateType is executed with payload:
      | Key             | Value                                            |
      | nodeAggregateId | "sir-david-nodenborough"                         |
      | newNodeTypeName | "Neos.ContentRepository.Testing:ParentNodeTypeB" |
      | strategy        | "delete"                                         |

    # the type has changed
    When I am in workspace "live" and dimension space point {"language":"de"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"language":"de"}
    And I expect this node to be of type "Neos.ContentRepository.Testing:ParentNodeTypeB"

    # the child nodes have been removed
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to no node
    When I am in workspace "live" and dimension space point {"language":"gsw"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to no node

  Scenario: Change to a node type that disallows already present grandchildren with the delete conflict resolution strategy
    When the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId  | originDimensionSpacePoint | parentNodeAggregateId  | nodeName | nodeTypeName                                  | tetheredDescendantNodeAggregateIds |
      | parent2-na       | {"language":"de"}         | lady-eleonode-rootford | parent2  | Neos.ContentRepository.Testing:ParentNodeType | {"tethered": "tethered-child"}     |
      | nody-mc-nodeface | {"language":"de"}         | tethered-child         | null     | Neos.ContentRepository.Testing:NodeTypeA      | {}                                 |

    When the command ChangeNodeAggregateType is executed with payload:
      | Key             | Value                                            |
      | nodeAggregateId | "parent2-na"                                     |
      | newNodeTypeName | "Neos.ContentRepository.Testing:ParentNodeTypeB" |
      | strategy        | "delete"                                         |

    # the type has changed
    When I am in workspace "live" and dimension space point {"language":"de"}
    Then I expect node aggregate identifier "parent2-na" to lead to node cs-identifier;parent2-na;{"language":"de"}
    And I expect this node to be of type "Neos.ContentRepository.Testing:ParentNodeTypeB"

    # the child nodes still exist
    When I am in workspace "live" and dimension space point {"language":"de"}
    Then I expect node aggregate identifier "tethered-child" to lead to node cs-identifier;tethered-child;{"language":"de"}
    When I am in workspace "live" and dimension space point {"language":"gsw"}
    Then I expect node aggregate identifier "tethered-child" to lead to node cs-identifier;tethered-child;{"language":"de"}

    # the grandchild nodes have been removed
    When I am in workspace "live" and dimension space point {"language":"de"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to no node
    When I am in workspace "live" and dimension space point {"language":"gsw"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to no node

  Scenario: Change to a node type with a differently typed tethered child that disallows already present (grand)children with the DELETE conflict resolution strategy
    When the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId  | originDimensionSpacePoint | parentNodeAggregateId  | nodeName | nodeTypeName                                  | initialPropertyValues | tetheredDescendantNodeAggregateIds |
      | nody-mc-nodeface | {"language":"de"}         | lady-eleonode-rootford | parent2  | Neos.ContentRepository.Testing:ParentNodeType | {}                    | {"tethered": "nodewyn-tetherton"}  |
      | nodimus-prime    | {"language":"de"}         | nodewyn-tetherton      | null     | Neos.ContentRepository.Testing:NodeTypeA      | {}                    | {}                                 |

    When the command ChangeNodeAggregateType is executed with payload:
      | Key             | Value                                            |
      | nodeAggregateId | "nody-mc-nodeface"                               |
      | newNodeTypeName | "Neos.ContentRepository.Testing:ParentNodeTypeC" |
      | strategy        | "delete"                                         |

    # the type has changed
    When I am in workspace "live" and dimension space point {"language":"de"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"language":"de"}
    And I expect this node to be of type "Neos.ContentRepository.Testing:ParentNodeTypeC"

    When I am in workspace "live" and dimension space point {"language":"gsw"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"language":"de"}
    And I expect this node to be of type "Neos.ContentRepository.Testing:ParentNodeTypeC"

    # the tethered child nodes still exist and are now properly typed
    When I am in workspace "live" and dimension space point {"language":"de"}
    Then I expect node aggregate identifier "nodewyn-tetherton" to lead to node cs-identifier;nodewyn-tetherton;{"language":"de"}
    And I expect this node to be of type "Neos.ContentRepository.Testing:NodeTypeCCollection"
    When I am in workspace "live" and dimension space point {"language":"gsw"}
    Then I expect node aggregate identifier "nodewyn-tetherton" to lead to node cs-identifier;nodewyn-tetherton;{"language":"de"}
    And I expect this node to be of type "Neos.ContentRepository.Testing:NodeTypeCCollection"

    # the now disallowed grandchild nodes have been removed
    When I am in workspace "live" and dimension space point {"language":"de"}
    Then I expect node aggregate identifier "nodimus-prime" to lead to no node
    When I am in workspace "live" and dimension space point {"language":"gsw"}
    Then I expect node aggregate identifier "nodimus-prime" to lead to no node

  Scenario: Change to a node type whose tethered child that is also type-changed disallows already present children with the DELETE conflict resolution strategy (recursive case)
    When the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId  | originDimensionSpacePoint | parentNodeAggregateId  | nodeTypeName                                        | tetheredDescendantNodeAggregateIds                                          |
      | nody-mc-nodeface | {"language":"de"}         | lady-eleonode-rootford | Neos.ContentRepository.Testing:GrandParentNodeTypeA | {"tethered": "nodewyn-tetherton", "tethered/tethered": "nodimer-tetherton"} |
      | nodingers-cat    | {"language": "de"}        | nodewyn-tetherton      | Neos.ContentRepository.Testing:NodeTypeA            | {}                                                                          |

    When the command ChangeNodeAggregateType is executed with payload:
      | Key             | Value                                                 |
      | nodeAggregateId | "nody-mc-nodeface"                                    |
      | newNodeTypeName | "Neos.ContentRepository.Testing:GrandParentNodeTypeB" |
      | strategy        | "delete"                                              |

    # the type has changed
    When I am in workspace "live" and dimension space point {"language":"de"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"language":"de"}
    And I expect this node to be of type "Neos.ContentRepository.Testing:GrandParentNodeTypeB"

    When I am in workspace "live" and dimension space point {"language":"gsw"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"language":"de"}
    And I expect this node to be of type "Neos.ContentRepository.Testing:GrandParentNodeTypeB"

    # the tethered child nodes still exist and are now properly typed
    When I am in workspace "live" and dimension space point {"language":"de"}
    Then I expect node aggregate identifier "nodewyn-tetherton" to lead to node cs-identifier;nodewyn-tetherton;{"language":"de"}
    And I expect this node to be of type "Neos.ContentRepository.Testing:ParentNodeTypeB"
    When I am in workspace "live" and dimension space point {"language":"gsw"}
    Then I expect node aggregate identifier "nodewyn-tetherton" to lead to node cs-identifier;nodewyn-tetherton;{"language":"de"}
    And I expect this node to be of type "Neos.ContentRepository.Testing:ParentNodeTypeB"

    # the now disallowed grandchild nodes have been removed
    When I am in workspace "live" and dimension space point {"language":"de"}
    Then I expect node aggregate identifier "nodingers-cat" to lead to no node
    When I am in workspace "live" and dimension space point {"language":"gsw"}
    Then I expect node aggregate identifier "nodingers-cat" to lead to no node

  Scenario: Change to a node type whose tethered child that is also type-changed disallows already present (grand)children with the DELETE conflict resolution strategy (recursive case)
    When the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId  | originDimensionSpacePoint | parentNodeAggregateId  | nodeTypeName                                        | tetheredDescendantNodeAggregateIds                                          |
      | nody-mc-nodeface | {"language":"de"}         | lady-eleonode-rootford | Neos.ContentRepository.Testing:GrandParentNodeTypeA | {"tethered": "nodewyn-tetherton", "tethered/tethered": "nodimer-tetherton"} |
      | nodingers-cat    | {"language": "de"}        | nodimer-tetherton      | Neos.ContentRepository.Testing:NodeTypeA            | {"child-of-type-a": "a-tetherton"}                                          |

    When the command ChangeNodeAggregateType is executed with payload:
      | Key             | Value                                                 |
      | nodeAggregateId | "nody-mc-nodeface"                                    |
      | newNodeTypeName | "Neos.ContentRepository.Testing:GrandParentNodeTypeB" |
      | strategy        | "delete"                                              |

    # the type has changed
    When I am in workspace "live" and dimension space point {"language":"de"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"language":"de"}
    And I expect this node to be of type "Neos.ContentRepository.Testing:GrandParentNodeTypeB"

    When I am in workspace "live" and dimension space point {"language":"gsw"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"language":"de"}
    And I expect this node to be of type "Neos.ContentRepository.Testing:GrandParentNodeTypeB"

    # the tethered child nodes still exist and are now properly typed
    When I am in workspace "live" and dimension space point {"language":"de"}
    Then I expect node aggregate identifier "nodewyn-tetherton" to lead to node cs-identifier;nodewyn-tetherton;{"language":"de"}
    And I expect this node to be of type "Neos.ContentRepository.Testing:ParentNodeTypeB"
    When I am in workspace "live" and dimension space point {"language":"gsw"}
    Then I expect node aggregate identifier "nodewyn-tetherton" to lead to node cs-identifier;nodewyn-tetherton;{"language":"de"}
    And I expect this node to be of type "Neos.ContentRepository.Testing:ParentNodeTypeB"

    # the now disallowed grandchild nodes and their descendants have been removed
    When I am in workspace "live" and dimension space point {"language":"de"}
    Then I expect node aggregate identifier "nodingers-cat" to lead to no node
    When I am in workspace "live" and dimension space point {"language":"gsw"}
    Then I expect node aggregate identifier "nodingers-cat" to lead to no node

    When I am in workspace "live" and dimension space point {"language":"de"}
    Then I expect node aggregate identifier "a-tetherton" to lead to no node
    When I am in workspace "live" and dimension space point {"language":"gsw"}
    Then I expect node aggregate identifier "a-tetherton" to lead to no node

  Scenario: Change to a node type whose tethered child that is also type-changed has a differently typed tethered child that disallows already present grandchildren with the DELETE conflict resolution strategy (recursive case)
    When the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId  | originDimensionSpacePoint | parentNodeAggregateId  | nodeTypeName                                        | tetheredDescendantNodeAggregateIds                                                     |
      | nody-mc-nodeface | {"language":"de"}         | lady-eleonode-rootford | Neos.ContentRepository.Testing:GrandParentNodeTypeB | {"tethered": "nodewyn-tetherton", "tethered/tethered": "nodimer-tetherton"}            |
      | nodingers-cat    | {"language": "de"}        | nodimer-tetherton      | Neos.ContentRepository.Testing:NodeTypeB            | {"child-of-type-a": "nodingers-tethered-a", "child-of-type-b": "nodingers-tethered-b"} |

    When the command ChangeNodeAggregateType is executed with payload:
      | Key                                | Value                                                 |
      | nodeAggregateId                    | "nody-mc-nodeface"                                    |
      | newNodeTypeName                    | "Neos.ContentRepository.Testing:GrandParentNodeTypeC" |
      | strategy                           | "delete"                                              |
      | tetheredDescendantNodeAggregateIds | {"tethered/tethered/tethered": "nodimus-tetherton"}   |

    Then I expect exactly 16 events to be published on stream "ContentStream:cs-identifier"
    And event at index 10 is of type "NodeAggregateTypeWasChanged" with payload:
      | Key             | Expected                                              |
      | workspaceName   | "live"                                                |
      | contentStreamId | "cs-identifier"                                       |
      | nodeAggregateId | "nody-mc-nodeface"                                    |
      | newNodeTypeName | "Neos.ContentRepository.Testing:GrandParentNodeTypeC" |
    And event at index 11 is of type "NodeAggregateTypeWasChanged" with payload:
      | Key             | Expected                                         |
      | workspaceName   | "live"                                           |
      | contentStreamId | "cs-identifier"                                  |
      | nodeAggregateId | "nodewyn-tetherton"                              |
      | newNodeTypeName | "Neos.ContentRepository.Testing:ParentNodeTypeC" |
    And event at index 12 is of type "NodePropertiesWereSet" with payload:
      | Key                          | Expected                                                       |
      | workspaceName                | "live"                                                         |
      | contentStreamId              | "cs-identifier"                                                |
      | nodeAggregateId              | "nodewyn-tetherton"                                            |
      | originDimensionSpacePoint    | {"language":"de"}                                              |
      | affectedDimensionSpacePoints | [{"language":"de"},{"language":"gsw"}]                         |
      | propertyValues               | {"parentCText":{"value":"parentCTextDefault","type":"string"}} |
      | propertyValues               | {"parentCText":{"value":"parentCTextDefault","type":"string"}} |
      | propertiesToUnset            | []                                                             |
    And event at index 13 is of type "NodeAggregateWasRemoved" with payload:
      | Key                                  | Expected                               |
      | workspaceName                        | "live"                                 |
      | contentStreamId                      | "cs-identifier"                        |
      | nodeAggregateId                      | "nodingers-cat"                        |
      | affectedCoveredDimensionSpacePoints  | [{"language":"de"},{"language":"gsw"}] |
    And event at index 14 is of type "NodeAggregateTypeWasChanged" with payload:
      | Key             | Expected                                             |
      | workspaceName   | "live"                                               |
      | contentStreamId | "cs-identifier"                                      |
      | nodeAggregateId | "nodimer-tetherton"                                  |
      | newNodeTypeName | "Neos.ContentRepository.Testing:NodeTypeCCollection" |
    And event at index 15 is of type "NodeAggregateWithNodeWasCreated" with payload:
      | Key                           | Expected                                                                                                                             |
      | workspaceName                 | "live"                                                                                                                               |
      | contentStreamId               | "cs-identifier"                                                                                                                      |
      | nodeAggregateId               | "nodimus-tetherton"                                                                                                                  |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Tethered"                                                                                            |
      | originDimensionSpacePoint     | {"language":"de"}                                                                                                                    |
      | succeedingSiblingsForCoverage | [{"dimensionSpacePoint":{"language":"de"},"nodeAggregateId":null},{"dimensionSpacePoint":{"language":"gsw"},"nodeAggregateId":null}] |
      | parentNodeAggregateId         | "nodimer-tetherton"                                                                                                                  |
      | nodeName                      | "tethered"                                                                                                                           |
      | initialPropertyValues         | []                                                                                                                                   |
      | nodeAggregateClassification   | "tethered"                                                                                                                           |

    # the type has changed
    When I am in workspace "live" and dimension space point {"language":"de"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"language":"de"}
    And I expect this node to be of type "Neos.ContentRepository.Testing:GrandParentNodeTypeC"

    When I am in workspace "live" and dimension space point {"language":"gsw"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"language":"de"}
    And I expect this node to be of type "Neos.ContentRepository.Testing:GrandParentNodeTypeC"

    # the tethered child nodes still exist and are now properly typed
    When I am in workspace "live" and dimension space point {"language":"de"}
    Then I expect node aggregate identifier "nodewyn-tetherton" to lead to node cs-identifier;nodewyn-tetherton;{"language":"de"}
    And I expect this node to be of type "Neos.ContentRepository.Testing:ParentNodeTypeC"
    When I am in workspace "live" and dimension space point {"language":"gsw"}
    Then I expect node aggregate identifier "nodewyn-tetherton" to lead to node cs-identifier;nodewyn-tetherton;{"language":"de"}
    And I expect this node to be of type "Neos.ContentRepository.Testing:ParentNodeTypeC"

    # the tethered grandchild nodes still exist and are now properly typed
    When I am in workspace "live" and dimension space point {"language":"de"}
    Then I expect node aggregate identifier "nodimer-tetherton" to lead to node cs-identifier;nodimer-tetherton;{"language":"de"}
    And I expect this node to be of type "Neos.ContentRepository.Testing:NodeTypeCCollection"
    When I am in workspace "live" and dimension space point {"language":"gsw"}
    Then I expect node aggregate identifier "nodimer-tetherton" to lead to node cs-identifier;nodimer-tetherton;{"language":"de"}
    And I expect this node to be of type "Neos.ContentRepository.Testing:NodeTypeCCollection"

    When I am in workspace "live" and dimension space point {"language":"de"}
    Then I expect node aggregate identifier "nodimus-tetherton" to lead to node cs-identifier;nodimus-tetherton;{"language":"de"}
    And I expect this node to be of type "Neos.ContentRepository.Testing:Tethered"
    When I am in workspace "live" and dimension space point {"language":"gsw"}
    Then I expect node aggregate identifier "nodimus-tetherton" to lead to node cs-identifier;nodimus-tetherton;{"language":"de"}
    And I expect this node to be of type "Neos.ContentRepository.Testing:Tethered"

    # the now disallowed grandchild nodes and their descendants have been removed
    When I am in workspace "live" and dimension space point {"language":"de"}
    Then I expect node aggregate identifier "nodingers-cat" to lead to no node
    When I am in workspace "live" and dimension space point {"language":"gsw"}
    Then I expect node aggregate identifier "nodingers-cat" to lead to no node

    When I am in workspace "live" and dimension space point {"language":"de"}
    Then I expect node aggregate identifier "nodingers-tethered-a" to lead to no node
    When I am in workspace "live" and dimension space point {"language":"gsw"}
    Then I expect node aggregate identifier "nodingers-tethered-a" to lead to no node

    When I am in workspace "live" and dimension space point {"language":"de"}
    Then I expect node aggregate identifier "nodingers-tethered-b" to lead to no node
    When I am in workspace "live" and dimension space point {"language":"gsw"}
    Then I expect node aggregate identifier "nodingers-tethered-b" to lead to no node


  Scenario: Change node type successfully
    When the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId     | originDimensionSpacePoint | parentNodeAggregateId  | nodeName | nodeTypeName                             | tetheredDescendantNodeAggregateIds         |
      | nodea-identifier-de | {"language":"de"}         | lady-eleonode-rootford | null     | Neos.ContentRepository.Testing:NodeTypeA | { "child-of-type-a": "child-of-type-a-id"} |

    When the command CreateNodeVariant is executed with payload:
      | Key             | Value                 |
      | nodeAggregateId | "nodea-identifier-de" |
      | sourceOrigin    | {"language":"de"}     |
      | targetOrigin    | {"language":"gsw"}    |

    When the command ChangeNodeAggregateType is executed with payload:
      | Key                                | Value                                      |
      | nodeAggregateId                    | "nodea-identifier-de"                      |
      | newNodeTypeName                    | "Neos.ContentRepository.Testing:NodeTypeB" |
      | strategy                           | "delete"                                   |
      | tetheredDescendantNodeAggregateIds | { "child-of-type-b": "child-of-type-b-id"} |

    # the type has changed
    When I am in workspace "live" and dimension space point {"language":"de"}
    Then I expect node aggregate identifier "nodea-identifier-de" to lead to node cs-identifier;nodea-identifier-de;{"language":"de"}
    And I expect this node to be of type "Neos.ContentRepository.Testing:NodeTypeB"

    When I am in workspace "live" and dimension space point {"language":"gsw"}
    Then I expect node aggregate identifier "nodea-identifier-de" to lead to node cs-identifier;nodea-identifier-de;{"language":"gsw"}
    And I expect this node to be of type "Neos.ContentRepository.Testing:NodeTypeB"
    And I expect this node to have the following child nodes:
      | Name            | NodeDiscriminator                                   |
      | child-of-type-a | cs-identifier;child-of-type-a-id;{"language":"gsw"} |
      | child-of-type-b | cs-identifier;child-of-type-b-id;{"language":"gsw"} |

  Scenario: When changing node type, a non-allowed tethered node should stay (Tethered nodes are not taken into account when checking constraints)
    When the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId     | originDimensionSpacePoint | parentNodeAggregateId  | nodeName | nodeTypeName                             | tetheredDescendantNodeAggregateIds         |
      | nodea-identifier-de | {"language":"de"}         | lady-eleonode-rootford | null     | Neos.ContentRepository.Testing:NodeTypeA | { "child-of-type-a": "child-of-type-a-id"} |

    When the command CreateNodeVariant is executed with payload:
      | Key             | Value                 |
      | nodeAggregateId | "nodea-identifier-de" |
      | sourceOrigin    | {"language":"de"}     |
      | targetOrigin    | {"language":"gsw"}    |

    When the command ChangeNodeAggregateType is executed with payload:
      | Key                                | Value                                      |
      | nodeAggregateId                    | "nodea-identifier-de"                      |
      | newNodeTypeName                    | "Neos.ContentRepository.Testing:NodeTypeB" |
      | strategy                           | "delete"                                   |
      | tetheredDescendantNodeAggregateIds | { "child-of-type-b": "child-of-type-b-id"} |

    # the type has changed
    When I am in workspace "live" and dimension space point {"language":"de"}
    Then I expect node aggregate identifier "nodea-identifier-de" to lead to node cs-identifier;nodea-identifier-de;{"language":"de"}
    And I expect this node to be of type "Neos.ContentRepository.Testing:NodeTypeB"

    # BOTH tethered child nodes still need to exist
    And I expect this node to have the following child nodes:
      | Name            | NodeDiscriminator                                  |
      | child-of-type-a | cs-identifier;child-of-type-a-id;{"language":"de"} |
      | child-of-type-b | cs-identifier;child-of-type-b-id;{"language":"de"} |
