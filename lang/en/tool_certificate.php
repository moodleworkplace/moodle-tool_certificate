<?php
// This file is part of the tool_certificate plugin for Moodle - http://moodle.org/
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

$string['addcertpage'] = 'New page';
$string['addelement'] = 'Add element';
$string['addelementwithname'] = 'Add \'{$a}\' element';
$string['after'] = 'After';
$string['aissueswerecreated'] = '{$a} certificates were issued';
$string['aligncentre'] = 'Centre';
$string['alignleft'] = 'Left';
$string['alignment'] = 'Text alignment';
$string['alignment_help'] = 'Right alignment of the text will mean that element coordinates (Position X and Position Y) will refer to the top right corner of the text box, in center alignment they will refer to the top middle and in left alignment to the top left corner';
$string['alignright'] = 'Right';
$string['allowfilters'] = 'Allowed filters for the PDF content';
$string['allowfilters_desc'] = 'Only the selected filters (if enabled) will apply to the texts inside the certificate PDFs';
$string['archived'] = 'Archived';
$string['availableincourses'] = 'Available in sub-categories and courses';
$string['availableincourses_help'] = 'By enabling this option, users (with issue capabilities) will be able to use this template in every course inside the selected category and the courses inside its sub-categories as well. If this option is disabled, this template will be available exclusively to users with issue capabilities in the selected category.';
$string['certificate'] = 'Certificate';
$string['certificate:image'] = 'Manage certificate images';
$string['certificate:issue'] = 'Issue certificate to users';
$string['certificate:manage'] = 'Manage certificates';
$string['certificate:verify'] = 'Verify any certificate';
$string['certificate:viewallcertificates'] = 'View all issued certificates and templates';
$string['certificate_customfield'] = 'Certificate custom fields';
$string['certificatecopy'] = '{$a} (copy)';
$string['certificateelement'] = 'Certificate element';
$string['certificateimages'] = 'Certificate images';
$string['certificates'] = 'Certificates';
$string['certificatesettings'] = 'Certificates settings';
$string['certificatesissues'] = 'Issued certificates';
$string['certificatetemplate'] = 'Certificate template';
$string['certificatetemplatename'] = 'Certificate template name';
$string['certificatetemplates'] = 'Certificate templates';
$string['changeelementsequence'] = 'Bring forward or move back';
$string['code'] = 'Code';
$string['codewithlink'] = 'Code with link';
$string['coursecategorywithlink'] = 'Course category with link';
$string['createtemplate'] = 'New certificate template';
$string['customfield_previewvalue'] = 'Preview value';
$string['customfield_previewvalue_help'] = 'Value displayed when previewing the certificate template';
$string['customfield_visible'] = 'Visible';
$string['customfield_visible_help'] = 'Allow to select this field on the certificate template';
$string['customfieldsettings'] = 'Common certificate custom fields settings';
$string['deleteelement'] = 'Delete element';
$string['deleteelementconfirm'] = 'Are you sure you want to delete the element \'{$a}\'?';
$string['deletepage'] = 'Delete page';
$string['deletepageconfirm'] = 'Are you sure you want to delete this certificate page?';
$string['deletetemplateconfirm'] = 'Are you sure you want to delete the certificate template \'{$a}\' and all associated data? This action cannot be undone.';
$string['demotmpl'] = 'Certificate demo template';
$string['demotmplawardedon'] = 'Awarded on';
$string['demotmplawardedto'] = 'This certificate is awarded to';
$string['demotmplbackground'] = 'Background image';
$string['demotmplcoursefullname'] = 'Course full name';
$string['demotmpldirector'] = 'School Director';
$string['demotmplforcompleting'] = 'For completing the course';
$string['demotmplissueddate'] = 'Issued date';
$string['demotmplqrcode'] = 'QR code';
$string['demotmplsignature'] = 'Signature';
$string['demotmplusername'] = 'User name';
$string['do_not_show'] = 'Do not show';
$string['duplicate'] = 'Duplicate';
$string['duplicatetemplateconfirm'] = 'Are you sure you want to duplicate the template \'{$a}\'?';
$string['editelement'] = 'Edit \'{$a}\'';
$string['editelementname'] = 'Edit element name';
$string['editpage'] = 'Edit page {$a}';
$string['edittemplatename'] = 'Edit template name';
$string['elementname'] = 'Element name';
$string['elementname_help'] = 'This will be the name used to identify this element when editing a certificate. Note that this will not displayed on the PDF.';
$string['elementwidth'] = 'Width';
$string['elementwidth_help'] = 'Specify the width of the element. Zero (0) means that there is no width constraint.';
$string['entitycertificate'] = 'Certificate';
$string['entitycertificateissue'] = 'Issued certificate';
$string['eventcertificateissued'] = 'Certificate issued';
$string['eventcertificateregenerated'] = 'Certificate regenerated';
$string['eventcertificaterevoked'] = 'Certificate revoked';
$string['eventcertificateverified'] = 'Certificate verified';
$string['eventtemplatecreated'] = 'Template created';
$string['eventtemplatedeleted'] = 'Template deleted';
$string['eventtemplateupdated'] = 'Template updated';
$string['expired'] = 'Expired';
$string['expiredcertificate'] = 'This certificate has expired';
$string['expirydate'] = 'Expiry date';
$string['expirydatetype'] = 'Expiry date type';
$string['font'] = 'Font';
$string['font_help'] = 'The font used when generating this element.';
$string['fontcolour'] = 'Colour';
$string['fontcolour_help'] = 'The colour of the font.';
$string['fontsize'] = 'Size';
$string['fontsize_help'] = 'The size of the font in points.';
$string['hideshow'] = 'Hide/show';
$string['invalidcolour'] = 'Invalid colour chosen. Please enter a valid HTML colour name, or a six-digit, or three-digit hexadecimal colour.';
$string['invalidelementwidth'] = 'Please enter a positive number.';
$string['invalidheight'] = 'The height has to be a valid number greater than 0.';
$string['invalidmargin'] = 'The margin has to be a valid number greater than 0.';
$string['invalidposition'] = 'Please select a positive number for position {$a}.';
$string['invalidwidth'] = 'The width has to be a valid number greater than 0.';
$string['issuecertificates'] = 'Issue certificates';
$string['issuedcertificates'] = 'Issued certificates';
$string['issueddate'] = 'Date issued';
$string['issuelang'] = 'Issue certificates in user language';
$string['issuelangdesc'] = 'On multi-lingual sites when user language is different from the site language the certificates will be generated in the user\'s language, otherwise all certificates will be generated in the site default language.';
$string['issuenotallowed'] = 'You are not allowed to issue certificates from this template.';
$string['issueormangenotallowed'] = 'You are not allowed to issue certificates from or manage this template.';
$string['leftmargin'] = 'Left margin';
$string['leftmargin_help'] = 'This is the left margin of the certificate PDF in mm.';
$string['linkedinorganizationid'] = 'LinkedIn organization id';
$string['linkedinorganizationid_desc'] = 'The id of the LinkedIn organization issuing certificates.

