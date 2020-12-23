<?php

namespace Jenko\WpPluginTroubleDetector;

class WpackagistHelperFactory
{
    public static function get_instance(): WpackagistHelperInterface {
        if ( defined( 'WP_PLUGIN_TROUBLE_DETECTOR_TESTING' ) && WP_PLUGIN_TROUBLE_DETECTOR_TESTING === true ) {
            return new FakeWpackagistHelper();
        }

        return new WpackagistHelper();
    }
}
