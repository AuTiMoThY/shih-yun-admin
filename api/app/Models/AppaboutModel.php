<?php
namespace App\Models;

use CodeIgniter\Model;

class AppaboutModel extends Model
{
    protected $table         = 'appabout';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'title',
        'sections_json',
        'status',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
