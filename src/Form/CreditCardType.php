<?php

namespace App\Form;

use App\Entity\CreditCard;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CreditCardType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('number', TextType::class, [
                'label' => 'Numéro de carte',
                'attr' => ['maxlength' => 16, 'placeholder' => '1234 5678 9012 3456']
            ])
            ->add('expirationDate', TextType::class, [
                'label' => 'Date d’expiration (MM/YY)',
                'attr' => [
                    'placeholder' => '12/28',
                    // Autorise uniquement 12/25 ou toute date >= 01/26
                    'pattern' => '((12)/25|((0[1-9]|1[0-2])/(2[6-9]|[3-9][0-9])))',
                    'title' => 'MM/YY (min 12/25)'
                ]
            ])
            ->add('cvv', IntegerType::class, [
                'label' => 'Code CVV',
                'attr' => ['maxlength' => 3, 'placeholder' => '123']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CreditCard::class,
        ]);
    }
}
