<?php

namespace App\DataTables;

use App\Models\EstimateRequest;
use App\Models\User;
use App\Scopes\ActiveScope;
use App\Scopes\CompanyScope;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Illuminate\Support\Str;

class EstimateRequestDataTable extends BaseDataTable
{
    public $isAdmin;
    private $addEstimatePermission;
    private $editEstimatePermission;
    private $deleteEstimatePermission;
    private $viewEstimatePermission;

    public function __construct()
    {
        parent::__construct();
        $this->viewEstimatePermission = user()->permission('view_estimates');
        $this->addEstimatePermission = user()->permission('add_estimates');
        $this->editEstimatePermission = user()->permission('edit_estimates');
        $this->deleteEstimatePermission = user()->permission('delete_estimates');
        $this->isAdmin = User::isAdmin(user()->id);
    }

    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return \Yajra\DataTables\DataTableAbstract
     */
    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->addIndexColumn()
            ->addColumn('description', function ($row) {
                $limitedDescription = Str::limit(strip_tags($row->description), 10, '...');

                return $limitedDescription;
            })
            ->editColumn('client', function ($row) {
                return '<div class="media align-items-center">
                    <a href="' . route('clients.show', [$row->client_id]) . '">
                    <img src="' . $row->client->image_url . '" class="mr-2 taskEmployeeImg rounded-circle" alt="' . $row->client->name . '" title="' . $row->client->name . '"></a>
                    <div class="media-body">
                    <h5 class="mb-0 f-13 text-darkest-grey"><a href="' . route('clients.show', [$row->client_id]) . '">' . $row->client->name_salutation . '</a></h5>
                    <p class="mb-0 f-13 text-dark-grey">' . $row->client->clientDetails?->company_name . '</p>
                    </div>
                  </div>';
            })
            ->editColumn('estimated_budget', function ($row) {
                return currency_format($row->estimated_budget, $row->currency_id);
            })
            ->editColumn('project', function ($row) {
                if ($row->project_id) {
                    return '<div class="media align-items-center">
                                <div class="media-body">
                            <h5 class="mb-0 f-13 text-darkest-grey"><a href="' . route('projects.show', [$row->project_id]) . '">' . $row->project->project_name . '</a></h5>
                            </div>
                        </div>';
                }

                return '--';
            })
            ->editColumn('estimate_id', function ($row) {
                if ($row->estimate) {
                    return '<h5 class="mb-0 f-13 text-darkest-grey"><a href="' . route('estimates.show', [$row->estimate->id]) . '">' . $row->estimate->estimate_number . '</a></h5>';
                }
                else {
                    return '--';
                }
            })
            ->addColumn('action', function ($row) {

                $action = '<div class="task_view">

                <div class="dropdown">
                    <a class="task_view_more d-flex align-items-center justify-content-center dropdown-toggle" type="link"
                        id="dropdownMenuLink-' . $row->id . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="icon-options-vertical icons"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuLink-' . $row->id . '" tabindex="0">';

                    $action .= '<a href="' . route('estimate-request.show', [$row->id]) . '" class="dropdown-item openRightModal"><i class="fa fa-eye mr-2"></i>' . __('app.view') . '</a>';

                if ($row->status == 'pending'){
                    if (
                        $this->editEstimatePermission == 'all'
                        || (($this->editEstimatePermission == 'added' || in_array('client', user_roles())) && $row->added_by == user()->id)
                        || (($this->editEstimatePermission == 'owned' || in_array('client', user_roles())) && $row->client_id == user()->id)
                        || (($this->editEstimatePermission == 'both' || in_array('client', user_roles())) && ($row->client_id == user()->id || $row->added_by == user()->id))
                    ) {
                        $action .= '<a class="dropdown-item openRightModal" href="' . route('estimate-request.edit', [$row->id]) . '">
                                <i class="fa fa-edit mr-2"></i>' . trans('app.edit') . ' </a>';
                    }
                }

                if ($row->status != 'accepted'){
                    if ($this->addEstimatePermission == 'all' || $this->addEstimatePermission == 'added'){
                        $action .= '<a class="dropdown-item" href="' . route('estimates.create') . '?estimate-request=' . $row->id . '">
                            <i class="fa fa-plus mr-2"></i>
                            ' . trans('app.create') . ' ' . trans('app.menu.estimate') . '
                        </a>';
                    }
                }

                if (
                    $this->deleteEstimatePermission == 'all'
                    || (($this->deleteEstimatePermission == 'added' || in_array('client', user_roles())) && $row->added_by == user()->id)
                    || (($this->deleteEstimatePermission == 'owned' || in_array('client', user_roles())) && $row->client_id == user()->id)
                    || (($this->deleteEstimatePermission == 'both' || in_array('client', user_roles())) && ($row->client_id == user()->id || $row->added_by == user()->id))
                ) {
                    $action .= '<a class="dropdown-item delete-table-row" href="javascript:;" data-toggle="tooltip"  data-estimate-request-id="' . $row->id . '">
                            <i class="fa fa-trash mr-2"></i>' . trans('app.delete') . '</a>';
                }

                $action .= '</div>
                </div>
            </div>';

                return $action;
            })
            ->addColumn('status1', function ($row) {

                $select = '';

                if ($this->addEstimatePermission == 'all' || $this->addEstimatePermission == 'added'){
                    $disable = in_array($row->status, ['accepted', 'rejected']) ? 'disabled' : '';
                    $select = '<select ' . $disable . ' class="form-control select-picker change-status" data-estimate-id="' . $row->id . '">';

                    if ($row->status == 'pending') {
                        $select .= '<option value="pending" data-content="<i class=\'fa fa-circle mr-2\' style=\'color: #f5c308\'></i> ' . __('app.pending') . '" selected >' . __('app.pending') . '</option>';
                        $select .= '<option value="in process" data-content="<i class=\'fa fa-circle mr-2\' style=\'color: #00b5ff\'></i> ' . __('app.inProcess') . '">' . __('app.inProcess') . '</option>';
                        $select .= '<option value="rejected" data-content="<i class=\'fa fa-circle mr-2\' style=\'color: #d21010\'></i> ' . __('app.rejected') . '">' . __('app.rejected') . '</option>';
                    }
                    elseif ($row->status == 'in process') {
                        $select .= '<option value="in process" data-content="<i class=\'fa fa-circle mr-2\' style=\'color: #00b5ff\'></i> ' . __('app.inProcess') . '" selected >' . __('app.inProcess') . '</option>';
                        $select .= '<option value="rejected" data-content="<i class=\'fa fa-circle mr-2\' style=\'color: #d21010\'></i> ' . __('app.rejected') . '">' . __('app.rejected') . '</option>';
                    }
                    elseif ($row->status == 'rejected') {
                        $select .= '<option value="rejected" data-content="<i class=\'fa fa-circle mr-2\' style=\'color: #d21010\'></i> ' . __('app.rejected') . '" selected >' . __('app.rejected') . '</option>';
                    }
                    elseif ($row->status == 'accepted') {
                        $select .= '<option value="accepted" data-content="<i class=\'fa fa-circle mr-2\' style=\'color: #679c0d\'></i> ' . __('app.accepted') . '" selected >' . __('app.accepted') . '</option>';
                    }

                    $select .= '</select>';
                }
                else{
                    if ($row->status == 'pending') {
                        $select .= '<i class="fa fa-circle mr-1 text-yellow f-10"></i>' . __('app.' . $row->status) . '</label>';
                    }
                    elseif ($row->status == 'in process') {
                        $select .= '<i class="fa fa-circle mr-1 text-blue f-10"></i>' . __('app.inProcess') . '</label>';
                    }
                    elseif ($row->status == 'rejected') {
                        $select .= '<i class="fa fa-circle mr-1 text-red f-10"></i>' . __('app.' . $row->status) . '</label>';
                    }
                    else {
                        $select .= '<i class="fa fa-circle mr-1 text-dark-green f-10"></i>' . __('app.' . $row->status) . '</label>';
                    }
                }

                return $select;
            })
            ->addColumn('status_name', function ($row) {
                return $row->status;
            })
            ->addColumn('early_requirement', function ($row) {
                return $row->early_requirement ?? '--';
            })
            ->rawColumns(['action', 'status1', 'description', 'client', 'project', 'estimate_id']);
    }

    /**
     * Get query source of dataTable.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(EstimateRequest $model)
    {
        $searchText = request('searchText');
        $model = $model->select('estimate_requests.*')
            ->leftJoin('estimates', 'estimates.id', '=', 'estimate_requests.estimate_id')
            ->leftJoin('users', 'users.id', '=', 'estimate_requests.client_id')
            ->leftJoin('currencies', 'currencies.id', '=', 'estimate_requests.currency_id')
            ->leftJoin('projects', 'projects.id', '=', 'estimate_requests.project_id')
            ->leftJoin('client_details', 'client_details.user_id', '=', 'users.id')
            ->withoutGlobalScopes([ActiveScope::class, CompanyScope::class])
            ->where(function ($query) use ($searchText) {
                $query->where('users.name', 'like', '%' . $searchText . '%')
                    ->orWhere('users.email', 'like', '%' . $searchText . '%');
            });

        if (request()->has('client') && request()->client != 'all') {
            $model = $model->whereHas('client', function ($query) {
                $query->where('id', request()->client);
            });
        }

        if (request()->has('status') && request()->status != 'all') {
            $model = $model->where('estimate_requests.status', request()->status);
        }

        return $model;

    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        $dataTable = $this->setBuilder('estimate-request-table', 0)
            ->parameters([
                'initComplete' => 'function () {
                    window.LaravelDataTables["estimate-request-table"].buttons().container()
                    .appendTo("#table-actions")
                }',
                'fnDrawCallback' => 'function( oSettings ) {
                    $("body").tooltip({
                        selector: \'[data-toggle="tooltip"]\'
                    });
                    $(".select-picker").selectpicker();
                }',
            ]);

        if (canDataTableExport()) {
            $dataTable->buttons(Button::make(['extend' => 'excel', 'text' => '<i class="fa fa-file-export"></i> ' . trans('app.exportExcel')]));
        }

        return $dataTable;
    }

    /**
     * Get columns.
     *
     * @return array
     */
    protected function getColumns()
    {
        return [
            '#' => ['data' => 'id', 'name' => 'id', 'visible' => false],
            __('app.clientName') => ['data' => 'client', 'name' => 'users.name', 'title' => __('app.clientName')],
            __('app.description') => ['data' => 'description', 'name' => 'id', 'title' => __('app.description')],
            __('modules.estimateRequest.estimatedBudget') => ['data' => 'estimated_budget', 'name' => 'estimated_budget', 'title' => __('modules.estimateRequest.estimatedBudget')],
            __('app.project') => ['data' => 'project', 'name' => 'projects.project_name', 'title' => __('app.project')],
            __('app.estimate') => ['data' => 'estimate_id', 'name' => 'estimate_id', 'title' => __('app.estimate')],
            __('app.status') => ['data' => 'status1', 'name' => 'status','width' => '10%', 'exportable' => false, 'visible' => true, 'title' => __('app.status')],
            __('modules.estimateRequest.earlyRequirement') => ['data' => 'early_requirement', 'name' => 'early_requirement',  'visible' => false],
            __('modules.estimateRequest.estimateRequest') . ' ' . __('app.status') => ['data' => 'status_name', 'name' => 'status', 'visible' => false, 'exportable' => true, 'title' => __('modules.estimateRequest.estimateRequest') . ' ' . __('app.status')],
            Column::computed('action', __('app.action'))
                ->exportable(false)
                ->printable(false)
                ->orderable(false)
                ->searchable(false)
                ->width(100)
                ->addClass('text-right pr-20')
        ];
    }

}
