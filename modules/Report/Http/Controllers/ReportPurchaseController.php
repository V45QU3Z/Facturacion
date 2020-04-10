<?php

namespace Modules\Report\Http\Controllers;

use App\Models\Tenant\Catalogs\DocumentType;
use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade as PDF;
use Modules\Report\Exports\PurchaseExport;
use Illuminate\Http\Request;
use Modules\Report\Traits\ReportTrait;
use App\Models\Tenant\Establishment;
use App\Models\Tenant\Purchase;
use App\Models\Tenant\Company;
use Carbon\Carbon;
use App\Http\Resources\Tenant\PurchaseCollection;

class ReportPurchaseController extends Controller
{
    use ReportTrait;
   
     
    public function filter() {

        $document_types = DocumentType::whereIn('id', ['01', '03'])->get();

        $establishments = Establishment::all()->transform(function($row) {
            return [
                'id' => $row->id,
                'name' => $row->description
            ];
        });
        
        return compact('document_types','establishments');
    }
      

    public function index() {
       
        return view('report::purchases.index');
    }
   
    public function records(Request $request)
    {
        $records = $this->getRecords($request->all(), Purchase::class);

        return new PurchaseCollection($records->paginate(config('tenant.items_per_page')));
    }

 

    public function pdf(Request $request) {

        $company = Company::first();
        $establishment = ($request->establishment_id) ? Establishment::findOrFail($request->establishment_id) : auth()->user()->establishment; 
        $records = $this->getRecords($request->all(), Purchase::class)->get();

        $pdf = PDF::loadView('report::purchases.report_pdf', compact("records", "company", "establishment"));

        $filename = 'Reporte_Compras_'.date('YmdHis');
        
        return $pdf->download($filename.'.pdf');
    }
    
  
    

    public function excel(Request $request) {
    
        $company = Company::first();
        $establishment = ($request->establishment_id) ? Establishment::findOrFail($request->establishment_id) : auth()->user()->establishment; 
        $records = $this->getRecords($request->all(), Purchase::class)->get();

        return (new PurchaseExport)
                ->records($records)
                ->company($company)
                ->establishment($establishment)
                ->download('Reporte_Compras_'.Carbon::now().'.xlsx');

    }
}
