<?php
/**
 *
 * @copyright Copyright 2003-2020 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id:  $
 */

namespace Zcgo\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class DumpAdminLogsCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'clear:adminlogs';

    /**
     *
     */
    protected function configure()
    {
        $this
            ->setDescription('Dump(empty) the Admin Logs table')
            ->setHelp(
                'This will empty the contents of the admin logs table.' . "\n" .
                '')
            ->setDefinition(
                new InputDefinition(
                    [
                        new InputOption('backup', 'b', InputOption::VALUE_OPTIONAL, 'Output contents of log table to a file'),
                    ])
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $dbh = new \PDO(DB_TYPE . ':host=' . DB_SERVER . ';dbname=' . DB_DATABASE, DB_SERVER_USERNAME, DB_SERVER_PASSWORD);
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
        try {
            $sth = $dbh->prepare('SELECT count(*) as count FROM ' . DB_PREFIX . 'admin_activity_log');
            $sth->execute();
            $result = $sth->fetch();
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
        if ((int)$result['count'] === 0) {
            $output->writeln('<comment>There are no entries in the Activity Log</comment>');
            return Command::SUCCESS;
        }
        $output->writeln('There are currently ' . $result['count'] . ' entries in Admin Activity Log');
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('Are you sure you want to delete all admin log data(y/n)?', false);
        if (!$helper->ask($input, $output, $question)) {
            return Command::SUCCESS;
        }
        try {
            $sth = $dbh->prepare('DELETE FROM ' . DB_PREFIX . 'admin_activity_log');
            $sth->execute();
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
        $output->writeln('<info>All Entries removed</info>');
        return Command::SUCCESS;
    }
}
