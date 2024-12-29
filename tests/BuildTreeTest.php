<?php

use PHPUnit\Framework\TestCase;

use function \Raiseinfo\Tools\buildTree;

class BuildTreeTest extends TestCase
{
    /**
     * 测试简单的树结构。
     *
     * @covers \Raiseinfo\Tools\buildTree
     * @return void
     */
    public function testSimpleTree()
    {
        $nodes = [
            ['id' => 1, 'pid' => 0, 'label' => 'Node 1'],
            ['id' => 2, 'pid' => 1, 'label' => 'Node 2'],
            ['id' => 3, 'pid' => 1, 'label' => 'Node 3'],
            ['id' => 4, 'pid' => 2, 'label' => 'Node 4']
        ];

        $expected = [
            [
                'id' => 1, 'pid' => 0, 'label' => 'Node 1', 'children' => [
                [
                    'id' => 2, 'pid' => 1, 'label' => 'Node 2', 'children' => [
                    ['id' => 4, 'pid' => 2, 'label' => 'Node 4', 'children' => []]
                ]
                ],
                ['id' => 3, 'pid' => 1, 'label' => 'Node 3', 'children' => []]
            ]
            ]
        ];

        $result = buildTree($nodes);
        $this->assertEquals($expected, $result, '应生成正确的树结构');
    }

    /**
     * 测试空数组的情况。
     *
     * @covers \Raiseinfo\Tools\buildTree
     * @return void
     */
    public function testEmptyArray()
    {
        $nodes = [];
        $expected = [];

        $result = buildTree($nodes);
        $this->assertEquals($expected, $result, '空数组应返回空数组');
    }

    /**
     * 测试单节点的情况。
     *
     * @covers \Raiseinfo\Tools\buildTree
     * @return void
     */
    public function testSingleNode()
    {
        $nodes = [['id' => 1, 'pid' => 0, 'label' => 'Node 1']];

        $expected = [
            ['id' => 1, 'pid' => 0, 'label' => 'Node 1', 'children' => []]
        ];

        $result = buildTree($nodes);
        $this->assertEquals($expected, $result, '单节点应返回正确的树结构');
    }

    /**
     * 测试自定义键名的情况。
     *
     * @covers \Raiseinfo\Tools\buildTree
     * @return void
     */
    public function testCustomKeys()
    {
        $nodes = [
            ['node_id' => 1, 'parent_id' => 0, 'node_label' => 'Node 1'],
            ['node_id' => 2, 'parent_id' => 1, 'node_label' => 'Node 2'],
            ['node_id' => 3, 'parent_id' => 1, 'node_label' => 'Node 3']
        ];

        $expected = [
            [
                'node_id' => 1, 'parent_id' => 0, 'node_label' => 'Node 1', 'sub_nodes' => [
                ['node_id' => 2, 'parent_id' => 1, 'node_label' => 'Node 2', 'sub_nodes' => []],
                ['node_id' => 3, 'parent_id' => 1, 'node_label' => 'Node 3', 'sub_nodes' => []]
            ]
            ]
        ];

        $result = buildTree($nodes, 0, 'node_id', 'parent_id', 'sub_nodes');
        $this->assertEquals($expected, $result, '应生成正确的树结构，使用自定义键名');
    }

    /**
     * 测试节点缺少必要键的情况。
     *
     * @covers \Raiseinfo\Tools\buildTree
     * @return void
     */
    public function testMissingKeys()
    {
        $this->expectException(InvalidArgumentException::class);

        $nodes = [
            ['id' => 1, 'pid' => 0, 'label' => 'Node 1'],
            ['id' => 2, 'label' => 'Node 2']  // 缺少 pid 键
        ];

        buildTree($nodes);
    }

    /**
     * 测试父节点不存在的情况。
     *
     * @covers \Raiseinfo\Tools\buildTree
     * @return void
     */
    public function testNonexistentParent()
    {
        $nodes = [
            ['id' => 1, 'pid' => 0, 'label' => 'Node 1'],
            ['id' => 2, 'pid' => 999, 'label' => 'Node 2']  // 父节点 999 不存在
        ];

        $expected = [
            ['id' => 1, 'pid' => 0, 'label' => 'Node 1', 'children' => []]
        ];

        $result = buildTree($nodes);
        $this->assertEquals($expected, $result, '应忽略不存在的父节点');
    }
}