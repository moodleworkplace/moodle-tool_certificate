<?php
// This file is part of the tool_certificate for Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Language strings for the certificate tool.
 *
 * @package    tool_certificate
 * @copyright  2013 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['addcertificate'] = 'Add certificate template';
$string['addcertificatedesc'] = 'Add new certificate template';
$string['addcertpage'] = 'Add page';
$string['addelement'] = 'Add element';
$string['aissueswerecreated'] = '{$a} issues were created';
$string['awardedto'] = 'Awarded to';
$string['cannotverifyallcertificates'] = 'You do not have the permission to verify all certificates on the site.';
$string['certificate'] = 'Certificate';
$string['certificates'] = 'Certificates';
$string['code'] = 'Code';
$string['copy'] = 'Copy';
$string['coursetimereq'] = 'Required minutes in course';
$string['coursetimereq_help'] = 'Enter here the minimum amount of time, in minutes, that a student must be logged into the course before they will be able to receive
the certificate.';
$string['createtemplate'] = 'Create template';
$string['certificate:issue'] = 'Issue certificate for users';
$string['certificate:issueforalltenants'] = 'Issue certificate for users in all tenants';
$string['certificate:imageforalltenants'] = 'Manage certificate images';
$string['certificate:manage'] = 'Manage certificates';
$string['certificate:manageforalltenants'] = 'Manage certificates in all tenants';
$string['certificate:verify'] = 'Vefiry all certificates for the current tenant';
$string['certificate:verifyforalltenants'] = 'Verify all certificates on the site for all tenants';
$string['certificate:viewallcertificates'] = 'View all certificates on the site for all tenants';
$string['certificateimages'] = 'Certificate images';
$string['certificatesdescription'] = 'These are all the certificates issued for the "{$a}" template.';
$string['certificatesissued'] = 'Certificates issued';
$string['settings'] = 'Certificate tool settings';
$string['deletecertpage'] = 'Delete page';
$string['deleteconfirm'] = 'Delete confirmation';
$string['deleteelement'] = 'Delete element';
$string['deleteelementconfirm'] = 'Are you sure you want to delete this element?';
$string['deleteissueconfirm'] = 'Are you sure you want to delete this certificate issue?';
$string['deleteissuedcertificates'] = 'Delete issued certificates';
$string['deletepageconfirm'] = 'Are you sure you want to delete this certificate page?';
$string['deletetemplateconfirm'] = 'Are you sure you want to delete this certificate template?';
$string['description'] = 'Description';
$string['duplicate'] = 'Duplicate';
$string['duplicateconfirm'] = 'Duplicate confirmation';
$string['duplicateselecttenant'] = 'Select the tenant to duplicate template on';
$string['duplicatetemplateconfirm'] = 'Are you sure you want to duplicate this certificate template?';
$string['editcertificate'] = 'Edit certificate';
$string['editelement'] = 'Edit element';
$string['edittemplate'] = 'Edit template';
$string['elementname'] = 'Element name';
$string['elementname_help'] = 'This will be the name used to identify this element when editing a certificate. Note: this will not displayed on the PDF.';
$string['elementplugins'] = 'Element plugins';
$string['elements'] = 'Elements';
$string['elements_help'] = 'This is the list of elements that will be displayed on the certificate.

