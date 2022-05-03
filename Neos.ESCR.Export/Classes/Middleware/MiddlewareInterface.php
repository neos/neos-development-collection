<?php
declare(strict_types=1);
namespace Neos\ESCR\Export\Middleware;

interface MiddlewareInterface
{
    public function getLabel(): string;

    public function processImport(Context $context): void;

    public function processExport(Context $context): void;
}
