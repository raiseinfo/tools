<?php
// src/functions.php

namespace Raiseinfo\Tools;

/**
 * 将平面结构的数据转换为树结构
 *
 * @param array $nodes 平面结构数据
 * @param int $root 根节点ID，默认为 0
 * @param string $primaryKey 主键键名，默认为 'id'
 * @param string $foreignKey 外键键名，默认为 'pid'
 * @param string $childrenKey 子节点键名，默认为 'children'
 * @return array 返回树形结构的数据
 */
function buildTree(
    array  $nodes,
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
    return addChildrenRecursively($tree, $lookup, $primaryKey, $childrenKey);
}

/**
 * 递归为每个节点添加子节点
 *
 * @param array $nodes 当前层级的节点数组
 * @param array $lookup 所有节点的哈希表
 * @param string $primaryKey 主键键名
 * @param string $childrenKey 子节点键名
 * @return array 返回带有子节点的树结构
 */
function addChildrenRecursively(array $nodes, array $lookup, string $primaryKey, string $childrenKey): array
{
    foreach ($nodes as &$node) {
        // 确保每个节点都有 children 键，即使它为空
        if (!isset($node[$childrenKey])) {
            $node[$childrenKey] = [];
        }

        // 如果当前节点有子节点，递归为每个子节点添加子节点
        if (isset($lookup[$node[$primaryKey]]) && isset($lookup[$node[$primaryKey]][$childrenKey])) {
            $node[$childrenKey] = addChildrenRecursively($lookup[$node[$primaryKey]][$childrenKey], $lookup, $primaryKey, $childrenKey);
        }
    }
    return $nodes;
}

/**
 * 从树中找到指定ID的节点的完整信息
 *
 * @param array $tree 需要查找的树
 * @param int $targetId 查找的节点ID
 * @param string $primaryKey 主键名，默认为 'id'
 * @param string $childrenKey 子节点的键名，默认为 'children'
 * @return array|null 返回找到的节点及其子节点，如果没有找到则返回 null
 */
function findNodeFormTree(
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
            $result = findNodeFormTree($node[$childrenKey], $targetId, $primaryKey, $childrenKey);
            if ($result !== null) {
                return $result;
            }
        }
    }

    // 如果遍历完所有节点仍未找到目标节点，返回 null
    return null;
}


/**
 * 收集树结构所有的叶子节点的ID
 * @param array $tree 需要查找的树
 * @param string $primaryKey 主键键名
 * @param string $childrenKey 子节点键名
 * @return mixed
 */
function findLeafNodeIds(
    array  $tree,
    string $primaryKey = 'id',
    string $childrenKey = 'children'
): array
{
    $leafNodeIds = [];

    foreach ($tree as $node) {
        // 如果没有 'children' 键或 'children' 数组为空，则为叶子节点
        if (!isset($node[$childrenKey]) || empty($node[$childrenKey])) {
            // 将叶子节点的ID添加到结果数组中
            $leafNodeIds[] = $node[$primaryKey];
        } else {
            // 递归查找子节点中的叶子节点
            $leafNodeIds = array_merge($leafNodeIds, findLeafNodeIds($node[$childrenKey], $primaryKey, $childrenKey));
        }
    }

    return $leafNodeIds;
}

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
function getTreeSelectOptions(
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
            $children = getTreeSelectOptions($data, $node[$primaryKey], $primaryKey, $foreignKey, $labelKey, $valueKey, $childrenKey);
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
