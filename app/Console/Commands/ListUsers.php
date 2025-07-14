<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ListUsers extends Command
{
    protected $signature = 'users:list';
    protected $description = 'List all users in the database';

    public function handle()
    {
        $users = User::all(['id', 'name', 'email', 'created_at']);
        
        if ($users->isEmpty()) {
            $this->info('No users found in the database.');
            return 0;
        }
        
        $this->info('Available users:');
        $this->newLine();
        
        $this->table(
            ['ID', 'Name', 'Email', 'Created At'],
            $users->map(function($user) {
                return [
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->created_at->format('Y-m-d H:i:s')
                ];
            })->toArray()
        );
        
        $this->newLine();
        $this->info('Default password for all users: password');
        
        return 0;
    }
}