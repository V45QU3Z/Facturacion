<?php

namespace App\CoreFacturalo;

use App\CoreFacturalo\Helpers\QrCode\QrCodeGenerate;
use App\CoreFacturalo\Helpers\Xml\XmlFormat;
use App\CoreFacturalo\Helpers\Xml\XmlHash;
use App\CoreFacturalo\Helpers\Storage\StorageDocument;
use App\CoreFacturalo\WS\Client\WsClient;
use App\CoreFacturalo\WS\Services\BillSender;
use App\CoreFacturalo\WS\Services\ConsultCdrService;
use App\CoreFacturalo\WS\Services\ExtService;
use App\CoreFacturalo\WS\Services\SummarySender;
use App\CoreFacturalo\WS\Services\SunatEndpoints;
use App\CoreFacturalo\WS\Signed\XmlSigned;
use App\CoreFacturalo\WS\Validator\XmlErrorCodeProvider;
use App\Models\Tenant\Company;
use App\Mail\Tenant\DocumentEmail;
use App\Models\Tenant\Invoice;
use Illuminate\Support\Facades\Mail;
use App\Models\Tenant\Dispatch;
use App\Models\Tenant\Document;
use App\Models\Tenant\Retention;
use App\Models\Tenant\Summary;
use App\Models\Tenant\Voided;
use Exception;
use Mpdf\HTMLParserMode;
use Mpdf\Mpdf;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use App\Models\Tenant\Perception;
use App\Models\Tenant\Configuration;

class Facturalo
{
    use StorageDocument;

    const SENT = '03';
    const ACCEPTED = '05';
    const OBSERVED = '07';
    const REJECTED = '09';
    const CANCELING = '13';
    const VOIDED = '11';

    protected $configuration;
    protected $company;
    protected $isDemo;
    protected $isOse;
    protected $signer;
    protected $wsClient;
    protected $document;
    protected $type;
    protected $actions;
    protected $xmlUnsigned;
    protected $xmlSigned;
    protected $pathCertificate;
    protected $soapUsername;
    protected $soapPassword;
    protected $endpoint;
    protected $response;

    public function __construct()
    {
        $this->configuration = Configuration::first();
        $this->company = Company::active();
        $this->isDemo = ($this->company->soap_type_id === '01')?true:false;
        $this->isOse = ($this->company->soap_send_id === '02')?true:false;
        $this->signer = new XmlSigned();
        $this->wsClient = new WsClient();
        $this->setDataSoapType();
    }

    public function setDocument($document)
    {
        $this->document = $document;
    }

