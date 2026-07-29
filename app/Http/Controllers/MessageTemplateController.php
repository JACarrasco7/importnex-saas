<?php

namespace App\Http\Controllers;

use App\Models\MessageTemplate;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MessageTemplateController extends Controller
{
    public function index(Request $request): Response
    {
        $templates = MessageTemplate::query()
            ->when($request->input('language'), fn($q, $l) => $q->where('language', $l))
            ->when($request->input('category'), fn($q, $c) => $q->where('category', $c))
            ->when($request->input('search'), function($q, $s) {
                $q->where(function($sub) use ($s) {
                    $sub->where('name', 'like', "%$s%")
                        ->orWhere('content', 'like', "%$s%");
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $languages = ['es', 'en', 'de'];
        $categories = ['seller', 'client'];

        return Inertia::render('MessageTemplates/Index', [
            'templates' => $templates,
            'languages' => $languages,
            'categories' => $categories,
            'filters' => $request->only(['language', 'category', 'search']),
        ]);
    }

    public function show(MessageTemplate $messageTemplate): Response
    {
        return Inertia::render('MessageTemplates/Show', [
            'template' => $messageTemplate,
        ]);
    }
}
