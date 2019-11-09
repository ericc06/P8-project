<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class UserRolesListChoiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if(!isset($options['data'])) {
            $data = null;
        }
        else {
            $data = $options['data'];
        }

        $builder
            ->add('roles', ChoiceType::class, [
                'label' => "Rôle(s)",
                'choices'  => [
                    '4 Simple utilisateur' => 'ROLE_USER',
                    'Administrateur' => 'ROLE_ADMIN',
                    //'' => null // Array to string conversion error if removed !!! TODO
                ],
                // To keep option values set as wanted, and not replaced with numbers.
                'choice_value' => function ($choice) {
                    return $choice;
                },
                'placeholder' => 'Choisissez un role',
                'expanded' => false,
                'required' => true,
                'multiple' => false,
                'data' => $data
                //'data' => 'ROLE_ADMIN'
                ])
        ;

        // See https://symfony.com/doc/3.4/form/data_transformers.html
        $builder->get('roles')
            ->addModelTransformer(new CallbackTransformer(
                // Note: this function is not used. It was just left here as an example.
                function ($rolesAsArray) {
                    // transform the array back to a string
                    // Note: In the Symfony documentation, implode() is used, but it generates
                    // a type error here. So we use explode() to avoid the PHP error.
                    //return explode(', ', $rolesAsArray);
                    //\var_dump($rolesAsString);
                    if ($rolesAsArray === null) {
                        return null;
                    }
                    if (\in_array('ROLE_ADMIN', $rolesAsArray)) {
                        return 'ROLE_ADMIN';
                    }
                    return 'ROLE_USER';
                },
                function ($rolesAsString) {
                    // transform the string to an array
                    //\var_dump($rolesAsString);
                    return [$rolesAsString];
                }
            ))
        ;
    }

    /*public function getParent()
    {
        return ChoiceType::class;
    }*/

    public function getName()
    {
        return 'userRolesListCustom';
    }
}