    public function getDocument()
    {
        return $this->document;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function save($inputs)
    {
        $this->actions = array_key_exists('actions', $inputs)?$inputs['actions']:[];
        $this->type = $inputs['type'];

        switch ($this->type) {
            case 'debit':
            case 'credit':
                $document = Document::create($inputs);
                foreach ($inputs['items'] as $row) {
                    $document->items()->create($row);
                }
                $document->note()->create($inputs['note']);
                $this->document = Document::find($document->id);
                break;
            case 'invoice':
                $document = Document::create($inputs);
                foreach ($inputs['payments'] as $row) {
                    $document->payments()->create($row);
                }
                foreach ($inputs['items'] as $row) {
                    $document->items()->create($row);
                }
                $this->updatePrepaymentDocuments($inputs);
                if($inputs['hotel']) $document->hotel()->create($inputs['hotel']);
                $document->invoice()->create($inputs['invoice']);
                $this->document = Document::find($document->id);
                break;
            case 'summary':
                $document = Summary::create($inputs);
                foreach ($inputs['documents'] as $row) {
                    $document->documents()->create($row);
                }
                $this->document = Summary::find($document->id);
                break;
            case 'voided':
                $document = Voided::create($inputs);
                foreach ($inputs['documents'] as $row) {
                    $document->documents()->create($row);
                }
                $this->document = Voided::find($document->id);
                break;
            case 'retention':
                $document = Retention::create($inputs);
                foreach ($inputs['documents'] as $row) {
                    $document->documents()->create($row);
                }
                $this->document = Retention::find($document->id);
                break;
            case 'perception':
                $document = Perception::create($inputs);
                foreach ($inputs['documents'] as $row) {
                    $document->documents()->create($row);
                }
                $this->document = Perception::find($document->id);
                break;
            default:
                $document = Dispatch::create($inputs);
                foreach ($inputs['items'] as $row) {
                    $document->items()->create($row);
                }
                $this->document = Dispatch::find($document->id);
                break;
        }
    }

    public function sendEmail()
    {
        $send_email = ($this->actions['send_email'] === true) ? true : false;

        if($send_email){

            $company = $this->company;
            $document = $this->document;
            $email = ($this->document->customer) ? $this->document->customer->email : $this->document->supplier->email;

            Mail::to($email)->send(new DocumentEmail($company, $document));

        }
    }

    public function createXmlUnsigned()
    {
        $template = new Template();
        $this->xmlUnsigned = XmlFormat::format($template->xml($this->type, $this->company, $this->document));
        $this->uploadFile($this->xmlUnsigned, 'unsigned');
    }

    public function signXmlUnsigned()
    {
        $this->setPathCertificate();
        $this->signer->setCertificateFromFile($this->pathCertificate);
        $this->xmlSigned = $this->signer->signXml($this->xmlUnsigned);
        $this->uploadFile($this->xmlSigned, 'signed');
    }

    public function updateHash()
    {
        $this->document->update([
            'hash' => $this->getHash(),
        ]);
    }

    public function updateQr()
    {
        $this->document->update([
            'qr' => $this->getQr(),
        ]);
    }

    public function updateState($state_type_id)
    {
        $this->document->update([
            'state_type_id' => $state_type_id
        ]);
    }

    public function updateSoap($soap_type_id, $type)
    {
        $this->document->update([
            'soap_type_id' => $soap_type_id
        ]);
        if($type === 'invoice') {
            $invoice = Invoice::where('document_id', $this->document->id)->first();
            $invoice->date_of_due = $this->document->date_of_issue;
            $invoice->save();
        }
    }

    public function updateStateDocuments($state_type_id)
    {
        foreach ($this->document->documents as $doc)
        {
            $doc->document->update([
                'state_type_id' => $state_type_id
            ]);
        }
    }

    private function getHash()
    {
        $helper = new XmlHash();
        return $helper->getHashSign($this->xmlSigned);
    }

    private function getQr()
    {
        $customer = $this->document->customer;
        $text = join('|', [
            $this->company->number,
            $this->document->document_type_id,
            $this->document->series,
            $this->document->number,
            $this->document->total_igv,
            $this->document->total,
            $this->document->date_of_issue->format('Y-m-d'),
            $customer->identity_document_type_id,
            $customer->number,
            $this->document->hash
        ]);

        $qrCode = new QrCodeGenerate();
        $qr = $qrCode->displayPNGBase64($text);
        return $qr;
    }

    public function createPdf($document = null, $type = null, $format = null) {
        ini_set("pcre.backtrack_limit", "5000000");
        $template = new Template();
        $pdf = new Mpdf();

        $format_pdf = $this->actions['format_pdf'];

        $this->document = ($document != null) ? $document : $this->document;
        $format_pdf = ($format != null) ? $format : $format_pdf;
        $this->type = ($type != null) ? $type : $this->type;

        $base_pdf_template = config('tenant.pdf_template');

        $html = $template->pdf($base_pdf_template, $this->type, $this->company, $this->document, $format_pdf);

        if (($format_pdf === 'ticket') OR ($format_pdf === 'ticket_58')) {

            $width = ($format_pdf === 'ticket_58') ? 56 : 78 ;
            if(config('tenant.enabled_template_ticket_80')) $width = 76;
            
            $company_name      = (strlen($this->company->name) / 20) * 10;
            $company_address   = (strlen($this->document->establishment->address) / 30) * 10;
            $company_number    = $this->document->establishment->telephone != '' ? '10' : '0';
            $customer_name     = strlen($this->document->customer->name) > '25' ? '10' : '0';
            $customer_address  = (strlen($this->document->customer->address) / 200) * 10;
            $customer_department_id  = ($this->document->customer->department_id == 16) ? 20:0; 
            $p_order           = $this->document->purchase_order != '' ? '10' : '0';

            $total_prepayment = $this->document->total_prepayment != '' ? '10' : '0';
            $total_discount = $this->document->total_discount != '' ? '10' : '0';
            $was_deducted_prepayment = $this->document->was_deducted_prepayment ? '10' : '0';

            $total_exportation = $this->document->total_exportation != '' ? '10' : '0';
            $total_free        = $this->document->total_free != '' ? '10' : '0';
            $total_unaffected  = $this->document->total_unaffected != '' ? '10' : '0';
            $total_exonerated  = $this->document->total_exonerated != '' ? '10' : '0';
            $total_taxed       = $this->document->total_taxed != '' ? '10' : '0';
            $perception       = $this->document->perception != '' ? '10' : '0';

            $total_plastic_bag_taxes       = $this->document->total_plastic_bag_taxes != '' ? '10' : '0';
            $quantity_rows     = count($this->document->items) + $was_deducted_prepayment;

            $extra_by_item_description = 0;
            $discount_global = 0;
            foreach ($this->document->items as $it) {
                if(strlen($it->item->description)>100){
                    $extra_by_item_description +=24;
                }
                if ($it->discounts) {
                    $discount_global = $discount_global + 1;
                }
            }
            $legends = $this->document->legends != '' ? '10' : '0';

            $pdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => [
                    $width,
                    120 +
                    (($quantity_rows * 8) + $extra_by_item_description) +
                    ($discount_global * 3) +
                    $company_name +
                    $company_address +
                    $company_number +
                    $customer_name +
                    $customer_address +
                    $p_order +
                    $legends +
                    $total_exportation +
                    $total_free +
                    $total_unaffected +
                    $total_exonerated +
                    $perception +
                    $total_taxed+
                    $total_prepayment +
                    $total_discount +
                    $was_deducted_prepayment +
                    $customer_department_id+
                    $total_plastic_bag_taxes],
                'margin_top' => 0,
                'margin_right' => 1,
                'margin_bottom' => 0,
                'margin_left' => 1
            ]);
        } else if($format_pdf === 'a5'){

            $company_name      = (strlen($this->company->name) / 20) * 10;
            $company_address   = (strlen($this->document->establishment->address) / 30) * 10;
            $company_number    = $this->document->establishment->telephone != '' ? '10' : '0';
            $customer_name     = strlen($this->document->customer->name) > '25' ? '10' : '0';
            $customer_address  = (strlen($this->document->customer->address) / 200) * 10;
            $p_order           = $this->document->purchase_order != '' ? '10' : '0';

            $total_exportation = $this->document->total_exportation != '' ? '10' : '0';
            $total_free        = $this->document->total_free != '' ? '10' : '0';
            $total_unaffected  = $this->document->total_unaffected != '' ? '10' : '0';
            $total_exonerated  = $this->document->total_exonerated != '' ? '10' : '0';
            $total_taxed       = $this->document->total_taxed != '' ? '10' : '0';
            $total_plastic_bag_taxes       = $this->document->total_plastic_bag_taxes != '' ? '10' : '0';
            $quantity_rows     = count($this->document->items);

            $extra_by_item_description = 0;
            $discount_global = 0;
            foreach ($this->document->items as $it) {
                if(strlen($it->item->description)>100){
                    $extra_by_item_description +=24;
                }
                if ($it->discounts) {
                    $discount_global = $discount_global + 1;
                }
            }
            $legends = $this->document->legends != '' ? '10' : '0';


            $height = ($quantity_rows * 8) +
                    ($discount_global * 3) +
                    $company_name +
                    $company_address +
                    $company_number +
                    $customer_name +
                    $customer_address +
                    $p_order +
                    $legends +
                    $total_exportation +
                    $total_free +
                    $total_unaffected +
                    $total_exonerated +
                    $total_taxed;
            $diferencia = 148 - (float)$height;

            $pdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => [
                    210,
                    $diferencia + $height 
                    ],
                'margin_top' => 2,
                'margin_right' => 5,
                'margin_bottom' => 0,
                'margin_left' => 5
            ]);


       } else {
            
            $pdf_font_regular = config('tenant.pdf_name_regular');
            $pdf_font_bold = config('tenant.pdf_name_bold');

            if ($pdf_font_regular != false) {
                $defaultConfig = (new ConfigVariables())->getDefaults();
                $fontDirs = $defaultConfig['fontDir'];

                $defaultFontConfig = (new FontVariables())->getDefaults();
                $fontData = $defaultFontConfig['fontdata'];

                $pdf = new Mpdf([
                    'fontDir' => array_merge($fontDirs, [
                        app_path('CoreFacturalo'.DIRECTORY_SEPARATOR.'Templates'.
                                                 DIRECTORY_SEPARATOR.'pdf'.
                                                 DIRECTORY_SEPARATOR.$base_pdf_template.
                                                 DIRECTORY_SEPARATOR.'font')
                    ]),
                    'fontdata' => $fontData + [
                        'custom_bold' => [
                            'R' => $pdf_font_bold.'.ttf',
                        ],
                        'custom_regular' => [
                            'R' => $pdf_font_regular.'.ttf',
                        ],
                    ]
                ]);
            }
        }

        $path_css = app_path('CoreFacturalo'.DIRECTORY_SEPARATOR.'Templates'.
                                             DIRECTORY_SEPARATOR.'pdf'.
                                             DIRECTORY_SEPARATOR.$base_pdf_template.
                                             DIRECTORY_SEPARATOR.'style.css');

        $stylesheet = file_get_contents($path_css);

        $pdf->WriteHTML($stylesheet, HTMLParserMode::HEADER_CSS);
        $pdf->WriteHTML($html, HTMLParserMode::HTML_BODY);

        if (($format_pdf != 'ticket') AND ($format_pdf != 'ticket_58')) {
            if(config('tenant.pdf_template_footer')) {
                $html_footer = $template->pdfFooter($base_pdf_template);
                $pdf->SetHTMLFooter($html_footer);
            }
//            $html_footer = $template->pdfFooter();
//            $pdf->SetHTMLFooter($html_footer);
        }
        $this->uploadFile($pdf->output('', 'S'), 'pdf');
    }

    public function loadXmlSigned()
    {
        $this->xmlSigned = $this->getStorage($this->document->filename, 'signed');
//        dd($this->xmlSigned);
    }

    private function senderXmlSigned()
    {
        $this->setDataSoapType();
        $sender = in_array($this->type, ['summary', 'voided'])?new SummarySender():new BillSender();
        $sender->setClient($this->wsClient);
        $sender->setCodeProvider(new XmlErrorCodeProvider());

        return $sender->send($this->document->filename, $this->xmlSigned);
    }

    public function senderXmlSignedBill()
    {
        if(!$this->actions['send_xml_signed']) {
            $this->response = [
                'sent' => false,
            ];
            return;
        }
        $this->onlySenderXmlSignedBill();

    }

    public function onlySenderXmlSignedBill()
    {
        $res = $this->senderXmlSigned();
        if($res->isSuccess()) {
            $cdrResponse = $res->getCdrResponse();
            $this->uploadFile($res->getCdrZip(), 'cdr');

            $code = $cdrResponse->getCode();
            $description = $cdrResponse->getDescription();
            $this->validationCodeResponse($code, $description);

            $this->response = [
                'sent' => true,
                'code' => $cdrResponse->getCode(),
                'description' => $cdrResponse->getDescription(),
                'notes' => $cdrResponse->getNotes()
            ];
        } else {
            $code = $res->getError()->getCode();
            $message = $res->getError()->getMessage();
            $this->validationCodeResponse($code, $message);
            $this->response = [
                'sent' => true,
                'code' => $code,
                'description' => $message
            ];
        }
    }

    public function validationCodeResponse($code, $message)
    {
        //Errors
        if($code === 'ERROR_CDR') {
            return;
        }
        if($code === 'HTTP') {
//            $message = 'La SUNAT no responde a su solicitud, vuelva a intentarlo.';
            throw new Exception("Code: {$code}; Description: {$message}");
        }
        if((int)$code === 0) {
            $this->updateState(self::ACCEPTED);
            return;
        }
        if((int)$code < 2000) {
            //Excepciones
            throw new Exception("Code: {$code}; Description: {$message}");
        } elseif ((int)$code < 4000) {
            //Rechazo
            $this->updateState(self::REJECTED);

        } else {
            $this->updateState(self::OBSERVED);
            //Observaciones
        }
        return;
    }

    public function senderXmlSignedSummary()
    {
        $res = $this->senderXmlSigned();
        if($res->isSuccess()) {
            $ticket = $res->getTicket();
            $this->updateTicket($ticket);
            $this->updateState(self::SENT);
            if($this->type === 'summary') {
                if($this->document->summary_status_type_id === '1') {
                    $this->updateStateDocuments(self::SENT);
                } else {
                    $this->updateStateDocuments(self::CANCELING);
                }
            } else {
                $this->updateStateDocuments(self::CANCELING);
            }
            $this->response = [
                'sent' => true
            ];
        } else {
            throw new Exception("Code: {$res->getError()->getCode()}; Description: {$res->getError()->getMessage()}");
        }
    }

    private function updateTicket($ticket)
    {
        $this->document->update([
            'ticket' => $ticket
        ]);
    }

    public function statusSummary($ticket)
    {
        $extService = new ExtService();
        $extService->setClient($this->wsClient);
        $extService->setCodeProvider(new XmlErrorCodeProvider());
        $res = $extService->getStatus($ticket);
        if(!$res->isSuccess()) {
            throw new Exception("Code: {$res->getError()->getCode()}; Description: {$res->getError()->getMessage()}");
        } else {
            $cdrResponse = $res->getCdrResponse();
            $this->uploadFile($res->getCdrZip(), 'cdr');
            $this->updateState(self::ACCEPTED);
            if($this->type === 'summary') {
                if($this->document->summary_status_type_id === '1') {
                    $this->updateStateDocuments(self::ACCEPTED);
                } else {
                    $this->updateStateDocuments(self::VOIDED);
                }
            } else {
                $this->updateStateDocuments(self::VOIDED);
            }
            $this->response = [
                'code' => $cdrResponse->getCode(),
                'description' => $cdrResponse->getDescription(),
                'notes' => $cdrResponse->getNotes()
            ];
        }
    }

    public function consultCdr()
    {
        $consultCdrService = new ConsultCdrService();
        $consultCdrService->setClient($this->wsClient);
        $consultCdrService->setCodeProvider(new XmlErrorCodeProvider());
        $res = $consultCdrService->getStatusCdr($this->company->number, $this->document->document_type_id,
                                                $this->document->series, $this->document->number);

        if(!$res->isSuccess()) {
            throw new Exception("Code: {$res->getError()->getCode()}; Description: {$res->getError()->getMessage()}");
        } else {
            $cdrResponse = $res->getCdrResponse();
            $this->uploadFile($res->getCdrZip(), 'cdr');
            $this->updateState(self::ACCEPTED);
            $this->response = [
                'sent' => true,
                'code' => $cdrResponse->getCode(),
                'description' => $cdrResponse->getDescription(),
                'notes' => $cdrResponse->getNotes()
            ];
        }
    }

    public function uploadFile($file_content, $file_type)
    {
        $this->uploadStorage($this->document->filename, $file_content, $file_type);
    }

    private function setDataSoapType()
    {
        $this->setSoapCredentials();
        $this->wsClient->setCredentials($this->soapUsername, $this->soapPassword);
        $this->wsClient->setService($this->endpoint);
    }

    private function setPathCertificate()
    {
        if($this->isOse) {
            $this->pathCertificate = storage_path('app'.DIRECTORY_SEPARATOR.
                'certificates'.DIRECTORY_SEPARATOR.$this->company->certificate);
        } else {
            if($this->isDemo) {
                $this->pathCertificate = app_path('CoreFacturalo'.DIRECTORY_SEPARATOR.
                    'WS'.DIRECTORY_SEPARATOR.
                    'Signed'.DIRECTORY_SEPARATOR.
                    'Resources'.DIRECTORY_SEPARATOR.
                    'certificate.pem');
            } else {
                $this->pathCertificate = storage_path('app'.DIRECTORY_SEPARATOR.
                    'certificates'.DIRECTORY_SEPARATOR.$this->company->certificate);
            }
        }

//        if($this->isDemo) {
//            $this->pathCertificate = app_path('CoreFacturalo'.DIRECTORY_SEPARATOR.
//                'WS'.DIRECTORY_SEPARATOR.
//                'Signed'.DIRECTORY_SEPARATOR.
//                'Resources'.DIRECTORY_SEPARATOR.
//                'certificate.pem');
//        } else {
//            $this->pathCertificate = storage_path('app'.DIRECTORY_SEPARATOR.
//                'certificates'.DIRECTORY_SEPARATOR.$this->company->certificate);
//        }
    }

    private function setSoapCredentials()
    {
        if($this->isDemo) {
            $this->soapUsername = $this->company->number.'MODDATOS';
            $this->soapPassword = 'moddatos';
        } else {
            $this->soapUsername = $this->company->soap_username;
            $this->soapPassword = $this->company->soap_password;
        }

//        $this->soapUsername = ($this->isDemo)?$this->company->number.'MODDATOS':$this->company->soap_username;
//        $this->soapPassword = ($this->isDemo)?'moddatos':$this->company->soap_password;

        if($this->isOse) {
            $this->endpoint = $this->company->soap_url;
//            dd($this->soapPassword);
        } else {
            switch ($this->type) {
                case 'perception':
                case 'retention':
                    $this->endpoint = ($this->isDemo)?SunatEndpoints::RETENCION_BETA:SunatEndpoints::RETENCION_PRODUCCION;
                    break;
                case 'dispatch':
                    $this->endpoint = ($this->isDemo)?SunatEndpoints::GUIA_BETA:SunatEndpoints::GUIA_PRODUCCION;
                    break;
                default:
                    // $this->endpoint = ($this->isDemo)?SunatEndpoints::FE_BETA:SunatEndpoints::FE_PRODUCCION;
                    $this->endpoint = ($this->isDemo)?SunatEndpoints::FE_BETA : ($this->configuration->sunat_alternate_server ? SunatEndpoints::FE_PRODUCCION_ALTERNATE : SunatEndpoints::FE_PRODUCCION);
                    break;
            }
        }

    }

    private function updatePrepaymentDocuments($inputs){
        // dd($inputs);

        if(isset($inputs['prepayments'])) {

            foreach ($inputs['prepayments'] as $row) {

                $fullnumber = explode('-', $row['number']);
                $series = $fullnumber[0];
                $number = $fullnumber[1];

                $doc = Document::where([['series',$series],['number',$number]])->first();
                if($doc){
                    $doc->was_deducted_prepayment = true;
                    $doc->save();
                }
            }
        }
    }
}
