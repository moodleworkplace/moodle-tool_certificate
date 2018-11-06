@tool @tool_certificate
Feature: Being able to manage pages in a certificate template
  In order to ensure managing pages in a certificate template works as expected
  As a teacher
  I need to manage pages in a certificate template

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "certificate templates" exist:
      | name |
      | Test template 1 |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Custom certificate 1"
    And I navigate to "Edit certificate" in current page administration

  Scenario: Adding a page to a certificate template
    And I follow "Add page"
    And I should see "Page 1"
    And I should see "Page 2"

  Scenario: Deleting a page from a certificate template
    And I add the element "Background image" to page "1" of the "Custom certificate 1" certificate template
    And I press "Save changes"
    And I add the element "Student name" to page "1" of the "Custom certificate 1" certificate template
    And I press "Save changes"
    And I follow "Add page"
    And I should see "Page 1"
    And I should see "Page 2"
    And I delete page "2" of the "Custom certificate 1" certificate template
    And I should see "Background image" in the "elementstable" "table"
    And I should see "Student name" in the "elementstable" "table"
    And I should not see "Page 1"
    And I should not see "Page 2"
