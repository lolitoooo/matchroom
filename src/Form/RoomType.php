<?php

namespace App\Form;

use App\Entity\RoomType as EntityRoomType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;

class RoomType extends AbstractType
{
    public const AMENITIES = [
        'Salle de bain' => [
            'Salle de bain' => 'bathroom',
            'Sèche-cheveux' => 'hairdryer',
            'Produits de nettoyage' => 'cleaning_products',
            'Shampoing vega' => 'vegan_shampoo',
            'Savon pour le corps vega' => 'vegan_soap',
            'Eau chaude' => 'hot_water',
            'Gel douche' => 'shower_gel',
        ],
        'Chambre et linge' => [
            'Équipements de base (serviettes, draps, savon, papier toilette)' => 'basic_essentials',
            'Cintres' => 'hangers',
            'Draps' => 'sheets',
            'Oreillers et couvertures supplémentaires' => 'extra_pillows_blankets',
            'Stores' => 'blinds',
            'Fer à repasser' => 'iron',
            'Espace de rangement pour les vêtements' => 'clothing_storage',
        ],
        'Divertissement' => [
            'Connexion Ethernet' => 'ethernet',
            'TV HD avec Netflix' => 'hd_tv_netflix',
        ],
        'Chauffage et climatisation' => [
            'Ventilateurs portables' => 'portable_fans',
            'Chauffage' => 'heating',
        ],
        'Sécurité' => [
            'Détecteur de fumée' => 'smoke_detector',
        ],
        'Internet et bureau' => [
            'Wifi' => 'wifi',
            'Wi-Fi portable' => 'portable_wifi',
        ],
        'Cuisine et salle à manger' => [
            'Cuisine' => 'kitchen',
            'Réfrigérateur' => 'refrigerator',
            'Four à micro-ondes' => 'microwave',
            'Équipements de cuisine de base' => 'basic_kitchen_equipment',
            'Vaisselle et couverts' => 'dishes_silverware',
            'Mini réfrigérateur' => 'mini_fridge',
            'Cuisinière à induction' => 'induction_stove',
            'Bouilloire électrique' => 'electric_kettle',
            'Cafetière' => 'coffee_maker',
            'Verres à vin' => 'wine_glasses',
            'Grille-pain' => 'toaster',
        ],
        'Caractéristiques de l\'emplacement' => [
            'Front de mer' => 'waterfront',
            'Accès plage ou bord de mer' => 'beach_access',
            'Entrée privée' => 'private_entrance',
            'Laverie automatique à proximité' => 'laundromat_nearby',
        ],
        'Extérieur' => [
            'Arrière-cour' => 'backyard',
        ],
        'Parking et installations' => [
            'Allée de stationnement gratuite sur place' => 'free_parking',
        ],
        'Services' => [
            'Arrivée autonome' => 'self_check_in',
            'Boîte à clé sécurisée' => 'lockbox',
        ],
        'Non inclus' => [
            'Caméras de surveillance extérieures présentes sur place' => 'outdoor_surveillance_cameras',
            'Lave-linge' => 'washer',
            'Sèche-linge' => 'dryer',
            'Climatisation' => 'air_conditioning',
            'Détecteur de monoxyde de carbone' => 'carbon_monoxide_detector',
        ],
    ];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Titre de la chambre',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer un titre pour la chambre',
                    ]),
                ],
                'attr' => [
                    'class' => 'mt-1 block w-full border border-primary-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm',
                    'placeholder' => 'Ex: Chambre Double Vue Mer'
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer une description pour la chambre',
                    ]),
                ],
                'attr' => [
                    'class' => 'mt-1 block w-full border border-primary-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm',
                    'rows' => 4,
                    'placeholder' => 'Décrivez cette chambre en détail...'
                ],
            ])
            ->add('capacity', IntegerType::class, [
                'label' => 'Nombre de voyageurs',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez indiquer le nombre de voyageurs',
                    ]),
                    new Positive([
                        'message' => 'Le nombre de voyageurs doit être positif',
                    ]),
                ],
                'attr' => [
                    'class' => 'mt-1 block w-full border border-primary-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm',
                    'min' => 1,
                ],
            ])
            ->add('roomCount', IntegerType::class, [
                'label' => 'Nombre de chambres',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez indiquer le nombre de chambres',
                    ]),
                    new Positive([
                        'message' => 'Le nombre de chambres doit être positif',
                    ]),
                ],
                'attr' => [
                    'class' => 'mt-1 block w-full border border-primary-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm',
                    'min' => 1,
                ],
            ])
            ->add('bedCount', IntegerType::class, [
                'label' => 'Nombre de lits',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez indiquer le nombre de lits',
                    ]),
                    new Positive([
                        'message' => 'Le nombre de lits doit être positif',
                    ]),
                ],
                'attr' => [
                    'class' => 'mt-1 block w-full border border-primary-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm',
                    'min' => 1,
                ],
            ])
            ->add('basePrice', MoneyType::class, [
                'label' => 'Prix par nuit',
                'required' => true,
                'currency' => 'EUR',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez indiquer le prix par nuit',
                    ]),
                    new Positive([
                        'message' => 'Le prix doit être positif',
                    ]),
                ],
                'attr' => [
                    'class' => 'mt-1 block w-full border border-primary-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm',
                    'placeholder' => 'Ex: 120.00',
                ],
            ])
            ->add('available', CheckboxType::class, [
                'label' => 'Disponible à la réservation',
                'required' => false,
                'attr' => [
                    'class' => 'h-4 w-4 text-primary-600 focus:ring-primary-500 border-primary-300 rounded',
                ],
                'label_attr' => [
                    'class' => 'ml-2 block text-sm text-primary-700',
                ],
            ])
            ->add('photos', FileType::class, [
                'label' => 'Photos de la chambre',
                'required' => false,
                'multiple' => true,
                'mapped' => false,
                'attr' => [
                    'class' => 'mt-1 block w-full border border-primary-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm',
                    'accept' => 'image/*',
                ],
                'help' => 'Sélectionnez une ou plusieurs photos pour illustrer cette chambre. Formats acceptés : JPG, PNG, GIF.',
                'help_attr' => [
                    'class' => 'text-sm text-primary-500 mt-1',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EntityRoomType::class,
        ]);
    }

    private function flattenAmenities(): array
    {
        $flattened = [];
        foreach (self::AMENITIES as $category => $items) {
            foreach ($items as $label => $value) {
                $flattened[$label] = $value;
            }
        }
        return $flattened;
    }
    
    public function buildAmenitiesForm(FormBuilderInterface $builder): void
    {
        foreach (self::AMENITIES as $category => $items) {
            $builder->add('amenities_' . strtolower(str_replace(' ', '_', $category)), ChoiceType::class, [
                'label' => $category,
                'required' => false,
                'expanded' => true,
                'multiple' => true,
                'mapped' => false,
                'choices' => array_flip($items),
                'choice_attr' => function($choice, $key, $value) {
                    return ['class' => 'h-4 w-4 text-primary-600 focus:ring-primary-500 border-primary-300 rounded'];
                },
                'label_attr' => [
                    'class' => 'font-medium text-primary-700 text-base mb-2 block',
                ],
                'row_attr' => [
                    'class' => 'bg-primary-50 p-4 rounded-lg mb-4',
                ],
            ]);
        }
    }
}
