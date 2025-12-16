<?php
namespace App\Models;

use CodeIgniter\Model;

class RolePermissionModel extends Model
{
    protected $table = 'sys_role_permissions';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'role_id',
        'permission_id',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
