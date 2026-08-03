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

class MessageHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id', 'message', 'user_id', 'number',
        'send_by', 'payload', 'status', 'type', 'note',
        'meta_message_id', 'delivery_status',
    ];

    // Rank used for upgrade-only delivery status progression
    public const STATUS_RANK = ['sent' => 1, 'delivered' => 2, 'read' => 3, 'failed' => 0];

    public function device(){
        return $this->belongsTo(Device::class);
    }


}
