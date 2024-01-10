<?php

namespace App\Form;

use App\Entity\Country;
use App\Entity\Episode;
use App\Entity\Series;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('email')
            ->add('password')
            ->add('registerDate')
            ->add('admin')
            ->add('userId')
            ->add('country', EntityType::class, [
                'class' => Country::class,
'choice_label' => 'id',
            ])
            ->add('series', EntityType::class, [
                'class' => Series::class,
'choice_label' => 'id',
'multiple' => true,
            ])
            ->add('episode', EntityType::class, [
                'class' => Episode::class,
'choice_label' => 'id',
'multiple' => true,
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
