<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Carbon;

class BackgroundJobDashboardController extends Controller
{
    /**
     * Display the background jobs dashboard.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // Get log file path
        $logPath = Config::get('background-jobs.log_path', storage_path('logs/background_jobs.log'));

        // Parse logs into job records
        $jobs = $this->parseLogFile($logPath);

        // Apply filters
        if ($request->has('status') && !empty($request->status)) {
            $jobs = array_filter($jobs, function ($job) use ($request) {
                return $job['status'] === $request->status;
            });
        }

        if ($request->has('class') && !empty($request->class)) {
            $jobs = array_filter($jobs, function ($job) use ($request) {
                return stripos($job['class'], $request->class) !== false;
            });
        }

        if ($request->has('date_from') && !empty($request->date_from)) {
            $dateFrom = Carbon::parse($request->date_from)->startOfDay();
            $jobs = array_filter($jobs, function ($job) use ($dateFrom) {
                return Carbon::parse($job['timestamp'])->gte($dateFrom);
            });
        }

        if ($request->has('date_to') && !empty($request->date_to)) {
            $dateTo = Carbon::parse($request->date_to)->endOfDay();
            $jobs = array_filter($jobs, function ($job) use ($dateTo) {
                return Carbon::parse($job['timestamp'])->lte($dateTo);
            });
        }

        // Sort jobs by timestamp (newest first)
        usort($jobs, function ($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });

        // Calculate stats
        $totalJobs = count($jobs);
        $completedJobs = count(array_filter($jobs, function ($job) {
            return $job['status'] === 'COMPLETED';
        }));
        $runningJobs = count(array_filter($jobs, function ($job) {
            return $job['status'] === 'RUNNING';
        }));
        $failedJobs = count(array_filter($jobs, function ($job) {
            return $job['status'] === 'FAILED';
        }));

        // Return view with data
        return view('background-jobs.index', compact(
            'jobs',
            'totalJobs',
            'completedJobs',
            'runningJobs',
            'failedJobs'
        ));
    }

    /**
     * Display detailed information about a specific job.
     *
     * @param string $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        // Get log file paths
        $logPath = Config::get('background-jobs.log_path', storage_path('logs/background_jobs.log'));
        $errorLogPath = Config::get('background-jobs.error_log_path', storage_path('logs/background_jobs_errors.log'));

        // Parse logs into job records
        $jobs = $this->parseLogFile($logPath);

        // Find the specific job
        $job = null;
        foreach ($jobs as $jobItem) {
            if ($jobItem['id'] === $id) {
                $job = $jobItem;
                break;
            }
        }

        // If job not found, return 404
        if (!$job) {
            abort(404, 'Job not found');
        }

        // If job failed, get error details
        if ($job['status'] === 'FAILED' && File::exists($errorLogPath)) {
            $errorContent = File::get($errorLogPath);
            $pattern = "/\[.*?\] \[{$job['class']}::{$job['method']}\] Error: (.*?) \| Params:/";

            if (preg_match($pattern, $errorContent, $matches)) {
                $job['error_message'] = $matches[1];
            }
        }

        // Build timeline
        $job['timeline'] = [];
        foreach ($jobs as $jobItem) {
            if ($jobItem['id'] === $id) {
                $job['timeline'][] = [
                    'type' => strtolower($jobItem['status']),
                    'message' => "Job {$jobItem['status']}",
                    'timestamp' => $jobItem['timestamp']
                ];
            }
        }

        // Get log entries
        if (File::exists($logPath)) {
            $logContent = File::get($logPath);
            $pattern = "/\[.*?\] \[.*?\] \[{$job['class']}::{$job['method']}\] Params:/";

            if (preg_match_all($pattern, $logContent, $matches, PREG_OFFSET_CAPTURE)) {
                $job['log_entries'] = '';
                foreach ($matches[0] as $match) {
                    $offset = $match[1];
                    $line = substr($logContent, $offset, strpos($logContent, "\n", $offset) - $offset);
                    $job['log_entries'] .= $line . "\n";
                }
            }
        }

        // Return view with data
        return view('background-jobs.show', compact('job'));
    }

    /**
     * Cancel a running job.
     *
     * @param string $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function cancel($id)
    {
        // Implementation for cancelling jobs would depend on how jobs are tracked
        // This is a placeholder implementation

        return redirect()->route('background-jobs.show', $id)
            ->with('success', 'Job cancelled successfully');
    }

    /**
     * Retry a failed job.
     *
     * @param string $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function retry($id)
    {
        // Get log file path
        $logPath = Config::get('background-jobs.log_path', storage_path('logs/background_jobs.log'));

        // Parse logs to find the job
        $jobs = $this->parseLogFile($logPath);

        // Find the specific job
        $job = null;
        foreach ($jobs as $jobItem) {
            if ($jobItem['id'] === $id) {
                $job = $jobItem;
                break;
            }
        }

        // If job not found, return 404
        if (!$job) {
            abort(404, 'Job not found');
        }

        // Retry the job using runBackgroundJob helper
        runBackgroundJob($job['class'], $job['method'], json_decode($job['params'], true) ?? []);

        return redirect()->route('background-jobs.show', $id)
            ->with('success', 'Job retried successfully');
    }

    /**
     * Parse the log file into job records.
     *
     * @param string $logPath
     * @return array
     */
    protected function parseLogFile($logPath)
    {
        $jobs = [];

        // Check if log file exists
        if (!File::exists($logPath)) {
            return $jobs;
        }

        // Read log file
        $logContent = File::get($logPath);
        $logLines = explode("\n", $logContent);

        // Parse each line
        foreach ($logLines as $line) {
            if (empty(trim($line))) {
                continue;
            }

            // Parse log line - format: [timestamp] [status] [class::method] Params: {...}
            if (preg_match('/\[(.*?)\] \[(.*?)\] \[(.*?)::(.*?)\] Params: (.*)/', $line, $matches)) {
                $timestamp = $matches[1];
                $status = $matches[2];
                $class = $matches[3];
                $method = $matches[4];
                $params = $matches[5];

                // Generate a unique ID for the job based on class, method, and params
                $id = md5($class . $method . $params . $timestamp);

                // Create or update job record
                $jobs[$id] = [
                    'id' => $id,
                    'timestamp' => $timestamp,
                    'status' => $status,
                    'class' => $class,
                    'method' => $method,
                    'params' => $params,
                    'retry_count' => isset($jobs[$id]) ? ($jobs[$id]['retry_count'] + 1) : 0,
                    'created_at' => isset($jobs[$id]) ? $jobs[$id]['created_at'] : $timestamp,
                    'updated_at' => $timestamp,
                ];
            }
        }

        return array_values($jobs);
    }
}
