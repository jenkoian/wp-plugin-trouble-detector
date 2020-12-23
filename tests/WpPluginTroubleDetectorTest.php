<?php

namespace Jenko\WpPluginTroubleDetector\Tests;

use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\Installer\PackageEvent;
use Composer\IO\IOInterface;
use Composer\Package\Link;
use Composer\Package\Package;
use Composer\Repository\RepositoryInterface;
use Composer\Semver\Constraint\Constraint;
use Jenko\WpPluginTroubleDetector\WpPluginTroubleDetector;
use PHPUnit\Framework\TestCase;

class WpPluginTroubleDetectorTest extends TestCase
{
    public function setUp(): void
    {
        if (!defined('WP_CONTENT_DIR')) {
            define('WP_CONTENT_DIR', __DIR__ . '/wp-content');
        }

        if (!defined('WP_PLUGIN_TROUBLE_DETECTOR_TESTING')) {
            define('WP_PLUGIN_TROUBLE_DETECTOR_TESTING', true);
        }
    }

    public function testInstallDoesNotWriteErrorIfNotWordPressPlugin(): void
    {
        $io = $this->createMock(IOInterface::class);
        $composer = $this->createMock(Composer::class);
        $localRepo = $this->createMock(RepositoryInterface::class);
        $installOperation = $this->createMock(InstallOperation::class);

        $mainPackage = new Package('a', '1.0.0', '1.0');
        $installedPackage = new Package('b', '1.0.0', '1.0');

        $composer->expects(self::once())->method('getPackage')->willReturn($mainPackage);
        $installOperation->expects(self::once())->method('getPackage')->willReturn($installedPackage);
        $io->expects(self::never())->method('writeError');

        $event = new PackageEvent('post-package-install', $composer, $io, true, $localRepo, [], $installOperation);

        WpPluginTroubleDetector::postPackageInstall($event);
    }

    public function testUpdateDoesNotWriteErrorIfNotWordPressPlugin(): void
    {
        $io = $this->createMock(IOInterface::class);
        $composer = $this->createMock(Composer::class);
        $localRepo = $this->createMock(RepositoryInterface::class);
        $updateOperation = $this->createMock(UpdateOperation::class);

        $mainPackage = new Package('a', '1.0.0', '1.0');
        $installedPackage = new Package('b', '1.0.0', '1.0');

        $composer->expects(self::once())->method('getPackage')->willReturn($mainPackage);
        $updateOperation->expects(self::once())->method('getTargetPackage')->willReturn($installedPackage);
        $io->expects(self::never())->method('writeError');

        $event = new PackageEvent('post-package-update', $composer, $io, true, $localRepo, [], $updateOperation);

        WpPluginTroubleDetector::postPackageInstall($event);
    }

    public function testInstallWritesErrorIfDependencyHasVendorDir(): void
    {
        $io = $this->createMock(IOInterface::class);
        $composer = $this->createMock(Composer::class);
        $localRepo = $this->createMock(RepositoryInterface::class);
        $installOperation = $this->createMock(InstallOperation::class);

        $mainPackage = new Package('a', '1.0.0', '1.0');
        $installedPackage = new Package('plugin-with-vendor-dir', '1.0.0', '1.0');
        $installedPackage->setType('wordpress-plugin');

        $composer->expects(self::once())->method('getPackage')->willReturn($mainPackage);
        $installOperation->expects(self::once())->method('getPackage')->willReturn($installedPackage);
        $io->expects(self::once())->method('writeError')->with('<warning>Oh Shit plugin-with-vendor-dir has committed vendor directory!! This could cause you some trouble.</warning>');

        $event = new PackageEvent('post-package-install', $composer, $io, true, $localRepo, [], $installOperation);

        WpPluginTroubleDetector::postPackageInstall($event);
    }

    public function testUpdateWritesErrorIfDependencyHasVendorDir(): void
    {
        $io = $this->createMock(IOInterface::class);
        $composer = $this->createMock(Composer::class);
        $localRepo = $this->createMock(RepositoryInterface::class);
        $updateOperation = $this->createMock(UpdateOperation::class);

        $mainPackage = new Package('a', '1.0.0', '1.0');
        $installedPackage = new Package('plugin-with-vendor-dir', '1.0.0', '1.0');
        $installedPackage->setType('wordpress-plugin');

        $composer->expects(self::once())->method('getPackage')->willReturn($mainPackage);
        $updateOperation->expects(self::once())->method('getTargetPackage')->willReturn($installedPackage);
        $io->expects(self::once())->method('writeError')->with('<warning>Oh Shit plugin-with-vendor-dir has committed vendor directory!! This could cause you some trouble.</warning>');

        $event = new PackageEvent('post-package-update', $composer, $io, true, $localRepo, [], $updateOperation);

        WpPluginTroubleDetector::postPackageInstall($event);
    }

