Feature: Prevent disconnected Node Variants when moving a document, in a setup with dimensions

  Let's say we have the following Node structure in the beginning:

  en -> de
  fr -> de

  |-- sites (de, fr) <-- en is shinethrough to de
  . |-- cr  (de, fr) <-- en is shinethrough to de
  . | |-- subpage (de, en, fr) <-- fr, en is shinethrough to de
  . | | |-- content1 (de) <-- shines through in en, fr as well.
  . |-- other  (en, fr)

  Now, the user moves /site/cr/subpage underneath /site/other/ in the user workspace.

  Until July 2021, this leads to the following situation / bug:

  |-- sites (de, en)
  . |-- cr  (de, en)
  . |-- other  (de)
  . | |-- subpage (de, en) <----- this page is DISCONNECTED in EN (as the parent page only exists in DE).

  We could:
  - prevent the move completely
  - ask the editor (though it's a difficult to put question, and hard to understand for editors as well)
  - relax our idea that document nodes across dimensions must ALWAYS move in sync.

  We suggest to go the 3rd way, because that is closest to what the user would expect. This would lead
  to the following tree:

  |-- sites (de, en)
  . |-- cr  (de, en)
  .   |-- subpage (en)
  .     |-- content2 (en)
  . |-- other  (de)
  .   |-- subpage (de)   user-demo
  .     |-- content1 (de)

  Now, when moving "subpage" underneath /site (which exists in "de" AND "en"), then we would expect BOTH variants again be moved:
  |-- site (de, en)
  . |-- subpage (de, en)   user-demo
  . |-- cr  (de, en)
  . |-- other  (de)

  ## FALLBACKS

  The problem gets more difficult when DIMENSION FALLBACKS are active: Because we only know based on a fallback
  whether a node becomes disconnected or not.

  For an example, let's say "en" falls back to "de" in the example above; then the initial structure looks as follows:

  |-- sites (de, en)
  . |-- cr  (de, en)
  . | |-- subpage (de, en)
  . |-- other  (de) <-- also visible in "en" now (because of fallbacks).

  In this case, it is ALLOWED to move "subpage" underneath "other" because it is still fully reachable in all dimension presets.



  Background:
    Given I have the following nodes:
      | Identifier                           | Path              | Node Type                           | Properties                | Language |
      | 86198d18-8c4a-41eb-95fa-56223b2a3a97 | /sites            | unstructured                        |                           | de       |
      | 86198d18-8c4a-41eb-95fa-56223b2a3a97 | /sites            | unstructured                        |                           | en       |
      | 498414ca-d211-4eee-a5ec-f54c056bfc3e | /sites/cr         | Neos.ContentRepository.Testing:Page | {"title": "Startseite"}   | de       |
      | 498414ca-d211-4eee-a5ec-f54c056bfc3e | /sites/cr         | Neos.ContentRepository.Testing:Page | {"title": "Home"}         | en       |
      | 0808f2ef-3430-49a3-908c-c6d41f86eea1 | /sites/cr/subpage | Neos.ContentRepository.Testing:Page | {"title": "Unterseite"}   | de       |
      | 0808f2ef-3430-49a3-908c-c6d41f86eea1 | /sites/cr/subpage | Neos.ContentRepository.Testing:Page | {"title": "Subpage"}      | en       |
      | c3aed33e-bb46-4200-ad85-9c46d7cfa8f8 | /sites/other      | Neos.ContentRepository.Testing:Page | {"title": "Andere Seite"} | de       |
    And I am authenticated with role "Neos.Neos:Editor"

  @fixtures
  Scenario: Move subpage across dimensions
    When I get a node by path "/sites/cr/subpage" with the following context:
      | Workspace  | Language |
      | user-admin | de       |
    And I move the node into the node with path "/sites/other"


    And I get a node by path "/sites/other/subpage" with the following context:
      | Workspace  | Language |
      | user-admin | de       |
    Then I should have one node
    And the node should be connected to the root

    # OLD BEHAVIOR: a disconnected node (underneath a parent node)
    And I get a node by path "/sites/other/subpage" with the following context:
      | Workspace  | Language |
      | user-admin | en       |
    Then I should have one node
    And the node should be connected to the root


    And I get a node by path "/sites/cr/subpage" with the following context:
      | Workspace  | Language |
      | user-admin | en       |
    Then I should have one node
    And the node should be connected to the root
