@tool @tool_certificate
Feature: Being able to manage site templates
  In order to ensure managing site templates works as expected
  As an admin
  I need to manage and load site templates

  Background:
    Given I log in as "admin"

  Scenario: Adding a site template
    When I navigate to "Certificates > Manage certificate templates" in site administration
    And I press "Create template"
    And I set the field "Name" to "Site template"
    And I press "Save changes"
    And I add the element "Border" to page "1" of the "Site template" certificate template
    And I set the following fields to these values:
      | Width  | 5 |
      | Colour | #045ECD |
    And I press "Save changes"
    And I follow "Manage certificate templates"
    Then I should see "Site template"

  Scenario: Deleting a site template
    When I navigate to "Certificates > Manage certificate templates" in site administration
    And I press "Create template"
    And I set the field "Name" to "Site template"
    And I press "Save changes"
    And I follow "Manage certificate templates"
    And I click on ".delete-icon" "css_element" in the "Site template" "table_row"
    And I press "Cancel"
    And I should see "Site template"
    And I click on ".delete-icon" "css_element" in the "Site template" "table_row"
    And I press "Continue"
    Then I should not see "Site template"

  Scenario: Duplicating a site template
    When I navigate to "Certificates > Manage certificate templates" in site administration
    And I press "Create template"
    And I set the field "Name" to "Site template"
    And I press "Save changes"
    And I follow "Manage certificate templates"
    And I click on ".duplicate-icon" "css_element" in the "Site template" "table_row"
    And I press "Cancel"
    And I should see "Site template"
    And I should not see "Site template (duplicate)"
    And I click on ".duplicate-icon" "css_element" in the "Site template" "table_row"
    And I press "Continue"
    Then I should see "Site template"
    And I should see "Site template (duplicate)"
