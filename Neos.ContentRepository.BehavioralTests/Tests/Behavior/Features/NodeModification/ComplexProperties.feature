@fixtures
Feature: Set properties of complex type

  As a user of the CR I want to set properties of complex types

  Background:
    Given I have no content dimensions
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Intermediary.Testing:Node':
      properties:
        array:
          type: array
          defaultValue:
            givenName: 'Nody'
            familyName: 'McNodeface'
        now:
          type: DateTimeImmutable
          defaultValue: 'now'
        date:
          type: DateTimeImmutable
          defaultValue: '2020-08-20T18:56:15+00:00'
        uri:
          type: GuzzleHttp\Psr7\Uri
          defaultValue: 'https://neos.io'
        postalAddress:
          type: Neos\EventSourcedContentRepository\Tests\Behavior\Fixtures\PostalAddress
          defaultValue:
            streetAddress: '28 31st of February Street'
            postalCode: '12345'
            addressLocality: 'City'
            addressCountry: 'Country'
    """
    And the event RootWorkspaceWasCreated was published with payload:
      | Key                        | Value                                  |
      | workspaceName              | "live"                                 |
      | workspaceTitle             | "Live"                                 |
      | workspaceDescription       | "The live workspace"                   |
      | initiatingUserIdentifier   | "00000000-0000-0000-0000-000000000000" |
      | newContentStreamIdentifier | "cs-identifier"                        |
    And the event RootNodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                  |
      | contentStreamIdentifier     | "cs-identifier"                        |
      | nodeAggregateIdentifier     | "lady-eleonode-rootford"               |
      | nodeTypeName                | "Neos.ContentRepository:Root"          |
      | coveredDimensionSpacePoints | [{}]                                   |
      | initiatingUserIdentifier    | "00000000-0000-0000-0000-000000000000" |
      | nodeAggregateClassification | "root"                                 |
    And the graph projection is fully up to date

  Scenario: Set complex values
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                           | Value                                              |
      | contentStreamIdentifier       | "cs-identifier"                                    |
      | nodeAggregateIdentifier       | "nody-mc-nodeface"                                 |
      | nodeTypeName                  | "Neos.ContentRepository.Intermediary.Testing:Node" |
      | originDimensionSpacePoint     | {}                                                 |
      | initiatingUserIdentifier      | "00000000-0000-0000-0000-000000000000"             |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                           |
    And the graph projection is fully up to date
    And the intermediary command SetNodeProperties is executed with payload:
      | Key                       | Value                                                                                                                    |
      | contentStreamIdentifier   | "cs-identifier"                                                                                                          |
      | nodeAggregateIdentifier   | "nody-mc-nodeface"                                                                                                       |
      | originDimensionSpacePoint | {}                                                                                                                       |
      | propertyValues            | {"postalAddress":"PostalAddress:anotherDummy", "date":"Date:2021-03-13T17:33:17+00:00", "uri":"URI:https://www.neos.io"} |
    And the graph projection is fully up to date
    When I am in content stream "cs-identifier" and Dimension Space Point {}
    And the read model with node aggregate identifier "nody-mc-nodeface" is instantiated
    Then I expect this read model to have the properties:
      | Key           | Value                                           |
      | array         | {"givenName":"Nody", "familyName":"McNodeface"} |
      | postalAddress | "PostalAddress:anotherDummy"                    |
      | date          | "Date:2021-03-13T17:33:17+00:00"                |
      | uri           | "URI:https://www.neos.io"                       |
