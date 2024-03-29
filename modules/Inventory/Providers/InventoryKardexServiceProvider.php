<?php

namespace Modules\Inventory\Providers;
 
use App\Models\Tenant\DocumentItem;
use App\Models\Tenant\Item;
use App\Models\Tenant\PurchaseItem;
use App\Models\Tenant\SaleNoteItem;
use Illuminate\Support\ServiceProvider;
use Modules\Inventory\Traits\InventoryTrait;

class InventoryKardexServiceProvider extends ServiceProvider
{
    use InventoryTrait;
    
    public function register() {
        // 
    }
    
    public function boot() {
        $this->purchase();
        $this->sale();
        $this->sale_note();
    }
    
    private function purchase() {
        PurchaseItem::created(function ($purchase_item) {

            $presentationQuantity = (!empty($purchase_item->item->presentation)) ? $purchase_item->item->presentation->quantity_unit : 1;

            $warehouse = $this->findWarehouse($this->findWarehouseById($purchase_item->warehouse_id)->establishment_id);
            // $warehouse = $this->findWarehouse();
            //$this->createInventory($purchase_item->item_id, $purchase_item->quantity, $warehouse->id);
            $this->createInventoryKardex($purchase_item->purchase, $purchase_item->item_id, /*$purchase_item->quantity*/ ($purchase_item->quantity * $presentationQuantity), $warehouse->id);
            $this->updateStock($purchase_item->item_id, ($purchase_item->quantity * $presentationQuantity), $warehouse->id);
        });
    }
    
    private function sale() {
        DocumentItem::created(function($document_item) {

            if(!$document_item->item->is_set){

                $presentationQuantity = (!empty($document_item->item->presentation)) ? $document_item->item->presentation->quantity_unit : 1;
            
                $document = $document_item->document;
                $factor = ($document->document_type_id === '07') ? 1 : -1;
                $warehouse = $this->findWarehouse();
                //$this->createInventory($document_item->item_id, $factor * $document_item->quantity, $warehouse->id);
                $this->createInventoryKardex($document_item->document, $document_item->item_id, ($factor * ($document_item->quantity * $presentationQuantity)), $warehouse->id);
                if(!$document_item->document->sale_note_id) $this->updateStock($document_item->item_id, ($factor * ($document_item->quantity * $presentationQuantity)), $warehouse->id);
            
            }
            else{

                $item = Item::findOrFail($document_item->item_id);
                
                foreach ($item->sets as $it) {

                    $ind_item  = $it->individual_item;
                    $presentationQuantity = 1;            
                    $document = $document_item->document;
                    $factor = ($document->document_type_id === '07') ? 1 : -1;
                    $warehouse = $this->findWarehouse();
                    $this->createInventoryKardex($document_item->document, $ind_item->id, ($factor * ($document_item->quantity * $presentationQuantity)), $warehouse->id);
                    if(!$document_item->document->sale_note_id) $this->updateStock($ind_item->id, ($factor * ($document_item->quantity * $presentationQuantity)), $warehouse->id);
                
                }

            }

        });
    }
    
    private function sale_note() {
        SaleNoteItem::created(function ($sale_note_item) {

            if(!$sale_note_item->item->is_set){

                $presentationQuantity = (!empty($sale_note_item->item->presentation)) ? $sale_note_item->item->presentation->quantity_unit : 1;
                
                $warehouse = $this->findWarehouse();
                //$this->createInventory($sale_note_item->item_id, -1 * $sale_note_item->quantity, $warehouse->id);
                $this->createInventoryKardex($sale_note_item->sale_note, $sale_note_item->item_id, (-1 * ($sale_note_item->quantity * $presentationQuantity)), $warehouse->id);
                $this->updateStock($sale_note_item->item_id, (-1 * ($sale_note_item->quantity * $presentationQuantity)), $warehouse->id);

            }else{

                $item = Item::findOrFail($sale_note_item->item_id);
                
                foreach ($item->sets as $it) {

                    $ind_item  = $it->individual_item;
                    $presentationQuantity = 1;                                
                    $warehouse = $this->findWarehouse();
                    $this->createInventoryKardex($sale_note_item->sale_note, $ind_item->id , (-1 * ($sale_note_item->quantity * $presentationQuantity)), $warehouse->id);
                    $this->updateStock($ind_item->id , (-1 * ($sale_note_item->quantity * $presentationQuantity)), $warehouse->id);

                }

            }

        });
    }
    
    private function createInventory($item_id, $quantity, $warehouse_id) {
        if(!$this->checkInventory($item_id, $warehouse_id)) {
            $item = $this->findItem($item_id);
            $this->createInitialInventory($item_id, $item->stock + (-1 * $quantity), $warehouse_id);
        }
    }
}
