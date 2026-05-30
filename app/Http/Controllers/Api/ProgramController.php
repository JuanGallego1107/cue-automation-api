<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProgramResource;
use App\Models\Program;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProgramController extends Controller
{
    /**
     * Display a listing of active programs.
     */
    public function index(): AnonymousResourceCollection
    {
        $programs = Program::where('is_active', true)->get();

        return ProgramResource::collection($programs);
    }
}
