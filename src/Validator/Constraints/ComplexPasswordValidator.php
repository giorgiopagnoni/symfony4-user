<?php
/**
 * Created by PhpStorm.
 * User: giorgiopagnoni
 * Date: 12/04/17
 * Time: 10:12
 */

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ComplexPasswordValidator extends ConstraintValidator
{
    /**
     * @param string $value
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if ($value) {
            if (!preg_match('/[0-9]/', $value, $matches) || // number
                !preg_match('/[a-z]/', $value, $matches) || // lowercase character
                !preg_match('/[A-Z]/', $value, $matches)    // uppercase character
            ) {
                $this->context->buildViolation($constraint->message)
                    ->addViolation();
            }
        }
    }
}