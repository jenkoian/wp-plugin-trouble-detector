<?php

namespace Jenko\WpPluginTroubleDetector;

class ComposerPackageHelperFactory
{
    public static function get_instance(): ComposerPackageHelperInterface {
        if ( defined( 'WP_PLUGIN_TROUBLE_DETECTOR_TESTING' ) && WP_PLUGIN_TROUBLE_DETECTOR_TESTING === true ) {
            return new FakeComposerPackageHelper();
        }

        return new ComposerPackageHelper();
    }
}
