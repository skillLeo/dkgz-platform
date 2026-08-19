<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;

abstract class Controller
{
    // Laravel 13 no longer folds these into the base controller. Policy checks
    // are the enforcement point for every gated route, so they belong here
    // rather than being imported case by case.
    use AuthorizesRequests, ValidatesRequests;
}
