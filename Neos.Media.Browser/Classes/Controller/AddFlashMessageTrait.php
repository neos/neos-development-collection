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
trait AddFlashMessageTrait
{
    /**
     * @Flow\Inject
     * @var \Neos\Flow\I18n\Translator
     */
    protected $_translator;

    /**
     * Add a translated flashMessage.
     *
     * @param string $messageBody The translation id for the message body.
     * @param string $messageTitle The translation id for the message title.
     * @param string $severity
     * @param array $messageArguments
     * @param integer $messageCode
     * @return void
     */
    public function addFlashMessage($messageBody, $messageTitle = '', $severity = Message::SEVERITY_OK, array $messageArguments = [], $messageCode = null): void
    {
        if (is_string($messageBody)) {
            $messageBody = $this->_translator->translateById($messageBody, $messageArguments, null, null, 'Main', 'Neos.Media.Browser') ?: $messageBody;
        }

        $messageTitle = (string)$this->_translator->translateById($messageTitle, $messageArguments, null, null, 'Main', 'Neos.Media.Browser');
        parent::addFlashMessage($messageBody, $messageTitle, $severity, $messageArguments, $messageCode);
    }
}
