<?php

namespace Tools;

use PHPUnit\Framework\TestCase;
use Raiseinfo\Tools;

class ToolsGetTreeSelectOptionsTest extends TestCase
{
    /**
     * 测试 getTreeSelectOptions 是否正确生成树形下拉框的选项数据。
     *
     * @return void
     */
    public function testGetTreeSelectOptionsGeneratesCorrectTree()
    {
        // 构造平面结构的数据
        $data = [
            ['id' => 1, 'pid' => 0, 'label' => 'Node 1', 'value' => 1],
            ['id' => 2, 'pid' => 1, 'label' => 'Node 2', 'value' => 2],
            ['id' => 3, 'pid' => 1, 'label' => 'Node 3', 'value' => 3],
            ['id' => 4, 'pid' => 2, 'label' => 'Node 4', 'value' => 4],
            ['id' => 5, 'pid' => 2, 'label' => 'Node 5', 'value' => 5]
        ];

        // 预期的树形结构
        $expectedTree = [
            [
                'label' => 'Node 1',
                'value' => 1,
                'children' => [
                    [
                        'label' => 'Node 2',
                        'value' => 2,
                        'children' => [
                            ['label' => 'Node 4', 'value' => 4],
                            ['label' => 'Node 5', 'value' => 5]
                        ]
                    ],
                    ['label' => 'Node 3', 'value' => 3]
                ]
            ]
        ];

        // 调用函数并获取返回值
        $tools = new Tools();
        $result = $tools->getTreeSelectOptions($data);

        // 断言实际结果与预期结果相等
        $this->assertEquals($expectedTree, $result, '生成的树形结构应与预期相同');

        // 断言返回的数组是一个数组
        $this->assertIsArray($result, '返回的结果应该是一个数组');

        // 断言返回的数组长度是否符合预期
        $this->assertCount(count($expectedTree), $result, '返回的树形结构长度应与预期相同');
    }

    /**
     * 测试空数据时的返回结果。
     *
     * @return void
     */
    public function testGetTreeSelectOptionsWithEmptyData()
    {
        // 构造空数据
        $data = [];

        // 预期的树形结构（空数组）
        $expectedTree = [];

        // 调用函数并获取返回值
        $tools = new Tools();
        $result = $tools->getTreeSelectOptions($data);

        // 断言实际结果与预期结果相等
        $this->assertEquals($expectedTree, $result, '空数据时应返回空数组');

        // 断言返回的数组是一个数组
        $this->assertIsArray($result, '返回的结果应该是一个数组');

        // 断言返回的数组长度是否符合预期
        $this->assertCount(0, $result, '空数据时返回的数组长度应为 0');
    }

    /**
     * 测试单节点树的情况。
     *
     * @return void
     */
    public function testGetTreeSelectOptionsWithSingleNode()
    {
        // 构造单节点数据
        $data = [
            ['id' => 1, 'pid' => 0, 'label' => 'Node 1', 'value' => 1]
        ];

        // 预期的树形结构
        $expectedTree = [
            ['label' => 'Node 1', 'value' => 1]
        ];

        // 调用函数并获取返回值
        $tools = new Tools();
        $result = $tools->getTreeSelectOptions($data);

        // 断言实际结果与预期结果相等
        $this->assertEquals($expectedTree, $result, '单节点树应返回正确的树形结构');

        // 断言返回的数组是一个数组
        $this->assertIsArray($result, '返回的结果应该是一个数组');

        // 断言返回的数组长度是否符合预期
        $this->assertCount(1, $result, '单节点树返回的数组长度应为 1');
    }

    /**
     * 测试自定义键名的情况。
     *
     * @return void
     */
    public function testGetTreeSelectOptionsWithCustomKeys()
    {
        // 构造自定义键名的数据
        $data = [
            ['node_id' => 1, 'parent_id' => 0, 'node_label' => 'Node 1', 'node_value' => 1],
            ['node_id' => 2, 'parent_id' => 1, 'node_label' => 'Node 2', 'node_value' => 2],
            ['node_id' => 3, 'parent_id' => 1, 'node_label' => 'Node 3', 'node_value' => 3]
        ];

        // 预期的树形结构
        $expectedTree = [
            [
                'label' => 'Node 1',
                'value' => 1,
                'children' => [
                    ['label' => 'Node 2', 'value' => 2],
                    ['label' => 'Node 3', 'value' => 3]
                ]
            ]
        ];

        // 调用函数并传递自定义键名
        $tools = new Tools();
        $result = $tools->getTreeSelectOptions(
            $data,
            0,
            'node_id',   // 主键键名
            'parent_id', // 外键键名
            'node_label',// 标签键名
            'node_value',// 值的键名
            'children'  // 子节点的键名
        );

        // 断言实际结果与预期结果相等
        $this->assertEquals($expectedTree, $result, '自定义键名时应返回正确的树形结构');

        // 断言返回的数组是一个数组
        $this->assertIsArray($result, '返回的结果应该是一个数组');

        // 断言返回的数组长度是否符合预期
        $this->assertCount(1, $result, '自定义键名时返回的数组长度应为 1');
    }
}