<?php

namespace Audios;

use PHPUnit\Framework\TestCase;
use Raiseinfo\Audios;

class AudiosGetAudioDurationTest extends TestCase
{
    private Audios $audios;

    protected function setUp(): void
    {
        parent::setUp();
        $this->audios = new Audios();
    }

    /**
     * 测试从本地文件路径获取音频文件并成功计算其时长
     */
    public function testGetAudioDurationFromLocalFile()
    {
        // 指定本地音频文件的路径
        $localFilePath = __DIR__ . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'sample.mp3';

        // 调用 getAudioDuration 方法
        $duration = $this->audios->getAudioDuration($localFilePath);

        // 断言返回的时长是否正确
        // 注意：你需要根据实际音频文件的时长调整这里的期望值
        $this->assertGreaterThan(30, $duration, 'The duration should be greater than 0 for a valid audio file.');
    }

    /**
     * 测试传入无效的本地文件路径时的行为
     */
    public function testGetAudioDurationWithInvalidFilePath()
    {
        // 无效的本地文件路径
        $invalidFilePath = __DIR__ . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'nonexistent.mp3';

        // 调用 getAudioDuration 方法
        $duration = $this->audios->getAudioDuration($invalidFilePath);

        // 断言返回的时长是否为 0
        $this->assertEquals(0, $duration, 'The duration should be 0 for an invalid file path.');
    }

    /**
     * 测试传入空文件或损坏文件时的行为
     */
    public function testGetAudioDurationWithEmptyOrCorruptedFile()
    {
        // 模拟空文件或损坏文件的路径
        $emptyFilePath = __DIR__ . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'empty.mp3';

        // 调用 getAudioDuration 方法
        $duration = $this->audios->getAudioDuration($emptyFilePath);

        // 断言返回的时长是否为 0
        $this->assertEquals(0, $duration, 'The duration should be 0 for an empty or corrupted file.');
    }


    /**
     * 测试从远程 URL 获取音频文件并成功计算其时长
     *
     * 注意：这个测试需要网络连接，并且依赖于外部资源。
     * 如果你不希望在每次测试中都访问网络，可以考虑跳过此测试或使用本地文件进行测试。
     */
    public function testGetAudioDurationFromRemoteUrl()
    {
        // 指定远程音频文件的 URL
        $remoteUrl = 'https://dssb.oss-cn-beijing.aliyuncs.com/origin.mp3';

        // 调用 getAudioDuration 方法
        $duration = $this->audios->getAudioDuration($remoteUrl);

        // 断言返回的时长是否正确
        // 注意：你需要根据实际音频文件的时长调整这里的期望值
        $this->assertGreaterThan(30, $duration, 'The duration should be greater than 0 for a valid remote audio file.');
    }
    

}