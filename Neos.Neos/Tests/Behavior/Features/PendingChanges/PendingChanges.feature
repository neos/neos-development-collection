@flowEntities
Feature: Tests for the "Neos.Neos:ContentCase" Fusion prototype

  Background:
    Given using the following content dimensions:
      | Identifier | Values                      | Generalizations                      |
      | example    | general, source, spec, peer | spec->source->general, peer->general |

    And using the following node types:
    """yaml
    'Neos.ContentRepository:Root':
      abstract: true
    'Neos.Neos:Sites':
      superTypes:
        'Neos.ContentRepository:Root': true
    'Neos.Neos:Document':
      abstract: true
    'Neos.Neos:Content':
      abstract: true
    'Neos.Neos:ContentCollection':
      constraints:
        nodeTypes:
          'Neos.Neos:Document': false
          '*': true
    'Neos.Neos:Site':
      superTypes:
        'Neos.Neos:Document': true
    'Neos.Neos:Test.WebPage':
      superTypes:
        'Neos.Neos:Document': true
      childNodes:
        main:
          type: 'Neos.Neos:ContentCollection'
    'Neos.Neos:Test.Text':
      superTypes:
        'Neos.Neos:Content': true
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And I am user identified by "initiating-user-identifier"
    When the command CreateRootWorkspace is executed with payload:
      | Key                | Value   |
      | workspaceName      | "live"  |
      | newContentStreamId | "cs-id" |
    And the command CreateWorkspace is executed with payload:
      | Key                | Value          |
      | workspaceName      | "review"       |
      | baseWorkspaceName  | "live"         |
      | newContentStreamId | "review-cs-id" |
    And the graph projection is fully up to date
    And the command CreateWorkspace is executed with payload:
      | Key                | Value        |
      | workspaceName      | "user"       |
      | baseWorkspaceName  | "review"     |
      | newContentStreamId | "user-cs-id" |
    And the graph projection is fully up to date
    And I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                    |
      | nodeAggregateId | "lady-eleonode-rootford" |
      | nodeTypeName    | "Neos.Neos:Sites"        |
    And the graph projection is fully up to date
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId            | parentNodeAggregateId  | nodeTypeName           | tetheredDescendantNodeAggregateIds |
      | sir-david-sitenborough     | lady-eleonode-rootford | Neos.Neos:Site         | {}                                 |
      | sir-nodeward-nodington-iii | sir-david-sitenborough | Neos.Neos:Test.WebPage | {"main": "nodimus-prime}           |
      | nody-mc-nodeface           | sir-david-sitenborough | Neos.Neos:Test.WebPage | {"main": "nodimus-mediocre}        |

  Scenario: Initial state
    Then I expect the ancestry to be exactly as follows:

  Scenario: Create a new site / document / content node - only on non-leaf workspace

  Scenario: Create a new generalization / peer / (restored) specialization variant- only on non-leaf workspace

  Scenario: Set node properties- only on non-leaf workspace

  Scenario: Set node references: expected: Only source- only on non-leaf workspace

  Scenario: Set subtree tags: - only on non-leaf workspace

  Scenario: Remove site / document / content node - only on non-leaf workspace

  Scenario: Move a node to a new parent, which is a different / the same document / a different / the same site with all strategies - only on non-leaf workspace

  Scenario: rename a site / document / content node - only on non-leaf workspace

  Scenario: change node type, especially among site / document / content

  Scenario: Rebase - expected: copy from base workspace;

  Scenario: Remove workspace - expected: removal of copy

  Scenario: Discard / discard site / discard document

  Scenario: Publish / publish site / publish document

  Scenario: structure adjustments
