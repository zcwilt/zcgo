<?php
/**
 *
 * @copyright Copyright 2003-2020 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id:  $
 */

namespace Zcgo\Lib;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Zcgo\Exceptions\InputOptionsValidationException;
use Zcgo\FileSystem\FileSystem;

class LangDefinesBaseCommand extends Command
{
    /**
     * @param InputInterface $input
     * @throws InputOptionsValidationException
     */
    protected function validateInputOptions(InputInterface $input): void
    {
        $file = $input->getOption('file');
        $dir = $input->getOption('dir');
        $config = $input->getOption('config');
        if (!isset($file) && !isset($dir) && !isset($config)) {
            throw new InputOptionsValidationException('Seems you didn\'t pass any options');
        }
        if ($config && !$this->fs->fileExists($config)) {
            throw new InputOptionsValidationException('Invalid file for config option');
        };
        if ($file && !$this->fs->fileExists($file)) {
            throw new InputOptionsValidationException('Invalid file for file option:' . $file);
        };
        if ($dir && !$this->fs->directoryExists($dir)) {
            throw new InputOptionsValidationException('Invalid directory for dir option: ' . $directory);
        }
    }

}
