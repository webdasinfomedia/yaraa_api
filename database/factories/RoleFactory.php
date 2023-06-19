<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {   
            $title = $this->faker->text(10);
            $users = User::all()->pluck('id');
    	return [
            'name'=> $title,
            'slug'=> Str::slug($title),
        //   'is_admin' =>  \App\Models\User::factory()->create()->id, // creates new user
            'is_admin' =>  $this->faker->randomElement($users), // picks random elements for array
    	];

    }
}
