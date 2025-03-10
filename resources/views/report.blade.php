<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('USA Mailchimp Audience') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Selection Card -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form id="audienceForm">
                        <label for="audience">Select Audience:</label>
                        <select name="audience" id="audience" class="border p-2 ms-3">
                            @foreach($audiences as $audience)
                                <option value="{{ $audience['id'] }}">{{ $audience['name'] }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded ml-2">
                            Enter
                        </button>
                    </form>
                </div>
            </div>

            <!-- Loading Spinner -->
            <div id="loadingSpinner" class="text-center mt-6 hidden">
                <div class="border-t-4 border-blue-500 border-solid rounded-full w-12 h-12 animate-spin mx-auto"></div>
                <p class="mt-2 text-gray-600">Loading...</p>
            </div>

            <!-- Dynamic Table -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mt-6 hidden" id="subscribersCard">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-semibold mb-4">Subscribers List</h3>
                        <button id="generateCSV" class="bg-green-500 text-white px-4 py-2 rounded ml-2 hidden">
                            Generate CSV
                        </button>
                    </div>
                    <table class="min-w-full bg-white border border-gray-300">
                        <thead>
                            <tr id="tableHeaders" class="bg-gray-200">
                                <!-- Dynamic Headers will be inserted here -->
                            </tr>
                        </thead>
                        <tbody id="subscribersTable">
                            <!-- Dynamic Data will be inserted here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript for AJAX Request and CSV Export -->
    <script>
        document.getElementById('audienceForm').addEventListener('submit', function(e) {
            e.preventDefault();
            let audienceId = document.getElementById('audience').value;

            // Show the loading spinner and hide the table
            document.getElementById('loadingSpinner').classList.remove('hidden');
            document.getElementById('subscribersCard').classList.add('hidden');

            fetch(`report/mailchimp/subscribers/${audienceId}`)
                .then(response => response.json())
                .then(data => {
                    let tableHeaders = document.getElementById('tableHeaders');
                    let tableBody = document.getElementById('subscribersTable');
                    let generateCSVButton = document.getElementById('generateCSV');

                    tableHeaders.innerHTML = ''; // Clear headers
                    tableBody.innerHTML = ''; // Clear previous data

                    if (data.length === 0) {
                        tableBody.innerHTML = '<tr><td colspan="100%" class="text-center p-4">No subscribers found</td></tr>';
                        generateCSVButton.classList.add('hidden');
                    } else {
                        // Get headers dynamically from the first subscriber
                        let headers = Object.keys(data[0]);

                        // Populate table headers
                        headers.forEach(header => {
                            tableHeaders.innerHTML += `<th class="border px-4 py-2">${header.replace('_', ' ').toUpperCase()}</th>`;
                        });

                        // Populate table data
                        data.forEach(subscriber => {
                            let row = '<tr>';
                            headers.forEach(header => {
                                row += `<td class="border px-4 py-2">${subscriber[header] || ''}</td>`;
                            });
                            row += '</tr>';
                            tableBody.innerHTML += row;
                        });

                        // Show CSV button
                        generateCSVButton.classList.remove('hidden');
                        generateCSVButton.dataset.data = JSON.stringify(data);
                    }

                    // Hide loading spinner and show table
                    document.getElementById('loadingSpinner').classList.add('hidden');
                    document.getElementById('subscribersCard').classList.remove('hidden');
                })
                .catch(error => console.error('Error fetching subscribers:', error));
        });

        // CSV Export Function
        document.getElementById('generateCSV').addEventListener('click', function() {
        let jsonData = JSON.parse(this.dataset.data);
        let headers = Object.keys(jsonData[0]);
        
        // Add "Tags" column to headers
        headers.push("Tags");
        
        let csvRows = [];
        csvRows.push(headers.join(',')); // Add headers to CSV

        jsonData.forEach(row => {
            console.log(row);
            let values = headers.map(header => {
                if (header === "Tags") {
                    let tags = [];
                    
                    // Check if audience is WAFT
                    let audienceName = document.getElementById('audience').selectedOptions[0].text;
                    if (audienceName === "WAFT USA 2025 (WIN AUDIENCE)") {
                        tags.push("2025", "INT - ALL", "FILM TOUR - WAFT");
                        
                        // Country condition
                        if (!row.address || /US|USA/i.test(row.address)) {
                            tags.push("COUNTRY - USA");
                        } else {
                            tags.push("COUNTRY - CANADA");
                        }

                        // Extract city from event location
                         if (row.location) {
                            let city = row.location.split(/,| - /)[0].trim().toUpperCase();
                            tags.push(`SHOW - ${city}`);
                            tags.push(`SOURCE - WAFT ${city} COMP 2025`);
                        }
                    } 
                    else if (audienceName === 'F3T USA 2025 (WIN AUDIENCE)') {
                        tags.push("2025", "INT - FLY FISHING", "FILM TOUR - FLY FISHING");
                        
                        // Country condition
                        if (!row.address || /US|USA/i.test(row.address)) {
                            tags.push("COUNTRY - USA");
                        } else {
                            tags.push("COUNTRY - CANADA");
                        }

                        // Extract city from event location
                         if (row.location) {
                            let city = row.location.split(/,| - /)[0].trim().toUpperCase();
                            tags.push(`SHOW - ${city}`);
                            tags.push(`SOURCE - F3T ${city} COMP 2025`);
                        }
                    }

                    return `"${tags.join(', ')}"`;
                }
                
                return `"${row[header] || ''}"`;
            });

            csvRows.push(values.join(','));
        });

        // Create CSV file
        let csvContent = "data:text/csv;charset=utf-8," + csvRows.join("\n");
        let encodedUri = encodeURI(csvContent);
        let link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "subscribers.csv");
        document.body.appendChild(link);
        link.click();
    });

    </script>
</x-app-layout>
