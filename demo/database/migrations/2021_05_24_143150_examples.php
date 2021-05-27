<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Examples extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('single_examples', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('variations');
            $table->json('image')->nullable();
            $table->json('file')->nullable();
            $table->timestamps();
        });

        Schema::create('collection_examples', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('images')->nullable();
            $table->json('files')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('image_collection_examples');
        Schema::dropIfExists('single_image_examples');
    }
}
