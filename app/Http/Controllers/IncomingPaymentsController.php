<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Application;

class IncomingPaymentsController extends Controller
{
    /**
     * Get the applications with incoming payments
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page') ?? config('japa.per_page');

        $incomingPaymentApplications = $this->query($request->user()->id)
                                    ->paginate($perPage);

        return $this->sendSuccess(__('app.request_successful'), 200, $incomingPaymentApplications);
    }

    /**
     * Get the count of applications with incoming payments
     */
    public function count(Request $request)
    {
        $incomingPaymentApplicationsCount = $this->query($request->user()->id)
                                ->count();

        return $this->sendSuccess(__('app.request_successful'), 200, [
            'incoming_payments' => $incomingPaymentApplicationsCount
        ]);
    }

    /**
     * Get the query for declined applications
     */
    private function query($userId)
    {
        $query = Application::whereHas('user', fn($query) => $query->where('referred_by', $userId))
                            ->whereNotNull('active_installment')
                            ->whereIn('active_installment->status', [
                                Application::INSTALLMENT_OPEN,
                                Application::INSTALLMENT_OVERDUE
                            ])
                            ->where('status', Application::OPEN);

        return $query;
    }
}
