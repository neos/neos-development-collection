@contentrepository @adapters=DoctrineDBAL
Feature: Change node aggregate type - behavior of HAPPYPATH strategy

  As a user of the CR I want to change the type of a node aggregate.

  # @todo change type to a type with a tethered child with the same name as one of the original one's but of different type
  Background:
    Given using the following content dimensions:
      | Identifier | Values  | Generalizations |
      | language   | de, gsw | gsw->de         |
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:AutoCreated': []
    'Neos.ContentRepository.Testing:ParentNodeType':
      childNodes:
        autocreated:
          type: 'Neos.ContentRepository.Testing:AutoCreated'
          constraints:
            nodeTypes:
              '*': TRUE
              'Neos.ContentRepository.Testing:NodeTypeB': FALSE
    'Neos.ContentRepository.Testing:ParentNodeTypeB':
      childNodes:
        autocreated:
          type: 'Neos.ContentRepository.Testing:AutoCreated'
          constraints:
            nodeTypes:
              '*': TRUE
              'Neos.ContentRepository.Testing:NodeTypeA': FALSE
      constraints:
        nodeTypes:
          '*': TRUE
          'Neos.ContentRepository.Testing:NodeTypeA': FALSE
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
        child-of-type-b:
          type: 'Neos.ContentRepository.Testing:ChildOfNodeTypeB'
      properties:
        otherText:
          type: string
          defaultValue: 'otherText'
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                  | Value                |
      | workspaceName        | "live"               |
      | workspaceTitle       | "Live"               |
      | workspaceDescription | "The live workspace" |
      | newContentStreamId   | "cs-identifier"      |
    And I am in workspace "live" and dimension space point {"language":"de"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |

    When the command CreateNodeAggregateWithNodeAndSerializedProperties is executed with payload:
      | Key                       | Value                                           |
      | nodeAggregateId           | "sir-david-nodenborough"                        |
      | nodeTypeName              | "Neos.ContentRepository.Testing:ParentNodeType" |
      | originDimensionSpacePoint | {"language":"de"}                               |
      | parentNodeAggregateId     | "lady-eleonode-rootford"                        |
      | nodeName                  | "parent"                                        |
      | initialPropertyValues     | {}                                              |


  Scenario: Try to change to a node type that disallows already present children with the HAPPYPATH conflict resolution strategy
    When the command CreateNodeAggregateWithNodeAndSerializedProperties is executed with payload:
      | Key                       | Value                                      |
      | nodeAggregateId           | "nody-mc-nodeface"                         |
      | nodeTypeName              | "Neos.ContentRepository.Testing:NodeTypeA" |
      | originDimensionSpacePoint | {"language":"de"}                          |
      | parentNodeAggregateId     | "sir-david-nodenborough"                   |

    When the command ChangeNodeAggregateType is executed with payload and exceptions are caught:
      | Key             | Value                                            |
      | nodeAggregateId | "sir-david-nodenborough"                         |
      | newNodeTypeName | "Neos.ContentRepository.Testing:ParentNodeTypeB" |
      | strategy        | "happypath"                                      |
    Then the last command should have thrown an exception of type "NodeConstraintException"

  Scenario: Try to change to a node type that disallows already present grandchildren with the HAPPYPATH conflict resolution strategy
    When the command CreateNodeAggregateWithNodeAndSerializedProperties is executed with payload:
      | Key                                | Value                                           |
      | nodeAggregateId                    | "parent2-na"                                    |
      | nodeTypeName                       | "Neos.ContentRepository.Testing:ParentNodeType" |
      | originDimensionSpacePoint          | {"language":"de"}                               |
      | parentNodeAggregateId              | "lady-eleonode-rootford"                        |
      | nodeName                           | "parent2"                                       |
      | tetheredDescendantNodeAggregateIds | {"autocreated": "autocreated-child"}            |

    When the command CreateNodeAggregateWithNodeAndSerializedProperties is executed with payload:
      | Key                       | Value                                      |
      | nodeAggregateId           | "nody-mc-nodeface"                         |
      | nodeTypeName              | "Neos.ContentRepository.Testing:NodeTypeA" |
      | originDimensionSpacePoint | {"language":"de"}                          |
      | parentNodeAggregateId     | "autocreated-child"                        |
      | initialPropertyValues     | {}                                         |

    When the command ChangeNodeAggregateType is executed with payload and exceptions are caught:
      | Key             | Value                                            |
      | nodeAggregateId | "parent2-na"                                     |
      | newNodeTypeName | "Neos.ContentRepository.Testing:ParentNodeTypeB" |
      | strategy        | "happypath"                                      |
    Then the last command should have thrown an exception of type "NodeConstraintException"

  Scenario: Change node type successfully
    When the command CreateNodeAggregateWithNodeAndSerializedProperties is executed with payload:
      | Key                                | Value                                      |
      | nodeAggregateId                    | "nodea-identifier-de"                      |
      | nodeTypeName                       | "Neos.ContentRepository.Testing:NodeTypeA" |
      | originDimensionSpacePoint          | {"language":"de"}                          |
      | parentNodeAggregateId              | "lady-eleonode-rootford"                   |
      | initialPropertyValues              | {}                                         |
      | tetheredDescendantNodeAggregateIds | { "child-of-type-a": "child-of-type-a-id"} |

    When the command CreateNodeVariant is executed with payload:
      | Key             | Value                 |
      | nodeAggregateId | "nodea-identifier-de" |
      | sourceOrigin    | {"language":"de"}     |
      | targetOrigin    | {"language":"gsw"}    |

    When the command ChangeNodeAggregateType is executed with payload:
      | Key                                | Value                                      |
      | nodeAggregateId                    | "nodea-identifier-de"                      |
      | newNodeTypeName                    | "Neos.ContentRepository.Testing:NodeTypeB" |
      | strategy                           | "happypath"                                |
      | tetheredDescendantNodeAggregateIds | { "child-of-type-b": "child-of-type-b-id"} |

    # the type has changed
    When I am in workspace "live" and dimension space point {"language":"de"}
    Then I expect node aggregate identifier "nodea-identifier-de" to lead to node cs-identifier;nodea-identifier-de;{"language":"de"}
    And I expect this node to be of type "Neos.ContentRepository.Testing:NodeTypeB"

    When I am in workspace "live" and dimension space point {"language":"gsw"}
    Then I expect node aggregate identifier "nodea-identifier-de" to lead to node cs-identifier;nodea-identifier-de;{"language":"gsw"}
    And I expect this node to be of type "Neos.ContentRepository.Testing:NodeTypeB"

    # the old "childOfTypeA" has not been removed with this strategy.
    And I expect this node to have the following child nodes:
      | Name            | NodeDiscriminator                                   |
      | child-of-type-a | cs-identifier;child-of-type-a-id;{"language":"gsw"} |
      | child-of-type-b | cs-identifier;child-of-type-b-id;{"language":"gsw"} |

#      #missing default property values of target type must be set
#      #extra properties of source target type must be removed (TBD)
