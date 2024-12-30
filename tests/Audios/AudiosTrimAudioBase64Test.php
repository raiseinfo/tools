<?php

namespace Audios;

use PHPUnit\Framework\TestCase;
use Raiseinfo\Audios;

class AudiosTrimAudioBase64Test extends TestCase
{
    private Audios $audios;

    protected function setUp(): void
    {
        parent::setUp();
        $this->audios = new Audios();
    }

    /**
     * 测试短音频文件（时长小于指定长度）
     */
    public function testTrimAudioBase64WithShortAudio()
    {
        // 指定本地短音频文件的路径
        $shortFilePath = __DIR__ . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'short.mp3';

        // 调用 trimAudioBase64 方法
        $base64Audio = $this->audios->trimAudioBase64($shortFilePath, 30);

        // 断言返回的 Base64 编码是否有效
        $this->assertNotEmpty(base64_decode(substr($base64Audio, strpos($base64Audio, ',') + 1)));
    }

    /**
     * 测试长音频文件（时长大于指定长度）
     */
    public function testTrimAudioBase64WithLongAudio()
    {
        // 指定本地长音频文件的路径
        $longFilePath = __DIR__ . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'sample.mp3';

        // 调用 trimAudioBase64 方法
        $base64Audio = $this->audios->trimAudioBase64($longFilePath, 10);

        // 验证音频时长是否符合预期
        $trimmedContent = base64_decode($base64Audio);
        $tempFile = tempnam(sys_get_temp_dir(), 'trimmed_audio');
        file_put_contents($tempFile, $trimmedContent);
        $trimmedDuration = $this->audios->getAudioDuration($tempFile);
        unlink($tempFile);

        // 断言音频时长是否接近指定的截取长度（允许一定误差）
        $this->assertGreaterThanOrEqual(9, $trimmedDuration);
        $this->assertLessThanOrEqual(11, $trimmedDuration);
    }

    /**
     * 测试无效的 URL 或文件路径
     */
    public function testTrimAudioBase64WithInvalidPath()
    {
        // 无效的文件路径
        $invalidFilePath = __DIR__ . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'nonexistent.mp3';

        // 断言调用 trimAudioBase64 方法时会抛出异常
        $this->expectException(\RuntimeException::class);
        $this->audios->trimAudioBase64($invalidFilePath, 10);
    }

    /**
     * 测试从远程 URL 获取音频文件并进行处理
     *
     * 注意：这个测试需要网络连接，并且依赖于外部资源。
     * 如果你不希望在每次测试中都访问网络，可以考虑跳过此测试或使用本地文件进行测试。
     */
    public function testTrimAudioBase64FromRemoteUrl()
    {
        // 指定远程音频文件的 URL
        $remoteUrl = 'https://dssb.oss-cn-beijing.aliyuncs.com/origin.mp3';

        // 调用 trimAudioBase64 方法
        $base64Audio = $this->audios->trimAudioBase64($remoteUrl, 10);

        // 验证音频时长是否符合预期
        $trimmedContent = base64_decode($base64Audio);
        $tempFile = tempnam(sys_get_temp_dir(), 'trimmed_audio');
        file_put_contents($tempFile, $trimmedContent);
        $trimmedDuration = $this->audios->getAudioDuration($tempFile);
        unlink($tempFile);

        // 断言音频时长是否接近指定的截取长度（允许一定误差）
        $this->assertGreaterThanOrEqual(9, $trimmedDuration);
        $this->assertLessThanOrEqual(11, $trimmedDuration);
    }
}