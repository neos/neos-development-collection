Feature: Export of used Assets, Image Variants and Persistent Resources

  Background:
    Given I have the following content dimensions:
      | Identifier | Default | Values     | Generalizations |
      | language   | en      | en, de, ch | ch->de          |
    And the following PersistentResources exist
      | identifier | filename       | collectionName | mediaType       |
      | resource1  | Some-File1.jpg | persistent     | image/jpeg      |
      | resource2  | Some-File2.png | persistent     | image/png       |
      | resource3  | Some-File3.jpg | persistent     | image/jpeg      |
      | resource4  | Some-File4.pdf | persistent     | application/pdf |
    And the following Assets exist
      | identifier | type     | title        | copyrightNotice   | caption         | assetSourceIdentifier | resourceId |
      | asset1     | IMAGE    | First asset  | copyright asset 1 | Asset 1 caption | neos                  | resource1  |
      | asset2     | IMAGE    | Second asset | copyright asset 2 | Asset 2 caption | neos                  | resource2  |
      | asset3     | IMAGE    | Third asset  | copyright asset 3 | Asset 3 caption | other-asset-source    | resource1  |
      | asset4     | DOCUMENT | Fourth asset | copyright asset 4 | Asset 4 caption | neos                  | resource4  |
    And the following ImageVariants exist
      | identifier | originalAssetIdentifier | name           | width | height | presetIdentifier  | presetVariantName      | imageAdjustments                                                                                                                                                                            |
      | variant1   | asset1                  | First variant  | 222   | 333    | SomePresetId      | SomePresetVariant      | [{"type": "RESIZE_IMAGE", "properties": {"width": 222, "height": 333}}]                                                                                                                     |
      | variant2   | asset1                  | Second variant | 300   | 300    | SomeOtherPresetId | SomeOtherPresetVariant | [{"type": "CROP_IMAGE", "properties": {"width": 300, "height": 300}}, {"type": "RESIZE_IMAGE", "properties": {"width": 444, "height": 234, "allowUpScaling": false, "ratioMode": "inset"}}] |
    And I have the following NodeTypes configuration:
    """
    'unstructured': {}
    'Some.Package:SomeNodeType':
      properties:
        'string':
          type: string
        'image':
          type: 'Neos\Media\Domain\Model\ImageInterface'
        'asset':
          type: 'Neos\Media\Domain\Model\Asset'
        'assets':
          type: 'array<Neos\Media\Domain\Model\Asset>'
    """

  Scenario: Exporting an Image Variant includes the original Image asset as well
    When I have the following node data rows:
      | Identifier    | Path             | Node Type                 | Properties                       |
      | sites-node-id | /sites           | unstructured              |                                  |
      | site-node-id  | /sites/test-site | Some.Package:SomeNodeType | {"string": "asset:\/\/variant1"} |
    And I run the asset migration
    Then I expect the following Assets to be exported:
    """
    [
      {
        "identifier": "asset1",
        "type": "IMAGE",
        "title": "First asset",
        "copyrightNotice": "copyright asset 1",
        "caption": "Asset 1 caption",
        "assetSourceIdentifier": "neos",
        "resource": {
          "filename": "Some-File1.jpg",
          "collectionName": "persistent",
          "mediaType": "image\/jpeg",
          "sha1": "76170d5a43129350f5d6f8fef2818c26ec57fc8c"
        }
      }
    ]
    """
    And I expect the following ImageVariants to be exported:
    """
    [
      {
        "identifier": "variant1",
        "originalAssetIdentifier": "asset1",
        "name": "First variant",
        "width": 222,
        "height": 333,
        "presetIdentifier": "SomePresetId",
        "presetVariantName": "SomePresetVariant",
        "imageAdjustments": [{"type": "RESIZE_IMAGE", "properties": {"width": 222, "height": 333}}]
      }
    ]
    """
    And I expect the following PersistentResources to be exported:
      | Filename                                 | Contents  |
      | 76170d5a43129350f5d6f8fef2818c26ec57fc8c | resource1 |

  Scenario: Assets and image variants are only exported once each
    When I have the following node data rows:
      | Identifier    | Path             | Node Type                 | Dimension Values     | Properties                                                                                                                                                                                                                                                                                    |
      | sites-node-id | /sites           | unstructured              |                      |                                                                                                                                                                                                                                                                                               |
      | site-node-id  | /sites/test-site | Some.Package:SomeNodeType |                      | {"string": "asset:\/\/asset1"}                                                                                                                                                                                                                                                                |
      | site-node-id  | /sites/test-site | Some.Package:SomeNodeType | {"language": ["ch"]} | {"image": {"__flow_object_type": "Neos\\Media\\Domain\\Model\\Image", "__identifier": "asset2"}}                                                                                                                                                                                              |
      | site-node-id  | /sites/test-site | Some.Package:SomeNodeType | {"language": ["en"]} | {"assets": [{"__flow_object_type": "Neos\\Media\\Domain\\Model\\Document", "__identifier": "asset3"}, {"__flow_object_type": "Neos\\Media\\Domain\\Model\\Image", "__identifier": "asset2"}, {"__flow_object_type": "Neos\\Media\\Domain\\Model\\ImageVariant", "__identifier": "variant1"}]} |
      | site-node-id  | /sites/test-site | Some.Package:SomeNodeType | {"language": ["de"]} | {"string": "some text with an <a href=\"asset:\/\/asset4\">asset link</a>"}                                                                                                                                                                                                                   |
    And I run the asset migration
    Then I expect the following Assets to be exported:
    """
    [
      {
        "identifier": "asset1",
        "type": "IMAGE",
        "title": "First asset",
        "copyrightNotice": "copyright asset 1",
        "caption": "Asset 1 caption",
        "assetSourceIdentifier": "neos",
        "resource": {
          "filename": "Some-File1.jpg",
          "collectionName": "persistent",
          "mediaType": "image\/jpeg",
          "sha1": "76170d5a43129350f5d6f8fef2818c26ec57fc8c"
        }
      },
      {
        "identifier": "asset2",
        "type": "IMAGE",
        "title": "Second asset",
        "copyrightNotice": "copyright asset 2",
        "caption": "Asset 2 caption",
        "assetSourceIdentifier": "neos",
        "resource": {
          "filename": "Some-File2.png",
          "collectionName": "persistent",
          "mediaType": "image\/png",
          "sha1": "c8afcb2e883fdbaee1375ce8f8eedf128e378db1"
        }
      },
      {
        "identifier": "asset3",
        "type": "IMAGE",
        "title": "Third asset",
        "copyrightNotice": "copyright asset 3",
        "caption": "Asset 3 caption",
        "assetSourceIdentifier": "other-asset-source",
        "resource": {
          "filename": "Some-File1.jpg",
          "collectionName": "persistent",
          "mediaType": "image\/jpeg",
          "sha1": "76170d5a43129350f5d6f8fef2818c26ec57fc8c"
        }
      },
      {
        "identifier": "asset4",
        "type": "DOCUMENT",
        "title": "Fourth asset",
        "copyrightNotice": "copyright asset 4",
        "caption": "Asset 4 caption",
        "assetSourceIdentifier": "neos",
        "resource": {
          "filename": "Some-File4.pdf",
          "collectionName": "persistent",
          "mediaType": "application\/pdf",
          "sha1": "b681ac9be92128a6b1f3726182f58f8b4a5cbd1d"
        }
      }
    ]
    """
    And I expect the following ImageVariants to be exported:
    """
    [
      {
        "identifier": "variant1",
        "originalAssetIdentifier": "asset1",
        "name": "First variant",
        "width": 222,
        "height": 333,
        "presetIdentifier": "SomePresetId",
        "presetVariantName": "SomePresetVariant",
        "imageAdjustments": [{"type": "RESIZE_IMAGE", "properties": {"width": 222, "height": 333}}]
      }
    ]
    """
    And I expect the following PersistentResources to be exported:
      | Filename                                 | Contents  |
      | 76170d5a43129350f5d6f8fef2818c26ec57fc8c | resource1 |
      | c8afcb2e883fdbaee1375ce8f8eedf128e378db1 | resource2 |
      | b681ac9be92128a6b1f3726182f58f8b4a5cbd1d | resource4 |

  Scenario: Referring to non-existing asset
    When I have the following node data rows:
      | Identifier    | Path             | Node Type                 | Properties                                 |
      | sites-node-id | /sites           | unstructured              |                                            |
      | site-node-id  | /sites/test-site | Some.Package:SomeNodeType | {"string": "asset:\/\/non-existing-asset"} |
    And I run the asset migration
    Then I expect no Assets to be exported
    And I expect no ImageVariants to be exported
    And I expect no PersistentResources to be exported
