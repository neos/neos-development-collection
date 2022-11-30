<?php
declare(strict_types=1);

namespace Neos\Media\Browser\Controller;

/*
 * This file is part of the Neos.Media.Browser package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Error\Messages\Message;
use Neos\Flow\Annotations as Flow;

/**
 * A trait to add backend translation based on the backend users settings
 */
trait AddTranslatedFlashMessageTrait
{
    /**
     * @Flow\Inject
     * @var \Neos\Flow\I18n\Translator
     */
    protected $_translator;

    public function addTranslatedFlashMessage(
        string $messageBodyTranslationId,
        string $severity = Message::SEVERITY_OK,
        array $messageArguments = [],
        ?int $messageCode = null
    ): void {
        $messageBody = $this->_translator->translateById($messageBodyTranslationId, $messageArguments, null, null, 'Main', 'Neos.Media.Browser');
        assert($messageBody !== null, "$messageBodyTranslationId could not be translated.");
        parent::addFlashMessage($messageBody, '', $severity, [], $messageCode);
    }
}
