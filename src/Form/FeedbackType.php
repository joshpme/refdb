<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Regex;

class FeedbackType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title')
            ->add('author')
            ->add('position', null, array("label"=>"pp.", "constraints"=>[new Regex(["pattern"=>"/^[0-9]+(-[0-9]+)?$/"])]))
            ->add('customDoi', TextType::class, ['required'=>false, 'label'=>"DOI (excluding doi: prefix)", "constraints"=>[new Regex(["pattern"=>"/^10.\d{4,9}\/[-._;()\/:A-Z0-9]+$/i"])]])
            ->add('feedback', TextareaType::class, ["label"=>"If other issue (please describe)", "required"=>false, "attr"=>["rows"=>5]])
            ->add('email', EmailType::class, ["label"=>"Your contact email address (optional)", "required"=>false])
            ;
    }/**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'App\Entity\Feedback'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'appbundle_feedback';
    }


}
