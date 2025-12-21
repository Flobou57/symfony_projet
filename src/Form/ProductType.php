<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\ProductStatus;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null, [
                'label' => 'product.form.name',
                'translation_domain' => 'messages',
            ])
            ->add('price', null, [
                'label' => 'product.form.price',
                'translation_domain' => 'messages',
            ])
            ->add('description', null, [
                'label' => 'product.form.description',
                'translation_domain' => 'messages',
            ])
            ->add('stock', null, [
                'label' => 'product.form.stock',
                'translation_domain' => 'messages',
            ])
            ->add('status', EntityType::class, [
                'class' => ProductStatus::class,
                'choice_label' => 'label',
                'label' => 'product.form.status',
                'placeholder' => 'product.form.status_placeholder',
                'translation_domain' => 'messages',
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'product.form.image',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'product.form.image_mime',
                    ]),
                ],
                'translation_domain' => 'messages',
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'label' => 'product.form.category',
                'translation_domain' => 'messages',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
