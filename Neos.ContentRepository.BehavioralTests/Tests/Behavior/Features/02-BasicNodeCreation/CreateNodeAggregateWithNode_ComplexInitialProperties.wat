@fixtures
Feature: Create a node aggregate with complex initial values

  As a user of the CR I want properties of complex types to be un/serialized

  Background:
    Given I have no content dimensions
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Intermediary.Testing:Node':
      properties:
        bool:
          type: bool
        int:
          type: int
        float:
          type: float
        string:
          type: string
        nullString:
          type: string
        array:
          type: array
        date:
          type: DateTimeImmutable
        uri:
          type: GuzzleHttp\Psr7\Uri
        postalAddress:
          type: Neos\EventSourcedContentRepository\Tests\Behavior\Fixtures\PostalAddress
        image:
          type: Neos\Media\Domain\Model\ImageInterface
        images:
          type: array<Neos\Media\Domain\Model\ImageInterface>
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

  Scenario: Create a node aggregate with complex initial values
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                           | Value                                                                                                                                                                                                                                                                                                   |
      | contentStreamIdentifier       | "cs-identifier"                                                                                                                                                                                                                                                                                         |
      | nodeAggregateIdentifier       | "nody-mc-nodeface"                                                                                                                                                                                                                                                                                      |
      | nodeTypeName                  | "Neos.ContentRepository.Intermediary.Testing:Node"                                                                                                                                                                                                                                                      |
      | originDimensionSpacePoint     | {}                                                                                                                                                                                                                                                                                                      |
      | initiatingUserIdentifier      | "00000000-0000-0000-0000-000000000000"                                                                                                                                                                                                                                                                  |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                                                                                                                                                                                                                                                                                |
      | initialPropertyValues         | {"string":"Nody McNodeface", "nullString": null, "bool":true, "int":42, "float":4.2, "array":{"givenName":"Nody", "familyName":"McNodeface"}, "postalAddress":"PostalAddress:dummy", "date":"Date:2020-08-20T18:56:15+00:00", "uri":"URI:https://neos.io", "image":"IMG:dummy", "images":"[IMG:dummy]"} |
    And the graph projection is fully up to date
    When I am in content stream "cs-identifier" and Dimension Space Point {}
    And the read model with node aggregate identifier "nody-mc-nodeface" is instantiated
    Then I expect this read model to have the properties:
      | Key           | Value                                           |
      | bool          | true                                            |
      | int           | 42                                              |
      | float         | 4.2                                             |
      | string        | "Nody McNodeface"                               |
      | nullString    | null                                            |
      | array         | {"givenName":"Nody", "familyName":"McNodeface"} |
      | postalAddress | "PostalAddress:dummy"                           |
      | date          | "Date:2020-08-20T18:56:15+00:00"                |
      | uri           | "URI:https://neos.io"                           |
      | image         | "IMG:dummy"                                     |
      | images        | "[IMG:dummy]"                                   |
