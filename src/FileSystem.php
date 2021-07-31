<?php

namespace Zcgo\FileSystem;

use Zcgo\Traits\Singleton;

class FileSystem
{
    use Singleton;

    /**
     * @param string $rootDir
     * @param string $fileRegx
     * @return array
     */
    public function listFilesFromDirectory(string $rootDir, string $fileRegx): array
    {
        if (!$dir = @dir($rootDir)) return [];
        $fileList = [];
        while ($file = $dir->read()) {
            if (preg_match($fileRegx, $file) > 0) {
                $fileList[] = basename($rootDir . '/' . $file);
            }
        }
        $dir->close();
        return $fileList;
    }
}