<?php
// src/functions.php

namespace Raiseinfo\Tools;


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
