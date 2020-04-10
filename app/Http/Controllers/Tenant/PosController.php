<?php

namespace App\Http\Controllers\Tenant;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Tenant\Item;
use App\Models\Tenant\Person;
use App\Models\Tenant\Catalogs\AffectationIgvType;
use App\Models\Tenant\Establishment;
use App\Models\Tenant\Series;
use App\Models\Tenant\PaymentMethodType;
use App\Models\Tenant\CardBrand;
use App\Models\Tenant\Catalogs\CurrencyType;
use App\Models\Tenant\User;
use Modules\Inventory\Models\Warehouse;
use App\Models\Tenant\Cash;
use App\Models\Tenant\Configuration; 
use Modules\Inventory\Models\InventoryConfiguration;
use Modules\Inventory\Models\ItemWarehouse;
use Exception;

class PosController extends Controller
{
    public function index()
    {
        $cash = Cash::where([['user_id', auth()->user()->id],['state', true]])->first();
        
        if(!$cash) return redirect()->route('tenant.cash.index');

        return view('tenant.pos.index');
    }

    public function search_items(Request $request)
    {
   
         
        $items = Item::where('description','like', "%{$request->input_item}%")
                            ->orWhere('internal_id','like', "%{$request->input_item}%") 
                            ->orWhereHas('category', function($query) use($request) {
                                $query->where('name', 'like', '%' . $request->input_item . '%');
                            })
                            ->orWhereHas('brand', function($query) use($request) {
                                $query->where('name', 'like', '%' . $request->input_item . '%');
                            })
                            ->whereWarehouse()
                            ->get()->transform(function($row) {
                                $full_description = ($row->internal_id)?$row->internal_id.' - '.$row->description:$row->description;
                                return [
                                    'id' => $row->id,
                                    'item_id' => $row->id,
                                    'full_description' => $full_description,
                                    'description' => $row->description,
                                    'currency_type_id' => $row->currency_type_id,
                                    'internal_id' => $row->internal_id,
                                    'currency_type_symbol' => $row->currency_type->symbol,
                                    'sale_unit_price' => $row->sale_unit_price,
                                    'purchase_unit_price' => $row->purchase_unit_price,
                                    'unit_type_id' => $row->unit_type_id,
                                    'sale_affectation_igv_type_id' => $row->sale_affectation_igv_type_id,
                                    'purchase_affectation_igv_type_id' => $row->purchase_affectation_igv_type_id,
                                    'calculate_quantity' => (bool) $row->calculate_quantity,
                                    'is_set' => (bool) $row->is_set,
                                    'has_igv' => (bool) $row->has_igv,
                                    'aux_quantity' => 1,            
                                    'image_url' => ($row->image !== 'imagen-no-disponible.jpg') ? asset('storage'.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'items'.DIRECTORY_SEPARATOR.$row->image) : asset("/logo/{$row->image}"),

                                ];
                            }); 

        return compact('items');

    }

    public function tables()
    {
        $affectation_igv_types = AffectationIgvType::whereActive()->get();
        $establishment = Establishment::where('id', auth()->user()->establishment_id)->first(); 
        $currency_types = CurrencyType::whereActive()->get();

        $customers = $this->table('customers');
        $user = User::findOrFail(auth()->user()->id); 
            
        $items = $this->table('items');

        return compact('items', 'customers','affectation_igv_types','establishment','user','currency_types');

    }

    public function payment_tables(){

        $series = Series::whereIn('document_type_id',['01','03'])
                        ->where([['establishment_id', auth()->user()->establishment_id],['contingency',false]])                
                        ->get();

        $payment_method_types = PaymentMethodType::all();
        $cards_brand = CardBrand::all();


        return compact('series','payment_method_types','cards_brand');

    }

