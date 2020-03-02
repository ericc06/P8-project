<?php

namespace App\Form;

use App\Entity\Task;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TaskType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'title',
            ])
            ->add('content', TextareaType::class, [
                'label' => 'content',
            ])
            //->add('author') ===> must be the authenticated user
        ;
    }

    /**
     *  Additional fields (if you want to edit them), the values shown are the default.
     *
     * 'csrf_protection' => true,
     * 'csrf_field_name' => '_token', // This must match in your test
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Task::class,
            // a unique key to help generate the secret token
            'intention' => 'task_type',
            //'csrf_protection' => false,
        ]);
    }
}
