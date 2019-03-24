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
    # Background image.
    When I change window size to "large"
    When I add the element "Background image" to page "1" of the "Certificate 1" certificate template
    And I press "Save" in the modal form dialogue
    And I should see "Background image" in the "[data-region='elementlist']" "css_element"
    # Border.
    And I add the element "Border" to page "1" of the "Certificate 1" certificate template
    And I set the following fields to these values:
      | Width  | 2 |
      | Colour | #045ECD |
    And I press "Save" in the modal form dialogue
    And I should see "Border" in the "[data-region='elementlist']" "css_element"
    And I click on "Edit" "link" in the "Border" "list_item"
    And the following fields match these values:
      | id_width | 2 |
      | Colour | #045ECD |
    And I press "Save" in the modal form dialogue
    # Code.
    And I add the element "Code" to page "1" of the "Certificate 1" certificate template
    And I set the following fields to these values:
      | Font                     | Helvetica |
      | Size                     | 20        |
      | Colour                   | #045ECD   |
      | Width                    | 20        |
      | Reference point location | Top left  |
    And I press "Save" in the modal form dialogue
    And I should see "Code" in the "[data-region='elementlist']" "css_element"
    And I click on "Edit" "link" in the "Code" "list_item"
    And the following fields match these values:
      | Font                     | Helvetica |
      | Size                     | 20        |
      | Colour                   | #045ECD   |
      | Width                    | 20        |
      | Reference point location | Top left  |
    And I press "Save" in the modal form dialogue
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
    And I press "Save" in the modal form dialogue
    And I should see "Date" in the "[data-region='elementlist']" "css_element"
    And I click on "Edit" "link" in the "Date" "list_item"
    And the following fields match these values:
      | Date item                | Issued date |
      | Date format              | 2                 |
      | Font                     | Helvetica         |
      | Size                     | 20                |
      | Colour                   | #045ECD           |
      | Width                    | 20                |
      | Reference point location | Top left          |
    And I press "Save" in the modal form dialogue
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
    And I press "Save" in the modal form dialogue
    And I should see "Digital signature" in the "[data-region='elementlist']" "css_element"
    And I click on "Edit" "link" in the "Digital signature" "list_item"
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
    And I add the element "Image" to page "1" of the "Certificate 1" certificate template
    And I set the following fields to these values:
      | Width  | 25 |
      | Height | 15 |
    And I press "Save" in the modal form dialogue
    And I should see "Image" in the "[data-region='elementlist']" "css_element"
    And I click on "Edit" "link" in the "Image" "list_item"
    And the following fields match these values:
      | Width  | 25 |
      | Height | 15 |
    And I press "Save" in the modal form dialogue
    # Student name.
    And I add the element "Student name" to page "1" of the "Certificate 1" certificate template
    And I set the following fields to these values:
      | Font                     | Helvetica |
      | Size                     | 20        |
      | Colour                   | #045ECD   |
      | Width                    | 20        |
      | Reference point location | Top left  |
    And I press "Save" in the modal form dialogue
    And I should see "Student name" in the "[data-region='elementlist']" "css_element"
    And I click on "Edit" "link" in the "Student name" "list_item"
    And the following fields match these values:
      | Font                     | Helvetica |
      | Size                     | 20        |
      | Colour                   | #045ECD   |
      | Width                    | 20        |
      | Reference point location | Top left  |
    And I press "Save" in the modal form dialogue
    # Text.
    And I add the element "Text" to page "1" of the "Certificate 1" certificate template
    And I set the following fields to these values:
      | Text                     | Test this |
      | Font                     | Helvetica |
      | Size                     | 20        |
      | Colour                   | #045ECD   |
      | Width                    | 20        |
      | Reference point location | Top left  |
    And I press "Save" in the modal form dialogue
    And I should see "Text" in the "[data-region='elementlist']" "css_element"
    And I click on "Edit" "link" in the "Text" "list_item"
    And the following fields match these values:
      | Text                     | Test this |
      | Font                     | Helvetica |
      | Size                     | 20        |
      | Colour                   | #045ECD   |
      | Width                    | 20        |
      | Reference point location | Top left  |
    And I press "Save" in the modal form dialogue
    # User field.
    And I add the element "User field" to page "1" of the "Certificate 1" certificate template
    And I set the following fields to these values:
      | User field               | Country   |
      | Font                     | Helvetica |
      | Size                     | 20        |
      | Colour                   | #045ECD   |
      | Width                    | 20        |
      | Reference point location | Top left  |
    And I press "Save" in the modal form dialogue
    And I should see "User field" in the "[data-region='elementlist']" "css_element"
    And I click on "Edit" "link" in the "User field" "list_item"
    And the following fields match these values:
      | User field               | Country   |
      | Font                     | Helvetica |
      | Size                     | 20        |
      | Colour                   | #045ECD   |
      | Width                    | 20        |
      | Reference point location | Top left  |
    And I press "Save" in the modal form dialogue
    # User picture.
    And I add the element "User picture" to page "1" of the "Certificate 1" certificate template
    And I set the following fields to these values:
      | Width  | 10 |
      | Height | 10 |
    And I press "Save" in the modal form dialogue
    And I should see "User picture" in the "[data-region='elementlist']" "css_element"
    And I click on "Edit" "link" in the "User picture" "list_item"
    And the following fields match these values:
      | Width  | 10 |
      | Height | 10 |
    And I press "Save" in the modal form dialogue
    And I log out

  Scenario: Delete an element from a certificate template
    When I change window size to "large"
    When I add the element "Background image" to page "1" of the "Certificate 1" certificate template
    And I press "Save" in the modal form dialogue
    And I should see "Background image" in the "[data-region='elementlist']" "css_element"
    And I add the element "Student name" to page "1" of the "Certificate 1" certificate template
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
