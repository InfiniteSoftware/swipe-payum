<?php


namespace Payum\Swipe\Type;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\NotBlank;

class SwipeGatewayConfigurationType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('publicKey', TextType::class, [
                'label' => 'public key',
                'constraints' => [
                    new NotBlank([
                        'message' => 'sylius.gateway_config.paypal.username.not_blank',
                        'groups' => 'sylius',
                    ]),
                ],
            ])
            ->add('privateKey', TextType::class, [
                'label' => 'private key',
                'constraints' => [
                    new NotBlank([
                        'message' => 'sylius.gateway_config.paypal.password.not_blank',
                        'groups' => 'sylius',
                    ]),
                ],
            ])
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
                $data = $event->getData();

                $data['payum.http_client'] = '@sylius.payum.http_client';
            })
        ;
    }
}