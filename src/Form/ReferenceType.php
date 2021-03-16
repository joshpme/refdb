<?php

namespace App\Form;

use App\Entity\Conference;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReferenceType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('author')
            ->add('title')
            ->add('inProc', ChoiceType::class, array("label"=>"Presented as","choices"=>["Unpublished"=>0,"Published"=>1]))
            ->add('conference', EntityType::class,array("choice_label"=>"getPlain","class"=>Conference::class))
            ->add("paperId", null, array("label"=>"Paper ID"))
            ->add('position', null, array("label"=>"pp."))
            ->add('paperUrl', UrlType::class, ['required'=>'false', 'label'=>"Paper URL"])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'App\Entity\Reference'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'appbundle_reference';
    }


}
