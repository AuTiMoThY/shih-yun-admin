<?php
namespace App\Models;

use CodeIgniter\Model;

class PermissionModel extends Model
{
    protected $table = 'sys_permissions';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'name',
        'label',
        'description',
        'module_id',
        'category',
        'action',
        'status',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'name' => 'required|min_length[1]|max_length[255]|is_unique[sys_permissions.name,id,{id}]',
        'label' => 'required|min_length[1]|max_length[255]',
        'status' => 'permit_empty|in_list[0,1]',
    ];

    protected $validationMessages = [
        'name' => [
            'required' => '權限名稱不能為空',
            'is_unique' => '權限名稱已存在',
        ],
        'label' => [
            'required' => '權限顯示名稱不能為空',
        ],
    ];
}
