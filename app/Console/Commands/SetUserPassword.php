<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class SetUserPassword extends Command
{
    protected $signature = 'user:set-password {email}';
    protected $description = 'Set a users password';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $user = User::where('email', '=', $email)->first();

        if (!$user) {
            return $this->error('No account found.');
        }

        $password = $this->ask('Password');
        $user->password = $password;
        $user->save();

        $this->info('Complete.');
    }
}
