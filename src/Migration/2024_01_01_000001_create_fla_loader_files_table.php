<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $schema->create('fla_loader_files', function (Blueprint $table) {
            $table->increments('id');
            $table->string('filename');
            $table->string('original_name');
            $table->string('path');
            $table->integer('size');
            $table->string('mime_type');
            $table->boolean('is_public')->default(false);
            $table->text('allowed_groups')->nullable(); // JSON array of group IDs
            $table->timestamps();
        });
    },
    'down' => function (Builder $schema) {
        $schema->dropIfExists('fla_loader_files');
    }
];
