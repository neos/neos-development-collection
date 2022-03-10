@fixtures
Feature: Prevent disconnected Node Variants when moving a document, in a setup with language-dimensions and configured fallbacks

  To understand this tests, you first should have a look at PreventDisconnectedNodesWhenMovingDimensionsWithoutFallbacks
  Those are basically the same tests, but without any configured fallbacks and therefore simpler.

  Let's say we have three language-dimensions: de, ch and at, were both at and ch fallback to de:

  .                      CH ─────►DE◄─────AT

  The content tree looks like this:
  |-- sites
  . |-- cr
  . | |-- subpage
  . | | |-- content
  . |-- other

  In the following tests we always try to move /sites/cr/subpage into /sites/other without
  creating disconnected node-variants (nodes not connected to the root node
  anymore, see https://github.com/neos/neos-development-collection/issues/3384)

  The different scenarios differ in the languages in which /sites/cr/subpage and /sites/other exist.
  We want to test cases where:
  - /sites/cr/subpage exists in exactly the same languages as /sites/other
  - /sites/cr/subpage exists in less languages than /sites/other
  - /sites/cr/subpage exists in more languages than /sites/other
  - /sites/cr/subpage shares only one language with /sites/other
  - /sites/cr/subpage shares only one language with /sites/other which is **NOT** configured as fallback
  - /sites/cr/subpage shares only one language with /sites/other which **IS** configured as fallback
  - /sites/cr/subpage has no common language with /sites/other and the language of the target is **NOT** fallback for the language of the start
  - /sites/cr/subpage has no common language with /sites/other and the language of the target **IS** a fallback for the language of the start

  Background:
    Given I have the following content dimensions:
      | Identifier | Default | Presets                   |
      | language   | de      | de=de; ch=ch,de; at=at,de |
    Given I have the following nodes:
      | Identifier                           | Path      | Node Type                           | Properties            | Language |
      | 86198d18-8c4a-41eb-95fa-56223b2a3a97 | /sites    | unstructured                        |                       | de       |
      | 86198d18-8c4a-41eb-95fa-56223b2a3a97 | /sites    | unstructured                        |                       | ch       |
      | 86198d18-8c4a-41eb-95fa-56223b2a3a97 | /sites    | unstructured                        |                       | at       |
      | 498414ca-d211-4eee-a5ec-f54c056bfc3e | /sites/cr | Neos.ContentRepository.Testing:Page | {"title": "CR SEITE"} | de       |
      | 498414ca-d211-4eee-a5ec-f54c056bfc3e | /sites/cr | Neos.ContentRepository.Testing:Page | {"title": "CR PAGE"}  | ch       |
      | 498414ca-d211-4eee-a5ec-f54c056bfc3e | /sites/cr | Neos.ContentRepository.Testing:Page | {"title": "CR page"}  | at       |
    And I am authenticated with role "Neos.Neos:Editor"

  Scenario:/sites/cr/subpage exists in exactly the same languages as /sites/other => SHOULD WORK
    Given I have the following nodes:
      | Identifier                           | Path                           | Node Type                           | Properties                | Language |
      | 0808f2ef-3430-49a3-908c-c6d41f86eea1 | /sites/cr/subpage              | Neos.ContentRepository.Testing:Page | {"title": "Subpage"}      | at       |
      | 1bb26211-74e8-450d-a4ac-7a01b0f9b21e | /sites/cr/subpage/main/content | Neos.ContentRepository.Testing:Text | {"text": "my text"}       | at       |
      | 0808f2ef-3430-49a3-908c-c6d41f86eea1 | /sites/cr/subpage              | Neos.ContentRepository.Testing:Page | {"title": "Unterseite"}   | de       |
      | 1bb26211-74e8-450d-a4ac-7a01b0f9b21e | /sites/cr/subpage/main/content | Neos.ContentRepository.Testing:Text | {"text": "der text"}      | de       |
      | c3aed33e-bb46-4200-ad85-9c46d7cfa8f8 | /sites/other                   | Neos.ContentRepository.Testing:Page | {"title": "Other page"}   | at       |
      | c3aed33e-bb46-4200-ad85-9c46d7cfa8f8 | /sites/other                   | Neos.ContentRepository.Testing:Page | {"title": "Andere Seite"} | de       |
    When I get a node by path "/sites/cr/subpage" with the following context:
      | Workspace  | Language |
      | user-admin | de       |
    And I move the node into the node with path "/sites/other"

    # Assertions: Nodes are moved to new location
    And I get a node by path "/sites/other/subpage" with the following context:
      | Workspace  | Language |
      | user-admin | de       |
    Then I should have one node
    And the node should be connected to the root
    And I get a node by path "/sites/other/subpage" with the following context:
      | Workspace  | Language |
      | user-admin | at,de    |
    Then I should have one node
    And the node should be connected to the root

    # Assertions: Nodes are not found anymore at old location
    And I get a node by path "/sites/cr/subpage" with the following context:
      | Workspace  | Language |
      | user-admin | de       |
    Then I should have 0 nodes
    And I get a node by path "/sites/cr/subpage" with the following context:
      | Workspace  | Language |
      | user-admin | at,de    |
    Then I should have 0 nodes


  Scenario: /sites/cr/subpage exists in less languages than /sites/other => SHOULD WORK
    Given I have the following nodes:
      | Identifier                           | Path                           | Node Type                           | Properties              | Language |
      | 0808f2ef-3430-49a3-908c-c6d41f86eea1 | /sites/cr/subpage              | Neos.ContentRepository.Testing:Page | {"title": "Subpage"}    | at       |
      | 1bb26211-74e8-450d-a4ac-7a01b0f9b21e | /sites/cr/subpage/main/content | Neos.ContentRepository.Testing:Text | {"text": "my text"}     | at       |
      | c3aed33e-bb46-4200-ad85-9c46d7cfa8f8 | /sites/other                   | Neos.ContentRepository.Testing:Page | {"title": "Other page"} | at       |
      | c3aed33e-bb46-4200-ad85-9c46d7cfa8f8 | /sites/other                   | Neos.ContentRepository.Testing:Page | {"title": "Other page"} | de       |
    When I get a node by path "/sites/cr/subpage" with the following context:
      | Workspace  | Language |
      | user-admin | at,de    |
    And I move the node into the node with path "/sites/other"

    # Assertions: Nodes are moved to new location
    And I get a node by path "/sites/other/subpage" with the following context:
      | Workspace  | Language |
      | user-admin | at,de    |
    Then I should have one node
    And the node should be connected to the root

    # Assertions: Nodes are not found anymore at old location
    And I get a node by path "/sites/cr/subpage" with the following context:
      | Workspace  | Language |
      | user-admin | at,de    |
    Then I should have 0 nodes

  Scenario: /sites/cr/subpage exists in more languages than /sites/other => SHOULD FAIL
    Given I have the following nodes:
      | Identifier                           | Path                           | Node Type                           | Properties              | Language |
      | 0808f2ef-3430-49a3-908c-c6d41f86eea1 | /sites/cr/subpage              | Neos.ContentRepository.Testing:Page | {"title": "Subpage"}    | at       |
      | 1bb26211-74e8-450d-a4ac-7a01b0f9b21e | /sites/cr/subpage/main/content | Neos.ContentRepository.Testing:Text | {"text": "my text"}     | at       |
      | 0808f2ef-3430-49a3-908c-c6d41f86eea1 | /sites/cr/subpage              | Neos.ContentRepository.Testing:Page | {"title": "Unterseite"} | de       |
      | 1bb26211-74e8-450d-a4ac-7a01b0f9b21e | /sites/cr/subpage/main/content | Neos.ContentRepository.Testing:Text | {"text": "der text"}    | de       |
      | c3aed33e-bb46-4200-ad85-9c46d7cfa8f8 | /sites/other                   | Neos.ContentRepository.Testing:Page | {"title": "Other page"} | at       |
    When I get a node by path "/sites/cr/subpage" with the following context:
      | Workspace  | Language |
      | user-admin | at,de    |
    And I move the node into the node with path "/sites/other" and exceptions are caught
    Then the last caught exception should be of type "NodeMoveIntegrityViolationException" with message:
    """
    Node Neos.ContentRepository.Testing:Page (subpage) can not be moved.
    When moving Document Nodes, they are moved across all dimensions.
    For node Neos.ContentRepository.Testing:Page (subpage), we attempted to move it across the following dimensions:
     - language ["de"] (ERROR: Non-Existing Parent)
     - language ["ch","de"] (ERROR: Non-Existing Parent)
     - language ["at","de"]
    """

    # Assertions: Nodes are NOT MOVED AT ALL (i.e. not findable at the target)
    And I get a node by path "/sites/other/subpage" with the following context:
      | Workspace  | Language |
      | user-admin | at,de    |
    Then I should have 0 nodes
    And I get a node by path "/sites/other/subpage" with the following context:
      | Workspace  | Language |
      | user-admin | de       |
    Then I should have 0 nodes

    # Assertions: Nodes are NOT MOVED AT ALL (i.e. still at the original location)
    And I get a node by path "/sites/cr/subpage" with the following context:
      | Workspace  | Language |
      | user-admin | at,de    |
    Then I should have one node
    And the node should be connected to the root
    And I get a node by path "/sites/cr/subpage" with the following context:
      | Workspace  | Language |
      | user-admin | de       |
    Then I should have one node
    And the node should be connected to the root

  Scenario: /sites/cr/subpage shares only one language with /sites/other which is **NOT** configured as fallback => SHOULD FAIL
    Given I have the following nodes:
      | Identifier                           | Path                           | Node Type                           | Properties              | Language |
      | 0808f2ef-3430-49a3-908c-c6d41f86eea1 | /sites/cr/subpage              | Neos.ContentRepository.Testing:Page | {"title": "Subpage"}    | de       |
      | 1bb26211-74e8-450d-a4ac-7a01b0f9b21e | /sites/cr/subpage/main/content | Neos.ContentRepository.Testing:Text | {"text": "my text"}     | de       |
      | 0808f2ef-3430-49a3-908c-c6d41f86eea1 | /sites/cr/subpage              | Neos.ContentRepository.Testing:Page | {"title": "Unterseite"} | at       |
      | 1bb26211-74e8-450d-a4ac-7a01b0f9b21e | /sites/cr/subpage/main/content | Neos.ContentRepository.Testing:Text | {"text": "my text"}     | at       |
      | c3aed33e-bb46-4200-ad85-9c46d7cfa8f8 | /sites/other                   | Neos.ContentRepository.Testing:Page | {"title": "Other page"} | at       |
      | c3aed33e-bb46-4200-ad85-9c46d7cfa8f8 | /sites/other                   | Neos.ContentRepository.Testing:Page | {"title": "Other page"} | ch       |
    When I get a node by path "/sites/cr/subpage" with the following context:
      | Workspace  | Language |
      | user-admin | at,de    |
    And I move the node into the node with path "/sites/other" and exceptions are caught
    Then the last caught exception should be of type "NodeMoveIntegrityViolationException" with message:
    """
    Node Neos.ContentRepository.Testing:Page (subpage) can not be moved.
    When moving Document Nodes, they are moved across all dimensions.
    For node Neos.ContentRepository.Testing:Page (subpage), we attempted to move it across the following dimensions:
     - language ["de"] (ERROR: Non-Existing Parent)
     - language ["ch","de"]
     - language ["at","de"]
    """

    # Assertions: Nodes are NOT MOVED AT ALL (i.e. not findable at the target)
    And I get a node by path "/sites/other/subpage" with the following context:
      | Workspace  | Language |
      | user-admin | de       |
    Then I should have 0 nodes
    And I get a node by path "/sites/other/subpage" with the following context:
      | Workspace  | Language |
      | user-admin | ch,de    |
    Then I should have 0 nodes
    And I get a node by path "/sites/other/subpage" with the following context:
      | Workspace  | Language |
      | user-admin | at,de    |
    Then I should have 0 nodes

    # Assertions: Nodes are NOT MOVED AT ALL (i.e. still at the original location)
    And I get a node by path "/sites/cr/subpage" with the following context:
      | Workspace  | Language |
      | user-admin | de       |
    Then I should have one node
    And the node should be connected to the root
    And I get a node by path "/sites/cr/subpage" with the following context:
      | Workspace  | Language |
      | user-admin | at,de    |
    Then I should have one node
    And the node should be connected to the root

  Scenario: /sites/cr/subpage shares only one language with /sites/other which **IS** configured as fallback => SHOULD WORK
    Given I have the following nodes:
      | Identifier                           | Path                           | Node Type                           | Properties              | Language |
      | 0808f2ef-3430-49a3-908c-c6d41f86eea1 | /sites/cr/subpage              | Neos.ContentRepository.Testing:Page | {"title": "Subpage"}    | de       |
      | 1bb26211-74e8-450d-a4ac-7a01b0f9b21e | /sites/cr/subpage/main/content | Neos.ContentRepository.Testing:Text | {"text": "my text"}     | de       |
      | 0808f2ef-3430-49a3-908c-c6d41f86eea1 | /sites/cr/subpage              | Neos.ContentRepository.Testing:Page | {"title": "Unterseite"} | at       |
      | 1bb26211-74e8-450d-a4ac-7a01b0f9b21e | /sites/cr/subpage/main/content | Neos.ContentRepository.Testing:Text | {"text": "my text"}     | at       |
      | c3aed33e-bb46-4200-ad85-9c46d7cfa8f8 | /sites/other                   | Neos.ContentRepository.Testing:Page | {"title": "Other page"} | de       |
      | c3aed33e-bb46-4200-ad85-9c46d7cfa8f8 | /sites/other                   | Neos.ContentRepository.Testing:Page | {"title": "Other page"} | ch       |
    When I get a node by path "/sites/cr/subpage" with the following context:
      | Workspace  | Language |
      | user-admin | de       |
    And I move the node into the node with path "/sites/other"

    # Assertions: Nodes are moved to new location
    And I get a node by path "/sites/other/subpage" with the following context:
      | Workspace  | Language |
      | user-admin | de       |
    Then I should have one node
    And the node should be connected to the root
    And I get a node by path "/sites/other/subpage" with the following context:
      | Workspace  | Language |
      | user-admin | at,de    |
    Then I should have one node
    And the node should be connected to the root

    # Assertions: Nodes are not found anymore at old location
    And I get a node by path "/sites/cr/subpage" with the following context:
      | Workspace  | Language |
      | user-admin | de       |
    Then I should have 0 nodes
    And I get a node by path "/sites/cr/subpage" with the following context:
      | Workspace  | Language |
      | user-admin | at,de    |
    Then I should have 0 nodes

  # As a editor this error can be triggered when a node is selected, cut and pasted in another dimension
  Scenario: /sites/cr/subpage has no common language with /sites/other and the language of the target is **NOT** fallback for the language of the start => SHOULD FAIL
    Given I have the following nodes:
      | Identifier                           | Path                           | Node Type                           | Properties              | Language |
      | 0808f2ef-3430-49a3-908c-c6d41f86eea1 | /sites/cr/subpage              | Neos.ContentRepository.Testing:Page | {"title": "Subpage"}    | de       |
      | 1bb26211-74e8-450d-a4ac-7a01b0f9b21e | /sites/cr/subpage/main/content | Neos.ContentRepository.Testing:Text | {"text": "my text"}     | de       |
      | c3aed33e-bb46-4200-ad85-9c46d7cfa8f8 | /sites/other                   | Neos.ContentRepository.Testing:Page | {"title": "Other page"} | at       |
    When I get a node by path "/sites/cr/subpage" with the following context:
      | Workspace  | Language |
      | user-admin | de       |
    And I move the node into the node with path "/sites/other" in the following context and exceptions are caught:
      | Workspace  | Language |
      | user-admin | at,de    |
    Then the last caught exception should be of type "NodeMoveIntegrityViolationException" with message:
    """
    Node Neos.ContentRepository.Testing:Page (subpage) can not be moved.
    When moving Document Nodes, they are moved across all dimensions.
    For node Neos.ContentRepository.Testing:Page (subpage), we attempted to move it across the following dimensions:
     - language ["de"] (ERROR: Non-Existing Parent)
     - language ["ch","de"] (ERROR: Non-Existing Parent)
     - language ["at","de"]
    """

    # Assertions: Nodes are NOT MOVED AT ALL (i.e. not findable at the target)
    And I get a node by path "/sites/other/subpage" with the following context:
      | Workspace  | Language |
      | user-admin | at,de    |
    Then I should have 0 nodes
    And I get a node by path "/sites/other/subpage" with the following context:
      | Workspace  | Language |
      | user-admin | de       |
    Then I should have 0 nodes

    # Assertions: Nodes are NOT MOVED AT ALL (i.e. still at the original location)
    And I get a node by path "/sites/cr/subpage" with the following context:
      | Workspace  | Language |
      | user-admin | de       |
    Then I should have one node
    And the node should be connected to the root

  # this happens when the nodes from the fallback language shine through
  Scenario: /sites/cr/subpage has no common language with /sites/other and the language of the target **IS** a fallback for the language of the start => SHOULD WORK
    Given I have the following nodes:
      | Identifier                           | Path                           | Node Type                           | Properties              | Language |
      | 0808f2ef-3430-49a3-908c-c6d41f86eea1 | /sites/cr/subpage              | Neos.ContentRepository.Testing:Page | {"title": "Subpage"}    | at       |
      | 1bb26211-74e8-450d-a4ac-7a01b0f9b21e | /sites/cr/subpage/main/content | Neos.ContentRepository.Testing:Text | {"text": "my text"}     | at       |
      | c3aed33e-bb46-4200-ad85-9c46d7cfa8f8 | /sites/other                   | Neos.ContentRepository.Testing:Page | {"title": "Other page"} | de       |
    When I get a node by path "/sites/cr/subpage" with the following context:
      | Workspace  | Language |
      | user-admin | at,de    |
    And I move the node into the node with path "/sites/other" in the following context:
      | Workspace  | Language |
      | user-admin | de       |

    # Assertions: Nodes are moved to new location
    And I get a node by path "/sites/other/subpage" with the following context:
      | Workspace  | Language |
      | user-admin | at,de    |
    Then I should have one node
    And the node should be connected to the root

    # Assertions: Nodes are not found anymore at old location
    And I get a node by path "/sites/cr/subpage" with the following context:
      | Workspace  | Language |
      | user-admin | at,de    |
    Then I should have 0 nodes
