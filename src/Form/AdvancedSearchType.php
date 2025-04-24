<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdvancedSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('destination', TextType::class, [
                'label' => 'Destination',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ville, région, pays...',
                    'class' => 'w-full rounded-md border-gray-300 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50',
                    'autocomplete' => 'off'
                ]
            ])
            ->add('startDate', DateType::class, [
                'label' => 'Date d\'arrivée',
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'w-full rounded-md border-gray-300 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50'
                ]
            ])
            ->add('endDate', DateType::class, [
                'label' => 'Date de départ',
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'w-full rounded-md border-gray-300 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50'
                ]
            ])
            ->add('budget', MoneyType::class, [
                'label' => 'Budget maximum par nuit',
                'currency' => 'EUR',
                'required' => false,
                'attr' => [
                    'class' => 'w-full rounded-md border-gray-300 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50',
                    'placeholder' => 'Budget maximum'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'method' => 'GET',
        ]);
    }
}