Where do I find my LinkedIn organization id?

1.    Log in to LinkedIn as the admin for your business\' Organisation Page
2.    Check the URL used when you are logged in as the admin. (The URL should resemble "https://linkedin.com/company/xxxxxxx/admin")
3.    Your LinkedIn organization id will be the seven-digit number in the URL (Shown as "xxxxxxx" in the step above)';
$string['manageelementplugins'] = 'Manage certificate element plugins';
$string['managetemplates'] = 'Manage certificate templates';
$string['messageprovider:certificateissued'] = 'Certificate received';
$string['milimeter'] = 'mm';
$string['mycertificates'] = 'My certificates';
$string['mycertificatesdescription'] = 'These are the certificates you have been issued by either email or downloading manually.';
$string['name'] = 'Name';
$string['nametoolong'] = 'You have exceeded the maximum length allowed for the name';
$string['never'] = 'Never';
$string['noimage'] = 'No image';
$string['noissueswerecreated'] = 'No certificates were issued';
$string['notificationmsgcertificateissued'] = 'Hi {$a->fullname},<br /><br />Your certificate is available! You will find it here:
<a href="{$a->url}">My Certificates</a>';
$string['notificationsubjectcertificateissued'] = 'Your certificate is available!';
$string['notverified'] = 'Not verified';
$string['numberofpages'] = 'Number of pages';
$string['oneissuewascreated'] = 'One issue was created';
$string['page'] = 'Page {$a}';
$string['pageheight'] = 'Page height';
$string['pageheight_help'] = 'This is the height of the certificate PDF in mm. For reference an A4 piece of paper is 297mm high and a letter is 279mm high.';
$string['pagewidth'] = 'Page width';
$string['pagewidth_help'] = 'This is the width of the certificate PDF in mm. For reference an A4 piece of paper is 210mm wide and a letter is 216mm wide.';
$string['pluginname'] = 'Certificate manager';
$string['posx'] = 'Position X';
$string['posx_help'] = 'This is the position in mm from the top left corner you wish the element\'s reference point to locate in the x direction.';
$string['posy'] = 'Position Y';
$string['posy_help'] = 'This is the position in mm from the top left corner you wish the element\'s reference point to locate in the y direction.';
$string['privacy:metadata:tool_certificate:issues'] = 'The list of issued certificates';
$string['privacy:metadata:tool_certificate_issues:code'] = 'The code that belongs to the certificate';
$string['privacy:metadata:tool_certificate_issues:expires'] = 'The timestamp when the certificate expires. 0 if does not expire.';
$string['privacy:metadata:tool_certificate_issues:templateid'] = 'The ID of the certificate';
$string['privacy:metadata:tool_certificate_issues:timecreated'] = 'The time the certificate was issued';
$string['privacy:metadata:tool_certificate_issues:userid'] = 'The ID of the user who was issued the certificate';
$string['reg_wpcertificates'] = 'Number of certificates ({$a})';
$string['reg_wpcertificatesissues'] = 'Number of issued certificates ({$a})';
$string['regenerate'] = 'Regenerate';
$string['regeneratefileconfirm'] = 'Are you sure you want to regenerate the certificate issued to this user?';
$string['regenerateissuefile'] = 'Regenerate issue file';
$string['revoke'] = 'Revoke';
$string['revokecertificateconfirm'] = 'Are you sure you want to revoke this certificate issue from this user?';
$string['rightmargin'] = 'Right margin';
$string['rightmargin_help'] = 'This is the right margin of the certificate PDF in mm.';
$string['selectdate'] = 'Select date';
$string['selectuserstoissuecertificatefor'] = 'Select users to issue certificate to';
$string['shared'] = 'Shared';
$string['shareonlinkedin'] = 'Share on LinkedIn';
$string['show_link_to_certificate_page'] = 'Show link to certificate page';
$string['show_link_to_verification_page'] = 'Show link to verification page';
$string['show_shareonlinkedin'] = 'Show share on LinkedIn';
$string['show_shareonlinkedin_desc'] = 'If the "Share on LinkedIn" button should be shown on the my certificates page. Linking to the certificate PDF directly is more visual but may show errors for expired certificates.';
$string['status'] = 'Status';
$string['subplugintype_certificateelement'] = 'Certificate element plugin';
$string['subplugintype_certificateelement_plural'] = 'Certificate element plugins';
$string['template'] = 'Template';
$string['templatepermission'] = 'Permission to access template';
$string['templatepermissionany'] = 'Do not check';
$string['templatepermissionyes'] = 'Check permission of current user';
$string['timecreated'] = 'Time created';
$string['uploadimage'] = 'Upload image';
$string['valid'] = 'Valid';
$string['validcertificate'] = 'This certificate is valid';
$string['verified'] = 'Verified';
$string['verify'] = 'Verify';
$string['verifycertificates'] = 'Verify certificates';
$string['verifynotallowed'] = 'You are not allowed to verify certificates.';
$string['viewcertificate'] = 'View certificate';
$string['viewmore'] = 'View more on the web';

// Deprecated since 4.2.
$string['editcertificate'] = 'Edit certificate template \'{$a}\'';
$string['issuenewcertificate'] = 'Issue certificates from this template';
$string['nopermissionform'] = 'You don\'t have permission to access this form.';
