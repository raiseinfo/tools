<?php

use PHPUnit\Framework\TestCase;
use Raiseinfo\Tools;

class FindParentNamesTest extends TestCase
{
    private Tools $tools;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tools = new Tools();
    }


    public function testFindParentNames()
    {
        $data = [
            ['id' => 1, 'pid' => 0, 'name' => 'Electronics'],
            ['id' => 2, 'pid' => 1, 'name' => 'Computers'],
            ['id' => 3, 'pid' => 2, 'name' => 'Laptops'],
            ['id' => 4, 'pid' => 2, 'name' => 'Desktops'],
            ['id' => 5, 'pid' => 1, 'name' => 'Mobile Phones'],
        ];

        // 测试获取ID为3的项的所有父级名称
        $result = $this->tools->findParentNames($data, 3);
        $this->assertEquals('Electronics-Computers-Laptops', $result);

        // 测试获取顶级ID(无父级)的项的父级名称
        $result = $this->tools->findParentNames($data, 1);
        $this->assertEquals('Electronics', $result);

        // 测试一个不存在的ID，应该返回空字符串或自定义错误信息
        $result = $this->tools->findParentNames($data, 999);
        $this->assertEquals('', $result);
    }
}