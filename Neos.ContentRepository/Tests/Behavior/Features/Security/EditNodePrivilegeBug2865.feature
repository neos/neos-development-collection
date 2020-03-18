Feature: Privilege to restrict editing of nodes (Bug #2865)

  This scenario tests the bug https://github.com/neos/neos-development-collection/issues/2865.

  Background:
    Given I have the following policies:
      """
      privilegeTargets:

        'Neos\ContentRepository\Security\Authorization\Privilege\Node\EditNodePrivilege':

          'Neos.ContentRepository:EditEventNodes':
            matcher: 'isDescendantNodeOf("11d3aded-fb1a-70e7-1412-0b465b11fcd8")'

          'Neos.ContentRepository:EditAll':
            matcher: 'true'

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
              privilegeTarget: 'Neos.ContentRepository:EditEventNodes'
              permission: GRANT
      """

    And I have the following nodes:
      | Identifier                           | Path                                 | Node Type                               | Properties                                            | Workspace | Hidden |
      | ecf40ad1-3119-0a43-d02e-55f8b5aa3c70 | /sites                               | unstructured                            |                                                       | live      | false  |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/content-repository            | Neos.ContentRepository.Testing:Document | {"title": "Home"}                                     | live      | false  |
      | 11d3aded-fb1a-70e7-1412-0b465b11fcd8 | /sites/content-repository/events     | Neos.ContentRepository.Testing:Document | {"title": "Events", "description": "Some cool event"} | live      | true  |
      | 0385ed39-99f4-4927-ab44-640e0e39a7dc | /sites/content-repository/events/foo | Neos.ContentRepository.Testing:Document | {"title": "Some Event", "description": "an event"}    | live      | false  |


  @Isolated @fixtures
  Scenario: Administrators are granted to edit the events node's description property
    Given I am authenticated with role "Neos.ContentRepository:Administrator"
    And I get a node by path "/sites/content-repository/events" with the following context:
      | Workspace  |
      | user-admin |
    Then I should be granted to set the "description" property to "Even cooler event!"
    And I should get true when asking the node authorization service if editing this node is granted

  @Isolated @fixtures
  Scenario: Administrators are granted to edit nested nodes, matched by "isDescendantNodeOf"; if the isDescendantNodeOf selector contains a Node UUID.
    Given I am authenticated with role "Neos.ContentRepository:Administrator"
    And I get a node by path "/sites/content-repository/events/foo" with the following context:
      | Workspace  |
      | user-admin |
    And I should get true when asking the node authorization service if editing this node is granted
