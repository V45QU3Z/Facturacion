<?php

namespace App\CoreFacturalo;

class Template
{
    public function pdf($base_template, $template, $company, $document, $format_pdf)
    {
        if($template === 'credit' || $template === 'debit') {
            $template = 'note';
        }

        $path_template =  $this->validate_template($base_template, $template, $format_pdf);

        return self::render($path_template, $company, $document);
    }

    public function xml($template, $company, $document)
    {
        return self::render('xml.'.$template, $company, $document);
    }

    private function render($view, $company, $document)
    {
        view()->addLocation(__DIR__.'/Templates');

        return view($view, compact('company', 'document'))->render();
    }

    public function pdfFooter($base_template)
    {
        view()->addLocation(__DIR__.'/Templates');

        return view('pdf.'.$base_template.'.partials.footer')->render();
    }

    public function validate_template($base_template, $template, $format_pdf)
    {
        $path_app_template = app_path('CoreFacturalo'.DIRECTORY_SEPARATOR.'Templates');
        $path_template_default = 'pdf'.DIRECTORY_SEPARATOR.'default'.DIRECTORY_SEPARATOR.$template.'_'.$format_pdf;
        $path_template = 'pdf'.DIRECTORY_SEPARATOR.$base_template.DIRECTORY_SEPARATOR.$template.'_'.$format_pdf;

      

        if(file_exists($path_app_template.DIRECTORY_SEPARATOR.$path_template.'.blade.php')) {
            return str_replace(DIRECTORY_SEPARATOR, '.', $path_template);
        }

        return str_replace(DIRECTORY_SEPARATOR, '.', $path_template_default);
    }
}