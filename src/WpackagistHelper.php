<?php

namespace Jenko\WpPluginTroubleDetector;

use Composer\Installer\PackageEvent;
use Composer\Package\Loader\JsonLoader;
use Composer\Package\Loader\RootPackageLoader;
use Composer\Package\PackageInterface;

class WpackagistHelper implements WpackagistHelperInterface
{
    public function getPackageFromComposerJson(PackageEvent $event, string $pluginDir): PackageInterface
    {
        $repoManager = $event->getComposer()->getRepositoryManager();
        $config = $event->getComposer()->getConfig();
        $loader = new RootPackageLoader($repoManager, $config);
        $jsonLoader = new JsonLoader($loader);
        return $jsonLoader->load($pluginDir . '/composer.json');
    }
}
