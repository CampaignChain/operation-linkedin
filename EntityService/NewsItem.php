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

/**
 * Class NewsItem
 * @package CampaignChain\Operation\LinkedInBundle\EntityService
 */
class NewsItem implements OperationServiceInterface
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * NewsItem constructor.
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @param $id
     * @return \CampaignChain\Operation\LinkedInBundle\Entity\NewsItem
     * @throws \Exception
     */
    public function getNewsItemByOperation($id)
    {
        $newsitem = $this->em->getRepository('CampaignChainOperationLinkedInBundle:NewsItem')
            ->findOneByOperation($id);

        if (!$newsitem) {
            throw new \Exception(
                'No news item found by operation id '.$id
            );
        }

        return $newsitem;
    }

    /**
     * @param Operation $oldOperation
     * @param Operation $newOperation
     */
    public function cloneOperation(Operation $oldOperation, Operation $newOperation)
    {
        $newsItem = $this->getNewsItemByOperation($oldOperation);
        $clonedNewsItem = clone $newsItem;
        $clonedNewsItem->setOperation($newOperation);
        $this->em->persist($clonedNewsItem);
        $this->em->flush();
    }

    /**
     * @param $id
     */
    public function removeOperation($id){
        try {
            $operation = $this->getNewsItemByOperation($id);
            $this->em->remove($operation);
            $this->em->flush();
        } catch (\Exception $e) {

        }
    }
}
