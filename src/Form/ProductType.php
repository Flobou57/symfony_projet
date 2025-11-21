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
                'label' => 'Nom du produit',
            ])
            ->add('price', null, [
                'label' => 'Prix (€)',
            ])
            ->add('description', null, [
                'label' => 'Description',
            ])
            ->add('stock', null, [
                'label' => 'Stock disponible',
            ])
            ->add('status', EntityType::class, [
                'class' => ProductStatus::class,
                'choice_label' => 'label',
                'label' => 'Statut du produit',
                'placeholder' => '— Choisir un statut —',
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Image du produit',
                'mapped' => false, // ne correspond pas directement à une propriété de l’entité Product
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Merci d\'ajouter une image au format JPEG, PNG ou WEBP',
                    ]),
                ],
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'label' => 'Catégorie',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
