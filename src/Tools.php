<?php

namespace Raiseinfo;

class Tools
{


    /**
     * 上传文件的过滤
     * @param string $filename
     * @return string|null
     */
    function uploadFilter(string $filename, array $blackList = null): ?string
    {
        // 定义黑名单扩展名模式
        $blacklistPatterns = $blackList ?: [
            '/\.php[345]?$/i',  // 匹配 .php, .php3, .php4, .php5
            '/\.phtml$/i',      // 匹配 .phtml
            '/\.pht$/i'         // 匹配 .pht
        ];

        // 检查文件名是否匹配黑名单中的任何模式
        foreach ($blacklistPatterns as $pattern) {
            if (preg_match($pattern, $filename)) {
                return null;
            }
        }

        // 如果没有匹配到黑名单扩展名，返回原始文件名
        return $filename;
    }

    /**
     * 自动安全过滤SQL语句，只允许查询语句
     * @param $sql
     * @return mixed|null
     */
    function sqlFilter(string $sql, array $blackList = []): mixed
    {
        // 定义黑名单关键字模式
        $blacklistPatterns = $blackList ?: [
            '/\binsert\s+into\b/i',         // 匹配 "INSERT INTO"
            '/\bupdate\s+\w+\b/i',          // 匹配 "UPDATE table_name"
            '/\bcreate\s+(table|database)\b/i',  // 匹配 "CREATE TABLE" 或 "CREATE DATABASE"
            '/\balter\s+table\b/i',         // 匹配 "ALTER TABLE"
            '/\bdelete\s+from\b/i',         // 匹配 "DELETE FROM"
            '/\bdrop\s+(table|database)\b/i',// 匹配 "DROP TABLE" 或 "DROP DATABASE"
            '/\bload_file\b/i',             // 匹配 "LOAD_FILE"
            '/\boutfile\b/i',               // 匹配 "OUTFILE"
            '/\bdump\b/i'                   // 匹配 "DUMP"
        ];

        // 检查 SQL 语句是否包含黑名单中的任何关键字
        foreach ($blacklistPatterns as $pattern) {
            if (preg_match($pattern, $sql)) {
                return null;
            }
        }

        // 返回原始 SQL 语句（未被过滤）
        return $sql;
    }

    /**
     * 递归的删除某个目录
     * @param $dirPath
     * @return bool
     */
    function deleteDir($dirPath): bool
    {
        // 检查给定路径是否为有效的目录
        if (!is_dir($dirPath)) {
            throw new \InvalidArgumentException("$dirPath 不是一个有效的目录");
        }

        // 创建迭代器遍历目录
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dirPath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        // 遍历目录中的每一个文件和子目录
        foreach ($iterator as $file) {
            // 获取文件或目录的路径
            $path = $file->getPathname();

            // 根据是文件还是目录来决定使用 unlink 或 rmdir
            if ($file->isDir()) {
                rmdir($path);
            } else {
                unlink($path);
            }
        }

        // 尝试删除顶级目录
        if (!rmdir($dirPath)) {
            throw new \RuntimeException("无法删除目录: $dirPath");
        }

        return true;
    }




    /*********************************************生成树形下拉框的选项数据********************************************/
    /**
     * 生成树形下拉框的选项数据
     * @param array $data 平面结构数据
     * @param int $root 根节点ID
     * @param string $primaryKey 主键键名
     * @param string $foreignKey 外键键名
     * @param string $labelKey 标签键名
     * @param string $valueKey 值的键名
     * @param string $childrenKey 子节点的键名
     * @return array
     */
    public function getTreeSelectOptions(
        array  $data,
        int    $root = 0,
        string $primaryKey = 'id',
        string $foreignKey = 'pid',
        string $labelKey = 'label',
        string $valueKey = 'value',
        string $childrenKey = 'children'
    ): array
    {
        $tree = [];
        if (empty($data) || !is_array($data)) {
            return $tree;
        }

        // Create a lookup table for faster access to nodes by their primary key.
        $lookup = [];
        foreach ($data as $node) {
            $lookup[$node[$primaryKey]] = $node;
        }

        // Iterate over the data to build the tree.
        foreach ($data as $node) {
            if ($node[$foreignKey] == $root) {
                // Recursively find children for the current node.
                $children = $this->getTreeSelectOptions($data, $node[$primaryKey], $primaryKey, $foreignKey, $labelKey, $valueKey, $childrenKey);
                if (!empty($children)) {
                    $tree[] = [
                        'label' => $node[$labelKey],
                        'value' => $node[$valueKey],
                        $childrenKey => $children,
                    ];
                } else {
                    $tree[] = [
                        'label' => $node[$labelKey],
                        'value' => $node[$valueKey],
                    ];
                }
            }
        }

        return $tree;
    }


