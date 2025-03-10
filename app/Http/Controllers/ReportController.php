<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

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
        $allSubscribers = collect();
        $offset = 0;
        $count = 1000; // Number of records per request

        do {
            $url = "https://{$server}.api.mailchimp.com/3.0/lists/{$audienceId}/members?count={$count}&offset={$offset}";
            $response = Http::withBasicAuth('anystring', $apiKey)->get($url);

            if ($response->failed()) {
                return response()->json([]);
            }

            $data = $response->json();
            $members = collect($data['members'] ?? []);
            
            if ($members->isEmpty()) {
                break;
            }

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

            $allSubscribers = $allSubscribers->concat($subscribers);
            $offset += $count;

        } while ($members->count() === $count);

        return response()->json($allSubscribers);
    }
    

}
