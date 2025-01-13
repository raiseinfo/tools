<?php

namespace Tools;

use PHPUnit\Framework\TestCase;
use Raiseinfo\Tools;

class ToolsUploadFilterTest extends TestCase
{
    private $tools;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tools = new Tools();
    }

    public function testValidFilename()
    {
        $filename = "example.jpg";
        $filteredFilename = $this->tools->uploadFilter($filename);
        $this->assertEquals($filename, $filteredFilename, 'Valid filename should not be filtered.');
    }

    public function testInvalidPhpExtension()
    {
        $filenames = ["script.php", "script.php3", "script.php4", "script.php5"];
        foreach ($filenames as $filename) {
            $filteredFilename = $this->tools->uploadFilter($filename);
            $this->assertNull($filteredFilename, "File with extension {$filename} should be filtered.");
        }
    }

    public function testInvalidPhtmlExtension()
    {
        $filename = "script.phtml";
        $filteredFilename = $this->tools->uploadFilter($filename);
        $this->assertNull($filteredFilename, "File with .phtml extension should be filtered.");
    }

    public function testInvalidPhtExtension()
    {
        $filename = "script.pht";
        $filteredFilename = $this->tools->uploadFilter($filename);
        $this->assertNull($filteredFilename, "File with .pht extension should be filtered.");
    }

    public function testMixedExtensions()
    {
        $filenames = [
            "safe_image.jpg",
            "dangerous_script.php",
            "another_dangerous_script.php3",
            "yet_another_dangerous_script.phtml"
        ];

        foreach ($filenames as $filename) {
            $filteredFilename = $this->tools->uploadFilter($filename);
            if (in_array($filename, ["dangerous_script.php", "another_dangerous_script.php3", "yet_another_dangerous_script.phtml"])) {
                $this->assertNull($filteredFilename, "File with dangerous extension {$filename} should be filtered.");
            } else {
                $this->assertEquals($filename, $filteredFilename, "Safe filename {$filename} should not be filtered.");
            }
        }
    }

    public function testComplexValidFilename()
    {
        $filename = "user_profile_picture_2023.png";
        $filteredFilename = $this->tools->uploadFilter($filename);
        $this->assertEquals($filename, $filteredFilename, 'Complex valid filename should not be filtered.');
    }

    public function testCustomBlacklistPatterns()
    {
        $customBlacklistPatterns = [
            '/\.txt$/i',  // 匹配 .txt
            '/\.log$/i'   // 匹配 .log
        ];

        // 测试自定义黑名单模式
        $filenames = [
            "note.txt",
            "error.log",
            "image.png"
        ];

        foreach ($filenames as $filename) {
            $filteredFilename = $this->tools->uploadFilter($filename, $customBlacklistPatterns);
            if (in_array($filename, ["note.txt", "error.log"])) {
                $this->assertNull($filteredFilename, "File with custom blacklist extension {$filename} should be filtered.");
            } else {
                $this->assertEquals($filename, $filteredFilename, "Safe filename {$filename} should not be filtered.");
            }
        }
    }

}