    /**
     * 从平面结构的树形数据中获得指定ID的所有子项目的ID的数组
     * @param array $data 平面数据
     * @param int $parentId 要搜索的父ID
     * @param string $primaryKey 主键字段名
     * @param string $foreign_key 外键字段名
     * @return array
     */
    public function findChildren(
        array  $data,
        int    $parentId,
        string $primaryKey = 'id',
        string $foreign_key = 'pid'
    ): array
    {
        $result = [];

        foreach ($data as $item) {
            // 如果当前项的外键等于给定的父ID
            if (isset($item[$foreign_key]) && $item[$foreign_key] == $parentId) {
                // 将当前项的主键添加到结果数组中
                $result[] = $item[$primaryKey];
                // 递归查找当前项的子项
                $children = $this->findChildren($data, $item[$primaryKey], $primaryKey, $foreign_key);
                // 将子项的结果合并到结果数组中
                $result = array_merge($result, $children);
            }
        }

        return $result;
    }


    /**
     * 获取指定ID的项目的各级父类名称，从顶级到当前级用"-"连接后返回
     * @param array $data 平面数据
     * @param int $id 要搜索的ID
     * @param string $primaryKey 主键字段名
     * @param string $foreign_key 外键字段名
     * @param string $nameKey 名称字段名
     * @return string
     */
    public function findParentNames(
        array  $data,
        int    $id,
        string $primaryKey = 'id',
        string $foreign_key = 'pid',
        string $nameKey = 'name'
    ): string
    {
        $names = [];
        $currentId = $id;

        // 循环查找直至没有父节点
        while ($currentId > 0) {
            $found = false;
            foreach ($data as $item) {
                if (isset($item[$primaryKey]) && $item[$primaryKey] == $currentId) {
                    // 如果找到了对应的项，则将其名称添加到数组中
                    if (isset($item[$nameKey])) {
                        array_unshift($names, $item[$nameKey]); // 在数组开头添加，以便按顺序排列
                    }
                    // 更新当前ID为父ID，继续循环
                    $currentId = $item[$foreign_key];
                    $found = true;
                    break;
                }
            }

            if (!$found) { // 如果未找到则退出循环
                break;
            }
        }

        return implode('-', $names); // 使用"-"连接所有名称并返回
    }

    /**
     * 获取指定ID的项目的各级父类ID，从顶级到当前级用","连接后返回
     * @param array $data 平面数据
     * @param int $id 要搜索的ID
     * @param string $primaryKey 主键字段名
     * @param string $foreign_key 外键字段名
     * @return string
     */
    public function findParentIds(
        array  $data,
        int    $id,
        string $primaryKey = 'id',
        string $foreign_key = 'pid'
    ): string
    {
        $ids = [];
        $currentId = $id;

        // 循环查找直至没有父节点
        while ($currentId > 0) {
            $found = false;
            foreach ($data as $item) {
                if (isset($item[$primaryKey]) && $item[$primaryKey] == $currentId) {
                    // 如果找到了对应的项，则将其ID添加到数组中
                    $ids[] = $currentId; // 在数组开头添加，以便按顺序排列
                    // 更新当前ID为父ID，继续循环
                    $currentId = $item[$foreign_key];
                    $found = true;
                    break;
                }
            }

            if (!$found) { // 如果未找到则退出循环
                break;
            }
        }

        // 注意：这里我们反转数组，因为我们是从下往上收集ID的
        return implode(',', array_reverse($ids)); // 使用","连接所有ID并返回
    }

    /*********************************************收集树结构所有的叶子节点的ID********************************************/
    /**
     * 收集树结构所有的叶子节点的ID
     * 叶子节点是指没有children的或者children为空的所有子节点
     * @param array $treeChildren 需要查找的树根下的Children
     * @param string $primaryKey 主键键名
     * @param string $childrenKey 子节点键名
     * @return mixed
     */
    public function findLeafNodeIds(
        array  $treeChildren,
        string $primaryKey = 'id',
        string $childrenKey = 'children'
    ): array
    {
        $leafNodeIds = [];
        // 获取树根下的children
        foreach ($treeChildren as $node) {
            // 检查当前节点是否为数组
            if (!is_array($node)) {
                continue; // 如果不是数组，跳过该节点
            }

            // 检查主键是否存在且为整数
            if (!isset($node[$primaryKey]) || !is_int($node[$primaryKey])) {
                continue; // 如果主键不存在或不是整数，跳过该节点
            }

            // 如果没有 'children' 键或 'children' 数组为空，则为叶子节点
            if (!isset($node[$childrenKey]) || empty($node[$childrenKey])) {
                // 将叶子节点的ID添加到结果数组中
                $leafNodeIds[] = $node[$primaryKey];
            } else {
                // 确保子节点是一个数组
                if (is_array($node[$childrenKey])) {
                    // 递归查找子节点中的叶子节点
                    $leafNodeIds = array_merge($leafNodeIds, $this->findLeafNodeIds($node[$childrenKey], $primaryKey, $childrenKey));
                }
            }
        }

        return $leafNodeIds;
    }

