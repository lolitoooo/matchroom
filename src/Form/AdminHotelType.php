<?php

namespace App\Form;

use App\Entity\Hotel;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\File;

class AdminHotelType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Récupérer tous les utilisateurs avec le rôle HOTEL
        $entityManager = $options['entity_manager'];
        $userRepository = $entityManager->getRepository(User::class);
        $allUsers = $userRepository->findAll();
        
        // Filtrer les utilisateurs qui ont le rôle HOTEL
        $hotelUsers = [];
        foreach ($allUsers as $user) {
            if (in_array('ROLE_HOTEL', $user->getRoles())) {
                $hotelUsers[] = $user;
            }
        }
        
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de l\'hôtel',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer un nom pour l\'hôtel',
                    ]),
                ],
            ])
            ->add('address', TextType::class, [
                'label' => 'Adresse',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer une adresse',
                    ]),
                ],
                'attr' => [
                    'class' => 'address-autocomplete',
                    'data-latitude-field' => 'admin_hotel_latitude',
                    'data-longitude-field' => 'admin_hotel_longitude',
                ],
            ])
            ->add('latitude', TextType::class, [
                'label' => 'Latitude',
                'required' => false,
                'attr' => [
                    'readonly' => true,
                ],
            ])
            ->add('longitude', TextType::class, [
                'label' => 'Longitude',
                'required' => false,
                'attr' => [
                    'readonly' => true,
                ],
            ])
            ->add('user', EntityType::class, [
                'class' => User::class,
                'label' => 'Propriétaire',
                'choices' => $hotelUsers,
                'choice_label' => function (User $user) {
                    return $user->getFirstname() . ' ' . $user->getName() . ' (' . $user->getEmail() . ')';
                },
                'required' => false,
            ])
            ->add('profileImage', FileType::class, [
                'label' => 'Image de profil',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'maxSizeMessage' => 'Le fichier est trop volumineux ({{ size }} {{ suffix }}). La taille maximale autorisée est {{ limit }} {{ suffix }}.',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPEG ou PNG)',
                    ])
                ],
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'image/jpeg, image/png'
                ],
                'help' => 'Formats acceptés : JPG, PNG. Taille maximale : 2 Mo.'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Hotel::class,
        ]);
        
        $resolver->setRequired(['entity_manager']);
    }
}
