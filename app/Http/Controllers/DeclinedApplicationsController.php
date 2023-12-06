<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Application;

class DeclinedApplicationsController extends Controller
{
    /**
     * Get the declined applications
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page') ?? config('japa.per_page');

        $declinedApplications = $this->query($request->user()->id)
                                    ->paginate($perPage);

        return $this->sendSuccess(__('app.request_successful'), 200, $declinedApplications);
    }

    /**
     * Get the count of declined applications
     */
    public function count(Request $request)
    {
        $declinedApplicationsCount = $this->query($request->user()->id)
                                        ->count();

        return $this->sendSuccess(__('app.request_successful'), 200, [
            'declined_applications' => $declinedApplicationsCount
        ]);
    }

    /**
     * Get the query for declined applications
     */
    private function query($userId)
    {
        $query = Application::whereHas('user', fn($query) => $query->where('referred_by', $userId))
                            ->where('status', Application::REJECTED);

        return $query;
    }
}
