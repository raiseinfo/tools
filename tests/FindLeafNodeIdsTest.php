<?php

use PHPUnit\Framework\TestCase;
use function \Raiseinfo\Tools\findLeafNodeIds;

class FindLeafNodeIdsTest extends TestCase
{
    /**
     * 测试 findLeafNodeIds 是否正确返回所有的叶子节点 ID。
     *
     * @return void
     */
    public function testFindLeafNodeIdsReturnsCorrectLeafIds()
    {
        // 构造一个树结构
        $tree = [
            ['id' => 1, 'children' => [
                ['id' => 2, 'children' => []],
                ['id' => 3, 'children' => [
                    ['id' => 4, 'children' => []],
                    ['id' => 5, 'children' => []]
                ]]
            ]],
            ['id' => 6, 'children' => [
                ['id' => 7, 'children' => []]
            ]],
            ['id' => 8, 'children' => []]
        ];

        // 预期结果
        $expectedLeafNodeIds = [2, 4, 5, 7, 8];

        // 调用函数并获取返回值
        $result = findLeafNodeIds($tree);

        // 断言实际结果与预期结果相等
        $this->assertEquals($expectedLeafNodeIds, $result, '叶子节点 ID 应该匹配');
    }
}