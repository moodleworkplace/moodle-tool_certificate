@tool @tool_certificate
Feature: Being able to manage elements in a certificate template
  In order to ensure managing elements in a certificate template works as expected
  As an admin
  I need to manage elements in a certificate template

  Background:
    Given the following certificate templates exist:
      | name | numberofpages |
      | Certificate 1 | 1 |
    And I log in as "admin"
    And I navigate to "Courses > Manage certificate templates" in site administration
    And I click on "Edit" "link"

  Scenario: Add and edit elements in a certificate template
    # Background image.
   When I add the element "Background image" to page "1" of the "Certificate 1" certificate template
    And I press "Save changes"
    And I should see "Background image" in the "elementstable" "table"
    # Border.
    And I add the element "Border" to page "1" of the "Certificate 1" certificate template
    And I set the following fields to these values:
      | Width  | 2 |
      | Colour | #045ECD |
    And I press "Save changes"
    And I should see "Border" in the "elementstable" "table"
    And I click on ".edit-icon" "css_element" in the "Border" "table_row"
    And the following fields match these values:
      | Width  | 2 |
      | Colour | #045ECD |
    And I press "Save changes"
    # Code.
    And I add the element "Code" to page "1" of the "Certificate 1" certificate template
    And I set the following fields to these values:
      | Font                     | Helvetica |
      | Size                     | 20        |
      | Colour                   | #045ECD   |
      | Width                    | 20        |
      | Reference point location | Top left  |
    And I press "Save changes"
    And I should see "Code" in the "elementstable" "table"
    And I click on ".edit-icon" "css_element" in the "Code" "table_row"
    And the following fields match these values:
      | Font                     | Helvetica |
      | Size                     | 20        |
      | Colour                   | #045ECD   |
      | Width                    | 20        |
      | Reference point location | Top left  |
    And I press "Save changes"
    # Date.
    And I add the element "Date" to page "1" of the "Certificate 1" certificate template
    And I set the following fields to these values:
      | Date item                | Issued date |
      | Date format              | 2                 |
      | Font                     | Helvetica         |
      | Size                     | 20                |
      | Colour                   | #045ECD           |
      | Width                    | 20                |
      | Reference point location | Top left          |
    And I press "Save changes"
    And I should see "Date" in the "elementstable" "table"
    And I click on ".edit-icon" "css_element" in the "Date" "table_row"
    And the following fields match these values:
      | Date item                | Issued date |
      | Date format              | 2                 |
      | Font                     | Helvetica         |
      | Size                     | 20                |
      | Colour                   | #045ECD           |
      | Width                    | 20                |
      | Reference point location | Top left          |
    And I press "Save changes"
    # Digital signature.
    And I add the element "Digital signature" to page "1" of the "Certificate 1" certificate template
    And I set the following fields to these values:
      | Signature name         | This is the signature name |
      | Signature password     | Some awesome password      |
      | Signature location     | Mordor                     |
      | Signature reason       | Meh, felt like it.         |
      | Signature contact info | Sauron                     |
      | Width                  | 25                         |
      | Height                 | 15                         |
    And I press "Save changes"
    And I should see "Digital signature" in the "elementstable" "table"
    And I click on ".edit-icon" "css_element" in the "Digital signature" "table_row"
    And the following fields match these values:
      | Signature name         | This is the signature name |
      | Signature password     | Some awesome password      |
      | Signature location     | Mordor                     |
      | Signature reason       | Meh, felt like it.         |
      | Signature contact info | Sauron                     |
      | Width                  | 25                         |
      | Height                 | 15                         |
    And I press "Save changes"
    # Image.
    And I add the element "Image" to page "1" of the "Certificate 1" certificate template
    And I set the following fields to these values:
      | Width  | 25 |
      | Height | 15 |
    And I press "Save changes"
    And I should see "Image" in the "elementstable" "table"
    And I click on ".edit-icon" "css_element" in the "Image" "table_row"
    And the following fields match these values:
      | Width  | 25 |
      | Height | 15 |
    And I press "Save changes"
    # Student name.
    And I add the element "Student name" to page "1" of the "Certificate 1" certificate template
    And I set the following fields to these values:
      | Font                     | Helvetica |
      | Size                     | 20        |
      | Colour                   | #045ECD   |
      | Width                    | 20        |
      | Reference point location | Top left  |
    And I press "Save changes"
    And I should see "Student name" in the "elementstable" "table"
    And I click on ".edit-icon" "css_element" in the "Student name" "table_row"
    And the following fields match these values:
      | Font                     | Helvetica |
      | Size                     | 20        |
      | Colour                   | #045ECD   |
      | Width                    | 20        |
      | Reference point location | Top left  |
    And I press "Save changes"
    # Text.
    And I add the element "Text" to page "1" of the "Certificate 1" certificate template
    And I set the following fields to these values:
      | Text                     | Test this |
      | Font                     | Helvetica |
      | Size                     | 20        |
      | Colour                   | #045ECD   |
      | Width                    | 20        |
      | Reference point location | Top left  |
    And I press "Save changes"
    And I should see "Text" in the "elementstable" "table"
    And I click on ".edit-icon" "css_element" in the "Text" "table_row"
    And the following fields match these values:
      | Text                     | Test this |
      | Font                     | Helvetica |
      | Size                     | 20        |
      | Colour                   | #045ECD   |
      | Width                    | 20        |
      | Reference point location | Top left  |
    And I press "Save changes"
    # User field.
    And I add the element "User field" to page "1" of the "Certificate 1" certificate template
    And I set the following fields to these values:
      | User field               | Country   |
      | Font                     | Helvetica |
      | Size                     | 20        |
      | Colour                   | #045ECD   |
      | Width                    | 20        |
      | Reference point location | Top left  |
    And I press "Save changes"
    And I should see "User field" in the "elementstable" "table"
    And I click on ".edit-icon" "css_element" in the "User field" "table_row"
    And the following fields match these values:
      | User field               | Country   |
      | Font                     | Helvetica |
      | Size                     | 20        |
      | Colour                   | #045ECD   |
      | Width                    | 20        |
      | Reference point location | Top left  |
    And I press "Save changes"
    # User picture.
    And I add the element "User picture" to page "1" of the "Certificate 1" certificate template
    And I set the following fields to these values:
      | Width  | 10 |
      | Height | 10 |
    And I press "Save changes"
    And I should see "User picture" in the "elementstable" "table"
    And I click on ".edit-icon" "css_element" in the "User picture" "table_row"
    And the following fields match these values:
      | Width  | 10 |
      | Height | 10 |
    And I press "Save changes"
    # Just to test there are no exceptions being thrown.
    And I follow "Reposition elements"
    And I press "Save and close"
    And I press "Save changes and preview"

  Scenario: Delete an element from a certificate template
    When I add the element "Background image" to page "1" of the "Certificate 1" certificate template
    And I press "Save changes"
    And I should see "Background image" in the "elementstable" "table"
    And I add the element "Student name" to page "1" of the "Certificate 1" certificate template
    And I press "Save changes"
    And I should see "Student name" in the "elementstable" "table"
    And I click on ".delete-icon" "css_element" in the "Student name" "table_row"
    And I press "Cancel"
    And I should see "Background image" in the "elementstable" "table"
    And I should see "Student name" in the "elementstable" "table"
    And I click on ".delete-icon" "css_element" in the "Student name" "table_row"
    And I press "Continue"
    And I should see "Background image" in the "elementstable" "table"
    And I should not see "Student name" in the "elementstable" "table"
