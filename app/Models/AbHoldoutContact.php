<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AbHoldoutContact extends Model
{
    public $timestamps = false;

    protected $table    = 'ab_holdout_contacts';
    protected $fillable = ['ab_test_id', 'contact_number'];
}
