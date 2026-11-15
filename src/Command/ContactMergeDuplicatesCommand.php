<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\Command;

use Danilovl\ContactBundle\Repository\ContactRepository;
use Danilovl\ContactBundle\Service\{
    ContactDuplicateFinder,
    ContactMerger,
    ContactNameFormatter
};
use Symfony\Component\Console\Attribute\{
    AsCommand,
    Option
};
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'danilovl:contact:merge-duplicates', description: 'Find contacts sharing an email or phone number and merge them.')]
readonly class ContactMergeDuplicatesCommand
{
    public function __construct(
        private ContactRepository $contactRepository,
        private ContactDuplicateFinder $duplicateFinder,
        private ContactMerger $contactMerger,
        private ContactNameFormatter $nameFormatter
    ) {}

    public function __invoke(
        SymfonyStyle $io,
        #[Option(description: 'Owner identifier (username) of contacts')]
        string $owner = 'default',
        #[Option(description: 'Only show duplicate groups without merging')]
        bool $dryRun = false
    ): int {
        $contacts = $this->contactRepository->findByOwnerWithValues($owner);
        $groups = $this->duplicateFinder->find($contacts);

        foreach ($groups as $group) {
            $names = [];
            foreach ($group as $contact) {
                $name = $this->nameFormatter->getDisplayName($contact);
                $names[] = sprintf('#%d %s', $contact->getId(), $name);
            }

            $io->writeln(implode(', ', $names));

            if ($dryRun) {
                continue;
            }

            $primary = $group[0];
            $others = array_slice($group, 1);
            $this->contactMerger->merge($primary, $others);
        }

        $message = sprintf('Duplicate groups: %d%s.', count($groups), $dryRun ? ' (dry run)' : ' merged');
        $io->success($message);

        return Command::SUCCESS;
    }
}
