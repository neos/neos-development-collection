Feature: Multiple locales as content dimension
  In order to have content with multiple content dimensions and fallbacks
  As an API user of the content repository
  I need a way to retrieve nodes matching an ordered list of dimension values

  Background:
    Given I have the following content dimensions:
      | Identifier | Default   |
      | locales    | mul_ZZ    |
      | personas   | everybody |
    And I have the following nodes:
      | Path                 | Node Type                 | Properties        |
      | /sites               | unstructured              |                   |
      | /sites/neosdemotypo3 | TYPO3.Neos.NodeTypes:Page | {"title": "Home"} |

  @fixtures
  Scenario: Get a node from multiple mixed content dimensions
    Given I have the following nodes:
      | Identifier                           | Path                         | Node Type                     | Properties                                   | Locales | Dimension: personas |
      | 68ca0dcd-2afb-ef0e-1106-a5301e65b8a0 | /sites/neosdemotypo3/main/c2 | TYPO3.Neos.NodeTypes:Headline | {"title": "Welcome!"}                        | en_ZZ   | everybody           |
      | 68ca0dcd-2afb-ef0e-1106-a5301e65b8a0 | /sites/neosdemotypo3/main/c2 | TYPO3.Neos.NodeTypes:Headline | {"title": "Welcome, nice to see you again!"} | en_ZZ   | customer            |
      | 68ca0dcd-2afb-ef0e-1106-a5301e65b8a0 | /sites/neosdemotypo3/main/c2 | TYPO3.Neos.NodeTypes:Headline | {"title": "Welcome, fellow customer!"}       | en_ZZ   | frequent_buyer      |
      | 68ca0dcd-2afb-ef0e-1106-a5301e65b8a0 | /sites/neosdemotypo3/main/c2 | TYPO3.Neos.NodeTypes:Headline | {"title": "Willkommen!"}                     | de_ZZ   | everybody           |
      | 68ca0dcd-2afb-ef0e-1106-a5301e65b8a0 | /sites/neosdemotypo3/main/c2 | TYPO3.Neos.NodeTypes:Headline | {"title": "Willkommen, lieber Kunde!"}       | de_ZZ   | frequent_buyer      |

    When I get a node by path "/sites/neosdemotypo3/main/c2" with the following context:
      | Locales              | Dimension: personas |
      | de_DE, de_ZZ, mul_ZZ | everybody           |
    Then I should have one node
    And The node property "title" should be "Willkommen!"

    When I get a node by path "/sites/neosdemotypo3/main/c2" with the following context:
      | Locales              | Dimension: personas       |
      | de_DE, de_ZZ, mul_ZZ | frequent_buyer, everybody |
    Then I should have one node
    And The node property "title" should be "Willkommen, lieber Kunde!"

    When I get a node by path "/sites/neosdemotypo3/main/c2" with the following context:
      | Locales              | Dimension: personas |
      | en_US, en_ZZ, mul_ZZ | everybody           |
    Then I should have one node
    And The node property "title" should be "Welcome!"

    When I get a node by path "/sites/neosdemotypo3/main/c2" with the following context:
      | Locales              | Dimension: personas       |
      | en_US, en_ZZ, mul_ZZ | frequent_buyer, everybody |
    Then I should have one node
    And The node property "title" should be "Welcome, fellow customer!"

    When I get a node by path "/sites/neosdemotypo3/main/c2" with the following context:
      | Locales              | Dimension: personas |
      | de_DE, de_ZZ, mul_ZZ | customer, everybody |
    Then I should have one node
    And The node property "title" should be "Willkommen!"

    When I get a node by path "/sites/neosdemotypo3/main/c2" with the following context:
      | Locales              | Dimension: personas |
      | en_US, en_ZZ, mul_ZZ | customer, everybody |
    Then I should have one node
    And The node property "title" should be "Welcome, nice to see you again!"
