<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{
    /**
     * @inheritdoc
     */
    public function registerBundles()
    {
        $bundles = array(
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            new JMS\SerializerBundle\JMSSerializerBundle(),
            new FOS\RestBundle\FOSRestBundle(),
            new FOS\HttpCacheBundle\FOSHttpCacheBundle(),
            new Nelmio\ApiDocBundle\NelmioApiDocBundle(),
            new AppBundle\AppBundle()
        );

        if (in_array($this->getEnvironment(), array('dev', 'test'), true)) {
            $bundles[] = new Symfony\Bundle\DebugBundle\DebugBundle();
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
            $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
        }

        return $bundles;
    }

    /**
     * @inheritdoc
     */
    public function getCacheDir()
    {
        return $this->getVarBaseDir() . '/var/cache/' . $this->getEnvironment();
    }

    /**
     * @inheritdoc
     */
    public function getLogDir()
    {
        return $this->getVarBaseDir() . '/var/logs';
    }

    /**
     * @inheritdoc
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load($this->getProjectDir() . '/app/config/config_' . $this->getEnvironment() . '.yml');
    }

    /**
     * @return string
     */
    private function getVarBaseDir(): string
    {
        // for VM with shared files to avoid permission issues (env var must be defined both for CLI and WEB)
        return $_SERVER['BATTLESHIPS_VAR_DIR'] ?? $this->getProjectDir();
    }
}
