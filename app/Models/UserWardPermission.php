<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserWardPermission extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $table = 'wf_ward_users';

    public function __construct($data = null)
    {
        //$this->connection = Session::get('ulb');
        $this->connection = $data;
    }
}
