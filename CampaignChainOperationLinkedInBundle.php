<?php

namespace CampaignChain\Operation\LinkedInBundle;

use CampaignChain\Operation\LinkedInBundle\DependencyInjection\CampaignChainOperationLinkedInExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class CampaignChainOperationLinkedInBundle extends Bundle
{
    public function getContainerExtension()
    {
        return new CampaignChainOperationLinkedInExtension();
    }
}
