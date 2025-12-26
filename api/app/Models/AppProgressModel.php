<?php
namespace App\Models;

use CodeIgniter\Model;

class AppProgressModel extends Model
{
    protected $table         = 'app_progress';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'case_id',
        'title',
        'progress_date',
        'status',
        'images',
        'sort',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}

