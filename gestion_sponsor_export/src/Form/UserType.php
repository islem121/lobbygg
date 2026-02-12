<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username')
            ->add('email')
            ->add('password')
            ->add('nom')
            ->add('prenom')
            ->add('bio')
            ->add('telephone')
            ->add('role', ChoiceType::class, [
                'choices' => [
                    'Joueur' => User::ROLE_CLIENT,
                    'Admin' => User::ROLE_ADMIN,
                    'Sponsor' => User::ROLE_SPONSOR,
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
