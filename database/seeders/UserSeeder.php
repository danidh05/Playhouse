<?php

// database/seeders/UserSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run()
    {
        $users = [
            ['role_id'=>2,'name'=>'Cashier 01','email'=>'cashier1@fluffypuffy.com','password'=>'Cashier01!'],
            ['role_id'=>2,'name'=>'Cashier 02','email'=>'cashier2@fluffypuffy.com','password'=>'Cashier02!'],
            ['role_id'=>2,'name'=>'Cashier 03','email'=>'cashier3@fluffypuffy.com','password'=>'Cashier03!'],
            ['role_id'=>2,'name'=>'Cashier 04','email'=>'cashier4@fluffypuffy.com','password'=>'Cashier04!'],
            // …cashiers 3 & 4…
            ['role_id'=>1,'name'=>'Admin 01','email'=>'admin1@fluffypuffy.com','password'=>'Admin01!'],
            ['role_id'=>1,'name'=>'Admin 02','email'=>'admin2@fluffypuffy.com','password'=>'Admin02!'],
        ];

        foreach ($users as $u) {
            User::create([
                'role_id'          => $u['role_id'],
                'name'             => $u['name'],
                'email'            => $u['email'],
                'email_verified_at'=> now(),
                'password'         => Hash::make($u['password']),
            ]);
        }
    }
}