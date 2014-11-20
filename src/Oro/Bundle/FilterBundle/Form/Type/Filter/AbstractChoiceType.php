<?php

namespace Oro\Bundle\FilterBundle\Form\Type\Filter;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Extension\Core\View\ChoiceView;

abstract class AbstractChoiceType extends AbstractType
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param FormView      $view
     * @param FormInterface $form
     * @param array         $options
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        if (!empty($view->children['value'])) {
            /** @var FormView $valueFormView */
            $valueFormView = $view->children['value'];
            if (!isset($options['field_options']['translatable_options'])
                || $options['field_options']['translatable_options']
            ) {
                $this->translateChoices($view, $valueFormView, $options);
            }
        }
    }

    /**
     * Translates choices
     *
     * @param FormView $view
     * @param FormView $valueFormView
     * @param array    $options
     */
    protected function translateChoices(FormView $view, FormView $valueFormView, array $options)
    {
        if (!empty($valueFormView->vars['choices'])) {
            // get translation domain
            $translationDomain = 'messages';
            if (!empty($options['translation_domain'])) {
                $translationDomain = $options['translation_domain'];
            } elseif (!empty($view->parent->vars['translation_domain'])) {
                $translationDomain = $view->parent->vars['translation_domain'];
            }

            // translate choice values
            /** @var $choiceView ChoiceView */
            foreach ($valueFormView->vars['choices'] as $key => $choiceView) {
                $choiceView->label = $this->translator->trans(
                    $choiceView->label,
                    array(),
                    $translationDomain
                );
                $valueFormView->vars['choices'][$key] = $choiceView;
            }
        }
    }
}
