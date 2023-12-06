<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class IndividualAgentController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $perPage = $request->query('per_page') ?? config('japa.per_page');

        $individualAgents = User::individualAgents()
                                ->latest()
                                ->paginate($perPage);

        return $this->sendSuccess(__('app.request_successful'), 200, $individualAgents);
    }
}
