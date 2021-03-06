<?php

declare(strict_types=1);

namespace Symplify\EasyCI\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symplify\EasyCI\ContentAnalyzer\NonExistingClassConstantExtractor;
use Symplify\EasyCI\ContentAnalyzer\NonExistingClassExtractor;
use Symplify\EasyCI\Reporter\NonExistingElementsReporter;
use Symplify\EasyCI\ValueObject\ConfigFileSuffixes;
use Symplify\PackageBuilder\Console\Command\AbstractSymplifyCommand;
use Symplify\PackageBuilder\Console\ShellCode;
use Symplify\PackageBuilder\ValueObject\Option;
use Symplify\SmartFileSystem\Finder\SmartFinder;

final class CheckConfigCommand extends AbstractSymplifyCommand
{
    /**
     * @var NonExistingClassExtractor
     */
    private $nonExistingClassExtractor;

    /**
     * @var NonExistingClassConstantExtractor
     */
    private $nonExistingClassConstantExtractor;

    /**
     * @var NonExistingElementsReporter
     */
    private $nonExistingElementsReporter;

    public function __construct(
        SmartFinder $smartFinder,
        NonExistingClassExtractor $nonExistingClassExtractor,
        NonExistingClassConstantExtractor $nonExistingClassConstantExtractor,
        NonExistingElementsReporter $nonExistingElementsReporter
    ) {
        $this->smartFinder = $smartFinder;
        $this->nonExistingClassExtractor = $nonExistingClassExtractor;
        $this->nonExistingClassConstantExtractor = $nonExistingClassConstantExtractor;

        parent::__construct();

        $this->nonExistingElementsReporter = $nonExistingElementsReporter;
    }

    protected function configure(): void
    {
        $this->setDescription('Check configs and template for existing classes and class constants');
        $this->addArgument(
            Option::SOURCES,
            InputArgument::REQUIRED | InputArgument::IS_ARRAY,
            'Path to directories or files to check'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string[] $sources */
        $sources = (array) $input->getArgument(Option::SOURCES);
        $fileInfos = $this->smartFinder->find($sources, ConfigFileSuffixes::provideRegex());

        $message = sprintf(
            'Checking %d files with "%s" suffixes',
            count($fileInfos),
            implode('", "', ConfigFileSuffixes::SUFFIXES)
        );
        $this->symfonyStyle->note($message);

        $nonExistingClassesByFile = $this->nonExistingClassExtractor->extractFromFileInfos($fileInfos);
        $nonExistingClassConstantsByFile = $this->nonExistingClassConstantExtractor->extractFromFileInfos($fileInfos);

        if ($nonExistingClassConstantsByFile === [] && $nonExistingClassesByFile === []) {
            $this->symfonyStyle->success('All classes and class constants exists');
            return ShellCode::SUCCESS;
        }

        $this->nonExistingElementsReporter->reportNonExistingElements(
            $nonExistingClassesByFile,
            $nonExistingClassConstantsByFile
        );

        return ShellCode::ERROR;
    }
}
