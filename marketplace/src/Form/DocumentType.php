<?php

namespace App\Form;

use App\Entity\Document;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class DocumentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomClient', TextType::class, [
                'label' => 'Nom Complet',
                'disabled' => false,
                'attr' => ['class' => 'cyber-input-field']
            ])
            ->add('emailClient', TextType::class, [
                'label' => 'Email',
                'disabled' => false,
                'attr' => ['class' => 'cyber-input-field']
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Message',
                'attr' => [
                    'placeholder' => 'Votre message pour le sponsor...',
                    'class' => 'cyber-input-field',
                    'rows' => 3
                ]
            ])
            ->add('motivation', TextareaType::class, [
                'label' => 'Pourquoi avez-vous besoin de cette sponsorisation ?',
                'attr' => [
                    'placeholder' => 'Expliquez vos motivations...',
                    'class' => 'cyber-input-field',
                    'rows' => 4
                ]
            ])
            ->add('dossierFile', FileType::class, [
                'label' => 'Dossier (PDF, Image)',
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'cyber-input-field'],
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'application/pdf',
                            'application/x-pdf',
                            'image/jpeg',
                            'image/png',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger un document PDF ou une image valide (JPG, PNG)',
                    ])
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Document::class,
            'attr' => ['novalidate' => 'novalidate'],
            'required' => false,
        ]);
    }
}
