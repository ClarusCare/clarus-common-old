<?php

namespace Clarus\SecureChat\Http\Controllers;

use Illuminate\Http\Request;
use Clarus\SecureChat\Gateways\UserGateway;
use Clarus\SecureChat\Http\Responders\UserResponder;

class UsersController extends BaseController
{
    /**
     * @var UserGateway
     */
    protected $users;

    /**
     * @var UserResponder
     */
    private $responder;

    /**
     * UsersController constructor.
     *
     * @param  UserGateway  $users
     * @param  UserResponder  $responder
     */
    public function __construct(UserGateway $users, UserResponder $responder)
    {
        $this->users = $users;
        $this->responder = $responder;
    }

    public function index(Request $request)
    {
        $users = $this->users->buildChatUserList($request->user(), $request->get('partner_id', null));

        return $this->responder->createCollectionResponse($users);
    }

    public function profile(Request $request)
    {
        $user = $request->user();

        return $this->responder->createItemResponse($user);
    }
}
