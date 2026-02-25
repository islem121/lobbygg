<?php

namespace App\Form;

use App\Entity\Tournament;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TournamentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Title',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
            ])
            ->add('startDate', DateTimeType::class, [
                'label' => 'Start date',
                'widget' => 'single_text',
                'input' => 'datetime',
            ])
            ->add('endDate', DateType::class, [
                'label' => 'End date',
                'widget' => 'single_text',
                'input' => 'datetime',
                'required' => false,
            ])
            ->add('maxPlayers', IntegerType::class, [
                'label' => 'Max players',
            ])
            ->add('mode', ChoiceType::class, [
                'label' => 'Mode',
                'choices' => [
                    'Solo' => Tournament::MODE_SOLO,
                    'Duo' => Tournament::MODE_DUO,
                    'Squad' => Tournament::MODE_SQUAD,
                ],
            ])
            ->add('entryFee', NumberType::class, [
                'label' => 'Entry fee',
                'scale' => 2,
                'html5' => true,
                'attr' => ['min' => 0, 'step' => 0.01],
            ])
            ->add('isAiGenerated', CheckboxType::class, [
                'label' => 'AI generated',
                'required' => false,
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => [
                    'Upcoming' => 'upcoming',
                    'Live' => 'live',
                    'Finished' => 'finished',
                    'Cancelled' => 'cancelled',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Tournament::class,
        ]);
    }
}

