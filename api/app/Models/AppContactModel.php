<?php
namespace App\Models;

use CodeIgniter\Model;

class AppContactModel extends Model
{
    protected $table         = 'app_contact';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'name',
        'phone',
        'email',
        'project',
        'message',
        'status',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
