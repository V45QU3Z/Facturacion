<?php

namespace App\Models\Tenant;

use App\Models\Tenant\Catalogs\Department;
use App\Models\Tenant\Catalogs\District;
use App\Models\Tenant\Catalogs\Province;

class PersonAddress extends ModelTenant
{
    protected $table = 'person_address';
    protected $with = ['identity_document_type', 'country', 'department', 'province', 'district'];
    public $timestamps = false;
    protected $fillable = [
        'person_id',
        'department_id',
        'province_id',
        'district_id',
        'address',
        'location_id',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function setLocationIdAttribute($value)
    {
        $this->attributes['department_id'] = (count($value) === 3)?$value[0]:null;
        $this->attributes['province_id'] = (count($value) === 3)?$value[1]:null;
        $this->attributes['district_id'] = (count($value) === 3)?$value[2]:null;
    }

    public function getLocationIdAttribute()
    {
        return [
            $this->department_id,
            $this->province_id,
            $this->district_id,
        ];
    }

    public function getAddressFullAttribute()
    {
        $address = trim($this->address);
        $address = ($address === '-' || $address === '')?'':$address.' ,';
        if ($address === '') {
            return '';
        }
        return "{$address} {$this->department->description} - {$this->province->description} - {$this->district->description}";
    }
}