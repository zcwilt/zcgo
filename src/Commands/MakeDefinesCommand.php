<?php
/**
 *
 * @copyright Copyright 2003-2020 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id:  $
 */

namespace Zcgo\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zcgo\Exceptions\InputOptionsValidationException;
use Zcgo\FileSystem\FileSystem;

class MakeDefinesCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'make:defines';

    /**
     *
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Creates an array based language define file.')
            ->setHelp(
                'This command allows you to create an array based language file based on a legacy define file.' . "\n" .
                ' You can use the options to either select a file or directory to convert, or use a config file for more control. ')
            ->setDefinition(
                new InputDefinition(
                    [
                        new InputOption('file', 'f', InputOption::VALUE_REQUIRED),
                        new InputOption('dir', 'd', InputOption::VALUE_REQUIRED),
                        new InputOption('config', 'c', InputOption::VALUE_REQUIRED),
                    ])
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->fs = FileSystem::getInstance();
        $this->input = $input;
        $this->output = $output;
        try {
            $this->validateInputOptions($input);
        } catch (InputOptionsValidationException $e) {
            $output->writeln($e->getMessage());
            exit(1);
        }
        try {
            $this->processFilesFromInput($input);
        } catch (InputOptionsValidationException $e) {
            $output->writeln($e->getMessage());
            exit(1);
        }
        exit(0);
    }

    /**
     * @param InputInterface $input
     * @throws InputOptionsValidationException
     */
    protected function processFilesFromInput(InputInterface $input): void
    {
        $file = $input->getOption('file');
        $dir = $input->getOption('dir');
        $config = $input->getOption('config');
        try {
            if (isset($file)) {
                $this->processSingleFile($file);
            }
            if (isset($dir)) {
                $this->processDirectory($dir);
            }
            if (isset($config)) {
                $this->processConfigFile($config);
            }
        } catch (InputOptionsValidationException $e) {
            throw new InputOptionsValidationException($e->getMessage());
        }
    }

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
        $this->validateConfigFile($config);
        $this->validateFile($file);
        $this->validateDirectory($dir);
    }

    /**
     * @param string $configFile
     * @throws InputOptionsValidationException
     */
    protected function validateConfigFile(string $configFile): void
    {
        if (!isset($configFile)) {
            return;
        }
        if (!is_file($configFile)) {
            throw new InputOptionsValidationException('Invalid file for config option');
        }
    }

    /**
     * @param string $file
     * @throws InputOptionsValidationException
     */
    protected function validateFile(string $file): void
    {
        if (!isset($file)) {
            return;
        }
        if (!is_file($file)) {
            throw new InputOptionsValidationException('Invalid file for file option:' . $file);
        }
    }

    /**
     * @param string $directory
     * @throws InputOptionsValidationException
     */
    protected function validateDirectory(string $directory): void
    {
        if (!isset($directory)) {
            return;
        }
        if (!is_dir($directory)) {
            throw new InputOptionsValidationException('Invalid directory for dir option: ' . $directory);
        }
    }

    /**
     * @param string $fileToConvert
     * @throws InputOptionsValidationException
     */
    protected function processSingleFile(string $fileToConvert): void
    {
        $pathInfo = pathinfo($fileToConvert);
        $originalFile = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.' . $pathInfo['extension'];
        $destinationFile = $pathInfo['dirname'] . '/' . 'lang.' . $pathInfo['filename'] . '.' . $pathInfo['extension'];
        $tokenized = $this->tokenizeData($originalFile);
        $parsed = $this->parseTokenizedData($tokenized);
        $outputData = $this->buildOutputData($parsed);
        if (count($outputData) == 0) {
            throw new InputOptionsValidationException($fileToConvert . ' does not appear to be a define file');
        }
        $this->writeOutputFile($destinationFile, $outputData);
    }

    /**
     * @param string $directory
     */
    protected function processDirectory(string $directory): void
    {
        $this->output->writeln('processing directory ' . $directory);
        // @todo update regex to not look at lang.* files
        $fileList = $this->fs->listFilesFromDirectory($directory, '~^[^\._].*\.php$~i');
        foreach ($fileList as $file) {
            try {
                $this->processSingleFile($directory . $file);
                $this->doVerboseOutput('processing file ' . $directory . $file);
            } catch (InputOptionsValidationException $e) {
                $this->doVerboseOutput('Invalid file :' . $directory . $file);
                // @todo only verbose output here
            }
        }
    }

    /**
     * @param string $configFile
     * @throws InputOptionsValidationException
     */
    protected function processConfigFile(string $configFile): void
    {
        $configDetails = require $configFile;
        if (!isset($configDetails['files']) && !isset($configDetails['directories'])) {
            throw new InputOptionsValidationException('config file does not appear to be valid');
        }
        $this->processConfigFileFiles($configDetails);
        $this->processConfigFileDirectories($configDetails);
    }

    /**
     * @param array $configDetails
     * @throws InputOptionsValidationException
     */
    protected function processConfigFileFiles(array $configDetails): void
    {
        if (!isset($configDetails['files'])) {
            return;
        }
        foreach ($configDetails['files'] as $file) {
            $this->validateFile($file);
            $this->processSingleFile($file);
        }
    }

    /**
     * @param array $configDetails
     * @throws InputOptionsValidationException
     */
    protected function processConfigFileDirectories(array $configDetails): void
    {
         if (!isset($configDetails['directories'])) {
            return;
        }
        foreach ($configDetails['directories'] as $directory) {
            $this->validateDirectory($directory);
            $this->processDirectory($directory);
        }
    }

    /**
     * @param string $originalFile
     * @return array
     */
    protected function tokenizeData(string $originalFile): array
    {
        $data = file_get_contents($originalFile);
        $tokenizedData = token_get_all($data);
        return $tokenizedData;
    }

    /**
     * @param array $tokenizedData
     * @return array
     */
    protected function parseTokenizedData(array $tokenizedData): array
    {
        $currentLineNumber = -1;
        $currentLine = [];
        $builtLines = [];

        foreach ($tokenizedData as $token) {
            if ($this->canSkipCurrentToken($token)) continue;
            $lineNumber = $token[2];
            if ($currentLineNumber != $lineNumber) {
                $currentLineNumber = $lineNumber;
                $builtLines[] = $currentLine;
                $currentLine = [];
            }
            $currentLine[] = $token;
        }
        $builtLines[] = $currentLine;
        return $builtLines;
    }

    /**
     * @param array $token
     * @return bool
     */
    protected function canSkipCurrentToken(array $token): bool
    {
        if (!is_array($token)) return true;
        if (count($token) < 3) return true;
        return false;
    }

    /**
     * @param array $builtLines
     * @return array
     */
    public function buildOutputData(array $builtLines): array
    {
        $outputData = [];
        foreach ($builtLines as $tokens) {
            $pointer = $this->skipLeadingWhiteSpace($tokens);
            if ($pointer == -1) continue;
            if (!isset($tokens[$pointer])) continue;
            if ($tokens[$pointer][1] != 'define') continue;
            $pointer++;
            $pointer = $this->skipLeadingWhiteSpace($tokens, $pointer);
            $defineKey = $tokens[$pointer][1];
            $pointer++;
            $pointer = $this->skipLeadingWhiteSpace($tokens, $pointer);
            $defineValue = $this->buildDefineValue($tokens, $pointer);
            $outputData[] = [$defineKey, $defineValue];
        }
        return $outputData;
    }

    /**
     * @param array $tokens
     * @param int $start
     * @return int
     */
    protected function skipLeadingWhiteSpace(array $tokens, int $start = 0): int
    {
        if (!isset($tokens[$start])) return false;
        if (!isset($tokens[$start][0])) return false;
        foreach ($tokens as $tokenPointer => $token) {
            $pointer = -1;
            if ($tokenPointer < $start) continue;
            if ($token[0] === T_WHITESPACE) continue;
            $pointer = $tokenPointer;
            break;
        }
        return $pointer;
    }

    /**
     * @param array $tokens
     * @param int $pointer
     * @return string
     */
    protected function buildDefineValue(array $tokens, int $pointer): string
    {
        $completed = false;
        $defineValue = '';
        $allowedTokens = [T_STRING, T_CONSTANT_ENCAPSED_STRING, T_LNUMBER];
        while ($completed == false) {
            if (in_array($tokens[$pointer][0], $allowedTokens)) {
                $defineValue .= $tokens[$pointer][1] . ' . ';
            }
            $pointer++;
            if ($pointer >= count($tokens)) {
                $defineValue = rtrim($defineValue, ' . ');
                $completed = true;
            }
        }
        return $defineValue;
    }

    /**
     * @param string $destinationFile
     * @param array $outputData
     */
    public function writeOutputFile(string $destinationFile, array $outputData): void
    {
        $this->doVerboseOutput('Writing Destination ' . $destinationFile);
        $fp = fopen($destinationFile, 'w');
        fwrite($fp, '<?php' . "\n");
        fwrite($fp, '/**' . "\n");
        fwrite($fp, ' * @copyright Copyright 2003-' . date('Y') . ' Zen Cart Development Team' . "\n");
        fwrite($fp, ' * @copyright Portions Copyright 2003 osCommerce' . "\n");
        fwrite($fp, ' * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0' . "\n");
        fwrite($fp, ' * @version $Id:' . "\n");
        fwrite($fp, '*/' . "\n\n");
        fwrite($fp, '$define = [' . "\n");
        foreach ($outputData as $definevalue) {
            fwrite($fp, '    ' . $definevalue[0] . " => ");
            fwrite($fp, $definevalue[1]);
            fwrite($fp, ",\n");
        }
        fwrite($fp, '];' . "\n\n");
        fwrite($fp, 'return $define;' . "\n");
        fclose($fp);
    }

    /**
     * @param OutputInterface $output
     */
    protected function doVerboseOutput(OutputInterface $output): void
    {
        if (!$this->output->isVerbose()) {
            return;
        }
        $this->output->writeln($output);
    }
}
