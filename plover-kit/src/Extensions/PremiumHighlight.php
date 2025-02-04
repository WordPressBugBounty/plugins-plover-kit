<?php

namespace Plover\Kit\Extensions;

use Plover\Core\Extensions\Highlight;
use Plover\Core\Services\Extensions\Contract\Extension;
use Plover\Core\Services\Settings\Control;
use Plover\Core\Toolkits\Filesystem;
use Plover\Core\Toolkits\Str;
use Plover\Core\Toolkits\StyleEngine;
/**
 * Add line-number, copy, show language, etc.
 *
 * @since 1.0.0
 */
class PremiumHighlight extends Extension {
    /**
     * All prism theme files
     *
     * @var array
     */
    protected $prism_theme_files = [];

    public function register() {
    }

    public function boot() {
    }

}
