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

  Scenario: View share certificate on LinkedIn
    When I log in as "student1"
    And the following config values are set as admin:
      | show_shareonlinkedin | true | tool_certificate |
    And I follow "Profile" in the user menu
    And I click on "//a[contains(.,'My certificates') and contains(@href,'tool/certificate')]" "xpath_element"
    Then I should see "Share on LinkedIn"
    And I should see a share on LinkedIn link for "Certificate 1"

  Scenario: Do not view share certificate on LinkedIn
    When I log in as "student1"
    And the following config values are set as admin:
      | show_shareonlinkedin | | tool_certificate |
    And I follow "Profile" in the user menu
    And I click on "//a[contains(.,'My certificates') and contains(@href,'tool/certificate')]" "xpath_element"
    Then I should not see "Share on LinkedIn"
    And I should not see a share on LinkedIn link for "Certificate 1"
