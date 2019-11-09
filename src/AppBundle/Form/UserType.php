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

        // On ajoute une fonction qui va écouter un évènement
        // 1er argument : L'évènement qui nous intéresse : ici, PRE_SET_DATA
        // 2e argument : La fonction à exécuter lorsque l'évènement est déclenché
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            // On récupère notre objet User sous-jacent
            $user = $event->getData();

//            \var_dump($user);

            if ((null === $user) || (null === $user->getUsername())) {
                $event->getForm()->add('roles', UserRolesListChoiceType::class);
            } elseif (!\in_array('ROLE_ADMIN', $user->getRoles())) {
                $event->getForm()->add('roles', UserRolesListChoiceType::class, [
                    'data' => ['ROLE_USER']
                ]);
            } else {
                $event->getForm()->add('roles', UserRolesListChoiceType::class, [
                    'data' => ['ROLE_ADMIN']
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
