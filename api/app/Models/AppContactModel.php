<?php
namespace App\Models;

use CodeIgniter\Model;

class AppContactModel extends Model
{
    protected $table         = 'app_contact';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'structure_id',
        'name',
        'phone',
        'email',
        'project',
        'message',
        'reply',
        'status',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
