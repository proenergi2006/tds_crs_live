<?php

namespace App\Http\Controllers\Monitoring;

use Illuminate\Http\Request;
use App\Services\Monitoring\LogFileReader;
use App\Services\Monitoring\LogParser;
use App\Http\Controllers\Controller;

class LogViewerController extends Controller
{
    public function __construct(
        protected LogFileReader $fileReader,
        protected LogParser $parser,
    ) {}

    public function index(Request $request)
    {
        if ($request->user()->id_role !== 1) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $limit = min($request->integer('limit', 100), 500);

        $entries = $this->fileReader->tail($limit);
        $parsed  = $this->parser->parseMany($entries);

        return response()->json(['data' => $parsed]);
    }
}
