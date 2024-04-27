<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDetails extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $connection= 'pgsql_master';
    protected $table = 'user';

}
