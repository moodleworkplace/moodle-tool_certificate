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
 * Module used when editing template details
 *
 * @module     tool_certificate/template-details
 * @copyright  2022 Ruslan Kabalin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import DynamicForm from 'core_form/dynamicform';
import Notification from 'core/notification';
import {get_string as getString} from 'core/str';
import {prefetchStrings} from 'core/prefetch';
import {add as addToast} from 'core/toast';

const init = () => {
    prefetchStrings('moodle', [
        'changessaved',
    ]);

    const dynamicForm = new DynamicForm(document.querySelector('#template-details'), 'tool_certificate\\form\\details');
    dynamicForm.addEventListener(dynamicForm.events.FORM_SUBMITTED, (e) => {
        e.preventDefault();
        dynamicForm.load({id: e.detail.id});
        getString('changessaved', 'moodle').then((string) => {
            addToast(string, {type: 'success'});
            return null;
        }).catch(Notification.exception);
    });
};

export default {
    init: init
};
