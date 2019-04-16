@tool @tool_certificate @javascript @moodleworkplace
Feature: Issue certificate with dynamic rules
  In order to issue a certificate in a dynamic rule
  As a manager
  I need to be able to create and edit certificate outcome

  Scenario: Add a rule with certificate outcome
    Given the following certificate templates exist:
      | name |
      | Certificate 1 |
      | Certificate 2 |
    When I log in as "admin"
    And I navigate to "Dynamic rules" in site administration
    And I follow "New rule"
    And I set the following fields to these values:
      | Name | Rule1 |
    And I press "Save" in the modal form dialogue
    And I follow "Actions"
    And I click on "Issue certificate" "link" in the "#ruleoutcomes" "css_element"
    And I open the autocomplete suggestions list
    And I click on "Certificate 1" item in the autocomplete list
    And I press key "27" in the field "Select certificate"
    And I press "Save changes"
    Then I should see "Issue certificate 'Certificate 1' to users"
    And I follow "Edit action"
    And I open the autocomplete suggestions list
    And I click on "Certificate 2" item in the autocomplete list
    And I press key "27" in the field "Select certificate"
    And I press "Save changes"
    Then I should see "Issue certificate 'Certificate 2' to users"
