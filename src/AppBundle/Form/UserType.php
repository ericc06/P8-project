<?php

namespace AppBundle\Form;

use AppBundle\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\CallbackTransformer;
use src\AppBundle\Service\UserManager;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', TextType::class, ['label' => "Nom d'utilisateur"])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'Les deux mots de passe doivent correspondre.',
                'required' => true,
                'first_options'  => ['label' => 'Mot de passe'],
                'second_options' => ['label' => 'Tapez le mot de passe à nouveau'],
            ])
            ->add('email', EmailType::class, ['label' => 'Adresse email'])
        ;

        // To add a correctly initialized "roles" select field, we add a function
        // that will listen to an event.
        // 1st argument: The event we want to listne to. Here, PRE_SET_DATA
        // 2nd argument: The funtion to be run whenthe event is triggered.
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            // We get our User entity
            $user = $event->getData();

            // If the user or his name is null, no existing value is selected in the select field.
            if ((null === $user) || (null === $user->getUsername())) {
                $event->getForm()->add('roles', UserRolesListChoiceType::class);
            // If the user has the 'ROLE_ADMIN' role, this value is selected in the select field.
            } elseif (\in_array('ROLE_ADMIN', $user->getRoles())) {
                $event->getForm()->add('roles', UserRolesListChoiceType::class, [
                    'data' => ['ROLE_ADMIN']
                ]);
            // Else, the user has the 'ROLE_USER' role and we select this value on form load.
            } else {
                $event->getForm()->add('roles', UserRolesListChoiceType::class, [
                    'data' => ['ROLE_USER']
                ]);          
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class
        ]);
    }
}
