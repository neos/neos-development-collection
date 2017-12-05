Feature: Multiple languages as content dimension
  In order to have content with multiple content dimensions and fallbacks
  As an API user of the content repository
  I need a way to retrieve nodes matching an ordered list of dimension values

  Background:
    Given I have the following content dimensions:
      | Identifier | Default   |
      | language   | mul_ZZ    |
      | personas   | everybody |
    And I have the following nodes:
      | Path           | Node Type                  | Properties        |
      | /sites         | unstructured               |                   |
      | /sites/content-repository | Neos.ContentRepository.Testing:Page | {"title": "Home"} |
    And I am authenticated with role "Neos.Neos:Editor"

  @fixtures
  Scenario: Get a node from multiple mixed content dimensions
    Given I have the following nodes:
      | Identifier                           | Path                   | Node Type                      | Properties                                   | Language  | Dimension: personas |
      | 68ca0dcd-2afb-ef0e-1106-a5301e65b8a0 | /sites/content-repository/main/c2 | Neos.ContentRepository.Testing:Headline | {"title": "Welcome!"}                        | en_ZZ     | everybody           |
      | 68ca0dcd-2afb-ef0e-1106-a5301e65b8a0 | /sites/content-repository/main/c2 | Neos.ContentRepository.Testing:Headline | {"title": "Welcome, nice to see you again!"} | en_ZZ     | customer            |
      | 68ca0dcd-2afb-ef0e-1106-a5301e65b8a0 | /sites/content-repository/main/c2 | Neos.ContentRepository.Testing:Headline | {"title": "Welcome, fellow customer!"}       | en_ZZ     | frequent_buyer      |
      | 68ca0dcd-2afb-ef0e-1106-a5301e65b8a0 | /sites/content-repository/main/c2 | Neos.ContentRepository.Testing:Headline | {"title": "Willkommen!"}                     | de_ZZ     | everybody           |
      | 68ca0dcd-2afb-ef0e-1106-a5301e65b8a0 | /sites/content-repository/main/c2 | Neos.ContentRepository.Testing:Headline | {"title": "Willkommen, lieber Kunde!"}       | de_ZZ     | frequent_buyer      |

    When I get a node by path "/sites/content-repository/main/c2" with the following context:
      | Language               | Dimension: personas |
      | de_DE, de_ZZ, mul_ZZ   | everybody           |
    Then I should have one node
    And the node property "title" should be "Willkommen!"

    When I get a node by path "/sites/content-repository/main/c2" with the following context:
      | Language               | Dimension: personas       |
      | de_DE, de_ZZ, mul_ZZ   | frequent_buyer, everybody |
    Then I should have one node
    And the node property "title" should be "Willkommen, lieber Kunde!"

    When I get a node by path "/sites/content-repository/main/c2" with the following context:
      | Language               | Dimension: personas |
      | en_US, en_ZZ, mul_ZZ   | everybody           |
    Then I should have one node
    And the node property "title" should be "Welcome!"

    When I get a node by path "/sites/content-repository/main/c2" with the following context:
      | Language               | Dimension: personas       |
      | en_US, en_ZZ, mul_ZZ   | frequent_buyer, everybody |
    Then I should have one node
    And the node property "title" should be "Welcome, fellow customer!"

    When I get a node by path "/sites/content-repository/main/c2" with the following context:
      | Language               | Dimension: personas |
      | de_DE, de_ZZ, mul_ZZ   | customer, everybody |
    Then I should have one node
    And the node property "title" should be "Willkommen!"

    When I get a node by path "/sites/content-repository/main/c2" with the following context:
      | Language               | Dimension: personas |
      | en_US, en_ZZ, mul_ZZ   | customer, everybody |
    Then I should have one node
    And the node property "title" should be "Welcome, nice to see you again!"
