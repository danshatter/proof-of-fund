<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Application;

class PendingApplicationsController extends Controller
{
    /**
     * Get the pending applications
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page') ?? config('japa.per_page');

        $pendingApplications = $this->query($request->user()->id)
                                    ->paginate($perPage);

        return $this->sendSuccess(__('app.request_successful'), 200, $pendingApplications);
    }

    /**
     * Get the count of pending applications
     */
    public function count(Request $request)
    {
        $pendingApplicationsCount = $this->query($request->user()->id)
                                        ->count();

        return $this->sendSuccess(__('app.request_successful'), 200, [
            'pending_applications' => $pendingApplicationsCount
        ]);
    }

    /**
     * Get the query for declined applications
     */
    private function query($userId)
    {
        $query = Application::whereHas('user', fn($query) => $query->where('referred_by', $userId))
                            ->whereIn('status', [
                                Application::PENDING,
                                Application::IN_REVIEW
                            ]);

        return $query;
    }
}
