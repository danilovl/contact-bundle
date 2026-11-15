<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\Command;

use Danilovl\ContactBundle\Service\ContactImporter;
use Symfony\Component\Console\Attribute\{
    Argument,
    AsCommand,
    Option
};
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'danilovl:contact:import', description: 'Import contacts from a vCard (.vcf) file.')]
readonly class ContactImportCommand
{
    public function __construct(private ContactImporter $contactImporter) {}

    public function __invoke(
        SymfonyStyle $io,
        #[Argument(description: 'Path to .vcf file')]
        string $file,
        #[Option(description: 'Owner identifier (username) of imported contacts')]
        string $owner = 'default'
    ): int {
        $isReadable = is_file($file) && is_readable($file);
        if (!$isReadable) {
            $message = sprintf('File "%s" is not readable.', $file);
            $io->error($message);

            return Command::FAILURE;
        }

        $content = file_get_contents($file);
        if ($content === false) {
            $io->error('File cannot be read.');

            return Command::FAILURE;
        }

        $result = $this->contactImporter->import($content, $owner);
        $message = sprintf(
            'Created: %d, updated: %d, skipped duplicates: %d, invalid: %d.',
            $result['created'],
            $result['updated'],
            $result['skipped'],
            $result['invalid']
        );
        $io->success($message);

        return Command::SUCCESS;
    }
}
