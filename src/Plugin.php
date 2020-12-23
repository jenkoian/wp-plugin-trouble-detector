<?php

namespace Jenko\WpPluginTroubleDetector;

use Composer\Composer;
use Composer\EventDispatcher\Event;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

class Plugin implements PluginInterface, EventSubscriberInterface
{
    private WpPluginTroubleDetector $wpPluginTroubleDetector;

    public function activate(Composer $composer, IOInterface $io): void
    {
        // Nothing to do here, all done through event listeners.
    }

    public function deactivate(Composer $composer, IOInterface $io)
    {
        // Nothing to do here, all done through event listeners.
    }

    public function uninstall(Composer $composer, IOInterface $io)
    {
        // Nothing to do here, all done through event listeners.
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PackageEvents::POST_PACKAGE_INSTALL => 'onPostPackageInstall',
            PackageEvents::POST_PACKAGE_UPDATE => 'onPostPackageUpdate',
        ];
    }

    public static function onPostPackageInstall(PackageEvent $event): void
    {
        WpPluginTroubleDetector::postPackageInstall($event);
    }

    public static function onPostPackageUpdate(PackageEvent $event): void
    {
        WpPluginTroubleDetector::postPackageInstall($event);
    }
}
