<?php
namespace App\Models;

use CodeIgniter\Model;

class AppAboutModel extends Model
{
    protected $table         = 'app_about';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'structure_id',
        'title',
        'sections_json',
        'status',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
