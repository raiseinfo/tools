<?php

use PHPUnit\Framework\TestCase;
use Raiseinfo\Tools;

class FindChildrenTest extends TestCase
{

    private Tools $tools;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tools = new Tools();
    }

    /**
     * 测试没有子项的情况
     */
    public function testNoChildren()
    {
        $data = [
            ['id' => 1, 'pid' => 0],
        ];
        $result = $this->tools->findChildren($data, 1);
        $this->assertEquals([], $result);
    }

    /**
     * 测试单层子项
     */
    public function testSingleLevelChildren()
    {
        $data = [
            ['id' => 1, 'pid' => 0],
            ['id' => 2, 'pid' => 1],
            ['id' => 3, 'pid' => 1],
        ];
        $result = $this->tools->findChildren($data, 1);
        $this->assertEquals([2, 3], $result);
    }

    /**
     * 测试多层子项
     */
    public function testMultiLevelChildren()
    {
        $data = [
            ['id' => 1, 'pid' => 0],
            ['id' => 2, 'pid' => 1],
            ['id' => 3, 'pid' => 1],
            ['id' => 4, 'pid' => 2],
            ['id' => 5, 'pid' => 2],
            ['id' => 6, 'pid' => 3],
        ];
        $result = $this->tools->findChildren($data, 1);
        $this->assertEqualsCanonicalizing([2, 3, 4, 5, 6], $result);
    }

    /**
     * 测试指定不同的主键和外键字段名
     */
    public function testCustomPrimaryKeyAndForeignKey()
    {
        $data = [
            ['node_id' => 1, 'parent_id' => 0],
            ['node_id' => 2, 'parent_id' => 1],
            ['node_id' => 3, 'parent_id' => 1],
        ];
        $result = $this->tools->findChildren($data, 1, 'node_id', 'parent_id');
        $this->assertEquals([2, 3], $result);
    }

    /**
     * 测试不存在的父ID
     */
    public function testNonExistentParentId()
    {
        $data = [
            ['id' => 1, 'pid' => 0],
            ['id' => 2, 'pid' => 1],
        ];
        $result = $this->tools->findChildren($data, 999);
        $this->assertEquals([], $result);
    }
}

// 注意：这里的YourClass应该替换为你实际定义findChildren方法的类名。