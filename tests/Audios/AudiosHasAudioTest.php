<?php

use PHPUnit\Framework\TestCase;
use Raiseinfo\Audios;

class AudiosHasAudioTest extends TestCase
{
    /**
     * @var Audios
     */
    private $audios;

    protected function setUp(): void
    {
        parent::setUp();
        $this->audios = new Audios();
    }

    /**
     * 测试有声音的音频文件
     */
    public function testHasAudioWithSound()
    {
        // 假设有一个包含声音的音频文件位于 tests/fixtures 目录下
        $audioFileUrl = __DIR__ . '/fixtures/audio_with_sound.mp3';

        // 执行 hasAudio 方法并断言结果
        $result = $this->audios->hasAudio($audioFileUrl);
        $this->assertTrue($result, '音频文件应包含声音');
    }

    /**
     * 测试完全静音的音频文件
     */
    public function testHasAudioWithSilentAudio()
    {
        // 假设有一个完全静音的音频文件位于 tests/fixtures 目录下
        $audioFileUrl = __DIR__ . '/fixtures/silent_audio.mp3';

        // 执行 hasAudio 方法并断言结果
        $result = $this->audios->hasAudio($audioFileUrl);
        $this->assertFalse($result, '音频文件应为完全静音');
    }

    /**
     * 测试无效 URL 或文件路径
     */
    public function testHasAudioWithInvalidUrl()
    {
        // 提供一个无效的 URL
        $invalidUrl = 'http://example.com/invalid_url.mp3';

        // 执行 hasAudio 方法并断言抛出异常
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to load file');

        // 执行 hasAudio 方法
        $this->audios->hasAudio($invalidUrl);
    }

    /**
     * 测试空文件或损坏文件
     */
    public function testHasAudioWithEmptyOrCorruptedFile()
    {
        // 创建一个临时的空文件
        $tempFile = tempnam(sys_get_temp_dir(), 'test_audio');
        file_put_contents($tempFile, '');

        // 执行 hasAudio 方法并断言抛出异常
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Downloaded file is empty or corrupted');

        // 执行 hasAudio 方法
        $this->audios->hasAudio($tempFile);

        // 清理临时文件
        unlink($tempFile);
    }

    /**
     * 测试音频文件总时长为 0 的情况
     */
    public function testHasAudioWithZeroDuration()
    {
        // 假设有一个总时长为 0 秒的音频文件位于 tests/fixtures 目录下
        $audioFileUrl = __DIR__ . '/fixtures/zero_duration_audio.mp3';

        // 执行 hasAudio 方法并断言结果
        $result = $this->audios->hasAudio($audioFileUrl);
        $this->assertFalse($result, '音频文件总时长为 0，应返回无声音');
    }
}