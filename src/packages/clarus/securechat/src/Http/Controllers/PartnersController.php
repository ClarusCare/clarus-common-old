<?php

namespace Clarus\SecureChat\Http\Controllers;

use Illuminate\Http\Request;
use Clarus\SecureChat\Gateways\UserGateway;
use Clarus\SecureChat\Gateways\PartnerGateway;
use Clarus\SecureChat\Http\Responders\PartnerResponder;

class PartnersController extends BaseController
{
    /**
     * @var PartnerGateway
     */
    protected $partners;

    /**
     * @var UserGateway
     */
    protected $users;

    /**
     * @var PartnerResponder
     */
    private $responder;

    /**
     * PartnersController constructor.
     *
     * @param  PartnerGateway  $partners
     * @param  UserGateway  $users
     * @param  PartnerResponder  $responder
     */
    public function __construct(PartnerGateway $partners, UserGateway $users, PartnerResponder $responder)
    {
        $this->partners = $partners;
        $this->users = $users;
        $this->responder = $responder;
    }

    public function index(Request $request)
    {
        $providerRelationshipsOnly = $request->has('providers_only') && $request->input('providers_only') != 0;
        $partners = $this->partners->getUnifiedPartnersForUser($request->user(), $providerRelationshipsOnly);
        $userGateway = $this->users;

        $partners->map(function ($partner) use ($request, $userGateway): void {
            $partner->users = $userGateway->buildChatUserList($request->user(), $partner->id);
        });

        return $this->responder->createCollectionResponse($partners);
    }
}