Please note: The elements are rendered in this order. The order can be changed by using the arrows next to each element.';
$string['elementwidth'] = 'Width';
$string['elementwidth_help'] = 'Specify the width of the element - \'0\' means that there is no width constraint.';
$string['eventcertificateissued'] = 'Certificate issued';
$string['eventcertificaterevoked'] = 'Certificate revoked';
$string['eventcertificateverified'] = 'Certificate verified';
$string['eventtemplatecreated'] = 'Template created';
$string['eventtemplatedeleted'] = 'Template deleted';
$string['eventtemplateupdated'] = 'Template updated';
$string['expired'] = 'Expired';
$string['expires'] = 'Expires on';
$string['font'] = 'Font';
$string['font_help'] = 'The font used when generating this element.';
$string['fontcolour'] = 'Colour';
$string['fontcolour_help'] = 'The colour of the font.';
$string['fontsize'] = 'Size';
$string['fontsize_help'] = 'The size of the font in points.';
$string['getcertificate'] = 'View certificate';
$string['height'] = 'Height';
$string['height_help'] = 'This is the height of the certificate PDF in mm. For reference an A4 piece of paper is 297mm high and a letter is 279mm high.';
$string['hideshow'] = 'Hide/show';
$string['invalidcode'] = 'Invalid code supplied.';
$string['invalidcolour'] = 'Invalid colour chosen, please enter a valid HTML colour name, or a six-digit, or three-digit hexadecimal colour.';
$string['invalidelementfortemplate'] = 'Invalid element for template.';
$string['invalidelementwidth'] = 'Please enter a positive number.';
$string['invalidposition'] = 'Please select a positive number for position {$a}.';
$string['invalidheight'] = 'The height has to be a valid number greater than 0.';
$string['invalidmargin'] = 'The margin has to be a valid number greater than 0.';
$string['invalidpagefortemplate'] = 'Invalid page for template.';
$string['invalidwidth'] = 'The width has to be a valid number greater than 0.';
$string['issuecertificates'] = 'Issue new certificates';
$string['issuedon'] = 'Issued on';
$string['issuenewcertificate'] = 'Issue new certificate from this template';
$string['issuenewcertificates'] = 'Issue new certificates';
$string['issuenotallowed'] = 'You are not allowed to issue certificates from this template.';
$string['issueormangenotallowed'] = 'You are not allowed to issue certificates from or manage this template.';
$string['landscape'] = 'Landscape';
$string['leftmargin'] = 'Left margin';
$string['leftmargin_help'] = 'This is the left margin of the certificate PDF in mm.';
$string['listofissues'] = 'Recipients';
$string['load'] = 'Load';
$string['loadtemplate'] = 'Load template';
$string['loadtemplatemsg'] = 'Are you sure you wish to load this template? This will remove any existing pages and elements for this certificate.';
$string['managetemplates'] = 'Manage certificate templates';
$string['managetemplatesdesc'] = 'This link will take you to a new screen where you will be able to manage templates used by certificate tool.';
$string['manageelementplugins'] = 'Manage certificate element plugins';
$string['modify'] = 'Modify';
$string['modulename'] = 'Certificate tool';
$string['modulenameplural'] = 'Certificates tool';
$string['modulename_help'] = 'This module allows for the dynamic generation of PDF certificates.';
$string['modulename_link'] = 'Certificate_tool';
$string['mycertificates'] = 'My certificates';
$string['mycertificatesdescription'] = 'These are the certificates you have been issued by either email or downloading manually.';
$string['name'] = 'Name';
$string['nametoolong'] = 'You have exceeded the maximum length allowed for the name';
$string['nocertificates'] = 'There are no certificates for this course';
$string['noimage'] = 'No image';
$string['noissueswerecreated'] = 'No issues were created';
$string['norecipients'] = 'No recipients';
$string['notemplates'] = 'No templates';
$string['notissued'] = 'Not awarded';
$string['notverified'] = 'Not verified';
$string['oneissuewascreated'] = 'One issue was created';
$string['options'] = 'Options';
$string['page'] = 'Page {$a}';
$string['pluginadministration'] = 'Certificate tool administration';
$string['pluginname'] = 'Certificate tool';
$string['portrait'] = 'Portrait';
$string['posx'] = 'Position X';
$string['posx_help'] = 'This is the position in mm from the top left corner you wish the element\'s reference point to locate in the x direction.';
$string['posy'] = 'Position Y';
$string['posy_help'] = 'This is the position in mm from the top left corner you wish the element\'s reference point to locate in the y direction.';
$string['print'] = 'Print';
$string['privacy:metadata:tool_certificate_issues'] = 'The list of issued certificates';
$string['privacy:metadata:tool_certificate_issues:code'] = 'The code that belongs to the certificate';
$string['privacy:metadata:tool_certificate_issues:templateid'] = 'The ID of the certificate';
$string['privacy:metadata:tool_certificate_issues:emailed'] = 'Whether or not the certificate was emailed';
$string['privacy:metadata:tool_certificate_issues:timecreated'] = 'The time the certificate was issued';
$string['privacy:metadata:tool_certificate_issues:userid'] = 'The ID of the user who was issued the certificate';
$string['rearrangeelements'] = 'Reposition elements';
$string['rearrangeelementsheading'] = 'Drag and drop elements to change where they are positioned on the certificate.';
$string['receiveddate'] = 'Awarded on';
$string['refpoint'] = 'Reference point location';
$string['refpoint_help'] = 'The reference point is the location of an element from which its x and y coordinates are determined. It is indicated by the \'+\' that appears in the centre or corners of the element.';
$string['revoke'] = 'Revoke';
$string['revokecertificateconfirm'] = 'Are you sure you want to revoke this certificate issue from this user?';
$string['replacetemplate'] = 'Replace';
$string['requiredtimenotmet'] = 'You must spend at least a minimum of {$a->requiredtime} minutes in the course before you can access this certificate.';
$string['rightmargin'] = 'Right margin';
$string['rightmargin_help'] = 'This is the right margin of the certificate PDF in mm.';
$string['save'] = 'Save';
$string['saveandclose'] = 'Save and close';
$string['saveandcontinue'] = 'Save and continue';
$string['savechangespreview'] = 'Save changes and preview';
$string['savetemplate'] = 'Save template';
$string['search:activity'] = 'Certificate tool - activity information';
$string['selectuserstoissuecertificatefor'] = 'Select users to issue certificate for';
$string['selecttenant'] = 'Select tenant';
$string['selectedtenant'] = 'Selected tentant';
$string['shared'] = 'Shared between tenants';
$string['subplugintype_certificateelement_plural'] = 'Element plugins';
$string['templatename'] = 'Template name';
$string['templatenameexists'] = 'That template name is currently in use, please choose another.';
$string['tenant'] = 'Tenant';
$string['topcenter'] = 'Center';
$string['topleft'] = 'Top left';
$string['topright'] = 'Top right';
$string['type'] = 'Type';
$string['uploadimage'] = 'Upload image';
$string['uploadimagedesc'] = 'This link will take you to a new screen where you will be able to upload images. Images uploaded using
this method will be available throughout your site to all users who are able to create a certificate.';
$string['valid'] = 'Valid';
$string['verified'] = 'Verified';
$string['verify'] = 'Verify';
$string['verifyallcertificates'] = 'Allow verification of all certificates';
$string['verifyallcertificates_desc'] = 'When this setting is enabled any person (including users not logged in) can visit the link \'{$a}\' in order to verify any certificate on the site, rather than having to go to the verification link for each certificate.

Note - this only applies to certificates where \'Allow anyone to verify a certificate\' has been set to \'Yes\' in the certificate settings.';
$string['verifycertificate'] = 'Verify certificate';
$string['verifycertificatedesc'] = 'This link will take you to a new screen where you will be able to verify certificates on the site';
$string['verifycertificateanyone'] = 'Allow anyone to verify a certificate';
$string['verifycertificateanyone_help'] = 'This setting enables anyone with the certificate verification link (including users not logged in) to verify a certificate.';
$string['verifycertificates'] = 'Verify certificates';
$string['verifynotallowed'] = 'You are not allowed to verify certificates.';
$string['viewcertificate'] = 'View certificate';
$string['width'] = 'Width';
$string['width_help'] = 'This is the width of the certificate PDF in mm. For reference an A4 piece of paper is 210mm wide and a letter is 216mm wide.';
