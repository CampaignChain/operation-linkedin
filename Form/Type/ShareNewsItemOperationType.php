<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain, Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\Operation\LinkedInBundle\Form\Type;

use CampaignChain\CoreBundle\Form\Type\OperationType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ShareNewsItemOperationType extends OperationType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('message', 'textarea', array(
                'property_path' => 'message',
                'label' => false,
                'attr' => array(
                    'placeholder' => 'Add message...',
                    'maxlength' => 200
                )
            ));

    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $defaults = array(
            'data_class' => 'CampaignChain\Operation\LinkedInBundle\Entity\NewsItem',
        );

        if($this->content){
            $defaults['data'] = $this->content;
        }
        $resolver->setDefaults($defaults);
    }

    public function getName()
    {
        return 'campaignchain_operation_linkedin_share_news_item';
    }
}