<?php

use App\Enums\UserRole;
use App\Jobs\CheckSubscriptions;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

Schedule::job(new CheckSubscriptions)->hourly()->withoutOverlapping();

Artisan::command('billing:check', function () {
    CheckSubscriptions::dispatchSync();
    $this->info('Payments checked, bills issued and subscription statuses updated.');
})->purpose('Run the billing sweep now (normally hourly from the scheduler)');

Artisan::command('smartlabel:create-superadmin {email} {--name=Platform Admin}', function (string $email) {
    $password = $this->secret('Password');

    $validator = Validator::make(['email' => $email, 'password' => $password], [
        'email' => ['required', 'email', 'unique:users,email'],
        'password' => ['required', Password::defaults()],
    ]);

    if ($validator->fails()) {
        foreach ($validator->errors()->all() as $error) {
            $this->error($error);
        }

        return 1;
    }

    User::create([
        'company_id' => null,
        'branch_id' => null,
        'name' => $this->option('name'),
        'email' => $email,
        'password' => $password,
        'role' => UserRole::SuperAdmin,
    ]);

    $this->info("Superadmin {$email} created. Log in and open Admin > Companies.");
})->purpose('Create a platform superadmin who onboards companies');
