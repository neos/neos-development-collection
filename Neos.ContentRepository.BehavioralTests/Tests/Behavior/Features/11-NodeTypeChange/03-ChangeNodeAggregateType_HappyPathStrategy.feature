@contentrepository @adapters=DoctrineDBAL
Feature: Change node aggregate type - behavior of HAPPYPATH strategy

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
    'Neos.ContentRepository.Testing:ChildOfNodeTypeA':
      properties:
        defaultTextA:
          type: string
          defaultValue: 'defaultTextA'
        commonDefaultText:
          type: string
          defaultValue: 'commonDefaultTextA'
    'Neos.ContentRepository.Testing:ChildOfNodeTypeB':
      properties:
        defaultTextB:
          type: string
          defaultValue: 'defaultTextB'
        commonDefaultText:
          type: string
          defaultValue: 'commonDefaultTextB'
    'Neos.ContentRepository.Testing:NodeTypeA':
      childNodes:
        child-of-type-a:
          type: 'Neos.ContentRepository.Testing:ChildOfNodeTypeA'
      properties:
        defaultTextA:
          type: string
          defaultValue: 'defaultTextA'
        commonDefaultText:
          type: string
          defaultValue: 'commonDefaultTextA'
    'Neos.ContentRepository.Testing:NodeTypeB':
      childNodes:
        child-of-type-b:
          type: 'Neos.ContentRepository.Testing:ChildOfNodeTypeB'
      properties:
        defaultTextB:
          type: string
          defaultValue: 'defaultTextB'
        commonDefaultText:
          type: string
          defaultValue: 'commonDefaultTextB'
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
      | nodeAggregateId        | originDimensionSpacePoint | parentNodeAggregateId  | nodeName | nodeTypeName                                  |
      | sir-david-nodenborough | {"language":"de"}         | lady-eleonode-rootford | parent   | Neos.ContentRepository.Testing:ParentNodeType |

  Scenario: Try to change to a node type that disallows already present children with the HAPPYPATH conflict resolution strategy
    When the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId  | originDimensionSpacePoint | parentNodeAggregateId  | nodeName | nodeTypeName                             |
      | nody-mc-nodeface | {"language":"de"}         | sir-david-nodenborough | null     | Neos.ContentRepository.Testing:NodeTypeA |

    When the command ChangeNodeAggregateType is executed with payload and exceptions are caught:
      | Key             | Value                                            |
      | nodeAggregateId | "sir-david-nodenborough"                         |
      | newNodeTypeName | "Neos.ContentRepository.Testing:ParentNodeTypeB" |
      | strategy        | "happypath"                                      |
    Then the last command should have thrown an exception of type "NodeConstraintException"

  Scenario: Try to change to a node type that disallows already present grandchildren with the HAPPYPATH conflict resolution strategy
    When the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId  | originDimensionSpacePoint | parentNodeAggregateId  | nodeName | nodeTypeName                                  | initialPropertyValues | tetheredDescendantNodeAggregateIds |
      | parent2-na       | {"language":"de"}         | lady-eleonode-rootford | parent2  | Neos.ContentRepository.Testing:ParentNodeType | {}                    | {"tethered": "nodewyn-tetherton"}  |
      | nody-mc-nodeface | {"language":"de"}         | nodewyn-tetherton      | null     | Neos.ContentRepository.Testing:NodeTypeA      | {}                    | {}                                 |

    When the command ChangeNodeAggregateType is executed with payload and exceptions are caught:
      | Key             | Value                                            |
      | nodeAggregateId | "parent2-na"                                     |
      | newNodeTypeName | "Neos.ContentRepository.Testing:ParentNodeTypeB" |
      | strategy        | "happypath"                                      |
    Then the last command should have thrown an exception of type "NodeConstraintException"

  Scenario: Try to change to a node type with a differently typed tethered child that disallows already present (grand)children with the HAPPYPATH conflict resolution strategy
    When the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId  | originDimensionSpacePoint | parentNodeAggregateId  | nodeName | nodeTypeName                                  | initialPropertyValues | tetheredDescendantNodeAggregateIds |
      | nody-mc-nodeface | {"language":"de"}         | lady-eleonode-rootford | parent2  | Neos.ContentRepository.Testing:ParentNodeType | {}                    | {"tethered": "nodewyn-tetherton"}  |
      | nodimus-prime    | {"language":"de"}         | nodewyn-tetherton      | null     | Neos.ContentRepository.Testing:NodeTypeA      | {}                    | {}                                 |

    When the command ChangeNodeAggregateType is executed with payload and exceptions are caught:
      | Key             | Value                                            |
      | nodeAggregateId | "nody-mc-nodeface"                               |
      | newNodeTypeName | "Neos.ContentRepository.Testing:ParentNodeTypeC" |
      | strategy        | "happypath"                                      |
    Then the last command should have thrown an exception of type "NodeConstraintException"

  Scenario: Try to change to a node type whose tethered child that is also type-changed disallows already present children with the HAPPYPATH conflict resolution strategy (recursive case)
    When the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId  | originDimensionSpacePoint | parentNodeAggregateId  | nodeTypeName                                        | tetheredDescendantNodeAggregateIds                                          |
      | nody-mc-nodeface | {"language":"de"}         | lady-eleonode-rootford | Neos.ContentRepository.Testing:GrandParentNodeTypeA | {"tethered": "nodewyn-tetherton", "tethered/tethered": "nodimer-tetherton"} |
      | nodingers-cat    | {"language": "de"}        | nodewyn-tetherton      | Neos.ContentRepository.Testing:NodeTypeA            | {}                                                                          |

    When the command ChangeNodeAggregateType is executed with payload and exceptions are caught:
      | Key             | Value                                                 |
      | nodeAggregateId | "nody-mc-nodeface"                                    |
      | newNodeTypeName | "Neos.ContentRepository.Testing:GrandParentNodeTypeB" |
      | strategy        | "happypath"                                           |
    Then the last command should have thrown an exception of type "NodeConstraintException"

  Scenario: Try to change to a node type whose tethered child that is also type-changed disallows already present (grand)children with the HAPPYPATH conflict resolution strategy (recursive case)
    When the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId  | originDimensionSpacePoint | parentNodeAggregateId  | nodeTypeName                                        | tetheredDescendantNodeAggregateIds                                          |
      | nody-mc-nodeface | {"language":"de"}         | lady-eleonode-rootford | Neos.ContentRepository.Testing:GrandParentNodeTypeA | {"tethered": "nodewyn-tetherton", "tethered/tethered": "nodimer-tetherton"} |
      | nodingers-cat    | {"language": "de"}        | nodimer-tetherton      | Neos.ContentRepository.Testing:NodeTypeA            | {}                                                                          |

    When the command ChangeNodeAggregateType is executed with payload and exceptions are caught:
      | Key             | Value                                                 |
      | nodeAggregateId | "nody-mc-nodeface"                                    |
      | newNodeTypeName | "Neos.ContentRepository.Testing:GrandParentNodeTypeB" |
      | strategy        | "happypath"                                           |
    Then the last command should have thrown an exception of type "NodeConstraintException"

  Scenario: Try to change to a node type whose tethered child that is also type-changed has a differently typed tethered child that disallows already present grandchildren with the HAPPYPATH conflict resolution strategy (recursive case)
    When the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId  | originDimensionSpacePoint | parentNodeAggregateId  | nodeTypeName                                        | tetheredDescendantNodeAggregateIds                                          |
      | nody-mc-nodeface | {"language":"de"}         | lady-eleonode-rootford | Neos.ContentRepository.Testing:GrandParentNodeTypeB | {"tethered": "nodewyn-tetherton", "tethered/tethered": "nodimer-tetherton"} |
      | nodingers-cat    | {"language": "de"}        | nodimer-tetherton      | Neos.ContentRepository.Testing:NodeTypeB            | {}                                                                          |

    When the command ChangeNodeAggregateType is executed with payload and exceptions are caught:
      | Key             | Value                                                 |
      | nodeAggregateId | "nody-mc-nodeface"                                    |
      | newNodeTypeName | "Neos.ContentRepository.Testing:GrandParentNodeTypeC" |
      | strategy        | "happypath"                                           |
    Then the last command should have thrown an exception of type "NodeConstraintException"


  Scenario: Change node type with tethered children
    When the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId  | originDimensionSpacePoint | parentNodeAggregateId  | nodeTypeName                             | initialPropertyValues | tetheredDescendantNodeAggregateIds        |
      | nody-mc-nodeface | {"language":"de"}         | lady-eleonode-rootford | Neos.ContentRepository.Testing:NodeTypeA | {}                    | { "child-of-type-a": "nodewyn-tetherton"} |

    When the command CreateNodeVariant is executed with payload:
      | Key             | Value              |
      | nodeAggregateId | "nody-mc-nodeface" |
      | sourceOrigin    | {"language":"de"}  |
      | targetOrigin    | {"language":"gsw"} |

    When the command ChangeNodeAggregateType is executed with payload:
      | Key                                | Value                                      |
      | nodeAggregateId                    | "nody-mc-nodeface"                         |
      | newNodeTypeName                    | "Neos.ContentRepository.Testing:NodeTypeB" |
      | strategy                           | "happypath"                                |
      | tetheredDescendantNodeAggregateIds | { "child-of-type-b": "nodimer-tetherton"}  |

    Then I expect exactly 13 events to be published on stream "ContentStream:cs-identifier"
    And event at index 8 is of type "NodeAggregateTypeWasChanged" with payload:
      | Key             | Expected                                   |
      | workspaceName   | "live"                                     |
      | contentStreamId | "cs-identifier"                            |
      | nodeAggregateId | "nody-mc-nodeface"                         |
      | newNodeTypeName | "Neos.ContentRepository.Testing:NodeTypeB" |
    And event at index 9 is of type "NodePropertiesWereSet" with payload:
      | Key                          | Expected                                                  |
      | workspaceName                | "live"                                                    |
      | contentStreamId              | "cs-identifier"                                           |
      | nodeAggregateId              | "nody-mc-nodeface"                                        |
      | originDimensionSpacePoint    | {"language":"gsw"}                                        |
      | affectedDimensionSpacePoints | [{"language":"gsw"}]                                      |
      | propertyValues               | {"defaultTextB":{"value":"defaultTextB","type":"string"}} |
      | propertiesToUnset            | ["defaultTextA"]                                          |
    And event at index 10 is of type "NodePropertiesWereSet" with payload:
      | Key                          | Expected                                                  |
      | workspaceName                | "live"                                                    |
      | contentStreamId              | "cs-identifier"                                           |
      | nodeAggregateId              | "nody-mc-nodeface"                                        |
      | originDimensionSpacePoint    | {"language":"de"}                                         |
      | affectedDimensionSpacePoints | [{"language":"de"}]                                       |
      | propertyValues               | {"defaultTextB":{"value":"defaultTextB","type":"string"}} |
      | propertiesToUnset            | ["defaultTextA"]                                          |
    And event at index 11 is of type "NodeAggregateWithNodeWasCreated" with payload:
      | Key                           | Expected                                                                                                                     |
      | workspaceName                 | "live"                                                                                                                       |
      | contentStreamId               | "cs-identifier"                                                                                                              |
      | nodeAggregateId               | "nodimer-tetherton"                                                                                                          |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:ChildOfNodeTypeB"                                                                            |
      | originDimensionSpacePoint     | {"language":"gsw"}                                                                                                           |
      | succeedingSiblingsForCoverage | [{"dimensionSpacePoint":{"language":"gsw"},"nodeAggregateId":null}]                                                          |
      | parentNodeAggregateId         | "nody-mc-nodeface"                                                                                                           |
      | nodeName                      | "child-of-type-b"                                                                                                            |
      | initialPropertyValues         | {"defaultTextB":{"value":"defaultTextB","type":"string"},"commonDefaultText":{"value":"commonDefaultTextB","type":"string"}} |
      | nodeAggregateClassification   | "tethered"                                                                                                                   |
    And event at index 12 is of type "NodeGeneralizationVariantWasCreated" with payload:
      | Key                       | Expected                                                           |
      | workspaceName             | "live"                                                             |
      | contentStreamId           | "cs-identifier"                                                    |
      | nodeAggregateId           | "nodimer-tetherton"                                                |
      | sourceOrigin              | {"language":"gsw"}                                                 |
      | generalizationOrigin      | {"language":"de"}                                                  |
      | variantSucceedingSiblings | [{"dimensionSpacePoint":{"language":"de"},"nodeAggregateId":null}] |

    # the type has changed
    When I am in workspace "live" and dimension space point {"language":"de"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"language":"de"}
    And I expect this node to be of type "Neos.ContentRepository.Testing:NodeTypeB"
    And I expect this node to have the following properties:
      | Key               | Value                |
      # Not modified because it was already present
      | commonDefaultText | "commonDefaultTextA" |
      | defaultTextB      | "defaultTextB"       |
      # defaultTextA missing because it does not exist in NodeTypeB

    And I expect this node to have the following child nodes:
      | Name            | NodeDiscriminator                                 |
      # the tethered child of the old node type has not been removed with this strategy.
      | child-of-type-a | cs-identifier;nodewyn-tetherton;{"language":"de"} |
      | child-of-type-b | cs-identifier;nodimer-tetherton;{"language":"de"} |

    And I expect node aggregate identifier "nodewyn-tetherton" to lead to node cs-identifier;nodewyn-tetherton;{"language":"de"}
    And I expect this node to have the following properties:
      | Key               | Value                |
      | commonDefaultText | "commonDefaultTextA" |
      | defaultTextA      | "defaultTextA"       |

    And I expect node aggregate identifier "nodimer-tetherton" to lead to node cs-identifier;nodimer-tetherton;{"language":"de"}
    And I expect this node to have the following properties:
      | Key               | Value                |
      | commonDefaultText | "commonDefaultTextB" |
      | defaultTextB      | "defaultTextB"       |

    When I am in workspace "live" and dimension space point {"language":"gsw"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"language":"gsw"}
    And I expect this node to be of type "Neos.ContentRepository.Testing:NodeTypeB"

    And I expect this node to have the following child nodes:
      | Name            | NodeDiscriminator                                  |
      # the tethered child of the old node type has not been removed with this strategy.
      | child-of-type-a | cs-identifier;nodewyn-tetherton;{"language":"gsw"} |
      | child-of-type-b | cs-identifier;nodimer-tetherton;{"language":"gsw"} |

    And I expect node aggregate identifier "nodewyn-tetherton" to lead to node cs-identifier;nodewyn-tetherton;{"language":"gsw"}
    And I expect this node to have the following properties:
      | Key               | Value                |
      | commonDefaultText | "commonDefaultTextA" |
      | defaultTextA      | "defaultTextA"       |

    And I expect node aggregate identifier "nodimer-tetherton" to lead to node cs-identifier;nodimer-tetherton;{"language":"gsw"}
    And I expect this node to have the following properties:
      | Key               | Value                |
      | commonDefaultText | "commonDefaultTextB" |
      | defaultTextB      | "defaultTextB"       |

  Scenario: Change node type, recursively also changing the types of tethered descendants
    When the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId  | originDimensionSpacePoint | parentNodeAggregateId  | nodeTypeName                                  | initialPropertyValues | tetheredDescendantNodeAggregateIds |
      | nody-mc-nodeface | {"language":"de"}         | lady-eleonode-rootford | Neos.ContentRepository.Testing:ParentNodeType | {}                    | {"tethered": "nodewyn-tetherton"}  |

    When the command CreateNodeVariant is executed with payload:
      | Key             | Value              |
      | nodeAggregateId | "nody-mc-nodeface" |
      | sourceOrigin    | {"language":"de"}  |
      | targetOrigin    | {"language":"gsw"} |

    When the command ChangeNodeAggregateType is executed with payload:
      | Key                                | Value                                                                                         |
      | nodeAggregateId                    | "nody-mc-nodeface"                                                                            |
      | newNodeTypeName                    | "Neos.ContentRepository.Testing:GrandParentNodeTypeC"                                         |
      | strategy                           | "happypath"                                                                                   |
      | tetheredDescendantNodeAggregateIds | {"tethered/tethered": "nodimer-tetherton", "tethered/tethered/tethered": "nodimus-tetherton"} |

    Then I expect exactly 16 events to be published on stream "ContentStream:cs-identifier"
    And event at index 8 is of type "NodeAggregateTypeWasChanged" with payload:
      | Key             | Expected                                              |
      | workspaceName   | "live"                                                |
      | contentStreamId | "cs-identifier"                                       |
      | nodeAggregateId | "nody-mc-nodeface"                                    |
      | newNodeTypeName | "Neos.ContentRepository.Testing:GrandParentNodeTypeC" |
    And event at index 9 is of type "NodeAggregateTypeWasChanged" with payload:
      | Key             | Expected                                         |
      | workspaceName   | "live"                                           |
      | contentStreamId | "cs-identifier"                                  |
      | nodeAggregateId | "nodewyn-tetherton"                              |
      | newNodeTypeName | "Neos.ContentRepository.Testing:ParentNodeTypeC" |
    And event at index 10 is of type "NodePropertiesWereSet" with payload:
      | Key                          | Expected                                                       |
      | workspaceName                | "live"                                                         |
      | contentStreamId              | "cs-identifier"                                                |
      | nodeAggregateId              | "nodewyn-tetherton"                                            |
      | originDimensionSpacePoint    | {"language":"de"}                                              |
      | affectedDimensionSpacePoints | [{"language":"de"}]                                            |
      | propertyValues               | {"parentCText":{"value":"parentCTextDefault","type":"string"}} |
      | propertiesToUnset            | []                                                             |
    And event at index 11 is of type "NodePropertiesWereSet" with payload:
      | Key                          | Expected                                                       |
      | workspaceName                | "live"                                                         |
      | contentStreamId              | "cs-identifier"                                                |
      | nodeAggregateId              | "nodewyn-tetherton"                                            |
      | originDimensionSpacePoint    | {"language":"gsw"}                                             |
      | affectedDimensionSpacePoints | [{"language":"gsw"}]                                           |
      | propertyValues               | {"parentCText":{"value":"parentCTextDefault","type":"string"}} |
      | propertiesToUnset            | []                                                             |
    And event at index 12 is of type "NodeAggregateWithNodeWasCreated" with payload:
      | Key                           | Expected                                                           |
      | workspaceName                 | "live"                                                             |
      | contentStreamId               | "cs-identifier"                                                    |
      | nodeAggregateId               | "nodimer-tetherton"                                                |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:NodeTypeCCollection"               |
      | originDimensionSpacePoint     | {"language":"de"}                                                  |
      | succeedingSiblingsForCoverage | [{"dimensionSpacePoint":{"language":"de"},"nodeAggregateId":null}] |
      | parentNodeAggregateId         | "nodewyn-tetherton"                                                |
      | nodeName                      | "tethered"                                                         |
      | initialPropertyValues         | []                                                                 |
      | nodeAggregateClassification   | "tethered"                                                         |
    And event at index 13 is of type "NodeSpecializationVariantWasCreated" with payload:
      | Key                    | Expected                                                            |
      | workspaceName          | "live"                                                              |
      | contentStreamId        | "cs-identifier"                                                     |
      | nodeAggregateId        | "nodimer-tetherton"                                                 |
      | sourceOrigin           | {"language":"de"}                                                   |
      | specializationOrigin   | {"language":"gsw"}                                                  |
      | specializationSiblings | [{"dimensionSpacePoint":{"language":"gsw"},"nodeAggregateId":null}] |
    And event at index 14 is of type "NodeAggregateWithNodeWasCreated" with payload:
      | Key                           | Expected                                                           |
      | workspaceName                 | "live"                                                             |
      | contentStreamId               | "cs-identifier"                                                    |
      | nodeAggregateId               | "nodimus-tetherton"                                                |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Tethered"                          |
      | originDimensionSpacePoint     | {"language":"de"}                                                  |
      | succeedingSiblingsForCoverage | [{"dimensionSpacePoint":{"language":"de"},"nodeAggregateId":null}] |
      | parentNodeAggregateId         | "nodimer-tetherton"                                                |
      | nodeName                      | "tethered"                                                         |
      | initialPropertyValues         | []                                                                 |
      | nodeAggregateClassification   | "tethered"                                                         |
    And event at index 15 is of type "NodeSpecializationVariantWasCreated" with payload:
      | Key                    | Expected                                                            |
      | workspaceName          | "live"                                                              |
      | contentStreamId        | "cs-identifier"                                                     |
      | nodeAggregateId        | "nodimus-tetherton"                                                 |
      | sourceOrigin           | {"language":"de"}                                                   |
      | specializationOrigin   | {"language":"gsw"}                                                  |
      | specializationSiblings | [{"dimensionSpacePoint":{"language":"gsw"},"nodeAggregateId":null}] |

    When I am in workspace "live" and dimension space point {"language":"de"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"language":"de"}
    And I expect this node to be of type "Neos.ContentRepository.Testing:GrandParentNodeTypeC"
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                 |
      | tethered | cs-identifier;nodewyn-tetherton;{"language":"de"} |

    And I expect node aggregate identifier "nodewyn-tetherton" to lead to node cs-identifier;nodewyn-tetherton;{"language":"de"}
    And I expect this node to be of type "Neos.ContentRepository.Testing:ParentNodeTypeC"
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                 |
      | tethered | cs-identifier;nodimer-tetherton;{"language":"de"} |

    And I expect node aggregate identifier "nodimer-tetherton" to lead to node cs-identifier;nodimer-tetherton;{"language":"de"}
    And I expect this node to be of type "Neos.ContentRepository.Testing:NodeTypeCCollection"

    When I am in workspace "live" and dimension space point {"language":"gsw"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"language":"gsw"}
    And I expect this node to be of type "Neos.ContentRepository.Testing:GrandParentNodeTypeC"
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                  |
      | tethered | cs-identifier;nodewyn-tetherton;{"language":"gsw"} |

    And I expect node aggregate identifier "nodewyn-tetherton" to lead to node cs-identifier;nodewyn-tetherton;{"language":"gsw"}
    And I expect this node to be of type "Neos.ContentRepository.Testing:ParentNodeTypeC"
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                  |
      | tethered | cs-identifier;nodimer-tetherton;{"language":"gsw"} |

    And I expect node aggregate identifier "nodimer-tetherton" to lead to node cs-identifier;nodimer-tetherton;{"language":"gsw"}
    And I expect this node to be of type "Neos.ContentRepository.Testing:NodeTypeCCollection"
