<?php

namespace Raiseinfo;


class Audios
{
    public function help()
    {
        return "this is a helper!";
    }

    /**
     * 获得音频文件的长度
     *
     * @param string $url 音频文件的URL或本地路径
     * @return float|int 音频时长（秒）
     */
    public function getAudioDuration(string $url)
    {
        try {
            // 如果是远程URL，先下载文件到临时目录
            if (filter_var($url, FILTER_VALIDATE_URL)) {
                $content = $this->getFileContent($url);
                $tempFile = tempnam(sys_get_temp_dir(), 'audio_duration_' . time());
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

            // 获取 FFprobe 路径
            $ffprobePath = $this->getExecutablePath('ffprobe');

            // 构建命令行并防止命令注入
            $cmd = sprintf(
                '%s -i %s -show_entries format=duration -v quiet -of csv="p=0"',
                escapeshellarg($ffprobePath),
                escapeshellarg($tempFile)
            );

            // 执行命令并获取输出
            $output = $this->executeCommand($cmd);

            // 检查命令是否成功执行
            if ($output !== null && trim($output[0]) !== '') {
                return round(floatval(trim($output[0])));
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
     * 删除两端静音部分，并截取指定长度的音频，返回Base64编码
     *
     * @param string $url 音频文件的URL或本地路径
     * @param int $duration 截取的音频长度（秒）
     * @return string 返回Base64编码的音频数据
     */
    public function trimAudioBase64(string $url, int $duration = 30): string
    {
        try {
            // 获取音频内容
            $content = $this->getFileContent($url);

            // 获取音频时长
            $audioDuration = $this->getAudioDuration($url);

            if ($audioDuration < $duration) {
                // 如果音频时长小于指定长度，直接返回原始音频的Base64编码
                return base64_encode($content);
            }

            // 创建临时文件用于处理音频
            $tempLocal = sys_get_temp_dir() . DIRECTORY_SEPARATOR . time() . '.mp3';
            $tempOutput = sys_get_temp_dir() . DIRECTORY_SEPARATOR . time() . '.m4a';

            if ($tempLocal === false || $tempOutput === false) {
                throw new \RuntimeException('Failed to create temporary files.');
            }

            // 将音频内容写入临时文件
            if (file_put_contents($tempLocal, $content) === false) {
                throw new \RuntimeException('Failed to write temporary file.');
            }

            // 获取 FFmpeg 路径
            $ffmpegPath = $this->getExecutablePath('ffmpeg');

            // 构建 ffmpeg 命令并防止命令注入
            $cmd = sprintf(
                '%s -i %s -af "silenceremove=start_periods=1:start_duration=1:start_threshold=-50dB:detection=peak,areverse,silenceremove=start_periods=1:start_duration=1:start_threshold=-50dB:detection=peak,areverse" -ar 16000 -b:a 64k -t %d %s',
                escapeshellarg($ffmpegPath),
                escapeshellarg($tempLocal),
                $duration,
                escapeshellarg($tempOutput)
            );

            // 执行命令
            $this->executeCommand($cmd);

            // 读取处理后的音频文件并进行Base64编码
            $trimmedContent = file_get_contents($tempOutput);
            if ($trimmedContent === false) {
                throw new \RuntimeException('Failed to read processed audio file.');
            }

            // 返回Base64编码的音频数据
            return base64_encode($trimmedContent);
        } catch (\RuntimeException $e) {
            // 记录错误日志
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

    /**
     * 检查音频文件是否有声音
     *
     * @param string $url 音频文件的URL或本地路径
     * @param float $silenceThreshold 静音阈值 (dB)
     * @param float $silenceDuration 静音持续时间 (秒)
     * @return float 静音占比
     */
    public function getSilenceRatio(
        string $url,
        float  $silenceThreshold = -50,
        float  $silenceDuration = 0.1
    ): float
    {
        try {
            // 下载并保存文件
            $filePath = tempnam(sys_get_temp_dir(), 'has_audio_' . time());
            file_put_contents($filePath, $this->getFileContent($url));

            // 获取 FFmpeg 和 FFprobe 的路径
            $ffmpegPath = $this->getExecutablePath('ffmpeg');
            $ffprobePath = $this->getExecutablePath('ffprobe');

            // 检查文件是否为空
            if (filesize($filePath) === 0) {
                throw new \RuntimeException("Downloaded file is empty or corrupted");
            }

            // 执行 FFmpeg 命令检测静音
            $output = $this->executeFfmpegSilenceDetect($ffmpegPath, $filePath, $silenceThreshold, $silenceDuration);

            // 获取文件的总时长
            $totalDuration = $this->getTotalDuration($ffprobePath, $filePath);

            // 如果文件总时长为 0，直接返回无声音（避免除以 0）
            if ($totalDuration <= 0) {
                return 1;
            }

            // 计算总的静音时间
            $totalSilenceDuration = $this->calculateSilenceDuration($output);

            // 计算静音比例
            $silenceRatio = $totalSilenceDuration / $totalDuration;

            return $silenceRatio;

        } catch (\Exception $e) {
            throw $e;
        } finally {
            // 确保临时文件在任何情况下都被删除
            if (isset($filePath) && file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }


    /**
     * 检查音频文件是否有声音
     *
     * @param string $url 音频文件的URL或本地路径
     * @param float $silenceThreshold 静音阈值 (dB)
     * @param float $silenceDuration 静音持续时间 (秒)
     * @param float $silenceRatioThreshold 静音比例阈值
     * @return bool 是否有声音
     */
    public function hasAudio(
        string $url,
        float  $silenceThreshold = -50,
        float  $silenceDuration = 0.1,
        float  $silenceRatioThreshold = 0.8): bool
    {
        try {
            // 下载并保存文件
            $filePath = tempnam(sys_get_temp_dir(), 'has_audio_' . time());
            file_put_contents($filePath, $this->getFileContent($url));

            // 获取 FFmpeg 和 FFprobe 的路径
            $ffmpegPath = $this->getExecutablePath('ffmpeg');
            $ffprobePath = $this->getExecutablePath('ffprobe');

            // 检查文件是否为空
            if (filesize($filePath) === 0) {
                throw new \RuntimeException("Downloaded file is empty or corrupted");
            }

            // 执行 FFmpeg 命令检测静音
            $output = $this->executeFfmpegSilenceDetect($ffmpegPath, $filePath, $silenceThreshold, $silenceDuration);

            // 获取文件的总时长
            $totalDuration = $this->getTotalDuration($ffprobePath, $filePath);

            // 如果文件总时长为 0，直接返回无声音（避免除以 0）
            if ($totalDuration <= 0) {
                return false;
            }

            // 计算总的静音时间
            $totalSilenceDuration = $this->calculateSilenceDuration($output);

            // 计算静音比例
            $silenceRatio = $totalSilenceDuration / $totalDuration;

            // 判断是否有声音
            $hasSound = $silenceRatio < $silenceRatioThreshold;

            return $hasSound;
        } catch (\Exception $e) {
            throw $e;
        } finally {
            // 确保临时文件在任何情况下都被删除
            if (isset($filePath) && file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }

    /**
     * 计算总的静音时间
     *
     * @param array $output FFmpeg 的输出
     * @return float 总的静音时间 (秒)
     */
    private function calculateSilenceDuration(array $output): float
    {
        $totalSilenceDuration = 0;
        foreach ($output as $line) {
            if (preg_match('/silence_duration: ([\d.]+)/', $line, $matches)) {
                $totalSilenceDuration += floatval($matches[1]);
            }
        }
        return $totalSilenceDuration;
    }

    /**
     * 获取文件的总时长
     *
     * @param string $ffprobePath FFprobe 的路径
     * @param string $filePath 输入文件路径
     * @return float 文件的总时长 (秒)
     */
    private function getTotalDuration(string $ffprobePath, string $filePath): float
    {
        $command = sprintf(
            '%s -i %s -show_entries format=duration -v quiet -of csv="p=0"',
            escapeshellarg($ffprobePath),
            escapeshellarg($filePath)
        );
        $output = $this->executeCommand($command);
        return floatval(trim(implode("\n", $output)));
    }


    /**
     * 执行 FFmpeg 命令检测静音
     *
     * @param string $ffmpegPath FFmpeg 的路径
     * @param string $filePath 输入文件路径
     * @param float $silenceThreshold 静音阈值 (dB)
     * @param float $silenceDuration 静音持续时间 (秒)
     * @return array FFmpeg 输出
     */
    private function executeFfmpegSilenceDetect(string $ffmpegPath, string $filePath, float $silenceThreshold, float $silenceDuration): array
    {
        $command = sprintf(
            '%s -i %s -af "silencedetect=noise=%sdB:d=%s" -f null - 2>&1',
            escapeshellarg($ffmpegPath),
            escapeshellarg($filePath),
            $silenceThreshold,
            $silenceDuration
        );
        return $this->executeCommand($command);
    }


    /**
     * 执行命令并捕获输出
     *
     * @param string $command 命令字符串
     * @return array 命令输出
     * @throws \RuntimeException 如果命令执行失败
     */
    private function executeCommand(string $command): array
    {
        exec($command, $output, $returnVar);
        if ($returnVar !== 0) {
            throw new \RuntimeException("Command failed with return code: $returnVar. Output: " . implode("\n", $output));
        }
        return $output;
    }

    /**
     * 读取文件的内容
     *
     * @param string $url 文件的URL或本地路径
     * @return string 文件内容
     * @throws \RuntimeException 如果无法加载文件
     */
    private function getFileContent(string $url): string
    {
        $content = @file_get_contents($url);
        if ($content === false) {
            $error = error_get_last();
            throw new \RuntimeException('Failed to load file: ' . ($error['message'] ?? 'Unknown error'));
        }
        return $content;
    }


    /**
     * 获取 FFmpeg 和 FFprobe 的执行路径
     *
     * @param string $tool 工具名称 ('ffmpeg' 或 'ffprobe')
     * @return string 工具的绝对路径
     */
    private function getExecutablePath(string $tool): string
    {
        $os = PHP_OS;
        if (stripos($os, 'WIN') !== false) {
            return "C:/ffmpeg/bin/{$tool}.exe";
        } elseif (stripos($os, 'LINUX') !== false) {
            return "/usr/local/bin/{$tool}";
        } else {
            throw new \RuntimeException("Unsupported operating system: " . $os);
        }
    }
}