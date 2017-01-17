<?php

namespace Sereno\Extensions\Plain;

use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Factory;
use Illuminate\View\Engines\PhpEngine;
use Sereno\Contracts\Builder;
use Sereno\Processors\FileProcessor;
use Symfony\Component\Finder\SplFileInfo;

class FileBuilder implements Builder {

    protected $paths = [];

    protected $processor;

    protected $filesystem;

    protected $view;

    public function __construct(FileProcessor $processor, Filesystem $filesystem, Factory $view) {
        $this->paths = (array) config('plain.handle', []);
        $this->processor = $processor;
        $this->filesystem = $filesystem;
        $this->view = $view;
    }

    public function handledPatterns(): array {
        return $this->paths;
    }

    public function data(array $files, array $data) : array {
        return $data;
    }

    public function build(array $files, array $data) {
        $data = $this->getViewData($data);

        foreach ($files as $file) {
            $filename = $this->processor->getOutputFilename(new SplFileInfo(
                $file->getPathname(), root_dir(), str_replace(root_dir(), '', $file->getPathname())
            ));
            debug('Copy: '.$file->getRelativePathname().' -> '.$filename);

            $this->processor->writeContent($filename, $this->compileWithBlade($file, $data));
        }
    }

    protected function compileWithBlade(SplFileInfo $file, array $data): string
    {
        $viewCache = cache_dir(sha1($file->getPathname()).'.php');
        $this->filesystem->put($viewCache, $this->getCompiler()->compileString($file->getContents()));

        return (new PhpEngine())->get($viewCache, $data);
    }

    protected function getViewData($data)
    {
        $data = array_merge($this->view->getShared(), $data);

        foreach ($data as $key => $value) {
            if ($value instanceof Renderable) {
                $data[$key] = $value->render();
            }
        }

        return $data;
    }

    protected function getCompiler(): BladeCompiler
    {
        /** @var Blade $blade */
        $blade = $this->view->getEngineResolver()->resolve('blade');

        return $blade->getCompiler();
    }
}
