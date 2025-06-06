<?php

namespace KmApiDocVendor\LaravelApiDocs\Http\Controllers;

use Illuminate\Routing\Controller;

class DocsController extends Controller
{
    public function index()
    {
        return view('kmapidocs::index');
    }
}
