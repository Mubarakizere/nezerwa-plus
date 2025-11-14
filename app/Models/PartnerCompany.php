<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PartnerCompany extends Model
{
    protected $fillable = [
        'name', 'contact_person', 'phone', 'email', 'address', 'notes',
    ];

    public function itemLoans(): HasMany
    {
        return $this->hasMany(ItemLoan::class, 'partner_id');
    }
}
