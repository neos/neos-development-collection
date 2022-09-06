@contentrepository @adapters=DoctrineDBAL
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
      | newContentStreamId | "cs-identifier" |
    And the graph projection is fully up to date
    And I have the following NodeTypes configuration:
    """
    Neos.ContentRepository:Root: {}
    'Neos.ContentRepository.Testing:Content':
      properties:
        text:
          type: string
        referenceProperty:
          type: reference
    'Neos.ContentRepository.Testing:Image':
      properties:
        image:
          type: string
    """
    And the event RootNodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                         |
      | contentStreamId     | "cs-identifier"               |
      | nodeAggregateId     | "lady-eleonode-rootford"      |
      | nodeTypeName                | "Neos.ContentRepository:Root" |
      | coveredDimensionSpacePoints | [{}]                          |
      | nodeAggregateClassification | "root"                        |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                               |
      | contentStreamId       | "cs-identifier"                                     |
      | nodeAggregateId       | "sir-david-nodenborough"                            |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Content"            |
      | originDimensionSpacePoint     | {}                                                  |
      | coveredDimensionSpacePoints   | [{}]                                                |
      | parentNodeAggregateId | "lady-eleonode-rootford"                            |
      | initialPropertyValues         | {"text": {"type": "string", "value": "Initial t1"}} |
      | nodeAggregateClassification   | "regular"                                           |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                               |
      | contentStreamId       | "cs-identifier"                                     |
      | nodeAggregateId       | "nody-mc-nodeface"                                  |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Content"            |
      | originDimensionSpacePoint     | {}                                                  |
      | coveredDimensionSpacePoints   | [{}]                                                |
      | parentNodeAggregateId | "sir-david-nodenborough"                            |
      | initialPropertyValues         | {"text": {"type": "string", "value": "Initial t2"}} |
      | nodeAggregateClassification   | "regular"                                           |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                  |
      | contentStreamId       | "cs-identifier"                                        |
      | nodeAggregateId       | "sir-nodeward-nodington-iii"                           |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Image"                 |
      | originDimensionSpacePoint     | {}                                                     |
      | coveredDimensionSpacePoints   | [{}]                                                   |
      | parentNodeAggregateId | "lady-eleonode-rootford"                               |
      | initialPropertyValues         | {"image": {"type": "image", "value": "Initial image"}} |
      | nodeAggregateClassification   | "regular"                                              |
    And the graph projection is fully up to date

  Scenario: (DisableNode) It is possible to publish hiding of a node.
    Given the command CreateWorkspace is executed with payload:
      | Key                        | Value                |
      | workspaceName              | "user-test"          |
      | baseWorkspaceName          | "live"               |
      | newContentStreamId | "user-cs-identifier" |
    And the graph projection is fully up to date

    # SETUP: hide two nodes in USER workspace
    Given the command DisableNodeAggregate is executed with payload:
      | Key                          | Value                                  |
      | contentStreamId      | "user-cs-identifier"                   |
      | nodeAggregateId      | "sir-david-nodenborough"               |
      | coveredDimensionSpacePoint   | {}                                     |
      | nodeVariantSelectionStrategy | "allVariants"                          |
    And the command DisableNodeAggregate is executed with payload:
      | Key                          | Value                                  |
      | contentStreamId      | "user-cs-identifier"                   |
      | nodeAggregateId      | "sir-nodeward-nodington-iii"           |
      | coveredDimensionSpacePoint   | {}                                     |
      | nodeVariantSelectionStrategy | "allVariants"                          |
    And the graph projection is fully up to date

    When the command PublishIndividualNodesFromWorkspace is executed with payload:
      | Key                      | Value                                                                                                                               |
      | workspaceName            | "user-test"                                                                                                                         |
      | nodesToPublish           | [{"nodeAggregateId": "sir-david-nodenborough", "contentStreamId": "user-cs-identifier", "dimensionSpacePoint": {}}] |
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
      | contentStreamId      | "cs-identifier"                        |
      | nodeAggregateId      | "sir-david-nodenborough"               |
      | coveredDimensionSpacePoint   | {}                                     |
      | nodeVariantSelectionStrategy | "allVariants"                          |
    Given the command DisableNodeAggregate is executed with payload:
      | Key                          | Value                                  |
      | contentStreamId      | "cs-identifier"                        |
      | nodeAggregateId      | "sir-nodeward-nodington-iii"           |
      | coveredDimensionSpacePoint   | {}                                     |
      | nodeVariantSelectionStrategy | "allVariants"                          |
    # we need to ensure that the projections are up to date now; otherwise a content stream is forked with an out-
    # of-date base version. This means the content stream can never be merged back, but must always be rebased.
    And the graph projection is fully up to date
    Given the command CreateWorkspace is executed with payload:
      | Key                        | Value                |
      | workspaceName              | "user-test"          |
      | baseWorkspaceName          | "live"               |
      | newContentStreamId | "user-cs-identifier" |
    And the graph projection is fully up to date

    # SETUP: show two nodes in USER workspace
    Given the command EnableNodeAggregate is executed with payload:
      | Key                          | Value                    |
      | contentStreamId      | "user-cs-identifier"     |
      | nodeAggregateId      | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {}                       |
      | nodeVariantSelectionStrategy | "allVariants"            |
    Given the command EnableNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | contentStreamId      | "user-cs-identifier"         |
      | nodeAggregateId      | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {}                           |
      | nodeVariantSelectionStrategy | "allVariants"                |
    And the graph projection is fully up to date

    When the command PublishIndividualNodesFromWorkspace is executed with payload:
      | Key                                     | Value                                                                                                                               |
      | workspaceName                           | "user-test"                                                                                                                         |
      | nodesToPublish                          | [{"nodeAggregateId": "sir-david-nodenborough", "contentStreamId": "user-cs-identifier", "dimensionSpacePoint": {}}] |
      | contentStreamIdForRemainingPart | "user-cs-identifier-modified"                                                                                                       |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{}
    And I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{}
    And I expect node aggregate identifier "sir-nodeward-nodington-iii" to lead to no node

    When I am in the active content stream of workspace "user-test" and dimension space point {}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node user-cs-identifier-modified;sir-david-nodenborough;{}
    And I expect node aggregate identifier "nody-mc-nodeface" to lead to node user-cs-identifier-modified;nody-mc-nodeface;{}
    And I expect node aggregate identifier "sir-nodeward-nodington-iii" to lead to node user-cs-identifier-modified;sir-nodeward-nodington-iii;{}


    # @todo check why these won't run

  #Scenario: (ChangeNodeAggregateName) It is possible to publish changing the node name.
  #  Given the command CreateWorkspace is executed with payload:
  #    | Key                        | Value                |
   ##   | workspaceName              | "user-test"          |
   #   | baseWorkspaceName          | "live"               |
   #   | newContentStreamId | "user-cs-identifier" |
   # And the graph projection is fully up to date

    # SETUP: change two node names in USER workspace
    #Given the command "ChangeNodeAggregateName" is executed with payload:
    #  | Key                     | Value                    |
    #  | contentStreamId | "user-cs-identifier"     |
    #  | nodeAggregateId | "sir-david-nodenborough" |
    #  | newNodeName             | "text1mod"               |
    #Given the command "ChangeNodeAggregateName" is executed with payload:
    #  | Key                     | Value                        |
    #  | contentStreamId | "user-cs-identifier"         |
    #  | nodeAggregateId | "sir-nodeward-nodington-iii" |
     # | newNodeName             | "imagemod"                   |
   # And the graph projection is fully up to date

   # When the command PublishIndividualNodesFromWorkspace is executed with payload:
   #   | Key           | Value                                                                                                                               |
   #   | workspaceName | "user-test"                                                                                                                         |
    #  | nodesToPublish | [{"nodeAggregateId": "sir-david-nodenborough", "contentStreamId": "user-cs-identifier", "dimensionSpacePoint": {}}] |
    #And the graph projection is fully up to date

   # When I am in the active content stream of workspace "live" and dimension space point {}
   ## Then I expect the node aggregate "lady-eleonode-rootford" to have the following child nodes:
    #  | Name     | nodeAggregateId    |
    #  | text1mod | sir-david-nodenborough     |
     # | image    | sir-nodeward-nodington-iii |

   # When I am in the active content stream of workspace "user-test" and dimension space point {}
   # Then I expect the node aggregate "lady-eleonode-rootford" to have the following child nodes:
   #   | Name     | nodeAggregateId    |
   #   | text1mod | sir-david-nodenborough     |
   #   | imagemod | sir-nodeward-nodington-iii |


  Scenario: (RemoveNodeAggregate) It is possible to publish a node removal
    Given the command CreateWorkspace is executed with payload:
      | Key                        | Value                |
      | workspaceName              | "user-test"          |
      | baseWorkspaceName          | "live"               |
      | newContentStreamId | "user-cs-identifier" |
    And the graph projection is fully up to date

    # SETUP: remove two nodes in USER workspace
    When the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                    |
      | contentStreamId      | "user-cs-identifier"     |
      | nodeAggregateId      | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {}                       |
      | nodeVariantSelectionStrategy | "allVariants"            |

    When the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | contentStreamId      | "user-cs-identifier"         |
      | nodeAggregateId      | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {}                           |
      | nodeVariantSelectionStrategy | "allVariants"                |
    And the graph projection is fully up to date

    When the command PublishIndividualNodesFromWorkspace is executed with payload:
      | Key                      | Value                                                                                                                               |
      | workspaceName            | "user-test"                                                                                                                         |
      | nodesToPublish           | [{"nodeAggregateId": "sir-david-nodenborough", "contentStreamId": "user-cs-identifier", "dimensionSpacePoint": {}}] |
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
      | newContentStreamId | "user-cs-identifier" |
    And the graph projection is fully up to date

    # SETUP: remove two nodes in USER workspace
    When the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                    |
      | contentStreamId      | "user-cs-identifier"     |
      | nodeAggregateId      | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {}                       |
      | nodeVariantSelectionStrategy | "allVariants"            |
    When the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | contentStreamId      | "user-cs-identifier"         |
      | nodeAggregateId      | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {}                           |
      | nodeVariantSelectionStrategy | "allVariants"                |
    And the graph projection is fully up to date

    When the command PublishIndividualNodesFromWorkspace is executed with payload:
      | Key                      | Value                                                                                                                               |
      | workspaceName            | "user-test"                                                                                                                         |
      | nodesToPublish           | [{"nodeAggregateId": "sir-david-nodenborough", "contentStreamId": "user-cs-identifier", "dimensionSpacePoint": {}}] |
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
      | newContentStreamId | "user-cs-identifier" |
    And the graph projection is fully up to date

    # SETUP: set two node references in USER workspace
    When the command SetNodeReferences is executed with payload:
      | Key                             | Value                                     |
      | contentStreamId         | "user-cs-identifier"                      |
      | sourceNodeAggregateId   | "sir-david-nodenborough"                  |
      | sourceOriginDimensionSpacePoint | {}                                        |
      | referenceName                   | "referenceProperty"                       |
      | references                      | [{"target":"sir-nodeward-nodington-iii"}] |
    And the command SetNodeReferences is executed with payload:
      | Key                             | Value                                     |
      | contentStreamId         | "user-cs-identifier"                      |
      | sourceNodeAggregateId   | "nody-mc-nodeface"                        |
      | sourceOriginDimensionSpacePoint | {}                                        |
      | referenceName                   | "referenceProperty"                       |
      | references                      | [{"target":"sir-nodeward-nodington-iii"}] |
    And the graph projection is fully up to date

    When the command PublishIndividualNodesFromWorkspace is executed with payload:
      | Key                                     | Value                                                                                                                               |
      | workspaceName                           | "user-test"                                                                                                                         |
      | nodesToPublish                          | [{"nodeAggregateId": "sir-david-nodenborough", "contentStreamId": "user-cs-identifier", "dimensionSpacePoint": {}}] |
      | contentStreamIdForRemainingPart | "user-cs-identifier-modified"                                                                                                       |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{}
    And I expect this node to have the following references:
      | Name              | Node                                        | Properties |
      | referenceProperty | cs-identifier;sir-nodeward-nodington-iii;{} | null       |
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{}
    And I expect this node to have no references
    Then I expect node aggregate identifier "sir-nodeward-nodington-iii" to lead to node cs-identifier;sir-nodeward-nodington-iii;{}
    And I expect this node to have no references
    And I expect this node to be referenced by:
      | Name              | Node                                    | Properties |
      | referenceProperty | cs-identifier;sir-david-nodenborough;{} | null       |

    When I am in the active content stream of workspace "user-test" and dimension space point {}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node user-cs-identifier-modified;sir-david-nodenborough;{}
    And I expect this node to have the following references:
      | Name              | Node                                                      | Properties |
      | referenceProperty | user-cs-identifier-modified;sir-nodeward-nodington-iii;{} | null       |
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node user-cs-identifier-modified;nody-mc-nodeface;{}
    And I expect this node to have the following references:
      | Name              | Node                                                      | Properties |
      | referenceProperty | user-cs-identifier-modified;sir-nodeward-nodington-iii;{} | null       |
    Then I expect node aggregate identifier "sir-nodeward-nodington-iii" to lead to node user-cs-identifier-modified;sir-nodeward-nodington-iii;{}
    And I expect this node to be referenced by:
      | Name              | Node                                                  | Properties |
      | referenceProperty | user-cs-identifier-modified;nody-mc-nodeface;{}       | null       |
      | referenceProperty | user-cs-identifier-modified;sir-david-nodenborough;{} | null       |

  Scenario: (CreateNodeAggregateWithNode) It is possible to publish new nodes
    Given the command CreateWorkspace is executed with payload:
      | Key                        | Value                |
      | workspaceName              | "user-test"          |
      | baseWorkspaceName          | "live"               |
      | newContentStreamId | "user-cs-identifier" |
    And the graph projection is fully up to date

    # SETUP: set two new nodes in USER workspace
    When the command CreateNodeAggregateWithNodeAndSerializedProperties is executed with payload:
      | Key                           | Value                                    |
      | contentStreamId       | "user-cs-identifier"                     |
      | nodeAggregateId       | "new1-agg"                               |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Content" |
      | originDimensionSpacePoint     | {}                                       |
      | parentNodeAggregateId | "lady-eleonode-rootford"                 |
      | nodeName                      | "foo"                                    |
    When the command CreateNodeAggregateWithNodeAndSerializedProperties is executed with payload:
      | Key                           | Value                                    |
      | contentStreamId       | "user-cs-identifier"                     |
      | nodeAggregateId       | "new2-agg"                               |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Content" |
      | originDimensionSpacePoint     | {}                                       |
      | parentNodeAggregateId | "lady-eleonode-rootford"                 |
      | nodeName                      | "foo2"                                   |
    And the graph projection is fully up to date

    When the command PublishIndividualNodesFromWorkspace is executed with payload:
      | Key                                     | Value                                                                                                                 |
      | workspaceName                           | "user-test"                                                                                                           |
      | nodesToPublish                          | [{"nodeAggregateId": "new1-agg", "contentStreamId": "user-cs-identifier", "dimensionSpacePoint": {}}] |
      | contentStreamIdForRemainingPart | "user-cs-identifier-modified"                                                                                         |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {}
    Then I expect node aggregate identifier "new1-agg" to lead to node cs-identifier;new1-agg;{}
    Then I expect node aggregate identifier "new2-agg" to lead to no node

    When I am in the active content stream of workspace "user-test" and dimension space point {}
    Then I expect node aggregate identifier "new1-agg" to lead to node user-cs-identifier-modified;new1-agg;{}
    Then I expect node aggregate identifier "new2-agg" to lead to node user-cs-identifier-modified;new2-agg;{}


  # TODO: implement MoveNodeAggregate testcase
  # TODO: implement CreateNodeVariant testcase
