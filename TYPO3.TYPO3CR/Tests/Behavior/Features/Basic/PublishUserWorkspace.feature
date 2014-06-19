Feature: Publish user workspace
  In order to have changes in the live workspace
  As an API user of the content repository
  I need support to publish changes in a workspace

  Background:
    Given I have the following nodes:
      | Identifier                           | Path                 | Node Type                 | Properties        | Workspace |
      | ecf40ad1-3119-0a43-d02e-55f8b5aa3c70 | /sites               | unstructured              |                   | live      |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/neosdemotypo3 | TYPO3.Neos.NodeTypes:Page | {"title": "Home"} | live      |

  @fixtures
  Scenario: Publish a new ContentCollection with Content
    When I create the following nodes:
      | Path                                     | Node Type                      | Properties              | Workspace |
      | /sites/neosdemotypo3/twocol              | TYPO3.Neos.NodeTypes:TwoColumn | {}                      | user-demo |
      | /sites/neosdemotypo3/twocol/column0/text | TYPO3.Neos.NodeTypes:Text      | {"text": "Hello world"} | user-demo |
    And I publish the workspace "user-demo"
    And I get a node by path "/sites/neosdemotypo3/twocol/column0/text" with the following context:
      | Workspace |
      | live      |
    Then I should have one node
