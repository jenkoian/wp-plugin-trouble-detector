<?php

namespace Jenko\WpPluginTroubleDetector;

use Composer\Installer\PackageEvent;
use Composer\IO\IOInterface;
use Composer\Package\Loader\JsonLoader;
use Composer\Package\Loader\RootPackageLoader;
use Composer\Package\PackageInterface;

class WpPluginTroubleDetector
{
    private const WPACKAGIST_PLUGIN_VENDOR_NAME = 'wpackagist-plugin';

    public static function postPackageInstall(PackageEvent $event): void
    {
        /** @var PackageInterface $installedPackage */
        $mainPackage = $event->getComposer()->getPackage();
        /** @var PackageInterface $installedPackage */
        $installedPackage = $event->getOperation()->getPackage();
        $io = $event->getIO();

        if ($installedPackage->getType() !== 'wordpress-plugin') {
            return;
        }

        // @see https://github.com/composer/installers/blob/master/src/Composer/Installers/BaseInstaller.php#L40-L46
        $prettyName = $installedPackage->getPrettyName();
        if (strpos($prettyName, '/') !== false) {
            list($vendor, $name) = explode('/', $prettyName);
        } else {
            $vendor = '';
            $name = $prettyName;
        }

        $wpContentDir = defined('WP_CONTENT_DIR') ? WP_CONTENT_DIR : __DIR__ . '/../wp-content';
        $pluginDir = $wpContentDir . '/plugins/' . $name;

        // Check to see if vendor directory has been committed by the plugin.
        if (is_dir($pluginDir . '/vendor')) {
            $io->writeError(
                sprintf(
                    "<warning>Oh Shit %s has committed vendor directory!! This could cause you some trouble.</warning>",
                    $installedPackage->getPrettyName()
                )
            );
        }

        // Check to see if they have packages that match packages of the main project.
        self::warnIfMatchingDeps($mainPackage, $installedPackage, $io);

        // Do the same as the above but for wpackagist packages.
        if (self::isWpackagistPackage($installedPackage)) {
            // Is there a composer.json file present?
            if (file_exists($pluginDir . '/composer.json')) {
                $wpackagistPackage = WpackagistHelperFactory::get_instance()->getPackageFromComposerJson($event, $pluginDir);
                self::warnIfMatchingDeps($mainPackage, $wpackagistPackage, $io);
            }
        }
    }

    private static function isWpackagistPackage(PackageInterface $installedPackage): bool
    {
        return strpos($installedPackage->getPrettyName(), self::WPACKAGIST_PLUGIN_VENDOR_NAME) !== false;
    }

    private static function warnIfMatchingDeps(PackageInterface $mainPackage, PackageInterface $packageToCompare, IOInterface $io): void
    {
        $matchingDeps = array_intersect(array_keys($mainPackage->getRequires()), array_keys($packageToCompare->getRequires())) !== [];
        if ($matchingDeps) {
            $io->writeError(
                sprintf(
                    "<warning>Oh Shit %s shares some deps with you!! This could cause you some trouble.</warning>",
                    $packageToCompare->getPrettyName()
                )
            );
        }
    }
}
