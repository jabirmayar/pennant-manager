<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scope_integer_people', function (Blueprint $table) {
            $table->id();
        });
        foreach (['features', 'custom_features'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('scope');
                $table->text('value');
                $table->timestamps();
                $table->unique(['name', 'scope']);
            });
        }
        foreach (['scope_people', 'scope_teams'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->string('uuid')->primary();
                $table->string('email')->nullable();
                $table->string('name')->nullable();
                $table->string('plan')->nullable();
                $table->string('secret')->nullable();
            });
        }
    }
};
