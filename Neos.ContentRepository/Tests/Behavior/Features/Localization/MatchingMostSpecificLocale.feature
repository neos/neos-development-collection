Feature: Matching most specific language
  In order to have translated content with flexible fallback rules
  As an API user of the content repository
  I need a way to retrieve nodes matching an ordered list of languages

  Background:
    Given I have the following nodes:
      | Path           | Node Type                  | Properties        | Language |
      | /sites         | unstructured               |                   | mul_ZZ   |
      | /sites/content-repository | Neos.ContentRepository.Testing:Page | {"title": "Home"} | mul_ZZ   |
    And I am authenticated with role "Neos.Neos:Editor"

  @fixtures
  Scenario: One document node and specific languages
    Given I have the following nodes:
      | Identifier                           | Path                   | Node Type                  | Properties              | Language |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/content-repository/subpage | Neos.ContentRepository.Testing:Page | {"title": "Subpage"}    | en_US    |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/content-repository/subpage | Neos.ContentRepository.Testing:Page | {"title": "Unterseite"} | de_DE    |
    When I get a node by path "/sites/content-repository/subpage" with the following context:
      | Language      |
      | de_DE, mul_ZZ |
    Then I should have one node
    And the node property "title" should be "Unterseite"
    When I get a node by path "/sites/content-repository/subpage" with the following context:
      | Language        |
      | en_US, mul_ZZ |
    Then I should have one node
    And the node property "title" should be "Subpage"

  @fixtures
  Scenario: One document node and fallback from specific language to language in list of languages
    Given I have the following nodes:
      | Identifier                           | Path                   | Node Type                  | Properties              | Language |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/content-repository/subpage | Neos.ContentRepository.Testing:Page | {"title": "Subpage"}    | en_ZZ    |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/content-repository/subpage | Neos.ContentRepository.Testing:Page | {"title": "Unterseite"} | de_ZZ    |
    When I get a node by path "/sites/content-repository/subpage" with the following context:
      | Language             |
      | de_DE, de_ZZ, mul_ZZ |
    Then I should have one node
    And The node language dimension should be "de_ZZ"

  @fixtures
  Scenario: One document node and fallback to most specific language in list of language
    Given I have the following nodes:
      | Identifier                           | Path                   | Node Type                  | Properties                 | Language |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/content-repository/subpage | Neos.ContentRepository.Testing:Page | {"title": "International"} | en_ZZ    |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/content-repository/subpage | Neos.ContentRepository.Testing:Page | {"title": "US"}            | en_US    |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/content-repository/subpage | Neos.ContentRepository.Testing:Page | {"title": "UK"}            | en_UK    |
    When I get a node by path "/sites/content-repository/subpage" with the following context:
      | Language            |
      | en_UK, en_US, en_ZZ |
    Then I should have one node
    And The node language dimension should be "en_UK"
    When I get a node by path "/sites/content-repository/subpage" with the following context:
      | Language            |
      | en_US, en_UK, en_ZZ |
    Then I should have one node
    And The node language dimension should be "en_US"

  @fixtures
  Scenario: Multiple child nodes and specific languages
    Given I have the following nodes:
      | Identifier                           | Path                           | Node Type                      | Properties                 | Language |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/content-repository/subpage         | Neos.ContentRepository.Testing:Page     | {"title": "International"} | mul_ZZ   |
      | ecf40ad1-3119-0a43-d02e-55f8b5aa3c70 | /sites/content-repository/subpage/main/c1 | Neos.ContentRepository.Testing:Headline | {"title": "First"}         | en_US    |
      | ecf40ad1-3119-0a43-d02e-55f8b5aa3c70 | /sites/content-repository/subpage/main/c1 | Neos.ContentRepository.Testing:Headline | {"title": "Erstens"}       | de_DE    |
      | 68ca0dcd-2afb-ef0e-1106-a5301e65b8a0 | /sites/content-repository/subpage/main/c2 | Neos.ContentRepository.Testing:Headline | {"title": "Second"}        | en_US    |
      | 68ca0dcd-2afb-ef0e-1106-a5301e65b8a0 | /sites/content-repository/subpage/main/c2 | Neos.ContentRepository.Testing:Headline | {"title": "Zweitens"}      | de_DE    |
      | 418158c7-6096-9243-dc5b-16159e6f15bc | /sites/content-repository/subpage/main/c3 | Neos.ContentRepository.Testing:Headline | {"title": "Third"}         | en_US    |
      | 418158c7-6096-9243-dc5b-16159e6f15bc | /sites/content-repository/subpage/main/c3 | Neos.ContentRepository.Testing:Headline | {"title": "Drittens"}      | de_DE    |
    When I get the child nodes of "/sites/content-repository/subpage/main" with the following context:
      | Language      |
      | de_DE, mul_ZZ |
    Then I should have 3 nodes
    And I should have the following nodes:
      | Path                           | Properties            | Language |
      | /sites/content-repository/subpage/main/c1 | {"title": "Erstens"}  | de_DE    |
      | /sites/content-repository/subpage/main/c2 | {"title": "Zweitens"} | de_DE    |
      | /sites/content-repository/subpage/main/c3 | {"title": "Drittens"} | de_DE    |

  @fixtures
  Scenario: Multiple child nodes and fallback from specific language to language in list of languages
    Given I have the following nodes:
      | Identifier                           | Path                           | Node Type                      | Properties                 | Language |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/content-repository/subpage         | Neos.ContentRepository.Testing:Page     | {"title": "International"} | mul_ZZ   |
      | ecf40ad1-3119-0a43-d02e-55f8b5aa3c70 | /sites/content-repository/subpage/main/c1 | Neos.ContentRepository.Testing:Headline | {"title": "First"}         | en_ZZ    |
      | ecf40ad1-3119-0a43-d02e-55f8b5aa3c70 | /sites/content-repository/subpage/main/c1 | Neos.ContentRepository.Testing:Headline | {"title": "Erstens"}       | de_ZZ    |
      | 68ca0dcd-2afb-ef0e-1106-a5301e65b8a0 | /sites/content-repository/subpage/main/c2 | Neos.ContentRepository.Testing:Headline | {"title": "Second"}        | en_ZZ    |
      | 68ca0dcd-2afb-ef0e-1106-a5301e65b8a0 | /sites/content-repository/subpage/main/c2 | Neos.ContentRepository.Testing:Headline | {"title": "Zweitens"}      | de_ZZ    |
      | 418158c7-6096-9243-dc5b-16159e6f15bc | /sites/content-repository/subpage/main/c3 | Neos.ContentRepository.Testing:Headline | {"title": "Third"}         | en_ZZ    |
      | 418158c7-6096-9243-dc5b-16159e6f15bc | /sites/content-repository/subpage/main/c3 | Neos.ContentRepository.Testing:Headline | {"title": "Drittens"}      | de_ZZ    |
      | e0dbe38c-9540-dd4e-7c38-df1518c46311 | /sites/content-repository/subpage/main/c4 | Neos.ContentRepository.Testing:Headline | {"title": "Fourth"}        | en_US    |
    When I get the child nodes of "/sites/content-repository/subpage/main" with the following context:
      | Language             |
      | en_US, en_ZZ, mul_ZZ |
    Then I should have 4 nodes
    And I should have the following nodes:
      | Path                           | Properties          | Language |
      | /sites/content-repository/subpage/main/c1 | {"title": "First"}  | en_ZZ    |
      | /sites/content-repository/subpage/main/c2 | {"title": "Second"} | en_ZZ    |
      | /sites/content-repository/subpage/main/c3 | {"title": "Third"}  | en_ZZ    |
      | /sites/content-repository/subpage/main/c4 | {"title": "Fourth"} | en_US    |

  @fixtures
  Scenario: Multiple child nodes and fallback to most specific language in list of languages
    Given I have the following nodes:
      | Identifier                           | Path                           | Node Type                      | Properties                        | Language |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/content-repository/subpage         | Neos.ContentRepository.Testing:Page     | {"title": "International"}        | mul_ZZ   |
      | ecf40ad1-3119-0a43-d02e-55f8b5aa3c70 | /sites/content-repository/subpage/main/c1 | Neos.ContentRepository.Testing:Headline | {"title": "First"}                | en_ZZ    |
      | 68ca0dcd-2afb-ef0e-1106-a5301e65b8a0 | /sites/content-repository/subpage/main/c2 | Neos.ContentRepository.Testing:Headline | {"title": "Second international"} | mul_ZZ   |
      | 68ca0dcd-2afb-ef0e-1106-a5301e65b8a0 | /sites/content-repository/subpage/main/c2 | Neos.ContentRepository.Testing:Headline | {"title": "Second"}               | en_ZZ    |
      | 418158c7-6096-9243-dc5b-16159e6f15bc | /sites/content-repository/subpage/main/c3 | Neos.ContentRepository.Testing:Headline | {"title": "Third"}                | en_ZZ    |
      | e0dbe38c-9540-dd4e-7c38-df1518c46311 | /sites/content-repository/subpage/main/c4 | Neos.ContentRepository.Testing:Headline | {"title": "Fourth"}               | en_ZZ    |
      | e0dbe38c-9540-dd4e-7c38-df1518c46311 | /sites/content-repository/subpage/main/c4 | Neos.ContentRepository.Testing:Headline | {"title": "Fourth of US"}         | en_US    |
    When I get the child nodes of "/sites/content-repository/subpage/main" with the following context:
      | Language             |
      | en_US, en_ZZ, mul_ZZ |
    Then I should have 4 nodes
    And I should have the following nodes:
      | Path                           | Properties                | Language |
      | /sites/content-repository/subpage/main/c1 | {"title": "First"}        | en_ZZ    |
      | /sites/content-repository/subpage/main/c2 | {"title": "Second"}       | en_ZZ    |
      | /sites/content-repository/subpage/main/c3 | {"title": "Third"}        | en_ZZ    |
      | /sites/content-repository/subpage/main/c4 | {"title": "Fourth of US"} | en_US    |

  @fixtures
  Scenario: Multiple nodes on path and specific languages
    Given I have the following nodes:
      | Identifier                           | Path                                                   | Node Type                  | Properties                       | Language |
      | a3474e1d-dd60-4a84-82b1-18d2f21891a3 | /sites/content-repository/features                                | Neos.ContentRepository.Testing:Page | {"title": "Features"}            | de_DE    |
      | a3474e1d-dd60-4a84-82b1-18d2f21891a3 | /sites/content-repository/features                                | Neos.ContentRepository.Testing:Page | {"title": "Features"}            | en_US    |
      | 452374b3-3580-2af3-71bd-f9932faea84d | /sites/content-repository/features/multiple-columns               | Neos.ContentRepository.Testing:Page | {"title": "Multiple columns"}    | en_US    |
      | 452374b3-3580-2af3-71bd-f9932faea84d | /sites/content-repository/features/multiple-columns               | Neos.ContentRepository.Testing:Page | {"title": "Mehrspalter"}         | de_DE    |
      | a66ec7db-3459-b67b-7bcb-16e2508a89f0 | /sites/content-repository/features/navigation-elements            | Neos.ContentRepository.Testing:Page | {"title": "Navigationselemente"} | de_DE    |
      | a66ec7db-3459-b67b-7bcb-16e2508a89f0 | /sites/content-repository/features/navigation-elements            | Neos.ContentRepository.Testing:Page | {"title": "Navigation elements"} | en_US    |
      | 7c3e4946-d216-14d0-92c5-d7fa75163863 | /sites/content-repository/features/navigation-elements/first-item | Neos.ContentRepository.Testing:Page | {"title": "Subpage #1"}          | en_US    |
      | 7c3e4946-d216-14d0-92c5-d7fa75163863 | /sites/content-repository/features/navigation-elements/first-item | Neos.ContentRepository.Testing:Page | {"title": "Unterseite #1"}       | de_DE    |
    When I get the nodes on path "/sites/content-repository/features" to "/sites/content-repository/features/navigation-elements/first-item" with the following context:
      | Language      |
      | en_US, mul_ZZ |
    Then I should have 3 nodes
    And I should have the following nodes:
      | Path                                                   | Properties                       | Language |
      | /sites/content-repository/features                                | {"title": "Features"}            | en_US    |
      | /sites/content-repository/features/navigation-elements            | {"title": "Navigation elements"} | en_US    |
      | /sites/content-repository/features/navigation-elements/first-item | {"title": "Subpage #1"}          | en_US    |

  @fixtures
  Scenario: Multiple nodes on path and fallback to most specific language in list of languages
    Given I have the following nodes:
      | Identifier                           | Path                                                   | Node Type                  | Properties                            | Language |
      | a3474e1d-dd60-4a84-82b1-18d2f21891a3 | /sites/content-repository/features                                | Neos.ContentRepository.Testing:Page | {"title": "Features"}                 | mul_ZZ   |
      | 452374b3-3580-2af3-71bd-f9932faea84d | /sites/content-repository/features/multiple-columns               | Neos.ContentRepository.Testing:Page | {"title": "Multiple columns"}         | en_ZZ    |
      | a66ec7db-3459-b67b-7bcb-16e2508a89f0 | /sites/content-repository/features/navigation-elements            | Neos.ContentRepository.Testing:Page | {"title": "Navigation elements"}      | en_US    |
      | a66ec7db-3459-b67b-7bcb-16e2508a89f0 | /sites/content-repository/features/navigation-elements            | Neos.ContentRepository.Testing:Page | {"title": "International navigation"} | en_ZZ    |
      | a66ec7db-3459-b67b-7bcb-16e2508a89f0 | /sites/content-repository/features/navigation-elements            | Neos.ContentRepository.Testing:Page | {"title": "Navigation items"}         | en_UK    |
      | 7c3e4946-d216-14d0-92c5-d7fa75163863 | /sites/content-repository/features/navigation-elements/first-item | Neos.ContentRepository.Testing:Page | {"title": "Subpage #1"}               | en_ZZ    |
    When I get the nodes on path "/sites/content-repository/features" to "/sites/content-repository/features/navigation-elements/first-item" with the following context:
      | Language             |
      | en_UK, en_ZZ, mul_ZZ |
    Then I should have 3 nodes
    And I should have the following nodes:
      | Path                                                   | Properties                    | Language |
      | /sites/content-repository/features                                | {"title": "Features"}         | mul_ZZ   |
      | /sites/content-repository/features/navigation-elements            | {"title": "Navigation items"} | en_UK    |
      | /sites/content-repository/features/navigation-elements/first-item | {"title": "Subpage #1"}       | en_ZZ    |

  @fixtures
  Scenario: One document node and specific languages by identifier
    Given I have the following nodes:
      | Identifier                           | Path                   | Node Type                  | Properties              | Language |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/content-repository/subpage | Neos.ContentRepository.Testing:Page | {"title": "Subpage"}    | en_US    |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/content-repository/subpage | Neos.ContentRepository.Testing:Page | {"title": "Unterseite"} | de_DE    |
    When I get a node by identifier "fd5ba6e1-4313-b145-1004-dad2f1173a35" with the following context:
      | Language      |
      | de_DE, mul_ZZ |
    Then I should have one node
    And the node property "title" should be "Unterseite"
    When I get a node by identifier "fd5ba6e1-4313-b145-1004-dad2f1173a35" with the following context:
      | Language      |
      | en_US, mul_ZZ |
    Then I should have one node
    And the node property "title" should be "Subpage"

  @fixtures
  Scenario: One document node and fallback from specific language to language in list of languages by identifier
    Given I have the following nodes:
      | Identifier                           | Path                   | Node Type                  | Properties              | Language |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/content-repository/subpage | Neos.ContentRepository.Testing:Page | {"title": "Subpage"}    | en_ZZ    |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/content-repository/subpage | Neos.ContentRepository.Testing:Page | {"title": "Unterseite"} | de_ZZ    |
    When I get a node by identifier "fd5ba6e1-4313-b145-1004-dad2f1173a35" with the following context:
      | Language             |
      | de_DE, de_ZZ, mul_ZZ |
    Then I should have one node
    And The node language dimension should be "de_ZZ"

  @fixtures
  Scenario: One document node and fallback to most specific language in list of languages by identifier
    Given I have the following nodes:
      | Identifier                           | Path                   | Node Type                  | Properties                 | Language |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/content-repository/subpage | Neos.ContentRepository.Testing:Page | {"title": "International"} | en_ZZ    |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/content-repository/subpage | Neos.ContentRepository.Testing:Page | {"title": "US"}            | en_US    |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/content-repository/subpage | Neos.ContentRepository.Testing:Page | {"title": "UK"}            | en_UK    |
    When I get a node by identifier "fd5ba6e1-4313-b145-1004-dad2f1173a35" with the following context:
      | Language            |
      | en_UK, en_US, en_ZZ |
    Then I should have one node
    And The node language dimension should be "en_UK"
    When I get a node by identifier "fd5ba6e1-4313-b145-1004-dad2f1173a35" with the following context:
      | Language            |
      | en_US, en_UK, en_ZZ |
    Then I should have one node
    And The node language dimension should be "en_US"
