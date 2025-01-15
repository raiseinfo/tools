<?php

namespace Tools;

use PHPUnit\Framework\TestCase;
use Raiseinfo\Tools;

class FindParentIdsTest extends TestCase
{
    private Tools $tools;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tools = new Tools();
    }


    public function testFindParentIds()
    {
        $data = [
            ['id' => 1, 'pid' => 0, 'name' => 'Electronics'],
            ['id' => 2, 'pid' => 1, 'name' => 'Computers'],
            ['id' => 3, 'pid' => 2, 'name' => 'Laptops'],
            ['id' => 4, 'pid' => 2, 'name' => 'Desktops'],
            ['id' => 5, 'pid' => 1, 'name' => 'Mobile Phones'],
        ];

        // 测试获取ID为3的项的所有父级ID
        $result = $this->tools->findParentIds($data, 3);
        $this->assertEquals('1,2,3', $result);

        // 测试获取顶级ID(无父级)的项的父级ID
        $result = $this->tools->findParentIds($data, 1);
        $this->assertEquals('1', $result);

        // 测试一个不存在的ID，应该返回空字符串或自定义错误信息
        $result = $this->tools->findParentIds($data, 999);
        $this->assertEquals('', $result);

        // 测试没有父节点的情况（直接顶级节点）
        $result = $this->tools->findParentIds($data, 5);
        $this->assertEquals('1,5', $result);
    }

}