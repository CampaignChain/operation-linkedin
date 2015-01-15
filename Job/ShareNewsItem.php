<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) Sandro Groganz <sandro@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\Operation\LinkedInBundle\Job;

use CampaignChain\CoreBundle\Entity\Action;
use Doctrine\ORM\EntityManager;
use CampaignChain\CoreBundle\Entity\Medium;
use CampaignChain\CoreBundle\Job\JobOperationInterface;
use Symfony\Component\HttpFoundation\Response;

class ShareNewsItem implements JobOperationInterface
{
    protected $em;
    protected $container;

    protected $message;
    protected $linkTitle;
    protected $linkDescription;
    protected $linkUrl;

    public function __construct(EntityManager $em, $container)
    {
        $this->em = $em;
        $this->container = $container;
    }

    public function execute($operationId)
    {
        $newsitem = $this->em->getRepository('CampaignChainOperationLinkedInBundle:NewsItem')->findOneByOperation($operationId);

        if (!$newsitem) {
            throw new \Exception('No news item found for an operation with ID: '.$operationId);
        }

        // Process the link URL to append the Tracking ID attached for
        // call to action tracking.
        $ctaService = $this->container->get('campaignchain.core.cta');
        $newsitem->setLinkUrl(
            $ctaService->processCTAs($newsitem->getLinkUrl(), $newsitem->getOperation(), 'txt')->getContent()
        );

        $client = $this->container->get('campaignchain.channel.linkedin.rest.client');
        $connection = $client->connectByActivity($newsitem->getOperation()->getActivity());
        
        $xmlBody = "<share><comment>" . $newsitem->getMessage() . "</comment><content><title>" . $newsitem->getLinkTitle() . "</title><description>" . $newsitem->getLinkDescription() . "</description><submitted-url>" . $newsitem->getLinkUrl() . "</submitted-url></content><visibility><code>anyone</code></visibility></share>";
        
        $request = $connection->post('people/~/shares', array('headers' => array('Content-Type' => 'application/xml')), $xmlBody);
        $response = $request->send()->xml();

        $newsitemUrl = (string)$response->{'update-url'};
        $newsitemId = (string)$response->{'update-key'};

        $newsitem->setUrl($newsitemUrl);
        $newsitem->setUpdateKey($newsitemId);

        // Get the data of the item as stored by Linkedin
        try {
            $request = $connection->get(
                'people/~/network/updates/key='.$newsitem->getUpdateKey().'?format=json'
            );
            $response = $request->send()->json();
            $newsitem->setLinkedinData($response);
        } catch (\Exception $e) {
            // TODO: Create a new job which gets the data later.
            // That job should check whether the raw data has meanwhile been
            // added (e.g. in the read view).
        }

        // Set Operation to closed.
        $newsitem->getOperation()->setStatus(Action::STATUS_CLOSED);

        $location = $newsitem->getOperation()->getLocations()[0];
        $location->setIdentifier($newsitemId);
        $location->setUrl($newsitemUrl);
        $location->setName($newsitem->getOperation()->getName());
        $location->setStatus(Medium::STATUS_ACTIVE);

        // Schedule data collection for report
        $report = $this->container->get('campaignchain.job.report.linkedin.share_news_item');
        $report->schedule($newsitem->getOperation());

        $this->em->flush();

        $this->message = 'The message "'.$newsitem->getMessage().'" with the ID "'.$newsitemId.'" has been posted on LinkedIn. See it on LinkedIn: <a href="'.$newsitemUrl.'">'.$newsitemUrl.'</a>';

        return self::STATUS_OK;
//            }
//            else {
//                // Handle errors, if authentication did not work.
//                // 1) Check if App is installed.
//                // 2) check if access token is valid and retrieve new access token if necessary.
//                // Log error, send email, prompt user, ask to check App Key and Secret or to authenticate again
//            }
    }

    public function getMessage(){
        return $this->message;
    }
}