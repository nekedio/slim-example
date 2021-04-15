<?php

namespace App;

class ValidatorCourse implements ValidatorInterface
{
    public function validate(array $data) {
        $errors = [];
        
        if (empty($data['title'])) {
            $errors['title'] = 'Can\'t be blank';
        }
        
        if (empty($data['paid'])) {
            $errors['paid'] = 'Can\'t be blank';
        }

        return $errors;
    }
}
