// This file is part of Moodle - http://moodle.org/
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
 * AMD module used when rearranging a custom certificate.
 *
 * @module     tool_certificate/rearrange-area
 * @package    tool_certificate
 * @copyright  2016 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/yui', 'core/fragment', 'tool_certificate/dialogue', 'core/notification',
        'core/str', 'core/templates', 'core/ajax', 'tool_wp/modal_form'],
        function($, Y, fragment, Dialogue, notification, str, template, ajax, ModalForm) {

            /**
             * RearrangeArea class.
             *
             * @param {String} selector The rearrange PDF selector
             */
            var RearrangeArea = function(selector) {
                this._node = $(selector);
                this._setEvents();
            };

            RearrangeArea.prototype.CUSTOMCERT_REF_POINT_TOPLEFT = 0;
            RearrangeArea.prototype.CUSTOMCERT_REF_POINT_TOPCENTER = 1;
            RearrangeArea.prototype.CUSTOMCERT_REF_POINT_TOPRIGHT = 2;
            RearrangeArea.prototype.PIXELSINMM = 3.779527559055;

            RearrangeArea.prototype._editElement = function(e) {
                var elementid = $(e.currentTarget).attr('data-id');
                var modal = new ModalForm({
                    formClass: 'tool_certificate\\edit_element_form',
                    args: this._getPosition(elementid),
                    modalConfig: {title: str.get_string('editelement', 'tool_certificate', $(e.currentTarget).attr('data-name'))},
                    saveButtonText: str.get_string('save'),
                    triggerElement: $(e.currentTarget),
                });
                modal.onSubmitSuccess = function(data) {
                    // Update the DOM to reflect the adjusted value.
                    var elementNode = $('#element-' + elementid);
                    var refpoint = parseInt(data.refpoint);
                    var refpointClass = '';
                    if (refpoint === this.CUSTOMCERT_REF_POINT_TOPLEFT) {
                        refpointClass = 'refpoint-left';
                    } else if (refpoint === this.CUSTOMCERT_REF_POINT_TOPCENTER) {
                        refpointClass = 'refpoint-center';
                    } else if (refpoint === this.CUSTOMCERT_REF_POINT_TOPRIGHT) {
                        refpointClass = 'refpoint-right';
                    }
                    elementNode.html(data.html);
                    // Update the ref point.
                    elementNode.removeClass();
                    elementNode.addClass('element ' + refpointClass);
                    elementNode.attr('data-refpoint', refpoint);
                    elementNode.attr('data-name', data.name);
                    // Move the element.
                    this._setPosition(elementid, refpoint, parseInt(data.posx), parseInt(data.posy));
                }.bind(this);
            };

            RearrangeArea.prototype._setEvents = function() {
                this._node.on('click', '[data-action="editelement"]', this._editElement.bind(this));
            };

            RearrangeArea.prototype._getPosition = function(elementid) {
                var element = $('#element-' + elementid),
                    pdf = $('#pdf');
                var posx = element.position().left - pdf.position().left;
                var posy = element.position().top - pdf.position().top;
                var refpoint = parseInt(element.attr('data-refpoint'));
                var nodewidth = parseFloat(element.width());

                switch (refpoint) {
                    case this.CUSTOMCERT_REF_POINT_TOPCENTER:
                        posx += nodewidth / 2;
                        break;
                    case this.CUSTOMCERT_REF_POINT_TOPRIGHT:
                        posx += nodewidth;
                        break;
                }

                return {
                    posx: Math.round(parseFloat(posx / this.PIXELSINMM)),
                    posy: Math.round(parseFloat(posy / this.PIXELSINMM)),
                    id: elementid
                };
            };

            RearrangeArea.prototype._setPosition = function(elementid, refpoint, posx, posy) {
                var element = $('#element-' + elementid);

                posx = $('#pdf').position().left + posx * this.PIXELSINMM;
                posy = $('#pdf').position().top + posy * this.PIXELSINMM;
                var nodewidth = parseFloat(element.width());

                switch (refpoint) {
                    case this.CUSTOMCERT_REF_POINT_TOPCENTER:
                        posx -= nodewidth / 2;
                        break;
                    case this.CUSTOMCERT_REF_POINT_TOPRIGHT:
                        posx = posx - nodewidth + 2;
                        break;
                }

                element.css({top: posy + 'px', left: posx + 'px'});
            };

            return {
                init: function(selector) {
                    new RearrangeArea(selector);
                }
            };
        }
    );
