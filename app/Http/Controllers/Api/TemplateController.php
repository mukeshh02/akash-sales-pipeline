<?php

namespace Modules\ACP_Sales_Guide\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\ACP_Sales_Guide\Models\Template;

class TemplateController extends Controller
{
    public function index()
    {
        $this->seedDefaultsIfEmpty();
        return response()->json(Template::all());
    }

    public function update(Request $request, Template $template)
    {
        $request->validate([
            'content'   => 'required|string',
            'is_active' => 'required|boolean',
        ]);

        $template->update($request->only('content', 'is_active'));
        return response()->json($template);
    }

    /**
     * Seed 5 default templates on fresh install.
     * Safe to call repeatedly — only runs when table is empty.
     */
    private function seedDefaultsIfEmpty(): void
    {
        if (Template::count() > 0) return;

        $defaults = [
            ['name' => 'intro',    'content' => "Hello {name},\n\nThis is {salesman_name} from {company_name}. I'm reaching out regarding your interest in our services. How can I help you today?",   'is_active' => true],
            ['name' => 'sample',   'content' => "Hi {name},\n\nI've shared some samples of our work with you. Please let me know if you have any questions!",                                          'is_active' => true],
            ['name' => 'estimate', 'content' => "Dear {name},\n\nBased on your requirements for {event_type} on {event_date}, here is our estimate: {budget}. Let's discuss if this fits your plan.", 'is_active' => true],
            ['name' => 'followup', 'content' => "Hello {name},\n\nJust checking in to see if you had a chance to review our estimate. Looking forward to hearing from you!",                         'is_active' => true],
            ['name' => 'lost',     'content' => "Hi {name},\n\nSorry we couldn't work together this time. Feel free to reach out anytime in the future!",                                            'is_active' => true],
        ];

        foreach ($defaults as $data) {
            Template::create($data);
        }
    }
}
