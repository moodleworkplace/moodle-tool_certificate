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

namespace tool_certificate\output;

use core_reportbuilder\system_report_factory;
use renderer_base;
use tool_certificate\reportbuilder\local\systemreports\issues;
use tool_certificate\template;

/**
 * Class tool_certificate\output\issues_page
 *
 * @package   tool_certificate
 * @copyright 2022 Moodle Pty Ltd <support@moodle.com>
 * @author    2022 Odei Alba <odei.alba@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class issues_page implements \templatable, \renderable {
    /** @var int */
    protected $templateid;

    /**
     * @var \stdClass[] The rows that were displayed in the table
     */
    public array $rows;

    /**
     * templates_page constructor.
     *
     * @param int $templateid
     */
    public function __construct(int $templateid) {
        $this->templateid = $templateid;
    }

    /**
     * Implementation of exporter from templatable interface
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        $context = template::instance($this->templateid)->get_context();
        $report = system_report_factory::create(issues::class, $context,
            '', '', 0, ['templateid' => $this->templateid]);

        $result = ['content' => $report->output()];
        if (isset($report->rows)) {
            $this->rows = $report->rows;
        }
        else
        {
            $this->rows = [];
        }
        return $result;
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \setasign\Fpdi\PdfParser\PdfParserException
     * @throws \setasign\Fpdi\PdfParser\PdfParserException
     */
    public function output_issues_pdf(template $template, string $type): void {
        global $CFG;
        $files = [];
        $handles = [];
        foreach ($this->rows as $row) {
            $file = $template->get_issue_file($row);
            $files[] = $file;
            $handles[$file->get_id()] = $file->get_content_file_handle();
        }

        $debug = optional_param('debug', false, PARAM_BOOL);

        require_once($CFG->libdir . '/pdflib.php');
        require_once($CFG->dirroot . '/mod/assign/feedback/editpdf/fpdi/autoload.php');

        // end all output buffers if any
        while (ob_get_level())
        {
            ob_get_clean();
        }

        try {
            $pdf = new \setasign\Fpdi\Tcpdf\Fpdi();
            $count = count($files);
            $name = clean_filename($template->get_name());
            $at = date('Y-m-d H-i-s');
            $name = "$name - $count certificate(s) - $at";

            if ($type == 'pdf') {
                $position = 0;
                foreach ($files as $file) {
                    $position++;
                    $filePages = $pdf->setSourceFile($handles[$file->get_id()]);
                    for ($pageNumber = 1; $pageNumber <= $filePages; $pageNumber++) {
                        $sourcePage = $pdf->importPage($pageNumber);
                        $size = $pdf->getTemplateSize($sourcePage);
                        $pdf->AddPage($size['orientation'], array($size['width'], $size['height']));

                        $pdf->useTemplate($sourcePage);

                        if ($debug) {
                            $pdf->SetFont('Helvetica');
                            $pdf->SetTextColor(200, 0, 0);
                            $pdf->SetXY(5, 5);
                            $pdf->Write(2, "PDF $position/$count, Page $pageNumber/$filePages");
                        }
                    }
                }

                $pdf->Output("$name.pdf");
            }
            else if ($type == 'pdfdecollate') {
                $pageCount = 1;
                for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
                    $position = 0;
                    foreach ($files as $file) {
                        $position++;
                        $filePages = $pdf->setSourceFile($handles[$file->get_id()]);
                        if ($pageNumber > $filePages) {
                            continue;
                        }
                        if ($filePages > $pageCount) {
                            $pageCount = $filePages;
                        }

                        $sourcePage = $pdf->importPage($pageNumber);
                        $size = $pdf->getTemplateSize($sourcePage);
                        $pdf->AddPage($size['orientation'], array($size['width'], $size['height']));

                        $pdf->useTemplate($sourcePage);

                        if ($debug) {
                            $pdf->SetFont('Helvetica');
                            $pdf->SetTextColor(200, 0, 0);
                            $pdf->SetXY(5, 5);
                            $pdf->Write(2, "PDF $position/$count, Page $pageNumber/$filePages");
                        }
                    }
                }

                $pdf->Output("$name - ordered.pdf");
            }
            else {
                throw new \InvalidArgumentException("Unknown download type: $type");
            }
        }
        finally {
            foreach ($handles as $handle) {
                fclose($handle);
            }
        }
    }
}
