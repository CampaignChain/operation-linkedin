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

use CampaignChain\CoreBundle\Entity\ReportAnalyticsActivityFact;
use CampaignChain\CoreBundle\Entity\SchedulerReportOperation;
use CampaignChain\CoreBundle\Job\JobReportInterface;
use Doctrine\ORM\EntityManager;

class ReportShareNewsItem implements JobReportInterface
{
    const BUNDLE_NAME = 'campaignchain/operation-linkedin';
    const METRIC_LIKES = 'Likes';
    const METRIC_SHARES = 'Shares';
    const METRIC_COMMENTS = 'Comments';

    protected $em;
    protected $container;

    protected $message;

    protected $newsitem;

    public function __construct(EntityManager $em, $container)
    {
        $this->em = $em;
        $this->container = $container;
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
//        $facts[self::METRIC_SHARES] = 0;
        $facts[self::METRIC_COMMENTS] = 0;

        $factService = $this->container->get('campaignchain.core.fact');
        $factService->addFacts('activity', self::BUNDLE_NAME, $operation, $facts);
    }

    public function execute($operationId)
    {
        $this->newsitem = $this->em->getRepository('CampaignChainOperationLinkedInBundle:NewsItem')->findOneByOperation($operationId);
        if (!$this->newsitem) {
            throw new \Exception('No Linkedin news item found for an operation with ID: '.$operationId);
        }

        $channel = $this->container->get('campaignchain.channel.linkedin.rest.client');
        $connection = $channel->connectByActivity($this->newsitem->getOperation()->getActivity());

        // Get the data of the item as stored by Linkedin
        $request = $connection->get('people/~/network/updates/key='.$this->newsitem->getUpdateKey().'?format=json');
        $response = $request->send()->json();

        ob_start();
        print_r($response);
        $this->message = ob_get_clean();

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
//        $facts[self::METRIC_SHARES] = $shares;
        $facts[self::METRIC_COMMENTS] = $comments;

        $factService = $this->container->get('campaignchain.core.fact');
        $factService->addFacts('activity', self::BUNDLE_NAME, $this->newsitem->getOperation(), $facts);

        //$this->message = 'Added to report: likes = '.$likes.', comments = '.$comments.'.';

        return self::STATUS_OK;
    }

    public function getMessage(){
        return $this->message;
    }
}