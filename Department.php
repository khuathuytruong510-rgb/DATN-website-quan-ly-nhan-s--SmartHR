<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $fillable = [
        'name',
        'manager',
        'description',
        'employee_count'
    ];

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }
    
}