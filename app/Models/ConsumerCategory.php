<?php

namespace App\Models;

use Illuminate\Support\Facades\Session;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsumerCategory extends Model
{
    use HasFactory;
    protected $connection;
    public $timestamps = false;
    protected $table = 'swm_consumer_categories';


    public function __construct($data = null)
    {
        // $this->connection = Session::get('ulb');
        $this->connection = $data;
    }
}
