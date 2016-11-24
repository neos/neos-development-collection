<?php
namespace TYPO3\Neos\Service\Controller;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Exception as FlowException;
use Neos\Flow\Http\Response as HttpResponse;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Flow\Mvc\Exception\StopActionException;
use Neos\Flow\Mvc\RequestInterface;
use Neos\Flow\Mvc\ResponseInterface;
use TYPO3\Neos\Controller\BackendUserTranslationTrait;

/**
 * Abstract Service Controller
 */
abstract class AbstractServiceController extends ActionController
{
    use BackendUserTranslationTrait;

    /**
     * @var array
     */
    protected $supportedMediaTypes = array('application/json');

    /**
     * A preliminary error action for handling validation errors
     *
     * @return void
     */
    public function errorAction()
    {
        if ($this->arguments->getValidationResults()->hasErrors()) {
            $errors = [];
            foreach ($this->arguments->getValidationResults()->getFlattenedErrors() as $propertyName => $propertyErrors) {
                foreach ($propertyErrors as $propertyError) {
                    /** @var \Neos\Flow\Error\Error $propertyError */
                    $error = array(
                        'severity' => $propertyError->getSeverity(),
                        'message' => $propertyError->render()
                    );
                    if ($propertyError->getCode()) {
                        $error['code'] = $propertyError->getCode();
                    }
                    if ($propertyError->getTitle()) {
                        $error['title'] = $propertyError->getTitle();
                    }
                    $errors[$propertyName][] = $error;
                }
            }
            $this->throwStatus(409, null, json_encode($errors));
        }
        $this->throwStatus(400);
    }

    /**
     * Catch exceptions while processing an exception and respond to JSON format
     * TODO: This is an explicit exception handling that will be replaced by format-enabled exception handlers.
     *
     * @param RequestInterface $request The request object
     * @param ResponseInterface $response The response, modified by this handler
     * @return void
     * @throws \Exception
     */
    public function processRequest(RequestInterface $request, ResponseInterface $response)
    {
        try {
            parent::processRequest($request, $response);
        } catch (StopActionException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            if ($this->request->getFormat() !== 'json' || !$response instanceof HttpResponse) {
                throw $exception;
            }
            $exceptionData = $this->convertException($exception);
            $response->setHeader('Content-Type', 'application/json');
            if ($exception instanceof FlowException) {
                $response->setStatus($exception->getStatusCode());
            } else {
                $response->setStatus(500);
            }
            $response->setContent(json_encode(array('error' => $exceptionData)));
            $this->systemLogger->logException($exception);
        }
    }

    /**
     * @param \Exception $exception
     * @return array
     */
    protected function convertException(\Exception $exception)
    {
        if ($this->objectManager->getContext()->isProduction()) {
            if ($exception instanceof FlowException) {
                $exceptionData['message'] = 'When contacting the maintainer of this application please mention the following reference code:<br /><br />' . $exception->getReferenceCode();
            }
        } else {
            $exceptionData = array(
                'code' => $exception->getCode(),
                'message' => $exception->getMessage(),
            );
            $splitMessagePattern = '/
                (?<=                # Begin positive lookbehind.
                  [.!?]\s           # Either an end of sentence punct,
                | \n                # or line break
                )
                (?<!                # Begin negative lookbehind.
                  i\.E\.\s          # Skip "i.E."
                )                   # End negative lookbehind.
                /ix';
            $sentences = preg_split($splitMessagePattern, $exception->getMessage(), 2, PREG_SPLIT_NO_EMPTY);
            if (!isset($sentences[1])) {
                $exceptionData['message'] = $exception->getMessage();
            } else {
                $exceptionData['message'] = trim($sentences[0]);
                $exceptionData['details'] = trim($sentences[1]);
            }
            if ($exception instanceof FlowException) {
                $exceptionData['referenceCode'] = $exception->getReferenceCode();
            }
            if ($exception->getPrevious() !== null) {
                $exceptionData['previous'] = $this->convertException($exception->getPrevious());
            }
        }
        return $exceptionData;
    }
}
