<?php

namespace Clarus\SecureChat\Console\Commands;

use Illuminate\Console\Command;
use Faker\Factory as FakerFactory;
use Clarus\SecureChat\Models\ChatRoom;
use Clarus\SecureChat\Models\ChatMessage;
use Clarus\SecureChat\Gateways\UserGateway;
use Faker\Provider\en_US\Text as FakerText;
use Clarus\SecureChat\Gateways\ChatRoomGateway;
use Clarus\SecureChat\Constants\ChatMessageType;
use Faker\Provider\en_US\Company as FakerCompany;

class GenerateChatMessageSeedData extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed chat with random data for performance testing';

    /**
     * @var \Faker\Generator
     */
    protected $faker;

    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'clarus:chat:seed
                            {roomId=random : A specific room to generate messages for.}
                            {numberOfMessages=1000 : The number of chat messages to generate per room.}
                            {--generateRooms : Whether rooms should be generated.}
                            {--numberOfRooms=5 : The number of chat rooms to generate (used with --generateRooms).}
                            {--userId1=random : The ID of the first user to add to the generated rooms (used with --generateRooms).}
                            {--userId2=random : The ID of the second user to add to the generated rooms (used with --generateRooms).}';

    /**
     * @var UserGateway
     */
    protected $users;

    /**
     * @var ChatRoomGateway
     */
    private $rooms;

    /**
     * Create a new command instance.
     *
     * @param  UserGateway  $users
     * @param  ChatRoomGateway  $rooms
     */
    public function __construct(UserGateway $users, ChatRoomGateway $rooms)
    {
        parent::__construct();
        $this->users = $users;
        $this->rooms = $rooms;
        $this->faker = FakerFactory::create();
        $this->faker->addProvider(new FakerText($this->faker));
        $this->faker->addProvider(new FakerCompany($this->faker));
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $roomId = $this->argument('roomId');
        $numberOfMessages = $this->argument('numberOfMessages');
        $shouldGenerateRooms = $this->option('generateRooms');
        $numberOfRooms = $this->option('numberOfRooms');
        $userId1 = $this->option('userId1');
        $userId2 = $this->option('userId2');

        if ($shouldGenerateRooms) {
            $this->generateRoomsWithMessages($numberOfRooms, $numberOfMessages, $userId1, $userId2);
        } else {
            $this->generateMessagesForRoom($roomId, $numberOfMessages);
        }

        $this->comment("\n");
        $this->addOutputSeparator(true);
        $this->comment('Done.');
    }

    protected function addOutputSeparator($useStars = false): void
    {
        $this->comment($useStars ? '********************************' : '--------------------------------');
    }

    protected function generateMessageContentForTypeAndUser($type, $user)
    {
        if ($type == ChatMessageType::ROOM_JOIN) {
            return "{$user->full_name} joined the room.";
        }
        if ($type == ChatMessageType::ROOM_LEAVE) {
            return "{$user->full_name} left the room.";
        }

        return $this->faker->realText($this->faker->numberBetween(10, 100), 3);
    }

    protected function generateMessagesForRoom($roomId, $numberOfMessages, $outputFullSectionHeader = true)
    {
        if ($outputFullSectionHeader) {
            $this->comment("Seeding room {$roomId}\nNumber of messages: {$numberOfMessages}");
            $this->addOutputSeparator(true);
            $this->comment("\n");
        } else {
            $this->info("Seeding {$numberOfMessages} messages");
        }

        if ($roomId == 'random') {
            $room = $this->rooms->random();
            $this->info("Using random room {$room->id}.");
        } else {
            // Validate room exists
            $room = $this->rooms->make()->find($roomId);
        }

        if (! $room) {
            $this->error("Room {$roomId} does not exist. Skipping!");

            return false;
        }

        $this->info('Generating messages...');

        $totalMessages = (int) $numberOfMessages;
        while ($totalMessages > 0) {
            $messageType = $this->randomizeMessageType();
            $user = $room->users->random();
            $content = $this->generateMessageContentForTypeAndUser($messageType, $user);

            $message = new ChatMessage();

            $message->fill([
                'chat_room_id' => $room->id,
                'user_id'      => $user->id,
                'type'         => $messageType,
                'content'      => $content,
            ]);

            $message->save();

            $message->syncForRoomUsers();

            $totalMessages--;
        }

        return true;
    }

    protected function generateRoomsWithMessages($numberOfRooms, $numberOfMessages, $userId1, $userId2): void
    {
        $this->comment("Seeding chat data for {$numberOfRooms} rooms with {$numberOfMessages} messages.");

        $this->comment("Setting user 1 to {$userId1}.");
        $user1 = $this->loadUser($userId1);

        if (! $user1) {
            $this->error("Could not set user 1 with ID of {$userId1}!");

            return;
        }

        $this->comment("Setting user 2 to {$userId2}.");
        $user2 = $this->loadUser($userId2, $user1->id);

        if (! $user2) {
            $this->error("Could not set user 2 with ID of {$userId2}!");

            return;
        }

        $this->addOutputSeparator(true);
        $this->comment("\n");

        $users = collect([$user1, $user2]);

        $totalRooms = (int) $numberOfRooms;
        while ($totalRooms > 0) {
            // create room
            $room = new ChatRoom();
            $room->fill([
                'name'    => $this->faker->bs(),
                'user_id' => $users->random()->id,
            ]);

            $room->save();

            $this->info("Generated room named \"{$room->name}\"");

            // Attach users
            foreach ($users as $roomUser) {
                $room->addUser($roomUser);
            }

            // Then seed random messages
            $succeeded = $this->generateMessagesForRoom($room->id, $numberOfMessages, false);
            $this->addOutputSeparator();

            if (! $succeeded) {
                $this->error('Halting generation because of error generating messages.');

                break;
            }

            $totalRooms--;
        }
    }

    protected function loadUser($userId, $excludeId = null)
    {
        if ($userId == 'random') {
            if ($excludeId) {
                $this->comment("Excluding {$excludeId} from user selection.");
            }

            $randomUser = $this->users->random($excludeId);

            $this->comment("Using random user {$randomUser->id}.");

            return $randomUser;
        }
        if ($userId == $excludeId) {
            $this->error("User {$excludeId} has already been used!");

            return;
        }

        return $this->users->find($userId);
    }

    protected function randomizeMessageType()
    {
        // Randomize message type, weighed heavily in favor of new_message
        $seed = $this->faker->biasedNumberBetween(1, 20);

        if ($seed >= 1 && $seed <= 3) {
            return ChatMessageType::ROOM_JOIN;
        }
        if ($seed > 3 && $seed <= 6) {
            return ChatMessageType::ROOM_LEAVE;
        }

        return ChatMessageType::NEW_MESSAGE;
    }
}
