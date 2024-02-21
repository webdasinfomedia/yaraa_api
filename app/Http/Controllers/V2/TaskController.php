<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class TaskController extends Controller
{
    public function csvImport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'csv_file' => 'required|file',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $file = $request->csv_file;
            $path = "task/csv/";
            $fileFullName = $file->getClientOriginalName();
            $fileName = str_replace(' ', '_', pathinfo($fileFullName, PATHINFO_FILENAME));
            $filePath = $path . $fileName . '-' . getUniqueStamp() . '.csv';
            $file->storeAs('public', $filePath);

            //import csv
            $csvFile = $filePath;

            if (Storage::disk('public')->exists($csvFile)) {
                $csvFile = Storage::disk('public')->path($csvFile);
                $file = fopen($csvFile, 'r');

                fgetcsv($file); // to skip first row
                while (($data = fgetcsv($file, 100, ",")) !== FALSE) {
                    $task = Task::create([
                        'name' => $data[0],
                        'priority' => $data[1],
                        'visibility' => "private",
                        'recurrence' => "no",
                        'created_by' => Auth::user()->id,
                        'assigned_by' => Auth::user()->email,
                        'reminder' => false,
                        'start_date' => null,
                        'status' => "pending",
                    ]);

                    //add assign members to task
                    $assignee = array_filter(explode(',', $data[2]));
                    $assignee[] = auth()->user()->email; //keep task owner as member
                    $task->sync($assignee, [], 'assignedTo')->syncProjectMember();
                    $task->skipUser(auth()->id())->sendTaskMail(false);

                    /** convert due_date from user TZ to UTC TZ and save **/
                    $currentDate = Carbon::now();
                    $dueDate = $currentDate->addDay();
                    $formattedDueDate = $dueDate->format('Y-m-d h:i A');
                    $date = Carbon::createFromFormat('Y-m-d H:i A', $formattedDueDate, getUserTimezone());
                    $date->setTimezone('UTC');
                    $task->due_date = $date;

                    $task->save();
                }

                Storage::disk('public')->delete($csvFile);
            }

            $this->setResponse(false, "Tasks imported successfully");
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }
}
