<?php

namespace App\Http\Controllers;

use App\Models\PunchDetail;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Carbon\CarbonInterval;
use Illuminate\Http\Request;

class PunchDetailController extends Controller
{
    public function punchIn(Request $request)
    {
        $dt = Carbon::now()->startOfDay();
        $edt = Carbon::now()->endOfDay();
        $alreadyPunchedIn = PunchDetail::whereBetween('created_at', [$dt, $edt])
            ->where('user_id', auth()->id())
            ->whereNotNull('punch_in')
            ->whereNull('punch_out')
            ->exists();

        if ($alreadyPunchedIn) {
            $this->setResponse(true, "Please punch out first.");
            return response()->json($this->_response, 200);
        }

        PunchDetail::create([
            "user_id" => auth()->id(),
            "punch_in" => Carbon::now(),
        ]);

        $this->setResponse(false, "Punch in successful.");
        return response()->json($this->_response, 200);
    }

    public function punchOut(Request $request)
    {
        $dt = Carbon::now()->startOfDay();
        $edt = Carbon::now()->endOfDay();

        $alreadyPunchedOut = PunchDetail::whereBetween('created_at', [$dt, $edt])
            ->where('user_id', auth()->id())
            ->whereNotNull('punch_in')
            ->whereNull('punch_out')
            ->exists();

        if (!$alreadyPunchedOut) {
            $this->setResponse(true, "Please punch in first.");
            return response()->json($this->_response, 200);
        }

        $punchRecord = PunchDetail::whereBetween('created_at', [$dt, $edt])
            ->where('user_id', auth()->id())
            ->whereNotNull('punch_in')
            ->whereNull('punch_out')
            ->first();

        $startHour = $punchRecord->punch_in;
        $endHour = Carbon::now();

        $diff = $startHour->floatDiffInMinutes($endHour);
        $diff = floatval(number_format($diff, 2));
        // $diff = $startHour->diffForHumans($endHour, CarbonInterface::DIFF_ABSOLUTE);
        // $diff = $startHour->longAbsoluteDiffForHumans($endHour);
        // dd(CarbonInterval::minutes(90)->cascade()->total('hours'));
        // dd(CarbonInterval::createFromFormat('H:i:s', 90));

        $punchRecord->punch_out = $endHour;
        $punchRecord->total_work_hour = $diff;
        $punchRecord->save();

        $this->setResponse(false, "Punch out successful.");
        return response()->json($this->_response, 200);
    }

    public function getTodaysDetails()
    {
        $dt = Carbon::now()->startOfDay();
        $edt = Carbon::now()->endOfDay();

        $punchDetails = PunchDetail::whereBetween('created_at', [$dt, $edt])
            ->where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();

        $workMinutes = 0;
        $liveWorkMinutes = 0;
        $breakMinutes = 0;
        $punchEntries = [];
        $count = 1;
        $firstPunchIn = null;

        if ($punchDetails->isNotEmpty()) {
            foreach ($punchDetails as $details) {
                $punchEntries[] = [
                    "punch_in" => $details->punch_in_usertimezone,
                    "punch_out" => $details->punch_out_usertimezone,
                ];
                if (is_null($details->punch_out)) {
                    $diff = $details->punch_in->floatDiffInMinutes(Carbon::now());
                    $diff = floatval(number_format($diff, 2));
                    $liveWorkMinutes += $diff;
                } else {
                    $liveWorkMinutes += $details->total_work_hour;
                    $workMinutes += $details->total_work_hour;
                }

                if ($count == 1) {
                    $firstPunchIn = $details->punch_in;
                }
            }

            /** get last punch out to check break hours if last punch out is not set then set last punch in as punch out **/
            $lastPunchIn = PunchDetail::orderBy('punch_in', 'desc')->first();
            $lastPunchOut = $lastPunchIn->punch_out != null ?  $lastPunchIn->punch_out : $lastPunchIn->punch_in;
            if (!is_null($lastPunchOut)) {
                $totalMinutes = $firstPunchIn->floatDiffInMinutes($lastPunchOut);
                $totalMinutes = floatval(number_format($totalMinutes, 2));
                $breakMinutes = $totalMinutes - $workMinutes;
            }
        }

        $row['total_work_hours'] = gmdate("H:i", ($liveWorkMinutes * 60)) . " Hours";
        $row['total_break_hours'] = gmdate("H:i", ($breakMinutes * 60)) . " Hours";
        $row['punch_entries'] = $punchEntries;

        $this->_response['data'] = $row;
        $this->setResponse(false, "");
        return response()->json($this->_response, 200);
    }
}
