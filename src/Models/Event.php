<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class Event extends Model
{
    protected $table = 'events';
    protected $fillable = ['community_id', 'name', 'banner', 'local', 'date', 'start_time', 'end_time', 'cfp_start_date', 'cfp_end_date'];
}