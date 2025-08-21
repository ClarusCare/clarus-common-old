<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SecureChatCreateChatRoomsTable extends Migration
{
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('chat_rooms', function (Blueprint $table): void {
            $table->dropForeign('chat_rooms_user_id_foreign');
        });

        Schema::drop('chat_rooms');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('chat_rooms', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->boolean('private')->default(false);
            $table->timestamps();

            $table->integer('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
}
