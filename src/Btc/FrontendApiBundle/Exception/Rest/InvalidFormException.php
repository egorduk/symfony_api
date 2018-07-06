<?php

namespace Btc\FrontendApiBundle\Exception\Rest;

/**
 * TODO: for developing and testing.
 */
class InvalidFormException extends \RuntimeException
{
    protected $form;

    public function __construct($form = null, $message = '')
    {
        parent::__construct($message);
        $this->form = $form;

        $this->getErrors();
    }

    /**
     * @return array|null
     */
    public function getForm()
    {
        return $this->form;
    }

    public function getErrors()
    {
        var_dump('is_valid:'.$this->getForm()->isValid());

        $children = $this->getForm()->all();
        array_push($children, $this->getForm());

        foreach ($children as $child) {
            foreach ($child->getErrors() as $err) {
                var_dump($err->getMessage());
                var_dump($err->getMessageParameters());
            }
        }
    }
}
