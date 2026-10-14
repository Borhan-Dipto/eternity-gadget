<?php

namespace App\Models;

use App\Models\Concerns\NormalizesMediaPath;
use Illuminate\Database\Eloquent\Model;

class Slider extends Model
{
    use NormalizesMediaPath;

    protected $fillable = ['title', 'subtitle', 'description', 'button_text', 'button_url', 'image', 'sort_order', 'is_active'];
}