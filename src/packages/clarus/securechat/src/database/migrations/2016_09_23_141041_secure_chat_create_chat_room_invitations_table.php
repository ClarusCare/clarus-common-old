<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SecureChatCreateChatRoomInvitationsTable extends Migration
{
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('chat_room_invitations', function (Blueprint $table): void {
            $table->dropForeign('chat_room_invitations_created_by_user_id_foreign');
            $table->dropForeign('chat_room_invitations_chat_room_id_foreign');
            $table->dropForeign('chat_room_invitations_user_id_foreign');
        });

        Schema::drop('chat_room_invitations');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('chat_room_invitations', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->integer('chat_room_id')->unsigned();
            $table->integer('created_by_user_id')->unsigned();

            $table->softDeletes();

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('chat_room_id')->references('id')->on('chat_rooms')->onDelete('cascade');
            $table->foreign('created_by_user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
}
