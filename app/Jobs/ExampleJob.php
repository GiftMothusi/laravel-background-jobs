<?php

namespace App\Jobs;

class ExampleJob
{
    public function handle($param1 = null, $param2 = null)
    {
        \Illuminate\Support\Facades\Log::info("ExampleJob executed with params: " . json_encode([$param1, $param2]));
        return "Job completed with: " . $param1 . ", " . $param2;
    }
}
