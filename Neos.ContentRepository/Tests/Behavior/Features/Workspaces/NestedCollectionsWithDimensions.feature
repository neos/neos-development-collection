Feature: Nested content collections with dimensions
  In order to cut and paste nodes across pages
  As an API user of the content repository
  I need support to publish and move nodes and child nodes considering dimensions

  # see https://github.com/neos/neos-development-collection/issues/1696
  Background:
    Given I am authenticated with role "Neos.Neos:Administrator"
    And I have the following nodes:
      | Identifier                           | Path                                        | Node Type                                | Properties          | Workspace | Language |
      | 9f9e6856-6a73-44f8-99a9-605d6b996e2a | /sites                                      | unstructured                             |                     | live      | mul_ZZ   |
      | f3ab0e95-6bca-400d-86ed-d698873358e1 | /sites/neos                                 | Neos.ContentRepository.Testing:Page      | {"title": "Home"}   | live      | mul_ZZ   |
      | 175d7f66-7942-4010-ad05-60ddb0bac128 | /sites/neos/source                          | Neos.ContentRepository.Testing:Page      | {"title": "Source"} | live      | en_US    |
      | d43d63e5-a0d2-4552-9cf9-8a0f358c66a9 | /sites/neos/source/main/outer               | Neos.ContentRepository.Testing:TwoColumn |                     | live      | en_US    |
      | 9357ea23-1447-4e6e-a23e-9d8d4ecb0bd8 | /sites/neos/source/main/outer/column0/inner | Neos.ContentRepository.Testing:TwoColumn |                     | live      | en_US    |
      | 6d10d3ec-3cbe-40b6-a8e2-e918ab902bb6 | /sites/neos/source                          | Neos.ContentRepository.Testing:Page      | {"title": "Source"} | live      | de_DE    |
      | 8a8a7eb7-a3fe-4dd6-baad-421aa10bab6a | /sites/neos/source/main/outer               | Neos.ContentRepository.Testing:TwoColumn |                     | live      | de_DE    |
      | 731c2dfa-febf-41f4-a13d-0ea14f6e25ef | /sites/neos/source/main/outer/column0/inner | Neos.ContentRepository.Testing:TwoColumn |                     | live      | de_DE    |
      | 6aae28a6-6296-4505-8a16-a756dd632803 | /sites/neos/target                          | Neos.ContentRepository.Testing:Page      | {"title": "Target"} | live      | en_US    |
    And I have the following workspaces:
      | Name       | Base Workspace |
      | user-admin | live           |

  @fixtures
  Scenario: Cut and paste into different page of same language

    When I get a node by path "/sites/neos/source/main/outer/column0/inner" with the following context:
      | Workspace  | Language |
      | user-admin | en_US    |
    Then I should have one node
    And I move the node into the node with path "/sites/neos/target/main"
    When I get the child nodes of "/sites/neos/source/main/outer/column0/inner" with the following context:
      | Workspace | Language |
      | live      | de_DE    |
    Then I should have 2 nodes

    And I publish the workspace "user-admin"
    When I get the child nodes of "/sites/neos/target/main/inner" with the following context:
      | Workspace | Language |
      | live      | en_US    |
    Then I should have 2 nodes
    And I get the child nodes of "/sites/neos/source/main/outer/column0" with the following context:
      | Workspace | Language |
      | live      | en_US    |
    Then I should have 0 nodes
    When I get the child nodes of "/sites/neos/source/main/outer/column0/inner" with the following context:
      | Workspace | Language |
      | live      | de_DE    |
    Then I should have 2 nodes
