<?php

namespace App\Form;

use App\Repository\BookingRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdvancedSearchType extends AbstractType
{
    private BookingRepository $bookingRepository;
    
    public function __construct(BookingRepository $bookingRepository)
    {
        $this->bookingRepository = $bookingRepository;
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Récupérer les dates déjà réservées
        $bookedDates = $this->bookingRepository->findAllBookedDates();
        $bookedDatesJson = json_encode($bookedDates);
        
        // Date minimale (aujourd'hui)
        $minDate = new \DateTime();
        
        $builder
            ->add('destination', TextType::class, [
                'label' => 'Destination',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Ville, région, pays...',
                    'class' => 'w-full rounded-md border-gray-300 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50',
                    'autocomplete' => 'off'
                ]
            ])
            ->add('startDate', DateType::class, [
                'label' => 'Date d\'arrivée',
                'widget' => 'single_text',
                'required' => true,
                'html5' => true,
                'attr' => [
                    'class' => 'w-full rounded-md border-gray-300 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50',
                    'min' => $minDate->format('Y-m-d'),
                    'data-booked-dates' => $bookedDatesJson
                ]
            ])
            ->add('endDate', DateType::class, [
                'label' => 'Date de départ',
                'widget' => 'single_text',
                'required' => true,
                'html5' => true,
                'attr' => [
                    'class' => 'w-full rounded-md border-gray-300 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50',
                    'min' => $minDate->format('Y-m-d'),
                    'data-booked-dates' => $bookedDatesJson
                ]
            ])
            ->add('persons', IntegerType::class, [
                'label' => 'Nombre de personnes',
                'required' => true,
                'attr' => [
                    'class' => 'w-full rounded-md border-gray-300 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50',
                    'min' => 1,
                    'value' => 1
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
