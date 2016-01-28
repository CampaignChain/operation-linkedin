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

use CampaignChain\CoreBundle\Entity\Action;
use CampaignChain\CoreBundle\Entity\CTAParserData;
use CampaignChain\CoreBundle\Exception\ExternalApiException;
use Doctrine\ORM\EntityManager;
use CampaignChain\CoreBundle\Entity\Medium;
use CampaignChain\CoreBundle\Job\JobActionInterface;
use Guzzle\Http\Exception\BadResponseException;

class ShareNewsItem implements JobActionInterface
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

        // if the message does not contain a url, we need to skip the content block
        if (is_null($newsitem->getLinkUrl())) {

            // only comment block
            $xmlBody = <<<XMLBODY
<share>
    <comment>{$newsitem->getMessage()}</comment>
    <visibility>
        <code>anyone</code>
    </visibility>
</share>
XMLBODY;

        } else {
            $ctaService = $this->container->get('campaignchain.core.cta');

            /*
             * process urls and add tracking
             * important: both the urls in the message and submitted url field must be identical
             *
            */

            $newsitem->setLinkUrl(
                $ctaService->processCTAs($newsitem->getLinkUrl(), $newsitem->getOperation(), 'txt')->getContent()
            );

            $newsitem->setMessage(
                $ctaService->processCTAs($newsitem->getMessage(), $newsitem->getOperation(), 'txt')->getContent()
            );

            $xmlBody = <<<XMLBODY
<share>
    <comment>{$newsitem->getMessage()}</comment>
    <content>
        <title>{$newsitem->getLinkTitle()}</title>
        <description>{$newsitem->getLinkDescription()}</description>
        <submitted-url>{$newsitem->getLinkUrl()}</submitted-url>
    </content>
    <visibility>
        <code>anyone</code>
    </visibility>
</share>
XMLBODY;

        }

        $client = $this->container->get('campaignchain.channel.linkedin.rest.client');
        $connection = $client->connectByActivity($newsitem->getOperation()->getActivity());

        $request = $connection->post('people/~/shares', array('headers' => array('Content-Type' => 'application/xml')), $xmlBody);

        try {
            $response = $request->send()->xml();
        } catch (BadResponseException $e) {
            throw new ExternalApiException($e->getMessage(), $e->getCode(), $e);
        }

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