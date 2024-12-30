<?php

namespace Tools;

use PHPUnit\Framework\TestCase;
use Raiseinfo\Tools;

class ToolsFindNodeFormTreeTest extends TestCase
{
    /**
     * 测试找到目标节点的情况。
     *
     * @covers \Raiseinfo\Tools\findNodeFormTree
     * @return void
     */
    public function testFindExistingNode()
    {
        $tree = [
            ['id' => 1, 'label' => 'Node 1', 'children' => [
                ['id' => 2, 'label' => 'Node 2', 'children' => []],
                ['id' => 3, 'label' => 'Node 3', 'children' => [
                    ['id' => 4, 'label' => 'Node 4', 'children' => []]
                ]]
            ]],
            ['id' => 5, 'label' => 'Node 5', 'children' => []]
        ];

        // 查找存在的节点
        $tools = new Tools();
        $result = $tools->findNodeFormTree($tree, 4);
        $expected = ['id' => 4, 'label' => 'Node 4', 'children' => []];
        $this->assertEquals($expected, $result, '应找到目标节点 4');
    }

    /**
     * 测试找不到目标节点的情况。
     *
     * @covers \Raiseinfo\Tools\findNodeFormTree
     * @return void
     */
    public function testFindNonExistingNode()
    {
        $tree = [
            ['id' => 1, 'label' => 'Node 1', 'children' => [
                ['id' => 2, 'label' => 'Node 2', 'children' => []],
                ['id' => 3, 'label' => 'Node 3', 'children' => [
                    ['id' => 4, 'label' => 'Node 4', 'children' => []]
                ]]
            ]],
            ['id' => 5, 'label' => 'Node 5', 'children' => []]
        ];

        // 查找不存在的节点
        $tools = new Tools();
        $result = $tools->findNodeFormTree($tree, 999);
        $this->assertNull($result, '应返回 null，因为节点 999 不存在');
    }

    /**
     * 测试空树的情况。
     *
     * @covers \Raiseinfo\Tools\findNodeFormTree
     * @return void
     */
    public function testEmptyTree()
    {
        $tree = [];

        // 查找空树中的节点
        $tools = new Tools();
        $result = $tools->findNodeFormTree($tree, 1);
        $this->assertNull($result, '空树中应返回 null');
    }

    /**
     * 测试单节点树的情况。
     *
     * @covers \Raiseinfo\Tools\findNodeFormTree
     * @return void
     */
    public function testSingleNodeTree()
    {
        $tree = [
            ['id' => 1, 'label' => 'Node 1', 'children' => []]
        ];

        // 查找根节点
        $tools = new Tools();
        $result = $tools->findNodeFormTree($tree, 1);
        $expected = ['id' => 1, 'label' => 'Node 1', 'children' => []];
        $this->assertEquals($expected, $result, '应找到根节点 1');
    }

    /**
     * 测试自定义键名的情况。
     *
     * @covers \Raiseinfo\Tools\findNodeFormTree
     * @return void
     */
    public function testCustomKeys()
    {
        $tree = [
            ['node_id' => 1, 'node_label' => 'Node 1', 'sub_nodes' => [
                ['node_id' => 2, 'node_label' => 'Node 2', 'sub_nodes' => []],
                ['node_id' => 3, 'node_label' => 'Node 3', 'sub_nodes' => [
                    ['node_id' => 4, 'node_label' => 'Node 4', 'sub_nodes' => []]
                ]]
            ]],
            ['node_id' => 5, 'node_label' => 'Node 5', 'sub_nodes' => []]
        ];

        // 查找存在节点，使用自定义键名
        $tools = new Tools();
        $result = $tools->findNodeFormTree($tree, 4, 'node_id', 'sub_nodes');
        $expected = ['node_id' => 4, 'node_label' => 'Node 4', 'sub_nodes' => []];
        $this->assertEquals($expected, $result, '应找到目标节点 4，使用自定义键名');
    }
}