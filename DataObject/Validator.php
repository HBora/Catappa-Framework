<?php

namespace Catappa\DataObject;

use Symfony\Component\Validator\Validation;
use Catappa\DataObject\Model;
use Symfony\Component\Validator\Constraints\Blank;
use Symfony\Component\Validator\Constraints\Email;
use Catappa\Patterns\ObjectFactory;
use Catappa\Collections\Config;

class Validator {

    public $messages = array();

    function validationArray($propert, $value, $validations) {
        $validations_params = array();
        $validator = Validation::createValidator();
        $config = Config::getInstance();
        $current_messages = $config->validation_messages;

        foreach ($validations as $class => $parameters) {

            if (!isset($parameters["message"]))
                $parameters["message"] = $current_messages[$class];
            $class = "Symfony\\Component\\Validator\\Constraints\\" . $class;
            $validations_params[] = ObjectFactory::getNewInstance($class, $parameters);
        }

        $violations = $validator->validate($value, $validations_params);
        if (0 !== count($violations)) {
            foreach ($violations as $v)
                $this->messages[$propert] = $v->getMessage();
        }
        return $violations;
    }

    public function getMessages() {
        return $this->messages;
    }

    public function validateGroup($values, $group) {
        $validator = Validation::createValidator();
        $violations = array();
        foreach ($values as $key => $value)
            if (isset($group[$key])) {
                $validations_params = array();
                foreach ($group[$key] as $class => $params) {
                    $class = "Symfony\\Component\\Validator\\Constraints\\" . $class;
                    $validations_params[] = ObjectFactory::getNewInstance($class, $params);
                }
                $violation = $validator->validate($value, $validations_params);
                if (0 !== count($violation)) {
                    $violations[] = $violation;
                    foreach ($violation as $v)
                        $this->messages[$key] = $v->getMessage();
                }
            }
        return $this->messages;
    }
}
