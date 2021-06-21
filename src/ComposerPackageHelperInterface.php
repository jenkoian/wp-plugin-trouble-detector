<?php

namespace Jenko\WpPluginTroubleDetector;

use Composer\Installer\PackageEvent;
use Composer\Package\PackageInterface;

interface ComposerPackageHelperInterface
{
    public function getPackageFromComposerJson(PackageEvent $event, string $pluginDir): PackageInterface;
}
