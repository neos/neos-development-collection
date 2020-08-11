Feature: Privilege to restrict editing of node properties

  Background:
    Given I have the following policies:
    """
    privilegeTargets:

      'Neos\ContentRepository\Security\Authorization\Privilege\Node\EditNodePropertyPrivilege':

        'Neos.ContentRepository:Service:EditServiceTextTitles':
          matcher: 'isDescendantNodeOf("/sites/content-repository/service/") && nodeIsOfType("Neos.NodeTypes:Text") && nodePropertyIsIn("title")'

        'Neos.ContentRepository:Service:EditDocumentVisibilityAttributes':
          matcher: 'nodeIsOfType("Neos.ContentRepository.Testing:Document") && nodePropertyIsIn(["hidden", "hiddenBeforeDateTime", "hiddenAfterDateTime", "hiddenInIndex"])'

        'Neos.ContentRepository:Service:HideServiceTeaser':
          matcher: 'isDescendantNodeOf("82246a9e-be03-3c06-018d-e4c68103ecd3") && nodePropertyIsIn("hidden")'

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
            privilegeTarget: 'Neos.ContentRepository:Service:EditServiceTextTitles'
            permission: GRANT
          -
            privilegeTarget: 'Neos.ContentRepository:Service:EditDocumentVisibilityAttributes'
            permission: GRANT
          -
            privilegeTarget: 'Neos.ContentRepository:Service:HideServiceTeaser'
            permission: GRANT
    """

    And I have the following nodes:
      | Identifier                           | Path                                     | Node Type                               | Properties                                         | Workspace |
      | ecf40ad1-3119-0a43-d02e-55f8b5aa3c70 | /sites                                   | unstructured                            |                                                    | live      |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/content-repository                | Neos.ContentRepository.Testing:Document | {"title": "Home"}                                  | live      |
      | 68ca0dcd-2afb-ef0e-1106-a5301e65b8a0 | /sites/content-repository/company        | Neos.ContentRepository.Testing:Document | {"title": "Company"}                               | live      |
      | 52540602-b417-11e3-9358-14109fd7a2dd | /sites/content-repository/service        | Neos.ContentRepository.Testing:Document | {"title": "Service"}                               | live      |
      | 82246a9e-be03-3c06-018d-e4c68103ecd3 | /sites/content-repository/service/teaser | Neos.NodeTypes:Text                     | {"title": "Some Teaser Text", "text": "Some Text"} | live      |


  @Isolated @fixtures
  Scenario: Anonymous users are not granted to set title of service text nodes
    Given I am not authenticated
    And I get a node by path "/sites/content-repository/service/teaser" with the following context:
      | Workspace  |
      | user-admin |
    Then I should not be granted to set the "title" property to "NewTitle"
    And I should get false when asking the node authorization service if setting the "title" property is granted

  @Isolated @fixtures
  Scenario: Anonymous users are granted to set title of service node
    Given I am not authenticated
    And I get a node by path "/sites/content-repository/service" with the following context:
      | Workspace  |
      | user-admin |
    Then I should be granted to set the "title" property to "NewTitle"
    And I should get true when asking the node authorization service if setting the "title" property is granted

  @Isolated @fixtures
  Scenario: Administrators are granted to set title of service text nodes
    Given I am authenticated with role "Neos.ContentRepository:Administrator"
    And I get a node by path "/sites/content-repository/service/teaser" with the following context:
      | Workspace  |
      | user-admin |
    Then I should be granted to set the "title" property to "NewTitle"
    And I should get true when asking the node authorization service if setting the "title" property is granted

  @Isolated @fixtures
  Scenario: Anonymous users are not granted to set visibility options of document nodes
    Given I am not authenticated
    And I get a node by path "/sites/content-repository" with the following context:
      | Workspace  |
      | user-admin |
    Then I should not be granted to set the "hidden" property to "true"
    And I should get false when asking the node authorization service if setting the "hidden" property is granted
    And I should not be granted to set the "hiddenBeforeDateTime" property to "2015-03-24"
    And I should get false when asking the node authorization service if setting the "hiddenBeforeDateTime" property is granted
    And I should not be granted to set the "hiddenAfterDateTime" property to "2015-03-24"
    And I should get false when asking the node authorization service if setting the "hiddenAfterDateTime" property is granted
    And I should not be granted to set the "hiddenInIndex" property to "true"
    And I should get false when asking the node authorization service if setting the "hiddenInIndex" property is granted
    And I should be granted to set the "accessRoles" property to "Neos.Flow:Everybody"
    And I should get true when asking the node authorization service if setting the "accessRoles" property is granted
    And I should be granted to set the "name" property to "new-name"
    And I should get true when asking the node authorization service if setting the "name" property is granted

  @Isolated @fixtures
  Scenario: Authorization service returns the denied properties of a node
    Given I am not authenticated
    And I get a node by path "/sites/content-repository" with the following context:
      | Workspace |
      | live      |
    Then I should get the following list of denied node properties from the node authorization service:
      | propertyName         |
      | hidden               |
      | hiddenBeforeDateTime |
      | hiddenAfterDateTime  |
      | hiddenInIndex        |

  @Isolated @fixtures
  Scenario: Anonymous users are not granted to hide service teaser
    Given I am not authenticated
    And I get a node by path "/sites/content-repository/service/teaser" with the following context:
      | Workspace  |
      | user-admin |
    Then I should not be granted to set the "hidden" property to "true"
    And I should be granted to set the "text" property to "Some New Text"
    And I should get false when asking the node authorization service if setting the "hidden" property is granted

  @Isolated @fixtures
  Scenario: Administrators are granted to hide service teaser
    Given I am authenticated with role "Neos.ContentRepository:Administrator"
    And I get a node by path "/sites/content-repository/service/teaser" with the following context:
      | Workspace  |
      | user-admin |
    Then I should be granted to set the "hidden" property to "true"
    And I should be granted to set the "text" property to "Some New Text"
    And I should get true when asking the node authorization service if setting the "hidden" property is granted
