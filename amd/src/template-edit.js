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
 * AMD module used when editing a single template
 *
 * @module     tool_certificate/template-edit
 * @copyright  2019 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'jqueryui', 'tool_certificate/modal_form', 'core/notification', 'core/str', 'core/ajax', 'core/sortable_list'],
function($, jqui, ModalForm, Notification, Str, Ajax, SortableList) {
    var editReportDetailsHandler = function(e) {
        e.preventDefault();
        var el = $(e.currentTarget),
            id = el.attr('data-id'),
            name = el.attr('data-name');

        var modal = new ModalForm({
            formClass: 'tool_certificate\\form\\details',
            args: {id: id},
            modalConfig: {title: Str.get_string('editcertificate', 'tool_certificate', name)},
            saveButtonText: Str.get_string('save'),
            triggerElement: el,
        });
        modal.onSubmitSuccess = function() {
            window.location.reload();
        };
    };

    var deleteElement = function(e) {
        e.preventDefault();
        Str.get_strings([
            {key: 'confirm', component: 'moodle'},
            {key: 'deleteelementconfirm', component: 'tool_certificate', param: $(e.currentTarget).attr('data-name')},
            {key: 'delete', component: 'moodle'},
            {key: 'cancel', component: 'moodle'}
        ]).done(function(s) {
            Notification.confirm(s[0], s[1], s[2], s[3], function() {
                var promises = Ajax.call([
                    {methodname: 'tool_certificate_delete_element',
                        args: {id: $(e.currentTarget).attr('data-id')}}
                ]);
                promises[0].done(function() {
                    // TODO reload only list of elements.
                    window.location.reload();
                }).fail(Notification.exception);
            });
        }).fail(Notification.exception);
    };

    var editElement = function(e) {
        e.preventDefault();
        if ($(e.currentTarget).hasClass('isdragged')) {
            return;
        }
        var modal = new ModalForm({
            formClass: 'tool_certificate\\edit_element_form',
            args: {id: $(e.currentTarget).attr('data-id')},
            modalConfig: {title: Str.get_string('editelement', 'tool_certificate', $(e.currentTarget).attr('data-name'))},
            saveButtonText: Str.get_string('save'),
            triggerElement: $(e.currentTarget),
        });
        modal.onSubmitSuccess = function() {
            window.location.reload();
        };
    };

    var addElement = function(e) {
        e.preventDefault();
        var pageid = $(e.currentTarget).attr('data-pageid'),
            type = $(e.currentTarget).attr('data-element');
        var modal = new ModalForm({
            formClass: 'tool_certificate\\edit_element_form',
            args: {pageid: pageid, element: type},
            modalConfig: {title: Str.get_string('addelementwithname', 'tool_certificate', $(e.currentTarget).text())},
            saveButtonText: Str.get_string('save'),
            triggerElement: $(e.currentTarget),
        });
        modal.onSubmitSuccess = function() {
            window.location.reload();
        };
    };

    var deletePage = function(e) {
        e.preventDefault();
        Str.get_strings([
            {key: 'confirm', component: 'moodle'},
            {key: 'deletepageconfirm', component: 'tool_certificate'},
            {key: 'delete', component: 'moodle'},
            {key: 'cancel', component: 'moodle'}
        ]).done(function(s) {
            Notification.confirm(s[0], s[1], s[2], s[3], function() {
                window.location.href = $(e.currentTarget).attr('href');
            });
        }).fail(Notification.exception);
    };

    var addPage = function(e) {
        e.preventDefault();
        var modal = new ModalForm({
            formClass: 'tool_certificate\\form\\page',
            args: {templateid: $('[data-region="template"][data-id]').attr('data-id')},
            modalConfig: {title: Str.get_string('addcertpage', 'tool_certificate')},
            saveButtonText: Str.get_string('save'),
            triggerElement: $(e.currentTarget),
        });
        modal.onSubmitSuccess = function() {
            window.location.reload();
        };
    };

    var editPage = function(e) {
        e.preventDefault();
        var modal = new ModalForm({
            formClass: 'tool_certificate\\form\\page',
            args: {id: $(e.currentTarget).attr('data-id')},
            modalConfig: {title: Str.get_string('editpage', 'tool_certificate', $(e.currentTarget).attr('data-pagenumber'))},
            saveButtonText: Str.get_string('save'),
            triggerElement: $(e.currentTarget),
        });
        modal.onSubmitSuccess = function() {
            window.location.reload();
        };
    };

    var initSorting = function() {
        $('[data-region="page"]').each(function() {
            var sortablelist = new SortableList($(this).find('[data-region="elementlist"]'));
            sortablelist.getElementName = function(element) {
                return $.Deferred().resolve(element.find('a.quickeditlink').text());
            };
            $(this).on(SortableList.EVENTS.DROP, '[data-region="elementlist"] > *', function(_, info) {
                if (info.positionChanged) {
                    var request = {
                        methodname: 'tool_certificate_update_element',
                        args: {id: info.element.data('id'), sequence: info.element.index() + 1}
                    };
                    Ajax.call([request])[0].fail(Notification.exception);
                }
            });
        });
    };

    var mmToPx = function(value) {
        // TODO replace 2 with a ratio.
        return parseFloat(value) * 2;
    };

    var pxToMm = function(value) {
        // TODO replace 2 with a ratio.
        return Math.round(parseFloat(value) / 2);
    };

    var ptToPx = function(value) {
        // 1pt = 1/72 inch = 0.352778 mm .
        return mmToPx(value) * 0.352778;
    };

    var recalculatePDF = function() {
        var page = $(this);
        page.css("width", mmToPx(page.data('pagewidth')) + 'px');
        page.css("height", mmToPx(page.data('pageheight')) + 'px');
        page.find('[data-width]').each(function() {
            $(this).css('width', mmToPx($(this).data('width')) + 'px');
        });
        page.find('[data-height]').each(function() {
            $(this).css('height', mmToPx($(this).data('height')) + 'px');
        });
        page.find('[data-fontsize]').each(function() {
            $(this).css('font-size', ptToPx($(this).data('fontsize')) + 'px');
        });
        page.find('[data-posy]').each(function() {
            $(this).css('top', mmToPx($(this).data('posy')) + 'px');
        });
        page.find('[data-posx]').each(function() {
            // For elements with a refpoint calculate the posx of the top left corner.
            // Refpoint=0 - no change, =1 - move left by half of the width, =2 - move left by the width.
            var left = mmToPx($(this).data('posx')),
                refpoint = $(this).data('refpoint') ? parseInt($(this).data('refpoint')) : 0,
                offset = refpoint ? parseInt($(this).width()) * refpoint / 2 : 0;
            $(this).css("left", (left - offset) + 'px');
        });
        page.addClass('recalculated');
        // Init draggable.
        page.find('[data-drag-type="move"]').draggable();
    };

    var initDraggable = function() {
        var selector = '[data-region="pdf"] [data-drag-type="move"]';
        $('body')
            .on('mousedown', selector, function(e) {
                var el = $(e.currentTarget),
                    page = el.closest('[data-region="pdf"]');
                // Set element "pagecenter" to be the same width as this element and place it in the page center.
                page.find('[data-region="pagecenter"]')
                    .css("left", (mmToPx(page.data('pagecentre')) - el.width() / 2) + "px")
                    .css("width", el.width() + "px");
                el.draggable({
                    // Snap to elements only if Shift is not held.
                    snap: e.shiftKey ? false : '.snapdraggable',
                    snapMode: 'inner',
                    snapTolerance: 10,
                    // Set containment so it can't be moved far away from the page outlines.
                    containment: [
                        page.offset().left - el.width(),
                        page.offset().top - el.height(),

                        page.offset().left + mmToPx(page.data('pagewidth')),
                        page.offset().top + mmToPx(page.data('pageheight'))
                    ],
                });
            })
            .on('dragstart', selector, function(e) {
                $(e.currentTarget).addClass('isdragged');
            })
            .on('dragstop', selector, function(e) {
                var el = $(e.currentTarget),
                    page = el.closest('[data-region="pdf"]'),
                    refpoint = parseInt($(this).data('refpoint')),
                    offset = refpoint ? parseInt($(this).width()) * refpoint / 2 : 0,
                    left = pxToMm(el.offset().left - page.offset().left + offset),
                    top = pxToMm(el.offset().top - page.offset().top);
                setTimeout(function() {
                    el.removeClass('isdragged');
                }, 100);
                var request = {
                    methodname: 'tool_certificate_update_element',
                    args: {id: el.data('id'), posx: left, posy: top}
                };
                Ajax.call([request])[0].fail(Notification.exception);
            });
    };

    return {
        init: function() {
            // Add button is not inside a tab, so we can't use Tab.addButtonOnClick .
            $('[data-action="editdetails"]').on('click', editReportDetailsHandler);
            $('[data-action="deleteelement"]').on('click', deleteElement);
            $('[data-action="editelement"]').on('click', editElement);
            $('[data-action="addelement"]').on('click', addElement);
            $('[data-action="deletepage"]').on('click', deletePage);
            $('[data-element="addbutton"]').on('click', addPage);
            $('[data-action="editpage"]').on('click', editPage);
            initSorting();
            initDraggable();
            $('[data-region="pdf"]').each(recalculatePDF);
        }
    };
});
