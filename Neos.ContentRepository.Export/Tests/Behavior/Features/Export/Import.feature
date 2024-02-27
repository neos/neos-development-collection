@contentrepository
Feature: As a user of the CR I want to export the event stream
  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    Vendor.Site:HomePage':
      superTypes:
        Neos.Neos:Site: true
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"

  Scenario: Import the event stream
    Then I expect exactly 0 events to be published on stream with prefix "ContentStream:cs-identifier"
    Given using the following events.jsonl:
      """
      {"identifier":"5f2da12d-7037-4524-acb0-d52037342c77","type":"ContentStreamWasCreated","payload":{"contentStreamId":"6ea2f6e1-4c9c-44f9-8a86-991705d13770"},"metadata":[]}
      {"identifier":"9f64c281-e5b0-48d9-900b-288a8faf92a9","type":"RootNodeAggregateWithNodeWasCreated","payload":{"contentStreamId":"6ea2f6e1-4c9c-44f9-8a86-991705d13770","nodeAggregateId":"acme-site-sites","nodeTypeName":"Neos.Neos:Sites","coveredDimensionSpacePoints":[[]],"nodeAggregateClassification":"root"},"metadata":[]}
      {"identifier":"1640ebbf-7ffe-4526-b0f4-7575cefabfab","type":"NodeAggregateWithNodeWasCreated","payload":{"contentStreamId":"6ea2f6e1-4c9c-44f9-8a86-991705d13770","nodeAggregateId":"acme-site","nodeTypeName":"Vendor.Site:HomePage","originDimensionSpacePoint":[],"coveredDimensionSpacePoints":[[]],"parentNodeAggregateId":"acme-site-sites","nodeName":"acme-site","initialPropertyValues":{"title":{"value":"My Site","type":"string"},"uriPathSegment":{"value":"my-site","type":"string"}},"nodeAggregateClassification":"regular","succeedingNodeAggregateId":null},"metadata":[]}
      """
    And I import the events.jsonl into "cs-identifier"
    Then I expect exactly 4 events to be published on stream with prefix "ContentStream:cs-identifier"

  Scenario: Import the event stream into a specific content stream
    Then I expect exactly 0 events to be published on stream with prefix "ContentStream:cs-imported-identifier"
    Given using the following events.jsonl:
      """
      {"identifier":"5f2da12d-7037-4524-acb0-d52037342c77","type":"ContentStreamWasCreated","payload":{"contentStreamId":"cs-imported-identifier"},"metadata":[]}
      {"identifier":"9f64c281-e5b0-48d9-900b-288a8faf92a9","type":"RootNodeAggregateWithNodeWasCreated","payload":{"contentStreamId":"cs-imported-identifier","nodeAggregateId":"acme-site-sites","nodeTypeName":"Neos.Neos:Sites","coveredDimensionSpacePoints":[[]],"nodeAggregateClassification":"root"},"metadata":[]}
      {"identifier":"1640ebbf-7ffe-4526-b0f4-7575cefabfab","type":"NodeAggregateWithNodeWasCreated","payload":{"contentStreamId":"cs-imported-identifier","nodeAggregateId":"acme-site","nodeTypeName":"Vendor.Site:HomePage","originDimensionSpacePoint":[],"coveredDimensionSpacePoints":[[]],"parentNodeAggregateId":"acme-site-sites","nodeName":"acme-site","initialPropertyValues":{"title":{"value":"My Site","type":"string"},"uriPathSegment":{"value":"my-site","type":"string"}},"nodeAggregateClassification":"regular","succeedingNodeAggregateId":null},"metadata":[]}
      """
    And I import the events.jsonl
    Then I expect exactly 4 events to be published on stream with prefix "ContentStream:cs-imported-identifier"
