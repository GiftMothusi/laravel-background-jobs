<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Details - Background Jobs Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Job Details</h1>
            <div>
                <a href="{{ url('background-jobs') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Job Details Card -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Job Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <dl class="row">
                            <dt class="col-sm-4">ID:</dt>
                            <dd class="col-sm-8">{{ $job['id'] ?? 'N/A' }}</dd>

                            <dt class="col-sm-4">Class:</dt>
                            <dd class="col-sm-8">{{ $job['class'] ?? 'N/A' }}</dd>

                            <dt class="col-sm-4">Method:</dt>
                            <dd class="col-sm-8">{{ $job['method'] ?? 'N/A' }}</dd>

                            <dt class="col-sm-4">Parameters:</dt>
                            <dd class="col-sm-8">
                                <pre class="mb-0"><code>{{ json_encode($job['params'] ?? [], JSON_PRETTY_PRINT) }}</code></pre>
                            </dd>
                        </dl>
                    </div>
                    <div class="col-md-6">
                        <dl class="row">
                            <dt class="col-sm-4">Status:</dt>
                            <dd class="col-sm-8">
                                <span class="badge bg-{{ $job['status'] === 'COMPLETED' ? 'success' : ($job['status'] === 'RUNNING' ? 'warning' : 'danger') }}">
                                    {{ $job['status'] ?? 'Unknown' }}
                                </span>
                            </dd>

                            <dt class="col-sm-4">Created At:</dt>
                            <dd class="col-sm-8">{{ $job['created_at'] ?? 'N/A' }}</dd>

                            <dt class="col-sm-4">Updated At:</dt>
                            <dd class="col-sm-8">{{ $job['updated_at'] ?? 'N/A' }}</dd>

                            <dt class="col-sm-4">Retry Count:</dt>
                            <dd class="col-sm-8">{{ $job['retry_count'] ?? 0 }}</dd>

                            <dt class="col-sm-4">Priority:</dt>
                            <dd class="col-sm-8">{{ $job['priority'] ?? 'N/A' }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <div class="d-flex justify-content-end">
                    @if($job['status'] === 'RUNNING')
                    <form method="POST" action="{{ url('background-jobs/'.$job['id'].'/cancel') }}" class="d-inline me-2">
                        @csrf
                        <button type="submit" class="btn btn-danger">Cancel Job</button>
                    </form>
                    @endif
                    @if($job['status'] === 'FAILED')
                    <form method="POST" action="{{ url('background-jobs/'.$job['id'].'/retry') }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-warning">Retry Job</button>
                    </form>
                    @endif
                </div>
            </div>
        </div>

        <!-- Execution Timeline -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Execution Timeline</h5>
            </div>
            <div class="card-body">
                <ul class="list-group">
                    @forelse ($job['timeline'] ?? [] as $event)
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between">
                            <div>
                                <span class="badge bg-{{ $event['type'] === 'start' ? 'primary' : ($event['type'] === 'success' ? 'success' : 'danger') }} me-2">
                                    {{ ucfirst($event['type']) }}
                                </span>
                                {{ $event['message'] }}
                            </div>
                            <div class="text-muted">
                                {{ $event['timestamp'] }}
                            </div>
                        </div>
                    </li>
                    @empty
                    <li class="list-group-item text-center">No timeline events available</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <!-- Error Details -->
        @if($job['status'] === 'FAILED')
        <div class="card mb-4">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0">Error Details</h5>
            </div>
            <div class="card-body">
                <h6>Error Message:</h6>
                <div class="alert alert-danger">
                    {{ $job['error_message'] ?? 'Unknown error' }}
                </div>

                @if(!empty($job['error_trace']))
                <h6>Stack Trace:</h6>
                <div class="bg-light p-3 rounded overflow-auto" style="max-height: 300px;">
                    <pre class="mb-0"><code>{{ $job['error_trace'] }}</code></pre>
                </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Log Entries -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Log Entries</h5>
            </div>
            <div class="card-body">
                <div class="bg-light p-3 rounded overflow-auto" style="max-height: 400px;">
                    <pre class="mb-0"><code>{{ $job['log_entries'] ?? 'No log entries available' }}</code></pre>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
