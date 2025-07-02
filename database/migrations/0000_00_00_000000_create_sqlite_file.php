<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        if (!file_exists(database_path('database.sqlite'))) {
            touch(database_path('database.sqlite'));
        }
    }

    public function down()
    {
        // Do not delete the database file on rollback
    }
};
