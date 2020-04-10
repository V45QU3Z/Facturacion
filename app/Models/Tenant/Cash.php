<?php

namespace App\Models\Tenant;
 

class Cash extends ModelTenant
{
    // protected $with = ['cash_documents'];

    protected $table = 'cash';

    protected $fillable = [
        'user_id',
        'date_opening',
        'time_opening',
        'date_closed',
        'time_closed',
        'beginning_balance',
        'final_balance',
        'income', 
        'state',
        'reference_number' 
 
    ];
 

  
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    //obtiene documentos y notas venta
    public function cash_documents()
    {
        return $this->hasMany(CashDocument::class);
    }
 
    public function scopeWhereTypeUser($query)
    {
        $user = auth()->user();         
        return ($user->type == 'seller') ? $query->where('user_id', $user->id) : null; 
    }
 
}