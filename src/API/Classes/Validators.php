<?php

namespace API\Classes;

use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;

class Validators {

    public static function validateEmail($that,$email) {
    
        $errorList = $that->get('validator')->validateValue(
                $email, array(new Email(),new NotBlank())
        );

        if (count($errorList) == 0) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
    
    public static function validateNonNumericalString($that,$string,$minLength,$maxLength) {

        $regexConstraint  = new Regex(array('pattern'=> "/^[a-zA-Z]+$/"));
        $errorList = $that->get('validator')->validateValue(
                $string, array(new Length(array('min'=>$minLength,'max'=>$maxLength)),new NotBlank(),$regexConstraint)
        );

        if (count($errorList) == 0) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
    
    public static function validateString($that,$string,$minLength,$maxLength) {

        $errorList = $that->get('validator')->validateValue(
                $string, array(new Length(array('min'=>$minLength,'max'=>$maxLength)),new NotBlank())
        );

        if (count($errorList) == 0) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

}
