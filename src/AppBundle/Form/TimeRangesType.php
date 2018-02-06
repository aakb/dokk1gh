<?php

/*
 * This file is part of Gæstehåndtering.
 *
 * (c) 2017–2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TimeRangesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // A pseudo field used only for general error messages.
        $builder
            ->add('message', TextType::class, [
                'required' => false,
                'label_attr' => [
                    'class' => 'hidden',
                ],
                'attr' => [
                    'class' => 'hidden',
                ],
            ]);

        $timeChoices = [];

        for ($hours = 6; $hours <= 22; ++$hours) {
            for ($minutes = 0; $minutes < 60; $minutes += 30) {
                $key = sprintf('%02d:%02d', $hours, $minutes);
                $timeChoices[] = $key;
            }
        }
        array_pop($timeChoices);

        $startTimeChoices = array_combine($timeChoices, $timeChoices);
        array_pop($startTimeChoices);
        $endTimeChoices = array_combine($timeChoices, $timeChoices);
        array_shift($endTimeChoices);

        for ($day = 1; $day <= 7; ++$day) {
            $builder
                ->add('start_time_'.$day, ChoiceType::class, [
                    'required' => false,
                    'choices' => $startTimeChoices,
                ])
                ->add('end_time_'.$day, ChoiceType::class, [
                    'required' => false,
                    'choices' => $endTimeChoices,
                ]);
        }

        $builder
            ->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
                $form = $event->getForm();
                $numberOfTimeIntervals = 0;
                for ($day = 1; $day <= 7; ++$day) {
                    $startTime = $form->get('start_time_'.$day);
                    $endTime = $form->get('end_time_'.$day);

                    if ($startTime->getData() && !$endTime->getData()) {
                        $endTime->addError(new FormError('Please specify an end time'));
                    } elseif (!$startTime->getData() && $endTime->getData()) {
                        $startTime->addError(new FormError('Please specify a start time'));
                    } elseif ($startTime->getData() && $endTime->getData()
                        && $endTime->getData() <= $startTime->getData()) {
                        $endTime->addError(new FormError('End time must be after start time'));
                    } elseif ($startTime->getData() && $endTime->getData()) {
                        ++$numberOfTimeIntervals;
                    }
                }
                if (0 === $numberOfTimeIntervals) {
                    $form->get('message')->addError(new FormError('Please specify at least one time interval'));
                }
            });
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
                                   'view_timezone' => 'GMT',
                               ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'appbundle_time_ranges';
    }
}
