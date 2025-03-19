<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class Cfp extends Model
{
    protected $table = 'cfps';
    protected $fillable = ['event_id', 'owner_id', 'title', 'description'];
}