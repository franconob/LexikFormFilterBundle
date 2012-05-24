<?php

namespace Lexik\Bundle\FormFilterBundle\Filter\Extension\Type;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilder;

use Lexik\Bundle\FormFilterBundle\Filter\Expr;
use Doctrine\ORM\QueryBuilder;

/**
 * Filter type for strings.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class TextFilterType extends TextType implements FilterTypeInterface
{
    const PATTERN_EQUALS     = '%s';
    const PATTERN_START_WITH = '%s%%';
    const PATTERN_END_WITH   = '%%%s';
    const PATTERN_CONTAINS   = '%%%s%%';

    const SELECT_PATTERN = 'select_pattern';

    protected $transformerId;

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilder $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $attributes          = array();
        $this->transformerId = 'lexik_form_filter.transformer.default';

        if ($options['condition_pattern'] == self::SELECT_PATTERN) {
            $textOptions             = array_intersect_key($options, parent::getDefaultOptions(array()));
            $textOptions['required'] = isset($options['required']) ? $options['required'] : false;
            $textOptions['trim']     = isset($options['trim']) ? $options['trim'] : true;

            $builder->add('condition_pattern', 'choice', array(
            	'choices' => self::getConditionChoices(),
            ));
            $builder->add('text', 'text', $textOptions);
            $this->transformerId = 'lexik_form_filter.transformer.text';
        } else {
            $attributes['condition_pattern'] = $options['condition_pattern'];
        }

        $builder->setAttribute('filter_options', $attributes);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultOptions()
    {
        return array_merge(parent::getDefaultOptions(), array(
            'condition_pattern' => self::PATTERN_EQUALS,
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getParent(array $options)
    {
        return ($options['condition_pattern'] == self::SELECT_PATTERN) ? 'filter' : 'filter_field';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'filter_text';
    }

    /**
     * {@inheritdoc}
     */
    public function getTransformerId()
    {
        return $this->transformerId;
    }

    /**
     * {@inheritdoc}
     */
    public function applyFilter(QueryBuilder $queryBuilder, Expr $e, $field, $values)
    {
        if (!empty($values['value'])) {
            $op    = ($values['condition_pattern'] == self::PATTERN_EQUALS) ? 'eq' : 'like';
            $value = sprintf($values['condition_pattern'], $values['value']);
            $queryBuilder->andWhere($e->$op($field, $e->literal($value)));
        }
    }

    /**
     * Retruns an array of available conditions patterns.
     *
     * @return array
     */
    static public function getConditionChoices()
    {
        $choices = array();

        $reflection = new \ReflectionClass(__CLASS__);
        foreach ($reflection->getConstants() as $name => $value) {
            if ('PATTERN_' === substr($name, 0, 8)) {
                $choices[$value] = strtolower(str_replace(array('PATTERN_', '_'), array('', ' '), $name));
            }
        }

        return $choices;
    }
}