<?php
declare(strict_types=1);

namespace Neos\CliSetup\Infrastructure\ImageHandler;

/*
 * This file is part of the Neos.CliSetup package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Imagine\ImagineFactory;

class ImageHandlerService
{

    /**
     * @Flow\InjectConfiguration(path="supportedImageHandlers")
     * @var string[]
     */
    protected $supportedImageHandlers;

    /**
     * @Flow\InjectConfiguration(path="requiredImageFormats")
     * @var string[]
     */
    protected $requiredImageFormats;

    /**
     * @Flow\Inject
     * @var ImagineFactory
     */
    protected $imagineFactory;

    /**
     * Return all Imagine drivers that support the loading of the required images
     *
     * @return array<string,string>
     */
    public function getAvailableImageHandlers(): array
    {
        $availableImageHandlers = [];
        foreach ($this->supportedImageHandlers as $driverName => $description) {
            if (\extension_loaded(strtolower($driverName))) {
                if ($driverName == "Vips" && !class_exists("Imagine\\Vips\\Imagine")) {
                    continue;
                }
                $unsupportedFormats = $this->findUnsupportedImageFormats($driverName);
                if (\count($unsupportedFormats) === 0) {
                    $availableImageHandlers[$driverName] = $description;
                }
            }
        }
        return $availableImageHandlers;
    }

    /**
     * Returns unavailable drivers.
     *
     * @return array
     */
    public function checkDriverAvailability(): array
    {
        $unavailableDrivers = [];
        foreach ($this->supportedImageHandlers as $driverName => $description) {
            if (\extension_loaded(strtolower($driverName))) {
                $className = 'Imagine\\' . ucfirst($driverName) . '\\Imagine';
                if (!class_exists($className)) {
                    $unavailableDrivers[] = $driverName;
                }
            }
        }

        return $unavailableDrivers;
    }

    /**
     * @param string $driver
     * @return array Not supported image formats
     */
    protected function findUnsupportedImageFormats(string $driver): array
    {
        $imagine = $this->getImagineInstance($driver);
        $unsupportedFormats = [];

        foreach ($this->requiredImageFormats as $imageFormat => $testFile) {
            try {
                $imagine->load(file_get_contents($testFile));
            } /** @noinspection BadExceptionsProcessingInspection */ catch (\Exception $exception) {
                $unsupportedFormats[] = $imageFormat;
            }
        }
        return $unsupportedFormats;
    }

    /**
     * @param string $driver
     * @return \Imagine\Image\ImagineInterface
     * @throws \ReflectionException
     */
    protected function getImagineInstance(string $driver): \Imagine\Image\ImagineInterface
    {
        $this->imagineFactory->injectSettings(['driver' => ucfirst($driver)]);
        return $this->imagineFactory->create();
    }
}
