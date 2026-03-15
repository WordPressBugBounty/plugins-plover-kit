<?php

namespace Plover\Kit\Extensions;

use Plover\Core\Extensions\AdvancedLayout;
use Plover\Core\Services\Blocks\Blocks;
use Plover\Core\Services\Extensions\Contract\Extension;
use Plover\Core\Services\Settings\Control;
use Plover\Core\Toolkits\Format;
use Plover\Core\Toolkits\Responsive;
/**
 * Add overflow, box-sizing and visibility options in display panel.
 * Add min/max width/height options
 *
 * @since 1.6.0
 */
class PremiumAdvancedLayout extends Extension {
    public function boot( Blocks $blocks ) {
    }

}
