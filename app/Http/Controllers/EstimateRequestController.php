<?php

namespace App\Http\Controllers;

use App\DataTables\EstimateRequestDataTable;
use App\Helper\Reply;
use App\Http\Requests\StoreEstimateRequest;
use App\Http\Requests\UpdateEstimateRequestStatus;
use App\Models\Currency;
use App\Models\Estimate;
use App\Models\EstimateRequest;
use App\Models\Project;
use App\Models\User;
use App\Notifications\EstimateRequestInvite;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class EstimateRequestController extends AccountBaseController
{

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'modules.estimateRequest.estimateRequests';
        $this->pageIcon = 'ti-file';
        $this->middleware(function ($request, $next) {
            abort_403(!in_array('estimates', $this->user->modules));

            return $next($request);
        });
    }

    /**
     * Display a listing of the resource.
     */
    public function index(EstimateRequestDataTable $dataTable)
    {
        abort_403(!(in_array(user()->permission('view_estimates'), ['all', 'added', 'owned', 'both']) || in_array('client', user_roles())));
        $this->clients = User::allClients(active:false);
        return $dataTable->render('estimate-requests.index', $this->data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        abort_403(!(in_array('client', user_roles())));
        $this->pageTitle = __('modules.estimateRequest.createEstimateRequest');

        $this->projects = Project::where('client_id', auth()->id())->get();
        $this->clients = User::allClients();
        $this->currencies = Currency::all();

        $this->view = 'estimate-requests.ajax.create';

        if (request()->ajax()) {
            return $this->returnAjax($this->view);
        }

        return view('estimate-requests.create', $this->data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEstimateRequest $request)
    {
        $estimateRequest = new EstimateRequest();
        $estimateRequest->client_id = auth()->id();
        $estimateRequest->company_id = user()->company_id;
        $estimateRequest->description = trim_editor($request->description);
        $estimateRequest->estimated_budget = round($request->estimated_budget, 2);
        $estimateRequest->project_id = $request->project_id;
        $estimateRequest->early_requirement = $request->early_requirement;
        $estimateRequest->currency_id = $request->currency_id;
        $estimateRequest->status = 'pending';
        $estimateRequest->save();

        $redirectUrl = urldecode($request->redirect_url);

        if ($redirectUrl == '') {
            $redirectUrl = route('estimate-request.index');
        }

        return Reply::successWithData(__('messages.recordSaved'), ['redirectUrl' => $redirectUrl]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $this->estimateRequest = EstimateRequest::findOrFail($id);
        $this->pageTitle = __('modules.estimateRequest.estimateRequest');
        $this->estimateLink = $this->estimateRequest->estimate ? '<p class="mb-0 text-dark-grey f-14 w-70 text-wrap m-0 mt-1"><a href="' . route('estimates.show', [$this->estimateRequest->estimate->id]) . '" class="text-dark-grey f-14 w-70 text-wrap">' . $this->estimateRequest->estimate->estimate_number . '</a></p>' : '--';
        $this->deleteEstimatePermission = user()->permission('delete_estimates');
        $this->editEstimatePermission = user()->permission('edit_estimates');
        $this->addEstimatePermission = user()->permission('add_estimates');
        $this->view = 'estimate-requests.ajax.show';

        if (request()->ajax()) {
            return $this->returnAjax($this->view);
        }

        return view('estimate-requests.create', $this->data);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        abort_403(!(in_array('client', user_roles()) || in_array(user()->permission('add_estimates'), ['all', 'added'])));
        $this->pageTitle = __('modules.estimateRequest.editEstimateRequest');

        $this->estimateRequest = EstimateRequest::findOrFail($id);

        $this->projects = Project::where('client_id', $this->estimateRequest->client_id)->get();
        $this->clients = User::allClients();
        $this->currencies = Currency::all();

        $this->view = 'estimate-requests.ajax.edit';

        if (request()->ajax()) {
            return $this->returnAjax($this->view);
        }

        return view('estimate-requests.create', $this->data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreEstimateRequest $request, string $id)
    {
        $estimateRequest = EstimateRequest::findOrFail($id);
        $estimateRequest->description = trim_editor($request->description);
        $estimateRequest->estimated_budget = round($request->estimated_budget, 2);
        $estimateRequest->project_id = $request->project_id;
        $estimateRequest->early_requirement = $request->early_requirement;
        $estimateRequest->currency_id = $request->currency_id;
        $estimateRequest->status = 'pending';
        $estimateRequest->save();

        $redirectUrl = urldecode($request->redirect_url);

        if ($redirectUrl == '') {
            $redirectUrl = route('estimate-request.index');
        }

        return Reply::successWithData( __('messages.updateSuccess'), ['redirectUrl' => $redirectUrl]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $estimateRequest = EstimateRequest::findOrFail($id);
        $estimateRequest->delete();

        return Reply::success(__('messages.deleteSuccess'));
    }

    public function applyQuickAction(Request $request)
    {
        if ($request->action_type == 'delete') {
            /* $this->deleteRecords($request); */

            return Reply::success(__('messages.deleteSuccess'));
        }

        return Reply::error(__('messages.selectAction'));
    }

    public function changeStatus (UpdateEstimateRequestStatus $request)
    {
        $estimateRequest = EstimateRequest::findOrFail($request->id);

        if ($request->status == 'rejected') {
            $estimateRequest->update(['status' => 'rejected', 'reason' => $request->reason]);
        }
        else {
            $estimateRequest->update(['status' => $request->status]);
        }

        return Reply::success(__('messages.updateSuccess'));
    }

    public function rejectConfirmation($id)
    {
        $this->estimateRequest = EstimateRequest::findOrFail($id);
        $this->pageTitle = __('modules.estimateRequest.confirmReject');

        return view('estimate-requests.ajax.confirm-reject', $this->data);
    }

    public function sendEstimateRequest()
    {
        $this->pageTitle = __('modules.estimateRequest.sendEstimateRequest');
        $this->clients = User::allClients()->whereNotNull('email');

        return view('estimate-requests.ajax.send-request', $this->data);
    }

    public function sendEstimateMail(Request $request)
    {
        if ($request->client_id == '') {
            return Reply::error(__('validation.required', ['attribute' => 'client']));
        }

        $client = User::findOrFail($request->client_id);

        if (isset($client->email)){
            Notification::send($client, new EstimateRequestInvite($client));
            return Reply::success(__('messages.inviteEmailSuccess'));
        }
    }

    /* public function createEstiamte($id)
    {
        $estimateRequest = EstimateRequest::findOrFail($id);
        $this->lastEstimate = Estimate::lastEstimateNumber() + 1;

        $estimate = new Estimate();
        $estimate->client_id = $estimateRequest->client_id;
        $estimate->company_id = $estimateRequest->company_id;
        $estimate->description = trim_editor($estimateRequest->description);
        $estimate->total = $estimateRequest->estimated_budget;
        $estimate->valid_till = now()->addDays(30)->format('Y-m-d');
        $estimate->status = 'waiting';
        $estimate->estimate_number = $this->lastEstimate;
        $estimate->currency_id = $estimateRequest->currency_id;
        $estimate->save();

        $estimateRequest->update(['status' => 'accepted', 'estimate_id' => $estimate->id]);

        $this->logSearchEntry($estimate->id, $estimate->estimate_number, 'estimates.show', 'estimate');

        $redirectUrl = route('estimates.index');

        return Reply::successWithData(__('messages.recordSaved'), ['estimateId' => $estimate->id, 'redirectUrl' => $redirectUrl]);
    } */

}