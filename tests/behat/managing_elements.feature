@tool @tool_certificate @moodleworkplace @javascript
Feature: Being able to manage elements in a certificate template
  In order to ensure managing elements in a certificate template works as expected
  As an admin
  I need to manage elements in a certificate template

  Background:
    Given the following certificate templates exist:
      | name | numberofpages |
      | Certificate 1 | 1 |
    Given the following "custom field categories" exist:
      | name          | component         | area    | itemid |
      | Other fields  | tool_certificate  | issue   | 0      |
    Given the following "custom fields" exist:
      | name              | category      | type | shortname       | configdata                                         |
      | Course full name  | Other fields  | text | coursefullname  | {"visible":true,"previewvalue":"Course full name"} |
    And I log in as "admin"
    And I navigate to "Certificates > Manage certificate templates" in site administration
    And I click on "Edit content" "link" in the "Certificate 1" "table_row"

  @_file_upload
  Scenario: Add and edit elements in a certificate template
    When I change window size to "large"
    # Border.
    And I add the element "Border" to page "1" of the "Certificate 1" site certificate template
    And I should see "Add 'Border' element" in the ".modal.show .modal-header" "css_element"
    And I set the following fields to these values:
      | Width  | 2 |
      | Colour | #045ECD |
    And I click on "Save" "button" in the ".modal.show .modal-footer" "css_element"
    And I should see "Border" in the "[data-region='elementlist']" "css_element"
    And I click on "Edit 'Border'" "link" in the "Border" "list_item"
    And the following fields match these values:
      | Width | 2 |
      | Colour | #045ECD |
    And I click on "Save" "button" in the ".modal.show .modal-footer" "css_element"
    # Code.
    And I add the element "Code" to page "1" of the "Certificate 1" site certificate template
    And I should see "Add 'Code' element" in the ".modal.show .modal-header" "css_element"
    And the following fields match these values:
      | Display | QR Code |
    And I set the following fields to these values:
      | Font                     | Times - Italic |
      | Size                     | 20        |
      | Colour                   | #045ECD   |
      | Width                    | 20        |
      | Text alignment           | Left      |
    And I click on "Save" "button" in the ".modal.show .modal-footer" "css_element"
    And I should see "Code" in the "[data-region='elementlist']" "css_element"
    And I click on "Edit 'Code'" "link" in the "Code" "list_item"
    And the following fields match these values:
      | Font                     | Times - Italic |
      | Size                     | 20        |
      | Colour                   | #045ECD   |
      | Width                    | 20        |
      | Text alignment           | Left      |
    And I click on "Save" "button" in the ".modal.show .modal-footer" "css_element"
    # Date.
    And I add the element "Date" to page "1" of the "Certificate 1" site certificate template
    And I should see "Add 'Date' element" in the ".modal.show .modal-header" "css_element"
    And I set the following fields to these values:
      | Date item                | Issued date       |
      | Date format              | strftimedateshort |
      | Font                     | Times - Italic         |
      | Size                     | 20                |
      | Colour                   | #045ECD           |
      | Width                    | 20                |
      | Text alignment           | Left              |
    And I click on "Save" "button" in the ".modal.show .modal-footer" "css_element"
    And I should see "Date" in the "[data-region='elementlist']" "css_element"
    And I click on "Edit 'Date'" "link" in the "Date" "list_item"
    And the following fields match these values:
      | Date item                | Issued date |
      | Date format              | strftimedateshort |
      | Font                     | Times - Italic         |
      | Size                     | 20                |
      | Colour                   | #045ECD           |
      | Width                    | 20                |
      | Text alignment           | Left              |
    And I click on "Save" "button" in the ".modal.show .modal-footer" "css_element"
    # Digital signature.
    And I add the element "Digital signature" to page "1" of the "Certificate 1" site certificate template
    And I should see "Add 'Digital signature' element" in the ".modal.show .modal-header" "css_element"
    And I upload "admin/tool/certificate/tests/fixtures/signature.crt" file to "Digital signature" filemanager
    And I set the following fields to these values:
      | Signature name         | This is the signature name |
      | Signature password     | Some awesome password      |
      | Signature location     | Mordor                     |
      | Signature reason       | Meh, felt like it.         |
      | Signature contact info | Sauron                     |
      | Width                  | 25                         |
      | Height                 | 15                         |
    And I click on "Save" "button" in the ".modal.show .modal-footer" "css_element"
    And I should see "Digital signature" in the "[data-region='elementlist']" "css_element"
    And I click on "Edit 'Digital signature'" "link" in the "Digital signature" "list_item"
    And the following fields match these values:
      | Signature name         | This is the signature name |
      | Signature password     | Some awesome password      |
      | Signature location     | Mordor                     |
      | Signature reason       | Meh, felt like it.         |
      | Signature contact info | Sauron                     |
      | Width                  | 25                         |
      | Height                 | 15                         |
    And I click on "Save" "button" in the ".modal.show .modal-footer" "css_element"
    # Image.
    And I add the element "Image" to page "1" of the "Certificate 1" site certificate template
    And I should see "Add 'Image' element" in the ".modal.show .modal-header" "css_element"
    And I upload "lib/tests/fixtures/gd-logo.png" file to "Upload image" filemanager
    And I set the following fields to these values:
      | Width  | 25 |
      | Height | 15 |
    And I click on "Save" "button" in the ".modal.show .modal-footer" "css_element"
    And I should see "Image" in the "[data-region='elementlist']" "css_element"
    And I click on "Edit 'Image'" "link" in the "Image" "list_item"
    And the following fields match these values:
      | Width  | 25 |
      | Height | 15 |
    And I click on "Save" "button" in the ".modal.show .modal-footer" "css_element"
    # Text.
    And I add the element "Text" to page "1" of the "Certificate 1" site certificate template
    And I should see "Add 'Text' element" in the ".modal.show .modal-header" "css_element"
    And I set the following fields to these values:
      | Text                     | Test this |
      | Font                     | Times - Italic |
      | Size                     | 20        |
      | Colour                   | #045ECD   |
      | Width                    | 20        |
      | Text alignment           | Left      |
    And I click on "Save" "button" in the ".modal.show .modal-footer" "css_element"
    And I should see "Text" in the "[data-region='elementlist']" "css_element"
    And I click on "Edit 'Text'" "link" in the "Text" "list_item"
    And the following fields match these values:
      | Text                     | Test this |
      | Font                     | Times - Italic |
      | Size                     | 20        |
      | Colour                   | #045ECD   |
      | Width                    | 20        |
      | Text alignment           | Left      |
    And I click on "Save" "button" in the ".modal.show .modal-footer" "css_element"
    # User field.
    And I add the element "User field" to page "1" of the "Certificate 1" site certificate template
    And I should see "Add 'User field' element" in the ".modal.show .modal-header" "css_element"
    And I set the following fields to these values:
      | User field               | Country   |
      | Font                     | Times - Italic |
      | Size                     | 20        |
      | Colour                   | #045ECD   |
      | Width                    | 20        |
      | Text alignment           | Left      |
    And I click on "Save" "button" in the ".modal.show .modal-footer" "css_element"
    And I should see "User field" in the "[data-region='elementlist']" "css_element"
    And I click on "Edit 'User field'" "link" in the "User field" "list_item"
    And the following fields match these values:
      | User field               | Country   |
      | Font                     | Times - Italic |
      | Size                     | 20        |
      | Colour                   | #045ECD   |
      | Width                    | 20        |
      | Text alignment           | Left      |
    And I click on "Save" "button" in the ".modal.show .modal-footer" "css_element"
    # User picture.
    And I add the element "User picture" to page "1" of the "Certificate 1" site certificate template
    And I should see "Add 'User picture' element" in the ".modal.show .modal-header" "css_element"
    And I set the following fields to these values:
      | Width  | 10 |
      | Height | 10 |
    And I click on "Save" "button" in the ".modal.show .modal-footer" "css_element"
    And I should see "User picture" in the "[data-region='elementlist']" "css_element"
    And I click on "Edit 'User picture'" "link" in the "User picture" "list_item"
    And the following fields match these values:
      | Width  | 10 |
      | Height | 10 |
    And I click on "Save" "button" in the ".modal.show .modal-footer" "css_element"
    # Dynamic fields.
    And I add the element "Dynamic fields" to page "1" of the "Certificate 1" site certificate template
    And I should see "Add 'Dynamic fields' element" in the ".modal.show .modal-header" "css_element"
    And I follow "Show more..."
    And I set the following fields to these values:
      | Field  | Course full name |
      | Position X | 100           |
    And I click on "Save" "button" in the ".modal.show .modal-footer" "css_element"
    And I should see "Dynamic fields" in the "[data-region='elementlist']" "css_element"
    And I click on "Edit 'Dynamic fields'" "link" in the "[data-region='elementlist']" "css_element"
    And the following fields match these values:
      | Field  | Course full name |
    And I click on "Save" "button" in the ".modal.show .modal-footer" "css_element"
    And I log out

  Scenario: Delete an element from a certificate template
    When I change window size to "large"
    When I add the element "Code" to page "1" of the "Certificate 1" site certificate template
    And I click on "Save" "button" in the ".modal.show .modal-footer" "css_element"
    And I should see "Code" in the "[data-region='elementlist']" "css_element"
    And I add the element "User field" to page "1" of the "Certificate 1" site certificate template
    And I click on "Save" "button" in the ".modal.show .modal-footer" "css_element"
    And I should see "User field" in the "[data-region='elementlist']" "css_element"
    And I click on "Delete" "link" in the "User field" "list_item"
    And I click on "Cancel" "button" in the "Confirm" "dialogue"
    And I should see "Code" in the "[data-region='elementlist']" "css_element"
    And I should see "User field" in the "[data-region='elementlist']" "css_element"
    And I click on "Delete" "link" in the "User field" "list_item"
    And I click on "Delete" "button" in the "Confirm" "dialogue"
    And I should see "Code" in the "[data-region='elementlist']" "css_element"
    And I should not see "User field" in the "[data-region='elementlist']" "css_element"

  Scenario: Edit element name on a certificate template
    When I change window size to "large"
    When I add the element "User field" to page "1" of the "Certificate 1" site certificate template
    And I click on "Save" "button" in the ".modal.show .modal-footer" "css_element"
    And I click on "Edit element name" "link" in the "User field" "list_item"
    And I set the field "New value for User field" to "User full name"
    And I press the enter key
    And I should not see "User field"
    And I should see "User full name"
    And I navigate to "Certificates > Manage certificate templates" in site administration
    And I click on "Edit content" "link"
    And I should not see "User field"
    And I should see "User full name"
    And I log out

  Scenario: Rearrange elements on a certificate template
    When I change window size to "large"
    When I add the element "Date" to page "1" of the "Certificate 1" site certificate template
    And I click on "Save" "button" in the ".modal.show .modal-footer" "css_element"
    When I add the element "User field" to page "1" of the "Certificate 1" site certificate template
    And I click on "Save" "button" in the ".modal.show .modal-footer" "css_element"
    When I add the element "Code" to page "1" of the "Certificate 1" site certificate template
    And I click on "Save" "button" in the ".modal.show .modal-footer" "css_element"
    Then "Date" "list_item" should appear before "User field" "list_item"
    And "User field" "list_item" should appear before "Code" "list_item"
    And I click on "Bring forward or move back" "button" in the "Date" "list_item"
    And I follow "After \" User field \""
    And "User field" "list_item" should appear before "Date" "list_item"
    Then "Date" "list_item" should appear before "Code" "list_item"
    And I navigate to "Certificates > Manage certificate templates" in site administration
    And I click on "Edit content" "link"
    And "User field" "list_item" should appear before "Date" "list_item"
    Then "Date" "list_item" should appear before "Code" "list_item"
    And I log out
