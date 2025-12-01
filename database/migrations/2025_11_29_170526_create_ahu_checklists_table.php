<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ahu_checklists', function (Blueprint $table) {
            $table->id();
            $table->integer('shift');
            $table->string('gpid')->nullable();
            $table->string('name')->nullable();
            
            // AHU MB-1 measurements (9 fields)
            $table->string('ahu_mb_1_1_hf')->nullable();
            $table->string('ahu_mb_1_1_pf')->nullable();
            $table->string('ahu_mb_1_1_mf')->nullable();
            $table->string('ahu_mb_1_2_hf')->nullable();
            $table->string('ahu_mb_1_2_mf')->nullable();
            $table->string('ahu_mb_1_2_pf')->nullable();
            $table->string('ahu_mb_1_3_hf')->nullable();
            $table->string('ahu_mb_1_3_mf')->nullable();
            $table->string('ahu_mb_1_3_pf')->nullable();
            
            // PAU MB measurements (10 fields)
            $table->string('pau_mb_1_pf')->nullable();
            $table->string('pau_mb_pr_1a_hf')->nullable();
            $table->string('pau_mb_pr_1a_mf')->nullable();
            $table->string('pau_mb_pr_1a_pf')->nullable();
            $table->string('pau_mb_pr_1b_hf')->nullable();
            $table->string('pau_mb_pr_1b_mf')->nullable();
            $table->string('pau_mb_pr_1b_pf')->nullable();
            $table->string('pau_mb_pr_1c_hf')->nullable();
            $table->string('pau_mb_pr_1c_pf')->nullable();
            $table->string('pau_mb_pr_1c_mf')->nullable();
            
            // AHU VRF measurements (6 fields)
            $table->string('ahu_vrf_mb_ms_1a_pf')->nullable();
            $table->string('ahu_vrf_mb_ms_1b_pf')->nullable();
            $table->string('ahu_vrf_mb_ms_1c_pf')->nullable();
            $table->string('ahu_vrf_mb_ss_1a_pf')->nullable();
            $table->string('ahu_vrf_mb_ss_1b_pf')->nullable();
            $table->string('ahu_vrf_mb_ss_1c_pf')->nullable();
            
            // IF (Inline Filter) measurements (18 fields)
            $table->string('if_pre_filter_a')->nullable();
            $table->string('if_medium_a')->nullable();
            $table->string('if_hepa_a')->nullable();
            $table->string('if_pre_filter_b')->nullable();
            $table->string('if_medium_b')->nullable();
            $table->string('if_hepa_b')->nullable();
            $table->string('if_pre_filter_c')->nullable();
            $table->string('if_medium_c')->nullable();
            $table->string('if_hepa_c')->nullable();
            $table->string('if_pre_filter_d')->nullable();
            $table->string('if_medium_d')->nullable();
            $table->string('if_hepa_d')->nullable();
            $table->string('if_pre_filter_e')->nullable();
            $table->string('if_medium_e')->nullable();
            $table->string('if_hepa_e')->nullable();
            $table->string('if_pre_filter_f')->nullable();
            $table->string('if_medium_f')->nullable();
            $table->string('if_hepa_f')->nullable();
            
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('shift');
            $table->index('gpid');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ahu_checklists');
    }
};
