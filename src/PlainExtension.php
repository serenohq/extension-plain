<?php

namespace Sereno\Extensions;

use Sereno\AbstractExtension;

class PlainExtension extends AbstractExtension {
    /**
     * Register builders.
     *
     * @return array
     */
    public function getBuilders(): array {
        return [Plain\FileBuilder::class];
    }

    public function getContentDirectory(): array
    {
        $dirs = collect((array) config('plain.handle'))->map(
            function ($entry) {
                return rtrim($entry, '*');
            }
        )->toArray();

        print_r($dirs);

        return $dirs;
    }
}
