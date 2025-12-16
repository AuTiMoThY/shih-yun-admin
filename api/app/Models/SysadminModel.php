<?php
namespace App\Models;

use CodeIgniter\Model;

class SysAdminModel extends Model
{
    protected $table         = 'sys_admin';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'permission_name',
        'status',
        'username',
        'password_hash',
        'name',
        'phone',
        'address',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}