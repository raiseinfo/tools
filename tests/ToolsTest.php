<?php

namespace Raiseinfo\Tools\Tests;

use PHPUnit\Framework\TestCase;
use Raiseinfo\Tools\Tools;

class ToolsTest extends TestCase
{
    public function testHelpMethod()
    {
        $tools = new Tools();
        $this->assertEquals("this is tools help doc", $tools->help());
    }
}