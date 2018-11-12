@tool @tool_certificate
Feature: Being able to set a site setting to determine whether or not to display the position X and Y fields
  In order to ensure the show position X and Y fields setting works as expected
  As an admin
  I need to ensure admins can see the position X and Y fields depending on the site setting

  Background:
    Given the following certificate templates exist:
      | name | numberofpages |
      | Certificate 1 | 1 |

  Scenario: Adding an element with the show position X and Y setting disabled
    When the following config values are set as admin:
      | showposxy | 0 | tool_certificate |
    And I log in as "admin"
    And I navigate to "Certificates > Manage certificate templates" in site administration
    And I click on "Edit" "link"
    And I add the element "Code" to page "1" of the "Certificate 1" certificate template
    And I should not see "Position X"
    And I should not see "Position Y"

  Scenario: Adding an element with the show position X and Y setting enabled
    When the following config values are set as admin:
      | showposxy | 1 | tool_certificate |
    And I log in as "admin"
    And I navigate to "Certificates > Manage certificate templates" in site administration
    And I click on "Edit" "link"
    And I add the element "Code" to page "1" of the "Certificate 1" certificate template
    And I should see "Position X"
    And I should see "Position Y"
    And I set the following fields to these values:
      | Position X | 5  |
      | Position Y | 10 |
    And I press "Save changes"
    And I click on ".edit-icon" "css_element" in the "Code" "table_row"
    And the following fields match these values:
      | Position X | 5  |
      | Position Y | 10 |
