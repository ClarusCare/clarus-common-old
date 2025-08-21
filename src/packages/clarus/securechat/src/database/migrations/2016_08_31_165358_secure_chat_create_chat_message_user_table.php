<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SecureChatCreateChatMessageUserTable extends Migration
{
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('chat_message_user', function (Blueprint $table): void {
            $table->dropForeign('chat_message_user_chat_message_id_foreign');
            $table->dropForeign('chat_message_user_user_id_foreign');
        });

        Schema::drop('chat_message_user');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('chat_message_user', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->integer('chat_message_id')->unsigned();

            $table->timestamp('read_at')->nullable();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('chat_message_id')->references('id')->on('chat_messages')->onDelete('cascade');
        });
    }
}
