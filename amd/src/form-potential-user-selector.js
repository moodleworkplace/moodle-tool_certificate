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
 * User selector form on modal based on form-potential-user-selector 2016 Damyon Wiese.
 *
 * @module     tool_certificate/form-potential-user-selector
 * @package    tool_certificate
 * @copyright  2018 David Matamoros <davidmc@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/templates', 'core/str'], function($, Ajax, Templates, Str) {

    // Maximum number of users to show.
    var MAXUSERS = 100;

    return {

        processResults: function(selector, results) {
            var users = [];
            if ($.isArray(results)) {
                $.each(results, function(index, user) {
                    users.push({
                        value: user.id,
                        label: user._label
                    });
                });
                return users;

            } else {
                return results;
            }
        },

        transport: function(selector, query, success, failure) {
            var promise;
            promise = Ajax.call([{
                methodname: 'tool_certificate_potential_users_selector',
                args: {
                    search: query,
                    itemid: $(selector).data('itemid'),
                }
            }]);
            promise[0].then(function(results) {
                var promises = [],
                    i = 0;
                if (results.length <= MAXUSERS) {
                    // Render the label.
                    $.each(results, function(index, user) {
                        var ctx = user,
                            identity = [];
                        $.each(['idnumber', 'email', 'phone1', 'phone2', 'department', 'institution'], function(i, k) {
                            if (typeof user[k] !== 'undefined' && user[k] !== '') {
                                ctx.hasidentity = true;
                                identity.push(user[k]);
                            }
                        });
                        ctx.identity = identity.join(', ');
                        promises.push(Templates.render('tool_certificate/form-user-selector-suggestion', ctx));
                    });

                    // Apply the label to the results.
                    return $.when.apply($.when, promises).then(function() {
                        var args = arguments;
                        $.each(results, function(index, user) {
                            user._label = args[i];
                            i++;
                        });
                        success(results);
                        return;
                    });

                } else {
                    return Str.get_string('toomanyuserstoshow', 'core', '>' + MAXUSERS).then(function(toomanyuserstoshow) {
                        success(toomanyuserstoshow);
                        return;
                    });
                }

            }).fail(failure);
        }
    };
});