    /*********************************************从树中找到指定ID的节点的完整信息********************************************/
    /**
     * 从树中找到指定ID的节点的完整信息
     *
     * @param array $tree 需要查找的树
     * @param int $targetId 查找的节点ID
     * @param string $primaryKey 主键名，默认为 'id'
     * @param string $childrenKey 子节点的键名，默认为 'children'
     * @return array|null 返回找到的节点及其子节点，如果没有找到则返回 null
     */
    public function findNodeFormTree(
        array  $tree,
        int    $targetId,
        string $primaryKey = 'id',
        string $childrenKey = 'children'
    ): ?array
    {
        // 如果树为空，直接返回 null
        if (empty($tree)) {
            return null;
        }

        foreach ($tree as $node) {
            // 如果当前节点的主键匹配目标ID，返回该节点
            if ($node[$primaryKey] == $targetId) {
                return $node;
            }

            // 如果当前节点有子节点，递归查找子节点
            if (isset($node[$childrenKey]) && !empty($node[$childrenKey])) {
                $result = $this->findNodeFormTree($node[$childrenKey], $targetId, $primaryKey, $childrenKey);
                if ($result !== null) {
                    return $result;
                }
            }
        }

        // 如果遍历完所有节点仍未找到目标节点，返回 null
        return null;
    }

    /*********************************************将平面结构的数据转换为树结构***********************************************/
    /**
     * 将平面结构的数据转换为树结构
     *
     * @param array $nodes 平面结构数据
     * @param int $root 根节点ID，默认为 0
     * @param bool $alwaysChildren 每个元素是否都构建 children 字段
     * @param string $primaryKey 主键键名，默认为 'id'
     * @param string $foreignKey 外键键名，默认为 'pid'
     * @param string $childrenKey 子节点键名，默认为 'children'
     * @return array 返回树形结构的数据
     */
    public function buildTree(
        array  $nodes,
        bool   $alwaysChildren = true,
        int    $root = 0,
        string $primaryKey = 'id',
        string $foreignKey = 'pid',
        string $childrenKey = 'children'
    ): array
    {
        // 如果节点数组为空，直接返回空数组
        if (empty($nodes)) {
            return [];
        }

        // 创建一个哈希表来存储所有节点，以便快速查找
        $lookup = [];
        foreach ($nodes as $node) {
            // 检查节点是否包含必要的键
            if (!isset($node[$primaryKey]) || !isset($node[$foreignKey])) {
                throw new \InvalidArgumentException("Node is missing required keys: {$primaryKey} or {$foreignKey}");
            }
            $lookup[$node[$primaryKey]] = $node;
        }

        // 创建一个数组来存储根节点
        $tree = [];

        // 遍历所有节点，构建树结构
        foreach ($nodes as $node) {
            // 如果当前节点是根节点，直接将其加入树中
            if ($node[$foreignKey] === $root) {
                $tree[] = $node;
            } else {
                // 否则，找到其父节点并将其作为子节点加入
                $parentId = $node[$foreignKey];
                if (isset($lookup[$parentId])) {
                    // 如果父节点存在，确保父节点有 children 键
                    if (!isset($lookup[$parentId][$childrenKey])) {
                        $lookup[$parentId][$childrenKey] = [];
                    }
                    // 将当前节点加入父节点的 children 中
                    $lookup[$parentId][$childrenKey][] = $node;
                } else {
                    // 如果父节点不存在，可以选择抛出异常或记录警告（根据需求选择）
                    // throw new InvalidArgumentException("Parent node with ID {$parentId} not found");
                }
            }
        }

        // 递归为每个根节点添加子节点
        return $this->addChildrenRecursively($tree, $lookup, $alwaysChildren, $primaryKey, $childrenKey);
    }

    /**
     * 递归为每个节点添加子节点
     *
     * @param array $nodes 当前层级的节点数组
     * @param array $lookup 所有节点的哈希表
     * @param bool $alwaysChildren 每个节点是否都有 children
     * @param string $primaryKey 主键键名
     * @param string $childrenKey 子节点键名
     * @return array 返回带有子节点的树结构
     */
    private function addChildrenRecursively(
        array  $nodes,
        array  $lookup,
        bool   $alwaysChildren,
        string $primaryKey,
        string $childrenKey
    ): array
    {
        foreach ($nodes as &$node) {
            // 确保每个节点都有 children 键，即使它为空
            if (!isset($node[$childrenKey]) && $alwaysChildren) {
                $node[$childrenKey] = [];
            }

            // 如果当前节点有子节点，递归为每个子节点添加子节点
            if (isset($lookup[$node[$primaryKey]]) && isset($lookup[$node[$primaryKey]][$childrenKey])) {
                $node[$childrenKey] = $this->addChildrenRecursively(
                    $lookup[$node[$primaryKey]][$childrenKey],
                    $lookup,
                    $alwaysChildren,
                    $primaryKey,
                    $childrenKey
                );
            }
        }
        return $nodes;
    }
}