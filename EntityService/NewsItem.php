<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) Sandro Groganz <sandro@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\Operation\LinkedInBundle\EntityService;

use Doctrine\ORM\EntityManager;

class NewsItem
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getNewsItemByOperation($id){
        $newsitem = $this->em->getRepository('CampaignChainOperationLinkedInBundle:NewsItem')
            ->findOneByOperation($id);

        if (!$newsitem) {
            throw new \Exception(
                'No news item found by operation id '.$id
            );
        }

        return $newsitem;
    }
}