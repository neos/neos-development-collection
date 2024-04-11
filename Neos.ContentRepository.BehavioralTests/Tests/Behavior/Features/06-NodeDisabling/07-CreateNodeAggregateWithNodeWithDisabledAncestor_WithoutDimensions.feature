@contentrepository @adapters=DoctrineDBAL,Postgres
Feature: Creation of nodes underneath disabled nodes

  If we create new nodes underneath of disabled nodes, they must be marked as disabled as well;
  i.e. they must have the proper restriction edges as well.

  These are the test cases without dimensions

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Document': {}
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootWorkspace is executed with payload:
      | Key                        | Value                |
      | workspaceName              | "live"               |
      | workspaceTitle             | "Live"               |
      | workspaceDescription       | "The live workspace" |
      | newContentStreamId | "cs-identifier"      |
    And I am in the active content stream of workspace "live" and dimension space point {}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                     | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName            | "Neos.ContentRepository:Root" |
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId | nodeTypeName                            | parentNodeAggregateId | nodeName |
      | the-great-nodini        | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford        | document |
    And the command DisableNodeAggregate is executed with payload:
      | Key                          | Value              |
      | nodeAggregateId      | "the-great-nodini" |
      | nodeVariantSelectionStrategy | "allVariants"      |

  Scenario: When a new node is created underneath a hidden node, this one should be hidden as well
    When the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId | nodeTypeName                            | parentNodeAggregateId | nodeName     |
      | nodingers-cat           | Neos.ContentRepository.Testing:Document | the-great-nodini              | pet-document |
    Then I expect the node aggregate "nodingers-cat" to exist
    And I expect this node aggregate to disable dimension space points []
    And I expect node aggregate identifier "nodingers-cat" and node path "document/pet-document" to lead to no node

    When the command EnableNodeAggregate is executed with payload:
      | Key                          | Value              |
      | nodeAggregateId      | "the-great-nodini" |
      | nodeVariantSelectionStrategy | "allVariants"      |
    Then I expect node aggregate identifier "nodingers-cat" and node path "document/pet-document" to lead to node cs-identifier;nodingers-cat;{}
    And I expect this node to be a child of node cs-identifier;the-great-nodini;{}
