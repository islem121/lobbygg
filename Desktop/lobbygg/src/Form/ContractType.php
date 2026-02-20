<?php

namespace App\Form;

use App\Entity\Contract;
use App\Entity\Document;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContractType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('content', TextareaType::class, [
                'label' => 'Contenu du Contrat',
                'attr' => ['rows' => 10]
            ])
            ->add('request', EntityType::class, [
                'class' => Document::class,
                'choice_label' => 'id',
                'label' => 'Demande associée'
            ])
            ->add('sponsor', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'username',
                'label' => 'Sponsor'
            ])
            ->add('client', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'username',
                'label' => 'Client'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Contract::class,
            'attr' => ['novalidate' => 'novalidate'],
            'required' => false,
        ]);
    }
}
