<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3CR\Admin\Controller;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3CR".                    *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * The export controller
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ExportController extends \F3\FLOW3\MVC\Controller\ActionController {

	/**
	 * The supported request types of this controller
	 *
	 * @var array
	 */
	protected $supportedRequestTypes = array('F3\FLOW3\MVC\Web\Request', 'F3\FLOW3\MVC\CLI\Request');

	/**
	 * @var \F3\PHPCR\SessionInterface
	 */
	protected $session;

	/**
	 * @var \F3\FLOW3\Utility\Environment
	 */
	protected $environment;

	/**
	 * Injects a Content Repository instance
	 *
	 * @param \F3\PHPCR\RepositoryInterface $contentRepository
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectContentRepository(\F3\PHPCR\RepositoryInterface $contentRepository) {
		$this->session = $contentRepository->login();
	}

	/**
	 * Injects an Environment instance
	 *
	 * @param \F3\FLOW3\Utility\Environment $environment
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectEnvironment(\F3\FLOW3\Utility\Environment $environment) {
		$this->environment = $environment;
	}

	/**
	 * Returns the XML export strating at the node with the given UUID in system
	 * view form.
	 *
	 * @param string $rootNodeIdentifier
	 * @param boolean $skipBinary
	 * @param boolean $noRecurse
	 * @return string
	 */
	public function systemViewAction($rootNodeIdentifier, $skipBinary, $noRecurse) {
		try {
			$rootNode = $this->session->getNodeByIdentifier($this->arguments['rootNodeIdentifier']->getValue());
		} catch(\F3\PHPCR\ItemNotFoundException $e) {
			$this->throwStatus(404);
		}

		$temporaryPathAndFilename = tempnam($this->environment->getPathToTemporaryDirectory(), 'TYPO3CR-XML-export');
		$this->response->setHeader('Content-Type', 'text/xml');
		$this->session->exportSystemView($rootNode->getPath(), $temporaryPathAndFilename, $this->arguments['skipBinary']->getValue(), $this->arguments['noRecurse']->getValue());

		$xml = file_get_contents($temporaryPathAndFilename);
		unlink($temporaryPathAndFilename);
		return $xml;
	}

	/**
	 * Returns the XML export strating at the node with the given UUID in
	 * document view form.
	 *
	 * @param string $rootNodeIdentifier
	 * @param boolean $skipBinary
	 * @param boolean $noRecurse
	 * @return string
	 */
	public function documentViewAction($rootNodeIdentifier, $skipBinary, $noRecurse) {
		try {
			$rootNode = $this->session->getNodeByIdentifier($this->arguments['rootNodeIdentifier']->getValue());
		} catch(\F3\PHPCR\ItemNotFoundException $e) {
			$this->throwStatus(404);
		}

		$temporaryPathAndFilename = tempnam($this->environment->getPathToTemporaryDirectory(), 'TYPO3CR-XML-export');
		$this->response->setHeader('Content-Type', 'text/xml');
		$this->session->exportDocumentView($rootNode->getPath(), $temporaryPathAndFilename, $this->arguments['skipBinary']->getValue(), $this->arguments['noRecurse']->getValue());

		$xml = file_get_contents($temporaryPathAndFilename);
		unlink($temporaryPathAndFilename);
		return $xml;
	}

}
?>