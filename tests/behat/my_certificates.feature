@mod @tool_certificate
Feature: Being able to view the certificates you have been issued
  In order to ensure that a user can view the certificates they have been issued
  As a student
  I need to view the certificates I have been issued

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
    And the following "certificate templates" exist:
      | name |
      | Test template 1 |
      | Test template 2 |

  Scenario: View your issued certificates on the my certificates page
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Certificate 1"
    And I press "View certificate"
    And I follow "Profile" in the user menu
    And I follow "My certificates"
    And I should see "Certificate 1"
    And I should not see "Custom certificate 2"
    And I am on "Course 2" course homepage
    And I follow "Custom certificate 2"
    And I press "View certificate"
    And I follow "Profile" in the user menu
    And I follow "My certificates"
    And I should see "Certificate 1"
    And I should see "Custom certificate 2"
