<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Health Summary</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #0f172a;
            margin: 32px;
            line-height: 1.5;
        }
        .header {
            border-bottom: 3px solid #1d4ed8;
            padding-bottom: 16px;
            margin-bottom: 24px;
        }
        .header h1 {
            margin: 0 0 4px;
            font-size: 28px;
        }
        .header p {
            margin: 0;
            color: #475569;
        }
        .section {
            margin-bottom: 24px;
        }
        .section h2 {
            font-size: 18px;
            margin: 0 0 10px;
            color: #1e3a8a;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }
        .card {
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 12px 14px;
            background: #f8fafc;
        }
        .label {
            font-size: 12px;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 6px;
        }
        .value {
            font-size: 18px;
            font-weight: 700;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        th, td {
            border: 1px solid #cbd5e1;
            padding: 10px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background: #eff6ff;
        }
        ul {
            margin: 8px 0 0 18px;
            padding: 0;
        }
        .muted {
            color: #64748b;
        }
    </style>
</head>
<body>
    @php
        $latestCheckup = $patient->healthCheckups->first();
        $medicalHistory = $patient->medicalHistory;
        $patientName = $patient->user->name ?: ('Patient #' . $patient->id);
        $patientEmail = $patient->user->email ?: 'No email recorded';
    @endphp

    <div class="header">
        <h1>{{ $patientName }}</h1>
        <p>Patient Health Summary</p>
        <p class="muted">Generated on {{ now()->format('d M Y h:i A') }}</p>
    </div>

    <div class="section">
        <h2>Patient Details</h2>
        <div class="grid">
            <div class="card">
                <div class="label">Email</div>
                <div class="value">{{ $patientEmail }}</div>
            </div>
            <div class="card">
                <div class="label">Demographics</div>
                <div class="value">{{ $patient->gender }}, {{ $patient->age }} years old</div>
            </div>
            <div class="card">
                <div class="label">Height / Weight</div>
                <div class="value">{{ $patient->height }} cm / {{ $patient->weight }} kg</div>
            </div>
            <div class="card">
                <div class="label">BMI</div>
                <div class="value">{{ number_format($patient->bmi, 1) }}</div>
            </div>
        </div>
    </div>

    <div class="section">
        <h2>Latest Check-up</h2>
        <div class="grid">
            <div class="card">
                <div class="label">Check-up Date</div>
                <div class="value">{{ $latestCheckup ? \Carbon\Carbon::parse($latestCheckup->checkup_date)->format('d M Y') : 'No data' }}</div>
            </div>
            <div class="card">
                <div class="label">Blood Pressure</div>
                <div class="value">{{ $latestCheckup?->blood_pressure ?? 'N/A' }}</div>
            </div>
            <div class="card">
                <div class="label">Haemoglobin</div>
                <div class="value">{{ $latestCheckup?->haemoglobin ?? 'N/A' }}</div>
            </div>
            <div class="card">
                <div class="label">Blood Sugar</div>
                <div class="value">{{ $latestCheckup?->blood_sugar ?? 'N/A' }}</div>
            </div>
            <div class="card">
                <div class="label">Sodium</div>
                <div class="value">{{ $latestCheckup?->sodium ?? 'N/A' }}</div>
            </div>
            <div class="card">
                <div class="label">Total Cholesterol</div>
                <div class="value">{{ $latestCheckup?->cholesterol ?? 'N/A' }}</div>
            </div>
        </div>
    </div>

    <div class="section">
        <h2>Medical History</h2>
        <table>
            <tr>
                <th>Hypertension</th>
                <td>{{ optional($medicalHistory)->hypertension ?: 'None' }}</td>
            </tr>
            <tr>
                <th>Diabetes</th>
                <td>{{ optional($medicalHistory)->diabetes ?: 'None' }}</td>
            </tr>
            <tr>
                <th>Allergies</th>
                <td>{{ optional($medicalHistory)->allergies ?: 'No known allergies' }}</td>
            </tr>
            <tr>
                <th>Drug Allergies</th>
                <td>{{ optional($medicalHistory)->drug_allergies ?: 'No known drug allergies' }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h2>Medication Summary</h2>
        @if($patient->medications->isNotEmpty())
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Dosage</th>
                        <th>Frequency / Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($patient->medications as $medication)
                        <tr>
                            <td>{{ $medication->name }}</td>
                            <td>{{ $medication->dosage ?: 'N/A' }}</td>
                            <td>{{ $medication->frequency ?? $medication->notes ?? 'N/A' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="muted">No medications recorded.</p>
        @endif
    </div>

    <div class="section">
        <h2>Recent Check-up History</h2>
        @if($patient->healthCheckups->isNotEmpty())
            <ul>
                @foreach($patient->healthCheckups->take(5) as $checkup)
                    <li>
                        {{ \Carbon\Carbon::parse($checkup->checkup_date)->format('d M Y') }}:
                        BP {{ $checkup->blood_pressure ?? 'N/A' }},
                        Sugar {{ $checkup->blood_sugar ?? 'N/A' }},
                        Cholesterol {{ $checkup->cholesterol ?? 'N/A' }}
                    </li>
                @endforeach
            </ul>
        @else
            <p class="muted">No check-up history available.</p>
        @endif
    </div>
</body>
</html>
