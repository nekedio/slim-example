<?php

namespace App;

class ValidatorUser
{
    public function validate(array $data)
    {
        $errors = [];
        if (empty($data['name'])) {
            $errors['name'] = 'Can\'t be blank';
        }

        if (strlen($data['password']) < 3) {
            $errors['password'] = 'weak password';
        }

        if ($data['password'] != $data['passwordConfirmation']) {
            $errors['passwordConfirmation'] = 'Password don\'t match';
        }

        return $errors;
    }
}
