<?php
namespace App\Models;

use CodeIgniter\Model;

class SysStructureModel extends Model
{
    protected $table = 'sys_structure';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'parent_id',
        'label',
        'module_id',
        'status',
        'sort_order',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * 取得所有層級（樹狀結構）
     * @param bool $onlyActive 只取得啟用的層級
     * @return array
     */
    public function getAllLevels($onlyActive = false)
    {
        $builder = $this->builder();

        if ($onlyActive) {
            $builder->where('status', 1);
        }

        $builder->orderBy('sort_order', 'ASC');
        $builder->orderBy('id', 'ASC');

        $levels = $builder->get()->getResultArray();

        // 轉換為樹狀結構
        return $this->buildTree($levels);
    }

    /**
     * 建立樹狀結構
     * @param array $levels 所有層級資料
     * @param int|null $parentId 父層級 ID
     * @return array
     */
    private function buildTree($levels, $parentId = null)
    {
        $tree = [];

        foreach ($levels as $level) {
            $levelParentId = $level['parent_id'] ? (int) $level['parent_id'] : null;

            if ($levelParentId === $parentId) {
                $children = $this->buildTree($levels, (int) $level['id']);
                if (!empty($children)) {
                    $level['children'] = $children;
                }
                $tree[] = $level;
            }
        }

        return $tree;
    }

    /**
     * 取得第一層級（沒有 parent_id 的層級）
     * @param bool $onlyActive 只取得啟用的層級
     * @return array
     */
    public function getFirstLevels($onlyActive = false)
    {
        $builder = $this->builder();
        $builder->where('parent_id IS NULL');

        if ($onlyActive) {
            $builder->where('status', 1);
        }

        $builder->orderBy('sort_order', 'ASC');
        $builder->orderBy('id', 'ASC');

        return $builder->get()->getResultArray();
    }

    /**
     * 取得指定層級的子層級
     * @param int $parentId 父層級 ID
     * @param bool $onlyActive 只取得啟用的層級
     * @return array
     */
    public function getChildren($parentId, $onlyActive = false)
    {
        $builder = $this->builder();
        $builder->where('parent_id', $parentId);

        if ($onlyActive) {
            $builder->where('status', 1);
        }

        $builder->orderBy('sort_order', 'ASC');
        $builder->orderBy('id', 'ASC');

        return $builder->get()->getResultArray();
    }

    /**
     * 更新排序順序
     * @param array $list 排序順序列表
     * @return bool
     */
    public function updateSortOrder($list)
    {
        if (empty($list) || !is_array($list)) {
            throw new \InvalidArgumentException('updateBatch() has no data.');
        }

        // 確保每個項目都有 id 和 sort_order
        $validList = [];
        foreach ($list as $item) {
            if (isset($item['id']) && isset($item['sort_order'])) {
                $validList[] = [
                    'id' => (int) $item['id'],
                    'sort_order' => (int) $item['sort_order'],
                ];
            }
        }

        if (empty($validList)) {
            throw new \InvalidArgumentException('updateBatch() has no data.');
        }

        $this->updateBatch($validList, 'id');
        return true;
    }
}

