<?php
namespace App\Models;

use CodeIgniter\Model;

class UserRoleModel extends Model
{
    protected $table = 'sys_user_roles';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'user_id',
        'role_id',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