    public function testInstallWritesErrorIfDependencyHasMatchingDeps(): void
    {
        $io = $this->createMock(IOInterface::class);
        $composer = $this->createMock(Composer::class);
        $localRepo = $this->createMock(RepositoryInterface::class);
        $installOperation = $this->createMock(InstallOperation::class);

        $commonDependency = new Package('common-dependency', '1.0.0', '1.0');

        $mainPackage = new Package('a', '1.0.0', '1.0');
        $mainPackage->setRequires(
            [
                $commonDependency->getName() => new Link($mainPackage->getName(), $commonDependency->getName(), new Constraint('>=', '1.0'))
            ]
        );

        $installedPackage = new Package('plugin-with-matching-deps', '1.0.0', '1.0');
        $installedPackage->setType('wordpress-plugin');
        $installedPackage->setRequires(
            [
                $commonDependency->getName() => new Link($mainPackage->getName(), $commonDependency->getName(), new Constraint('>=', '1.0'))
            ]
        );

        $composer->expects(self::once())->method('getPackage')->willReturn($mainPackage);
        $installOperation->expects(self::once())->method('getPackage')->willReturn($installedPackage);
        $io->expects(self::once())->method('writeError')->with('<warning>Oh Shit plugin-with-matching-deps shares some deps with you!! This could cause you some trouble.</warning>');

        $event = new PackageEvent('post-package-install', $composer, $io, true, $localRepo, [], $installOperation);

        WpPluginTroubleDetector::postPackageInstall($event);
    }

    public function testUpdateWritesErrorIfDependencyHasMatchingDeps(): void
    {
        $io = $this->createMock(IOInterface::class);
        $composer = $this->createMock(Composer::class);
        $localRepo = $this->createMock(RepositoryInterface::class);
        $updateOperation = $this->createMock(UpdateOperation::class);

        $commonDependency = new Package('common-dependency', '1.0.0', '1.0');

        $mainPackage = new Package('a', '1.0.0', '1.0');
        $mainPackage->setRequires(
            [
                $commonDependency->getName() => new Link($mainPackage->getName(), $commonDependency->getName(), new Constraint('>=', '1.0'))
            ]
        );

        $installedPackage = new Package('plugin-with-matching-deps', '1.0.0', '1.0');
        $installedPackage->setType('wordpress-plugin');
        $installedPackage->setRequires(
            [
                $commonDependency->getName() => new Link($mainPackage->getName(), $commonDependency->getName(), new Constraint('>=', '1.0'))
            ]
        );

        $composer->expects(self::once())->method('getPackage')->willReturn($mainPackage);
        $updateOperation->expects(self::once())->method('getTargetPackage')->willReturn($installedPackage);
        $io->expects(self::once())->method('writeError')->with('<warning>Oh Shit plugin-with-matching-deps shares some deps with you!! This could cause you some trouble.</warning>');

        $event = new PackageEvent('post-package-update', $composer, $io, true, $localRepo, [], $updateOperation);

        WpPluginTroubleDetector::postPackageInstall($event);
    }

    public function testInstallWritesErrorIfWpackagistDependencyHasMatchingDeps(): void
    {
        $io = $this->createMock(IOInterface::class);
        $composer = $this->createMock(Composer::class);
        $localRepo = $this->createMock(RepositoryInterface::class);
        $installOperation = $this->createMock(InstallOperation::class);

        $commonDependency = new Package('common-dependency', '1.0.0', '1.0');

        $mainPackage = new Package('a', '1.0.0', '1.0');
        $mainPackage->setRequires(
            [
                $commonDependency->getName() => new Link($mainPackage->getName(), $commonDependency->getName(), new Constraint('>=', '1.0'))
            ]
        );

        $installedPackage = new Package('wpackagist-plugin-with-matching-deps', '1.0.0', '1.0');
        $installedPackage->setType('wordpress-plugin');

        $composer->expects(self::once())->method('getPackage')->willReturn($mainPackage);
        $installOperation->method('getPackage')->willReturn($installedPackage);
        $io->expects(self::once())->method('writeError')->with('<warning>Oh Shit wpackagist-plugin-with-matching-deps shares some deps with you!! This could cause you some trouble.</warning>');

        $event = new PackageEvent('post-package-install', $composer, $io, true, $localRepo, [], $installOperation);

        WpPluginTroubleDetector::postPackageInstall($event);
    }

    public function testUpdateWritesErrorIfWpackagistDependencyHasMatchingDeps(): void
    {
        $io = $this->createMock(IOInterface::class);
        $composer = $this->createMock(Composer::class);
        $localRepo = $this->createMock(RepositoryInterface::class);
        $updateOperation = $this->createMock(UpdateOperation::class);

        $commonDependency = new Package('common-dependency', '1.0.0', '1.0');

        $mainPackage = new Package('a', '1.0.0', '1.0');
        $mainPackage->setRequires(
            [
                $commonDependency->getName() => new Link($mainPackage->getName(), $commonDependency->getName(), new Constraint('>=', '1.0'))
            ]
        );

        $installedPackage = new Package('wpackagist-plugin-with-matching-deps', '1.0.0', '1.0');
        $installedPackage->setType('wordpress-plugin');

        $composer->expects(self::once())->method('getPackage')->willReturn($mainPackage);
        $updateOperation->method('getTargetPackage')->willReturn($installedPackage);
        $io->expects(self::once())->method('writeError')->with('<warning>Oh Shit wpackagist-plugin-with-matching-deps shares some deps with you!! This could cause you some trouble.</warning>');

        $event = new PackageEvent('post-package-update', $composer, $io, true, $localRepo, [], $updateOperation);

        WpPluginTroubleDetector::postPackageInstall($event);
    }
}
