<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', TextType::class, ['label' => 'login_username'])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'both_passwords_must_match',
                'required' => true,
                'first_options' => ['label' => 'login_password'],
                'second_options' => ['label' => 'type_password_again'],
            ])
            ->add('email', EmailType::class, ['label' => 'email_address'])
        ;

        // To add a correctly initialized "roles" select field, we add a function
        // that will listen to an event.
        // 1st argument: The event we want to listen to. Here, PRE_SET_DATA
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
                    'data' => ['ROLE_ADMIN'],
                ]);
            // Else, the user has the 'ROLE_USER' role and we select this value on form load.
            } else {
                $event->getForm()->add('roles', UserRolesListChoiceType::class, [
                    'data' => ['ROLE_USER'],
                ]);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
