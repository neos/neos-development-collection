@fixtures
Feature: Publishing hide/show scenario of nodes

  Node structure is as follows:
  - rn-identifier (root node)
  -- sir-david-nodenborough (name=text1) <== this one is modified and published
  --- nody-mc-nodeface (name=text2)
  -- sir-nodeward-nodington-iii (name=image) <== this one is modified

  The setup is always as follows:
  - we modify two nodes using a certain command (e.g. DisableNode) in the USER workspace
  - we publish one of them
  - we check that the user workspace still sees both nodes as hidden; and the live workspace only sees one of the changes.

  We do the same for other commands. This way, we ensure that both the command works generally;
  and the matchesNodeAddress() address of the command is actually implemented somehow properly.


  Background:
    Given I have no content dimensions
    And the command CreateRootWorkspace is executed with payload:
      | Key                        | Value           |
      | workspaceName              | "live"          |
      | newContentStreamIdentifier | "cs-identifier" |
      | initiatingUserIdentifier   | "user-id"       |
    And the graph projection is fully up to date
    And I have the following NodeTypes configuration:
    """
    Neos.ContentRepository:Root: {}
    'Neos.ContentRepository.Testing:Content':
      properties:
        text:
          type: string
    'Neos.ContentRepository.Testing:Image':
      properties:
        image:
          type: string
    """
    And the event RootNodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                         |
      | contentStreamIdentifier     | "cs-identifier"               |
      | nodeAggregateIdentifier     | "lady-eleonode-rootford"      |
      | nodeTypeName                | "Neos.ContentRepository:Root" |
      | coveredDimensionSpacePoints | [{}]                          |
      | initiatingUserIdentifier    | "system"                      |
      | nodeAggregateClassification | "root"                        |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                               |
      | contentStreamIdentifier       | "cs-identifier"                                     |
      | nodeAggregateIdentifier       | "sir-david-nodenborough"                            |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Content"            |
      | originDimensionSpacePoint     | {}                                                  |
      | coveredDimensionSpacePoints   | [{}]                                                |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                            |
      | initialPropertyValues         | {"text": {"type": "string", "value": "Initial t1"}} |
      | nodeAggregateClassification   | "regular"                                           |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                               |
      | contentStreamIdentifier       | "cs-identifier"                                     |
      | nodeAggregateIdentifier       | "nody-mc-nodeface"                                  |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Content"            |
      | originDimensionSpacePoint     | {}                                                  |
      | coveredDimensionSpacePoints   | [{}]                                                |
      | parentNodeAggregateIdentifier | "sir-david-nodenborough"                            |
      | initialPropertyValues         | {"text": {"type": "string", "value": "Initial t2"}} |
      | nodeAggregateClassification   | "regular"                                           |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                  |
      | contentStreamIdentifier       | "cs-identifier"                                        |
      | nodeAggregateIdentifier       | "sir-nodeward-nodington-iii"                           |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Image"                 |
      | originDimensionSpacePoint     | {}                                                     |
      | coveredDimensionSpacePoints   | [{}]                                                   |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                               |
      | initialPropertyValues         | {"image": {"type": "image", "value": "Initial image"}} |
      | nodeAggregateClassification   | "regular"                                              |
    And the graph projection is fully up to date

  Scenario: (DisableNode) It is possible to publish hiding of a node.
    Given the command CreateWorkspace is executed with payload:
      | Key                        | Value                |
      | workspaceName              | "user-test"          |
      | baseWorkspaceName          | "live"               |
      | newContentStreamIdentifier | "user-cs-identifier" |
    And the graph projection is fully up to date

    # SETUP: hide two nodes in USER workspace
    Given the command DisableNodeAggregate is executed with payload:
      | Key                          | Value                                  |
      | contentStreamIdentifier      | "user-cs-identifier"                   |
      | nodeAggregateIdentifier      | "sir-david-nodenborough"               |
      | coveredDimensionSpacePoint   | {}                                     |
      | nodeVariantSelectionStrategy | "allVariants"                          |
      | initiatingUserIdentifier     | "00000000-0000-0000-0000-000000000000" |
    And the command DisableNodeAggregate is executed with payload:
      | Key                          | Value                                  |
      | contentStreamIdentifier      | "user-cs-identifier"                   |
      | nodeAggregateIdentifier      | "sir-nodeward-nodington-iii"           |
      | coveredDimensionSpacePoint   | {}                                     |
      | nodeVariantSelectionStrategy | "allVariants"                          |
      | initiatingUserIdentifier     | "00000000-0000-0000-0000-000000000000" |
    And the graph projection is fully up to date

    When the command "PublishIndividualNodesFromWorkspace" is executed with payload:
      | Key                      | Value                                                                                                                               |
      | workspaceName            | "user-test"                                                                                                                         |
      | nodeAddresses            | [{"nodeAggregateIdentifier": "sir-david-nodenborough", "contentStreamIdentifier": "user-cs-identifier", "dimensionSpacePoint": {}}] |
      | initiatingUserIdentifier | "initiating-user-identifier"                                                                                                        |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to no node
    And I expect node aggregate identifier "nody-mc-nodeface" to lead to no node
    And I expect node aggregate identifier "sir-nodeward-nodington-iii" to lead to node cs-identifier;sir-nodeward-nodington-iii;{}

    When I am in the active content stream of workspace "user-test" and dimension space point {}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to no node
    And I expect node aggregate identifier "nody-mc-nodeface" to lead to no node
    And I expect node aggregate identifier "sir-nodeward-nodington-iii" to lead to no node

  Scenario: (ShowNode) It is possible to publish showing of a node.
    # BEFORE: ensure two nodes are hidden in live (and user WS)
    Given the command DisableNodeAggregate is executed with payload:
      | Key                          | Value                                  |
      | contentStreamIdentifier      | "cs-identifier"                        |
      | nodeAggregateIdentifier      | "sir-david-nodenborough"               |
      | coveredDimensionSpacePoint   | {}                                     |
      | nodeVariantSelectionStrategy | "allVariants"                          |
      | initiatingUserIdentifier     | "00000000-0000-0000-0000-000000000000" |
    Given the command DisableNodeAggregate is executed with payload:
      | Key                          | Value                                  |
      | contentStreamIdentifier      | "cs-identifier"                        |
      | nodeAggregateIdentifier      | "sir-nodeward-nodington-iii"           |
      | coveredDimensionSpacePoint   | {}                                     |
      | nodeVariantSelectionStrategy | "allVariants"                          |
      | initiatingUserIdentifier     | "00000000-0000-0000-0000-000000000000" |
    Given the command CreateWorkspace is executed with payload:
      | Key                        | Value                |
      | workspaceName              | "user-test"          |
      | baseWorkspaceName          | "live"               |
      | newContentStreamIdentifier | "user-cs-identifier" |
    And the graph projection is fully up to date

    # SETUP: show two nodes in USER workspace
    Given the command EnableNodeAggregate is executed with payload:
      | Key                          | Value                    |
      | contentStreamIdentifier      | "user-cs-identifier"     |
      | nodeAggregateIdentifier      | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {}                       |
      | nodeVariantSelectionStrategy | "allVariants"            |
    Given the command EnableNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | contentStreamIdentifier      | "user-cs-identifier"         |
      | nodeAggregateIdentifier      | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {}                           |
      | nodeVariantSelectionStrategy | "allVariants"                |
    And the graph projection is fully up to date

    When the command "PublishIndividualNodesFromWorkspace" is executed with payload:
      | Key                      | Value                                                                                                                               |
      | workspaceName            | "user-test"                                                                                                                         |
      | nodeAddresses            | [{"nodeAggregateIdentifier": "sir-david-nodenborough", "contentStreamIdentifier": "user-cs-identifier", "dimensionSpacePoint": {}}] |
      | initiatingUserIdentifier | "initiating-user-identifier"                                                                                                        |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{}
    And I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{}
    And I expect node aggregate identifier "sir-nodeward-nodington-iii" to lead to no node

    When I am in the active content stream of workspace "user-test" and dimension space point {}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node user-cs-identifier;sir-david-nodenborough;{}
    And I expect node aggregate identifier "nody-mc-nodeface" to lead to node user-cs-identifier;nody-mc-nodeface;{}
    And I expect node aggregate identifier "sir-nodeward-nodington-iii" to lead to node user-cs-identifier;nody-mc-nodeface;{}


    # @todo check why these won't run

  #Scenario: (ChangeNodeAggregateName) It is possible to publish changing the node name.
  #  Given the command CreateWorkspace is executed with payload:
  #    | Key                        | Value                |
   ##   | workspaceName              | "user-test"          |
   #   | baseWorkspaceName          | "live"               |
   #   | newContentStreamIdentifier | "user-cs-identifier" |
   # And the graph projection is fully up to date

    # SETUP: change two node names in USER workspace
    #Given the command "ChangeNodeAggregateName" is executed with payload:
    #  | Key                     | Value                    |
    #  | contentStreamIdentifier | "user-cs-identifier"     |
    #  | nodeAggregateIdentifier | "sir-david-nodenborough" |
    #  | newNodeName             | "text1mod"               |
    #Given the command "ChangeNodeAggregateName" is executed with payload:
    #  | Key                     | Value                        |
    #  | contentStreamIdentifier | "user-cs-identifier"         |
    #  | nodeAggregateIdentifier | "sir-nodeward-nodington-iii" |
     # | newNodeName             | "imagemod"                   |
   # And the graph projection is fully up to date

   # When the command "PublishIndividualNodesFromWorkspace" is executed with payload:
   #   | Key           | Value                                                                                                                               |
   #   | workspaceName | "user-test"                                                                                                                         |
    #  | nodeAddresses | [{"nodeAggregateIdentifier": "sir-david-nodenborough", "contentStreamIdentifier": "user-cs-identifier", "dimensionSpacePoint": {}}] |
    #And the graph projection is fully up to date

   # When I am in the active content stream of workspace "live" and dimension space point {}
   ## Then I expect the node aggregate "lady-eleonode-rootford" to have the following child nodes:
    #  | Name     | NodeAggregateIdentifier    |
    #  | text1mod | sir-david-nodenborough     |
     # | image    | sir-nodeward-nodington-iii |

   # When I am in the active content stream of workspace "user-test" and dimension space point {}
   # Then I expect the node aggregate "lady-eleonode-rootford" to have the following child nodes:
   #   | Name     | NodeAggregateIdentifier    |
   #   | text1mod | sir-david-nodenborough     |
   #   | imagemod | sir-nodeward-nodington-iii |


  Scenario: (RemoveNodeAggregate) It is possible to publish a node removal
    Given the command CreateWorkspace is executed with payload:
      | Key                        | Value                |
      | workspaceName              | "user-test"          |
      | baseWorkspaceName          | "live"               |
      | newContentStreamIdentifier | "user-cs-identifier" |
    And the graph projection is fully up to date

    # SETUP: remove two nodes in USER workspace
    When the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                    |
      | contentStreamIdentifier      | "user-cs-identifier"     |
      | nodeAggregateIdentifier      | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {}                       |
      | nodeVariantSelectionStrategy | "allVariants"            |

    When the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | contentStreamIdentifier      | "user-cs-identifier"         |
      | nodeAggregateIdentifier      | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {}                           |
      | nodeVariantSelectionStrategy | "allVariants"                |
    And the graph projection is fully up to date

    When the command "PublishIndividualNodesFromWorkspace" is executed with payload:
      | Key                      | Value                                                                                                                               |
      | workspaceName            | "user-test"                                                                                                                         |
      | nodeAddresses            | [{"nodeAggregateIdentifier": "sir-david-nodenborough", "contentStreamIdentifier": "user-cs-identifier", "dimensionSpacePoint": {}}] |
      | initiatingUserIdentifier | "initiating-user-identifier"                                                                                                        |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to no node
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to no node
    Then I expect node aggregate identifier "sir-nodeward-nodington-iii" to lead to node cs-identifier;sir-nodeward-nodington-iii;{}

    When I am in the active content stream of workspace "user-test" and dimension space point {}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to no node
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to no node
    Then I expect node aggregate identifier "sir-nodeward-nodington-iii" to lead to no node


  Scenario: (RemoveNodeAggregate) It is possible to publish a node removal
    Given the command CreateWorkspace is executed with payload:
      | Key                        | Value                |
      | workspaceName              | "user-test"          |
      | baseWorkspaceName          | "live"               |
      | newContentStreamIdentifier | "user-cs-identifier" |
    And the graph projection is fully up to date

    # SETUP: remove two nodes in USER workspace
    When the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                    |
      | contentStreamIdentifier      | "user-cs-identifier"     |
      | nodeAggregateIdentifier      | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {}                       |
      | nodeVariantSelectionStrategy | "allVariants"            |
    When the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | contentStreamIdentifier      | "user-cs-identifier"         |
      | nodeAggregateIdentifier      | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {}                           |
      | nodeVariantSelectionStrategy | "allVariants"                |
    And the graph projection is fully up to date

    When the command "PublishIndividualNodesFromWorkspace" is executed with payload:
      | Key                      | Value                                                                                                                               |
      | workspaceName            | "user-test"                                                                                                                         |
      | nodeAddresses            | [{"nodeAggregateIdentifier": "sir-david-nodenborough", "contentStreamIdentifier": "user-cs-identifier", "dimensionSpacePoint": {}}] |
      | initiatingUserIdentifier | "initiating-user-identifier"                                                                                                        |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to no node
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to no node
    Then I expect node aggregate identifier "sir-nodeward-nodington-iii" to lead to node cs-identifier;sir-nodeward-nodington-iii;{}

    When I am in the active content stream of workspace "user-test" and dimension space point {}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to no node
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to no node
    Then I expect node aggregate identifier "sir-nodeward-nodington-iii" to lead to no node


  Scenario: (SetNodeReferences) It is possible to publish setting node references
    Given the command CreateWorkspace is executed with payload:
      | Key                        | Value                |
      | workspaceName              | "user-test"          |
      | baseWorkspaceName          | "live"               |
      | newContentStreamIdentifier | "user-cs-identifier" |
    And the graph projection is fully up to date

    # SETUP: set two node references in USER workspace
    When the command "SetNodeReferences" is executed with payload:
      | Key                                 | Value                          |
      | contentStreamIdentifier             | "user-cs-identifier"           |
      | sourceNodeAggregateIdentifier       | "sir-david-nodenborough"       |
      | sourceOriginDimensionSpacePoint     | {}                             |
      | referenceName                       | "referenceProperty"            |
      | destinationNodeAggregateIdentifiers | ["sir-nodeward-nodington-iii"] |
      | initiatingUserIdentifier            | "initiating-user-identifier"   |
    And the command "SetNodeReferences" is executed with payload:
      | Key                                 | Value                          |
      | contentStreamIdentifier             | "user-cs-identifier"           |
      | sourceNodeAggregateIdentifier       | "nody-mc-nodeface"             |
      | sourceOriginDimensionSpacePoint     | {}                             |
      | referenceName                       | "referenceProperty"            |
      | destinationNodeAggregateIdentifiers | ["sir-nodeward-nodington-iii"] |
      | initiatingUserIdentifier            | "initiating-user-identifier"   |
    And the graph projection is fully up to date

    When the command "PublishIndividualNodesFromWorkspace" is executed with payload:
      | Key                      | Value                                                                                                                               |
      | workspaceName            | "user-test"                                                                                                                         |
      | nodeAddresses            | [{"nodeAggregateIdentifier": "sir-david-nodenborough", "contentStreamIdentifier": "user-cs-identifier", "dimensionSpacePoint": {}}] |
      | initiatingUserIdentifier | "initiating-user-identifier"                                                                                                        |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{}
    And I expect this node to have the following references:
      | Key               | Value                                           |
      | referenceProperty | ["cs-identifier;sir-nodeward-nodington-iii;{}"] |
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{}
    And I expect this node to have no references
    Then I expect node aggregate identifier "sir-nodeward-nodington-iii" to lead to node cs-identifier;sir-nodeward-nodington-iii;{}
    And I expect this node to have no references
    And I expect this node to be referenced by:
      | Key               | Value                                       |
      | referenceProperty | ["cs-identifier;sir-david-nodenborough;{}"] |

    When I am in the active content stream of workspace "user-test" and dimension space point {}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node user-cs-identifier;sir-david-nodenborough;{}
    And I expect this node to have the following references:
      | Key               | Value                                                |
      | referenceProperty | ["user-cs-identifier;sir-nodeward-nodington-iii;{}"] |
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node user-cs-identifier;nody-mc-nodeface;{}
    And I expect this node to have the following references:
      | Key               | Value                                                |
      | referenceProperty | ["user-cs-identifier;sir-nodeward-nodington-iii;{}"] |
    Then I expect node aggregate identifier "sir-nodeward-nodington-iii" to lead to node user-cs-identifier;sir-nodeward-nodington-iii;{}
    And I expect this node to have the following references:
      | Key               | Value                                                                                     |
      | referenceProperty | ["user-cs-identifier;sir-david-nodenborough;{}","user-cs-identifier;nody-mc-nodeface;{}"] |

  Scenario: (CreateNodeAggregateWithNode) It is possible to publish new nodes
    Given the command CreateWorkspace is executed with payload:
      | Key                        | Value                |
      | workspaceName              | "user-test"          |
      | baseWorkspaceName          | "live"               |
      | newContentStreamIdentifier | "user-cs-identifier" |
    And the graph projection is fully up to date

    # SETUP: set two new nodes in USER workspace
    When the command CreateNodeAggregateWithNodeAndSerializedProperties is executed with payload:
      | Key                           | Value                                    |
      | contentStreamIdentifier       | "user-cs-identifier"                     |
      | nodeAggregateIdentifier       | "new1-agg"                               |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Content" |
      | originDimensionSpacePoint     | {}                                       |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                 |
      | nodeName                      | "foo"                                    |
    When the command CreateNodeAggregateWithNodeAndSerializedProperties is executed with payload:
      | Key                           | Value                                    |
      | contentStreamIdentifier       | "user-cs-identifier"                     |
      | nodeAggregateIdentifier       | "new2-agg"                               |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Content" |
      | originDimensionSpacePoint     | {}                                       |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                 |
      | nodeName                      | "foo2"                                   |
    And the graph projection is fully up to date

    When the command "PublishIndividualNodesFromWorkspace" is executed with payload:
      | Key                      | Value                                                                                                                 |
      | workspaceName            | "user-test"                                                                                                           |
      | nodeAddresses            | [{"nodeAggregateIdentifier": "new1-agg", "contentStreamIdentifier": "user-cs-identifier", "dimensionSpacePoint": {}}] |
      | initiatingUserIdentifier | "initiating-user-identifier"                                                                                          |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {}
    Then I expect node aggregate identifier "new1-agg" to lead to node cs-identifier;new1-agg;{}
    Then I expect node aggregate identifier "new2-agg" to lead to no node

    When I am in the active content stream of workspace "user-test" and dimension space point {}
    Then I expect node aggregate identifier "new1-agg" to lead to node user-cs-identifier;new1-agg;{}
    Then I expect node aggregate identifier "new2-agg" to lead to node user-cs-identifier;new2-agg;{}


  # TODO: implement MoveNodeAggregate testcase
  # TODO: implement CreateNodeVariant testcase
