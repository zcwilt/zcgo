<?php

namespace Zcgo\Lib;

use Zcgo\Exceptions\InputOptionsValidationException;
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

    /**
     * @param string $file
     * @throws InputOptionsValidationException
     */
    public function fileExists(string $file = null, $checkWriteable = false): bool
    {
        if (!isset($file)) {
            return false;
        }
        if (!file_exists($file)) {
            return false;
        }
        if ($checkWriteable && !is_writable($file)) {
            return false;
        }
        return true;
    }

    /**
     * @param string $directory
     * @throws InputOptionsValidationException
     */
    public function directoryExists(string $directory = null, $checkWriteable = false): bool
    {
        if (!isset($directory)) {
            return false;
        }
        if (!is_dir($directory)) {
            return false;
        }
        if ($checkWriteable && !is_writable($directory)) {
            return false;
        }
        return true;
    }
}
