<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

#[Signature('lms:create-trainer {--first-name= : Prénom} {--last-name= : Nom} {--email= : Adresse email} {--password= : Mot de passe (sinon demandé de façon masquée)}')]
#[Description('Crée le compte formateur unique (administrateur)')]
class CreateTrainer extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (User::where('role', Role::Trainer)->exists()) {
            $this->error('Un compte formateur existe déjà.');

            return self::FAILURE;
        }

        $data = [
            'first_name' => $this->option('first-name') ?? text('Prénom', required: true),
            'last_name' => $this->option('last-name') ?? text('Nom', required: true),
            'email' => $this->option('email') ?? text('Adresse email', required: true),
            'password' => $this->option('password') ?? password('Mot de passe', required: true),
        ];

        $validator = Validator::make($data, [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::default()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $trainer = new User($validator->validated());
        $trainer->role = Role::Trainer;
        $trainer->activated_at = now();
        $trainer->markEmailAsVerified();

        $this->info("Compte formateur créé pour {$trainer->email}.");

        return self::SUCCESS;
    }
}
