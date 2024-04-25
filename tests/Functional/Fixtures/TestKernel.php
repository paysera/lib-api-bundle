<?php
declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Functional\Fixtures;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Paysera\Bundle\ApiBundle\Tests\Functional\Fixtures\FixtureTestBundle\Service\TestHelper;
use Paysera\Bundle\NormalizationBundle\PayseraNormalizationBundle;
use Paysera\Bundle\ApiBundle\PayseraApiBundle;
use Paysera\Bundle\ApiBundle\Tests\Functional\Fixtures\FixtureTestBundle\PayseraFixtureTestBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class TestKernel extends Kernel
{
    private $configFile;
    private $commonFile;

    public function __construct($testCase, $commonFile = 'common.yml')
    {
        parent::__construct((string)crc32($testCase . $commonFile), true);
        $this->configFile = $testCase . '.yml';
        $this->commonFile = $commonFile;
    }

    public function registerBundles(): array
    {
        return [
            new FrameworkBundle(),
            new SecurityBundle(),
            new DoctrineBundle(),
            new PayseraNormalizationBundle(),
            new PayseraApiBundle(),
            new PayseraFixtureTestBundle(),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__ . '/config/services.yml');
        $loader->load(__DIR__ . '/config/' . $this->configFile);
        $loader->load(__DIR__ . '/config/' . $this->commonFile);

        if (TestHelper::phpAttributeSupportExists()) {
            $loader->load(__DIR__ . '/config/attributed_common.yml');
        }
    }
}
