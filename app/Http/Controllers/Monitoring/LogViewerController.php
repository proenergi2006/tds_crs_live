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

        $requestedFile = $request->string('file')->toString();
        $file = $requestedFile;

        if ($file === '') {
            $files = $this->fileReader->listFiles();

            if (empty($files)) {
                return response()->json(['data' => []]);
            }

            $file = $files[0]['filename'];
        }

        $entries = $this->fileReader->read($file);

        if (empty($entries) && $requestedFile !== '') {
            return response()->json(['message' => 'Log file tidak ditemukan.'], 404);
        }

        $parsed = $this->parser->parseMany($entries);

        return response()->json(['data' => $parsed]);
    }

    public function files(Request $request)
    {
        if ($request->user()->id_role !== 1) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return response()->json(['data' => $this->fileReader->listFiles()]);
    }
}
