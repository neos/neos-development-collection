<?php
namespace Neos\Fusion\Fixtures;

/**
 * subclass to test implementation of abstract class
 *
 * TestAbsorbingHandler
 */
class AbstractRenderingExceptionHandler extends \Neos\Fusion\Core\ExceptionHandlers\AbstractRenderingExceptionHandler
{
    /**
     * @var string
     */
    protected $fusionPath;

    /**
     * @var \Exception
     */
    protected $exception;

    /**
     * @var string
     */
    protected $referenceCode;

    /**
     * @var string
     */
    protected $message = 'message';

    /**
     * received exception
     *
     * @return \Exception
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * resulting message
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return mixed
     */
    public function getReferenceCode()
    {
        return $this->referenceCode;
    }

    /**
     * @return mixed
     * @deprecated
     */
    public function getTypoScriptPath()
    {
        return $this->fusionPath;
    }

    /**
     * @return mixed
     */
    public function getFusionPath()
    {
        return $this->fusionPath;
    }

    /**
     * dummy implementation of message-generation-stub
     *
     * @param string $fusionPath path causing the exception
     * @param \Exception $exception exception to handle
     * @param integer $referenceCode reference code for the exception
     * @return string
     */
    protected function handle($fusionPath, \Exception $exception, $referenceCode = null)
    {
        $this->fusionPath = $fusionPath;
        $this->exception = $exception;
        $this->referenceCode = $referenceCode;
        return $this->message;
    }
}
