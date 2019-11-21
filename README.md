Tool Certificate
================

Issue a new certificate for a user from an existing template and saving custom data.

    $issuedata = ['certificationname' => 'Certification 1', 'programname' => 'Program 1', 'completiondate' => time(),
                  'completedcourses' => [
                     $course1->id => $course1->fullname,
                     $course2->id => $course2->fullname,
                 ]];

    $component = 'tool_xyz';

    $template = \tool_certificate\template::find_by_id($templateid);

    $expires = strtotime('+ 1 year');

    $template->issue_certificate($userid, $expires, $issuedata, $component);

Get a list of certificate templates for the current user (shared templates or templates on current user toplevel category)

    $certificates = \tool_certificate\template::get_all();
