@tool @tool_certificate @javascript
Feature: Being able to manage elements in a certificate template
  In order to ensure managing elements in a certificate template works as expected
  As an admin
  I need to manage elements in a certificate template

  Background:
    Given the following certificate templates exist:
      | name | numberofpages |
      | Certificate 1 | 1 |
    And I log in as "admin"
    And I navigate to "Certificates > Manage certificate templates" in site administration
    And I click on "Edit content" "link"

  Scenario: Add and edit elements in a certificate template
    When I change window size to "large"
    # Background image.
    When I add the element "Background image" to page "1" of the "Certificate 1" site certificate template
    And I press "Save" in the modal form dialogue
    And I should see "Background image" in the "[data-region='elementlist']" "css_element"
    # Border.
    And I add the element "Border" to page "1" of the "Certificate 1" site certificate template
    And I set the following fields to these values:
      | Width  | 2 |
      | Colour | #045ECD |
    And I press "Save" in the modal form dialogue
    And I should see "Border" in the "[data-region='elementlist']" "css_element"
    And I click on "Edit 'Border'" "link" in the "Border" "list_item"
    And the following fields match these values:
      | id_width | 2 |
      | Colour | #045ECD |
    And I press "Save" in the modal form dialogue
    # Code.
    And I add the element "Code" to page "1" of the "Certificate 1" site certificate template
    And I set the following fields to these values:
      | Font                     | Times - Italic |
      | Size                     | 20        |
      | Colour                   | #045ECD   |
      | Width                    | 20        |
      | Reference point location | Top left  |
    And I press "Save" in the modal form dialogue
    And I should see "Code" in the "[data-region='elementlist']" "css_element"
    And I click on "Edit 'Code'" "link" in the "Code" "list_item"
    And the following fields match these values:
      | Font                     | Times - Italic |
      | Size                     | 20        |
      | Colour                   | #045ECD   |
      | Width                    | 20        |
      | Reference point location | Top left  |
    And I press "Save" in the modal form dialogue
    # Date.
    And I add the element "Date" to page "1" of the "Certificate 1" site certificate template
    And I set the following fields to these values:
      | Date item                | Issued date       |
      | Date format              | strftimedateshort |
      | Font                     | Times - Italic         |
      | Size                     | 20                |
      | Colour                   | #045ECD           |
      | Width                    | 20                |
      | Reference point location | Top left          |
    And I press "Save" in the modal form dialogue
    And I should see "Date" in the "[data-region='elementlist']" "css_element"
    And I click on "Edit 'Date'" "link" in the "Date" "list_item"
    And the following fields match these values:
      | Date item                | Issued date |
      | Date format              | strftimedateshort |
      | Font                     | Times - Italic         |
      | Size                     | 20                |
      | Colour                   | #045ECD           |
      | Width                    | 20                |
      | Reference point location | Top left          |
    And I press "Save" in the modal form dialogue
    # Digital signature.
    And I add the element "Digital signature" to page "1" of the "Certificate 1" site certificate template
    And I set the following fields to these values:
      | Signature name         | This is the signature name |
      | Signature password     | Some awesome password      |
      | Signature location     | Mordor                     |
      | Signature reason       | Meh, felt like it.         |
      | Signature contact info | Sauron                     |
      | Width                  | 25                         |
      | Height                 | 15                         |
    And I press "Save" in the modal form dialogue
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
    And I press "Save" in the modal form dialogue
    # Image.
    And I add the element "Image" to page "1" of the "Certificate 1" site certificate template
    And I set the following fields to these values:
      | Width  | 25 |
      | Height | 15 |
    And I press "Save" in the modal form dialogue
    And I should see "Image" in the "[data-region='elementlist']" "css_element"
    And I click on "Edit 'Image'" "link" in the "Image" "list_item"
    And the following fields match these values:
      | Width  | 25 |
      | Height | 15 |
    And I press "Save" in the modal form dialogue
    # Student name.
    And I add the element "Student name" to page "1" of the "Certificate 1" site certificate template
    And I set the following fields to these values:
      | Font                     | Times - Italic |
      | Size                     | 20        |
      | Colour                   | #045ECD   |
      | Width                    | 20        |
      | Reference point location | Top left  |
    And I press "Save" in the modal form dialogue
    And I should see "Student name" in the "[data-region='elementlist']" "css_element"
    And I click on "Edit 'Student name'" "link" in the "Student name" "list_item"
    And the following fields match these values:
      | Font                     | Times - Italic |
      | Size                     | 20        |
      | Colour                   | #045ECD   |
      | Width                    | 20        |
      | Reference point location | Top left  |
    And I press "Save" in the modal form dialogue
    # Text.
    And I add the element "Text" to page "1" of the "Certificate 1" site certificate template
    And I set the following fields to these values:
      | Text                     | Test this |
      | Font                     | Times - Italic |
      | Size                     | 20        |
      | Colour                   | #045ECD   |
      | Width                    | 20        |
      | Reference point location | Top left  |
    And I press "Save" in the modal form dialogue
    And I should see "Text" in the "[data-region='elementlist']" "css_element"
    And I click on "Edit 'Text'" "link" in the "Text" "list_item"
    And the following fields match these values:
      | Text                     | Test this |
      | Font                     | Times - Italic |
      | Size                     | 20        |
      | Colour                   | #045ECD   |
      | Width                    | 20        |
      | Reference point location | Top left  |
    And I press "Save" in the modal form dialogue
    # User field.
    And I add the element "User field" to page "1" of the "Certificate 1" site certificate template
    And I set the following fields to these values:
      | User field               | Country   |
      | Font                     | Times - Italic |
      | Size                     | 20        |
      | Colour                   | #045ECD   |
      | Width                    | 20        |
      | Reference point location | Top left  |
    And I press "Save" in the modal form dialogue
    And I should see "User field" in the "[data-region='elementlist']" "css_element"
    And I click on "Edit 'User field'" "link" in the "User field" "list_item"
    And the following fields match these values:
      | User field               | Country   |
      | Font                     | Times - Italic |
      | Size                     | 20        |
      | Colour                   | #045ECD   |
      | Width                    | 20        |
      | Reference point location | Top left  |
    And I press "Save" in the modal form dialogue
    # User picture.
    And I add the element "User picture" to page "1" of the "Certificate 1" site certificate template
    And I set the following fields to these values:
      | Width  | 10 |
      | Height | 10 |
    And I press "Save" in the modal form dialogue
    And I should see "User picture" in the "[data-region='elementlist']" "css_element"
    And I click on "Edit 'User picture'" "link" in the "User picture" "list_item"
    And the following fields match these values:
      | Width  | 10 |
      | Height | 10 |
    And I press "Save" in the modal form dialogue
    # Program.
    And I add the element "Program" to page "1" of the "Certificate 1" site certificate template
    And I follow "Show more..."
    And I set the following fields to these values:
      | Field  | Completed courses |
      | Position X | 100           |
    And I press "Save" in the modal form dialogue
    And I should see "Program" in the "[data-region='elementlist']" "css_element"
    And I click on "Edit 'Program'" "link" in the "[data-region='elementlist']" "css_element"
    And the following fields match these values:
      | Field  | Completed courses |
    And I press "Save" in the modal form dialogue
    And I log out

  Scenario: Delete an element from a certificate template
    When I change window size to "large"
    When I add the element "Background image" to page "1" of the "Certificate 1" site certificate template
    And I press "Save" in the modal form dialogue
    And I should see "Background image" in the "[data-region='elementlist']" "css_element"
    And I add the element "Student name" to page "1" of the "Certificate 1" site certificate template
    And I press "Save" in the modal form dialogue
    And I should see "Student name" in the "[data-region='elementlist']" "css_element"
    And I click on "Delete" "link" in the "Student name" "list_item"
    And I click on "Cancel" "button" in the "Confirm" "dialogue"
    And I should see "Background image" in the "[data-region='elementlist']" "css_element"
    And I should see "Student name" in the "[data-region='elementlist']" "css_element"
    And I click on "Delete" "link" in the "Student name" "list_item"
    And I click on "Delete" "button" in the "Confirm" "dialogue"
    And I should see "Background image" in the "[data-region='elementlist']" "css_element"
    And I should not see "Student name" in the "[data-region='elementlist']" "css_element"

  Scenario: Edit element name on a certificate template
    When I add the element "Background image" to page "1" of the "Certificate 1" site certificate template
    And I press "Save" in the modal form dialogue
    And I click on "Edit element name" "link" in the "Background image" "list_item"
    And I set the field "New value for Background image" to "Sunrise"
    And I press key "13" in the field "New value for Background image"
    And I should not see "Background image"
    And I should see "Sunrise"
    And I navigate to "Certificates > Manage certificate templates" in site administration
    And I click on "Edit content" "link"
    And I should not see "Background image"
    And I should see "Sunrise"
    And I log out

  Scenario: Rearrange elements on a certificate template
    When I add the element "Date" to page "1" of the "Certificate 1" site certificate template
    And I press "Save" in the modal form dialogue
    When I add the element "Student name" to page "1" of the "Certificate 1" site certificate template
    And I press "Save" in the modal form dialogue
    When I add the element "Code" to page "1" of the "Certificate 1" site certificate template
    And I press "Save" in the modal form dialogue
    Then "Date" "list_item" should appear before "Student name" "list_item"
    And "Student name" "list_item" should appear before "Code" "list_item"
    And I click on "Bring forward or move back" "button" in the "Date" "list_item"
    And I follow "After \" Student name \""
    And "Student name" "list_item" should appear before "Date" "list_item"
    Then "Date" "list_item" should appear before "Code" "list_item"
    And I navigate to "Certificates > Manage certificate templates" in site administration
    And I click on "Edit content" "link"
    And "Student name" "list_item" should appear before "Date" "list_item"
    Then "Date" "list_item" should appear before "Code" "list_item"
    And I log out
