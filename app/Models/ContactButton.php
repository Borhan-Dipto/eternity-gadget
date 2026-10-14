<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactButton extends Model
{
    protected $fillable = ['label', 'type', 'url', 'sort_order', 'is_active'];
}