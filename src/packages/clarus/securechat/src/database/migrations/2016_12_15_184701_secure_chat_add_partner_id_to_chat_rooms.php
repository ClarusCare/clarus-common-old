<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SecureChatAddPartnerIdToChatRooms extends Migration
{
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('chat_rooms', function (Blueprint $table): void {
            $table->dropForeign('chat_rooms_partner_id_foreign');
            $table->dropColumn('partner_id');
        });
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('chat_rooms', function (Blueprint $table): void {
            $table->integer('partner_id')->unsigned();
            $table->foreign('partner_id')->references('id')->on('partners')->onDelete('cascade');
        });
    }
}
