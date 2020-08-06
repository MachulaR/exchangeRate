<?php
namespace App\Form;

use App\Entity\DTO\CurrencyInquiryDTO;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CurrencyInquiryType extends AbstractType
{
    const CURRENCY = [
        'Dolar amerykański (USD)' => 'USD',
        'Dolar australijski (AUD)' => 'AUD',
        'Dolar kanadyjski (CAD)' => 'CAD',
        'Euro (EUR)' => 'EUR',
        'Forint (HUF)' => 'HUF',
        'Frank szwajcarski (CHF)' => 'CHF',
        'Funt szterling (GBP)' => 'GBP',
        'jen (JPY)' => 'JPY',
        'Korona czeska (CZK)' => 'CZK',
        'Korona duńska (DKK)' => 'DKK',
        'Korona norweska (NOK)' => 'NOK',
        'Korona szwedzka (SEK)' => 'SEK',
    ];

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('Start_at', DateType::class, [
                'data' => new \DateTime(),
                'attr' => [
                    'style' =>'margin:10px 25px 10px 4px'
                ],
                'widget' => 'single_text',
                'label' => 'Start at: ',
            ])
            ->add('End_at', DateType::class, [
                'data' => new \DateTime(),
                'attr' => [
                    'style' =>'margin:10px 25px 10px 4px'
                ],
                'widget' => 'single_text',
                'label' => 'End at: ',
            ])
            ->add('Currency', ChoiceType::class, [
                'attr' => [
                    'style' =>'margin:10px 25px 10px 4px'
                ],
                'choices' => self::CURRENCY,
                'label' => 'Currency:',
            ])
            ->add('Check', SubmitType::class)
        ;
    }
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => CurrencyInquiryDTO::class,
        ]);
    }
}
