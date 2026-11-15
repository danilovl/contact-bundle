<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\Command;

use Danilovl\ContactBundle\Service\ContactOwnerManager;
use Symfony\Component\Console\Attribute\{
    Argument,
    AsCommand
};
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'danilovl:contact:purge-owner', description: 'Delete all contacts and tags of an owner, e.g. after deleting a user.')]
readonly class ContactPurgeOwnerCommand
{
    public function __construct(private ContactOwnerManager $ownerManager) {}

    public function __invoke(
        SymfonyStyle $io,
        #[Argument(description: 'Owner identifier')]
        string $owner
    ): int {
        $question = sprintf('Delete all contacts of "%s"?', $owner);
        $isConfirmed = $io->confirm($question, false);

        if (!$isConfirmed) {
            return Command::SUCCESS;
        }

        $count = $this->ownerManager->purge($owner);
        $message = sprintf('Contacts deleted: %d.', $count);
        $io->success($message);

        return Command::SUCCESS;
    }
}
