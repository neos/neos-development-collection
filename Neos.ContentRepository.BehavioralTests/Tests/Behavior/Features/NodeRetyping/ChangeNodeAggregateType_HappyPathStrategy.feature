@contentrepository @adapters=DoctrineDBAL
Feature: Change node aggregate type - behavior of HAPPYPATH strategy

  As a user of the CR I want to change the type of a node aggregate.

  # @todo change type to a type with a tethered child with the same name as one of the original one's but of different type
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
    And the graph projection is fully up to date
    And the event RootNodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                     |
      | contentStreamId     | "cs-identifier"                           |
      | nodeAggregateId     | "lady-eleonode-rootford"                  |
      | nodeTypeName                | "Neos.ContentRepository:Root"             |
      | coveredDimensionSpacePoints | [{"language": "de"}, {"language": "gsw"}] |
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

    And the graph projection is fully up to date

  Scenario: Try to change to a node type that disallows already present children with the HAPPYPATH conflict resolution strategy
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
    And the graph projection is fully up to date

    When the command ChangeNodeAggregateType was published with payload and exceptions are caught:
      | Key                      | Value                                            |
      | contentStreamId  | "cs-identifier"                                  |
      | nodeAggregateId  | "sir-david-nodenborough"                         |
      | newNodeTypeName          | "Neos.ContentRepository.Testing:ParentNodeTypeB" |
      | strategy                 | "happypath"                                      |
    Then the last command should have thrown an exception of type "NodeConstraintException"

  Scenario: Try to change to a node type that disallows already present grandchildren with the HAPPYPATH conflict resolution strategy
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
    And the graph projection is fully up to date

    When the command CreateNodeAggregateWithNodeAndSerializedProperties is executed with payload:
      | Key                           | Value                                      |
      | contentStreamId       | "cs-identifier"                            |
      | nodeAggregateId       | "nody-mc-nodeface"                         |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:NodeTypeA" |
      | originDimensionSpacePoint     | {"language":"de"}                          |
      | parentNodeAggregateId | "autocreated-child"                        |
      | initialPropertyValues         | {}                                         |
    And the graph projection is fully up to date

    When the command ChangeNodeAggregateType was published with payload and exceptions are caught:
      | Key                      | Value                                            |
      | contentStreamId  | "cs-identifier"                                  |
      | nodeAggregateId  | "parent2-na"                                     |
      | newNodeTypeName          | "Neos.ContentRepository.Testing:ParentNodeTypeB" |
      | strategy                 | "happypath"                                      |
    Then the last command should have thrown an exception of type "NodeConstraintException"

  Scenario: Change node type successfully
    When the command CreateNodeAggregateWithNodeAndSerializedProperties is executed with payload:
      | Key                                        | Value                                      |
      | contentStreamId                    | "cs-identifier"                            |
      | nodeAggregateId                    | "nodea-identifier-de"                      |
      | nodeTypeName                               | "Neos.ContentRepository.Testing:NodeTypeA" |
      | originDimensionSpacePoint                  | {"language":"de"}                          |
      | parentNodeAggregateId              | "lady-eleonode-rootford"                   |
      | initialPropertyValues                      | {}                                         |
      | tetheredDescendantNodeAggregateIds | { "child-of-type-a": "child-of-type-a-id"} |
    And the graph projection is fully up to date

    When the command CreateNodeVariant is executed with payload:
      | Key                      | Value                 |
      | contentStreamId  | "cs-identifier"       |
      | nodeAggregateId  | "nodea-identifier-de" |
      | sourceOrigin             | {"language":"de"}     |
      | targetOrigin             | {"language":"gsw"}    |
    And the graph projection is fully up to date

    When the command ChangeNodeAggregateType was published with payload:
      | Key                                        | Value                                      |
      | contentStreamId                    | "cs-identifier"                            |
      | nodeAggregateId                    | "nodea-identifier-de"                      |
      | newNodeTypeName                            | "Neos.ContentRepository.Testing:NodeTypeB" |
      | strategy                                   | "happypath"                                |
      | tetheredDescendantNodeAggregateIds | { "child-of-type-b": "child-of-type-b-id"} |
    And the graph projection is fully up to date

    # the type has changed
    When I am in content stream "cs-identifier" and dimension space point {"language":"de"}
    Then I expect node aggregate identifier "nodea-identifier-de" to lead to node cs-identifier;nodea-identifier-de;{"language":"de"}
    And I expect this node to be of type "Neos.ContentRepository.Testing:NodeTypeB"

    When I am in content stream "cs-identifier" and dimension space point {"language":"gsw"}
    Then I expect node aggregate identifier "nodea-identifier-de" to lead to node cs-identifier;nodea-identifier-de;{"language":"gsw"}
    And I expect this node to be of type "Neos.ContentRepository.Testing:NodeTypeB"

    # the old "childOfTypeA" has not been removed with this strategy.
    And I expect this node to have the following child nodes:
      | Name            | NodeDiscriminator                                  |
      | child-of-type-a | cs-identifier;child-of-type-a-id;{"language":"gsw"} |
      | child-of-type-b | cs-identifier;child-of-type-b-id;{"language":"gsw"} |

#      #missing default property values of target type must be set
#      #extra properties of source target type must be removed (TBD)
