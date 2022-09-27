<?php
namespace Neos\Fusion\FusionObjects;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Error\Messages\Message;
use Neos\Flow\Annotations as Flow;
use Neos\Fusion\FusionObjects\MapImplementation;

/**
 * A Fusion object to return the flashmessages from the current controller context
 * //fusionPath itemRenderer the Fusion object which is triggered for each flashMessage
 * //fusionPath severity to filter the messages to return by severity (One of the Neos\Error\Messages\Message::SEVERITY_* constants)
 * //fusionPath flushMessages whether the messages should be flushed after reading them, default is true
 */
class FlashMessagesImplementation extends MapImplementation
{
    /**
     * @return Message[]
     */
    public function getItems()
    {
        if($this->getFlushMessages()){
            $messages = $this->getRuntime()->getControllerContext()->getFlashMessageContainer()->getMessagesAndFlush($this->getSeverity());
        }else{
            $messages = $this->getRuntime()->getControllerContext()->getFlashMessageContainer()->getMessages($this->getSeverity());
        }
        return $messages;
    }

    /**
     * @return string
     */
    public function getSeverity()
    {
        return $this->fusionValue('severity') ?? null;
    }

    /**
     * @return string
     */
    public function getFlushMessages()
    {
        return $this->fusionValue('flushMessages');
    }

    /**
     * Get the glue to insert between items
     *
     * @return string
     */
    public function getGlue()
    {
        return $this->fusionValue('__meta/glue') ?? '';
    }

    /**
     * Evaluate the collection nodes
     *
     * @return string|Message[]
     * @throws \Neos\Fusion\Exception
     */
    public function evaluate()
    {
        if($this->runtime->canRender($this->path . '/content')) {
            $glue = $this->getGlue();
            return implode($glue, parent::evaluate());
        }else{
            return $this->getItems();
        }
    }

}
