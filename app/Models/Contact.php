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

class Contact extends Model
{
    use HasFactory;

    public const STATUSES = ['Lead', 'Contacted', 'Customer', 'Churned'];

    protected $fillable = [
        'user_id', 'name', 'number',
        'company', 'email', 'address', 'linkedin_url', 'facebook_url',
        'website', 'source', 'status', 'remarks',
    ];

    public function tags(){
        return $this->belongsToMany(Tag::class, 'contact_tag');
    }
}
