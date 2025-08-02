<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $guarded = [];

    public function setTable($table)
    {
        $this->table = $table;
        return $this;
    }

    public static function forSymbol($symbol)
    {
        $table = 'orders_' . str_replace('/', '_', strtolower($symbol));
        return (new self)->setTable($table);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
