<?php

Route::group(['middleware' => config('secure-chat.route_middleware'), 'prefix' => config('secure-chat.route_prefix'), 'namespace' => 'Clarus\SecureChat\Http\Controllers'], function (): void {

    //start AUTHENTICATE

    Route::post('authenticate', [
        'uses' => 'AuthController@authenticate',
        'as'   => 'secure-chat.authenticate',
    ]);

    //end AUTHENTICATE

    //start USERS

    Route::get('users', [
        'uses' => 'UsersController@index',
        'as'   => 'secure-chat.users',
    ]);

    Route::get('users/me', [
        'uses' => 'UsersController@profile',
        'as'   => 'secure-chat.users.profile',
    ]);

    //end USERS

    //start USER STATUS

    Route::put('user-status', [
        'uses' => 'UserStatusController@update',
        'as'   => 'secure-chat.user-status.update',
    ]);

    //end USER STATUS

    //start PARTNERS

    Route::get('partners', [
        'uses' => 'PartnersController@index',
        'as'   => 'secure-chat.partners.index',
    ]);

    //end PARTNERS

    //start CHAT ROOMS

    Route::get('chat-rooms', [
        'uses' => 'ChatRoomsController@index',
        'as'   => 'secure-chat.chat-rooms.index',
    ]);

    Route::post('chat-rooms', [
        'uses' => 'ChatRoomsController@store',
        'as'   => 'secure-chat.chat-rooms.store',
    ]);

    Route::put('chat-rooms/{id}', [
        'uses' => 'ChatRoomsController@update',
        'as'   => 'secure-chat.chat-rooms.update',
    ]);

    //end CHAT ROOMS

    //start CHAT MESSAGES

    Route::get('chat-rooms/{id}/messages', [
        'uses' => 'ChatRoomChatMessagesController@index',
        'as'   => 'secure-chat.chat-room.messages.index',
    ]);

    Route::post('chat-rooms/{id}/messages', [
        'uses' => 'ChatRoomChatMessagesController@store',
        'as'   => 'secure-chat.chat-room.messages.store',
    ]);

    Route::put('chat-rooms/{id}/messages/mark-as-read', [
        'uses' => 'ChatRoomChatMessagesController@markAsRead',
        'as'   => 'secure-chat.chat-room.messages.mark-as-read',
    ]);

    Route::get('messages/{id}', [
        'uses' => 'ChatMessagesController@show',
        'as'   => 'secure-chat.messages.show',
    ]);

    Route::put('messages/mark-as-read', [
        'uses' => 'UserChatMessagesController@markAsRead',
        'as'   => 'secure-chat.messages.mark-as-read',
    ]);

    //end CHAT MESSAGES

    //start CHAT INVITATIONS

    Route::get('invitations', [
        'uses' => 'ChatRoomInvitationsController@index',
        'as'   => 'secure-chat.invitations.index',
    ]);

    Route::post('chat-rooms/{id}/invitations', [
        'uses' => 'ChatRoomInvitationsController@store',
        'as'   => 'secure-chat.invitations.store',
    ]);

    Route::put('invitations/{id}/accept', [
        'uses' => 'ChatRoomInvitationsController@accept',
        'as'   => 'secure-chat.invitations.accept',
    ]);

    Route::delete('invitations/{id}', [
        'uses' => 'ChatRoomInvitationsController@delete',
        'as'   => 'secure-chat.invitations.delete',
    ]);

    //end CHAT INVITATIONS

    //start CHAT ROOM USERS

    Route::get('chat-rooms/{id}/users', [
        'uses' => 'ChatRoomUsersController@index',
        'as'   => 'secure-chat.chat-room.users.index',
    ]);

    Route::delete('chat-rooms/{chatRoomId}/users/{userId}', [
        'uses' => 'ChatRoomUsersController@delete',
        'as'   => 'secure-chat.chat-room.users.delete',
    ]);

    //end CHAT ROOM USERS
});
