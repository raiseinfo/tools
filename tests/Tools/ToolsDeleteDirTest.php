<?php

namespace Tools;

use PHPUnit\Framework\TestCase;
use Raiseinfo\Tools;

class ToolsDeleteDirTest extends TestCase
{
    private $tools;
    private $testDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tools = new Tools();
        // 使用系统临时目录创建一个唯一的测试目录
        $this->testDir = sys_get_temp_dir() . '/test_delete_dir_' . uniqid();
    }

    protected function tearDown(): void
    {
        // 清理：如果测试目录仍然存在，则尝试删除它
        if (is_dir($this->testDir)) {
            try {
                $this->tools->deleteDir($this->testDir);
            } catch (\Exception $e) {
                // 在tearDown中捕获异常以避免影响其他测试
                error_log("Failed to clean up test directory: " . $e->getMessage());
            }
        }
        parent::tearDown();
    }

    /**
     * 测试删除一个空目录。
     */
    public function testDeleteEmptyDirectory()
    {
        // 创建空测试目录
        mkdir($this->testDir, 0777, true);

        // 调用 deleteDir 并检查结果
        $this->assertTrue($this->tools->deleteDir($this->testDir), '应成功删除空目录');
        $this->assertFalse(is_dir($this->testDir), '空目录未被删除');
    }

    /**
     * 测试删除一个非空目录。
     */
    public function testDeleteNonEmptyDirectory()
    {
        // 创建测试目录及其子文件和子目录
        mkdir($this->testDir, 0777, true);
        file_put_contents($this->testDir . '/file.txt', 'content');
        mkdir($this->testDir . '/subdir', 0777, true);

        // 调用 deleteDir 并检查结果
        $this->assertTrue($this->tools->deleteDir($this->testDir), '应成功删除非空目录');
        $this->assertFalse(is_dir($this->testDir), '非空目录未被删除');
    }

    /**
     * 测试尝试删除不存在的目录。
     */
    public function testDeleteNonExistentDirectory()
    {
        $nonExistentDir = $this->testDir . '/does_not_exist';

        // 确保目录不存在
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("$nonExistentDir 不是一个有效的目录");

        // 尝试删除不存在的目录
        $this->tools->deleteDir($nonExistentDir);
    }
}