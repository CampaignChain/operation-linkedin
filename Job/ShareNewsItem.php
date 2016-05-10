<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain, Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\Operation\LinkedInBundle\Job;

use CampaignChain\Channel\LinkedInBundle\REST\LinkedInClient;
use CampaignChain\CoreBundle\Entity\Action;
use CampaignChain\CoreBundle\EntityService\CTAService;
use CampaignChain\Operation\LinkedInBundle\Entity\NewsItem;
use Doctrine\ORM\EntityManager;
use CampaignChain\CoreBundle\Entity\Medium;
use CampaignChain\CoreBundle\Job\JobActionInterface;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;

/**
 * Class ShareNewsItem
 * @package CampaignChain\Operation\LinkedInBundle\Job
 */
class ShareNewsItem implements JobActionInterface
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var CTAService
     */
    protected $ctaService;

    /**
     * @var LinkedInClient
     */
    protected $client;

    /**
     * @var ReportShareNewsItem
     */
    protected $reportShareNewsItem;

    /**
     * @var string
     */
    protected $message;

    /**
     * @var CacheManager
     */
    protected $cacheManager;

    /**
     * ShareNewsItem constructor.
     *
     * @param EntityManager $em
     * @param CTAService $ctaService
     * @param LinkedInClient $client
     * @param ReportShareNewsItem $reportShareNewsItem
     * @param CacheManager $cacheManager
     */
    public function __construct(EntityManager $em, CTAService $ctaService, LinkedInClient $client, ReportShareNewsItem $reportShareNewsItem, CacheManager $cacheManager)
    {
        $this->em = $em;
        $this->ctaService = $ctaService;
        $this->client = $client;
        $this->reportShareNewsItem = $reportShareNewsItem;
        $this->cacheManager = $cacheManager;
    }

    /**
     * @param string $operationId
     * @return string
     * @throws \Exception
     */
    public function execute($operationId)
    {
        /** @var NewsItem $newsItem */
        $newsItem = $this->em
            ->getRepository('CampaignChainOperationLinkedInBundle:NewsItem')
            ->findOneByOperation($operationId);

        if (!$newsItem) {
            throw new \Exception('No news item found for an operation with ID: '.$operationId);
        }

        // if the message does not contain a url, we need to skip the content block
        if (is_null($newsItem->getLinkUrl())) {
            $content = [
                'comment' => $newsItem->getMessage(),
                'visibility' => [
                    'code' => 'anyone',
                ],
            ];
        } else {
            /*
             * process urls and add tracking
             * important: both the urls in the message and submitted url field must be identical
            */

            $newsItem->setLinkUrl(
                $this->ctaService->processCTAs($newsItem->getLinkUrl(), $newsItem->getOperation(), 'txt')->getContent()
            );
            $newsItem->setMessage(
                $this->ctaService->processCTAs($newsItem->getMessage(), $newsItem->getOperation(), 'txt')->getContent()
            );

            $content = [
                'comment' => $newsItem->getMessage(),
                'content' => [
                    'title' => $newsItem->getLinkTitle(),
                    'description' => $newsItem->getLinkDescription(),
                    'submitted-url' => $newsItem->getLinkUrl(),
                ],
                'visibility' => [
                    'code' => 'anyone',
                ],
            ];

            //have images?
            //At this point LinkedIn accept an image link only if we provide a submitted-url
            $images = $this->em
                ->getRepository('CampaignChainHookImageBundle:Image')
                ->getImagesForOperation($newsItem->getOperation());

            if ($images) {
                //Linkedin can handle only 1 image
                $content['content']['submitted-image-url'] = $this->cacheManager
                    ->getBrowserPath($images[0]->getPath(), "auto_rotate");

            }
        }

        $activity = $newsItem->getOperation()->getActivity();
        $locationModuleIdentifier = $activity->getLocation()->getLocationModule()->getIdentifier();
        $isCompanyPageShare = 'campaignchain-linkedin-page' == $locationModuleIdentifier;

        $connection = $this->client->getConnectionByActivity($activity);

        if ($isCompanyPageShare) {
            $response = $connection->shareOnCompanyPage($activity, $content);
        } else {
            $response = $connection->shareOnUserPage($content);
        }

        $newsItem->setUrl($response['updateUrl']);
        $newsItem->setUpdateKey($response['updateKey']);

        // Set Operation to closed.
        $newsItem->getOperation()->setStatus(Action::STATUS_CLOSED);

        $location = $newsItem->getOperation()->getLocations()[0];
        $location->setIdentifier($response['updateKey']);
        $location->setUrl($response['updateUrl']);
        $location->setName($newsItem->getOperation()->getName());
        $location->setStatus(Medium::STATUS_ACTIVE);

        // Schedule data collection for report
        $this->reportShareNewsItem->schedule($newsItem->getOperation());

        $this->em->flush();

        $this->message = 'The message "'.$newsItem->getMessage().'" with the ID "'.$newsItem->getUpdateKey().'" has been posted on LinkedIn. See it on LinkedIn: <a href="'.$newsItem->getUrl().'">'.$newsItem->getUrl().'</a>';

        return self::STATUS_OK;
    }

    public function getMessage(){
        return $this->message;
    }
}