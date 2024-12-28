<?php

namespace Raiseinfo\Tools;


class Tools
{
    public static function help(): string
    {
        return "this is tools help doc";
    }

    /**
     * 生成树形下拉框的选项数据
     * @param array $data 平面结构数据
     * @param int $root 根节点ID
     * @param string $primaryKey 主键键名
     * @param string $foreignKey 外键键名
     * @param string $labelKey 标签键名
     * @param string $valueKey 值的键名
     * @param string $childrenKey 子节点的键名
     * @return array
     */
    public static function getTreeSelectOptions(
        array  $data,
        int    $root = 0,
        string $primaryKey = 'id',
        string $foreignKey = 'pid',
        string $labelKey = 'label',
        string $valueKey = 'value',
        string $childrenKey = 'children'
    ): array
    {
        $tree = [];
        if (empty($data) || !is_array($data)) {
            return $tree;
        }

        // Create a lookup table for faster access to nodes by their primary key.
        $lookup = [];
        foreach ($data as $node) {
            $lookup[$node[$primaryKey]] = $node;
        }

        // Iterate over the data to build the tree.
        foreach ($data as $node) {
            if ($node[$foreignKey] == $root) {
                // Recursively find children for the current node.
                $children = self::getTreeSelectOptions($data, $node[$primaryKey], $primaryKey, $foreignKey, $labelKey, $valueKey, $childrenKey);
                if (!empty($children)) {
                    $tree[] = [
                        'label' => $node[$labelKey],
                        'value' => $node[$valueKey],
                        $childrenKey => $children,
                    ];
                } else {
                    $tree[] = [
                        'label' => $node[$labelKey],
                        'value' => $node[$valueKey],
                    ];
                }
            }
        }

        return $tree;
    }


    /**
     * 收集树结构所有的叶子节点的ID
     * @param array $tree 需要查找的树
     * @param array $leafNodeIds 变量的引用用于存储找到的ID
     * @param string $primaryKey 主键键名
     * @param string $childrenKey 子节点键名
     * @return mixed
     */
    public static function findLeafNodeIds(
        array  $tree,
        array  &$leafNodeIds,
        string $primaryKey = 'id',
        string $childrenKey = 'children'
    ): mixed
    {
        foreach ($tree as $node) {
            // 如果没有 'children' 键或 'children' 数组为空，则为叶子节点
            if (!isset($node[$childrenKey]) || empty($node[$childrenKey])) {
                // 将叶子节点的ID添加到结果数组中
                $leafNodeIds[] = $node[$primaryKey];
            } else {
                // 递归查找子节点中的叶子节点
                self::findLeafNodeIds($node[$childrenKey], $leafNodeIds, $primaryKey, $childrenKey);
            }
        }
        return $leafNodeIds;
    }

    /**
     * 从树中找到指定ID的节点的完整信息
     * @param array $tree 需要查找的树
     * @param int $targetId 查找的节点ID
     * @param string $primaryKey 主键名
     * @param string $childrenKey 子节点的键名
     * @param array $foundNode 此节点的子树
     * @return array|mixed
     */
    public static function findNodeFormTree(
        array  $tree,
        int    $targetId,
        string $primaryKey = 'id',
        string $childrenKey = 'children',
        array  &$foundNode
    ): mixed
    {
        foreach ($tree as $node) {
            if ($node[$primaryKey] == $targetId) {
                // Return the entire node including its children.
                $foundNode = $node;
                return isset($node[$childrenKey]) ? $node[$childrenKey] : [];
            }
            if (isset($node[$childrenKey])) {
                // Recursively search in the subtree.
                $result = self::findNodeFormTree($node[$childrenKey], $targetId, $primaryKey, $childrenKey, $foundNode);
                if (!empty($result)) {
                    return $result;
                }
            }
        }
        return []; // Return an empty array if no match is found.
    }


    /**
     * 将平面结构的数据转换为树结构
     * @param array $nodes 平面结构数据
     * @param int $root 根节点ID
     * @param string $primaryKey 主键键名
     * @param string $foreignKey 外键键名
     * @param string $childrenKey 子节点键名
     * @return array
     */
    public static function buildTree(
        array  $nodes,
        int    $root = 0,
        string $primaryKey = 'id',
        string $foreignKey = 'pid',
        string $childrenKey = 'children'
    ): array
    {
        $tree = [];
        foreach ($nodes as $node) {
            if ($node[$foreignKey] === $root) {
                // Recursively find children for the current node.
                $children = self::buildTree($nodes, $node[$primaryKey], $primaryKey, $foreignKey, $childrenKey);
                if ($children) {
                    // Add the children to the current node if any exist.
                    $node[$childrenKey] = $children;
                }
                $tree[] = $node;
            }
        }
        return $tree;
    }

    /**
     * 获得音频文件的长度
     * @param $url
     * @return float|int
     */
    public static function getAudioDuration($url)
    {
        $content = self::getFileContent($url);
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
    public static function trimAudioBase64(string $url, int $duration = 10): string
    {
        $base64Audio = '';
        $content = self::getFileContent($url);
        if (self::getAudioDuration($url) < $duration) {
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
    private static function getFileContent(string $url): string
    {
        $content = file_get_contents($url);
        if ($content === false) {
            throw new \RuntimeException('Failed to load file');
        }
        return $content;
    }

}