<?php

namespace Webkul\Website\Http\Responses;

use Illuminate\Http\RedirectResponse;
use Webkul\Website\Filament\Customer\Pages\Homepage;

/**
 * Logout Response class
 *
 */
class LogoutResponse implements \Filament\Auth\Http\Responses\Contracts\LogoutResponse
{
    /**
     * To Response
     *
     * @param mixed $request The incoming request
     * @return RedirectResponse
     */
    public function toResponse($request): RedirectResponse
    {
        if ($request->route()->getName() == 'filament.customer.auth.logout') {
            return redirect()->route(Homepage::getRouteName());
        } else {
            return redirect()->route('filament.admin.auth.login');
        }
    }
}
