<?php
// getTreeSelectOptionsTest.php

// 引入 Composer 的自动加载器
require __DIR__ . '/../vendor/autoload.php';

use function Raiseinfo\Tools\getTreeSelectOptions;

// 辅助函数：格式化输出数组为 JSON
function printResult($result)
{
    echo json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
}

// 测试 1: 空数据
echo "Test 1: Empty Data" . PHP_EOL;
$data = [];
$result = getTreeSelectOptions($data);
printResult($result);

// 测试 2: 单层数据
echo PHP_EOL . "Test 2: Single Level Data" . PHP_EOL;
$data = [
    ['id' => 1, 'pid' => 0, 'label' => 'Option 1', 'value' => '1'],
    ['id' => 2, 'pid' => 0, 'label' => 'Option 2', 'value' => '2'],
    ['id' => 3, 'pid' => 0, 'label' => 'Option 3', 'value' => '3'],
];
$result = getTreeSelectOptions($data);
printResult($result);

// 测试 3: 多层数据
echo PHP_EOL . "Test 3: Multi Level Data" . PHP_EOL;
$data = [
    ['id' => 1, 'pid' => 0, 'label' => 'Parent 1', 'value' => '1'],
    ['id' => 2, 'pid' => 1, 'label' => 'Child 1-1', 'value' => '2'],
    ['id' => 3, 'pid' => 1, 'label' => 'Child 1-2', 'value' => '3'],
    ['id' => 4, 'pid' => 2, 'label' => 'Grandchild 1-1-1', 'value' => '4'],
    ['id' => 5, 'pid' => 0, 'label' => 'Parent 2', 'value' => '5'],
];
$result = getTreeSelectOptions($data);
printResult($result);

// 测试 4: 自定义键名
echo PHP_EOL . "Test 4: Custom Keys" . PHP_EOL;
$data = [
    ['node_id' => 1, 'parent_id' => 0, 'text' => 'Option 1', 'val' => '1'],
    ['node_id' => 2, 'parent_id' => 1, 'text' => 'Option 2', 'val' => '2'],
];
$result = getTreeSelectOptions(
    $data,
    0,
    'node_id',
    'parent_id',
    'text',
    'val'
);
printResult($result);

// 测试 5: 不同的根节点 ID
echo PHP_EOL . "Test 5: Different Root ID" . PHP_EOL;
$data = [
    ['id' => 1, 'pid' => 10, 'label' => 'Option 1', 'value' => '1'],
    ['id' => 2, 'pid' => 1, 'label' => 'Option 2', 'value' => '2'],
];
$result = getTreeSelectOptions($data, 10);
printResult($result);