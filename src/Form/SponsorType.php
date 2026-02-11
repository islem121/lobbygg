<?php

namespace App\Form;

use App\Entity\Sponsor;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SponsorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomSociete', TextType::class, [
                'label' => 'Nom de la Société',
                'attr' => [
                    'placeholder' => 'Entrez le nom de votre société',
                    'class' => 'cyber-input-field'
                ],
                'disabled' => true,
            ])
            ->add('targetType', ChoiceType::class, [
                'label' => 'Type de Cible',
                'choices' => [
                    'Client' => 'client',
                    'Tournament' => 'tournament',
                ],
                'attr' => [
                    'class' => 'cyber-input-field'
                ]
            ])
            ->add('amount', NumberType::class, [
                'label' => 'Montant du Sponsoring (DT)',
                'attr' => [
                    'placeholder' => '0.00',
                    'class' => 'cyber-input-field'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description de l\'offre',
                'attr' => [
                    'placeholder' => 'Décrivez votre offre de sponsoring...',
                    'class' => 'cyber-input-field',
                    'rows' => 5
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Sponsor::class,
        ]);
    }
}
