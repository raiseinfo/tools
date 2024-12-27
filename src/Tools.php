<?php

namespace Raiseinfo\Tools;


class Tools
{
    public function help()
    {
        return "this is tools help doc";
    }


    /**
     * 获得音频文件的长度
     * @param $url
     * @return float|int
     */
    public function getAudioDuration($url)
    {
        $content = $this->getFileContent($url);
        $temp_file = tempnam(sys_get_temp_dir(), 'audio_duration');
        file_put_contents($temp_file, $content);
        // 构建命令行
        $cmd = 'ffprobe -i ' . $temp_file . ' -show_entries format=duration -v quiet -of csv="p=0"';
        exec($cmd, $output, $return_var);
        if ($return_var === 0 && count($output) > 0) {
            return round((float)$output[0]);
        } else {
            return 0;
        }
    }

    /**
     * 删除两端静音部分，并截取指定长度的音频，返回Base64编码
     * @param string $url
     * @param int $duration
     * @return string
     */
    public function trimAudioBase64(string $url, int $duration = 10): string
    {
        $base64Audio = '';
        $content = $this->getFileContent($url);
        if ($this->getAudioDuration($url) < $duration) {
            $base64Audio = base64_encode($content);
        } else {
            $temp_local = tempnam(sys_get_temp_dir(), 'audio_local');
            $temp_output = tempnam(sys_get_temp_dir(), 'audio_output');
            file_put_contents($temp_local, $content);
            // 组建命令行
            $cmd = "ffmpeg -i $temp_local -af ";
            $cmd .= ' "silenceremove=start_periods=1:start_duration=1:start_threshold=-50dB:detection=peak,areverse,silenceremove=start_periods=1:start_duration=1:start_threshold=-50dB:detection=peak,areverse"';
            $cmd .= " -ar 16000 -b:a 64k -t $duration ";
            $cmd .= $temp_output;
            // 执行命令
            exec($cmd, $output, $return_var);
            if ($return_var === 0 && file_exists($temp_output)) {
                $base64Audio = base64_encode(file_get_contents($temp_output));
            } else {
                throw new \RuntimeException('Failed to load file');
            }
        }
        return $base64Audio;
    }

    /**
     * 读取文件的内容
     * @param string $url
     * @return string
     */
    private function getFileContent(string $url): string
    {
        $content = file_get_contents($url);
        if ($content === false) {
            throw new \RuntimeException('Failed to load file');
        }
        return $content;
    }

}