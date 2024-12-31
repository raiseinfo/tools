<?php


namespace Tools;

use PHPUnit\Framework\TestCase;
use Raiseinfo\Tools;

class ToolsFindLeafNodeIdsTest extends TestCase
{
    /**
     * 测试 findLeafNodeIds 方法是否能正确收集树结构中的所有叶子节点 ID。
     */
    public function testFindLeafNodeIds()
    {
        // 创建一个树结构的样本数据
        $tree = [
            [
                'id' => 1,
                'children' => [
                    ['id' => 2, 'children' => []],
                    ['id' => 3, 'children' => [
                        ['id' => 6, 'children' => []],
                        ['id' => 7, 'children' => []]
                    ]],
                    ['id' => 4, 'children' => []]
                ]
            ],
            [
                'id' => 5,
                'children' => []
            ]
        ];

        // 实例化 Tools 类
        $tools = new Tools();

        // 调用 findLeafNodeIds 方法，并传入样本数据
        $leafNodeIds = $tools->findLeafNodeIds($tree);

        // 预期结果是所有叶子节点的ID
        $expected = [2, 6, 7, 4, 5];

        // 断言方法返回的结果与预期结果相同
        $this->assertEquals($expected, $leafNodeIds);
    }

    /**
     * 测试空树的情况
     */
    public function testFindLeafNodeIdsWithEmptyTree()
    {
        // 空树结构
        $emptyTree = [];

        // 实例化 Tools 类
        $tools = new Tools();

        // 调用 findLeafNodeIds 方法，并传入空树数据
        $leafNodeIds = $tools->findLeafNodeIds($emptyTree);

        // 预期结果是一个空数组
        $expected = [];

        // 断言方法返回的结果与预期结果相同
        $this->assertEquals($expected, $leafNodeIds);
    }

    /**
     * 测试只有根节点的情况
     */
    public function testFindLeafNodeIdsWithOnlyRootNode()
    {
        // 只有根节点的树结构
        $singleNodeTree = [
            ['id' => 1, 'children' => []]
        ];

        // 实例化 Tools 类
        $tools = new Tools();

        // 调用 findLeafNodeIds 方法，并传入只有一个根节点的树数据
        $leafNodeIds = $tools->findLeafNodeIds($singleNodeTree);

        // 预期结果是根节点的ID
        $expected = [1];

        // 断言方法返回的结果与预期结果相同
        $this->assertEquals($expected, $leafNodeIds);
    }

    /**
     * 测试自定义主键和子节点键名的情况
     */
    public function testFindLeafNodeIdsWithCustomKeys()
    {
        // 使用自定义主键和子节点键名的树结构
        $customKeyTree = [
            [
                'node_id' => 1,
                'subnodes' => [
                    ['node_id' => 2, 'subnodes' => []],
                    ['node_id' => 3, 'subnodes' => [
                        ['node_id' => 6, 'subnodes' => []],
                        ['node_id' => 7, 'subnodes' => []]
                    ]],
                    ['node_id' => 4, 'subnodes' => []]
                ]
            ],
            [
                'node_id' => 5,
                'subnodes' => []
            ]
        ];

        // 实例化 Tools 类
        $tools = new Tools();

        // 调用 findLeafNodeIds 方法，并传入自定义键名的树数据
        $leafNodeIds = $tools->findLeafNodeIds($customKeyTree, 'node_id', 'subnodes');

        // 预期结果是所有叶子节点的ID
        $expected = [2, 6, 7, 4, 5];

        // 断言方法返回的结果与预期结果相同
        $this->assertEquals($expected, $leafNodeIds);
    }

}