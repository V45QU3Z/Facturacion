<?php

namespace Modules\Report\Http\Controllers;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade as PDF;
use Modules\Report\Exports\QuotationExport;
use Illuminate\Http\Request;
use Modules\Report\Traits\ReportTrait;
use App\Models\Tenant\Establishment;
use App\Models\Tenant\Quotation;
use App\Models\Tenant\Company;
use Carbon\Carbon;
use Modules\Report\Http\Resources\QuotationCollection;

class ReportQuotationController extends Controller
{

    use ReportTrait;   
     
    public function filter() {

        $document_types = [];

        $establishments = Establishment::all()->transform(function($row) {
            return [
                'id' => $row->id,
                'name' => $row->description
            ];
        });
        
        return compact('document_types','establishments');
    }
      

    public function index() {
       
        return view('report::quotations.index');
    }
   
    public function records(Request $request)
    {
        $records = $this->getRecords($request->all(), Quotation::class);

        return new QuotationCollection($records->paginate(config('tenant.items_per_page')));
    }

 

    public function pdf(Request $request) {

        $company = Company::first();
        $establishment = ($request->establishment_id) ? Establishment::findOrFail($request->establishment_id) : auth()->user()->establishment; 
        $records = $this->getRecords($request->all(), Quotation::class)->get();
        
        $pdf = PDF::loadView('report::quotations.report_pdf', compact("records", "company", "establishment"));

        $filename = 'Reporte_Cotizaciones_'.date('YmdHis');
        
        return $pdf->download($filename.'.pdf');
    }
    
  
    

    public function excel(Request $request) {
    
        $company = Company::first();
        $establishment = ($request->establishment_id) ? Establishment::findOrFail($request->establishment_id) : auth()->user()->establishment; 
        
        $records = $this->getRecords($request->all(), Quotation::class)->get();

        return (new QuotationExport)
                ->records($records)
                ->company($company)
                ->establishment($establishment)
                ->download('Reporte_Cotizaciones_'.Carbon::now().'.xlsx');

    }
}
