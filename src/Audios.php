<?php

namespace Raiseinfo;

class Audios
{
    public function help()
    {
        return "this is a helper!";
    }

    /**
     * 删除两端静音部分，并截取指定长度的音频，返回Base64编码
     *
     * @param string $url 音频文件的URL或本地路径
     * @param int $duration 截取的音频长度（秒）
     * @return string 返回Base64编码的音频数据
     */
    public function trimAudioBase64(string $url, int $duration = 10): string
    {
        // 获取音频内容
        $content = $this->getFileContent($url);

        // 获取音频时长
        $audioDuration = $this->getAudioDuration($url);

        if ($audioDuration < $duration) {
            // 如果音频时长小于指定长度，直接返回原始音频的Base64编码
            return base64_encode($content);
        }

        try {
            // 创建临时文件用于处理音频
            $tempLocal = tempnam(sys_get_temp_dir(), 'audio_local');
            $tempOutput = tempnam(sys_get_temp_dir(), 'audio_output');

            if ($tempLocal === false || $tempOutput === false) {
                throw new \RuntimeException('Failed to create temporary files.');
            }

            // 将音频内容写入临时文件
            if (file_put_contents($tempLocal, $content) === false) {
                throw new \RuntimeException('Failed to write temporary file.');
            }

            // 构建 ffmpeg 命令并防止命令注入
            $cmd = sprintf(
                'ffmpeg -y -i %s -af "silenceremove=start_periods=1:start_duration=1:start_threshold=-50dB:detection=peak,areverse,silenceremove=start_periods=1:start_duration=1:start_threshold=-50dB:detection=peak,areverse" -ar 16000 -b:a 64k -t %d -f mp3 %s',
                escapeshellarg($tempLocal),
                $duration,
                escapeshellarg($tempOutput)
            );

            echo "命令行为：\n";
            echo $cmd . "\n";

            // 执行命令
            exec($cmd, $output, $returnVar);

            // 检查命令是否成功执行
            if ($returnVar !== 0 || !file_exists($tempOutput)) {
                throw new \RuntimeException("FFmpeg command failed with return code: $returnVar. Output: " . implode("\n", $output));
            }

            // 读取处理后的音频文件并进行Base64编码
            $trimmedContent = file_get_contents($tempOutput);
            if ($trimmedContent === false) {
                throw new \RuntimeException('Failed to read processed audio file.');
            }

            // 返回Base64编码的音频数据
            $base64Audio = base64_encode($trimmedContent);

            return $base64Audio;
        } catch (\RuntimeException $e) {
            // 记录错误日志
            error_log("Error trimming audio: " . $e->getMessage());

            // 抛出异常
            throw $e;
        } finally {
            // 删除临时文件
            if (isset($tempLocal) && file_exists($tempLocal)) {
                unlink($tempLocal);
            }
            if (isset($tempOutput) && file_exists($tempOutput)) {
                unlink($tempOutput);
            }
        }
    }



    /****************************************获得音频文件的长度************************************************/
    /**
     * 获得音频文件的长度
     * @param $url
     * @return float|int
     */
    function getAudioDuration($url)
    {
        try {
            // 如果是远程URL，先下载文件到临时目录
            if (filter_var($url, FILTER_VALIDATE_URL)) {
                $content = self::getFileContent($url);
                $tempFile = tempnam(sys_get_temp_dir(), 'audio_duration');
                if ($tempFile === false) {
                    throw new \RuntimeException('Failed to create temporary file.');
                }
                if (file_put_contents($tempFile, $content) === false) {
                    throw new \RuntimeException('Failed to write temporary file.');
                }
            } else {
                // 如果是本地文件路径，直接使用该路径
                $tempFile = $url;
            }

            // 构建命令行并防止命令注入
            $cmd = sprintf('ffprobe -i %s -show_entries format=duration -v quiet -of csv="p=0"', escapeshellarg($tempFile));

            // 执行命令并获取输出
            $output = shell_exec($cmd);

            // 检查命令是否成功执行
            if ($output !== null && trim($output) !== '') {
                $duration = (float)trim($output);
                $result = round($duration);
                return $result;
            } else {
                throw new \RuntimeException('Failed to retrieve duration from ffprobe.');
            }
        } catch (\Exception $e) {
            // 记录错误日志
            error_log("Error getting audio duration: " . $e->getMessage());
            return 0;
        } finally {
            // 删除临时文件（如果是从URL下载的）
            if (isset($tempFile) && filter_var($url, FILTER_VALIDATE_URL)) {
                unlink($tempFile);
            }
        }
    }


    /**
     * 读取文件的内容
     * @param string $url 文件的URL或本地路径
     * @return string 文件内容
     * @throws \RuntimeException 如果无法加载文件
     */
    private function getFileContent(string $url): string
    {
        // 尝试读取文件内容
        $content = @file_get_contents($url);

        // 检查是否读取成功
        if ($content === false) {
            // 获取错误信息
            $error = error_get_last();
            throw new \RuntimeException('Failed to load file: ' . ($error['message'] ?? 'Unknown error'));
        }

        return $content;
    }

}