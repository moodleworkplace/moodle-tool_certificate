@tool @tool_certificate
Feature: Being able to manage pages in a certificate template
  In order to ensure managing pages in a certificate template works as expected
  As an admin
  I need to manage pages in a certificate template

  Background:
    Given the following certificate templates exist:
      | name | numberofpages |
      | Certificate 1 | 1 |
    And I log in as "admin"
    And I navigate to "Courses > Manage certificate templates" in site administration
    And I click on "Edit" "link"

  Scenario: Adding a page to a certificate template
    When I follow "Add page"
    And I should see "Page 1"
    And I should see "Page 2"

  Scenario: Deleting a page from a certificate template
    When I add the element "Background image" to page "1" of the "Certificate 1" certificate template
    And I press "Save changes"
    And I add the element "Student name" to page "1" of the "Certificate 1" certificate template
    And I press "Save changes"
    And I follow "Add page"
    And I should see "Page 1"
    And I should see "Page 2"
    And I delete page "2" of the "Certificate 1" certificate template
    And I should see "Background image" in the "elementstable" "table"
    And I should see "Student name" in the "elementstable" "table"
    And I should not see "Page 1"
    And I should not see "Page 2"
