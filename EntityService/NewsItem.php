<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain, Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\Operation\LinkedInBundle\EntityService;

use Doctrine\ORM\EntityManager;
use CampaignChain\CoreBundle\EntityService\OperationServiceInterface;
use CampaignChain\CoreBundle\Entity\Operation;

class NewsItem implements OperationServiceInterface
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

    public function cloneOperation(Operation $oldOperation, Operation $newOperation)
    {
        $newsItem = $this->getNewsItemByOperation($oldOperation);
        $clonedNewsItem = clone $newsItem;
        $clonedNewsItem->setOperation($newOperation);
        $this->em->persist($clonedNewsItem);
        $this->em->flush();
    }
    
    public function removeOperation($id){
        try {
            $operation = $this->getNewsItemByOperation($id);
            $this->em->remove($operation);
            $this->em->flush();
        } catch (\Exception $e) {

        }
    }
}
