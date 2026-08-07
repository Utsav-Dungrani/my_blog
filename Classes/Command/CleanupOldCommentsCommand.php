<?php
declare(strict_types=1);

namespace NitsanAi\MyBlog\Command;

use NitsanAi\MyBlog\Domain\Repository\CommentRepository;
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
    public function __construct(
        private readonly CommentRepository $commentRepository
    ) {
        parent::__construct();
    }

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

        $deletedCount = $this->commentRepository->deleteUnapprovedOlderThan($days);

        $io->success(sprintf('Successfully deleted %d unapproved comments older than %d days.', $deletedCount, $days));

        return Command::SUCCESS;
    }
}
