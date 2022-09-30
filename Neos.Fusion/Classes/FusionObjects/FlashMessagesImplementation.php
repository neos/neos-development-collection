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

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\FusionObjects\AbstractFusionObject;
use Neos\Error\Messages\Message;
use Neos\Error\Messages\Error;
use Neos\Error\Messages\Notice;
use Neos\Error\Messages\Warning;


/**
 * A Fusion object to return the flashmessages from the current controller context
 *
 * //fusionPath renderer a Fusion object to render the flashMessage collection
 */
class FlashMessagesImplementation  extends AbstractFusionObject
{
    /**
     * @return Message[]
     */
    protected function getFlashMessages()
    {
        if($this->getFlushMessages()){
            $messages = $this->getRuntime()->getControllerContext()->getFlashMessageContainer()->getMessagesAndFlush($this->getSeverity());
        }else{
            $messages = $this->getRuntime()->getControllerContext()->getFlashMessageContainer()->getMessages($this->getSeverity());
        }

        foreach ($this->getAdditionalMessages() as $additionalMessage){
            switch ($additionalMessage['severity']) {
                case Message::SEVERITY_ERROR:
                    $messages[] = new Error($additionalMessage['message'], $additionalMessage['code'], $additionalMessage['arguments'], $additionalMessage['title']);
                    break;
                case Message::SEVERITY_WARNING:
                    $messages[] = new Warning($additionalMessage['message'], $additionalMessage['code'], $additionalMessage['arguments'], $additionalMessage['title']);
                    break;
                case Message::SEVERITY_NOTICE:
                    $messages[] = new Notice($additionalMessage['message'], $additionalMessage['code'], $additionalMessage['arguments'], $additionalMessage['title']);
                    break;
                default:
                    $messages[] = new Message($additionalMessage['message'], $additionalMessage['code'], $additionalMessage['arguments'], $additionalMessage['title']);
            }
        }
        return $messages;
    }

    /**
     * severity of the messages to return (One of the Message::SEVERITY_* constants)
     *
     * @return string
     */
    public function getSeverity()
    {
        return $this->fusionValue('severity') ?? null;
    }

    /**
     * whether the messages should be flushed after reading them, default is true
     *
     * @return string
     */
    public function getFlushMessages()
    {
        return $this->fusionValue('flushMessages');
    }

    /**
     * name of the property that contains the flashMessages inside the prototype
     *
     * @return string
     */
    public function getCollectionName()
    {
        return $this->fusionValue('collectionName');
    }

    /**
     * additional messages that were added in the fusion code
     *
     * @return array
     */
    public function getAdditionalMessages()
    {
        return $this->fusionValue('additionalMessages');
    }


    /**
     * Returns the rendered flashMessages or if there is no rendering defined as array
     *
     * @return string|Message[]
     * @throws \Neos\Flow\Security\Exception
     * @throws \Neos\Fusion\Exception
     */
    public function evaluate()
    {
        $rendererPath = $this->path . '/renderer';
        $contentPath = $this->path . '/content';

        if ($this->runtime->canRender($rendererPath) === false){
            if ($this->runtime->canRender($contentPath) === false){
                return $this->getFlashMessages();
            }else{
                $rendererPath = $contentPath;
            }
        }
        $flashMessages = $this->getFlashMessages();

        $context = $this->runtime->getCurrentContext();
        $context[$this->getCollectionName()] = $flashMessages;
        $context['hasFlashMessages'] = count($flashMessages) > 0;
        $this->runtime->pushContextArray($context);

        $renderedMessages = $this->runtime->render($rendererPath);
        $this->runtime->popContext();

        return $renderedMessages;
    }

}
