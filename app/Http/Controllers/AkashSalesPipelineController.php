<?php

namespace Modules\AkashSalesPipeline\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class AkashSalesPipelineController extends Controller
{
    public function index(): View
    {
        return view('akashsalespipeline::index');
    }
}
