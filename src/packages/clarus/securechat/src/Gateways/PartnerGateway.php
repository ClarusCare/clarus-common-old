<?php

namespace Clarus\SecureChat\Gateways;

use App\Models\User;
use App\Models\Partner;

class PartnerGateway extends BaseGateway
{
    /**
     * @var \App\Models\Partner
     */
    private $partner;

    public function __construct(Partner $partner)
    {
        $this->partner = $partner ?: $partner->newInstance();
    }

    public function buildFlatUserList(Partner $partner)
    {
        $users = collect($partner->users()->get());

        $partnerProviders = $partner->partnerProviders->filter(function ($partnerProvider) {
            return $partnerProvider->active;
        });

        $partnerProviders->each(function ($partnerProvider) use (&$users): void {
            $provider = $partnerProvider->provider;

            if ($provider && $provider->active && $provider->user) {
                $user = $provider->user;
                $users->push($user);
            }
        });

        return $users;
    }

    public function find($id)
    {
        return $this->partner->find($id);
    }

    public function getUnifiedPartnersForUser(User $user, $providersOnly = false)
    {
        // This includes partners that are directly and indirectly related
        $partners = collect();

        // Direct relationship
        if (! $providersOnly) {
            $partners = $partners->merge($user->partners()->active()->get());
        }

        // Provider relationship
        $providers = $user->providers()
            ->where('active', '=', true)
            ->with(['partner' => function ($query): void {
                $query->active();
            }])
            ->get();

        $partners = $partners->merge($providers->pluck('partner'));
        $partners = $this->uniqueByKey($partners, 'id');

        // Filter out partners without secure chat enabled
        return $partners->filter(function ($partner) {
            return $partner && $partner->hasChat();
        });
    }

    public function userHasAccess(User $user, Partner $partnerToCheck)
    {
        $partners = $this->getUnifiedPartnersForUser($user);
        $hasAccess = false;

        $partners->each(function ($partner) use ($partnerToCheck, &$hasAccess): void {
            if ($partner->id == $partnerToCheck->id) {
                $hasAccess = true;
            }
        });

        return $hasAccess;
    }
}
