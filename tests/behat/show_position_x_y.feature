@tool @tool_certificate @moodleworkplace @javascript
Feature: Being able to set a site setting to determine whether or not to display the position X and Y fields
  In order to ensure the show position X and Y fields setting works as expected
  As an admin
  I need to ensure admins can see the position X and Y fields depending on the site setting

  Background:
    Given the following certificate templates exist:
      | name | numberofpages |
      | Certificate 1 | 1 |

  Scenario: Adding an element with the show position X and Y setting enabled
    When I log in as "admin"
    When I change window size to "large"
    And I navigate to "Certificates > Manage certificate templates" in site administration
    And I click on "Edit content" "link"
    And I add the element "Code" to page "1" of the "Certificate 1" site certificate template
    And I follow "Show more..."
    And I should see "Position X"
    And I should see "Position Y"
    And I set the following fields to these values:
      | Position X | 5  |
      | Position Y | 10 |
    And I click on "Save" "button" in the ".modal.show .modal-footer" "css_element"
    And I click on "Edit 'Code'" "link" in the "Code" "list_item"
    And the following fields match these values:
      | Position X | 5  |
      | Position Y | 10 |
