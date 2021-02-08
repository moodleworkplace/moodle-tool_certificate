Certificate manager
===================

This plugin allows users to create certificate templates on the system and course category levels.
Certificate templates can have user fields such as user name, profile picture, etc, and also
additional dynamic fields that are added by the issuer (through API).

The built-in interface allows users to issue certificates manually and browse the issued certificates.

A verification code / link / QR code can be added to the certificate template. Certificates can be
verified by unauthenticated users even on sites with forced login and no guest access.

Other plugins can depend on this plugin to issue certificates based on some criteria, for
example **Course certificates (mod_coursecertificate)** is an activity module that will automatically
issue certificates when the student satisfies the access restrictions. The mod_coursecertificate
plugin will send the course name and completion information to the tool_certificate plugin, so
if these fields are included in the template, they will be displayed on the certificate.

Acknowledgements and history
----------------------------

This plugin was originally copied from mod_customcert plugin. Big thanks to Mark Nelson for all the
work on it.

Certificate manager has a different approach to storing templates and issuing certificates. It is
completely independent of mod_customcert and both plugins can be used at the same time without
any conflicts.

There are a lot of similarities but there are also significant structural differences, such as:

- Templates are designed on system and course category levels (and not inside the courses)
- Allows viewing cross-course reports on issued certificates
- Allows issuing certificates manually and not linked to any courses
- Provides API to issue certificates from other plugins with customisable dynamic fields
- Generated PDFs are stored and contain information as it was at the moment when the certificate
  was issued; certificate is still available even if user data is changed, course is deleted, etc.
- Has some improvements to the UX
- It is not a course activity plugin but an admin tool plugin (see also mod_coursecertificate)

API
---

As mentioned above, this plugin works best in combination with other plugins. For example, in
Moodle Workplace it is used by Dynamic rules to automatically issue certificates on completion
of Programs, Certifications and Courses. Information about those programs, certifications and
courses is added to the issue data.

Plugins can implement a callback in lib.php:

    function PLUGINNAME_tool_certificate_fields() {}

In this callback the plugin can define additional fields that the plugin can send. The Certificate
Manager then will make these fields available in the Template designer. You can find an example
of this callback in mod_coursecertificate.

To retrieve a list of templates available in the context:

    \tool_certificate\permission::get_visible_templates($context)

To issue certificate:

    $template = \tool_certificate\template::instance($templateid);
    $template->issue_certificate(....)
