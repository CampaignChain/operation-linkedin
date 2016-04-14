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
use CampaignChain\CoreBundle\Entity\SchedulerReportOperation;
use CampaignChain\CoreBundle\EntityService\FactService;
use CampaignChain\CoreBundle\Job\JobReportInterface;
use CampaignChain\Operation\LinkedInBundle\Entity\NewsItem;
use Doctrine\ORM\EntityManager;

/**
 * Class ReportShareNewsItem
 * @package CampaignChain\Operation\LinkedInBundle\Job
 */
class ReportShareNewsItem implements JobReportInterface
{
    const BUNDLE_NAME = 'campaignchain/operation-linkedin';
    const METRIC_LIKES = 'Likes';
    const METRIC_SHARES = 'Shares';
    const METRIC_COMMENTS = 'Comments';

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var FactService
     */
    protected $factService;

    /**
     * @var LinkedInClient
     */
    protected $client;

    /**
     * @var string
     */
    protected $message;

    /**
     * @var NewsItem
     */
    protected $newsitem;

    /**
     * ReportShareNewsItem constructor.
     *
     * @param EntityManager  $em
     * @param FactService    $factService
     * @param LinkedInClient $client
     */
    public function __construct(EntityManager $em, FactService $factService, LinkedInClient $client)
    {
        $this->em = $em;
        $this->factService = $factService;
        $this->client = $client;
    }

    public function schedule($operation, $facts = null)
    {
        $scheduler = new SchedulerReportOperation();
        $scheduler->setOperation($operation);
        $scheduler->setInterval('1 hour');
        $scheduler->setEndAction($operation->getActivity()->getCampaign());
        $this->em->persist($scheduler);

        // Add initial data to report.
        $this->newsitem = $this->em->getRepository('CampaignChainOperationLinkedInBundle:NewsItem')->findOneByOperation($operation);

        if (!$this->newsitem) {
            throw new \Exception('No Linkedin news item found for an operation with ID: '.$operation->getId());
        }

        $facts[self::METRIC_LIKES] = 0;
        $facts[self::METRIC_COMMENTS] = 0;

        $this->factService->addFacts('activity', self::BUNDLE_NAME, $operation, $facts);
    }

    /**
     * @param string $operationId
     * @return string
     * @throws \Exception
     */
    public function execute($operationId)
    {
        $this->newsitem = $this->em->getRepository('CampaignChainOperationLinkedInBundle:NewsItem')->findOneByOperation($operationId);
        if (!$this->newsitem) {
            throw new \Exception('No Linkedin news item found for an operation with ID: '.$operationId);
        }
        $activity = $this->newsitem->getOperation()->getActivity();

        $this->message = $this->client->getCompanyUpdate($activity, $this->newsitem);

        $likes = 0;
        if(isset($response['numLikes'])){
            $likes = $response['numLikes'];
        }

        $comments = 0;
        if(isset($response['updateComments']) && isset($response['updateComments']['_total'])){
            $comments = $response['updateComments']['_total'];
        }

        // Add report data.
        $facts[self::METRIC_LIKES] = $likes;
        $facts[self::METRIC_COMMENTS] = $comments;

        $this->factService->addFacts('activity', self::BUNDLE_NAME, $this->newsitem->getOperation(), $facts);

        return self::STATUS_OK;
    }

    public function getMessage(){
        return $this->message;
    }
}