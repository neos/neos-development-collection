Feature: Matching most specific language
  In order to have translated content with flexible fallback rules
  As an API user of the content repository
  I need a way to retrieve nodes matching an ordered list of languages

  Background:
    Given I have the following nodes:
      | Path                 | Node Type                 | Properties        | Language |
      | /sites               | unstructured              |                   | mul_ZZ |
      | /sites/neosdemotypo3 | TYPO3.Neos.NodeTypes:Page | {"title": "Home"} | mul_ZZ |

  @fixtures
  Scenario: One document node and specific languages
    Given I have the following nodes:
      | Identifier                           | Path                         | Node Type                 | Properties              | Language |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/neosdemotypo3/subpage | TYPO3.Neos.NodeTypes:Page | {"title": "Subpage"}    | en_US  |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/neosdemotypo3/subpage | TYPO3.Neos.NodeTypes:Page | {"title": "Unterseite"} | de_DE  |
    When I get a node by path "/sites/neosdemotypo3/subpage" with the following context:
      | Language        |
      | de_DE, mul_ZZ |
    Then I should have one node
    And The node property "title" should be "Unterseite"
    When I get a node by path "/sites/neosdemotypo3/subpage" with the following context:
      | Language        |
      | en_US, mul_ZZ |
    Then I should have one node
    And The node property "title" should be "Subpage"

  @fixtures
  Scenario: One document node and fallback from specific language to language in list of languages
    Given I have the following nodes:
      | Identifier                           | Path                         | Node Type                 | Properties              | Language |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/neosdemotypo3/subpage | TYPO3.Neos.NodeTypes:Page | {"title": "Subpage"}    | en_ZZ  |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/neosdemotypo3/subpage | TYPO3.Neos.NodeTypes:Page | {"title": "Unterseite"} | de_ZZ  |
    When I get a node by path "/sites/neosdemotypo3/subpage" with the following context:
      | Language               |
      | de_DE, de_ZZ, mul_ZZ |
    Then I should have one node
    And The node language dimension should be "de_ZZ"

  @fixtures
  Scenario: One document node and fallback to most specific language in list of language
    Given I have the following nodes:
      | Identifier                           | Path                         | Node Type                 | Properties                 | Language |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/neosdemotypo3/subpage | TYPO3.Neos.NodeTypes:Page | {"title": "International"} | en_ZZ  |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/neosdemotypo3/subpage | TYPO3.Neos.NodeTypes:Page | {"title": "US"}            | en_US  |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/neosdemotypo3/subpage | TYPO3.Neos.NodeTypes:Page | {"title": "UK"}            | en_UK  |
    When I get a node by path "/sites/neosdemotypo3/subpage" with the following context:
      | Language              |
      | en_UK, en_US, en_ZZ |
    Then I should have one node
    And The node language dimension should be "en_UK"
    When I get a node by path "/sites/neosdemotypo3/subpage" with the following context:
      | Language             |
      | en_US, en_UK, en_ZZ |
    Then I should have one node
    And The node language dimension should be "en_US"

  @fixtures
  Scenario: Multiple child nodes and specific languages
    Given I have the following nodes:
      | Identifier                           | Path                                 | Node Type                     | Properties                 | Language |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/neosdemotypo3/subpage         | TYPO3.Neos.NodeTypes:Page     | {"title": "International"} | mul_ZZ |
      | ecf40ad1-3119-0a43-d02e-55f8b5aa3c70 | /sites/neosdemotypo3/subpage/main/c1 | TYPO3.Neos.NodeTypes:Headline | {"title": "First"}         | en_US  |
      | ecf40ad1-3119-0a43-d02e-55f8b5aa3c70 | /sites/neosdemotypo3/subpage/main/c1 | TYPO3.Neos.NodeTypes:Headline | {"title": "Erstens"}       | de_DE  |
      | 68ca0dcd-2afb-ef0e-1106-a5301e65b8a0 | /sites/neosdemotypo3/subpage/main/c2 | TYPO3.Neos.NodeTypes:Headline | {"title": "Second"}        | en_US  |
      | 68ca0dcd-2afb-ef0e-1106-a5301e65b8a0 | /sites/neosdemotypo3/subpage/main/c2 | TYPO3.Neos.NodeTypes:Headline | {"title": "Zweitens"}      | de_DE  |
      | 418158c7-6096-9243-dc5b-16159e6f15bc | /sites/neosdemotypo3/subpage/main/c3 | TYPO3.Neos.NodeTypes:Headline | {"title": "Third"}         | en_US  |
      | 418158c7-6096-9243-dc5b-16159e6f15bc | /sites/neosdemotypo3/subpage/main/c3 | TYPO3.Neos.NodeTypes:Headline | {"title": "Drittens"}      | de_DE  |
    When I get the child nodes of "/sites/neosdemotypo3/subpage/main" with the following context:
      | Language       |
      | de_DE, mul_ZZ |
    Then I should have 3 nodes
    And I should have the following nodes:
      | Path                                 | Properties            | Language |
      | /sites/neosdemotypo3/subpage/main/c1 | {"title": "Erstens"}  | de_DE   |
      | /sites/neosdemotypo3/subpage/main/c2 | {"title": "Zweitens"} | de_DE   |
      | /sites/neosdemotypo3/subpage/main/c3 | {"title": "Drittens"} | de_DE   |

  @fixtures
  Scenario: Multiple child nodes and fallback from specific language to language in list of languages
    Given I have the following nodes:
      | Identifier                           | Path                                 | Node Type                     | Properties                 | Language |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/neosdemotypo3/subpage         | TYPO3.Neos.NodeTypes:Page     | {"title": "International"} | mul_ZZ |
      | ecf40ad1-3119-0a43-d02e-55f8b5aa3c70 | /sites/neosdemotypo3/subpage/main/c1 | TYPO3.Neos.NodeTypes:Headline | {"title": "First"}         | en_ZZ  |
      | ecf40ad1-3119-0a43-d02e-55f8b5aa3c70 | /sites/neosdemotypo3/subpage/main/c1 | TYPO3.Neos.NodeTypes:Headline | {"title": "Erstens"}       | de_ZZ  |
      | 68ca0dcd-2afb-ef0e-1106-a5301e65b8a0 | /sites/neosdemotypo3/subpage/main/c2 | TYPO3.Neos.NodeTypes:Headline | {"title": "Second"}        | en_ZZ  |
      | 68ca0dcd-2afb-ef0e-1106-a5301e65b8a0 | /sites/neosdemotypo3/subpage/main/c2 | TYPO3.Neos.NodeTypes:Headline | {"title": "Zweitens"}      | de_ZZ  |
      | 418158c7-6096-9243-dc5b-16159e6f15bc | /sites/neosdemotypo3/subpage/main/c3 | TYPO3.Neos.NodeTypes:Headline | {"title": "Third"}         | en_ZZ  |
      | 418158c7-6096-9243-dc5b-16159e6f15bc | /sites/neosdemotypo3/subpage/main/c3 | TYPO3.Neos.NodeTypes:Headline | {"title": "Drittens"}      | de_ZZ  |
      | e0dbe38c-9540-dd4e-7c38-df1518c46311 | /sites/neosdemotypo3/subpage/main/c4 | TYPO3.Neos.NodeTypes:Headline | {"title": "Fourth"}        | en_US  |
    When I get the child nodes of "/sites/neosdemotypo3/subpage/main" with the following context:
      | Language              |
      | en_US, en_ZZ, mul_ZZ |
    Then I should have 4 nodes
    And I should have the following nodes:
      | Path                                 | Properties          | Language |
      | /sites/neosdemotypo3/subpage/main/c1 | {"title": "First"}  | en_ZZ   |
      | /sites/neosdemotypo3/subpage/main/c2 | {"title": "Second"} | en_ZZ   |
      | /sites/neosdemotypo3/subpage/main/c3 | {"title": "Third"}  | en_ZZ   |
      | /sites/neosdemotypo3/subpage/main/c4 | {"title": "Fourth"} | en_US   |

  @fixtures
  Scenario: Multiple child nodes and fallback to most specific language in list of languages
    Given I have the following nodes:
      | Identifier                           | Path                                 | Node Type                     | Properties                        | Language |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/neosdemotypo3/subpage         | TYPO3.Neos.NodeTypes:Page     | {"title": "International"}        | mul_ZZ   |
      | ecf40ad1-3119-0a43-d02e-55f8b5aa3c70 | /sites/neosdemotypo3/subpage/main/c1 | TYPO3.Neos.NodeTypes:Headline | {"title": "First"}                | en_ZZ    |
      | 68ca0dcd-2afb-ef0e-1106-a5301e65b8a0 | /sites/neosdemotypo3/subpage/main/c2 | TYPO3.Neos.NodeTypes:Headline | {"title": "Second international"} | mul_ZZ   |
      | 68ca0dcd-2afb-ef0e-1106-a5301e65b8a0 | /sites/neosdemotypo3/subpage/main/c2 | TYPO3.Neos.NodeTypes:Headline | {"title": "Second"}               | en_ZZ    |
      | 418158c7-6096-9243-dc5b-16159e6f15bc | /sites/neosdemotypo3/subpage/main/c3 | TYPO3.Neos.NodeTypes:Headline | {"title": "Third"}                | en_ZZ    |
      | e0dbe38c-9540-dd4e-7c38-df1518c46311 | /sites/neosdemotypo3/subpage/main/c4 | TYPO3.Neos.NodeTypes:Headline | {"title": "Fourth"}               | en_ZZ    |
      | e0dbe38c-9540-dd4e-7c38-df1518c46311 | /sites/neosdemotypo3/subpage/main/c4 | TYPO3.Neos.NodeTypes:Headline | {"title": "Fourth of US"}         | en_US    |
    When I get the child nodes of "/sites/neosdemotypo3/subpage/main" with the following context:
      | Language              |
      | en_US, en_ZZ, mul_ZZ |
    Then I should have 4 nodes
    And I should have the following nodes:
      | Path                                 | Properties                | Language |
      | /sites/neosdemotypo3/subpage/main/c1 | {"title": "First"}        | en_ZZ   |
      | /sites/neosdemotypo3/subpage/main/c2 | {"title": "Second"}       | en_ZZ   |
      | /sites/neosdemotypo3/subpage/main/c3 | {"title": "Third"}        | en_ZZ   |
      | /sites/neosdemotypo3/subpage/main/c4 | {"title": "Fourth of US"} | en_US   |

  @fixtures
  Scenario: Multiple nodes on path and specific languages
    Given I have the following nodes:
      | Identifier                           | Path                                                         | Node Type                 | Properties                       | Language |
      | a3474e1d-dd60-4a84-82b1-18d2f21891a3 | /sites/neosdemotypo3/features                                | TYPO3.Neos.NodeTypes:Page | {"title": "Features"}            | de_DE    |
      | a3474e1d-dd60-4a84-82b1-18d2f21891a3 | /sites/neosdemotypo3/features                                | TYPO3.Neos.NodeTypes:Page | {"title": "Features"}            | en_US    |
      | 452374b3-3580-2af3-71bd-f9932faea84d | /sites/neosdemotypo3/features/multiple-columns               | TYPO3.Neos.NodeTypes:Page | {"title": "Multiple columns"}    | en_US    |
      | 452374b3-3580-2af3-71bd-f9932faea84d | /sites/neosdemotypo3/features/multiple-columns               | TYPO3.Neos.NodeTypes:Page | {"title": "Mehrspalter"}         | de_DE    |
      | a66ec7db-3459-b67b-7bcb-16e2508a89f0 | /sites/neosdemotypo3/features/navigation-elements            | TYPO3.Neos.NodeTypes:Page | {"title": "Navigationselemente"} | de_DE    |
      | a66ec7db-3459-b67b-7bcb-16e2508a89f0 | /sites/neosdemotypo3/features/navigation-elements            | TYPO3.Neos.NodeTypes:Page | {"title": "Navigation elements"} | en_US    |
      | 7c3e4946-d216-14d0-92c5-d7fa75163863 | /sites/neosdemotypo3/features/navigation-elements/first-item | TYPO3.Neos.NodeTypes:Page | {"title": "Subpage #1"}          | en_US    |
      | 7c3e4946-d216-14d0-92c5-d7fa75163863 | /sites/neosdemotypo3/features/navigation-elements/first-item | TYPO3.Neos.NodeTypes:Page | {"title": "Unterseite #1"}       | de_DE    |
    When I get the nodes on path "/sites/neosdemotypo3/features" to "/sites/neosdemotypo3/features/navigation-elements/first-item" with the following context:
      | Language       |
      | en_US, mul_ZZ   |
    Then I should have 3 nodes
    And I should have the following nodes:
      | Path                                                         | Properties                       | Language |
      | /sites/neosdemotypo3/features                                | {"title": "Features"}            | en_US     |
      | /sites/neosdemotypo3/features/navigation-elements            | {"title": "Navigation elements"} | en_US     |
      | /sites/neosdemotypo3/features/navigation-elements/first-item | {"title": "Subpage #1"}          | en_US     |

  @fixtures
  Scenario: Multiple nodes on path and fallback to most specific language in list of languages
    Given I have the following nodes:
      | Identifier                           | Path                                                         | Node Type                 | Properties                            | Language |
      | a3474e1d-dd60-4a84-82b1-18d2f21891a3 | /sites/neosdemotypo3/features                                | TYPO3.Neos.NodeTypes:Page | {"title": "Features"}                 | mul_ZZ   |
      | 452374b3-3580-2af3-71bd-f9932faea84d | /sites/neosdemotypo3/features/multiple-columns               | TYPO3.Neos.NodeTypes:Page | {"title": "Multiple columns"}         | en_ZZ    |
      | a66ec7db-3459-b67b-7bcb-16e2508a89f0 | /sites/neosdemotypo3/features/navigation-elements            | TYPO3.Neos.NodeTypes:Page | {"title": "Navigation elements"}      | en_US    |
      | a66ec7db-3459-b67b-7bcb-16e2508a89f0 | /sites/neosdemotypo3/features/navigation-elements            | TYPO3.Neos.NodeTypes:Page | {"title": "International navigation"} | en_ZZ    |
      | a66ec7db-3459-b67b-7bcb-16e2508a89f0 | /sites/neosdemotypo3/features/navigation-elements            | TYPO3.Neos.NodeTypes:Page | {"title": "Navigation items"}         | en_UK    |
      | 7c3e4946-d216-14d0-92c5-d7fa75163863 | /sites/neosdemotypo3/features/navigation-elements/first-item | TYPO3.Neos.NodeTypes:Page | {"title": "Subpage #1"}               | en_ZZ    |
    When I get the nodes on path "/sites/neosdemotypo3/features" to "/sites/neosdemotypo3/features/navigation-elements/first-item" with the following context:
      | Language            |
      | en_UK, en_ZZ, mul_ZZ |
    Then I should have 3 nodes
    And I should have the following nodes:
      | Path                                                         | Properties                    | Language |
      | /sites/neosdemotypo3/features                                | {"title": "Features"}         | mul_ZZ   |
      | /sites/neosdemotypo3/features/navigation-elements            | {"title": "Navigation items"} | en_UK    |
      | /sites/neosdemotypo3/features/navigation-elements/first-item | {"title": "Subpage #1"}       | en_ZZ    |

  @fixtures
  Scenario: One document node and specific languages by identifier
    Given I have the following nodes:
      | Identifier                           | Path                         | Node Type                 | Properties              | Language |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/neosdemotypo3/subpage | TYPO3.Neos.NodeTypes:Page | {"title": "Subpage"}    | en_US   |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/neosdemotypo3/subpage | TYPO3.Neos.NodeTypes:Page | {"title": "Unterseite"} | de_DE   |
    When I get a node by identifier "fd5ba6e1-4313-b145-1004-dad2f1173a35" with the following context:
      | Language     |
      | de_DE, mul_ZZ |
    Then I should have one node
    And The node property "title" should be "Unterseite"
    When I get a node by identifier "fd5ba6e1-4313-b145-1004-dad2f1173a35" with the following context:
      | Language     |
      | en_US, mul_ZZ |
    Then I should have one node
    And The node property "title" should be "Subpage"

  @fixtures
  Scenario: One document node and fallback from specific language to language in list of languages by identifier
    Given I have the following nodes:
      | Identifier                           | Path                         | Node Type                 | Properties              | Language |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/neosdemotypo3/subpage | TYPO3.Neos.NodeTypes:Page | {"title": "Subpage"}    | en_ZZ    |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/neosdemotypo3/subpage | TYPO3.Neos.NodeTypes:Page | {"title": "Unterseite"} | de_ZZ    |
    When I get a node by identifier "fd5ba6e1-4313-b145-1004-dad2f1173a35" with the following context:
      | Language            |
      | de_DE, de_ZZ, mul_ZZ |
    Then I should have one node
    And The node language dimension should be "de_ZZ"

  @fixtures
  Scenario: One document node and fallback to most specific language in list of languages by identifier
    Given I have the following nodes:
      | Identifier                           | Path                         | Node Type                 | Properties                 | Language |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/neosdemotypo3/subpage | TYPO3.Neos.NodeTypes:Page | {"title": "International"} | en_ZZ    |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/neosdemotypo3/subpage | TYPO3.Neos.NodeTypes:Page | {"title": "US"}            | en_US    |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/neosdemotypo3/subpage | TYPO3.Neos.NodeTypes:Page | {"title": "UK"}            | en_UK    |
    When I get a node by identifier "fd5ba6e1-4313-b145-1004-dad2f1173a35" with the following context:
      | Language           |
      | en_UK, en_US, en_ZZ |
    Then I should have one node
    And The node language dimension should be "en_UK"
    When I get a node by identifier "fd5ba6e1-4313-b145-1004-dad2f1173a35" with the following context:
      | Language           |
      | en_US, en_UK, en_ZZ |
    Then I should have one node
    And The node language dimension should be "en_US"