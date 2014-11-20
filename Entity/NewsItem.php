<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) Sandro Groganz <sandro@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\Operation\LinkedInBundle\Entity;

use CampaignChain\CoreBundle\Entity\Meta;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="campaignchain_operation_linkedin_news_item")
 */
class NewsItem extends Meta
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\OneToOne(targetEntity="CampaignChain\CoreBundle\Entity\Operation", cascade={"persist"})
     */
    protected $operation;

    /**
     * @ORM\Column(type="text")
     */
    protected $message;
    
    /**
     * @ORM\Column(type="text")
     */
    protected $linkTitle;

    /**
     * @ORM\Column(type="text")
     */
    protected $linkDescription;
    
    /**
     * URL included within the share content
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $linkUrl;

    /**
     * direct URL to the share
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $url;
    
    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set message
     *
     * @param string $message
     * @return Status
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get message
     *
     * @return string 
     */
    public function getMessage()
    {
        return $this->message;
    }
    
    /**
     * Set title
     *
     * @param string $linkTitle
     * @return Status
     */
    public function setLinkTitle($linkTitle)
    {
        $this->linkTitle = $linkTitle;

        return $this;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getLinkTitle()
    {
        return $this->linkTitle;
    }
    
    /**
     * Set description
     *
     * @param string $linkDescription
     * @return Status
     */
    public function setLinkDescription($linkDescription)
    {
        $this->linkDescription = $linkDescription;

        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getLinkDescription()
    {
        return $this->linkDescription;
    }    

    /**
     * Set submit URL
     *
     * @param string $linkUrl
     * @return Status
     */
    public function setLinkUrl($linkUrl)
    {
        $this->linkUrl = $linkUrl;

        return $this;
    }

    /**
     * Get submit URL
     *
     * @return string 
     */
    public function getLinkUrl()
    {
        return $this->linkUrl;
    }        

    /**
     * Set url
     *
     * @param string $url
     * @return Status
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url
     *
     * @return string 
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set operation
     *
     * @param \CampaignChain\CoreBundle\Entity\Operation $operation
     * @return Status
     */
    public function setOperation(\CampaignChain\CoreBundle\Entity\Operation $operation = null)
    {
        $this->operation = $operation;

        return $this;
    }

    /**
     * Get operation
     *
     * @return \CampaignChain\CoreBundle\Entity\Operation
     */
    public function getOperation()
    {
        return $this->operation;
    }
}
