<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserRolesListChoiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // See https://symfony.com/doc/3.4/form/data_transformers.html
        // The CallbackTransformer takes two callback functions as arguments.
        // The first transforms the original value into a format that'll be used
        // to render the field. The second does the reverse: it transforms
        // the submitted value back into the format you'll use in your code.
        // In our case:
        // Original value format: array
        // Submitted value format: string
        $builder->addModelTransformer(new CallbackTransformer(
            // We get the roles array from the user entity, and we return a string
            // corresponding to the select field option values to initialize it.
            function ($rolesAsArray) {
                if ($rolesAsArray === null) {
                    return null;
                }
                if (\in_array('ROLE_ADMIN', $rolesAsArray)) {
                    return 'ROLE_ADMIN';
                }
                return 'ROLE_USER';
            },
            // We transform the string (the value of the selected option) into an array.
            function ($rolesAsString) {
                return (array) $rolesAsString;
            }
        ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'label' => "Rôle(s)",
            'choices'  => [
                'Simple utilisateur' => 'ROLE_USER',
                'Administrateur' => 'ROLE_ADMIN',
            ],
            // To keep the select field options values set as wanted and not replaced with numbers.
            // See https://stackoverflow.com/a/39411469/10980984
            'choice_value' => function ($choice) {
                return $choice;
            },
            'placeholder' => 'Choisissez un role',
            'expanded' => false,
            'required' => true,
            'multiple' => false
        ));
    }

    public function getParent()
    {
        return ChoiceType::class;
    }
}
