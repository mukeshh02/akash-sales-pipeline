<?php

namespace Modules\AkashSalesPipeline\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\AkashSalesPipeline\Models\Template;

class TemplateController extends Controller
{
    /**
     * List all templates.
     */
    public function index()
    {
        $this->seedIfEmpty();
        return response()->json(Template::all());
    }

    /**
     * Update a template.
     */
    public function update(Request $request, Template $template)
    {
        $request->validate([
            'content' => 'required|string',
            'is_active' => 'required|boolean',
        ]);

        $template->update([
            'content' => $request->content,
            'is_active' => $request->is_active,
        ]);

        return response()->json($template);
    }

    /**
     * Seed default templates if the table is empty.
     */
    protected function seedIfEmpty()
    {
        if (Template::count() === 0) {
            $defaults = [
                [
                    'name' => 'intro',
                    'content' => "Hello {name},\n\nThis is {salesman_name} from {company_name}. I'm reaching out regarding your interest in our Camera Sales services. How can I help you today?",
                    'is_active' => true,
                ],
                [
                    'name' => 'sample',
                    'content' => "Hi {name},\n\nI've shared some samples of our work with you. Please let me know if you have any questions about our quality or style.",
                    'is_active' => true,
                ],
                [
                    'name' => 'estimate',
                    'content' => "Dear {name},\n\nBased on your requirements for the {event_type} on {event_date}, here is our estimated budget: {budget}. Let's discuss if this fits your plan.",
                    'is_active' => true,
                ],
                [
                    'name' => 'followup',
                    'content' => "Hello {name},\n\nJust checking in to see if you had a chance to review the estimate I sent. Looking forward to hearing from you!",
                    'is_active' => true,
                ],
                [
                    'name' => 'lost',
                    'content' => "Hi {name},\n\nI'm sorry we couldn't work together this time. We've updated our records. Feel free to reach out if you need anything in the future!",
                    'is_active' => true,
                ],
            ];

            foreach ($defaults as $data) {
                Template::create($data);
            }
        }
    }
}
