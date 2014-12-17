<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) Sandro Groganz <sandro@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\Operation\LinkedInBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\ORM\EntityManager;

class ShareNewsItemOperationType extends AbstractType
{
    private $newsitem;
    private $view = 'default';
    protected $em;
    protected $container;

    public function __construct(EntityManager $em, ContainerInterface $container)
    {
        $this->em = $em;
        $this->container = $container;
    }

    public function setNewsItem($newsitem){
        $this->newsitem = $newsitem;
    }

    public function setView($view){
        $this->view = $view;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('message', 'textarea', array(
                'property_path' => 'message',
                'label' => false,
                'attr' => array(
                    'placeholder' => 'Add message...',
                    'max_length' => 200
                )
            ));
        $builder
            ->add('linkTitle', 'text', array(
                'property_path' => 'linkTitle',
                'label' => 'Title of page being shared',
                'attr' => array(
                    'placeholder' => 'Add title...',
                    'max_length' => 140
                )
            ));
        $builder
            ->add('description', 'textarea', array(
                'property_path' => 'linkDescription',
                'label' => 'Description of page being shared',
                'attr' => array(
                    'placeholder' => 'Add description...',
                    'max_length' => 300
                )
            ));
        $builder
            ->add('submitUrl', 'url', array(
                'property_path' => 'linkUrl',
                'label' => 'URL of page being shared',
                'attr' => array(
                    'placeholder' => 'Add URL...',
                    'max_length' => 255
                )
            ));            
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $defaults = array(
            'data_class' => 'CampaignChain\Operation\LinkedInBundle\Entity\NewsItem',
        );

        if($this->newsitem){
            $defaults['data'] = $this->newsitem;
        }
        $resolver->setDefaults($defaults);
    }

    public function getName()
    {
        return 'campaignchain_operation_linkedin_share_news_item';
    }
}