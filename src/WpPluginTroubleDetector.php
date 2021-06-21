<?php

namespace Jenko\WpPluginTroubleDetector;

use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\Installer\PackageEvent;
use Composer\IO\IOInterface;
use Composer\Package\Link;
use Composer\Package\PackageInterface;

class WpPluginTroubleDetector
{
    private const WPACKAGIST_PLUGIN_VENDOR_NAME = 'wpackagist-plugin';
    private const IGNORED_DEPS = ['php'];

    public static function postPackageInstall(PackageEvent $event): void
    {
        /** @var PackageInterface $installedPackage */
        $mainPackage = $event->getComposer()->getPackage();
        /** @var PackageInterface $installedPackage */
        $installedPackage = $event->getOperation() instanceof UpdateOperation ? $event->getOperation()->getTargetPackage() : $event->getOperation()->getPackage();
        $io = $event->getIO();

        if ($installedPackage->getType() !== 'wordpress-plugin') {
            return;
        }

        // @see https://github.com/composer/installers/blob/master/src/Composer/Installers/BaseInstaller.php#L40-L46
        $name = self::shortPackageNameFromPrettyName($installedPackage->getPrettyName());

        $pluginDir = self::getPackageDir($name);

        // Check to see if vendor directory has been committed by the plugin.
        self::checkVendorDir($pluginDir, $io, $installedPackage);

        // Check to see if they have packages that match packages of the main project.
        self::checkMatchingDeps($mainPackage, $installedPackage, $io, $pluginDir, $event);

        // Check all existing plugins to see if any of these have matching deps.
        self::checkExistingPlugins($mainPackage, $installedPackage, $io, $event);
    }

    private static function checkExistingPlugins(
        PackageInterface $mainPackage,
        PackageInterface $installedPackage,
        IOInterface $io,
        PackageEvent $event
    ) {
        $requires = $mainPackage->getRequires();

        foreach ($requires as $name => $require) {
            if ($name === $installedPackage->getPrettyName()) {
                continue;
            }

            $name = self::shortPackageNameFromPrettyName($name);
            $pluginDir = self::getPackageDir($name);

            if (file_exists($pluginDir . '/composer.json')) {
                $existingPluginPackage = ComposerPackageHelperFactory::get_instance()->getPackageFromComposerJson($event, $pluginDir);

                // Only check plugins.
                if ($existingPluginPackage->getType() !== 'wordpress-plugin') {
                    continue;
                }

                self::warnIfMatchingDeps($existingPluginPackage, $installedPackage, $io);
            }
        }
    }

    private static function isWpackagistPackage(PackageInterface $installedPackage): bool
    {
        return strpos($installedPackage->getPrettyName(), self::WPACKAGIST_PLUGIN_VENDOR_NAME) !== false;
    }

    private static function warnIfMatchingDeps(
        PackageInterface $mainPackage,
        PackageInterface $packageToCompare,
        IOInterface $io
    ): void {
        $matchingDeps = array_intersect(
            array_keys(array_filter($mainPackage->getRequires(), static fn(Link $req) => !in_array( $req->getTarget(), self::IGNORED_DEPS, true ))),
            array_keys(array_filter($packageToCompare->getRequires(), static fn($req) => !in_array( $req->getTarget(), self::IGNORED_DEPS, true ))),
        );
        $hasMatchingDeps = $matchingDeps !== [];
        if ($hasMatchingDeps) {
            $io->writeError(
                sprintf(
                    "<warning>Oh snap %s shares some deps with %s!! This could cause you some trouble.</warning>",
                    $packageToCompare->getPrettyName(),
                    $mainPackage->getPrettyName()
                )
            );
        }
    }

    /**
     * @param string $pluginDir
     * @param IOInterface $io
     * @param PackageInterface $installedPackage
     */
    private static function checkVendorDir(
        string $pluginDir,
        IOInterface $io,
        PackageInterface $installedPackage
    ): void {
        if (is_dir($pluginDir . '/vendor')) {
            $io->writeError(
                sprintf(
                    "<warning>Oh snap %s has a committed vendor directory!! This could cause you some trouble.</warning>",
                    $installedPackage->getPrettyName()
                )
            );
        }
    }

    /**
     * @param PackageInterface $mainPackage
     * @param PackageInterface $installedPackage
     * @param IOInterface $io
     * @param string $pluginDir
     * @param PackageEvent $event
     */
    private static function checkMatchingDeps(
        PackageInterface $mainPackage,
        PackageInterface $installedPackage,
        IOInterface $io,
        string $pluginDir,
        PackageEvent $event
    ): void {
        self::warnIfMatchingDeps($mainPackage, $installedPackage, $io);

        // Do the same as the above but for wpackagist packages.
        if (self::isWpackagistPackage($installedPackage)) {
            // Is there a composer.json file present?
            if (file_exists($pluginDir . '/composer.json')) {
                $wpackagistPackage = ComposerPackageHelperFactory::get_instance()->getPackageFromComposerJson($event,
                    $pluginDir);
                self::warnIfMatchingDeps($mainPackage, $wpackagistPackage, $io);
            }
        }
    }

    /**
     * @param PackageInterface $package
     *
     * @return string
     */
    private static function getPackageDir(string $packageName): string
    {
        $wpContentDir = defined('WP_CONTENT_DIR') ? WP_CONTENT_DIR : getcwd() . '/wp-content';
        $pluginDir = $wpContentDir . '/plugins/' . $packageName;

        return $pluginDir;
}

    /**
     * @param string $prettyName
     *
     * @return mixed|string
     */
    private static function shortPackageNameFromPrettyName(string $prettyName)
    {
        if (strpos($prettyName, '/') !== false) {
            list($vendor, $name) = explode('/', $prettyName);
        } else {
            $vendor = '';
            $name = $prettyName;
        }

        return $name;
}
}
