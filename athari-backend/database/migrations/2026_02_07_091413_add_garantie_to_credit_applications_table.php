<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGarantieToCreditApplicationsTable extends Migration
{
    public function up()
    {
        Schema::table('credit_applications', function (Blueprint $table) {
            if (!Schema::hasColumn('credit_applications', 'garantie')) {
                $table->string('garantie')->nullable()->after('observation');
            }
        });
    }

    public function down()
    {
        Schema::table('credit_applications', function (Blueprint $table) {
            if (Schema::hasColumn('credit_applications', 'garantie')) {
                $table->dropColumn('garantie');
            }
        });
    }
}