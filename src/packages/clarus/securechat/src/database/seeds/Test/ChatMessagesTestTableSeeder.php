<?php

namespace Clarus\SecureChat\database\seeds\Test;

use DB;
use Illuminate\Database\Seeder;

class ChatMessagesTestTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('chat_messages')->insert(
            [
                'user_id'      => 1,
                'chat_room_id' => 2,
                'content'      => 'This is a test message.',
                'created_at'   => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ]
        );

        $messages = [];
        $numberOfMessagesToSeed = 100;

        for ($i = 0; $i < $numberOfMessagesToSeed; $i++) {
            $j = $i + 2;

            $messages[] = [
                'user_id'      => 1,
                'chat_room_id' => 1,
                'content'      => "This is seeded message number {$j}.",
                'created_at'   => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ];
        }

        DB::table('chat_messages')->insert($messages);
    }
}
