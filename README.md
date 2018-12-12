Tool Certificate
================

Issue a new certificate for a user from an existing template.

    $template = \tool_certificate\template::find_by_id($templateid);
    $template->issue_certificate($userid, $expires, $data, $component);
