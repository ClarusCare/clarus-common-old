<?php

namespace Clarus\SecureChat\database\seeds\Test;

use DB;
use Illuminate\Database\Seeder;

class ChatRoomsTestTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('chat_rooms')->insert([
            [
                'name'       => 'Test Room One',
                'private'    => false,
                'user_id'    => 1,
                'partner_id' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name'       => 'Test Room Two',
                'private'    => false,
                'user_id'    => 1,
                'partner_id' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name'       => 'Test Room Three',
                'private'    => false,
                'user_id'    => 1,
                'partner_id' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ]);

        $chatRoomOne = \Clarus\SecureChat\Models\ChatRoom::find(1);
        $chatRoomOne->users()->attach([4, 2]);
    }
}
