<?php

namespace App\Form\Type;

use App\Entity\Answer;
use App\Entity\Question;
use App\Entity\SessionResponse;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SessionResponseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('answer', EntityType::class, [
                'class'         => Answer::class,
                'query_builder' => function (EntityRepository $er) use ($options) {
                    return $er->createQueryBuilder('a')
                        ->andWhere('a.question = :question')
                        ->setParameter('question', $options['question']->getId())
                        ->orderBy('RAND()');
                },
                'choice_label'  => 'value',
                'expanded'      => true,
                'multiple'      => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'        => SessionResponse::class,
            'csrf_protection'   => true,
            'csrf_field_name'   => '_token',
            'csrf_token_id'     => 'response_answer_session_item',
            'question'          => null,
        ]);

        $resolver->setAllowedTypes('question', [Question::class, 'null']);
        parent::configureOptions($resolver);
    }
}