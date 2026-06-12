<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Kalnoy\Nestedset\NodeTrait;

class Navigation extends Model
{
    use NodeTrait;

    protected $table = 'navigations';

    protected $fillable = [
        'type',
        'name',
        'description',
        'status',
    ];

    public function getScopeAttributes(): array
    {
        return ['type', 'status'];
    }
}
