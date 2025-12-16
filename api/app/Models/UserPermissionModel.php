<?php
namespace App\Models;

use CodeIgniter\Model;

class UserPermissionModel extends Model
{
    protected $table = 'sys_user_permissions';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'user_id',
        'permission_id',
        'is_granted',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
