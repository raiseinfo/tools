<?php
// tests/ToolsTest.php

namespace Audios;

use PHPUnit\Framework\TestCase;
use Raiseinfo\Audios;

final class AudiosHelpTest extends TestCase
{
    public function testHelp(): void
    {
        $audios = new Audios();
        $this->assertEquals('this is a helper!', $audios->help());
    }
}