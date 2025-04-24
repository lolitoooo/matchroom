<?php

namespace App\Form;

use App\Entity\Negotiation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;

class NegotiationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('startDate', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date d\'arrivée',
                'constraints' => [
                    new NotBlank(),
                    new GreaterThanOrEqual([
                        'value' => 'today',
                        'message' => 'La date d\'arrivée doit être aujourd\'hui ou ultérieure.'
                    ]),
                ],
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('endDate', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de départ',
                'constraints' => [
                    new NotBlank(),
                    new GreaterThan([
                        'propertyPath' => 'parent.all[startDate].data',
                        'message' => 'La date de départ doit être ultérieure à la date d\'arrivée.'
                    ]),
                ],
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('proposedPrice', MoneyType::class, [
                'currency' => 'EUR',
                'label' => 'Prix proposé',
                'constraints' => [
                    new NotBlank(),
                    new GreaterThan([
                        'value' => 0,
                        'message' => 'Le prix proposé doit être supérieur à 0.'
                    ]),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Entrez votre offre'
                ]
            ])
            ->add('hotelResponse', TextareaType::class, [
                'label' => 'Message (optionnel)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Ajoutez un message à votre offre (facultatif)'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Negotiation::class,
        ]);
    }
}
