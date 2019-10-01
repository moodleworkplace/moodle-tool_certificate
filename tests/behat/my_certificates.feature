@tool @tool_certificate @moodleworkplace
Feature: Being able to view the certificates you have been issued
  In order to ensure that a user can view the certificates they have been issued
  As a student
  I need to view the certificates I have been issued

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
      | manager1 | Manager   | 1        | manager1@example.com |
    And the following certificate templates exist:
      | name |
      | Certificate 1 |
      | Certificate 2 |
    And the following certificate issues exist:
      | template | user |
      | Certificate 1 | student1 |

  Scenario: View your issued site certificates on the my certificates page
    When I log in as "student1"
    And I follow "Profile" in the user menu
    And I click on "//a[contains(.,'My certificates') and contains(@href,'tool/certificate')]" "xpath_element"
    Then I should see "Certificate 1"
    And I should not see "Certificate 2"

  Scenario: View the certificates from people user is manager over
    Given user "manager1" has a global manager position over users "student1" with permissions "7"
    When I log in as "manager1"
    And I click on "Profile" "link" in the "Student 1" "table_row"
    And I follow "Certificates"
    Then I should see "Certificate 1"
