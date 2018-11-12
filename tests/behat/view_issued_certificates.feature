@tool @tool_certificate
Feature: Being able to view the certificates that have been issued
  In order to ensure that a user can view the certificates that have been issued
  As an admin
  I need to view the certificates that have been issued

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
      | student2 | Student   | 2        | student2@example.com |
    And the following certificate templates exist:
      | name |
      | Certificate 1|
    And the following certificate issues exist:
      | template | user |
      | Certificate 1 | student1 |
      | Certificate 1 | student2 |
    And I log in as "admin"

  Scenario: View the issued certificates
    When I navigate to "Certificates > Manage certificate templates" in site administration
    And I click on "Certificates issued" "link"
    And I should see "Student 1"
    And I should see "Student 2"

  Scenario: Delete an issued certificate
    When I navigate to "Certificates > Manage certificate templates" in site administration
    And I click on "Certificates issued" "link"
    And I should see "Student 1"
    And I should see "Student 2"
    And I click on ".delete-icon" "css_element" in the "Student 2" "table_row"
    And I press "Cancel"
    And I should see "Student 1"
    And I should see "Student 2"
    And I click on ".delete-icon" "css_element" in the "Student 2" "table_row"
    And I press "Continue"
    And I should see "Student 1"
    And I should not see "Student 2"
