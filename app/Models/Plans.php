<?php
/*
Copyright © Magd Almuntaser, OneXGen Technology. All rights reserved.
Project: MPWA Whatsapp Gateway | Multi Device
Licensed under the CC BY-NC-ND 4.0 License.
For details, visit https://creativecommons.org/licenses/by-nc-nd/4.0/.
*/

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plans extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'plans';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'price',
        'symbol',
        'is_recommended',
        'is_trial',
        'status',
        'days',
        'trial_days',
        'data',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'double',
        'is_recommended' => 'integer',
        'is_trial' => 'integer',
        'status' => 'integer',
        'days' => 'integer',
        'trial_days' => 'integer',
        // Plan feature flags / limits are stored as JSON and consumed as an
        // associative array in the pricing and admin views.
        'data' => 'array',
    ];

    /**
     * Orders placed against this plan.
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'plan_id');
    }
}
