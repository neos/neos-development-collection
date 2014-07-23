Feature: Multiple languages as content dimension
  In order to have content with multiple content dimensions and fallbacks
  As an API user of the content repository
  I need a way to retrieve nodes matching an ordered list of dimension values

  Background:
    Given I have the following nodes:
      | Path                 | Node Type                 | Properties        |
      | /sites               | unstructured              |                   |
      | /sites/neosdemotypo3 | TYPO3.Neos.NodeTypes:Page | {"title": "Home"} |

  @fixtures
  Scenario: Assign multiple values to language content dimension for a node variant
    Given I have the following nodes:
      | Identifier                           | Path                         | Node Type                     | Properties                  | Language    |
      | 68ca0dcd-2afb-ef0e-1106-a5301e65b8a0 | /sites/neosdemotypo3/main/c2 | TYPO3.Neos.NodeTypes:Headline | {"title": "Swiss content"}  | fr_CH,de_CH |
      | 68ca0dcd-2afb-ef0e-1106-a5301e65b8a0 | /sites/neosdemotypo3/main/c2 | TYPO3.Neos.NodeTypes:Headline | {"title": "German content"} | de_ZZ       |
    When I get a node by path "/sites/neosdemotypo3/main/c2" with the following context:
      | Language      |
      | de_CH, mul_ZZ |
    Then I should have one node
    And The node property "title" should be "Swiss content"
    When I get a node by path "/sites/neosdemotypo3/main/c2" with the following context:
      | Language      |
      | fr_CH, mul_ZZ |
    Then I should have one node
    And The node property "title" should be "Swiss content"
    When I get a node by path "/sites/neosdemotypo3/main/c2" with the following context:
      | Language      |
      | de_ZZ, mul_ZZ |
    Then I should have one node
    And The node property "title" should be "German content"
