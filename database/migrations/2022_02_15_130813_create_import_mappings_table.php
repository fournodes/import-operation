<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateImportMappingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(config('fournodes.import-operation.import_mapping_table_name'), function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('model_type', 255);
            $table->longText('mapping');
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
        Schema::dropIfExists(config('fournodes.import-operation.import_mapping_table_name'));
    }
}
