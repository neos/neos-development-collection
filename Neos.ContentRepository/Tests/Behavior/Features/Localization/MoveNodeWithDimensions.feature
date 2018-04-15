Feature: Move node with dimension support
  In order to move nodes
  As an API user of the content repository
  I need support to move nodes and child nodes considering dimensions; moving all nodes across dimensions consistently (for aggregate nodes).

  Background:
    Given I have the following nodes:
      | Identifier                           | Path                              | Node Type                           | Properties                    | Workspace | Language |
      | 85f17826-64d1-11e4-a6e3-14109fd7a2dd | /sites                            | unstructured                        |                               | live      | mul_ZZ   |
      | 8952d7b2-64d1-11e4-9fe2-14109fd7a2dd | /sites/content-repository         | Neos.ContentRepository.Testing:Page | {"title": "Home"}             | live      | en       |
      | 8ed74376-64d1-11e4-b98b-14109fd7a2dd | /sites/content-repository/company | Neos.ContentRepository.Testing:Page | {"title": "Company"}          | live      | en       |
      | 9315622e-64d1-11e4-a28c-14109fd7a2dd | /sites/content-repository/service | Neos.ContentRepository.Testing:Page | {"title": "Service"}          | live      | en       |
      | 8952d7b2-64d1-11e4-9fe2-14109fd7a2dd | /sites/content-repository         | Neos.ContentRepository.Testing:Page | {"title": "Startseite"}       | live      | de       |
      | 8ed74376-64d1-11e4-b98b-14109fd7a2dd | /sites/content-repository/company | Neos.ContentRepository.Testing:Page | {"title": "Die Firma"}        | live      | de       |
      | 9315622e-64d1-11e4-a28c-14109fd7a2dd | /sites/content-repository/service | Neos.ContentRepository.Testing:Page | {"title": "Dienstleistungen"} | live      | de       |
    And I am authenticated with role "Neos.Neos:Editor"

  @fixtures
  Scenario: Moving an aggregate node (Document) in user workspace should move across all dimensions; making sure the live workspace is unaffected
    When I get a node by path "/sites/content-repository/service" with the following context:
      | Workspace  | Language |
      | user-admin | en       |
    And I move the node into the node with path "/sites/content-repository/company"
    And I get a node by path "/sites/content-repository/company/service" with the following context:
      | Workspace  | Language |
      | user-admin | en       |
    Then I should have one node
    And I get a node by path "/sites/content-repository/service" with the following context:
      | Workspace  | Language |
      | user-admin | en       |
    Then I should have 0 nodes

    # different dimension
    And I get a node by path "/sites/content-repository/company/service" with the following context:
      | Workspace  | Language |
      | user-admin | de       |
    Then I should have one node
    And I get a node by path "/sites/content-repository/service" with the following context:
      | Workspace  | Language |
      | user-admin | de       |
    Then I should have 0 nodes

    # make sure the live workspace is unaffected
    And I get a node by path "/sites/content-repository/service" with the following context:
      | Workspace  | Language |
      | live       | en       |
    Then I should have one node
    And I get a node by path "/sites/content-repository/service" with the following context:
      | Workspace  | Language |
      | live       | de       |
    Then I should have one node
    And I get a node by path "/sites/content-repository/company/service" with the following context:
      | Workspace  | Language |
      | live       | en       |
    Then I should have 0 nodes
    And I get a node by path "/sites/content-repository/company/service" with the following context:
      | Workspace  | Language |
      | live       | de       |
    Then I should have 0 nodes

  @fixtures
  Scenario: Moving an aggregate node (Document) with a partially translated subtree should move across all dimensions
    Given I have the following nodes:
      | Identifier                           | Path                                        | Node Type                           | Properties             | Workspace | Language |
      | 9de83f6c-6596-11e4-b3aa-14109fd7a2dd | /sites/content-repository/service/downloads | Neos.ContentRepository.Testing:Page | {"title": "Downloads"} | live      | en       |
    When I get a node by path "/sites/content-repository/service" with the following context:
      | Workspace  | Language |
      | user-admin | de       |
    And I move the node into the node with path "/sites/content-repository/company"
    And I get a node by path "/sites/content-repository/company/service/downloads" with the following context:
      | Workspace  | Language |
      | user-admin | en       |
    Then I should have one node
    And I get a node by path "/sites/content-repository/service/downloads" with the following context:
      | Workspace  | Language |
      | user-admin | en       |
    Then I should have 0 nodes

  @fixtures
  Scenario: Moving an aggregate node (Document) with a partially translated subtree with fallbacks should move across all dimensions
    Given I have the following nodes:
      | Identifier                           | Path                                                | Node Type                           | Properties             | Workspace | Language |
      | 8952d7b2-64d1-11e4-9fe2-14109fd7a2dd | /sites/content-repository                           | Neos.ContentRepository.Testing:Page | {"title": "Home"}      | live      | mul_ZZ   |
      | 9315622e-64d1-11e4-a28c-14109fd7a2dd | /sites/content-repository/service                   | Neos.ContentRepository.Testing:Page | {"title": "Service"}   | live      | mul_ZZ   |
      | 9de83f6c-6596-11e4-b3aa-14109fd7a2dd | /sites/content-repository/service/downloads         | Neos.ContentRepository.Testing:Page | {"title": "Downloads"} | live      | mul_ZZ   |
      | 4e80336e-65c1-11e4-8f8f-14109fd7a2dd | /sites/content-repository/service/downloads/drivers | Neos.ContentRepository.Testing:Page | {"title": "Drivers"}   | live      | en       |
    When I get a node by path "/sites/content-repository/service" with the following context:
      | Workspace  | Language   |
      | user-admin | de, mul_ZZ |
    And I move the node into the node with path "/sites/content-repository/company"
    And I get a node by path "/sites/content-repository/company/service/downloads/drivers" with the following context:
      | Workspace  | Language |
      | user-admin | en       |
    Then I should have one node
    And I get a node by path "/sites/content-repository/service/downloads/drivers" with the following context:
      | Workspace  | Language |
      | user-admin | en       |
    Then I should have 0 nodes

  @fixtures
  Scenario: Moving an aggregate node (Document) in user workspace should move across all dimensions after being published to live workspace
    When I get a node by path "/sites/content-repository/service" with the following context:
      | Workspace  | Language |
      | user-admin | en       |
    And I move the node into the node with path "/sites/content-repository/company"
    And I publish the workspace "user-admin"

    And I get a node by path "/sites/content-repository/company/service" with the following context:
      | Workspace | Language |
      | live      | en       |
    Then I should have one node
    And I get a node by path "/sites/content-repository/company/service" with the following context:
      | Workspace | Language |
      | live      | de       |
    Then I should have one node

  @fixtures
  Scenario: Moving a non-aggregate node (Content) in a workspace should be independent from other dimensions
    Given I have the following nodes:
      | Identifier                           | Path                                                         | Properties | Node Type                                | Language |
      | ebfa51b2-64f2-11e4-be8f-14109fd7a2dd | /sites/content-repository/company/main/text0                 |            | Neos.ContentRepository.Testing:Text      | en       |
      | ebfa51b2-64f2-11e4-be8f-14109fd7a2dd | /sites/content-repository/company/main/text0                 |            | Neos.ContentRepository.Testing:Text      | de       |
      | 74fe032a-6442-11e4-8135-14109fd7a2dd | /sites/content-repository/company/main/two-col               |            | Neos.ContentRepository.Testing:TwoColumn | en       |
      | 74fe032a-6442-11e4-8135-14109fd7a2dd | /sites/content-repository/company/main/two-col               |            | Neos.ContentRepository.Testing:TwoColumn | de       |
      | 864b6a8c-6442-11e4-8791-14109fd7a2dd | /sites/content-repository/company/main/two-col/column0/text1 |            | Neos.ContentRepository.Testing:Text      | en       |
      | 864b6a8c-6442-11e4-8791-14109fd7a2dd | /sites/content-repository/company/main/two-col/column0/text1 |            | Neos.ContentRepository.Testing:Text      | de       |
    When I get a node by path "/sites/content-repository/company/main/text0" with the following context:
      | Workspace  | Language   |
      | user-admin | de, mul_ZZ |
    And I move the node before the node with path "/sites/content-repository/company/main/two-col/column0/text1"
    And I get a node by path "/sites/content-repository/company/main/two-col/column0/text0" with the following context:
      | Workspace  | Language   |
      | user-admin | de, mul_ZZ |
    Then I should have one node
    When I get a node by path "/sites/content-repository/company/main/text0" with the following context:
      | Workspace  | Language   |
      | user-admin | en, mul_ZZ |
    Then I should have one node
    When I get a node by path "/sites/content-repository/company/main/text0" with the following context:
      | Workspace  | Language   |
      | user-admin | de, mul_ZZ |
    Then I should have 0 nodes
    When I get a node by path "/sites/content-repository/company/main/two-col/column0/text0" with the following context:
      | Workspace  | Language   |
      | user-admin | en, mul_ZZ |
    Then I should have 0 nodes

  @fixtures
  Scenario: Re-odering a non-aggregate node (Content) in a workspace should be independent from other dimensions
    Given I have the following nodes:
      | Identifier                           | Path                                         | Properties | Node Type                           | Language |
      | ebfa51b2-64f2-11e4-be8f-14109fd7a2dd | /sites/content-repository/company/main/text0 |            | Neos.ContentRepository.Testing:Text | en       |
      | ebfa51b2-64f2-11e4-be8f-14109fd7a2dd | /sites/content-repository/company/main/text0 |            | Neos.ContentRepository.Testing:Text | de       |
      | 3d9d597c-6509-11e4-9b97-14109fd7a2dd | /sites/content-repository/company/main/text1 |            | Neos.ContentRepository.Testing:Text | en       |
      | 3d9d597c-6509-11e4-9b97-14109fd7a2dd | /sites/content-repository/company/main/text1 |            | Neos.ContentRepository.Testing:Text | de       |
    When I get a node by path "/sites/content-repository/company/main/text0" with the following context:
      | Workspace  | Language   |
      | user-admin | de, mul_ZZ |
    And I move the node after the node with path "/sites/content-repository/company/main/text1"
    When I get the child nodes of "/sites/content-repository/company/main" with the following context:
      | Workspace  | Language   |
      | user-admin | de, mul_ZZ |
    Then I should have the following nodes:
      | Path                              |
      | /sites/content-repository/company/main/text1 |
      | /sites/content-repository/company/main/text0 |
    When I get the child nodes of "/sites/content-repository/company/main" with the following context:
      | Workspace  | Language   |
      | user-admin | en, mul_ZZ |
    Then I should have the following nodes:
      | Path                              |
      | /sites/content-repository/company/main/text0 |
      | /sites/content-repository/company/main/text1 |

  @fixtures
  Scenario: When a node is moved, this node's node path should change
    This is needed inside Neos, when moving nodes inside the Node Tree, the system has to return
    the updated Node Path to the User Interface. Thus, this test effectively checks whether
    NodeInterface->getPath() can be used to retrieve the updated path after node moving.

    When I get a node by path "/sites/content-repository/service" with the following context:
      | Workspace  | Language |
      | user-admin | en       |
    And I move the node into the node with path "/sites/content-repository/company"
    Then I should have the following nodes:
      | Path                           |
      | /sites/content-repository/company/service |

  @fixtures
  Scenario: When a node is moved, this node's node path should change, even with fallback dimensions configured
    See the description of the scenario above why this feature is important for Neos. This reproduces bug NEOS-1652.

    When I get a node by path "/sites/content-repository/service" with the following context:
      | Workspace  | Language |
      | user-admin | en, de   |
    And I move the node into the node with path "/sites/content-repository/company"
    Then I should have the following nodes:
      | Path                           |
      | /sites/content-repository/company/service |
