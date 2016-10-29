<?php
/*
 * Copyright 2016 CampaignChain, Inc. <info@campaignchain.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace CampaignChain\Operation\LinkedInBundle\Job;

use CampaignChain\Channel\LinkedInBundle\REST\LinkedInClient;
use CampaignChain\CoreBundle\Entity\SchedulerReportOperation;
use CampaignChain\CoreBundle\EntityService\FactService;
use CampaignChain\CoreBundle\Job\JobReportInterface;
use CampaignChain\Operation\LinkedInBundle\Entity\NewsItem;
use Doctrine\Common\Persistence\ManagerRegistry;

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
     * @var Registry
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
     * @param Registry  $em
     * @param FactService    $factService
     * @param LinkedInClient $client
     */
    public function __construct(ManagerRegistry $managerRegistry, FactService $factService, LinkedInClient $client)
    {
        $this->em = $managerRegistry->getManager();
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

        $connection = $this->client->getConnectionByActivity($activity);
        $this->message = $connection->getCompanyUpdate($activity, $this->newsitem);

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