Feature: Empty dimension values act as a fallback
  In order to have nodes without knowledge about particular dimensions
  As an API user of the content repository
  I need a way to access nodes without dimension values

  Background:
    Given I have the following content dimensions:
      | Identifier | Default   |
      | language   | en_US     |
      | personas   | everybody |
    And I have the following nodes:
      | Identifier                           | Path           | Node Type                  | Properties        | Dimension: language | Target dimension: language | Dimension: personas | Target dimension: personas |
      # Intentionally the /sites node should not have any dimension value assigned!
      | 0befa678-79ad-11e5-b465-14109fd7a2dd | /sites         | unstructured               |                   |                     |                            |                     |                            |
      | 17046dc8-79ad-11e5-9fef-14109fd7a2dd | /sites/content-repository | Neos.ContentRepository.Testing:Page | {"title": "Home"} | en_US               | en_US                      | specialist          | specialist                 |
    And I am authenticated with role "Neos.Neos:Editor"

  @fixtures
  Scenario: Node variant with empty dimension values is found
    When I get a node by path "/sites" with the following context:
      | Dimension: language | Dimension: personas |
      | de_DE               | everybody           |
    Then I should have one node

    When I get a node by path "/sites/content-repository" with the following context:
      | Dimension: language | Dimension: personas |
      | de_DE               | everybody           |
    Then I should have 0 nodes

  @fixtures
  Scenario: Node variant with empty dimension values has least priority
    Given I have the following nodes:
      | Identifier                           | Path           | Node Type                  | Properties                | Target dimension: language | Target dimension: personas |
      | 17046dc8-79ad-11e5-9fef-14109fd7a2dd | /sites/content-repository | Neos.ContentRepository.Testing:Page | {"title": "Default Home"} |                            |                            |

    When I get a node by path "/sites/content-repository" with the following context:
      | Dimension: language | Dimension: personas |
      | de_DE               | everybody           |
    Then I should have one node
    And the node property "title" should be "Default Home"

    When I get a node by path "/sites/content-repository" with the following context:
      | Dimension: language | Dimension: personas   |
      | de_DE, en_US        | specialist, everybody |
    Then I should have one node
    And the node property "title" should be "Home"
