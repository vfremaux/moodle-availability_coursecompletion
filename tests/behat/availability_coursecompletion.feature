@availability @availability_coursecompletion
Feature: availability_coursecompletion
  In order to control student access to activities
  As a teacher
  I need to set completion conditions which prevent student access

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format | enablecompletion |
      | Course 1 | C1        | topics | 1                |
      | Course 2 | C2        | topics | 1                |
    And the following "users" exist:
      | username |
      | teacher1 |
      | student1 |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | teacher1 | C2     | editingteacher |
      | student1 | C2     | student        |
    And the following config values are set as admin:
      | enableavailability  | 1 |
      | enablecompletion | 1 |

  @javascript
  Scenario: Test condition
    # Basic setup in trigger course.
    Given I log in as "teacher1"
    And I follow "Course 2"
    And I turn editing mode on

    # Add a Page with a completion tickbox in trigger course.
    And I add a "Page" to section "1" and I fill the form with:
      | Name                | Page 2 |
      | Description         | Test   |
      | Page content        | Test   |
      | Completion tracking | 1      |

    # And another one in trigger course.
    And I follow "My page"
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Page" to section "1"
    And I set the following fields to these values:
      | Name         | Page 1 |
      | Description  | Test   |
      | Page content | Test   |
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Course completion" "button" in the "Add restriction..." "dialogue"
    And I click on ".availability-item .availability-eye img" "css_element"
    And I set the field "Course" to "Course 2"
    And I press "Save and return to course"

    # Log back in as student.
    And I log out
    And I log in as "student1"
    And I follow "Course 1"

    # Page 2 should not appear yet.
    Then I should not see "Page 1" in the "region-main" "region"

    # Mark page 1 complete
    When I log out
    And I log in as "student1"
    And I follow "Course 2"
    And I click on ".togglecompletion input[type=image]" "css_element"
    And I log out
    And I log in as "student1"
    And I follow "Course 1"
    Then I should see "Page 1" in the "region-main" "region"
