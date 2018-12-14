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

Get a list of certificate templates for the current user (templates shared or on current user's tenant)

    $certificates = \tool_certificate\template::get_all();

Get a list of certificate templates given a tenantid. For templates shared between tenants, use tenantid = 0.

    $certificates = \tool_certificate\template::get_all_by_tenantid($tenantid);
