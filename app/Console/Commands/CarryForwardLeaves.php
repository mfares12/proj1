<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\EmployeeLeaveQuota;
use App\Models\LeaveType;
use App\Models\Company;
use Illuminate\Support\Carbon;

class CarryForwardLeaves extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'carry-forward-leave';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'update carry forward leaves';

    /**
     * Execute the console command.
     *
     * @return mixed
     */

    public function handle()
    {
        Company::active()->chunk(50, function ($companies) {

            foreach ($companies as $company) {

                $this->getStartMonthAndYear($company);
            }
        });

        return Command::SUCCESS;
    }

    public function getStartMonthAndYear(Company $company){

        // Fetch all users
        $users = User::withoutGlobalScopes()->onlyEmployee()->with(['leaves', 'leaveTypes', 'leaveTypes.leaveType', 'employeeDetail'])->get();

        foreach ($users as $user) {

            if ($company->leaves_start_from == 'year_start') {
                $today = Carbon::now($company->timezone)->startOfDay();
                $startMonth = Carbon::create($today->year, (int)$company->year_starts_from, tz: $company->timezone)->startOfDay();
                $startYear = now()->year;
            }else{
                $joiningYear = $user->employeeDetail->joining_date->format('Y');
                if($joiningYear < now()->year){
                    $startMonth = Carbon::create(now()->year, 1, 1, 0, 0, 0, $company->timezone);
                    $startYear = now()->year;
                }else{
                    $startMonth = $user->employeeDetail->joining_date;
                    $startYear = $user->employeeDetail->joining_date->format('Y');
                }
            }

            // Loop through each month from the starting month until the current month
            for ($month = $startMonth->copy(); $month < now()->startOfMonth(); $month->addMonth()) {

                $this->carryForwardLeave($user, $month, $startYear);

            }
        }
    }

    private function carryForwardLeave($user, $month, $startYear){

        $leaveTypes = LeaveType::where('unused_leave', 'carry forward')->where('leavetype', 'monthly')->get();

        foreach($leaveTypes as $leaveType){

            $employeeLeaveQuota = EmployeeLeaveQuota::where('leave_type_id', $leaveType->id)->where('user_id', $user->id)
                ->where('carry_forward_status', 'like', '%'.$month->format('F Y').'%')->exists();

            if (!$employeeLeaveQuota && !$month->isCurrentMonth()) {

                // Get Approved Leave's count for user
                $approvedLeaves = $user->leaves()
                ->whereYear('leave_date', $startYear)
                ->whereMonth('leave_date', $month)
                ->where('leave_type_id', $leaveType->id)
                ->where('status', 'approved')
                ->count();

                $totalRemainingLeaves = $leaveType->no_of_leaves - $approvedLeaves;

                if($totalRemainingLeaves > 0){

                    $leaveQuota = EmployeeLeaveQuota::where('user_id', $user->id)->where('leave_type_id', $leaveType->id)->first();

                    if($leaveQuota){

                        $carryForwardStatus = json_decode($leaveQuota->carry_forward_status, true) ?? [];
                        $carryForwardStatus[$month->format('F Y')] = true;

                        $leaveQuota->no_of_leaves = $leaveQuota->no_of_leaves + $totalRemainingLeaves;
                        $leaveQuota->leaves_remaining = $leaveQuota->no_of_leaves - $leaveQuota->leaves_used;
                        $leaveQuota->carry_forward_status = json_encode($carryForwardStatus);
                        $leaveQuota->save();
                        $this->info('Carry forward leaves updated successfully.');
                    }
                }
            }
        }
    }

}
