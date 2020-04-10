<?php

namespace Modules\Expense\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'external_id' => $this->external_id,
            'number' => $this->number,
            'date_of_issue' => $this->date_of_issue->format('Y-m-d'), 
            'payments' => $this->payments->transform(function($row, $key) {       
                return [
                    'id' => $row->id, 
                    'expense_method_type_description' => $row->expense_method_type->description,
                    'reference' => $row->reference,
                    'payment' => $row->payment, 
                ];
            })
        ];
    }
}
