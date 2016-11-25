# This feature contains scenarios also available in CR but need to be run against
# the Neos.Neos PublishingService too
Feature: Publish user workspace
  In order to have changes in the live workspace
  As an API user of the content repository
  I need support to publish changes in a workspace

  Background:
    Given I have the following nodes:
      | Identifier                           | Path                 | Node Type                 | Properties        | Workspace |
      | ecf40ad1-3119-0a43-d02e-55f8b5aa3c70 | /sites               | unstructured              |                   | live      |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/example       | Neos.Neos.NodeTypes:Page | {"title": "Home"} | live      |

  @fixtures
  Scenario: Publish a new ContentCollection with Content
    Given I am authenticated with role "Neos.Neos:Editor"
    When I create the following nodes:
      | Path                                     | Node Type                      | Properties              | Workspace |
      | /sites/example/main/twocol               | Neos.Neos.NodeTypes:TwoColumn | {}                      | user-demo |
      | /sites/example/main/twocol/column0/text  | Neos.Neos.NodeTypes:Text      | {"text": "Hello world"} | user-demo |
    And I publish the workspace "user-demo"
    And I get a node by path "/sites/example/main/twocol/column0/text" with the following context:
      | Workspace |
      | live      |
    Then I should have one node

  @fixtures
  Scenario: Unpublished nodes returns the correct count before publish
    Given I am authenticated with role "Neos.Neos:Editor"
    And I create the following nodes:
      | Path                                     | Node Type                      | Properties              | Workspace |
      | /sites/example/main/twocol               | Neos.Neos.NodeTypes:TwoColumn | {}                      | user-demo |
      | /sites/example/main/twocol/column0/text  | Neos.Neos.NodeTypes:Text      | {"text": "Hello world"} | user-demo |
    # We expect 4, the 2 column element with 2 columns (3) and the text element (1)
    Then I expect to have 4 unpublished nodes for the following context:
      | Workspace |
      | user-demo |

  @fixtures
  Scenario: Unpublished nodes returns the correct count after publish
    Given I am authenticated with role "Neos.Neos:Editor"
    And I create the following nodes:
      | Path                                     | Node Type                      | Properties              | Workspace |
      | /sites/example/main/twocol              | Neos.Neos.NodeTypes:TwoColumn | {}                      | user-demo |
      | /sites/example/main/twocol/column0/text | Neos.Neos.NodeTypes:Text      | {"text": "Hello world"} | user-demo |
    And I publish the workspace "user-demo"
    Then I expect to have 0 unpublished nodes for the following context:
      | Workspace |
      | user-demo |

  @fixtures
  Scenario: Unpublished nodes will return an empty array for the live workspace
    Then I expect to have 0 unpublished nodes for the following context:
      | Workspace |
      | live      |
