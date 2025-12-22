<?php
namespace App\Models;

use CodeIgniter\Model;

class AppCaseModel extends Model
{
    protected $table         = 'app_case';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'structure_id',
        'year',
        'title',
        's_text',
        'cover',
        'content',
        'slide',
        'ca_type',
        'ca_area',
        'ca_square',
        'ca_phone',
        'ca_adds',
        'ca_map',
        'ca_pop_type',
        'ca_pop_img',
        'is_sale',
        'is_msg',
        'sort',
        'status',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}

