<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateImportBatchesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(config('fournodes.import-operation.import_batch_table_name'), function (Blueprint $table) {
            $table->id();
            $table->text('defaults')->default(null)->nullable();
            $table->text('path')->default(null);
            $table->text('settings')->default(null);
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
        Schema::dropIfExists(config('fournodes.import-operation.import_batch_table_name'));
    }
}