    public function table($table)
    {
        if ($table === 'customers') {
            $customers = Person::whereType('customers')->orderBy('name')->get()->transform(function($row) {
                return [
                    'id' => $row->id,
                    'description' => $row->number.' - '.$row->name,
                    'name' => $row->name,
                    'number' => $row->number,
                    'identity_document_type_id' => $row->identity_document_type_id,
                    'identity_document_type_code' => $row->identity_document_type->code
                ];
            });
            return $customers;
        }

        if ($table === 'items') {
        
            $items = Item::whereWarehouse()->orderBy('description')->take(20)
                            ->get()->transform(function($row) {
                                $full_description = ($row->internal_id)?$row->internal_id.' - '.$row->description:$row->description;
                                return [
                                    'id' => $row->id,
                                    'item_id' => $row->id,
                                    'full_description' => $full_description,
                                    'description' => $row->description,
                                    'currency_type_id' => $row->currency_type_id,
                                    'internal_id' => $row->internal_id,
                                    'currency_type_symbol' => $row->currency_type->symbol,
                                    'sale_unit_price' => $row->sale_unit_price,
                                    'purchase_unit_price' => $row->purchase_unit_price,
                                    'unit_type_id' => $row->unit_type_id,
                                    'sale_affectation_igv_type_id' => $row->sale_affectation_igv_type_id,
                                    'purchase_affectation_igv_type_id' => $row->purchase_affectation_igv_type_id,
                                    'calculate_quantity' => (bool) $row->calculate_quantity,
                                    'has_igv' => (bool) $row->has_igv,
                                    'is_set' => (bool) $row->is_set,
                                    'aux_quantity' => 1,
                                    'image_url' => ($row->image !== 'imagen-no-disponible.jpg') ? asset('storage'.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'items'.DIRECTORY_SEPARATOR.$row->image) : asset("/logo/{$row->image}"),
                                ];
                            }); 
            return $items;
        }


        if ($table === 'card_brands') {
        
            $card_brands = CardBrand::all(); 
            return $card_brands;
            
        }

        return [];
    }

    public function payment()
    {
        return view('tenant.pos.payment');
    }

    public function status_configuration(){

        $configuration = Configuration::first();

        return $configuration;
    }

    public function validate_stock($item_id, $quantity){

        $inventory_configuration = InventoryConfiguration::firstOrFail();
        $warehouse = Warehouse::where('establishment_id', auth()->user()->establishment_id)->first();
        $item_warehouse = ItemWarehouse::where([['item_id',$item_id], ['warehouse_id',$warehouse->id]])->first();
        $item = Item::findOrFail($item_id);
        
        if($item->is_set){

            $sets = $item->sets;

            foreach ($sets as $set) {
                
                $individual_item = $set->individual_item;
                $item_warehouse = ItemWarehouse::where([['item_id',$individual_item->id], ['warehouse_id',$warehouse->id]])->first();

                if(!$item_warehouse)
                    return [
                        'success' => false,
                        'message' => "El producto seleccionado no está disponible en su almacén!"
                    ];

                $stock = $item_warehouse->stock - $quantity;
                

                if($item_warehouse->item->unit_type_id !== 'ZZ'){
                    if (($inventory_configuration->stock_control) && ($stock < 0)){             
                        return [
                            'success' => false,
                            'message' => "El producto {$item_warehouse->item->description} registrado en el conjunto {$item->description} no tiene suficiente stock!"
                        ];
                    }
                }
                // dd($individual_item);
            }



        }else{


            if(!$item_warehouse)
                return [
                    'success' => false,
                    'message' => "El producto seleccionado no está disponible en su almacén!"
                ];

            $stock = $item_warehouse->stock - $quantity;
            

            if($item_warehouse->item->unit_type_id !== 'ZZ'){
                if (($inventory_configuration->stock_control) && ($stock < 0)){             
                    return [
                        'success' => false,
                        'message' => "El producto {$item_warehouse->item->description} no tiene suficiente stock!"
                    ];
                }
            }

        }


        
        return [
            'success' => true,
            'message' => ''
        ];
        

    } 
    
}
