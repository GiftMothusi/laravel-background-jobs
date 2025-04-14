<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Background Jobs Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .job-card {
            transition: all 0.3s ease;
        }
        .job-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .status-running {
            background-color: #fff3cd;
        }
        .status-completed {
            background-color: #d1e7dd;
        }
        .status-failed {
            background-color: #f8d7da;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Background Jobs Dashboard</h1>
            <div>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#filterModal">
                    <i class="bi bi-funnel"></i> Filter
                </button>
                <a href="{{ url('background-jobs/refresh') }}" class="btn btn-outline-secondary ms-2">
                    <i class="bi bi-arrow-clockwise"></i> Refresh
                </a>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h5 class="card-title">Total Jobs</h5>
                        <h2>{{ $totalJobs ?? 0 }}</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h5 class="card-title">Completed</h5>
                        <h2>{{ $completedJobs ?? 0 }}</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning">
                    <div class="card-body text-center">
                        <h5 class="card-title">Running</h5>
                        <h2>{{ $runningJobs ?? 0 }}</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body text-center">
                        <h5 class="card-title">Failed</h5>
                        <h2>{{ $failedJobs ?? 0 }}</h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Jobs Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Recent Jobs</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Class</th>
                                <th>Method</th>
                                <th>Status</th>
                                <th>Retry Count</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($jobs ?? [] as $job)
                            <tr class="status-{{ strtolower($job['status'] ?? 'unknown') }}">
                                <td>{{ $job['timestamp'] ?? 'N/A' }}</td>
                                <td>{{ $job['class'] ?? 'N/A' }}</td>
                                <td>{{ $job['method'] ?? 'N/A' }}</td>
                                <td>
                                    <span class="badge bg-{{ $job['status'] === 'COMPLETED' ? 'success' : ($job['status'] === 'RUNNING' ? 'warning' : 'danger') }}">
                                        {{ $job['status'] ?? 'Unknown' }}
                                    </span>
                                </td>
                                <td>{{ $job['retry_count'] ?? 0 }}</td>
                                <td>
                                    <a href="{{ url('background-jobs/'.$job['id']) }}" class="btn btn-sm btn-info">
                                        Details
                                    </a>
                                    @if($job['status'] === 'RUNNING')
                                    <form method="POST" action="{{ url('background-jobs/'.$job['id'].'/cancel') }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-danger">Cancel</button>
                                    </form>
                                    @endif
                                    @if($job['status'] === 'FAILED')
                                    <form method="POST" action="{{ url('background-jobs/'.$job['id'].'/retry') }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-warning">Retry</button>
                                    </form>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center">No jobs found</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Filter Modal -->
        <div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="filterModalLabel">Filter Jobs</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ url('background-jobs') }}" method="GET">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">All Statuses</option>
                                    <option value="RUNNING">Running</option>
                                    <option value="COMPLETED">Completed</option>
                                    <option value="FAILED">Failed</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="class" class="form-label">Class</label>
                                <input type="text" class="form-control" id="class" name="class" placeholder="Filter by class name">
                            </div>
                            <div class="mb-3">
                                <label for="date_from" class="form-label">Date From</label>
                                <input type="date" class="form-control" id="date_from" name="date_from">
                            </div>
                            <div class="mb-3">
                                <label for="date_to" class="form-label">Date To</label>
                                <input type="date" class="form-control" id="date_to" name="date_to">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
