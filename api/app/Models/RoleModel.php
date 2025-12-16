<?php
namespace App\Models;

use CodeIgniter\Model;

class RoleModel extends Model
{
    protected $table = 'sys_roles';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'name',
        'label',
        'description',
        'status',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'name' => 'required|min_length[1]|max_length[100]|is_unique[sys_roles.name,id,{id}]',
        'label' => 'required|min_length[1]|max_length[255]',
        'status' => 'permit_empty|in_list[0,1]',
    ];

    protected $validationMessages = [
        'name' => [
            'required' => '角色名稱不能為空',
            'is_unique' => '角色名稱已存在',
        ],
        'label' => [
            'required' => '角色顯示名稱不能為空',
        ],
    ];
}
