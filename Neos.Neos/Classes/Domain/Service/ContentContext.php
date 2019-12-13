<?php
namespace Neos\Neos\Domain\Service;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Flow\Persistence\Exception\IllegalObjectTypeException;
use Neos\Flow\Security\Authorization\PrivilegeManagerInterface;
use Neos\Flow\Security\Exception;
use Neos\Neos\Domain\Model\Domain;
use Neos\Neos\Domain\Model\UserInterfaceMode;
use Neos\Neos\Domain\Model\Site;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Service\Context;
use Neos\ContentRepository\Domain\Utility\NodePaths;

/**
 * The Content Context
 *
 * @Flow\Scope("prototype")
 * @api
 */
class ContentContext extends Context
{
    /**
     * @var Site
     */
    protected $currentSite;

    /**
     * @var Domain
     */
    protected $currentDomain;

    /**
     * @var NodeInterface
     */
    protected $currentSiteNode;

    /**
     * @Flow\Inject
     * @var PrivilegeManagerInterface
     */
    protected $privilegeManager;

    /**
     * @Flow\Inject
     * @var UserInterfaceModeService
     */
    protected $interfaceRenderModeService;

    /**
     * Creates a new Content Context object.
     *
     * NOTE: This is for internal use only, you should use the ContextFactory for creating Context instances.
     *
     * @param string $workspaceName Name of the current workspace
     * @param \DateTimeInterface $currentDateTime The current date and time
     * @param array $dimensions Array of dimensions with array of ordered values
     * @param array $targetDimensions Array of dimensions used when creating / modifying content
     * @param boolean $invisibleContentShown If invisible content should be returned in query results
     * @param boolean $removedContentShown If removed content should be returned in query results
     * @param boolean $inaccessibleContentShown If inaccessible content should be returned in query results
     * @param Site $currentSite The current Site object
     * @param Domain $currentDomain The current Domain object
     * @see ContextFactoryInterface
     */
    public function __construct($workspaceName, \DateTimeInterface $currentDateTime, array $dimensions, array $targetDimensions, $invisibleContentShown, $removedContentShown, $inaccessibleContentShown, Site $currentSite = null, Domain $currentDomain = null)
    {
        parent::__construct($workspaceName, $currentDateTime, $dimensions, $targetDimensions, $invisibleContentShown, $removedContentShown, $inaccessibleContentShown);
        $this->currentSite = $currentSite;
        $this->currentDomain = $currentDomain;
        $this->targetDimensions = $targetDimensions;
    }

    /**
     * Returns the current site from this frontend context
     *
     * @return Site The current site
     */
    public function getCurrentSite()
    {
        return $this->currentSite;
    }

    /**
     * Returns the current domain from this frontend context
     *
     * @return Domain The current domain
     * @api
     */
    public function getCurrentDomain()
    {
        return $this->currentDomain;
    }

    /**
     * Returns the node of the current site.
     *
     * @return NodeInterface
     */
    public function getCurrentSiteNode()
    {
        if ($this->currentSite !== null && $this->currentSiteNode === null) {
            $siteNodePath = NodePaths::addNodePathSegment(SiteService::SITES_ROOT_PATH, $this->currentSite->getNodeName());
            $this->currentSiteNode = $this->getNode($siteNodePath);
            if (!($this->currentSiteNode instanceof NodeInterface)) {
                $this->systemLogger->warning(sprintf('Couldn\'t load the site node for path "%s" in workspace "%s". This is probably due to a missing baseworkspace for the workspace of the current user.', $siteNodePath, $this->workspaceName), LogEnvironment::fromMethodName(__METHOD__));
            }
        }
        return $this->currentSiteNode;
    }

    /**
     * Returns the properties of this context.
     *
     * @return array
     */
    public function getProperties()
    {
        return [
            'workspaceName' => $this->workspaceName,
            'currentDateTime' => $this->currentDateTime,
            'dimensions' => $this->dimensions,
            'targetDimensions' => $this->targetDimensions,
            'invisibleContentShown' => $this->invisibleContentShown,
            'removedContentShown' => $this->removedContentShown,
            'inaccessibleContentShown' => $this->inaccessibleContentShown,
            'currentSite' => $this->currentSite,
            'currentDomain' => $this->currentDomain
        ];
    }

    /**
     * Returns true if current context is live workspace, false otherwise
     *
     * @return bool
     * @throws IllegalObjectTypeException
     */
    public function isLive(): bool
    {
        return ($this->getWorkspace()->getBaseWorkspace() === null);
    }

    /**
     * Returns true while rendering backend (not live workspace and access to backend granted), false otherwise
     *
     * @return boolean
     * @throws IllegalObjectTypeException
     */
    public function isInBackend(): bool
    {
        return (!$this->isLive() && $this->hasAccessToBackend());
    }

    /**
     * @return UserInterfaceMode
     */
    public function getCurrentRenderingMode(): UserInterfaceMode
    {
        return $this->interfaceRenderModeService->findModeByCurrentUser();
    }

    /**
     * Is access to the neos backend granted by current authentications.
     *
     * @return bool
     */
    protected function hasAccessToBackend(): bool
    {
        try {
            return $this->privilegeManager->isPrivilegeTargetGranted('Neos.Neos:Backend.GeneralAccess');
        } catch (Exception $exception) {
            return false;
        }
    }
}
