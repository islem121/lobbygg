<?php

namespace App\Form;

use App\Entity\Post;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class PostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('content', TextareaType::class, [
                'label' => false,
                'required' => false,
                'attr' => [
                    'placeholder' => 'Write something that helps your squad level up...',
                    'class' => 'form-control',
                    'rows' => 3
                ]
            ])
            ->add('image', FileType::class, [
                'label' => 'Media',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '10240k',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'video/mp4',
                            'video/mpeg'
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image or video',
                    ])
                ],
                'attr' => ['class' => 'd-none', 'id' => 'post_image_input']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Post::class,
        ]);
    }
}
