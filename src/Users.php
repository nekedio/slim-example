<?php

namespace App;

class Users
{
    public function save($item)
    {
        $item['id'] = random_int(1,999);
        file_put_contents('users.txt', json_encode($item) . "\n", FILE_APPEND);
    }

   
    public function getUsers()
    {
        $usersJson = explode("\n", trim(file_get_contents('users.txt')));
        $users = array_map(function ($user) {
            return json_decode($user, true);
        }, $usersJson);
        return $users;
    }

    public function find($id)
    {
        $users = self::getUsers();
        $user = collect($users)->firstWhere('id', $id);
        return $user;
    }
   
    public function patch($newUser)
    {
        $users = self::getUsers();
        $newUsers = array_map(function ($user) use ($newUser) {
            if ($user['id'] === $newUser['id']) {
                $user['name'] = $newUser['name'];
                $user['email'] = $newUser['email'];
                $user['password'] = $newUser['password'];
                $user['passwordConfirmation'] = $newUser['passwordConfirmation'];
                $user['city'] = $newUser['city'];
            }
            return $user;
        }, $users);
        $usersJson = implode("\n", array_map(fn($user) => (json_encode($user)), $newUsers));
        return file_put_contents('users.txt', $usersJson);
    }

    public function destroy($id)
    {
        $users = self::getUsers();
        print_r($users);
        $filtered = array_filter($users, fn($user)=>($user['id'] != $id));
        $usersJson = implode("\n", array_map(fn($user) => (json_encode($user)), $filtered));
        return file_put_contents('users.txt', $usersJson);
    }
}
