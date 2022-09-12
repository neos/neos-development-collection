@contentrepository @adapters=DoctrineDBAL
Feature: Change node aggregate type - behavior of DELETE strategy

  As a user of the CR I want to change the type of a node aggregate.

  Background:
    Given I have the following content dimensions:
      | Identifier | Values  | Generalizations |
      | language   | de, gsw | gsw->de         |
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Testing:ParentNodeType': []
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
    And the command CreateRootWorkspace is executed with payload:
      | Key                        | Value                |
      | workspaceName              | "live"               |
      | workspaceTitle             | "Live"               |
      | workspaceDescription       | "The live workspace" |
      | newContentStreamId | "cs-identifier"      |
      | initiatingUserId   | "system-user"        |
    And the graph projection is fully up to date
    And the event RootNodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                     |
      | contentStreamId     | "cs-identifier"                           |
      | nodeAggregateId     | "lady-eleonode-rootford"                  |
      | nodeTypeName                | "Neos.ContentRepository:Root"             |
      | coveredDimensionSpacePoints | [{"language": "de"}, {"language": "gsw"}] |
      | initiatingUserId    | "system-user"                             |
      | nodeAggregateClassification | "root"                                    |
    And the graph projection is fully up to date

    When the command CreateNodeAggregateWithNodeAndSerializedProperties is executed with payload:
      | Key                           | Value                                           |
      | contentStreamId       | "cs-identifier"                                 |
      | nodeAggregateId       | "sir-david-nodenborough"                        |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:ParentNodeType" |
      | originDimensionSpacePoint     | {"language":"de"}                               |
      | parentNodeAggregateId | "lady-eleonode-rootford"                        |
      | nodeName                      | "parent"                                        |
      | initialPropertyValues         | {}                                              |
      | initiatingUserId      | "user"                                          |

    And the graph projection is fully up to date

  Scenario: Try to change to a node type that disallows already present children with the delete conflict resolution strategy
    Given I have the following additional NodeTypes configuration:
    """
    'Neos.ContentRepository.Testing:ParentNodeTypeB':
      constraints:
        nodeTypes:
          '*': TRUE
          'Neos.ContentRepository.Testing:NodeTypeA': FALSE
    """
    When the command CreateNodeAggregateWithNodeAndSerializedProperties is executed with payload:
      | Key                           | Value                                      |
      | contentStreamId       | "cs-identifier"                            |
      | nodeAggregateId       | "nody-mc-nodeface"                         |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:NodeTypeA" |
      | originDimensionSpacePoint     | {"language":"de"}                          |
      | parentNodeAggregateId | "sir-david-nodenborough"                   |
      | initiatingUserId      | "user"                                     |
    And the graph projection is fully up to date

    When the command ChangeNodeAggregateType was published with payload:
      | Key                      | Value                                            |
      | contentStreamId  | "cs-identifier"                                  |
      | nodeAggregateId  | "sir-david-nodenborough"                         |
      | newNodeTypeName          | "Neos.ContentRepository.Testing:ParentNodeTypeB" |
      | strategy                 | "delete"                                         |
      | initiatingUserId | "user"                                           |
    And the graph projection is fully up to date

    # the type has changed
    When I am in content stream "cs-identifier" and dimension space point {"language":"de"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"language":"de"}
    And I expect this node to be of type "Neos.ContentRepository.Testing:ParentNodeTypeB"

    # the child nodes have been removed
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to no node
    When I am in content stream "cs-identifier" and dimension space point {"language":"gsw"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to no node

  Scenario: Try to change to a node type that disallows already present grandchildren with the delete conflict resolution strategy
    Given I have the following additional NodeTypes configuration:
    """
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
    """
    When the command CreateNodeAggregateWithNodeAndSerializedProperties is executed with payload:
      | Key                                        | Value                                           |
      | contentStreamId                    | "cs-identifier"                                 |
      | nodeAggregateId                    | "parent2-na"                                    |
      | nodeTypeName                               | "Neos.ContentRepository.Testing:ParentNodeType" |
      | originDimensionSpacePoint                  | {"language":"de"}                               |
      | parentNodeAggregateId              | "lady-eleonode-rootford"                        |
      | nodeName                                   | "parent2"                                       |
      | tetheredDescendantNodeAggregateIds | {"autocreated": "autocreated-child"}            |
      | initiatingUserId                   | "user"                                          |
    And the graph projection is fully up to date

    When the command CreateNodeAggregateWithNodeAndSerializedProperties is executed with payload:
      | Key                           | Value                                      |
      | contentStreamId       | "cs-identifier"                            |
      | nodeAggregateId       | "nody-mc-nodeface"                         |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:NodeTypeA" |
      | originDimensionSpacePoint     | {"language":"de"}                          |
      | parentNodeAggregateId | "autocreated-child"                        |
      | initialPropertyValues         | {}                                         |
      | initiatingUserId      | "user"                                     |
    And the graph projection is fully up to date

    When the command ChangeNodeAggregateType was published with payload:
      | Key                      | Value                                            |
      | contentStreamId  | "cs-identifier"                                  |
      | nodeAggregateId  | "parent2-na"                                     |
      | newNodeTypeName          | "Neos.ContentRepository.Testing:ParentNodeTypeB" |
      | strategy                 | "delete"                                         |
      | initiatingUserId | "user"                                           |
    And the graph projection is fully up to date

    # the type has changed
    When I am in content stream "cs-identifier" and dimension space point {"language":"de"}
    Then I expect node aggregate identifier "parent2-na" to lead to node cs-identifier;parent2-na;{"language":"de"}
    And I expect this node to be of type "Neos.ContentRepository.Testing:ParentNodeTypeB"

    # the child nodes still exist
    When I am in content stream "cs-identifier" and dimension space point {"language":"de"}
    Then I expect node aggregate identifier "autocreated-child" to lead to node cs-identifier;autocreated-child;{"language":"de"}
    When I am in content stream "cs-identifier" and dimension space point {"language":"gsw"}
    Then I expect node aggregate identifier "autocreated-child" to lead to node cs-identifier;autocreated-child;{"language":"de"}

    # the grandchild nodes have been removed
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to no node
    When I am in content stream "cs-identifier" and dimension space point {"language":"gsw"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to no node


  Scenario: Change node type successfully
    When the command CreateNodeAggregateWithNodeAndSerializedProperties is executed with payload:
      | Key                           | Value                                      |
      | contentStreamId       | "cs-identifier"                            |
      | nodeAggregateId       | "nodea-identifier-de"                      |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:NodeTypeA" |
      | originDimensionSpacePoint     | {"language":"de"}                          |
      | parentNodeAggregateId | "lady-eleonode-rootford"                   |
      | initialPropertyValues         | {}                                         |
      | initiatingUserId      | "user"                                     |
    And the graph projection is fully up to date

    When the command CreateNodeVariant is executed with payload:
      | Key                      | Value                 |
      | contentStreamId  | "cs-identifier"       |
      | nodeAggregateId  | "nodea-identifier-de" |
      | sourceOrigin             | {"language":"de"}     |
      | targetOrigin             | {"language":"gsw"}    |
      | initiatingUserId | "user"                |
    And the graph projection is fully up to date

    When the command ChangeNodeAggregateType was published with payload:
      | Key                                        | Value                                      |
      | contentStreamId                    | "cs-identifier"                            |
      | nodeAggregateId                    | "nodea-identifier-de"                      |
      | newNodeTypeName                            | "Neos.ContentRepository.Testing:NodeTypeB" |
      | strategy                                   | "delete"                                   |
      | initiatingUserId                   | "user"                                     |
      | tetheredDescendantNodeAggregateIds | { "child-of-type-b": "child-of-type-b-id"} |
    And the graph projection is fully up to date

    # the type has changed
    When I am in content stream "cs-identifier" and dimension space point {"language":"de"}
    Then I expect node aggregate identifier "nodea-identifier-de" to lead to node cs-identifier;nodea-identifier-de;{"language":"de"}
    And I expect this node to be of type "Neos.ContentRepository.Testing:NodeTypeB"

    When I am in content stream "cs-identifier" and dimension space point {"language":"gsw"}
    Then I expect node aggregate identifier "nodea-identifier-de" to lead to node cs-identifier;nodea-identifier-de;{"language":"gsw"}
    And I expect this node to be of type "Neos.ContentRepository.Testing:NodeTypeB"
    And I expect this node to have the following child nodes:
      | Name            | NodeDiscriminator                                   |
      | child-of-type-b | cs-identifier;child-of-type-b-id;{"language":"gsw"} |

  Scenario: When changing node type, a non-allowed tethered node should stay (Tethered nodes are not taken into account when checking constraints)
    And I have the following additional NodeTypes configuration:
    """
    'Neos.ContentRepository.Testing:NodeTypeB':
      childNodes:
        # !! NodeTypeB has BOTH childOfTypeA AND childOfTypeB as tethered child nodes...
        child-of-type-a:
          type: 'Neos.ContentRepository.Testing:ChildOfNodeTypeA'
      constraints:
        nodeTypes:
          # both of these types are forbidden.
          'Neos.ContentRepository.Testing:ChildOfNodeTypeA': false
          'Neos.ContentRepository.Testing:ChildOfNodeTypeB': false
    """
    When the command CreateNodeAggregateWithNodeAndSerializedProperties is executed with payload:
      | Key                                        | Value                                      |
      | contentStreamId                    | "cs-identifier"                            |
      | nodeAggregateId                    | "nodea-identifier-de"                      |
      | nodeTypeName                               | "Neos.ContentRepository.Testing:NodeTypeA" |
      | originDimensionSpacePoint                  | {"language":"de"}                          |
      | parentNodeAggregateId              | "lady-eleonode-rootford"                   |
      | initialPropertyValues                      | {}                                         |
      | initiatingUserId                   | "user"                                     |
      | tetheredDescendantNodeAggregateIds | { "child-of-type-a": "child-of-type-a-id"} |
    And the graph projection is fully up to date

    When the command CreateNodeVariant is executed with payload:
      | Key                      | Value                 |
      | contentStreamId  | "cs-identifier"       |
      | nodeAggregateId  | "nodea-identifier-de" |
      | sourceOrigin             | {"language":"de"}     |
      | targetOrigin             | {"language":"gsw"}    |
      | initiatingUserId | "user"                |
    And the graph projection is fully up to date

    When the command ChangeNodeAggregateType was published with payload:
      | Key                                        | Value                                      |
      | contentStreamId                    | "cs-identifier"                            |
      | nodeAggregateId                    | "nodea-identifier-de"                      |
      | newNodeTypeName                            | "Neos.ContentRepository.Testing:NodeTypeB" |
      | strategy                                   | "delete"                                   |
      | initiatingUserId                   | "user"                                     |
      | tetheredDescendantNodeAggregateIds | { "child-of-type-b": "child-of-type-b-id"} |
    And the graph projection is fully up to date

    # the type has changed
    When I am in content stream "cs-identifier" and dimension space point {"language":"de"}
    Then I expect node aggregate identifier "nodea-identifier-de" to lead to node cs-identifier;nodea-identifier-de;{"language":"de"}
    And I expect this node to be of type "Neos.ContentRepository.Testing:NodeTypeB"

    # BOTH tethered child nodes still need to exist
    And I expect this node to have the following child nodes:
      | Name            | NodeDiscriminator                                  |
      | child-of-type-a | cs-identifier;child-of-type-a-id;{"language":"de"} |
      | child-of-type-b | cs-identifier;child-of-type-b-id;{"language":"de"} |
