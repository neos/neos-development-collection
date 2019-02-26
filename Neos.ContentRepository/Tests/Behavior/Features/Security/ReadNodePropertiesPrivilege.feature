Feature: Privilege to restrict reading of node properties

  Background:
    Given I have the following policies:
    """
    privilegeTargets:

      'Neos\ContentRepository\Security\Authorization\Privilege\Node\ReadNodePropertyPrivilege':

        'Neos.ContentRepository:Service:ReadServiceTextTitles':
          matcher: 'isDescendantNodeOf("/sites/content-repository/service/") && nodeIsOfType("Neos.NodeTypes:Text") && nodePropertyIsIn(["title"])'

        'Neos.ContentRepository:Service:ReadDocumentVisibilityAttributes':
          matcher: 'nodeIsOfType("Neos.ContentRepository.Testing:Document") && nodePropertyIsIn(["hidden", "hiddenBeforeDateTime", "hiddenAfterDateTime", "hiddenInIndex"])'


    roles:
      'Neos.Flow:Everybody':
        privileges: []

      'Neos.Flow:Anonymous':
        privileges: []

      'Neos.Flow:AuthenticatedUser':
        privileges: []

      'Neos.ContentRepository:Administrator':
        privileges:
          -
            privilegeTarget: 'Neos.ContentRepository:Service:ReadServiceTextTitles'
            permission: GRANT
          -
            privilegeTarget: 'Neos.ContentRepository:Service:ReadDocumentVisibilityAttributes'
            permission: GRANT

    """

    And I have the following nodes:
      | Identifier                           | Path                                     | Node Type                               | Properties                    | Workspace |
      | ecf40ad1-3119-0a43-d02e-55f8b5aa3c70 | /sites                                   | unstructured                            |                               | live      |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/content-repository                | Neos.ContentRepository.Testing:Document | {"title": "Home"}             | live      |
      | 68ca0dcd-2afb-ef0e-1106-a5301e65b8a0 | /sites/content-repository/company        | Neos.ContentRepository.Testing:Document | {"title": "Company"}          | live      |
      | 52540602-b417-11e3-9358-14109fd7a2dd | /sites/content-repository/service        | Neos.ContentRepository.Testing:Document | {"title": "Service"}          | live      |
      | 82246a9e-be03-3c06-018d-e4c68103ecd3 | /sites/content-repository/service/teaser | Neos.NodeTypes:Text                     | {"title": "Some Teaser Text"} | live      |


  @Isolated @fixtures
  Scenario: Anonymous users are not granted to get title of service text nodes
    Given I am not authenticated
    And I get a node by path "/sites/content-repository/service/teaser" with the following context:
      | Workspace  |
      | user-admin |
    Then I should not be granted to get the "title" property
    And I should get false when asking the node authorization service if getting the "title" property is granted

  @Isolated @fixtures
  Scenario: Anonymous users are granted to get title of service node
    Given I am not authenticated
    And I get a node by path "/sites/content-repository/service" with the following context:
      | Workspace  |
      | user-admin |
    Then I should be granted to get the "title" property
    And I should get true when asking the node authorization service if getting the "title" property is granted

  @Isolated @fixtures
  Scenario: Administrators are granted to get title of service text nodes
    Given I am authenticated with role "Neos.ContentRepository:Administrator"
    And I get a node by path "/sites/content-repository/service/teaser" with the following context:
      | Workspace  |
      | user-admin |
    Then I should be granted to get the "title" property
    And I should get true when asking the node authorization service if getting the "title" property is granted

  @Isolated @fixtures
  Scenario: Anonymous users are not granted to get visibility options of document nodes
    Given I am not authenticated
    And I get a node by path "/sites/content-repository" with the following context:
      | Workspace  |
      | user-admin |
    Then I should not be granted to get the "hidden" property
    And I should get false when asking the node authorization service if getting the "hidden" property is granted
    And I should not be granted to get the "hiddenBeforeDateTime" property
    And I should get false when asking the node authorization service if getting the "hiddenBeforeDateTime" property is granted
    And I should not be granted to get the "hiddenAfterDateTime" property
    And I should get false when asking the node authorization service if getting the "hiddenAfterDateTime" property is granted
    And I should not be granted to get the "hiddenInIndex" property
    And I should get false when asking the node authorization service if getting the "hiddenInIndex" property is granted
    And I should be granted to get the "name" property
    And I should get true when asking the node authorization service if getting the "name" property is granted
    And I should be granted to get the "accessRoles" property
    And I should get true when asking the node authorization service if getting the "accessRoles" property is granted
