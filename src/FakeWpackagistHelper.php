<?php

namespace Jenko\WpPluginTroubleDetector;

use Composer\Installer\PackageEvent;
use Composer\Package\Link;
use Composer\Package\Package;
use Composer\Package\PackageInterface;
use Composer\Semver\Constraint\Constraint;

class FakeWpackagistHelper implements WpackagistHelperInterface
{
    private function getMockPackageWithDependency(string $packageName): PackageInterface
    {
        // Specify packages with a dependency, e.g. packageName => Dependency
        $packages = [
            'wpackagist-plugin-with-matching-deps' => new Package('common-dependency', '1.0.0', '1.0')
        ];

        $package = new Package($packageName, '1.0.0', '1.0');
        $packageDependency = $packages[$packageName] ?? new Package('fake-package', '1.0.0', '1.0');
        $package->setRequires(
            [
                $packageDependency->getName() => new Link($package->getName(), $packageDependency->getName(), new Constraint('>=', '1.0'))
            ]
        );
        return $package;
    }

    public function getPackageFromComposerJson(PackageEvent $event, string $pluginDir): PackageInterface
    {
        $packageName = $event->getOperation()->getPackage()->getName();

        return $this->getMockPackageWithDependency($packageName);
    }
}
