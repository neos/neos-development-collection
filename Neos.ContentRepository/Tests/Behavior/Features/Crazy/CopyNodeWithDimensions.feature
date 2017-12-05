Feature: Copy node with dimension support
  In order to copy nodes
  As an API user of the content repository
  I need support to copy non-aggregate nodes to other dimensions, keeping the links across dimensions.

  Background:
    Given I have the following nodes:
      | Identifier                           | Path                                              | Node Type                       | Properties                 | Workspace | Language |
      | 85f17826-64d1-11e4-a6e3-14109fd7a2dd | /sites                                            | unstructured                    |                            | live      | mul_ZZ   |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/content-repository                                    | Neos.ContentRepository.Testing:Page      | {"title": "Home"}          | live      | mul_ZZ   |
      | 8ed74376-64d1-11e4-b98b-14109fd7a2dd | /sites/content-repository/company                            | Neos.ContentRepository.Testing:Page      | {"title": "Company"}       | live      | en       |
      | 8ed74376-64d1-11e4-b98b-14109fd7a2dd | /sites/content-repository/company                            | Neos.ContentRepository.Testing:Page      | {"title": "Firma"}         | live      | de       |
      | 74fe032a-6442-11e4-8135-14109fd7a2dd | /sites/content-repository/company/main/two-col               | Neos.ContentRepository.Testing:TwoColumn |                            | live      | en       |
      | 74fe032a-6442-11e4-8135-14109fd7a2dd | /sites/content-repository/company/main/two-col               | Neos.ContentRepository.Testing:TwoColumn |                            | live      | de       |
      | 864b6a8c-6442-11e4-8791-14109fd7a2dd | /sites/content-repository/company/main/two-col/column0/text0 | Neos.ContentRepository.Testing:Text      | {"text": "The Company"}    | live      | en       |
      | 0c1a50e9-3db5-4c57-a5c7-6cc0b7649ee7 | /sites/content-repository/about-us                           | Neos.ContentRepository.Testing:Page      | {"title": "About Us"}      | live      | en       |
      | 1c063cf4-65ca-11e4-b79a-14109fd7a2dd | /sites/content-repository/about-us/main/text0                | Neos.ContentRepository.Testing:Text      | {"text": "Infos about us"} | live      | en       |

    And I am authenticated with role "Neos.Neos:Editor"

  @fixtures
  Scenario: Copying a non-aggregate node creates a node variant in the other dimension if the node does not exist in the target dimension.
    When I get a node by path "/sites/content-repository/company/main/two-col/column0/text0" with the following context:
      | Language   | Workspace  |
      | en, mul_ZZ | user-admin |
    And I copy the node into path "/sites/content-repository/company/main/two-col/column1" with the following context:
      | Language   | Workspace  |
      | de, mul_ZZ | user-admin |
    When I get a node by path "/sites/content-repository/company/main/two-col/column1/text0-1" with the following context:
      | Language   | Workspace  |
      | de, mul_ZZ | user-admin |
    Then I should have one node
    When I get a node by identifier "864b6a8c-6442-11e4-8791-14109fd7a2dd" with the following context:
      | Language   | Workspace  |
      | de, mul_ZZ | user-admin |
    Then I should have one node


  @fixtures
  Scenario: Copying a non-aggregate node does a detached copy if the node exists already in the target dimension
    Given I have the following nodes:
      | Identifier                           | Path                                              | Node Type                       | Properties              | Workspace  | Language |
      | 864b6a8c-6442-11e4-8791-14109fd7a2dd | /sites/content-repository/company/main/two-col/column1/text0 | Neos.ContentRepository.Testing:Text      | {"text": "Die Firma"}   | user-admin | de       |
    When I get a node by identifier "864b6a8c-6442-11e4-8791-14109fd7a2dd" with the following context:
      | Language   | Workspace  |
      | de, mul_ZZ | user-admin |
    Then I should have one node
    When I get a node by path "/sites/content-repository/company/main/two-col/column0/text0" with the following context:
      | Language   | Workspace  |
      | en, mul_ZZ | user-admin |
    And I copy the node into path "/sites/content-repository/company/main" with the following context:
      | Language   | Workspace  |
      | de, mul_ZZ | user-admin |
    When I get a node by path "/sites/content-repository/company/main/text0-1" with the following context:
      | Language   | Workspace  |
      | de, mul_ZZ | user-admin |
    Then I should have one node
    And the node property "text" should be "The Company"

  @fixtures
  Scenario: Copying an aggregate node does a detached copy for the aggregate and all children as well, so they have a different identity.
    When I get a node by path "/sites/content-repository/about-us" with the following context:
      | Language   | Workspace  |
      | en, mul_ZZ | user-admin |
    And I copy the node into path "/sites/content-repository/company" with the following context:
      | Language   | Workspace  |
      | de, mul_ZZ | user-admin |
    When I get a node by path "/sites/content-repository/company/about-us-1" with the following context:
      | Language   | Workspace  |
      | de, mul_ZZ | user-admin |
    Then I should have one node
    When I get a node by identifier "0c1a50e9-3db5-4c57-a5c7-6cc0b7649ee7" with the following context:
      | Language   | Workspace  |
      | de, mul_ZZ | user-admin |
    Then I should have 0 nodes
    When I get a node by path "/sites/content-repository/company/about-us-1/main/text0" with the following context:
      | Language   | Workspace  |
      | de, mul_ZZ | user-admin |
    Then I should have one node
    When I get a node by identifier "1c063cf4-65ca-11e4-b79a-14109fd7a2dd" with the following context:
      | Language   | Workspace  |
      | de, mul_ZZ | user-admin |
    Then I should have 0 nodes
