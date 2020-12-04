<?php

namespace Jenko\WpPluginTroubleDetector;

use Composer\Installer\PackageEvent;
use Composer\Package\PackageInterface;

class WpPluginTroubleDetector
{
    /**
     * @param PackageEvent $event
     *
     * @return mixed|void
     */
    public static function postPackageInstall(PackageEvent $event)
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
        $matchingDeps = array_intersect($mainPackage->getRequires(), $installedPackage->getRequires()) !== false;
        if ($matchingDeps) {
            $io->writeError(
                sprintf(
                    "<warning>Oh Shit %s shares some deps with you!! This could cause you some trouble.</warning>",
                    $installedPackage->getPrettyName()
                )
            );
        }
    }
}
