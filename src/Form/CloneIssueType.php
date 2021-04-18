<?php

namespace App\Form;

use function Sodium\add;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CloneIssueType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('issue', TextType::class)
            ->add('tracker', ChoiceType::class, [
                'choices' => $options['trackers'],
                'placeholder' => 'Choose a tracker',
                'required' => false
            ])
            ->add('project', ChoiceType::class, [
                'choices' => $options['projects'],
                'placeholder' => 'Choose a project',
                'required' => false
            ])
            ->add('submit', SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'trackers' => [],
            'projects' => [],
            'statuses' => [],
        ]);
    }
}
