<?php

namespace tool_certificate;

class download_issues_form {
    public int $templateid;

    public function __construct(int $templateid) {
        $this->templateid = $templateid;
    }

    public function render(): string {
        return <<<HTML
<form method="post" target="_blank" class="dataformatselector m-1">
    <div class="form-inline text-xs-right">
        <input type="hidden" name="templateid" value="$this->templateid">
        <label for="downloadissues_select" class="mr-1">Download issued PDFs as</label>
        <select name="downloadissues" id="downloadissues_select" class="form-control custom-select mr-1">
                <option value="pdf">Merged PDF</option>
                <option value="pdfdecollate">Merged PDF (De-collated)</option>
        </select>
        <button type="submit" class="btn btn-secondary">Download PDFs</button>
    </div>
</form>
HTML;
    }

    public function get_data() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return false;
        }

        $downloadissues = required_param('downloadissues', PARAM_ALPHA);
        return $downloadissues;
    }

    protected function definition(): void {
        $this->_form->setAttributes(['class' => 'form-inline']);

        $templateid = $this->_customdata['templateid'];
        $this->_form->addElement('hidden', 'templateid', $templateid);
        $this->_form->setType('id', PARAM_INT);

        $options =
            [
                'pdf' => 'Merged PDF',
                'pdfdecollate' => 'Merged PDF (De-collated)',
            ];
        $this->_form->addElement('select', 'downloadissues', 'Download issued PDFs as', $options);
        $this->_form->setType('downloadissues', PARAM_TEXT);

        $submit = $this->_form->addElement('submit', 'submit', 'Download PDFs');
        $submit->setAttributes(['class' => 'btn btn-secondary']);
    }
}