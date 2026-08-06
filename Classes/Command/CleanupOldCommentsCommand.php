<?php
declare(strict_types=1);

namespace NitsanAi\MyBlog\Command;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'myblog:cleanupcomments',
    description: 'Deletes unapproved comments older than X days.',
)]
class CleanupOldCommentsCommand extends Command
{
    protected function configure(): void
    {
        $this->addArgument(
            'days',
            InputArgument::REQUIRED,
            'Number of days. Unapproved comments older than this will be permanently deleted.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $days = (int)$input->getArgument('days');

        if ($days <= 0) {
            $io->error('The number of days must be greater than 0.');
            return Command::FAILURE;
        }

        $thresholdTimestamp = strtotime('-' . $days . ' days');
        if ($thresholdTimestamp === false) {
            $io->error('Failed to calculate the date threshold.');
            return Command::FAILURE;
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_myblog_domain_model_comment');

        $deletedCount = $queryBuilder
            ->delete('tx_myblog_domain_model_comment')
            ->where(
                $queryBuilder->expr()->eq('approved', $queryBuilder->createNamedParameter(0, \Doctrine\DBAL\ParameterType::INTEGER)),
                $queryBuilder->expr()->lt('crdate', $queryBuilder->createNamedParameter($thresholdTimestamp, \Doctrine\DBAL\ParameterType::INTEGER))
            )
            ->executeStatement();

        $io->success(sprintf('Successfully deleted %d unapproved comments older than %d days.', $deletedCount, $days));

        return Command::SUCCESS;
    }
}
