<?php

declare(strict_types=1);

namespace Symplify\EasyCI\Tests\Analyzer\MissingClassesLatteAnalyzer;

use Iterator;
use Symplify\EasyCI\HttpKernel\EasyCIKernel;
use Symplify\EasyCI\Latte\Analyzer\MissingClassesLatteAnalyzer;
use Symplify\PackageBuilder\Testing\AbstractKernelTestCase;
use Symplify\SmartFileSystem\SmartFileInfo;

final class MissingClassesLatteAnalyzerTest extends AbstractKernelTestCase
{
    /**
     * @var MissingClassesLatteAnalyzer
     */
    private $missingClassesLatteAnalyzer;

    protected function setUp(): void
    {
        $this->bootKernel(EasyCIKernel::class);
        $this->missingClassesLatteAnalyzer = $this->getService(MissingClassesLatteAnalyzer::class);
    }

    /**
     * @dataProvider provideData()
     */
    public function test(SmartFileInfo $inputFileInfo, int $expectedErrorCount): void
    {
        $result = $this->missingClassesLatteAnalyzer->analyze([$inputFileInfo]);
        $this->assertCount($expectedErrorCount, $result);
    }

    /**
     * @return Iterator<int[]|SmartFileInfo[]>
     */
    public function provideData(): Iterator
    {
        yield [new SmartFileInfo(__DIR__ . '/Fixture/existing_classes.latte'), 0];
        yield [new SmartFileInfo(__DIR__ . '/Fixture/existing_var_type.latte'), 0];
        yield [new SmartFileInfo(__DIR__ . '/Fixture/existing_class_instanceof.latte'), 0];
        yield [new SmartFileInfo(__DIR__ . '/Fixture/non_classes.latte'), 0];

        yield [new SmartFileInfo(__DIR__ . '/Fixture/missing_classes.latte'), 2];
        yield [new SmartFileInfo(__DIR__ . '/Fixture/missing_class_instanceof.latte'), 2];
        yield [new SmartFileInfo(__DIR__ . '/Fixture/missing_var_type.latte'), 2];
        yield [new SmartFileInfo(__DIR__ . '/Fixture/missing_input.latte'), 2];

        yield [new SmartFileInfo(__DIR__ . '/Fixture/skip_scalar_vartype.latte'), 0];
    }
}
