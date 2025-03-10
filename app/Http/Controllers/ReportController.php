<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReportController extends Controller
{
    public function index()
    {
        $apiKey = env('MAILCHIMP_API_KEY');
        $server = env('MAILCHIMP_SERVER_PREFIX'); // Example: us12
    
        $url = "https://{$server}.api.mailchimp.com/3.0/lists";
    
        $response = Http::withBasicAuth('anystring', $apiKey)->get($url);
    
        if ($response->failed()) {
            return back()->with('error', 'Failed to fetch audiences from Mailchimp.');
        }
    
        $allAudiences = $response->json()['lists'] ?? [];
    
        // Filter to only include lists with "WIN AUDIENCE" in the name
        $audiences = array_filter($allAudiences, function ($audience) {
            return stripos($audience['name'], 'WIN AUDIENCE') !== false;
        });
    
        return view('report', compact('audiences'));
    }

    public function getSubscribers($audienceId)
    {
        $apiKey = env('MAILCHIMP_API_KEY');
        $server = env('MAILCHIMP_SERVER_PREFIX');
    
        $url = "https://{$server}.api.mailchimp.com/3.0/lists/{$audienceId}/members?count=5000&status=subscribed";
        
        $response = Http::withBasicAuth('anystring', $apiKey)->get($url);
    
        if ($response->failed()) {
            Log::error("Mailchimp API request failed: " . $response->body());
            return response()->json([]);
        }
    
        $data = $response->json();
        $members = collect($data['members'] ?? []);
    
        $subscribers = $members->map(function ($subscriber) {
            $address = $subscriber['merge_fields']['ADDRESS'] ?? [];
    
            return [
                'location' => $subscriber['merge_fields']['EVENTSLOCA'] ?? 'N/A',
                'email' => $subscriber['email_address'],
                'fname' => $subscriber['merge_fields']['FNAME'] ?? 'N/A',
                'lname' => $subscriber['merge_fields']['LNAME'] ?? 'N/A',
                'phone' => $subscriber['merge_fields']['PHONE'] ?? 'N/A',
                'age' => $subscriber['merge_fields']['AGE'] ?? 'N/A',
                'gender' => $subscriber['merge_fields']['GENDER'] ?? 'N/A',
                'address' => isset($address['addr1']) ? 
                    trim("{$address['addr1']} {$address['addr2']}, {$address['city']}, {$address['state']} {$address['zip']}, {$address['country']}") 
                    : '',
            ];
        });
    
        Log::info("Total subscribers fetched: " . $subscribers->count());
    
        return response()->json($subscribers);
    }
    
    

}
