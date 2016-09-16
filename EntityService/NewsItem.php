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

    public function getContent(Operation $operation)
    {
        return $this->getNewsItemByOperation($operation->getId());
    }

    /**
     * @param $id
     * @return \CampaignChain\Operation\LinkedInBundle\Entity\NewsItem
     * @throws \Exception
     * @deprecated Use getContent(Operation $operation) instead.
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
