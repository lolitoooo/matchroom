<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class AdminUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstname', TextType::class, [
                'label' => 'Prénom',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer un prénom',
                    ]),
                ],
            ])
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer un nom',
                    ]),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer un email',
                    ]),
                    new Email([
                        'message' => 'Veuillez entrer un email valide',
                    ]),
                ],
            ])
            ->add('phone', TextType::class, [
                'label' => 'Téléphone',
                'required' => false,
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Mot de passe',
                'required' => $options['is_new'],
                'mapped' => false,
                'attr' => [
                    'autocomplete' => 'new-password',
                ],
                'constraints' => $options['is_new'] ? [
                    new NotBlank([
                        'message' => 'Veuillez entrer un mot de passe',
                    ]),
                ] : [],
            ])
            ->add('userRole', ChoiceType::class, [
                'label' => 'Rôle',
                'choices' => [
                    'Utilisateur' => 'ROLE_USER',
                    'Hôtelier' => 'ROLE_HOTEL',
                    'Administrateur' => 'ROLE_ADMIN',
                ],
                'mapped' => false,
                'multiple' => false,
                'expanded' => true,
            ])
            ->add('banned', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Actif' => false,
                    'Banni' => true,
                ],
                'expanded' => true,
            ])
        ;
        
        // Pré-remplir le rôle avec un écouteur d'événement
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $user = $event->getData();
            $form = $event->getForm();
            
            if (!$user instanceof User) {
                return;
            }
            
            // Déterminer le rôle par défaut
            $defaultRole = 'ROLE_USER';
            $roles = $user->getRoles();
            
            if (in_array('ROLE_ADMIN', $roles)) {
                $defaultRole = 'ROLE_ADMIN';
            } elseif (in_array('ROLE_HOTEL', $roles)) {
                $defaultRole = 'ROLE_HOTEL';
            }
            
            // Remplacer le champ userRole avec la valeur par défaut
            $form->add('userRole', ChoiceType::class, [
                'label' => 'Rôle',
                'choices' => [
                    'Utilisateur' => 'ROLE_USER',
                    'Hôtelier' => 'ROLE_HOTEL',
                    'Administrateur' => 'ROLE_ADMIN',
                ],
                'mapped' => false,
                'multiple' => false,
                'expanded' => true,
                'data' => $defaultRole,
            ]);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_new' => false,
        ]);
    }
}
