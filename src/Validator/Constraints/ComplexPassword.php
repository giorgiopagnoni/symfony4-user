<?php
/**
 * Created by PhpStorm.
 * User: giorgiopagnoni
 * Date: 12/04/17
 * Time: 10:12
 */

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ComplexPassword extends Constraint
{
    public $message = 'user.password.must_be_complex';
}